define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlEvent', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
		$scope.pages4NonMember = [];
		$scope.pages4Nonfan = [];
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
		/*直接给进入规则创建页面*/
		$scope.newPage = function(prop) {
			$scope.createPage().then(function(page) {
				var rule = $scope.entryRule;
				prop = prop.split('.');
				if (!angular.isObject(rule[prop[0]])) {
					rule[prop[0]] = {};
				}
				rule[prop[0]][prop[1]] = page.name;
				$scope.update('entry_rule');
			});
		};
		$scope.$watch('app', function(app) {
			if (!app) return;

			$scope.entryRule = $scope.app.entry_rule;

			$scope.pages4NonMember = [{
				name: '$memberschema',
				title: '填写自定义用户信息'
			}];
			$scope.pages4Nonfan = [{
				name: '$mpfollow',
				title: '提示关注'
			}];
			angular.forEach(app.pages, function(page) {
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