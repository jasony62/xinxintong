define(['require'], function() {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'member.xxt', 'channel.fe.pl', 'service.article']);
	ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', function($routeProvider, $locationProvider, $controllerProvider) {
		var RouteParam = function(name,baseURL) {
			!baseURL && (baseURL = '/views/default/pl/fe/matter/article/');
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
		$routeProvider.when('/rest/pl/fe/matter/article/log', new RouteParam('log'))
			.when('/rest/pl/fe/matter/article/coin', new RouteParam('coin'))
			.when('/rest/pl/fe/matter/article/discuss', new RouteParam('discuss', '/views/default/pl/fe/_module/'))
			.otherwise(new RouteParam('setting'));

		$locationProvider.html5Mode(true);
	}]);
	ngApp.controller('ctrlArticle', ['$scope', '$location', 'http2', function($scope, $location, http2) {
		var ls = $location.search();
		$scope.id = ls.id;
		http2.get('/rest/pl/fe/matter/article/get?id=' + $scope.id, function(rsp) {
			var url, editing;
			$scope.siteId = rsp.data.siteid;
			$scope.editing = editing = rsp.data;
			!editing.attachments && (editing.attachments = []);
			url = 'http://' + location.host + '/rest/site/fe/matter?site=' + editing.siteid + '&id=' + ls.id + '&type=article';
			$scope.entry = {
				url: url,
				qrcode: '/rest/site/fe/matter/article/qrcode?site=' + editing.siteid + '&url=' + encodeURIComponent(url),
			};
			// 用户评论
			if (editing.can_discuss === 'Y') {
				$scope.discussParams = {
					title: editing.title,
					threadKey: 'article,' + editing.id,
					domain: editing.siteid
				};
			}
		});
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});