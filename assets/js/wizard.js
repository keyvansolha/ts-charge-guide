/* The three-step wizard. Steps 2 and 3 are <template> elements rendered by
 * PHP, so no guide copy lives in this file; the result comes from the REST
 * route and is built with DOM APIs. */

import { recommend } from './rest.js';
import { el, safeMarkup } from './dom.js';

const STEP_TEMPLATE = {
	2: 'cg-step2',
	3: 'cg-step3-',
};

/**
 * Read the visible label of an option button (the first text node).
 *
 * @param {HTMLElement} button Option button.
 * @return {string} Label.
 */
const optionLabel = ( button ) => {
	const first = button && button.firstChild ? button.firstChild.textContent : '';
	return String( first || '' ).trim();
};

/**
 * Build one product pick from a REST payload row.
 *
 * @param {Object} row Public product row.
 * @return {HTMLElement} Anchor element.
 */
const pick = ( row ) => {
	const link = el( 'a', {
		className: 'cg-pick',
		href: String( row.url || '' ),
		target: '_blank',
		rel: 'noopener',
	} );
	if ( row.image ) {
		link.appendChild( el( 'img', { src: String( row.image ), alt: '', width: '65', height: '75', loading: 'lazy' } ) );
	}
	const body = el( 'span' );
	body.appendChild( el( 'b', {}, String( row.name || '' ) ) );
	body.appendChild( el( 'small', {}, 'دیدن مشخصات و قیمت روز' ) );
	link.appendChild( body );
	return link;
};

/**
 * Wire the wizard.
 *
 * @param {HTMLElement} root   Guide root.
 * @param {Object}      config Public bootstrap config.
 */
