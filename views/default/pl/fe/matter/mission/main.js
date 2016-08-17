var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt']);
ngApp.config(['$controllerProvider', '$locationProvider', '$uibTooltipProvider', function($controllerProvider, $locationProvider, $uibTooltipProvider) {
	ngApp.provider = {
		controller: $controllerProvider.register
	};
	$locationProvider.html5Mode(true);
	$uibTooltipProvider.setTriggers({
		'show': 'hide'
	});
}]);
ngApp.controller('ctrlApp', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	$scope.id = $location.search().id;
	$scope.siteId = $location.search().site;
	$scope.back = function() {
		history.back();
	};
	http2.get('/rest/pl/fe/matter/mission/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
		var mission = rsp.data;
		mission.type = 'mission';
		mission.extattrs = (mission.extattrs && mission.extattrs.length) ? JSON.parse(mission.extattrs) : {};
		$scope.editing = mission;
	});
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', '$uibModal', 'mediagallery', 'noticebox', function($scope, http2, $uibModal, mediagallery, noticebox) {
	var modifiedData = {};
	$scope.modified = false;
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
	$scope.sub = 'basic';
	$scope.subView = '/views/default/pl/fe/matter/mission/basic.html?_=7';
	$scope.gotoSub = function(sub) {
		$scope.sub = sub;
		$scope.subView = '/views/default/pl/fe/matter/mission/' + sub + '.html?_=7';
	};
	$scope.submit = function() {
		http2.post('/rest/pl/fe/matter/mission/setting/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function(rsp) {
			$scope.modified = false;
			modifiedData = {};
			noticebox.success('完成保存');
		});
	};
	$scope.remove = function() {
		if (window.confirm('确定删除项目？')) {
			http2.get('/rest/pl/fe/matter/mission/remove?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
				history.back();
			});
		}
	};
	$scope.update = function(name) {
		modifiedData[name] = $scope.editing[name];
		$scope.modified = true;
		$scope.submit();
	};
	$scope.setPic = function() {
		var options = {
			callback: function(url) {
				$scope.editing.pic = url + '?_=' + (new Date()) * 1;
				$scope.update('pic');
			}
		};
		mediagallery.open($scope.siteId, options);
	};
	$scope.removePic = function() {
		var nv = {
			pic: ''
		};
		http2.post('/rest/pl/fe/matter/mission/setting/update?site=' + $scope.siteId + '&id=' + $scope.id, nv, function() {
			$scope.editing.pic = '';
		});
	};
	$scope.$on('xxt.tms-datepicker.change', function(event, data) {
		var prop;
		if (data.state.indexOf('mission.') === 0) {
			prop = data.state.substr(8);
			$scope.editing[prop] = data.value;
			$scope.update(prop);
		}
	});
	$scope.editPage = function(event, page) {
		event.preventDefault();
		event.stopPropagation();
		var prop = page + '_page_name',
			codeName = $scope.editing[prop];
		if (codeName && codeName.length) {
			location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeName;
		} else {
			http2.get('/rest/pl/fe/matter/mission/setting/pageCreate?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page, function(rsp) {
				$scope.editing[prop] = rsp.data.name;
				location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
			});
		}
	};
	$scope.resetPage = function(event, page) {
		event.preventDefault();
		event.stopPropagation();
		if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
			var codeName = $scope.editing[page + '_page_name'];
			if (codeName && codeName.length) {
				http2.get('/rest/pl/fe/matter/mission/setting/pageReset?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page, function(rsp) {
					location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + codeName;
				});
			} else {
				http2.get('/rest/pl/fe/matter/mission/setting/pageCreate?site=' + $scope.siteId + '&id=' + $scope.id + '&page=' + page, function(rsp) {
					$scope.editing[prop] = rsp.data.name;
					location.href = '/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + rsp.data.name;
				});
			}
		}
	};
}]);
ngApp.controller('ctrlPhase', ['$scope', 'http2', 'noticebox', function($scope, http2, noticebox) {
	$scope.numberOfNewPhases = 1;
	var newPhase = function() {
		var data = {
			title: '阶段' + ($scope.phases.length + 1)
		};
		/*设置阶段的缺省起止时间*/
		(function() {
			var nextDay = new Date(),
				lastEndAt;
			if ($scope.phases.length) {
				lastEndAt = 0;
				angular.forEach($scope.phases, function(phase) {
					if (phase.end_at > lastEndAt) {
						lastEndAt = phase.end_at;
					}
				});
				/* 最后一天的下一天 */
				nextDay.setTime(lastEndAt * 1000 + 86400000);
			} else {
				/* tomorrow */
				nextDay.setTime(nextDay.getTime() + 86400000);
			}
			data.start_at = nextDay.setHours(0, 0, 0, 0) / 1000;
			data.end_at = nextDay.setHours(23, 59, 59, 0) / 1000;
		})();

		return data;
	};
	$scope.add = function() {
		var phase;
		if ($scope.numberOfNewPhases > 0) {
			phase = newPhase();
			http2.post('/rest/pl/fe/matter/mission/phase/create?site=' + $scope.siteId + '&mission=' + $scope.id, phase, function(rsp) {
				$scope.phases.push(rsp.data);
				$scope.numberOfNewPhases--;
				if ($scope.numberOfNewPhases > 0) {
					$scope.add();
				}
			});
		}
	};
	$scope.update = function(phase, name) {
		var modifiedData = {};
		modifiedData[name] = phase[name];
		http2.post('/rest/pl/fe/matter/mission/phase/update?site=' + $scope.siteId + '&mission=' + $scope.id + '&id=' + phase.phase_id, modifiedData, function(rsp) {
			noticebox.success('完成保存');
		});
	};
	$scope.remove = function(phase) {
		http2.get('/rest/pl/fe/matter/mission/phase/remove?site=' + $scope.siteId + '&mission=' + $scope.id + '&id=' + phase.phase_id, function(rsp) {
			$scope.phases.splice($scope.phases.indexOf(phase), 1);
		});
	};
	$scope.$on('xxt.tms-datepicker.change', function(event, data) {
		var prop;
		if (data.state.indexOf('phase.') === 0) {
			prop = data.state.substr(6);
			data.obj[prop] = data.value;
			$scope.update(data.obj, prop);
		}
	});
	http2.get('/rest/pl/fe/matter/mission/phase/list?site=' + $scope.siteId + '&mission=' + $scope.id, function(rsp) {
		$scope.phases = rsp.data;
	});
}]);
ngApp.controller('ctrlCoworker', ['$scope', 'http2', function($scope, http2) {
	$scope.label = '';
	$scope.add = function() {
		var url = '/rest/pl/fe/matter/mission/coworker/add?site=' + $scope.siteId + '&mission=' + $scope.id;
		url += '&label=' + $scope.label;
		http2.get(url, function(rsp) {
			$scope.coworkers.splice(0, 0, rsp.data);
			$scope.label = '';
		});
	};
	$scope.remove = function(acl) {
		http2.get('/rest/pl/fe/matter/mission/coworker/remove?site=' + $scope.siteId + '&mission=' + $scope.id + '&coworker=' + acl.coworker, function(rsp) {
			var index = $scope.coworkers.indexOf(acl);
			$scope.coworkers.splice(index, 1);
		});
	};
	$scope.makeInvite = function() {
		http2.get('/rest/pl/fe/matter/mission/coworker/makeInvite?site=' + $scope.siteId + '&mission=' + $scope.id, function(rsp) {
			var url = 'http://' + location.host + rsp.data;
			$scope.inviteURL = url;
			$('#shareMission').trigger('show');
		});
	};
	$scope.closeInvite = function() {
		$scope.inviteURL = '';
		$('#shareMission').trigger('hide');
	};
	http2.get('/rest/pl/fe/matter/mission/coworker/list?site=' + $scope.siteId + '&mission=' + $scope.id, function(rsp) {
		$scope.coworkers = rsp.data;
	});
}]);
ngApp.controller('ctrlMatter', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
	var indicators = {
		registration: {
			title: '在线报名',
			handler: function() {
				$scope.addEnroll('registration');
			}
		},
		signin: {
			title: '签到',
			handler: function() {
				$scope.addSignin();
			}
		},
		group: {
			title: '分组',
			handler: function() {
				$scope.addGroup();
			}
		},
		voting: {
			title: '问卷',
			handler: function() {
				$scope.addEnroll('voting');
			}
		},
	};
	$scope.addByIndicator = function(indicator) {
		indicator.handler();
	};
	$scope.addArticle = function() {
		var url = '/rest/pl/fe/matter/article/create?site=' + $scope.siteId + '&mission=' + $scope.id,
			config = {
				proto: {
					title: $scope.editing.title + '-资料'
				}
			};
		http2.post(url, config, function(rsp) {
			location.href = '/rest/pl/fe/matter/article?site=' + $scope.siteId + '&id=' + rsp.data;
		});
	};
	$scope.addEnroll = function(assignedScenario) {
		$uibModal.open({
			templateUrl: '/views/default/pl/fe/_module/enroll-template.html',
			backdrop: 'static',
			windowClass: 'auto-height template',
			controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
				$scope2.data = {};
				$scope2.cancel = function() {
					$mi.dismiss();
				};
				$scope2.blank = function() {
					$mi.close();
				};
				$scope2.ok = function() {
					$mi.close($scope2.data);
				};
				$scope2.chooseScenario = function() {};
				$scope2.chooseTemplate = function() {
					if (!$scope2.data.template) return;
					var url;
					url = '/rest/pl/fe/matter/enroll/template/config';
					url += '?scenario=' + $scope2.data.scenario.name;
					url += '&template=' + $scope2.data.template.name;
					http2.get(url, function(rsp) {
						var elSimulator, url;
						$scope2.data.simpleSchema = rsp.data.simpleSchema ? rsp.data.simpleSchema : '';
						$scope2.pages = rsp.data.pages;
						$scope2.data.selectedPage = $scope2.pages[0];
						elSimulator = document.querySelector('#simulator');
						url = 'http://' + location.host;
						url += '/rest/site/fe/matter/enroll/template';
						url += '?scenario=' + $scope2.data.scenario.name;
						url += '&template=' + $scope2.data.template.name;
						url += '&_=' + (new Date()).getTime();
						elSimulator.src = url;
						elSimulator.onload = function() {
							$scope.$apply(function() {
								$scope2.choosePage();
							});
						};
					});
				};
				$scope2.choosePage = function() {
					var elSimulator, page;
					elSimulator = document.querySelector('#simulator');
					config = {
						simpleSchema: $scope2.data.simpleSchema
					};
					page = $scope2.data.selectedPage.name;
					if (elSimulator.contentWindow.renew) {
						elSimulator.contentWindow.renew(page, config);
					}
				};
				http2.get('/rest/pl/fe/matter/enroll/template/list', function(rsp) {
					var keysOfTemplate;
					$scope2.templates = rsp.data;
					if (assignedScenario) {
						$scope2.data.scenario = $scope2.templates[assignedScenario];
						keysOfTemplate = Object.keys($scope2.data.scenario.templates);
						if (keysOfTemplate.length) {
							$scope2.data.template = $scope2.data.scenario.templates[keysOfTemplate[0]];
							$scope2.chooseTemplate();
						}
						$scope2.fixedScenario = true;
					}
				});
			}]
		}).result.then(function(data) {
			var url, config;
			url = '/rest/pl/fe/matter/enroll/create?site=' + $scope.siteId + '&mission=' + $scope.id;
			config = {
				proto: {
					title: $scope.editing.title + '-报名'
				}
			};
			if (data) {
				url += '&scenario=' + data.scenario.name;
				url += '&template=' + data.template.name;
				if (data.simpleSchema && data.simpleSchema.length) {
					config.simpleSchema = data.simpleSchema;
				}
			}
			http2.post(url, config, function(rsp) {
				location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.siteId + '&id=' + rsp.data.id;
			});
		});
	};
	$scope.addSignin = function() {
		var url = '/rest/pl/fe/matter/signin/create?site=' + $scope.siteId + '&mission=' + $scope.id,
			config = {
				proto: {
					title: $scope.editing.title + '-签到'
				}
			};
		http2.post(url, config, function(rsp) {
			location.href = '/rest/pl/fe/matter/signin?site=' + $scope.siteId + '&id=' + rsp.data.id;
		});
	};
	$scope.addGroup = function() {
		var url = '/rest/pl/fe/matter/group/create?site=' + $scope.siteId + '&mission=' + $scope.id + '&scenario=split',
			config = {
				proto: {
					title: $scope.editing.title + '-分组'
				}
			};
		http2.post(url, config, function(rsp) {
			location.href = '/rest/pl/fe/matter/group?site=' + $scope.siteId + '&id=' + rsp.data.id;
		});
	};
	$scope.addMatter = function() {
		if (/voting|registration/.test($scope.matterType)) {
			$scope.addEnroll($scope.matterType);
		} else {
			$scope['add' + $scope.matterType[0].toUpperCase() + $scope.matterType.substr(1)]();
		}
	};
	$scope.open = function(matter) {
		var type = matter.type || $scope.matterType,
			id = matter.id;
		switch (type) {
			case 'article':
			case 'enroll':
			case 'group':
			case 'signin':
				location.href = '/rest/pl/fe/matter/' + type + '?id=' + id + '&site=' + $scope.siteId;
				break;
		}
	};
	$scope.matterType = '';
	$scope.list = function(matterType) {
		var url;

		matterType === undefined && (matterType = '');
		$scope.matterType = matterType;

		if (matterType === '') {
			url = '/rest/pl/fe/matter/mission/matter/list?site=' + $scope.siteId + '&id=' + $scope.id;
			url += '&_=' + (new Date() * 1);

			http2.get(url, function(rsp) {
				var typeCount = {};
				angular.forEach(rsp.data, function(matter) {
					matter._operator = matter.modifier_name || matter.creater_name;
					matter._operateAt = matter.modifiy_at || matter.create_at;
					if (matter.type === 'enroll') {
						typeCount[matter.scenario] ? typeCount[matter.scenario]++ : (typeCount[matter.scenario] = 1);
					} else {
						typeCount[matter.type] ? typeCount[matter.type]++ : (typeCount[matter.type] = 1);
					}
				});
				$scope.matters = rsp.data;
				$scope.indicators = [];
				if (matterType === '') {
					!typeCount.registration && $scope.indicators.push(indicators.registration);
					!typeCount.signin && $scope.indicators.push(indicators.signin);
					!typeCount.group && $scope.indicators.push(indicators.group);
					!typeCount.voting && $scope.indicators.push(indicators.voting);
				}
			});
		} else {
			var scenario;
			url = '/rest/pl/fe/matter/';
			if (/registration|voting/.test(matterType)) {
				url += 'enroll'
				scenario = $scope.matterType;
			} else {
				url += matterType;
			}
			url += '/list?site=' + $scope.siteId;
			url += '&mission=' + $scope.id;
			scenario && (url += '&scenario=' + scenario);
			url += '&_=' + (new Date() * 1);
			http2.get(url, function(rsp) {
				$scope.indicators = [];
				if (/article/.test(matterType)) {
					$scope.matters = rsp.data.articles;
					if (rsp.data.total == 0) {
						indicators.article && $scope.indicators.push(indicators.article);
					}
				} else if (/enroll|voting|registration|signin|group/.test(matterType)) {
					$scope.matters = rsp.data.apps;
					if (rsp.data.total == 0) {
						indicators[matterType] && $scope.indicators.push(indicators[matterType]);
					}
				} else {
					$scope.matters = rsp.data;
				}
			});
		}
	};
	$scope.list();
}]);