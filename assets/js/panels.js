/* Section switching: battery-settings walkthroughs, troubleshooting entries
 * and the product filter. All panels are rendered by PHP; this module only
 * decides which one is visible. */

/**
 * Wire a tablist: buttons in the DOM, panels already rendered.
 *
 * @param {HTMLElement} root      Guide root.
 * @param {Object}      options   { buttonSelector, buttonAttribute, panelSelector, dataAttribute }.
 */
const bindTabs = ( root, options ) => {
	const buttons = Array.from( root.querySelectorAll( options.buttonSelector ) );
	if ( ! buttons.length ) {
		return;
	}
	const panels = Array.from( root.querySelectorAll( options.panelSelector ) );
	const select = ( value ) => {
		buttons.forEach( ( button ) => {
			const on = button.getAttribute( options.buttonAttribute ) === value;
			button.classList.toggle( 'is-selected', on );
			if ( button.hasAttribute( 'aria-selected' ) ) {
				button.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			}
		} );
		panels.forEach( ( panel ) => {
			const on = panel.getAttribute( options.dataAttribute ) === value;
			panel.hidden = ! on;
		} );
	};

	buttons.forEach( ( button ) => {
		button.addEventListener( 'click', () => select( button.getAttribute( options.buttonAttribute ) ) );
	} );
};

/**
 * Wire the product-kind filter.
 *
 * @param {HTMLElement} root Guide root.
 */
const bindProductFilter = ( root ) => {
	const buttons = Array.from( root.querySelectorAll( '[data-cg-filter]' ) );
	const cards = Array.from( root.querySelectorAll( '.cg-card' ) );
	if ( ! buttons.length || ! cards.length ) {
		return;
	}
	buttons.forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const value = button.getAttribute( 'data-cg-filter' ) || 'all';
			buttons.forEach( ( other ) => {
				const on = other === button;
				other.classList.toggle( 'is-selected', on );
				other.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			cards.forEach( ( card ) => {
				const kind = card.getAttribute( 'data-cg-kind' ) || '';
				card.hidden = 'all' !== value && kind !== value;
			} );
		} );
	} );
};

/**
 * Wire every switchable region of the guide.
 *
 * @param {HTMLElement} root Guide root.
 */
const initPanels = ( root ) => {
	bindTabs( root, {
		buttonSelector: '[data-cg-care]',
		buttonAttribute: 'data-cg-care',
		panelSelector: '[data-cg-care-panel]',
		dataAttribute: 'data-cg-care-panel',
	} );
	bindTabs( root, {
		buttonSelector: '[data-cg-issue]',
		buttonAttribute: 'data-cg-issue',
		panelSelector: '[data-cg-issue-panel]',
		dataAttribute: 'data-cg-issue-panel',
	} );
	bindProductFilter( root );
};

export { initPanels, bindTabs, bindProductFilter };
