<?php

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;
use PHPUnit\Framework\TestCase;

final class FieldsTest extends TestCase {

	private Registry $fields;

	protected function setUp(): void {
		WPStub::reset();
		$this->fields = Registry::with_defaults();
	}

	private function clean( array $config, $value ) {
		$fields = $this->fields->make_all( [ $config ] );

		return $this->fields->sanitize_values( $fields, [ $config['name'] => $value ] )[ $config['name'] ];
	}

	public function test_registers_all_default_types(): void {
		$this->assertCount( 24, $this->fields->types() );
	}

	public function test_text_strips_tags(): void {
		$this->assertSame( 'Hello', $this->clean( [ 'name' => 't', 'type' => 'text' ], '<b>Hello</b>' ) );
	}

	public function test_number_respects_step(): void {
		$this->assertSame( 4, $this->clean( [ 'name' => 'n', 'type' => 'number' ], '3,6' ) );
		$this->assertSame( 3.6, $this->clean( [ 'name' => 'n', 'type' => 'number', 'step' => 0.1 ], '3,6' ) );
		$this->assertSame( '', $this->clean( [ 'name' => 'n', 'type' => 'number' ], 'abc' ) );
	}

	public function test_choice_keeps_only_known_options(): void {
		$select = [ 'name' => 's', 'type' => 'select', 'options' => [ 'a' => 'A', 'b' => 'B' ] ];

		$this->assertSame( 'a', $this->clean( $select, 'a' ) );
		$this->assertSame( '', $this->clean( $select, 'evil' ) );
		$this->assertSame( [ 'a', 'b' ], $this->clean( $select + [ 'multiple' => true ], [ 'a', 'x', 'b', 'a' ] ) );
		$this->assertSame( [ 'b' ], $this->clean( [ 'name' => 'c', 'type' => 'checkbox', 'options' => "a : A\nb : B" ], 'b' ) );
	}

	public function test_options_can_come_from_a_filter(): void {
		add_filter( 'libreblocks/fields/options', fn( $options, Field $field ) => 'city' === $field->glossary ? [ 'sp' => 'São Paulo' ] : $options );

		$this->assertSame( 'sp', $this->clean( [ 'name' => 'c', 'type' => 'select', 'glossary' => 'city' ], 'sp' ) );
	}

	public function test_dates_are_normalized(): void {
		$this->assertSame( '2026-10-16', $this->clean( [ 'name' => 'd', 'type' => 'date' ], '2026-10-16' ) );
		$this->assertSame( '2026-10-16', $this->clean( [ 'name' => 'd', 'type' => 'date' ], '16 October 2026' ) );
		$this->assertSame( '09:30', $this->clean( [ 'name' => 't', 'type' => 'time' ], '9:30' ) );
		$this->assertSame( '', $this->clean( [ 'name' => 'd', 'type' => 'date' ], 'not a date' ) );
		$this->assertIsInt( $this->clean( [ 'name' => 'd', 'type' => 'datetime', 'store_as' => 'timestamp' ], '2026-10-16T10:00' ) );
	}

	public function test_media_and_pickers_store_ids(): void {
		$this->assertSame( 12, $this->clean( [ 'name' => 'm', 'type' => 'media' ], [ 'id' => '12', 'url' => 'x' ] ) );
		$this->assertSame( [ 3, 5 ], $this->clean( [ 'name' => 'g', 'type' => 'gallery' ], '3,5,3,0' ) );
		$this->assertSame( 7, $this->clean( [ 'name' => 'p', 'type' => 'post_picker' ], [ 7, 8 ] ) );
		$this->assertSame( [ 7, 8 ], $this->clean( [ 'name' => 'p', 'type' => 'post_picker', 'multiple' => true ], [ 7, 8 ] ) );
	}

