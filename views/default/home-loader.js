var timestamp, minutes;
timestamp = new Date();
minutes = timestamp.getMinutes();
minutes = Math.floor(minutes / 5) * 5;
timestamp.setMinutes(minutes);
timestamp.setMilliseconds(0);
timestamp.setSeconds(0);
require.config({
	waitSeconds: 0,
	paths: {
		"domReady": '/static/js/domReady',
		"jquery": "/static/js/jquery.min",
		"angular": "/static/js/angular.min",
		"ui-bootstrap": "/static/js/ui-bootstrap-tpls.min",
		"xxt-page": "/static/js/xxt.ui.page",
	},
	shim: {
		"bootstrap": {
			deps: ['jquery'],
		},
		"angular": {
			deps: ['jquery'],
			exports: "angular"
		},
	},
	urlArgs: function(id, url) {
		if (/^[xxt-]/.test(id)) {
			return "?bust=" + (timestamp * 1);
		}
		return '';
	}
});
require(['jquery'], function() {
	require(['angular'], function(angular) {
		require(['ui-bootstrap'], function(angular) {
			require(['xxt-page'], function(loader) {
				loader.bootstrap('/views/default/home.js?_=' + (timestamp * 1));
			});
		});
	});
});