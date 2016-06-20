require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"angular": "/static/js/angular.min",
		"angular-sanitize": "/static/js/angular-sanitize.min",
		"xxt-page": "/static/js/xxt.ui.page",
	},
	shim: {
		"angular": {
			exports: "angular"
		},
		"angular-sanitize": {
			deps: ['angular'],
			exports: "angular-sanitize"
		},
	},
	urlArgs: "bust=" + (new Date()).getTime()
});
require(['xxt-page'], function(uiPage) {
	uiPage.bootstrap('/views/default/site/op/matter/group/main.js');
});