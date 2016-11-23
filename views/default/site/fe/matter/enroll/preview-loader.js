window.loading = {
	finish: function() {
		eleLoading = document.querySelector('.loading');
		eleLoading.parentNode.removeChild(eleLoading);
	},
	load: function() {
		var timestamp, minutes;
		timestamp = new Date();
		minutes = timestamp.getMinutes();
		minutes = Math.floor(minutes / 5) * 5;
		timestamp.setMinutes(minutes);
		timestamp.setMilliseconds(0);
		timestamp.setSeconds(0);

		require.config({
			paths: {
				"domReady": '/static/js/domReady',
				"angular": "/static/js/angular.min",
				"angular-sanitize": "/static/js/angular-sanitize.min",
				"tms-discuss": "/static/js/xxt.ui.discuss2",
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
			urlArgs: "bust=" + (timestamp * 1)
		});
		require(['xxt-page'], function(assembler) {
			assembler.bootstrap('/views/default/site/fe/matter/enroll/preview.js?_=' + (timestamp * 1));
		});
	}
};
window.loading.load();