const initWizard = ( root, config ) => {
	const wizard = root.querySelector( '#cg-wizard' );
	const result = root.querySelector( '#cg-result' );
	const step1 = wizard ? wizard.querySelector( '[data-cg-step="1"]' ) : null;
	const stage = wizard ? wizard.querySelector( '#cg-stage' ) : null;
	if ( ! wizard || ! result || ! step1 || ! stage ) {
		return;
	}

	const state = { step: 1, reached: 1, need: null, device: null, priority: null, deviceLabel: '', priorityLabel: '' };

	const rail = ( step ) => {
		const current = Math.min( step, 3 );
		// How far the visitor has got: steps up to here can be jumped back to.
		state.reached = Math.max( state.reached || 1, current );
		root.querySelectorAll( '[data-rail]' ).forEach( ( item ) => {
			const n = Number( item.getAttribute( 'data-rail' ) );
			if ( n === current ) {
				item.setAttribute( 'aria-current', 'step' );
			} else {
				item.removeAttribute( 'aria-current' );
			}
			// Reached steps turn green; the ones still ahead stay navy.
			if ( n <= current ) {
				item.setAttribute( 'data-done', '1' );
			} else {
				item.removeAttribute( 'data-done' );
			}
			// Only a step already reached can be opened again.
			if ( 'BUTTON' === item.tagName ) {
				item.disabled = n > state.reached;
			}
		} );
	};

	const show = ( step ) => {
		state.step = step;
		step1.hidden = 1 !== step;
		// Steps 2 and 3 share the stage container.
		stage.hidden = 2 !== step && 3 !== step;
		result.hidden = 4 !== step;
		if ( 4 !== step ) {
			result.textContent = '';
		}
		rail( step );
	};

	/**
	 * Replay the entrance animation of a panel that was just rebuilt.
	 *
	 * @param {HTMLElement} node Revealed panel.
	 */
	const reveal = ( node ) => {
		node.classList.remove( 'cg-enter' );
		void node.offsetWidth;
		node.classList.add( 'cg-enter' );
	};

	const goToStep = ( step ) => {
		if ( 2 === step ) {
			const template = root.querySelector( '#' + STEP_TEMPLATE[ 2 ] );
			if ( ! template ) {
				return;
			}
			stage.replaceChildren( template.content.cloneNode( true ) );
			reveal( stage );
		} else if ( 3 === step ) {
			const template = root.querySelector( '#' + STEP_TEMPLATE[ 3 ] + state.need );
			if ( ! template ) {
				return;
			}
			stage.replaceChildren( template.content.cloneNode( true ) );
			reveal( stage );
		}
		show( step );
		const heading = ( 2 === step || 3 === step ? stage : step1 ).querySelector( 'h3' );
		if ( heading ) {
			heading.focus( { preventScroll: true } );
		}
	};

	// Paint the rail before anything is answered: step 1 current, the rest locked.
	rail( 1 );

	// Tapping a step circle walks back to it so an answer can be changed.
	root.querySelectorAll( '[data-rail]' ).forEach( ( item ) => {
		item.addEventListener( 'click', () => {
			const n = Number( item.getAttribute( 'data-rail' ) );
			if ( n && n <= state.reached && n !== state.step ) {
				goToStep( n );
			}
		} );
	} );

	const renderResult = ( data ) => {
		const head = el( 'div', { className: 'cg-result-head' } );
		const tick = el( 'span', { className: 'cg-tick', 'aria-hidden': 'true' }, '✓' );
		const headText = el( 'div' );
		headText.appendChild( el( 'h3', { tabindex: '-1' }, 'حالا می‌دانی چه چیزهایی را بررسی کنی.' ) );
		headText.appendChild( el( 'p', {}, [ state.deviceLabel, state.priorityLabel ].filter( Boolean ).join( ' · ' ) ) );
		head.appendChild( tick );
		head.appendChild( headText );

		const summary = el( 'div', { className: 'cg-result-summary' } );
		summary.appendChild( el( 'h4', {}, String( data.heading || '' ) ) );
		summary.appendChild( el( 'p', {}, String( data.lead || '' ) ) );

		const list = el( 'ul', { className: 'cg-checklist' } );
		( data.checklist || [] ).forEach( ( line ) => {
			const item = el( 'li' );
			// Server copy: the device line carries <b> emphasis only.
			if ( safeMarkup( String( line ) ) ) {
				item.innerHTML = String( line );
			} else {
				item.textContent = String( line );
			}
			list.appendChild( item );
		} );

		const warning = el( 'div', { className: 'cg-result-warning' }, String( data.warning || '' ) );

		result.replaceChildren( head, summary, list, warning );

		const picks = Array.isArray( data.products ) ? data.products : [];
		if ( picks.length ) {
			const grid = el( 'div', { className: 'cg-picks' } );
			picks.forEach( ( row ) => grid.appendChild( pick( row ) ) );
			result.appendChild( grid );
		}

		const actions = el( 'div', { className: 'cg-wizard-actions' } );
		actions.appendChild( el( 'button', { className: 'cg-back', type: 'button', 'data-cg-back': '3' }, 'تغییر اولویت' ) );
		actions.appendChild( el( 'button', { className: 'cg-button cg-button--secondary', type: 'button', 'data-cg-reset': '1' }, 'از اول انتخاب کنم' ) );
		result.appendChild( actions );
		reveal( result );
		show( 4 );
		const heading = result.querySelector( 'h3' );
		if ( heading ) {
			heading.focus( { preventScroll: true } );
		}
	};

	const renderError = () => {
		result.replaceChildren( el( 'div', { className: 'cg-result-error' }, String( config.error || '' ) ) );
		const actions = el( 'div', { className: 'cg-wizard-actions' } );
		actions.appendChild( el( 'button', { className: 'cg-back', type: 'button', 'data-cg-back': '3' }, 'برگشت' ) );
		result.appendChild( actions );
		show( 4 );
	};

	const request = () => {
		result.hidden = false;
		result.replaceChildren( el( 'p', { className: 'cg-loading' }, 'داریم بررسی می‌کنیم…' ) );
		rail( 4 );
		recommend( config, { need: state.need, device: state.device, priority: state.priority } )
			.then( renderResult )
			.catch( renderError );
	};

	wizard.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( 'button' );
		if ( ! button ) {
			return;
		}
		if ( button.hasAttribute( 'data-cg-restart' ) || button.hasAttribute( 'data-cg-reset' ) ) {
			state.need = null;
			state.device = null;
			state.priority = null;
			show( 1 );
			return;
		}
		if ( button.hasAttribute( 'data-cg-back' ) ) {
			goToStep( Number( button.getAttribute( 'data-cg-back' ) ) );
			return;
		}
		if ( button.hasAttribute( 'data-cg-need' ) ) {
			state.need = button.getAttribute( 'data-cg-need' );
			goToStep( 2 );
			return;
		}
		if ( button.hasAttribute( 'data-cg-device' ) ) {
			state.device = button.getAttribute( 'data-cg-device' );
			state.deviceLabel = optionLabel( button );
			goToStep( 3 );
			return;
		}
		if ( button.hasAttribute( 'data-cg-priority' ) ) {
			state.priority = button.getAttribute( 'data-cg-priority' );
			state.priorityLabel = optionLabel( button );
			request();
		}
	} );

	root.addEventListener( 'click', ( event ) => {
		const trigger = event.target.closest( '[data-cg-restart]' );
		if ( ! trigger ) {
			return;
		}
		state.need = null;
		state.device = null;
		state.priority = null;
		show( 1 );
		const anchor = root.querySelector( '#cg-journey' );
		if ( anchor && typeof anchor.scrollIntoView === 'function' ) {
			anchor.scrollIntoView( { block: 'start' } );
		}
	} );

	show( 1 );
};

export { initWizard, optionLabel };
