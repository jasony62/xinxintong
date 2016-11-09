define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
		$scope.editPage = function(pageName) {
			var name = $scope.platform[pageName + '_page_name'];
			if (name && name.length) {
				location.href = '/rest/pl/fe/code?site=platform&name=' + name;
			} else {
				http2.get('/rest/pl/be/home/pageCreate?name=' + pageName, function(rsp) {
					$scope.platform[pageName + '_page_name'] = rsp.data.name;
					location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
				});
			}
		};
		$scope.resetPage = function(pageName) {
			if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
				var name = $scope.platform[pageName + '_page_name'];
				if (name && name.length) {
					http2.get('/rest/pl/be/home/pageReset?name=' + pageName, function(rsp) {
						location.href = '/rest/pl/fe/code?site=platform&name=' + name;
					});
				} else {
					http2.get('/rest/pl/be/home/pageCreate?name=' + pageName, function(rsp) {
						$scope.platform[pageName + '_page_name'] = rsp.data.name;
						location.href = '/rest/pl/fe/code?site=platform&name=' + rsp.data.name;
					});
				}
			}
		};
	}]);
	ngApp.provider.controller('ctrlHomeCarousel', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		var slides;
		$scope.add = function() {
			var options = {
				callback: function(url) {
					slides.push({
						picUrl: url + '?_=' + (new Date() * 1)
					});
					$scope.update('home_carousel');
				}
			};
			mediagallery.open('platform', options);
		};
		$scope.remove = function(homeChannel, index) {
			slides.splice(index, 1);
			$scope.update('home_carousel');
		};
		$scope.$watch('platform', function(platform) {
			if (platform === undefined) return;
			if (!platform.home_carousel) platform.home_carousel = [];
			slides = platform.home_carousel;
			$scope.slides = slides;
		});
	}]);
});