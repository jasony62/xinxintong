var timestamp, minutes;
timestamp = new Date();
minutes = timestamp.getMinutes();
minutes = Math.floor(minutes / 5) * 5;
timestamp.setMinutes(minutes);
timestamp.setMilliseconds(0);
timestamp.setSeconds(0);
window.loading = {
	finish: function() {
		var eleLoading, eleStyle;
		eleLoading = document.querySelector('.loading');
		eleLoading.parentNode.removeChild(eleLoading);
		eleStyle = document.querySelector('#loadingStyle');
		eleStyle.parentNode.removeChild(eleStyle);
	},
	load: function() {
		require.config({
			paths: {
				"domReady": '/static/js/domReady',
				"angular": "/static/js/angular.min",
				"xxt-discuss": "/static/js/xxt.ui.discuss",
				"xxt-page": "/static/js/xxt.ui.page",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
			},
			urlArgs: function(id, url) {
				return "?bust=" + (timestamp * 1);
			}
		});
		require(['angular'], function(angular) {
			require(['xxt-discuss'], function() {
				require(['xxt-page'], function(loader) {
					loader.bootstrap('/views/default/site/fe/discuss/main.js?_=' + (timestamp * 1));
				});
			});
		});
	}
};
window.loading.load();