define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', function($scope, http2) {
		var previewURL = '/rest/site/fe/matter/enroll/preview?site=' + $scope.siteId + '&app=' + $scope.id + '&start=Y';
		$scope.params = {
			openAt: 'ontime'
		};
		$scope.publish = function() {
			$scope.app.state = 2;
			$scope.update('state').then(function() {
				location.href = '/rest/pl/fe/matter/enroll/publish?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.$watch('params', function(params) {
			if (params) {
				$scope.previewURL = previewURL + '&openAt=' + params.openAt;
			}
		}, true);
	}]);
	/**
	 * app setting controller
	 */
	ngApp.provider.controller('ctrlApp', ['$scope', '$q', 'http2', function($scope, $q, http2) {
		//
		function arrangePhases(mission) {
			if (mission.phases && mission.phases.length) {
				$scope.phases = angular.copy(mission.phases);
				$scope.phases.unshift({
					title: '全部',
					phase_id: ''
				});
			}
		};
		$scope.phases = null;
		$scope.$on('xxt.tms-datepicker.change', function(event, data) {
			$scope.app[data.state] = data.value;
			$scope.update(data.state);
		});
		$scope.choosePhase = function() {
			var phaseId = $scope.app.mission_phase_id,
				i, phase, newPhase, updatedFields = ['mission_phase_id'];

			// 去掉活动标题中现有的阶段后缀
			for (i = $scope.app.mission.phases.length - 1; i >= 0; i--) {
				phase = $scope.app.mission.phases[i];
				$scope.app.title = $scope.app.title.replace('-' + phase.title, '');
				if (phase.phase_id === phaseId) {
					newPhase = phase;
				}
			}

			if (newPhase) {
				// 给活动标题加上阶段后缀
				$scope.app.title += '-' + newPhase.title;
				updatedFields.push('title');
				// 设置活动开始时间
				if ($scope.app.start_at == 0) {
					$scope.app.start_at = newPhase.start_at;
					updatedFields.push('start_at');
				}
				// 设置活动结束时间
				if ($scope.app.end_at == 0) {
					$scope.app.end_at = newPhase.end_at;
					updatedFields.push('end_at');
				}
			}

			$scope.update(updatedFields);
		};
		/*初始化页面数据*/
		if ($scope.app && $scope.app.mission) {
			arrangePhases($scope.app.mission);
		} else {
			$scope.$watch('app.mission', function(mission) {
				if (!mission) return;
				arrangePhases(mission);
			});
		}
	}]);
});