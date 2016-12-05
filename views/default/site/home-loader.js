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
		"bootstrap": "/static/js/bootstrap.min",
		"angular": "/static/js/angular.min",
		"angular-route": "/static/js/angular-route.min",
		"angular-sanitize": "/static/js/angular-sanitize.min",
		"ui-bootstrap": "/static/js/ui-bootstrap-tpls.min",
		"ui-tms": "/static/js/ui-tms",
		"xxt-discuss": "/static/js/xxt.ui.discuss",
		"xxt-page": "/static/js/xxt.ui.page",
	},
	shim: {
		"angular": {
			deps: ['jquery'],
			exports: "angular"
		},
		"angular-route": {
			deps: ['angular'],
			exports: "angular-route"
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
	require(['bootstrap'], function() {
		require(['angular'], function(angular) {
			require(['angular-route'], function() {
				require(['angular-sanitize'], function() {
					require(['ui-bootstrap'], function() {
						require(['ui-tms', 'xxt-discuss'], function() {
							require(['xxt-page'], function(loader) {
								loader.bootstrap('/views/default/site/home.js?_=' + (timestamp * 1));
							});
						});
					});
				});
			});
		});
	});
});