require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"frame": '/views/default/pl/fe/matter/wall/frame'
		//"page": '/views/default/pl/fe/matter/wall/lib/page',
		//"schema": '/views/default/pl/fe/matter/wall/lib/schema',
		//"wrap": '/views/default/pl/fe/matter/wall/lib/wrap',
	},
	urlArgs: "bust=" + (new Date()).getTime()
});
require(['frame']);