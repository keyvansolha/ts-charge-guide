/* DOM controller: reads the public bootstrap config and wires the guide. */

import { initWizard } from './wizard.js';
import { initPanels } from './panels.js';

/**
 * Read the JSON bootstrap printed by the plugin view.
 *
 * @return {Object} Config.
 */
const readConfig = () => {
	const node = document.getElementById( 'ts-charge-config' );
	if ( ! node ) {
		return {};
	}
	try {
		return JSON.parse( node.textContent || '{}' );
	} catch ( error ) {
		return {};
	}
};

const boot = () => {
	const root = document.getElementById( 'ts-charge' );
	if ( ! root ) {
		return;
	}
	const config = readConfig();
	initWizard( root, config );
	initPanels( root );
};

if ( 'loading' === document.readyState ) {
	document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
} else {
	boot();
}

export { boot, readConfig };
