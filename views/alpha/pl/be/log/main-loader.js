require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"main": '/views/default/pl/be/log/main',
	},
	urlArgs: "bust=" + (new Date() * 1)
});
require(['main']);