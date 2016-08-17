define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlEvent', ['$scope', '$location', 'http2', '$uibModal', function($scope, $location, http2, $uibModal) {
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
		$scope.pages4NonMember = [];
		$scope.pages4Nonfan = [];
		$scope.updateEntryRule = function() {
			var p = {
				entry_rule: encodeURIComponent(JSON.stringify($scope.app.entry_rule))
			};
			http2.post('/rest/pl/fe/matter/signin/update?site=' + $scope.siteId + '&app=' + $scope.id, p, function(rsp) {
				$scope.persisted = angular.copy($scope.app);
			});
		};
		$scope.resetEntryRule = function() {
			http2.get('/rest/pl/fe/matter/signin/entryRuleReset?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
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
		http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
			$scope.sns = rsp.data;
		});
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
});