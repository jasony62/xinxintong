define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlDiscuss', ['$scope', 'http2', '$uibModal', '$timeout', function($scope, http2, $uibModal, $timeout) {

		$scope.$watch('discussParams', function(params) {
			if (!params) return;
			http2.get('/rest/discuss/thread/listPosts?domain=' + params.domain + '&threadKey=' + params.threadKey + '&title=' + params.title, function(rsp) {
				var posts, thread;
				$scope.posts = posts = rsp.data.posts;
				$scope.thread = thread = rsp.data.thread;
			});
		});
	}]);
});