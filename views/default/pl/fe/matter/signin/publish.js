define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlPublish', ['$scope', 'http2', 'mediagallery', function($scope, http2, mediagallery) {
		(function() {
			var text2Clipboard = new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
		})();
		$scope.opUrl = 'http://' + location.host + '/rest/site/op/matter/signin?site=' + $scope.siteId + '&app=' + $scope.id;
		$scope.downloadQrcode = function(url) {
			$('<a href="' + url + '" download="签到二维码.png"></a>')[0].click();
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
		$scope.summaryOfRecords().then(function(data) {
			$scope.summary = data;
		});
		$scope.gotoRecords = function() {
			location.href = '/rest/pl/fe/matter/signin/record?site=' + $scope.siteId + '&id=' + $scope.id;
		};
		$scope.$watch('app', function(app) {
			if (!app) return;
			var entry = {
				url: $scope.url,
				qrcode: '/rest/site/fe/matter/signin/qrcode?site=' + $scope.siteId + '&url=' + encodeURIComponent($scope.url),
			};
			$scope.entry = entry;
		});
	}]);
	/**
	 * 微信二维码
	 */
	ngApp.provider.controller('ctrlWxQrcode', ['$scope', 'http2', function($scope, http2) {
		$scope.create = function() {
			var url;

			url = '/rest/pl/fe/site/sns/wx/qrcode/create?site=' + $scope.siteId;
			url += '&matter_type=signin&matter_id=' + $scope.id;
			url += '&expire=864000';

			http2.get(url, function(rsp) {
				$scope.qrcode = rsp.data;
			});
		};
		$scope.download = function() {
			$('<a href="' + $scope.qrcode.pic + '" download="微信签到二维码.jpeg"></a>')[0].click();
		};
		http2.get('/rest/pl/fe/matter/signin/wxQrcode?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
			var qrcodes = rsp.data;
			$scope.qrcode = qrcodes.length ? qrcodes[0] : false;
		});
	}]);
	/**
	 * app setting controller
	 */
	ngApp.provider.controller('ctrlApp', ['$scope', '$uibModal', '$q', 'http2', 'mattersgallery', 'noticebox', function($scope, $uibModal, $q, http2, mattersgallery, noticebox) {
		$scope.assignMission = function() {
			mattersgallery.open($scope.siteId, function(matters, type) {
				var app;
				if (matters.length === 1) {
					app = {
						id: $scope.id,
						type: 'signin'
					};
					http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.siteId + '&id=' + matters[0].mission_id, app, function(rsp) {
						$scope.app.mission = rsp.data;
						$scope.app.mission_id = rsp.data.id;
						$scope.update('mission_id');
					});
				}
			}, {
				matterTypes: [{
					value: 'mission',
					title: '项目',
					url: '/rest/pl/fe/matter'
				}],
				singleMatter: true
			});
		};
		$scope.assignEnrollApp = function() {
			$uibModal.open({
				templateUrl: 'assignEnrollApp.html',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
					$scope2.app = app;
					$scope2.data = {
						filter: {},
						source: ''
					};
					app.mission && ($scope2.data.sameMission = 'Y');
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.data);
					};
					var url = '/rest/pl/fe/matter/enroll/list?site=' + $scope.siteId + '&scenario=registration&size=999';
					app.mission && (url += '&mission=' + app.mission.id);
					http2.get(url, function(rsp) {
						$scope2.apps = rsp.data.apps;
					});
				}],
				backdrop: 'static'
			}).result.then(function(data) {
				$scope.app.enroll_app_id = data.source;
				$scope.update('enroll_app_id').then(function(rsp) {
					var app = $scope.app,
						url = '/rest/pl/fe/matter/enroll/get?site=' + $scope.siteId + '&id=' + app.enroll_app_id;
					http2.get(url, function(rsp) {
						rsp.data.data_schemas = JSON.parse(rsp.data.data_schemas);
						app.enrollApp = rsp.data;
					});
					for (var i = app.data_schemas.length - 1; i > 0; i--) {
						if (app.data_schemas[i].id === 'mobile') {
							app.data_schemas[i].requireCheck = 'Y';
							break;
						}
					}
					$scope.update('data_schemas');
				});
			});
		};
		$scope.cancelEnrollApp = function() {
			var app = $scope.app;
			app.enroll_app_id = '';
			$scope.update('enroll_app_id');
			$scope.submit().then(function() {
				angular.forEach(app.data_schemas, function(dataSchema) {
					delete dataSchema.requireCheck;
				});
				$scope.update('data_schemas');
			});
		};
		$scope.addPage = function() {
			$scope.createPage().then(function(page) {
				$scope.choosePage(page);
			});
		};
		$scope.updPage = function(page, names) {
			var defer = $q.defer(),
				url, p = {};

			angular.isString(names) && (names = [names]);
			angular.forEach(names, function(name) {
				p[name] = name === 'html' ? encodeURIComponent(page[name]) : page[name];
			});
			url = '/rest/pl/fe/matter/signin/page/update';
			url += '?site=' + $scope.siteId;
			url += '&app=' + $scope.id;
			url += '&pid=' + page.id;
			url += '&cname=' + page.code_name;
			http2.post(url, p, function(rsp) {
				page.$$modified = false;
				noticebox.success('完成保存');
				defer.resolve();
			});
			return defer.promise;
		};
		$scope.delPage = function() {
			if (window.confirm('确定删除页面？')) {
				var url = '/rest/pl/fe/matter/signin/page/remove';
				url += '?site=' + $scope.siteId;
				url += '&app=' + $scope.id;
				url += '&pid=' + $scope.ep.id;
				url += '&cname=' + $scope.ep.code_name;
				http2.get(url, function(rsp) {
					$scope.app.pages.splice($scope.app.pages.indexOf($scope.ep), 1);
					if ($scope.app.pages.length) {
						$scope.choosePage($scope.app.pages[0]);
					} else {
						$scope.ep = null;
					}
				});
			}
		};
		$scope.choosePhase = function() {
			var phaseId = $scope.app.mission_phase_id,
				i, phase, newPhase;
			for (i = $scope.app.mission.phases.length - 1; i >= 0; i--) {
				phase = $scope.app.mission.phases[i];
				$scope.app.title = $scope.app.title.replace('-' + phase.title, '');
				if (phase.phase_id === phaseId) {
					newPhase = phase;
				}
			}
			if (newPhase) {
				$scope.app.title += '-' + newPhase.title;
			}
			$scope.update(['mission_phase_id', 'title']).then(function() {
				/* 如果活动只有一个轮次，且没有指定过时间，用阶段的时间更新 */
				if (newPhase && $scope.app.rounds.length === 1) {
					(function() {
						var round = $scope.app.rounds[0],
							url;
						if (round.start_at === '0' && round.end_at === '0') {
							url = '/rest/pl/fe/matter/signin/round/update';
							url += '?site=' + $scope.siteId;
							url += '&app=' + $scope.id;
							url += '&rid=' + round.rid;
							http2.post(url, {
								start_at: newPhase.start_at,
								end_at: newPhase.end_at
							}, function(rsp) {
								round.start_at = newPhase.start_at;
								round.end_at = newPhase.end_at;
							});
						}
					})();
				}
			});
		};
	}]);
	ngApp.provider.controller('ctrlRound', ['$scope', '$uibModal', 'http2', 'noticebox', function($scope, $uibModal, http2, noticebox) {
		$scope.batch = function() {
			$uibModal.open({
				templateUrl: 'batchRounds.html',
				backdrop: 'static',
				resolve: {
					app: function() {
						return $scope.app;
					}
				},
				controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
					var params = {
						timesOfDay: 2,
						overwrite: 'Y'
					};
					if (app.mission && app.mission_phase_id) {
						(function() {
							var i, phase;
							for (i = app.mission.phases.length - 1; i >= 0; i--) {
								phase = app.mission.phases[i];
								if (app.mission_phase_id === phase.phase_id) {
									params.start_at = phase.start_at;
									params.end_at = phase.end_at;
									break;
								}
							}
						})();
					} else {
						/*设置阶段的缺省起止时间*/
						(function() {
							var nextDay = new Date();
							nextDay.setTime(nextDay.getTime() + 86400000);
							params.start_at = nextDay.setHours(0, 0, 0, 0) / 1000;
							params.end_at = nextDay.setHours(23, 59, 59, 0) / 1000;
						})();
					}
					$scope2.params = params;
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.close($scope2.params);
					};
				}]
			}).result.then(function(params) {
				http2.post('/rest/pl/fe/matter/signin/round/batch?site=' + $scope.siteId + '&app=' + $scope.id, params, function(rsp) {
					if (params.overwrite === 'Y') {
						$scope.app.rounds = rsp.data;
					} else {
						$scope.app.rounds = $scope.rounds.concat(rsp.data);
					}
					$scope.rounds = $scope.app.rounds;
				});
			});
		};
		$scope.add = function() {
			var newRound = {
				title: '轮次' + ($scope.rounds.length + 1),
				start_at: Math.round((new Date()).getTime() / 1000),
				end_at: Math.round((new Date()).getTime() / 1000) + 7200,
			};
			http2.post('/rest/pl/fe/matter/signin/round/add?site=' + $scope.siteId + '&app=' + $scope.id, newRound, function(rsp) {
				$scope.rounds.push(rsp.data);
			});
		};
		$scope.update = function(round, prop) {
			var url = '/rest/pl/fe/matter/signin/round/update',
				posted = {};
			url += '?site=' + $scope.siteId;
			url += '&app=' + $scope.id;
			url += '&rid=' + round.rid;
			posted[prop] = round[prop];
			http2.post(url, posted, function(rsp) {
				noticebox.success('完成保存');
			});
		};
		$scope.$on('xxt.tms-datepicker.change', function(event, data) {
			data.obj[data.state] = data.value;
			$scope.update(data.obj, data.state);
		});
		$scope.remove = function(round) {
			var url;
			if (window.confirm('确定删除：' + round.title + '？')) {
				url = '/rest/pl/fe/matter/signin/round/remove';
				url += '?site=' + $scope.siteId;
				url += '&app=' + $scope.id;
				url += '&rid=' + round.rid;
				http2.get(url, function(rsp) {
					$scope.rounds.splice($scope.rounds.indexOf(round), 1);
				});
			}
		};
		$scope.qrcode = function(round) {
			$uibModal.open({
				templateUrl: 'roundQrcode.html',
				backdrop: 'static',
				resolve: {
					round: function() {
						return round;
					}
				},
				controller: ['$scope', '$timeout', '$uibModalInstance', 'round', function($scope2, $timeout, $mi, round) {
					var popover = {
							title: round.title,
							url: $scope.url + '&round=' + round.rid,
						},
						zeroClipboard;

					popover.qrcode = '/rest/site/fe/matter/signin/qrcode?site=' + $scope.siteId + '&url=' + encodeURIComponent(popover.url);
					$scope2.popover = popover;
					$scope2.downloadQrcode = function(url) {
						$('<a href="' + url + '" download="' + round.title + '签到二维码.png"></a>')[0].click();
					};
					$scope2.cancel = function() {
						$mi.dismiss();
					};
					$scope2.ok = function() {
						$mi.dismiss();
					};
					$timeout(function() {
						new ZeroClipboard(document.querySelector('#copyURL'));
					});
				}]
			}).result.then(function() {});
		};
		$scope.$watch('app', function(app) {
			if (app) {
				$scope.rounds = app.rounds;
			}
		});
	}]);
});