/**
 * One control per field type. Every control receives { field, value, onChange, id }.
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	CheckboxControl,
	RadioControl,
	SelectControl,
	Spinner,
	TextareaControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

const asList = ( value ) => {
	if ( Array.isArray( value ) ) {
		return value;
	}

	return value === '' || value === null || value === undefined ? [] : [ value ];
};

function Text( { field, value, onChange, id, type = 'text' } ) {
	return (
		<TextControl
			id={ id }
			type={ type }
			value={ value ?? '' }
			onChange={ onChange }
			placeholder={ field.placeholder || '' }
			maxLength={ field.max_length || undefined }
			__nextHasNoMarginBottom
		/>
	);
}

function Textarea( { field, value, onChange, id } ) {
	return <TextareaControl id={ id } value={ value ?? '' } onChange={ onChange } rows={ field.rows || 4 } __nextHasNoMarginBottom />;
}

/**
 * Classic TinyMCE editor (wp.oldEditor), the same one WordPress uses for term descriptions.
 * Falls back to a textarea when the editor scripts are not loaded.
 */
function Wysiwyg( { value, onChange, id } ) {
	const latest = useRef( onChange );
	latest.current = onChange;

	useEffect( () => {
		const editor = window.wp?.oldEditor;

		if ( ! editor ) {
			return undefined;
		}

		editor.initialize( id, {
			tinymce: {
				wpautop: true,
				setup: ( instance ) => instance.on( 'change keyup undo redo', () => latest.current( instance.getContent() ) ),
			},
			quicktags: true,
			mediaButtons: true,
		} );

		const textarea = document.getElementById( id );
		const onInput = () => latest.current( textarea.value );
		textarea?.addEventListener( 'input', onInput );

		return () => {
			textarea?.removeEventListener( 'input', onInput );
			editor.remove( id );
		};
	}, [ id ] );

	return <textarea id={ id } className="lb-field__wysiwyg" defaultValue={ value ?? '' } rows={ 8 } />;
}

