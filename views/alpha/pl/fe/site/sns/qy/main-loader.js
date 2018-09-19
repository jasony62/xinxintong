require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"main": '/views/default/pl/fe/site/sns/qy/main',
	},
	urlArgs: "bust=" + (new Date()).getTime()
});
require(['main']);