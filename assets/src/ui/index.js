/**
 * LibreBlocks admin UI kit, shared by every LibreBlocks plugin so all screens
 * look the same. Products import it as "@libreblocks/ui" (webpack alias) and
 * bundle it; it only depends on WordPress packages provided as globals.
 */
import { Button, Dropdown, SearchControl, Spinner, ToggleControl } from '@wordpress/components';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DASHICONS } from './dashicons';
import './ui.scss';

/** Dashicon by name, with or without the "dashicons-" prefix. */
export function Icon( { name, className = '' } ) {
	if ( ! name ) {
		return null;
	}

	if ( /^(data:|https?:)/.test( name ) ) {
		return <img className={ `lb-icon lb-icon--img ${ className }` } src={ name } alt="" aria-hidden="true" />;
	}

	return <span className={ `lb-icon dashicons ${ name.startsWith( 'dashicons-' ) ? name : `dashicons-${ name }` } ${ className }` } aria-hidden="true" />;
}

/** Rounded square with an icon, used on cards and headers. */
export function IconTile( { icon, tone = 'accent', size = 'md' } ) {
	return (
		<span className={ `lb-tile lb-tile--${ tone } lb-tile--${ size }` }>
			<Icon name={ icon } />
		</span>
	);
}

export function Badge( { tone = 'neutral', children } ) {
	return <span className={ `lb-badge lb-badge--${ tone }` }>{ children }</span>;
}

/**
 * Page frame: product bar with logo, tabs to the product's screens and optional actions.
 *
 * @param {Object} props
 * @param {Object} props.product { name, icon } where icon is a dashicon or data URI.
 * @param {Array}  props.tabs    [ { key, label, href, icon } ]
 * @param {string} props.current Key of the active tab.
 */
export function AppShell( { product, tabs = [], current, actions, children } ) {
	return (
		<div className="lb-app">
			<header className="lb-bar">
				<span className="lb-bar__logo">
					<Icon name={ product.icon } />
				</span>
				<span className="lb-bar__name">{ product.name }</span>
				<nav className="lb-bar__nav" aria-label={ product.name }>
					{ tabs.map( ( tab ) => (
						<a key={ tab.key } href={ tab.href } className={ `lb-bar__tab${ tab.key === current ? ' is-active' : '' }` } aria-current={ tab.key === current ? 'page' : undefined }>
							{ tab.icon && <Icon name={ tab.icon } /> }
							{ tab.label }
						</a>
					) ) }
				</nav>
				{ actions && <div className="lb-bar__actions">{ actions }</div> }
			</header>
			<main className="lb-page">{ children }</main>
		</div>
	);
}

export function PageHeader( { title, subtitle, icon, badge, back, actions } ) {
	return (
		<div className="lb-header">
			{ back && (
				<Button className="lb-header__back" icon="arrow-left-alt2" label={ back.label } onClick={ back.onClick } />
			) }
			{ icon && <IconTile icon={ icon } size="lg" /> }
			<div className="lb-header__text">
				<h1>
					{ title }
					{ badge }
				</h1>
				{ subtitle && <p>{ subtitle }</p> }
			</div>
			{ actions && <div className="lb-header__actions">{ actions }</div> }
		</div>
	);
}

export function StatGrid( { children } ) {
	return <div className="lb-stats">{ children }</div>;
}

export function StatCard( { label, value, icon, href } ) {
	const content = (
		<>
			<span className="lb-stat__top">
				<span className="lb-stat__label">{ label }</span>
				{ icon && <Icon name={ icon } /> }
			</span>
			<span className="lb-stat__value">{ value ?? <Spinner /> }</span>
		</>
	);

	return href ? (
		<a className="lb-stat lb-stat--link" href={ href }>
			{ content }
		</a>
	) : (
		<div className="lb-stat">{ content }</div>
	);
}

export function CardGrid( { children, columns = 3 } ) {
	return <div className={ `lb-cards lb-cards--${ columns }` }>{ children }</div>;
}

/** Clickable card for a stored item (post type, field group...). */
export function ItemCard( { icon, title, code, status, description, onClick, actions } ) {
	return (
		<div className="lb-card lb-item">
			<button type="button" className="lb-item__main" onClick={ onClick }>
				<span className="lb-item__head">
					<IconTile icon={ icon } />
					<span className="lb-item__title">
						<strong>{ title }</strong>
						{ code && <code>{ code }</code> }
					</span>
					{ status }
				</span>
				{ description && <span className="lb-item__desc">{ description }</span> }
			</button>
			{ actions && <div className="lb-item__actions">{ actions }</div> }
		</div>
	);
}

/** Feature that can be switched on and off. Without onToggle it shows its badge instead. */
export function ModuleCard( { icon, title, description, badge, checked, onToggle } ) {
	return (
		<div className={ `lb-card lb-module${ checked ? ' is-on' : '' }` }>
			<IconTile icon={ icon } tone={ checked ? 'accent' : 'neutral' } />
			<div className="lb-module__text">
				<strong>{ title }</strong>
				<p>{ description }</p>
			</div>
			{ onToggle ? <ToggleControl checked={ !! checked } onChange={ onToggle } label={ <span className="screen-reader-text">{ title }</span> } __nextHasNoMarginBottom /> : badge }
		</div>
	);
}

export function EmptyState( { icon, title, text, action } ) {
	return (
		<div className="lb-empty">
			<IconTile icon={ icon } size="xl" />
			<h2>{ title }</h2>
			{ text && <p>{ text }</p> }
			{ action }
		</div>
	);
}

