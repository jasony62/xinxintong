require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"frame": '/views/default/pl/fe/matter/article/frame',
	},
	urlArgs: "bust=" + (new Date()).getTime()
});
require(['frame']);