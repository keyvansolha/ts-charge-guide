/* JS unit suite: DOM helpers, the wizard flow against the real rendered
 * markup, the section switches and the REST client's failure modes.
 *
 * Usage: node tests/js/run.js   (fixture: php tests/js/make-fixture.php) */

import { readFileSync } from 'node:fs';
import { JSDOM } from 'jsdom';
import { safeMarkup, el } from '../../assets/js/dom.js';
import { recommend } from '../../assets/js/rest.js';

let pass = 0;
let fail = 0;
const check = ( name, cond ) => {
	if ( cond ) { pass++; console.log( `ok  ${name}` ); } else { fail++; console.log( `FAIL ${name}` ); }
};

/* ---------------- DOM helpers ---------------- */

check( 'safeMarkup accepts plain price markup', safeMarkup( '<span class="woocommerce-Price-amount">۱۰٬۰۰۰ تومان</span>' ) );
check( 'safeMarkup rejects a script tag', ! safeMarkup( '<span>x</span><script>alert(1)</script>' ) );
check( 'safeMarkup rejects an inline handler', ! safeMarkup( '<b onmouseover="steal()">x</b>' ) );
check( 'safeMarkup rejects a javascript: URL', ! safeMarkup( '<a href="javascript:alert(1)">x</a>' ) );
check( 'safeMarkup rejects an empty value', ! safeMarkup( '' ) );
check( 'el refuses a tag that is not a tag name', ( () => { try { el( '<img onerror=1>', {} ); return false; } catch ( e ) { return true; } } )() );

/* ---------------- REST client ---------------- */

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
const realFetch = globalThis.fetch;
const config = { endpoint: 'https://store.example/wp-json/ts-charge/v1/recommend', nonce: 'n' };

globalThis.fetch = async ( url, options ) => {
	globalThis.__lastCall = { url, options };
	return { ok: true, status: 200, json: async () => ( { heading: 'h', lead: 'l', checklist: [ 'a' ], products: [] } ) };
};
const okPayload = await recommend( config, { need: 'powerbank', device: 'samsung', priority: 'light' } );
check( 'the client posts JSON to the configured endpoint', 'https://store.example/wp-json/ts-charge/v1/recommend' === globalThis.__lastCall.url && 'POST' === globalThis.__lastCall.options.method );
check( 'the client sends the nonce header', 'n' === globalThis.__lastCall.options.headers[ 'X-WP-Nonce' ] );
check( 'the client returns the server payload', 'h' === okPayload.heading );

globalThis.fetch = async () => ( { ok: false, status: 503, json: async () => ( {} ) } );
let failed = false;
try { await recommend( config, { need: 'powerbank', device: 'other', priority: 'light' } ); } catch ( e ) { failed = 'http_503' === e.message; }
check( 'a failed request rejects with the status code', failed );

globalThis.fetch = async () => ( { ok: true, status: 200, json: async () => ( { heading: 'h' } ) } );
failed = false;
try { await recommend( config, { need: 'powerbank', device: 'other', priority: 'light' } ); } catch ( e ) { failed = 'bad_payload' === e.message; }
check( 'an unexpected payload is rejected, not rendered', failed );

failed = false;
try { await recommend( { endpoint: '' }, {} ); } catch ( e ) { failed = 'no_endpoint' === e.message; }
check( 'a missing endpoint fails closed', failed );
globalThis.fetch = realFetch;

/* ---------------- integration: the real rendered markup ---------------- */

const fixture = readFileSync( new URL( './fixtures/guide-markup.html', import.meta.url ), 'utf8' );
const dom = new JSDOM( `<!doctype html><html lang="fa" dir="rtl"><body>${ fixture }</body></html>`, { pretendToBeVisual: true } );

globalThis.window = dom.window;
globalThis.document = dom.window.document;
globalThis.Node = dom.window.Node;
globalThis.Event = dom.window.Event;
globalThis.HTMLElement = dom.window.HTMLElement;

globalThis.__requests = [];
// Mirrors the documented route contract: the reuse path answers with no
// product at all (asserted server-side in tests/unit/run.php).
globalThis.fetch = async ( url, options ) => {
	const answers = JSON.parse( options.body );
	globalThis.__requests.push( answers );
	const reuse = 'reuse' === answers.priority;
	return {
		ok: true,
		status: 200,
		json: async () => ( {
			heading: 'ظرفیت بیشتر، با توجه به وزن.',
			lead: 'lead',
			checklist: [ 'کابل متناسب با Lightning — body', 'دوم', 'سوم' ],
			warning: 'warning',
			products: reuse ? [] : [ { id: 901, name: 'پاوربانک بیسوس مدل A 20000', url: 'https://store.example/product/901/', image: 'https://store.example/img/901.webp', priceHtml: '', inStock: true } ],
		} ),
	};
};

// jsdom keeps the document in the loading state, so the entry module wires its
// DOMContentLoaded listener; firing the event is what boots the guide here.
await import( '../../assets/js/entry.js' );
document.dispatchEvent( new dom.window.Event( 'DOMContentLoaded' ) );

const root = document.getElementById( 'ts-charge' );
const $ = ( selector ) => root.querySelector( selector );
const $$ = ( selector ) => Array.from( root.querySelectorAll( selector ) );
const click = ( node ) => node.dispatchEvent( new dom.window.MouseEvent( 'click', { bubbles: true } ) );

