'use strict';
define(['require'], function() {
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'member.xxt', 'channel.fe.pl']);
	ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', function($routeProvider, $locationProvider, $controllerProvider) {
		var RouteParam = function(name) {
			var baseURL = '/views/default/pl/fe/matter/article/';
			this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
			this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
			this.resolve = {
				load: function($q) {
					var defer = $q.defer();
					require([baseURL + name + '.js'], function() {
						defer.resolve();
					});
					return defer.promise;
				}
			};
		};
		ngApp.provider = {
			controller: $controllerProvider.register
		};
		$routeProvider
			.otherwise(new RouteParam('setting'));

		$locationProvider.html5Mode(true);
	}]);
	ngApp.controller('ctrlArticle', ['$scope', '$location', 'http2', function($scope, $location, http2) {
		var ls = $location.search();
		$scope.id = ls.id;
		$scope.siteId = ls.site;
		http2.get('/rest/pl/fe/matter/article/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
			var url;
			$scope.editing = rsp.data;
			!$scope.editing.attachments && ($scope.editing.attachments = []);
			url = 'http://' + location.host + '/rest/site/fe/matter?site=' + ls.site + '&id=' + ls.id + '&type=article';
			$scope.entry = {
				url: url,
				qrcode: '/rest/site/fe/matter/article/qrcode?site=' + ls.site + '&url=' + encodeURIComponent(url),
			};
		});
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});