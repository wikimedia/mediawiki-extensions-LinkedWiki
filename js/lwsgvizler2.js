$(document).ready(function () {
    $("[data-sgvizler-query]").each(
		function() {
			var obj = $( this );
			obj.containerchart({
				googleApiKey: obj.data( "googleapikey" ) ,
				osmAccessToken: obj.data( "osmaccesstoken" ) ,
				path: mw.config.get('wgScriptPath') + "/extensions/LinkedWiki/node_modules/sgvizler2/build/browser"
			});
		}
	);
});
