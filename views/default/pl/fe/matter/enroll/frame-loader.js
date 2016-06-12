require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"frame": '/views/default/pl/fe/matter/enroll/frame',
		"page": '/views/default/pl/fe/matter/enroll/lib/page',
		"schema": '/views/default/pl/fe/matter/enroll/lib/schema',
		"wrap": '/views/default/pl/fe/matter/enroll/lib/wrap',
	},
	urlArgs: "bust=" + (new Date()).getTime()
});
require(['frame']);