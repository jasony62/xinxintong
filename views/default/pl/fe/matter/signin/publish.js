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
	 * 访问控制规则
	 */
	ngApp.provider.controller('ctrlAccessRule', ['$scope', 'http2', function($scope, http2) {
		var firstInputPage;
		$scope.pages4NonMember = [];
		$scope.pages4Nonfan = [];
		$scope.rule = {};
		$scope.updateEntryRule = function() {
			var p = {
				entry_rule: encodeURIComponent(JSON.stringify($scope.app.entry_rule))
			};
			http2.post('/rest/pl/fe/matter/signin/update?site=' + $scope.siteId + '&app=' + $scope.id, p, function(rsp) {});
		};
		$scope.reset = function() {
			http2.get('/rest/pl/fe/matter/signin/entryRuleReset?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
				$scope.app.entry_rule = rsp.data;
			});
		};
		$scope.changeUserScope = function() {
			var entryRule = $scope.app.entry_rule;
			entryRule.scope = $scope.rule.scope;
			switch ($scope.rule.scope) {
				case 'member':
					entryRule.member === undefined && (entryRule.member = {});
					entryRule.other === undefined && (entryRule.other = {});
					entryRule.other.entry = '$memberschema';
					$scope.memberSchemas.forEach(function(ms) {
						entryRule.member[ms.id] = {
							entry: firstInputPage ? firstInputPage.name : ''
						};
					});
					break;
				case 'sns':
					entryRule.sns === undefined && (entryRule.sns = {});
					entryRule.other === undefined && (entryRule.other = {});
					entryRule.other.entry = '$mpfollow';
					Object.keys($scope.sns).forEach(function(snsName) {
						entryRule.sns[snsName] = {
							entry: firstInputPage ? firstInputPage.name : ''
						};
					});
					break;
				default:
			}
			$scope.updateEntryRule();
		};
		$scope.$watch('app', function(app) {
			if (!app) return;
			var pages = app.pages;
			$scope.rule.scope = app.entry_rule.scope || 'none';
			$scope.pages4NonMember = [{
				name: '$memberschema',
				title: '填写自定义用户信息'
			}];
			$scope.pages4Nonfan = [{
				name: '$mpfollow',
				title: '提示关注'
			}];
			pages.forEach(function(page) {
				var newPage = {
					name: page.name,
					title: page.title
				};
				$scope.pages4NonMember.push(newPage);
				$scope.pages4Nonfan.push(newPage);
				page.type === 'I' && (firstInputPage = newPage);
			});
		}, true);
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
						var mission = rsp.data,
							app = $scope.app,
							updatedFields = ['mission_id'];

						app.mission = mission;
						app.mission_id = mission.id;
						if (!app.pic || app.pic.length === 0) {
							app.pic = mission.pic;
							updatedFields.push('pic');
						}
						if (!app.summary || app.summary.length === 0) {
							app.summary = mission.summary;
							updatedFields.push('summary');
						}
						$scope.update(updatedFields);
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