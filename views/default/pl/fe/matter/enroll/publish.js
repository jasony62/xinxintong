define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', 'templateShop', function($scope, http2, mediagallery, templateShop) {
		(function() {
			new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
		})();
		$scope.$watch('app', function(app) {
			if (!app) return;
			var entry;
			entry = {
				url: $scope.url,
				qrcode: '/rest/site/fe/matter/enroll/qrcode?site=' + $scope.siteId + '&url=' + encodeURIComponent($scope.url),
			};
			$scope.entry = entry;
		});
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
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
			$scope.app.pic = '';
			$scope.update('pic');
		};
		$scope.downloadQrcode = function(url) {
			$('<a href="' + url + '" download="登记二维码.png"></a>')[0].click();
		};
		$scope.summaryOfRecords().then(function(data) {
			$scope.summary = data;
		});
		$scope.shareAsTemplate = function() {
			templateShop.share($scope.siteId, $scope.app);
		};
	}]);
	/**
	 * 微信二维码
	 */
	ngApp.provider.controller('ctrlWxQrcode', ['$scope', 'http2', function($scope, http2) {
		$scope.create = function() {
			var url;

			url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + $scope.siteId;
			url += '&matter_type=enroll&matter_id=' + $scope.id;
			url += '&expire=864000';

			http2.get(url, function(rsp) {
				$scope.qrcode = rsp.data;
			});
		};
		$scope.download = function() {
			$('<a href="' + $scope.qrcode.pic + '" download="微信登记二维码.jpeg"></a>')[0].click();
		};
		http2.get('/rest/pl/fe/matter/enroll/wxQrcode?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
			var qrcodes = rsp.data;
			$scope.qrcode = qrcodes.length ? qrcodes[0] : false;
		});
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
	ngApp.provider.controller('ctrlPreview', ['$scope', 'http2', function($scope, http2) {
		var previewURL = '/rest/site/fe/matter/enroll/preview?site=' + $scope.siteId + '&app=' + $scope.id + '&start=Y';
		$scope.params = {
			openAt: 'ontime'
		};
		$scope.$watch('params', function(params) {
			if (params) {
				$scope.previewURL = previewURL + '&openAt=' + params.openAt;
			}
		}, true);
	}]);
	ngApp.provider.controller('ctrlRound', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
		$scope.roundState = ['新建', '启用', '停止'];
		$scope.add = function() {
			$uibModal.open({
				templateUrl: 'roundEditor.html',
				backdrop: 'static',
				resolve: {
					roundState: function() {
						return $scope.roundState;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'roundState', function($scope, $mi, roundState) {
					$scope.round = {
						state: 0
					};
					$scope.roundState = roundState;
					$scope.close = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close($scope.round);
					};
					$scope.start = function() {
						$scope.round.state = 1;
						$mi.close($scope.round);
					};
				}]
			}).result.then(function(newRound) {
				http2.post('/rest/pl/fe/matter/enroll/round/add?site=' + $scope.siteId + '&app=' + $scope.id, newRound, function(rsp) {
					!$scope.app.rounds && ($scope.app.rounds = []);
					if ($scope.app.rounds.length > 0 && rsp.data.state == 1) {
						$scope.app.rounds[0].state = 2;
					}
					$scope.app.rounds.splice(0, 0, rsp.data);
				});
			});
		};
		$scope.open = function(round) {
			$uibModal.open({
				templateUrl: 'roundEditor.html',
				backdrop: 'static',
				resolve: {
					roundState: function() {
						return $scope.roundState;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'roundState', function($scope, $mi, roundState) {
					$scope.round = angular.copy(round);
					$scope.roundState = roundState;
					$scope.close = function() {
						$mi.dismiss();
					};
					$scope.ok = function() {
						$mi.close({
							action: 'update',
							data: $scope.round
						});
					};
					$scope.remove = function() {
						$mi.close({
							action: 'remove'
						});
					};
					$scope.stop = function() {
						$scope.round.state = 2;
						$mi.close({
							action: 'update',
							data: $scope.round
						});
					};
					$scope.start = function() {
						$scope.round.state = 1;
						$mi.close({
							action: 'update',
							data: $scope.round
						});
					};
				}]
			}).result.then(function(rst) {
				var url;
				if (rst.action === 'update') {
					url = '/rest/pl/fe/matter/enroll/round/update';
					url += '?site=' + $scope.siteId;
					url += '&app=' + $scope.id;
					url += '&rid=' + round.rid;
					http2.post(url, rst.data, function(rsp) {
						if ($scope.app.rounds.length > 1 && rst.data.state == 1) {
							$scope.app.rounds[1].state = 2;
						}
						angular.extend(round, rst.data);
					});
				} else if (rst.action === 'remove') {
					url = '/rest/pl/fe/matter/enroll/round/remove';
					url += '?site=' + $scope.siteId;
					url += '&app=' + $scope.id;
					url += '&rid=' + round.rid;
					http2.get(url, function(rsp) {
						var i = $scope.app.rounds.indexOf(round);
						$scope.app.rounds.splice(i, 1);
					});
				}
			});
		};
	}]);
	ngApp.provider.controller('ctrlReceiver', ['$scope', 'http2', '$interval', function($scope, http2, $interval) {
		var baseURL = '/rest/pl/fe/matter/enroll/receiver/';
		$scope.qrcodeShown = false;
		$scope.supportQrcode = {
			wx: 'N',
			yx: 'N'
		};
		$scope.qrcode = function(snsName) {
			if ($scope.qrcodeShown === false) {
				var url = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/createOneOff';
				url += '?site=' + $scope.siteId;
				url += '&matter_type=enrollreceiver';
				url += '&matter_id=' + $scope.id;
				http2.get(url, function(rsp) {
					var qrcode = rsp.data,
						eleQrcode = $("#" + snsName + "Qrcode");
					eleQrcode.trigger('show');
					$scope.qrcodeURL = qrcode.pic;
					$scope.qrcodeShown = true;
					(function() {
						var fnCheckQrcode, url2;
						url2 = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/get';
						url2 += '?site=' + qrcode.siteid;
						url2 += '&id=' + rsp.data.id;
						fnCheckQrcode = $interval(function() {
							http2.get(url2, function(rsp) {
								if (rsp.data == false) {
									$interval.cancel(fnCheckQrcode);
									eleQrcode.trigger('hide');
									$scope.qrcodeShown = false;
									(function() {
										var fnCheckReceiver;
										fnCheckReceiver = $interval(function() {
											http2.get('/rest/pl/fe/matter/enroll/receiver/afterJoin?site=' + $scope.siteId + '&app=' + $scope.id + '&timestamp=' + qrcode.create_at, function(rsp) {
												if (rsp.data.length) {
													$interval.cancel(fnCheckReceiver);
													$scope.receivers = $scope.receivers.concat(rsp.data);
												}
											});
										}, 2000);
									})();
								}
							});
						}, 2000);
					})();
				});
			} else {
				$("#yxQrcode").trigger('hide');
				$scope.qrcodeShown = false;
			}
		};
		$scope.remove = function(receiver) {
			http2.get(baseURL + 'remove?site=' + $scope.siteId + '&app=' + $scope.id + '&receiver=' + receiver.userid, function(rsp) {
				$scope.receivers.splice($scope.receivers.indexOf(receiver), 1);
			});
		};
		http2.get(baseURL + 'list?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
			$scope.receivers = rsp.data;
		});
		http2.get('/rest/pl/fe/site/snsList?site=' + $scope.siteId, function(rsp) {
			var snsConfig = rsp.data;
			snsConfig.wx && (snsConfig.wx.can_qrcode === 'Y') && ($scope.supportQrcode.wx = 'Y');
			snsConfig.yx && (snsConfig.yx.can_qrcode === 'Y') && ($scope.supportQrcode.yx = 'Y');
		});
	}]);
});