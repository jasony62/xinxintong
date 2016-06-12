(function() {
	ngApp.provider.controller('ctrlCoin', ['$scope', 'http2', '$uibModal', '$timeout', function($scope, http2, $uibModal, $timeout) {
		var prefix = 'app.enroll,' + $scope.id,
			actions = [{
				name: 'record.submit',
				desc: '用户A成功提交登记记录'
			}, {
				name: 'share.F',
				desc: '用户A转发好友',
			}, {
				name: 'share.T',
				desc: '用户A分享至朋友圈',
			}, {
				name: 'invite.success',
				desc: '用户A邀请用户B参与成功',
			}];
		$scope.$parent.subView = 'coin';
		$scope.rules = {};
		angular.forEach(actions, function(act) {
			var name;
			name = prefix + '.' + act.name;
			$scope.rules[name] = {
				act: name,
				desc: act.desc,
				delta: 0
			};
		});
		$scope.save = function() {
			var posted, url;
			posted = [];
			angular.forEach($scope.rules, function(rule) {
				if (rule.id || rule.delta != 0) {
					var data;
					data = {
						act: rule.act,
						delta: rule.delta,
						objid: '*'
					};
					rule.id && (data.id = rule.id);
					posted.push(data);
				}
			});
			url = '/rest/mp/app/enroll/coin/save';
			http2.post(url, posted, function(rsp) {
				$scope.$root.infomsg = '保存成功';
				angular.forEach(rsp.data, function(id, act) {
					$scope.rules[act].id = id;
				});
			});
		};
		$scope.fetch = function() {
			var url;
			url = '/rest/mp/app/enroll/coin/get?aid=' + $scope.aid;
			http2.get(url, function(rsp) {
				angular.forEach(rsp.data, function(rule) {
					$scope.rules[rule.act].id = rule.id;
					$scope.rules[rule.act].delta = rule.delta;
				});
			});
		};
		$scope.fetch();
	}]);
})();