check( 'the fixture is the real rendered guide', null !== root && $$( '.cg-cell' ).length === 4 );
check( 'the cards are the theme component, not a guide card', 4 === $$( '.cg-cell .product-simple-card' ).length && 0 === $$( '.cg-card' ).length );
check( 'the escaped product name is text, not markup', $$( '.product-simple-card h2' ).some( ( h ) => h.textContent.includes( '<script>' ) ) && 0 === $$( '.product-simple-card script' ).length );
check( 'the out-of-stock product is not rendered at all', ! $$( '.product-simple-card' ).some( ( c ) => c.textContent.includes( 'ناموجود' ) ) );
check( 'the reading cards are the theme post card', 2 === $$( '.blog-row-post-card' ).length && $$( '.blog-row-post-card' ).every( ( c ) => c.classList.contains( 'full-card' ) ) );
check( 'step 1 is the only visible step at boot', ! $( '[data-cg-step="1"]' ).hidden && $( '#cg-stage' ).hidden && $( '#cg-result' ).hidden );

click( $( '[data-cg-need="powerbank"]' ) );
check( 'choosing a need reveals step 2', $( '[data-cg-step="1"]' ).hidden && ! $( '#cg-stage' ).hidden && $$( '[data-cg-device]' ).length === 6 );
check( 'the rail marks the second step', 'step' === $( '[data-rail="2"]' ).getAttribute( 'aria-current' ) );

click( $( '[data-cg-device="iphone-lightning"]' ) );
check( 'choosing a device reveals the priorities of that need', $$( '[data-cg-priority]' ).length === 3 );
check( 'the third step offers the need-specific priorities', $$( '[data-cg-priority]' ).some( ( b ) => 'capacity' === b.getAttribute( 'data-cg-priority' ) ) );

click( $( '[data-cg-priority="capacity"]' ) );
await flush();
check( 'one request is sent for one selection', 1 === globalThis.__requests.length );
check( 'the request carries the three answers', { need: 'powerbank', device: 'iphone-lightning', priority: 'capacity' } && 'powerbank' === globalThis.__requests[ 0 ].need && 'iphone-lightning' === globalThis.__requests[ 0 ].device && 'capacity' === globalThis.__requests[ 0 ].priority );
check( 'the result shows the server copy', ! $( '#cg-result' ).hidden && $( '#cg-result' ).textContent.includes( 'ظرفیت بیشتر' ) );
check( 'the result shows the three checklist lines', 3 === $$( '.cg-checklist li' ).length );
check( 'the result shows the live product pick', 1 === $$( '.cg-pick' ).length && $( '.cg-pick' ).getAttribute( 'href' ) === 'https://store.example/product/901/' );
check( 'the picks open in a new tab safely', 'noopener' === $( '.cg-pick' ).getAttribute( 'rel' ) );
check( 'the result is built from DOM nodes, never raw HTML', 0 === $( '#cg-result' ).querySelectorAll( 'script' ).length );

click( $( '#cg-result .cg-back' ) );
check( 'going back returns to the priorities', $( '#cg-result' ).hidden && ! $( '#cg-stage' ).hidden && $$( '[data-cg-priority]' ).length === 3 );

click( $( '[data-cg-priority="reuse"]' ) );
await flush();
check( 'the reuse answer offers nothing to buy', 2 === globalThis.__requests.length && 0 === $$( '.cg-pick' ).length );

click( root.querySelector( '#cg-start' ) );
check( 'restarting returns to step 1', ! $( '[data-cg-step="1"]' ).hidden && $( '#cg-result' ).hidden );

globalThis.fetch = async () => { throw new Error( 'offline' ); };
click( $( '[data-cg-need="charger"]' ) );
click( $( '[data-cg-device="samsung"]' ) );
click( $( '[data-cg-priority="multi"]' ) );
await flush();
await flush();
check( 'a failed request shows the guide copy instead of breaking', 1 === $$( '.cg-result-error' ).length && $( '.cg-result-error' ).textContent.includes( 'یک بار دیگر امتحان کن' ) );

/* ---------------- section switches ---------------- */

const careButtons = $$( '[data-cg-care]' );
check( 'every care walkthrough is rendered server-side', 4 === $$( '[data-cg-care-panel]' ).length && 4 === careButtons.length );
check( 'one care panel is visible before any click', 1 === $$( '[data-cg-care-panel]' ).filter( ( p ) => ! p.hidden ).length );
click( careButtons[ 2 ] );
check( 'switching care keeps exactly one panel visible', 1 === $$( '[data-cg-care-panel]' ).filter( ( p ) => ! p.hidden ).length && ! $( '[data-cg-care-panel="samsung"]' ).hidden );
check( 'the selected care button is marked for assistive tech', 'true' === careButtons[ 2 ].getAttribute( 'aria-selected' ) && 'false' === careButtons[ 0 ].getAttribute( 'aria-selected' ) );

const issueButtons = $$( '[data-cg-issue]' );
check( 'every troubleshooting entry is rendered server-side', 5 === $$( '[data-cg-issue-panel]' ).length );
click( issueButtons[ 1 ] );
check( 'switching the issue shows the matching steps', ! $( '[data-cg-issue-panel="refill"]' ).hidden && 1 === $$( '[data-cg-issue-panel]' ).filter( ( p ) => ! p.hidden ).length && 3 === $$( '[data-cg-issue-panel="refill"] li' ).length );

const filterButtons = $$( '[data-cg-filter]' );
click( filterButtons.find( ( b ) => 'powerbank' === b.getAttribute( 'data-cg-filter' ) ) );
check( 'the product filter hides the other kind', 2 === $$( '.cg-cell' ).filter( ( c ) => ! c.hidden ).length && $$( '.cg-cell' )[ 0 ].hidden === false );
click( filterButtons.find( ( b ) => 'all' === b.getAttribute( 'data-cg-filter' ) ) );
check( 'clearing the filter shows everything again', 4 === $$( '.cg-cell' ).filter( ( c ) => ! c.hidden ).length );

console.log( `\n${ pass } passed, ${ fail } failed` );
process.exit( fail ? 1 : 0 );
