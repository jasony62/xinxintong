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
			urlArgs: "bust=" + (new Date() * 1)
		});
		require(['xxt-page'], function(assembler) {
			assembler.bootstrap('/views/default/site/fe/matter/enroll/preview.js');
		});
	}
};
window.loading.load();