/** White card section with an optional title. */
export function Panel( { title, description, children, actions } ) {
	return (
		<section className="lb-card lb-panel">
			{ ( title || actions ) && (
				<div className="lb-panel__head">
					<div>
						{ title && <h2>{ title }</h2> }
						{ description && <p>{ description }</p> }
					</div>
					{ actions }
				</div>
			) }
			<div className="lb-panel__body">{ children }</div>
		</section>
	);
}

/**
 * Editor with a vertical section menu on the left.
 *
 * @param {Object} props
 * @param {Array}  props.sections [ { id, title, icon, hasError } ]
 */
export function SideLayout( { sections, current, onSelect, children } ) {
	return (
		<div className="lb-side">
			<nav className="lb-side__nav">
				{ sections.map( ( section ) => (
					<button
						type="button"
						key={ section.id }
						className={ `lb-side__item${ section.id === current ? ' is-active' : '' }` }
						onClick={ () => onSelect( section.id ) }
						aria-current={ section.id === current ? 'true' : undefined }
					>
						<Icon name={ section.icon } />
						<span>{ section.title }</span>
						{ section.hasError && <span className="lb-side__error" aria-label={ __( 'Has errors', 'libreblocks-core' ) } /> }
					</button>
				) ) }
			</nav>
			<div className="lb-side__content">{ children }</div>
		</div>
	);
}

/** Sticky footer with the save button. Warns before leaving the page with unsaved changes. */
export function SaveBar( { dirty, saving, onSave, onDiscard, message, saveLabel } ) {
	useEffect( () => {
		if ( ! dirty ) {
			return undefined;
		}

		const warn = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};

		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );

	return (
		<div className={ `lb-savebar${ dirty ? ' is-dirty' : '' }` }>
			<span className="lb-savebar__status">
				{ message || ( dirty ? __( 'Unsaved changes', 'libreblocks-core' ) : __( 'All changes saved', 'libreblocks-core' ) ) }
			</span>
			{ dirty && onDiscard && (
				<Button variant="tertiary" onClick={ onDiscard } disabled={ saving }>
					{ __( 'Discard', 'libreblocks-core' ) }
				</Button>
			) }
			<Button variant="primary" onClick={ onSave } isBusy={ saving } disabled={ saving }>
				{ saveLabel || __( 'Save changes', 'libreblocks-core' ) }
			</Button>
		</div>
	);
}

/** Dashicon picker with search. */
export function IconPicker( { label, value, onChange } ) {
	const [ search, setSearch ] = useState( '' );
	const icons = useMemo( () => DASHICONS.filter( ( name ) => name.includes( search.trim().toLowerCase() ) ), [ search ] );

	return (
		<div className="lb-iconpicker">
			{ label && <span className="lb-iconpicker__label">{ label }</span> }
			<Dropdown
				popoverProps={ { placement: 'bottom-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button variant="secondary" className="lb-iconpicker__toggle" onClick={ onToggle } aria-expanded={ isOpen }>
						<Icon name={ value || 'dashicons-admin-post' } />
						<span>{ ( value || 'dashicons-admin-post' ).replace( 'dashicons-', '' ) }</span>
					</Button>
				) }
				renderContent={ ( { onClose } ) => (
					<div className="lb-iconpicker__popover">
						<SearchControl value={ search } onChange={ setSearch } placeholder={ __( 'Search icons', 'libreblocks-core' ) } __nextHasNoMarginBottom />
						<div className="lb-iconpicker__grid">
							{ icons.map( ( name ) => (
								<Button
									key={ name }
									className={ `lb-iconpicker__icon${ value === `dashicons-${ name }` ? ' is-selected' : '' }` }
									label={ name }
									showTooltip
									onClick={ () => {
										onChange( `dashicons-${ name }` );
										onClose();
									} }
								>
									<Icon name={ name } />
								</Button>
							) ) }
						</div>
					</div>
				) }
			/>
		</div>
	);
}

/**
 * Collapsible card section, used to split long editors.
 */
export function Collapsible( { title, count, icon, open: initiallyOpen = false, hasError = false, children } ) {
	const [ open, setOpen ] = useState( initiallyOpen );

	useEffect( () => {
		if ( hasError ) {
			setOpen( true );
		}
	}, [ hasError ] );

	return (
		<section className={ `lb-card lb-collapsible${ open ? ' is-open' : '' }${ hasError ? ' has-error' : '' }` }>
			<button type="button" className="lb-collapsible__head" onClick={ () => setOpen( ! open ) } aria-expanded={ open }>
				<Icon name={ open ? 'arrow-down-alt2' : 'arrow-right-alt2' } className="lb-collapsible__chevron" />
				{ icon && <Icon name={ icon } /> }
				<span className="lb-collapsible__title">{ title }</span>
				{ count !== undefined && <span className="lb-collapsible__count">{ count }</span> }
				{ hasError && <span className="lb-side__error" aria-label={ __( 'Has errors', 'libreblocks-core' ) } /> }
			</button>
			{ open && <div className="lb-collapsible__body">{ children }</div> }
		</section>
	);
}

/**
 * One setting: name and explanation on the left, the control on the right.
 */
export function SettingRow( { label, help, error, children, stacked = false } ) {
	return (
		<div className={ `lb-setting${ stacked ? ' is-stacked' : '' }${ error ? ' has-error' : '' }` }>
			<div className="lb-setting__info">
				<span className="lb-setting__label">{ label }</span>
				{ help && <span className="lb-setting__help">{ help }</span> }
			</div>
			<div className="lb-setting__control">
				{ children }
				{ error && <p className="lb-setting__error">{ error }</p> }
			</div>
		</div>
	);
}

/** Main content plus a sticky sidebar (actions, help). */
export function EditorColumns( { main, aside } ) {
	return (
		<div className="lb-columns">
			<div className="lb-columns__main">{ main }</div>
			<aside className="lb-columns__aside">{ aside }</aside>
		</div>
	);
}
