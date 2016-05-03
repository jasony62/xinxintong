(function() {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', '$modal', 'mediagallery', function($scope, $location, http2, $modal, mediagallery) {
		window.onbeforeunload = function(e) {
			var message;
			if ($scope.modified) {
				message = '修改还没有保存，是否要离开当前页面？',
					e = e || window.event;
				if (e) {
					e.returnValue = message;
				}
				return message;
			}
		};
		$scope.run = function() {
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/lottery/running?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.setPic = function() {
			var options = {
				callback: function(url) {
					$scope.app.pic = url + '?_=' + (new Date()) * 1;
					$scope.update('pic');
				}
			};
			mediagallery.open($scope.siteId, options);
		};
		$scope.removePic = function() {
			var nv = {
				pic: ''
			};
			http2.post('/rest/mp/app/group/update?aid=' + $scope.id, nv, function() {
				$scope.app.pic = '';
			});
		};
		$scope.gotoCode = function() {
			var app, url;
			app = $scope.app;
			if (app.page_code_id != 0) {
				window.open('/rest/code?pid=' + app.page_code_id, '_self');
			} else {
				url = '/rest/pl/fe/matter/lottery/page/create?site=' + $scope.siteId + '&app=' + app.id;
				http2.get(url, function(rsp) {
					app.page_code_id = rsp.data;
					window.open('/rest/code?pid=' + app.page_code_id, '_self');
				});
			}
		};
		$scope.resetCode = function() {
			var app, url;
			if (window.confirm('重置操作将丢失已做修改，确定？')) {
				app = $scope.app;
				url = '/rest/pl/fe/matter/lottery/page/reset?site=' + $scope.siteId + '&app=' + app.id;;
				http2.get(url, function(rsp) {
					window.open('/rest/code?pid=' + app.page_code_id, '_self');
				});
			}
		};
	}]);
	ngApp.provider.controller('ctrlTask', ['$scope', 'http2', function($scope, http2) {
		$scope.taskHtml = function(task) {
			var url;
			url = '/views/default/pl/fe/matter/lottery/task/' + task.task_type + '.html';
			return url;
		};
		$scope.add = function() {
			var url, data = {};
			url = '/rest/pl/fe/matter/lottery/task/add?site=' + $scope.siteId + '&app=' + $scope.app.id;
			data.task_type = 'sns_share';
			http2.post(url, data, function(rsp) {
				$scope.app.tasks.push(rsp.data);
			});
		};
		$scope.remove = function(task) {
			var url;
			url = '/rest/pl/fe/matter/lottery/task/remove?site=' + $scope.siteId + '&app=' + $scope.app.id;
			url += '&task=' + task.tid;
			http2.get(url, function(rsp) {
				var i = $scope.app.tasks.indexOf(task);
				$scope.app.tasks.splice(i, 1);
			});
		};
		$scope.save = function(task) {
			var url, data = {};
			url = '/rest/pl/fe/matter/lottery/task/update?site=' + $scope.siteId + '&app=' + $scope.app.id;
			url += '&task=' + task.tid;
			data.description = task.description;
			data.task_params = task.task_params;
			http2.post(url, data, function(rsp) {});
		};
	}]);
})();