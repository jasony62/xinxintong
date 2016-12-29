define(['require'], function() {
	'use strict';
	var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'ui.xxt', 'member.xxt', 'channel.fe.pl', 'service.article']);
	ngApp.config(['$routeProvider', '$locationProvider', '$controllerProvider', 'srvAppProvider', function($routeProvider, $locationProvider, $controllerProvider, srvAppProvider) {
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
		//设置服务参数
		(function() {
			var ls, siteId, articleId;
			ls = location.search;
			siteId = ls.match(/[\?&]site=([^&]*)/)[1];
			articleId = ls.match(/[\?&]id=([^&]*)/)[1];
			//
			srvAppProvider.setSiteId(siteId);
			srvAppProvider.setAppId(articleId);
		})();
	}]);
	ngApp.controller('ctrlArticle', ['$scope', '$location', 'http2', '$q', 'mattersgallery', 'srvApp', 'noticebox', function($scope, $location, http2, $q, mattersgallery, srvApp, noticebox) {
		var ls = $location.search();
		$scope.id = ls.id;
		$scope.siteId = ls.site;
		$scope.subView = '';
		$scope.$on('$locationChangeSuccess', function(event, currentRoute) {
			var subView = currentRoute.match(/([^\/]+?)\?/);
			$scope.subView = subView[1] === 'article' ? 'setting' : subView[1];
		});
		$scope.update = function(names) {
			return srvApp.update(names);
		};
		$scope.assignMission = function() {
			srvApp.assignMission().then(function(mission) {});
		};
		$scope.quitMission = function() {
			srvApp.quitMission().then(function() {});
		};
		if (document.referrer.split('?')[0].indexOf('/pl/fe/site') !== -1) {
			$scope.referrer = 'site';
		}
		srvApp.get().then(function(editing){
			var url;
			$scope.editing = editing;
			!editing.attachments && (editing.attachments = []);
			url = 'http://' + location.host + '/rest/site/fe/matter?site=' + $scope.editing.siteid + '&id=' + $scope.editing.id + '&type=article';
			$scope.entry = {
				url: url,
				qrcode: '/rest/site/fe/matter/article/qrcode?site=' + $scope.editing.siteid + '&url=' + encodeURIComponent(url),
			};
			// 用户评论
			if (editing.can_discuss === 'Y') {
				$scope.discussParams = {
					title: editing.title,
					threadKey: 'article,' + editing.id,
					domain: editing.siteid
				};
			}
		})
	}]);
	/***/
	require(['domReady!'], function(document) {
		angular.bootstrap(document, ["app"]);
	});
	/***/
	return ngApp;
});