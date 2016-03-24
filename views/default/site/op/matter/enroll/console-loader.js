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
				"util.site": "/views/default/site/util",
			},
			shim: {
				"angular": {
					exports: "angular"
				},
			},
			deps: ['/views/default/site/op/matter/enroll/console.js?_=1'],
			urlArgs: "bust=" + (new Date()).getTime()
		});
	}
};
window.loading.load();