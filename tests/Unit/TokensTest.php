<?php

use LibreBlocks\Core\Context\ObjectStack;
use LibreBlocks\Core\Tokens\Registry;
use LibreBlocks\Core\Tokens\Token;
use PHPUnit\Framework\TestCase;

final class TokensTest extends TestCase {

	private ObjectStack $stack;
	private Registry $tokens;

	protected function setUp(): void {
		WPStub::reset();
		$this->stack  = new ObjectStack();
		$this->tokens = Registry::with_defaults( $this->stack );
	}

	public function test_renders_known_tokens_and_keeps_unknown_ones(): void {
		WPStub::$user_id = 9;

		$this->assertSame( 'User 9 / {{nope}} / {{ also.unknown|x }}', $this->tokens->render( 'User {{user.id}} / {{nope}} / {{ also.unknown|x }}' ) );
		$this->assertSame( 'no tokens here', $this->tokens->render( 'no tokens here' ) );
	}

	public function test_object_tokens_follow_the_stack(): void {
		WPStub::$queried                 = new WP_Post( [ 'ID' => 1, 'post_title' => 'Page' ] );
		WPStub::$meta['post'][2]['price'] = '350000';

		$this->assertSame( 1, $this->tokens->value( 'object.id' ) );

		$this->stack->with( new WP_Post( [ 'ID' => 2, 'post_title' => 'House' ] ), function () {
			$this->assertSame( 2, $this->tokens->value( 'object.id' ) );
			$this->assertSame( 1, $this->tokens->value( 'queried.id' ) );
			$this->assertSame( 'House costs 350000', $this->tokens->render( '{{object.prop|post_title}} costs {{object.meta|price}}' ) );

			$this->stack->with( [ 'id' => 50, 'room' => 'Suite' ], function () {
				$this->assertSame( 'row', $this->tokens->value( 'object.type' ) );
				$this->assertSame( 'Suite', $this->tokens->value( 'object.meta', 'room' ) );
				$this->assertSame( 2, $this->tokens->value( 'parent.id' ) );
			} );
		} );

		$this->assertSame( 1, $this->tokens->value( 'object.id' ) );
	}

	public function test_resolve_keeps_raw_types_for_single_tokens(): void {
		WPStub::$user_id = 4;
		$this->tokens->add( new Token( 'test.list', fn() => [ 1, 2, 3 ] ) );

		$resolved = $this->tokens->resolve( [
			'author'   => '{{user.id}}',
			'post__in' => ' {{test.list}} ',
			'title'    => 'Posts of {{user.id}}: {{test.list}}',
			'nested'   => [ 'x' => '{{user.id}}' ],
			'number'   => 10,
		] );

		$this->assertSame( 4, $resolved['author'] );
		$this->assertSame( [ 1, 2, 3 ], $resolved['post__in'] );
		$this->assertSame( 'Posts of 4: 1,2,3', $resolved['title'] );
		$this->assertSame( 4, $resolved['nested']['x'] );
		$this->assertSame( 10, $resolved['number'] );
	}

	public function test_sensitive_values_are_never_exposed(): void {
		WPStub::$user_id                                 = 3;
		WPStub::$options['smtp_password']                = 'hunter2';
		WPStub::$options['blogname']                     = 'My site';
		WPStub::$options['lbforge_opts']                 = [ 'phone' => '1234' ];
		WPStub::$meta['user'][3]['session_tokens']       = 'tokens';

		add_filter( 'libreblocks/tokens/allowed_options', fn( $allowed ) => array_merge( $allowed, [ 'lbforge_opts' ] ) );

		$this->assertNull( $this->tokens->value( 'option', 'smtp_password' ) );
		$this->assertSame( 'My site', $this->tokens->value( 'option', 'blogname' ) );
		$this->assertSame( '1234', $this->tokens->value( 'option', 'lbforge_opts:phone' ) );
		$this->assertNull( $this->tokens->value( 'user.meta', 'session_tokens' ) );

		$this->stack->push( new WP_User( 3 ) );
		$this->assertNull( $this->tokens->value( 'object.prop', 'user_pass' ) );
		$this->assertSame( 'User 3', $this->tokens->value( 'object.prop', 'display_name' ) );
	}

	public function test_url_and_date_tokens(): void {
		$_GET['city'] = '<b>Recife</b>';

		$this->assertSame( 'Recife', $this->tokens->value( 'url.param', 'city' ) );
		$this->assertNull( $this->tokens->value( 'url.param', 'missing' ) );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $this->tokens->value( 'date.today' ) );
		$this->assertSame(
			( new DateTimeImmutable( '+7 days', wp_timezone() ) )->format( 'Y-m-d' ),
			$this->tokens->value( 'date.str', '+7 days' )
		);
		$this->assertNull( $this->tokens->value( 'date.str', 'garbage date' ) );
	}

	public function test_value_filter(): void {
		add_filter( 'libreblocks/tokens/value', fn( $value, $name ) => 'user.id' === $name ? 'filtered' : $value );

		$this->assertSame( 'filtered', $this->tokens->value( 'user.id' ) );
	}
}
