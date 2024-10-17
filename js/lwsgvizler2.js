/**
 * JavaScript for the LinkedWiki extension.
 *
 * @see https://www.mediawiki.org/wiki/Extension:LinkedWiki
 *
 * @author Karima Rafes < karima dot rafes@gmail.com >
 */
/* globals mediaWiki jQuery */
( function ( mw, $ ) {
	$( () => {
		// eslint-disable-next-line  no-jquery/no-global-selector
		$( '[data-sgvizler-query]' ).each(
			function () {
				const $obj = $( this );
				$obj.containerchart( {
					googleApiKey: $obj.data( 'googleapikey' ),
					osmAccessToken: $obj.data( 'osmaccesstoken' ),
					path: mw.config.get( 'wgScriptPath' ) + '/extensions/LinkedWiki/node_modules/sgvizler2/build/browser'
				} );
			}
		);
	} );
}( mediaWiki, jQuery ) );
