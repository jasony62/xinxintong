require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"frame": '/views/default/pl/fe/site/template',
	},
	urlArgs: "bust=" + (new Date() * 1)
});
require(['frame']);