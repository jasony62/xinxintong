require.config({
	waitSeconds: 0,
	paths: {
		"domReady": '/static/js/domReady',
		"ngSanitize": '/static/js/angular-sanitize.min',
		"tmsUI": '/static/js/ui-tms',
		"main": '/views/default/pl/fe/site/invite',
	},
	urlArgs: "bust=" + (new Date() * 1)
});
require(['main']);