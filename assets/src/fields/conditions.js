/**
 * Mirror of LibreBlocks\Core\Fields\Conditions::matches() so hidden fields
 * disappear as soon as the user changes the field they depend on.
 */

const isBlank = ( value ) =>
	value === null || value === undefined || value === '' || value === false || ( Array.isArray( value ) && value.length === 0 );

const toList = ( value ) => ( Array.isArray( value ) ? value : String( value ?? '' ).split( ',' ).map( ( item ) => item.trim() ) ).map( String );

const equals = ( actual, expected ) => {
	if ( typeof actual === 'boolean' || typeof expected === 'boolean' ) {
		return Boolean( actual ) === [ true, 'true', '1', 1, 'yes', 'on' ].includes( expected );
	}

	if ( Array.isArray( actual ) ) {
		return actual.map( String ).includes( String( expected ) );
	}

	return String( actual ?? '' ) === String( expected ?? '' );
};

const isNumber = ( value ) => value !== '' && value !== null && ! isNaN( Number( value ) );

function rule( { field, operator = '=', value: expected }, values ) {
	const actual = values[ field ];

	switch ( operator ) {
		case '!=':
			return ! equals( actual, expected );
		case 'in':
			return toList( actual ).some( ( item ) => toList( expected ).includes( item ) );
		case 'not_in':
			return ! toList( actual ).some( ( item ) => toList( expected ).includes( item ) );
		case 'contains':
			return Array.isArray( actual ) ? actual.map( String ).includes( String( expected ) ) : String( actual ?? '' ).includes( String( expected ) );
		case 'empty':
			return isBlank( actual );
		case 'not_empty':
			return ! isBlank( actual );
		case '>':
			return isNumber( actual ) && Number( actual ) > Number( expected );
		case '>=':
			return isNumber( actual ) && Number( actual ) >= Number( expected );
		case '<':
			return isNumber( actual ) && Number( actual ) < Number( expected );
		case '<=':
			return isNumber( actual ) && Number( actual ) <= Number( expected );
		default:
			return equals( actual, expected );
	}
}

export function matches( conditions, values ) {
	const rules = ( conditions?.rules || [] ).filter( Boolean );

	if ( ! rules.length ) {
		return true;
	}

	return String( conditions.relation || 'AND' ).toUpperCase() === 'OR'
		? rules.some( ( item ) => rule( item, values ) )
		: rules.every( ( item ) => rule( item, values ) );
}
