define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		$scope.$watch('app', function(app) {
			if (!app) return;
			var entry = {},
				i, l, page, signinUrl;
			entry = {
				url: $scope.url,
				qrcode: '/rest/pl/fe/matter/enroll/qrcode?url=' + encodeURIComponent($scope.url),
			};
			$scope.entry = entry;
		});
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/enroll?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.stop = function() {
			$scope.app.state = 1;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/enroll/app?site=' + $scope.siteId + '&id=' + $scope.id;
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
			$scope.app.pic = '';
			$scope.update('pic');
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
					var qrcode = rsp.data;
					$("#yxQrcode").trigger('show');
					$scope.qrcodeURL = qrcode.pic;
					$scope.qrcodeShown = true;
					(function() {
						var fnCheckQrcode, url2;
						url2 = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/get';
						url2 += '?site=' + $scope.siteId;
						url2 += '&id=' + rsp.data.id;
						fnCheckQrcode = $interval(function() {
							http2.get(url2, function(rsp) {
								if (rsp.data == false) {
									$interval.cancel(fnCheckQrcode);
									$("#yxQrcode").trigger('hide');
									$scope.qrcodeShown = false;
									(function() {
										var fnCheckReceiver, url3;
										url3 = '/rest/pl/fe/site/sns/' + snsName + '/qrcode/get';
										url3 += '?site=' + $scope.siteId;
										url3 += '&id=' + rsp.data.id;
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
});