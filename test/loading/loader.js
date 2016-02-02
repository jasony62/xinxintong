require.config({
	paths: {
		"angular": "/static/js/angular.min",
	},
	shim: {
		"angular": {
			exports: "angular"
		},
	},
	deps: ['/test/loading/app.js?_=10']
});