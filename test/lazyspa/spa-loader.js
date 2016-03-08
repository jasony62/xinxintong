window.loading = {
	finish: function() {
		var eleLoading, eleStyle;
		eleLoading = document.querySelector('.loading');
		if (eleLoading) {
			eleLoading.parentNode.removeChild(eleLoading);
			eleStyle = document.querySelector('#loadingStyle');
			eleStyle.parentNode.removeChild(eleStyle);
		}
	},
	load: function() {
		require.config({
			paths: {
				"domReady": '/static/js/domReady',
				"angular": "//cdn.bootcss.com/angular.js/1.4.8/angular.min",
				"angular-route": "//cdn.bootcss.com/angular.js/1.4.8/angular-route.min",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
				"angular-route": {
					deps: ["angular"]
				},
			},
			deps: ['/test/lazyspa/spa.js'],
			urlArgs: "bust=" + (new Date()).getTime()
		});
	}
};
window.loading.load();