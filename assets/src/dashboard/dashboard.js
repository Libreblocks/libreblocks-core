/**
 * LibreBlocks dashboard: environment, installed products and the building
 * blocks (field types, tokens) available to them.
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardBody,
	CardHeader,
	Notice,
	Spinner,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { createRoot, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import './dashboard.scss';

/**
 * The family of products. Installed ones come from the REST status; the rest
 * are shown as "coming soon".
 */
const CATALOG = [
	{ slug: 'libreforge', name: 'LibreForge', description: __( 'Post types, fields, relations, queries and dynamic listings.', 'libreblocks-core' ) },
	{ slug: 'libresift', name: 'LibreSift', description: __( 'AJAX filters for listings.', 'libreblocks-core' ) },
	{ slug: 'libreforms', name: 'LibreForms', description: __( 'Form builder.', 'libreblocks-core' ) },
	{ slug: 'librestay', name: 'LibreStay', description: __( 'Nightly bookings.', 'libreblocks-core' ) },
	{ slug: 'libreagenda', name: 'LibreAgenda', description: __( 'Appointments by time slot.', 'libreblocks-core' ) },
];

function ProductCard( { product, installed } ) {
	let status = __( 'Coming soon', 'libreblocks-core' );
	let modifier = 'soon';

	if ( installed ) {
		status = installed.compatible
			? sprintf( /* translators: %s: version */ __( 'Active · v%s', 'libreblocks-core' ), installed.version )
			: __( 'Needs a newer LibreBlocks Core', 'libreblocks-core' );
		modifier = installed.compatible ? 'active' : 'error';
	}

	return (
		<Card className="lb-product" size="small">
			<CardBody>
				<div className="lb-product__head">
					<strong>{ product.name }</strong>
					<span className={ `lb-badge lb-badge--${ modifier }` }>{ status }</span>
				</div>
				<p>{ product.description }</p>
			</CardBody>
		</Card>
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

	if ( error ) {
		return <Notice status="error" isDismissible={ false }>{ error }</Notice>;
	}

	if ( ! status ) {
		return <Spinner />;
	}

	const installed = Object.fromEntries( status.products.map( ( product ) => [ product.slug, product ] ) );

	return (
		<div className="lb-dashboard">
			<header className="lb-dashboard__header">
				<h1>LibreBlocks</h1>
				<span className="lb-dashboard__meta">
					{ sprintf(
						/* translators: 1: core version, 2: WordPress version, 3: PHP version */
						__( 'Core %1$s · WordPress %2$s · PHP %3$s', 'libreblocks-core' ),
						status.core,
						status.wp,
						status.php
					) }
				</span>
			</header>

			{ status.pending_tables.length > 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'Some database tables are waiting to be created or updated. Reload any admin page to run the update.', 'libreblocks-core' ) }
				</Notice>
			) }

			<Heading level={ 2 }>{ __( 'Products', 'libreblocks-core' ) }</Heading>
			<div className="lb-grid">
				{ CATALOG.map( ( product ) => (
					<ProductCard key={ product.slug } product={ product } installed={ installed[ product.slug ] } />
				) ) }
			</div>

			<div className="lb-grid lb-grid--two">
				<Card>
					<CardHeader>
						<strong>
							{ sprintf( /* translators: %d: number of field types */ __( 'Field types (%d)', 'libreblocks-core' ), status.field_types.length ) }
						</strong>
					</CardHeader>
					<CardBody>
						<ul className="lb-tags">
							{ status.field_types.map( ( type ) => (
								<li key={ type.type }>{ type.label }</li>
							) ) }
						</ul>
					</CardBody>
				</Card>

				<Card>
					<CardHeader>
						<strong>
							{ sprintf( /* translators: %d: number of tokens */ __( 'Dynamic tokens (%d)', 'libreblocks-core' ), status.tokens.length ) }
						</strong>
					</CardHeader>
					<CardBody>
						<table className="lb-tokens">
							<tbody>
								{ status.tokens.map( ( token ) => (
									<tr key={ token.name }>
										<td>
											<code>{ `{{${ token.name }${ token.arg_label ? '|…' : '' }}}` }</code>
										</td>
										<td>{ token.label }</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</CardBody>
				</Card>
			</div>
		</div>
	);
}

const mount = document.getElementById( 'libreblocks-dashboard' );

if ( mount ) {
	createRoot( mount ).render( <Dashboard /> );
}
