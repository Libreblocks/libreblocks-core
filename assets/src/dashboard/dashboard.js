/**
 * LibreBlocks dashboard: installed products, environment and the building
 * blocks (field types, tokens) available to them.
 */
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, Spinner } from '@wordpress/components';
import { createRoot, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { AppShell, Badge, CardGrid, Icon, IconTile, PageHeader, Panel, StatCard, StatGrid } from '../ui';
import './dashboard.scss';

const data = window.libreblocksDashboard || {};

/** The family. Installed products come from the REST status; the rest show as "coming soon". */
const CATALOG = [
	{ slug: 'libreforge', name: 'LibreForge', icon: 'hammer', description: __( 'Post types, fields, relations, queries and dynamic listings.', 'libreblocks-core' ) },
	{ slug: 'libresift', name: 'LibreSift', icon: 'filter', description: __( 'AJAX filters for listings.', 'libreblocks-core' ) },
	{ slug: 'libreforms', name: 'LibreForms', icon: 'feedback', description: __( 'Form builder.', 'libreblocks-core' ) },
	{ slug: 'librestay', name: 'LibreStay', icon: 'bed', description: __( 'Nightly bookings.', 'libreblocks-core' ) },
	{ slug: 'libreagenda', name: 'LibreAgenda', icon: 'calendar-alt', description: __( 'Appointments by time slot.', 'libreblocks-core' ) },
];

function greeting() {
	const hour = new Date().getHours();

	if ( hour < 12 ) {
		return __( 'Good morning', 'libreblocks-core' );
	}

	return hour < 18 ? __( 'Good afternoon', 'libreblocks-core' ) : __( 'Good evening', 'libreblocks-core' );
}

function ProductCard( { product, installed } ) {
	let badge = <Badge>{ __( 'Coming soon', 'libreblocks-core' ) }</Badge>;

	if ( installed ) {
		badge = installed.compatible ? (
			<Badge tone="success">{ sprintf( /* translators: %s: version */ __( 'Active · v%s', 'libreblocks-core' ), installed.version ) }</Badge>
		) : (
			<Badge tone="warning">{ __( 'Needs a newer LibreBlocks Core', 'libreblocks-core' ) }</Badge>
		);
	}

	return (
		<div className={ `lb-card lb-product${ installed ? ' is-installed' : '' }` }>
			<div className="lb-product__head">
				<IconTile icon={ product.icon } tone={ installed ? 'accent' : 'neutral' } size="lg" />
				{ badge }
			</div>
			<h3>{ product.name }</h3>
			<p>{ product.description }</p>
			{ installed?.url && (
				<Button variant="secondary" href={ installed.url } className="lb-product__open">
					{ sprintf( /* translators: %s: product name */ __( 'Open %s', 'libreblocks-core' ), product.name ) }
				</Button>
			) }
		</div>
	);
}

function Dashboard() {
	const [ status, setStatus ] = useState( null );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		apiFetch( { path: '/libreblocks/v1/status' } )
			.then( setStatus )
			.catch( ( err ) => setError( err.message || __( 'Could not load the status.', 'libreblocks-core' ) ) );
	}, [] );

	const installed = Object.fromEntries( ( status?.products || [] ).map( ( product ) => [ product.slug, product ] ) );
	const active = ( status?.products || [] ).filter( ( product ) => product.compatible ).length;

	return (
		<AppShell product={ { name: 'LibreBlocks', icon: data.icon || 'screenoptions' } } tabs={ [ { key: 'dashboard', label: __( 'Dashboard', 'libreblocks-core' ), href: '#', icon: 'dashboard' } ] } current="dashboard">
			<PageHeader
				title={ data.user ? `${ greeting() }, ${ data.user }` : greeting() }
				subtitle={ status ? sprintf( /* translators: 1: core version, 2: WordPress version, 3: PHP version */ __( 'Core %1$s · WordPress %2$s · PHP %3$s', 'libreblocks-core' ), status.core, status.wp, status.php ) : '' }
			/>

			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }

			{ status?.pending_tables.length > 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'Some database tables are waiting to be created or updated. Reload any admin page to run the update.', 'libreblocks-core' ) }
				</Notice>
			) }

			<StatGrid>
				<StatCard label={ __( 'Active plugins', 'libreblocks-core' ) } value={ status ? active : null } icon="admin-plugins" />
				<StatCard label={ __( 'Field types', 'libreblocks-core' ) } value={ status?.field_types.length } icon="forms" />
				<StatCard label={ __( 'Dynamic tokens', 'libreblocks-core' ) } value={ status?.tokens.length } icon="editor-code" />
				<StatCard label={ __( 'Core version', 'libreblocks-core' ) } value={ status?.core } icon="shield-alt" />
			</StatGrid>

			<h2 className="lb-section-title">{ __( 'Products', 'libreblocks-core' ) }</h2>
			<CardGrid columns={ 3 }>
				{ CATALOG.map( ( product ) => (
					<ProductCard key={ product.slug } product={ product } installed={ installed[ product.slug ] } />
				) ) }
			</CardGrid>

			{ ! status && ! error && <Spinner /> }

			{ status && (
				<div className="lb-dashboard__reference">
					<Panel title={ sprintf( /* translators: %d: number of field types */ __( 'Field types (%d)', 'libreblocks-core' ), status.field_types.length ) } description={ __( 'Available in field groups, options pages and forms.', 'libreblocks-core' ) }>
						<ul className="lb-chips">
							{ status.field_types.map( ( type ) => (
								<li key={ type.type }>{ type.label }</li>
							) ) }
						</ul>
					</Panel>

					<Panel title={ sprintf( /* translators: %d: number of tokens */ __( 'Dynamic tokens (%d)', 'libreblocks-core' ), status.tokens.length ) } description={ __( 'Use them in queries and texts. Click to copy.', 'libreblocks-core' ) }>
						<ul className="lb-tokens">
							{ status.tokens.map( ( token ) => {
								const code = `{{${ token.name }${ token.arg_label ? '|' : '' }}}`;

								return (
									<li key={ token.name }>
										<button type="button" onClick={ () => window.navigator.clipboard?.writeText( code ) } title={ __( 'Copy', 'libreblocks-core' ) }>
											<code>{ code }</code>
											<Icon name="admin-page" />
										</button>
										<span>{ token.label }</span>
									</li>
								);
							} ) }
						</ul>
					</Panel>
				</div>
			) }
		</AppShell>
	);
}

const mount = document.getElementById( 'libreblocks-dashboard' );

if ( mount ) {
	createRoot( mount ).render( <Dashboard /> );
}
