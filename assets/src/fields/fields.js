/**
 * LibreBlocks field renderer.
 *
 * Mounts on every element with data-libreblocks-fields='{ "fields": [...], "values": {...}, "input": "<hidden input id>" }'
 * and keeps the hidden input filled with the edited values as JSON, so the
 * surrounding WordPress form (post, term, user, options page) saves them.
 */
import { Button, TabPanel } from '@wordpress/components';
import { createRoot, Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { matches } from './conditions';
import { CONTROLS, rowTitle } from './controls';
import './fields.scss';

let uid = 0;
const nextId = () => `lb-field-${ ++uid }`;

const LAYOUT_ONLY = [ 'html', 'tab' ];

/** Values with defaults applied to every storing field that has no value yet. */
function withDefaults( fields, values ) {
	const result = { ...( values || {} ) };

	fields.forEach( ( field ) => {
		if ( ! LAYOUT_ONLY.includes( field.type ) && result[ field.name ] === undefined && field.default !== null && field.default !== undefined ) {
			result[ field.name ] = field.default;
		}
	} );

	return result;
}

function FieldRow( { field, value, onChange } ) {
	const [ id ] = useState( nextId );
	let control;

	if ( field.type === 'repeater' ) {
		control = <Repeater field={ field } value={ value } onChange={ onChange } />;
	} else if ( field.type === 'group' ) {
		control = <FieldList fields={ field.fields || [] } values={ value || {} } onChange={ onChange } />;
	} else {
		const Control = CONTROLS[ field.type ] || CONTROLS.text;
		control = <Control field={ field } value={ value } onChange={ onChange } id={ id } />;
	}

	return (
		<div className={ `lb-field lb-field--${ field.type } lb-field--w-${ parseInt( field.width, 10 ) || 100 }` }>
			{ field.label && field.type !== 'html' && (
				<label className="lb-field__label" htmlFor={ id }>
					{ field.label }
					{ field.required && <span className="lb-field__required" aria-label={ __( 'required', 'libreblocks-core' ) }> *</span> }
				</label>
			) }
			{ control }
			{ field.description && <p className="lb-field__description" dangerouslySetInnerHTML={ { __html: field.description } } /> }
		</div>
	);
}

function Repeater( { field, value, onChange } ) {
	const rows = Array.isArray( value ) ? value : [];
	const [ open, setOpen ] = useState( () => ( rows.length ? [] : [ 0 ] ) );
	const max = Number( field.max_rows || 0 );

	const update = ( index, row ) => onChange( rows.map( ( current, i ) => ( i === index ? row : current ) ) );
	const remove = ( index ) => onChange( rows.filter( ( _, i ) => i !== index ) );
	const move = ( index, offset ) => {
		const target = index + offset;

		if ( target < 0 || target >= rows.length ) {
			return;
		}

		const next = [ ...rows ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		onChange( next );
	};
	const add = () => {
		onChange( [ ...rows, withDefaults( field.fields || [], {} ) ] );
		setOpen( [ ...open, rows.length ] );
	};
	const toggle = ( index ) => setOpen( open.includes( index ) ? open.filter( ( item ) => item !== index ) : [ ...open, index ] );

	return (
		<div className="lb-repeater">
			{ rows.map( ( row, index ) => (
				<div className="lb-repeater__row" key={ index }>
					<div className="lb-repeater__head">
						<Button variant="link" className="lb-repeater__title" onClick={ () => toggle( index ) } aria-expanded={ open.includes( index ) }>
							{ rowTitle( field, row, index ) }
						</Button>
						<Button icon="arrow-up-alt2" size="small" label={ __( 'Move up', 'libreblocks-core' ) } disabled={ index === 0 } onClick={ () => move( index, -1 ) } />
						<Button icon="arrow-down-alt2" size="small" label={ __( 'Move down', 'libreblocks-core' ) } disabled={ index === rows.length - 1 } onClick={ () => move( index, 1 ) } />
						<Button icon="trash" size="small" isDestructive label={ __( 'Remove item', 'libreblocks-core' ) } onClick={ () => remove( index ) } />
					</div>
					{ open.includes( index ) && (
						<div className="lb-repeater__body">
							<FieldList fields={ field.fields || [] } values={ row } onChange={ ( next ) => update( index, next ) } />
						</div>
					) }
				</div>
			) ) }
			{ ( ! max || rows.length < max ) && (
				<Button variant="secondary" icon="plus-alt2" onClick={ add }>
					{ field.add_label || __( 'Add item', 'libreblocks-core' ) }
				</Button>
			) }
		</div>
	);
}

/** Renders a flat list of fields (tabs are handled by FieldsForm). */
function FieldList( { fields, values, onChange } ) {
	const current = withDefaults( fields, values );

	return (
		<div className="lb-fields">
			{ fields
				.filter( ( field ) => field.type !== 'tab' && matches( field.conditions, current ) )
				.map( ( field, index ) => (
					<FieldRow
						key={ field.name || `${ field.type }-${ index }` }
						field={ field }
						value={ current[ field.name ] }
						onChange={ ( value ) => onChange( { ...current, [ field.name ]: value } ) }
					/>
				) ) }
		</div>
	);
}

/** Splits the top-level fields on "tab" fields. Fields before the first tab stay above the tabs. */
function splitTabs( fields ) {
	const intro = [];
	const tabs = [];

	fields.forEach( ( field, index ) => {
		if ( field.type === 'tab' ) {
			tabs.push( { name: `tab-${ index }`, title: field.label || __( 'Tab', 'libreblocks-core' ), fields: [] } );
		} else if ( tabs.length ) {
			tabs[ tabs.length - 1 ].fields.push( field );
		} else {
			intro.push( field );
		}
	} );

	return { intro, tabs };
}

function FieldsForm( { config } ) {
	const [ values, setValues ] = useState( () => withDefaults( config.fields, config.values ) );
	const { intro, tabs } = splitTabs( config.fields );

	useEffect( () => {
		const input = document.getElementById( config.input );

		if ( input ) {
			input.value = JSON.stringify( values );
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	}, [ values, config.input ] );

	// Children edit the whole object; "tab" lists only show part of it.
	const onChange = ( next ) => setValues( ( current ) => ( { ...current, ...next } ) );

	return (
		<Fragment>
			{ intro.length > 0 && <FieldList fields={ intro } values={ values } onChange={ onChange } /> }
			{ tabs.length > 0 && (
				<TabPanel className="lb-tabs" tabs={ tabs }>
					{ ( tab ) => <FieldList fields={ tab.fields } values={ values } onChange={ onChange } /> }
				</TabPanel>
			) }
		</Fragment>
	);
}

function mountAll() {
	document.querySelectorAll( '[data-libreblocks-fields]:not([data-mounted])' ).forEach( ( element ) => {
		let config;

		try {
			config = JSON.parse( element.dataset.libreblocksFields );
		} catch ( error ) {
			return;
		}

		element.dataset.mounted = '1';
		createRoot( element ).render( <FieldsForm config={ { fields: [], values: {}, ...config } } /> );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mountAll );
} else {
	mountAll();
}

// Lets other scripts mount forms added later (e.g. after an AJAX term insert).
window.libreblocks = { ...( window.libreblocks || {} ), mountFields: mountAll };
