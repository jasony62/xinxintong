require.config({
	paths: {
		"domReady": '/static/js/domReady',
		"angular": "/static/js/angular.min",
		"angular-sanitize": "/static/js/angular-sanitize.min",
		"tms-discuss": "/static/js/xxt.ui.discuss2",
		"xxt-page": "/static/js/xxt.ui.page",
		"enroll-directive": "/views/default/site/fe/matter/enroll/directive",
		"enroll-common": "/views/default/site/fe/matter/enroll/common",
	},
	shim: {
		"angular": {
			exports: "angular"
		},
		"angular-sanitize": {
			deps: ['angular'],
			exports: "angular-sanitize"
		},
		"enroll-common": {
			deps: ['angular-sanitize'],
			exports: "enroll-common"
		},
	},
	urlArgs: "bust=" + (new Date() * 1)
});
require(['xxt-page'], function(assembler) {
	assembler.bootstrap('/views/default/site/fe/matter/enroll/template.js');
});