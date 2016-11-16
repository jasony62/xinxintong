define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlUser', ['$scope', '$uibModal', 'http2', 'noticebox', function($scope, $uibModal, http2, noticebox) {
		var _missionApps;
		$scope.missionApps = _missionApps = {};
		$scope.assignUserApp = function() {
			var mission = $scope.mission;
			$uibModal.open({
				templateUrl: 'assignUserApp.html',
				controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
					$scope2.data = {
						appId: ''
					};
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.data);
					};
					var url = '/rest/pl/fe/matter/enroll/list?mission=' + mission.id;
					http2.get(url, function(rsp) {
						$scope2.apps = rsp.data.apps;
					});
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				mission.user_app_id = data.appId;
				$scope.update('user_app_id').then(function(rsp) {
					var url = '/rest/pl/fe/matter/enroll/get?site=' + mission.siteid + '&id=' + data.appId;
					http2.get(url, function(rsp) {
						mission.userApp = rsp.data;
					});
				});
			});
		};
		$scope.cancelEnrollApp = function() {
			var mission = $scope.mission;
			mission.user_app_id = '';
			$scope.update('user_app_id').then(function() {
				delete mission.userApp;
			});
		};
		$scope.extract = function() {
			var url = '/rest/pl/fe/matter/mission/user/extract?mission=' + $scope.mission.id;
			http2.get(url, function(rsp) {
				noticebox.success('完成');
			});
		};
		$scope.$watch('mission', function(mission) {
			if (!mission) return;
			http2.get('/rest/pl/fe/matter/enroll/list?mission=' + mission.id, function(rsp) {
				_missionApps.enroll = rsp.data.apps;
			});
			http2.get('/rest/pl/fe/matter/signin/list?mission=' + mission.id, function(rsp) {
				_missionApps.signin = rsp.data.apps;
			});
			http2.get('/rest/pl/fe/matter/group/list?mission=' + mission.id, function(rsp) {
				_missionApps.group = rsp.data.apps;
			});
		});
	}]);
	ngApp.provider.controller('ctrlUserAction', ['$scope', '$filter', 'http2', function($scope, $filter, http2) {
		function parseEnrollAct(user) {
			var enrollAct, act2Html = {};
			enrollAct = JSON.parse(user.enroll_act);
			for (var appId in enrollAct) {
				act2Html[appId] = [];
				for (var ek in enrollAct[appId]) {
					act2Html[appId].push($filter('date')(enrollAct[appId][ek].at * 1000, 'yyyy-MM-dd HH:mm'));
				}
			}
			user.enrollAct = act2Html;
		};

		function parseSigninAct(user) {
			var signinAct, act2Html = {};
			signinAct = JSON.parse(user.signin_act);
			for (var appId in signinAct) {
				act2Html[appId] = [];
				for (var ek in signinAct[appId]) {
					act2Html[appId].push(signinAct[appId][ek].num);
				}
			}
			user.signinAct = act2Html;
		};

		function parseGroupAct(user) {
			var groupAct, act2Html = {};
			groupAct = JSON.parse(user.group_act);
			for (var appId in groupAct) {
				act2Html[appId] = [];
				for (var ek in groupAct[appId]) {
					act2Html[appId].push(groupAct[appId][ek].round);
				}
			}
			user.groupAct = act2Html;
		};

		function parseUserAct(user) {
			if (user.enroll_act) {
				parseEnrollAct(user);
			}
			if (user.signin_act) {
				parseSigninAct(user);
			}
			if (user.group_act) {
				parseGroupAct(user);
			}
		};
		var _page, users;
		$scope.page = _page = {
			at: 1,
			size: 30,
			j: function() {
				return 'page=' + this.at + '&size=' + this.size;
			}
		};
		$scope.users = users = [];
		$scope.doSearch = function() {
			var url = '/rest/pl/fe/matter/mission/user/list?mission=' + $scope.mission.id;
			url += '&' + _page.j();
			http2.get(url, function(rsp) {
				users.splice(0, users.length);
				rsp.data.users.forEach(function(user) {
					parseUserAct(user);
					users.push(user);
				});
				_page.total = rsp.data.total;
			});
		};
		$scope.doSearch();
	}]);
});