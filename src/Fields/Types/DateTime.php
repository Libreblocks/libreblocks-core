<?php
/**
 * Date, time and date-time fields.
 *
 * Values are stored as normalized strings (Y-m-d, H:i, Y-m-d\TH:i) so they sort
 * correctly in meta queries. Arg "store_as" => "timestamp" stores a Unix timestamp instead.
 *
 * @package LibreBlocks\Core
 */

namespace LibreBlocks\Core\Fields\Types;

use LibreBlocks\Core\Fields\Field;
use LibreBlocks\Core\Fields\Registry;

defined( 'ABSPATH' ) || exit;

class DateTime extends BaseType {

	private const FORMATS = [
		'date'     => 'Y-m-d',
		'time'     => 'H:i',
		'datetime' => 'Y-m-d\TH:i',
	];

	public function __construct( private string $type ) {
		if ( ! isset( self::FORMATS[ $type ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Unsupported date type "%s".', $type ) );
		}
	}

	public function type(): string {
		return $this->type;
	}

	public function label(): string {
		switch ( $this->type ) {
			case 'time':
				return __( 'Time', 'libreblocks-core' );
			case 'datetime':
				return __( 'Date & time', 'libreblocks-core' );
			default:
				return __( 'Date', 'libreblocks-core' );
		}
	}

	public function sanitize( $value, Field $field, Registry $registry ) {
		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
			return '';
		}

		$value    = trim( (string) $value );
		$timezone = wp_timezone();

		if ( 'time' !== $this->type && ctype_digit( $value ) ) {
			$date = ( new \DateTimeImmutable( '@' . $value ) )->setTimezone( $timezone );
		} else {
			$date = \DateTimeImmutable::createFromFormat( '!' . self::FORMATS[ $this->type ], $value, $timezone );

			if ( ! $date ) {
				try {
					$date = new \DateTimeImmutable( $value, $timezone );
				} catch ( \Exception $e ) {
					return '';
				}
			}
		}

		if ( 'timestamp' === $field->arg( 'store_as' ) && 'time' !== $this->type ) {
			return $date->getTimestamp();
		}

		return $date->format( self::FORMATS[ $this->type ] );
	}
}
