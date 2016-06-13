window.loading = {
	finish: function() {
		eleLoading = document.querySelector('.loading');
		eleLoading.parentNode.removeChild(eleLoading);
	},
	load: function() {
		require.config({
			paths: {
				"domReady": '/static/js/domReady',
				"angular": "/static/js/angular.min",
				"angular-sanitize": "/static/js/angular-sanitize.min",
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
				"enroll-directive": {
					deps: ['enroll-common'],
					exports: "enroll-directive"
				},
			},
			urlArgs: "bust=" + (new Date()).getTime()
		});
		require(['xxt-page'], function(assembler) {
			assembler.bootstrap('/views/default/site/fe/matter/enroll/template.js');
		});
	}
};
window.loading.load();