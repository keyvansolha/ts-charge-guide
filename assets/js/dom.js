/* DOM helpers. Every value that reaches the document goes through a DOM API
 * or the HTML guard below; never a raw innerHTML assignment. */

const TAG_ATTRIBUTE = /^[a-z][a-z0-9-]*$/i;

/**
 * Create an element with attributes and text.
 *
 * @param {string} tag        Tag name.
 * @param {Object} attributes Attribute map (className, src, href, rel, target…).
 * @param {string} text       Text content.
 * @return {HTMLElement} Element.
 */
const el = ( tag, attributes = {}, text = '' ) => {
	if ( ! TAG_ATTRIBUTE.test( tag ) ) {
		throw new Error( 'bad_tag' );
	}
	const node = document.createElement( tag );
	Object.keys( attributes ).forEach( ( key ) => {
		const value = attributes[ key ];
		if ( value === null || value === undefined || '' === value ) {
			return;
		}
		if ( 'className' === key ) {
			node.className = String( value );
			return;
		}
		node.setAttribute( key, String( value ) );
	} );
	if ( '' !== text ) {
		node.textContent = text;
	}
	return node;
};

/**
 * Whether a fragment is safe to place in the document as markup.
 *
 * Only for markup the server generated (WooCommerce price markup). Rejects
 * scripts, frames, inline event handlers and javascript: URLs.
 *
 * @param {string} html Candidate markup.
 * @return {boolean} True when safe.
 */
const safeMarkup = ( html ) => {
	const text = String( html || '' );
	if ( '' === text ) {
		return false;
	}
	if ( /<\s*(script|iframe|object|embed|style|link|meta|form)/i.test( text ) ) {
		return false;
	}
	if ( /\son[a-z]+\s*=/i.test( text ) ) {
		return false;
	}
	if ( /(javascript|data|vbscript)\s*:/i.test( text ) ) {
		return false;
	}
	return true;
};

export { el, safeMarkup };
