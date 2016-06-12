(function() {
	ngApp.provider.controller('ctrlConfig', ['$scope', '$location', 'http2', '$uibModal', function($scope, $location, http2, $uibModal) {
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
		$scope.addPage = function() {
			$uibModal.open({
				templateUrl: 'createPage.html',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope, $mi, app) {
					$scope.app = app;
					$scope.options = {};
					$scope.ok = function() {
						$mi.close($scope.options);
					};
					$scope.cancel = function() {
						$mi.dismiss();
					};
				}],
			}).result.then(function(options) {
				http2.post('/rest/pl/fe/matter/enroll/page/add?site=' + $scope.siteId + '&app=' + $scope.id, options, function(rsp) {
					var page = rsp.data;
					$scope.app.pages.push(page);
					location.href = '/rest/pl/fe/matter/enroll/page?id=' + $scope.id + '&page=' + page.name;
				});
			});
		};
		$scope.pages4NonMember = [];
		$scope.pages4Nonfan = [];
		$scope.updateEntryRule = function() {
			var p = {
				entry_rule: encodeURIComponent(JSON.stringify($scope.app.entry_rule))
			};
			http2.post('/rest/pl/fe/matter/enroll/update?site=' + $scope.siteId + '&app=' + $scope.id, p, function(rsp) {
				$scope.persisted = angular.copy($scope.app);
			});
		};
		$scope.resetEntryRule = function() {
			http2.get('/rest/pl/fe/matter/enroll/entryRuleReset?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				$scope.app.entry_rule = rsp.data;
				$scope.persisted = angular.copy($scope.app);
			});
		};
		$scope.isInputPage = function(pageName) {
			if (!$scope.app) {
				return false;
			}
			for (var i in $scope.app.pages) {
				if ($scope.app.pages[i].name === pageName && $scope.app.pages[i].type === 'I') {
					return true;
				}
			}
			return false;
		};
		$scope.$watch('app.pages', function(pages) {
			if (!pages) return;
			$scope.pages4NonMember = [{
				name: '$memberschema',
				title: '填写自定义用户信息'
			}];
			$scope.pages4Nonfan = [{
				name: '$mpfollow',
				title: '提示关注'
			}];
			angular.forEach(pages, function(page) {
				var newPage = {
					name: page.name,
					title: page.title
				};
				$scope.pages4NonMember.push(newPage);
				$scope.pages4Nonfan.push(newPage);
			});
		}, true);
	}]);
})();