require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"main": '/views/default/pl/fe/site/setting',
	},
	urlArgs: "bust=" + (new Date()).getTime()
});
require(['main']);