	public function test_color_rejects_garbage(): void {
		$this->assertSame( '#ff0000', $this->clean( [ 'name' => 'c', 'type' => 'color' ], '#FF0000' ) );
		$this->assertSame( 'rgba(0, 0, 0, .5)', $this->clean( [ 'name' => 'c', 'type' => 'color' ], 'rgba(0, 0, 0, .5)' ) );
		$this->assertSame( '', $this->clean( [ 'name' => 'c', 'type' => 'color' ], 'red;background:url(x)' ) );
	}

	public function test_repeater_drops_empty_rows_and_limits_count(): void {
		$config = [
			'name'     => 'rooms',
			'type'     => 'repeater',
			'max_rows' => 2,
			'fields'   => [
				[ 'name' => 'title', 'type' => 'text' ],
				[ 'name' => 'suite', 'type' => 'switch' ],
			],
		];

		$rows = $this->clean( $config, [
			[ 'title' => 'Room 1', 'suite' => '1' ],
			[ 'title' => '', 'suite' => '1' ],
			[ 'title' => 'Room 2' ],
			[ 'title' => 'Room 3' ],
			'garbage',
		] );

		$this->assertSame( [
			[ 'title' => 'Room 1', 'suite' => true ],
			[ 'title' => 'Room 2', 'suite' => false ],
		], $rows );
	}

	public function test_layout_fields_store_nothing(): void {
		$fields = $this->fields->make_all( [
			[ 'type' => 'tab', 'label' => 'General' ],
			[ 'name' => 'title', 'type' => 'text' ],
			[ 'name' => 'x', 'type' => 'unknown_type' ],
		] );

		$this->assertCount( 2, $fields );
		$this->assertSame( [ 'title' => 'Hi' ], $this->fields->sanitize_values( $fields, [ 'title' => 'Hi', 'x' => 'y' ] ) );
	}

	public function test_required_and_conditional_validation(): void {
		$fields = $this->fields->make_all( [
			[ 'name' => 'kind', 'type' => 'select', 'options' => [ 'house' => 'House', 'flat' => 'Flat' ] ],
			[
				'name'       => 'floor',
				'type'       => 'number',
				'label'      => 'Floor',
				'required'   => true,
				'max'        => 50,
				'conditions' => [ 'rules' => [ [ 'field' => 'kind', 'operator' => '=', 'value' => 'flat' ] ] ],
			],
		] );

		$house = $this->fields->sanitize_values( $fields, [ 'kind' => 'house' ] );
		$this->assertSame( [], $this->fields->validate_values( $fields, $house ) );

		$flat = $this->fields->sanitize_values( $fields, [ 'kind' => 'flat' ] );
		$this->assertSame( [ 'floor' => 'Floor is required.' ], $this->fields->validate_values( $fields, $flat ) );

		$high = $this->fields->sanitize_values( $fields, [ 'kind' => 'flat', 'floor' => 80 ] );
		$this->assertSame( [ 'floor' => 'Floor must be at most 50.' ], $this->fields->validate_values( $fields, $high ) );
	}

	public function test_defaults_apply_when_input_is_missing(): void {
		$fields = $this->fields->make_all( [ [ 'name' => 'status', 'type' => 'radio', 'options' => [ 'a' => 'A', 'b' => 'B' ], 'default' => 'b' ] ] );

		$this->assertSame( [ 'status' => 'b' ], $this->fields->sanitize_values( $fields, [] ) );
	}

	public function test_field_round_trips_to_array(): void {
		$config = Field::from_array( [ 'name' => 'Price', 'type' => 'number', 'step' => 0.01, 'options' => [] ] )->to_array();

		$this->assertSame( 'price', $config['name'] );
		$this->assertSame( 0.01, $config['step'] );
	}

	public function test_schema_for_repeater(): void {
		$field = $this->fields->make_all( [ [ 'name' => 'r', 'type' => 'repeater', 'fields' => [ [ 'name' => 'n', 'type' => 'number' ] ] ] ] )[0];

		$this->assertSame(
			[ 'type' => 'array', 'items' => [ 'type' => 'object', 'properties' => [ 'n' => [ 'type' => 'integer' ] ] ] ],
			$this->fields->get( 'repeater' )->schema( $field, $this->fields )
		);
	}
}
