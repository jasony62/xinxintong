require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"frame": '/views/default/pl/be/home/frame',
	},
	urlArgs: "bust=" + (new Date() * 1)
});
require(['frame']);