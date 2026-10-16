<?php

use LibreBlocks\Core\Fields\Conditions;
use PHPUnit\Framework\TestCase;

final class ConditionsTest extends TestCase {

	private function rule( string $operator, $value = null ): array {
		return [ 'rules' => [ [ 'field' => 'x', 'operator' => $operator, 'value' => $value ] ] ];
	}

	public function test_no_rules_always_match(): void {
		$this->assertTrue( Conditions::matches( [], [] ) );
	}

	public function test_operators(): void {
		$this->assertTrue( Conditions::matches( $this->rule( '=', 'a' ), [ 'x' => 'a' ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( '!=', 'a' ), [ 'x' => 'b' ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( 'in', 'a, b' ), [ 'x' => 'b' ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( 'not_in', [ 'a' ] ), [ 'x' => 'c' ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( 'contains', 'b' ), [ 'x' => [ 'a', 'b' ] ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( 'empty' ), [ 'x' => [] ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( 'not_empty' ), [ 'x' => '0' ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( '>', 5 ), [ 'x' => '6' ] ) );
		$this->assertFalse( Conditions::matches( $this->rule( '<=', 5 ), [ 'x' => 'abc' ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( '=', 'true' ), [ 'x' => true ] ) );
		$this->assertTrue( Conditions::matches( $this->rule( '=', 'b' ), [ 'x' => [ 'a', 'b' ] ] ) );
	}

	public function test_relation(): void {
		$rules = [
			[ 'field' => 'a', 'value' => '1' ],
			[ 'field' => 'b', 'value' => '1' ],
		];

		$this->assertFalse( Conditions::matches( [ 'rules' => $rules ], [ 'a' => '1', 'b' => '0' ] ) );
		$this->assertTrue( Conditions::matches( [ 'relation' => 'or', 'rules' => $rules ], [ 'a' => '1', 'b' => '0' ] ) );
		$this->assertFalse( Conditions::matches( [ 'relation' => 'OR', 'rules' => $rules ], [ 'a' => '0', 'b' => '0' ] ) );
	}
}