function NumberInput( { field, value, onChange, id } ) {
	return (
		<TextControl
			id={ id }
			type="number"
			value={ value ?? '' }
			min={ field.min }
			max={ field.max }
			step={ field.step || 1 }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
}

const pad = ( number ) => String( number ).padStart( 2, '0' );

/** Timestamps (store_as = timestamp) are shown in the browser's local time. */
function toInputValue( value, type ) {
	if ( typeof value === 'number' || /^\d{9,}$/.test( String( value ) ) ) {
		const date = new Date( Number( value ) * 1000 );
		const day = `${ date.getFullYear() }-${ pad( date.getMonth() + 1 ) }-${ pad( date.getDate() ) }`;

		return type === 'date' ? day : `${ day }T${ pad( date.getHours() ) }:${ pad( date.getMinutes() ) }`;
	}

	return value ?? '';
}

function DateInput( { field, value, onChange, id } ) {
	const type = { date: 'date', time: 'time', datetime: 'datetime-local' }[ field.type ];

	return <TextControl id={ id } type={ type } value={ toInputValue( value, field.type ) } onChange={ onChange } __nextHasNoMarginBottom />;
}

function Select( { field, value, onChange, id } ) {
	if ( field.multiple ) {
		return (
			<SelectControl
				id={ id }
				multiple
				value={ asList( value ).map( String ) }
				options={ field.options }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		);
	}

	return (
		<SelectControl
			id={ id }
			value={ value ?? '' }
			options={ [ { value: '', label: __( '— Select —', 'libreblocks-core' ) }, ...field.options ] }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
}

function Radio( { field, value, onChange } ) {
	return <RadioControl selected={ value ?? '' } options={ field.options } onChange={ onChange } />;
}

function Checkboxes( { field, value, onChange } ) {
	const selected = asList( value ).map( String );

	return (
		<div className="lb-field__checkboxes">
			{ field.options.map( ( option ) => (
				<CheckboxControl
					key={ option.value }
					label={ option.label }
					checked={ selected.includes( option.value ) }
					onChange={ ( checked ) =>
						onChange( checked ? [ ...selected, option.value ] : selected.filter( ( item ) => item !== option.value ) )
					}
					__nextHasNoMarginBottom
				/>
			) ) }
		</div>
	);
}

function Toggle( { field, value, onChange } ) {
	return (
		<ToggleControl
			label={ field.toggle_label || '' }
			checked={ value === true || value === '1' || value === 1 }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
}

function Color( { value, onChange, id } ) {
	const hex = /^#[0-9a-f]{6}$/i.test( value || '' ) ? value : '#000000';

	return (
		<div className="lb-field__color">
			<input type="color" value={ hex } onChange={ ( event ) => onChange( event.target.value ) } aria-label={ __( 'Pick a color', 'libreblocks-core' ) } />
			<TextControl id={ id } value={ value ?? '' } onChange={ onChange } placeholder="#rrggbb" __nextHasNoMarginBottom />
		</div>
	);
}

/** Thumbnails for attachment IDs, loaded through wp.media. */
function useAttachments( ids ) {
	const [ items, setItems ] = useState( {} );
	const key = ids.join( ',' );

	useEffect( () => {
		ids.forEach( ( attachmentId ) => {
			const attachment = window.wp?.media?.attachment( attachmentId );

			attachment?.fetch().then( () => {
				const data = attachment.toJSON();
				const url = data.sizes?.thumbnail?.url || data.icon || data.url;
				setItems( ( current ) => ( { ...current, [ attachmentId ]: { url, title: data.title || data.filename } } ) );
			} );
		} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ key ] );

	return items;
}

function openMediaFrame( { multiple, type, selected, onSelect } ) {
	const frame = window.wp.media( {
		title: multiple ? __( 'Select images', 'libreblocks-core' ) : __( 'Select a file', 'libreblocks-core' ),
		multiple: multiple ? 'add' : false,
		library: type ? { type } : {},
	} );

	frame.on( 'open', () => {
		const selection = frame.state().get( 'selection' );
		selected.forEach( ( attachmentId ) => selection.add( window.wp.media.attachment( attachmentId ) ) );
	} );

	frame.on( 'select', () => onSelect( frame.state().get( 'selection' ).map( ( item ) => item.id ) ) );
	frame.open();
}

function Media( { field, value, onChange } ) {
	const ids = asList( value ).map( Number ).filter( Boolean );
	const items = useAttachments( ids );
	const multiple = field.type === 'gallery';

	if ( ! window.wp?.media ) {
		return <p>{ __( 'The media library is not available on this screen.', 'libreblocks-core' ) }</p>;
	}

	const remove = ( attachmentId ) => {
		const rest = ids.filter( ( item ) => item !== attachmentId );
		onChange( multiple ? rest : '' );
	};

	return (
		<div className="lb-field__media">
			{ ids.length > 0 && (
				<ul className="lb-field__thumbs">
					{ ids.map( ( attachmentId ) => (
						<li key={ attachmentId }>
							{ items[ attachmentId ] ? <img src={ items[ attachmentId ].url } alt={ items[ attachmentId ].title } /> : <Spinner /> }
							<Button
								icon="no-alt"
								label={ __( 'Remove', 'libreblocks-core' ) }
								onClick={ () => remove( attachmentId ) }
								size="small"
							/>
						</li>
					) ) }
				</ul>
			) }
			<Button
				variant="secondary"
				onClick={ () =>
					openMediaFrame( {
						multiple,
						type: field.mime_type || ( multiple ? 'image' : '' ),
						selected: ids,
						onSelect: ( selected ) => onChange( multiple ? selected : selected[ 0 ] || '' ),
					} )
				}
			>
				{ multiple ? __( 'Add images', 'libreblocks-core' ) : __( 'Choose file', 'libreblocks-core' ) }
			</Button>
		</div>
	);
}

/** Search-as-you-type picker for posts, terms and users (libreblocks/v1/objects). */
function ObjectPicker( { field, value, onChange, id } ) {
	const object = field.type.replace( '_picker', '' );
	const ids = asList( value ).map( Number ).filter( Boolean );
	const [ labels, setLabels ] = useState( {} );
	const [ search, setSearch ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const filters = {
		post_type: [].concat( field.post_type || [] ).join( ',' ),
		taxonomy: [].concat( field.taxonomy || [] ).join( ',' ),
		role: [].concat( field.role || [] ).join( ',' ),
	};

	const request = ( args ) => apiFetch( { path: addQueryArgs( '/libreblocks/v1/objects', { object, ...filters, ...args } ) } );

	useEffect( () => {
		const missing = ids.filter( ( item ) => ! labels[ item ] );

		if ( missing.length ) {
			request( { include: missing.join( ',' ) } ).then( ( items ) =>
				setLabels( ( current ) => ( { ...current, ...Object.fromEntries( items.map( ( item ) => [ item.id, item.label ] ) ) } ) )
			);
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ ids.join( ',' ) ] );

	useEffect( () => {
		if ( search.trim().length < 2 ) {
			setResults( [] );
			return undefined;
		}

		setLoading( true );
		const timer = setTimeout( () => {
			request( { search } )
				.then( setResults )
				.finally( () => setLoading( false ) );
		}, 300 );

		return () => clearTimeout( timer );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ search ] );

	const select = ( item ) => {
		setLabels( ( current ) => ( { ...current, [ item.id ]: item.label } ) );
		onChange( field.multiple ? [ ...ids.filter( ( current ) => current !== item.id ), item.id ] : item.id );
		setSearch( '' );
	};

	const remove = ( removed ) => {
		const rest = ids.filter( ( item ) => item !== removed );
		onChange( field.multiple ? rest : '' );
	};

	return (
		<div className="lb-field__picker">
			{ ids.length > 0 && (
				<ul className="lb-field__chips">
					{ ids.map( ( item ) => (
						<li key={ item }>
							<span>{ labels[ item ] || `#${ item }` }</span>
							<Button icon="no-alt" size="small" label={ __( 'Remove', 'libreblocks-core' ) } onClick={ () => remove( item ) } />
						</li>
					) ) }
				</ul>
			) }
			{ ( field.multiple || ids.length === 0 ) && (
				<div className="lb-field__search">
					<TextControl
						id={ id }
						value={ search }
						onChange={ setSearch }
						placeholder={ __( 'Type to search…', 'libreblocks-core' ) }
						__nextHasNoMarginBottom
					/>
					{ loading && <Spinner /> }
					{ results.length > 0 && (
						<ul className="lb-field__results" role="listbox">
							{ results.map( ( item ) => (
								<li key={ item.id }>
									<Button variant="link" onClick={ () => select( item ) }>
										{ item.label }
									</Button>
								</li>
							) ) }
						</ul>
					) }
				</div>
			) }
		</div>
	);
}

function Icon( { value, onChange, id } ) {
	return (
		<div className="lb-field__icon">
			{ value && /^dashicons-/.test( value ) && <span className={ `dashicons ${ value }` } aria-hidden="true" /> }
			<TextControl id={ id } value={ value ?? '' } onChange={ onChange } placeholder="dashicons-star-filled" __nextHasNoMarginBottom />
		</div>
	);
}

function Html( { field } ) {
	return <RawHTML className="lb-field__html">{ field.content || '' }</RawHTML>;
}

export const CONTROLS = {
	text: Text,
	email: ( props ) => <Text { ...props } type="email" />,
	url: ( props ) => <Text { ...props } type="url" />,
	textarea: Textarea,
	wysiwyg: Wysiwyg,
	number: NumberInput,
	date: DateInput,
	time: DateInput,
	datetime: DateInput,
	select: Select,
	radio: Radio,
	checkbox: Checkboxes,
	switch: Toggle,
	color: Color,
	media: Media,
	gallery: Media,
	post_picker: ObjectPicker,
	term_picker: ObjectPicker,
	user_picker: ObjectPicker,
	icon: Icon,
	html: Html,
};

export function rowTitle( field, row, index ) {
	const firstText = ( field.fields || [] ).find( ( child ) => [ 'text', 'email', 'url', 'number' ].includes( child.type ) );
	const label = firstText ? row?.[ firstText.name ] : '';

	/* translators: %d: item number */
	return label ? String( label ) : sprintf( __( 'Item %d', 'libreblocks-core' ), index + 1 );
}
