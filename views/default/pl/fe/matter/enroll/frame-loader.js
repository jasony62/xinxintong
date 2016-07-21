require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"frame": '/views/default/pl/fe/matter/enroll/frame',
		"peditor": '/views/default/pl/fe/matter/enroll/lib/peditor',
		"page": '/views/default/pl/fe/matter/enroll/lib/page',
		"schema": '/views/default/pl/fe/matter/enroll/lib/schema',
		"wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
	},
	urlArgs: "bust=" + (new Date() * 1)
});
require(['frame']);