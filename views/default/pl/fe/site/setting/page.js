define(['main'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlPage', ['$scope', 'http2', function($scope, http2) {}]);
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
			mediagallery.open($scope.siteId, options);
		};
		$scope.remove = function(homeChannel, index) {
			slides.splice(index, 1);
			$scope.update('home_carousel');
		};
		$scope.$watch('site', function(site) {
			if (site === undefined) return;
			if (!site.home_carousel) site.home_carousel = [];
			slides = site.home_carousel;
			$scope.slides = slides;
		});
	}]);
	ngApp.provider.controller('ctrlHomeChannel', ['$scope', 'http2', 'mattersgallery', function($scope, http2, mattersgallery) {
		$scope.add = function() {
			var options = {
				matterTypes: [{
					value: 'channel',
					title: '频道',
					url: '/rest/pl/fe/matter'
				}],
				singleMatter: true
			};
			mattersgallery.open($scope.siteId, function(channels) {
				var channel;
				if (channels && channels.length) {
					channel = channels[0];
					http2.post('/rest/pl/fe/site/setting/page/addHomeChannel?site=' + $scope.siteId, channel, function(rsp) {
						$scope.channels.push(rsp.data);
					});
				}
			}, options);
		};
		$scope.remove = function(homeChannel, index) {
			http2.get('/rest/pl/fe/site/setting/page/removeHomeChannel?site=' + $scope.siteId + '&id=' + homeChannel.id, function(rsp) {
				$scope.channels.splice(index, 1);
			});
		};
		http2.get('/rest/pl/fe/site/setting/page/listHomeChannel?site=' + $scope.siteId, function(rsp) {
			$scope.channels = rsp.data;
		});
	}]);
});