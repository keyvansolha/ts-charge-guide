/* REST client for the recommend route. */

const TIMEOUT_MS = 8000;

/**
 * Ask the server for a result. Rejects with a short reason code; the guide
 * shows its own copy either way.
 *
 * @param {Object} config  Public bootstrap config (endpoint, nonce).
 * @param {Object} answers { need, device, priority }.
 * @return {Promise<Object>} Result payload.
 */
const recommend = async ( config, answers ) => {
	if ( ! config || typeof config.endpoint !== 'string' || '' === config.endpoint ) {
		throw new Error( 'no_endpoint' );
	}
	const controller = new AbortController();
	const timer = setTimeout( () => controller.abort(), TIMEOUT_MS );
	try {
		const response = await fetch( config.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': String( config.nonce || '' ),
			},
			body: JSON.stringify( answers ),
			signal: controller.signal,
			credentials: 'same-origin',
		} );
		if ( ! response.ok ) {
			throw new Error( 'http_' + response.status );
		}
		const data = await response.json();
		if ( ! data || typeof data !== 'object' || ! Array.isArray( data.checklist ) ) {
			throw new Error( 'bad_payload' );
		}
		return data;
	} catch ( error ) {
		throw error instanceof Error ? error : new Error( 'request_failed' );
	} finally {
		clearTimeout( timer );
	}
};

export { recommend, TIMEOUT_MS };
