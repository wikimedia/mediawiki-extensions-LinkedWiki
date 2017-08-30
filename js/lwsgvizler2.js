

$(document).ready(function () {
    sgvizler2.containerDrawAll({
        googleApiKey: googleApiKey ,
        osmAccessToken: osmAccessToken ,
        path: mw.config.get('wgScriptPath') + "/extensions/LinkedWiki/node_modules/sgvizler2/build/browser"
    });
});


