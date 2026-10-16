<?php
/**
 * Evaluates "show this field when..." rules against a set of values.
 *
 * Format:
 *   [
 *     'relation' => 'AND' | 'OR',
 *     'rules'    => [ [ 'field' => 'kind', 'operator' => '=', 'value' => 'house' ], ... ],
 *   ]
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields;

defined( 'ABSPATH' ) || exit;

final class Conditions {

	public const OPERATORS = [ '=', '!=', 'in', 'not_in', 'contains', 'empty', 'not_empty', '>', '>=', '<', '<=' ];

	/**
	 * @param array<string, mixed> $conditions
	 * @param array<string, mixed> $values
	 */
	public static function matches( array $conditions, array $values ): bool {
		$rules = array_filter( (array) ( $conditions['rules'] ?? [] ), 'is_array' );

		if ( ! $rules ) {
			return true;
		}

		$any = 'OR' === strtoupper( (string) ( $conditions['relation'] ?? 'AND' ) );

		foreach ( $rules as $rule ) {
			$result = self::rule( $rule, $values );

			if ( $any && $result ) {
				return true;
			}

			if ( ! $any && ! $result ) {
				return false;
			}
		}

		return ! $any;
	}

	/**
	 * @param array<string, mixed> $rule
	 * @param array<string, mixed> $values
	 */
	private static function rule( array $rule, array $values ): bool {
		$actual   = $values[ (string) ( $rule['field'] ?? '' ) ] ?? null;
		$expected = $rule['value'] ?? null;

		switch ( (string) ( $rule['operator'] ?? '=' ) ) {
			case '!=':
				return ! self::equals( $actual, $expected );
			case 'in':
				return self::intersects( $actual, $expected );
			case 'not_in':
				return ! self::intersects( $actual, $expected );
			case 'contains':
				return is_array( $actual )
					? in_array( (string) $expected, array_map( 'strval', $actual ), true )
					: false !== strpos( (string) $actual, (string) $expected );
			case 'empty':
				return self::is_blank( $actual );
			case 'not_empty':
				return ! self::is_blank( $actual );
			case '>':
				return is_numeric( $actual ) && (float) $actual > (float) $expected;
			case '>=':
				return is_numeric( $actual ) && (float) $actual >= (float) $expected;
			case '<':
				return is_numeric( $actual ) && (float) $actual < (float) $expected;
			case '<=':
				return is_numeric( $actual ) && (float) $actual <= (float) $expected;
			default:
				return self::equals( $actual, $expected );
		}
	}

	/**
	 * @param mixed $actual
	 * @param mixed $expected
	 */
	private static function equals( $actual, $expected ): bool {
		if ( is_bool( $actual ) || is_bool( $expected ) ) {
			return (bool) $actual === filter_var( $expected, FILTER_VALIDATE_BOOLEAN );
		}

		if ( is_array( $actual ) ) {
			return in_array( (string) $expected, array_map( 'strval', $actual ), true );
		}

		return (string) $actual === (string) $expected;
	}

	/**
	 * @param mixed $actual
	 * @param mixed $expected
	 */
	private static function intersects( $actual, $expected ): bool {
		$expected = is_array( $expected ) ? $expected : array_map( 'trim', explode( ',', (string) $expected ) );

		return (bool) array_intersect( array_map( 'strval', (array) $actual ), array_map( 'strval', $expected ) );
	}

	/**
	 * @param mixed $value
	 */
	private static function is_blank( $value ): bool {
		return null === $value || '' === $value || [] === $value || false === $value;
	}
}
