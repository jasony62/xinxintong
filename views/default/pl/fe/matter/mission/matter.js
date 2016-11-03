define(['frame'], function(ngApp) {
	'use strict';
	ngApp.provider.controller('ctrlMatter', ['$scope', 'http2', 'templateShop', function($scope, http2, templateShop) {
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
				title: '评价',
				handler: function() {
					$scope.addEnroll('voting');
				}
			},
		};
		$scope.addByIndicator = function(indicator) {
			indicator.handler();
		};
		$scope.addArticle = function() {
			var url = '/rest/pl/fe/matter/article/create?mission=' + $scope.id,
				config = {
					proto: {
						title: $scope.editing.title + '-资料'
					}
				};
			http2.post(url, config, function(rsp) {
				location.href = '/rest/pl/fe/matter/article?id=' + rsp.data;
			});
		};
		$scope.addEnroll = function(assignedScenario) {
			templateShop.choose('enroll', assignedScenario).then(function(choice) {
				var url, config = {
					proto: {}
				};
				if (assignedScenario === 'registration') {
					config.proto.title = $scope.editing.title + '-报名';
				} else if (assignedScenario === 'voting') {
					config.proto.title = $scope.editing.title + '-评价';
				}
				if (choice) {
					var data = choice.data;
					if (choice.source === 'share') {
						url = '/rest/pl/fe/matter/enroll/createByOther?site=' + $scope.editing.siteid + '&mission=' + $scope.id + '&template=' + data.id;
					} else if (choice.source === 'platform') {
						url = '/rest/pl/fe/matter/enroll/create?site=' + $scope.editing.siteid + '&mission=' + $scope.id;
						url += '&scenario=' + data.scenario.name;
						url += '&template=' + data.template.name;
						if (data.simpleSchema && data.simpleSchema.length) {
							config.simpleSchema = data.simpleSchema;
						}
					}
				} else {
					url = '/rest/pl/fe/matter/enroll/create?site=' + $scope.editing.siteid + '&mission=' + $scope.id;
				}
				http2.post(url, config, function(rsp) {
					location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.editing.siteid + '&id=' + rsp.data.id;
				});
			});
		};
		$scope.addSignin = function() {
			var url = '/rest/pl/fe/matter/signin/create?site=' + $scope.editing.siteid + '&mission=' + $scope.id,
				config = {
					proto: {
						title: $scope.editing.title + '-签到'
					}
				};
			http2.post(url, config, function(rsp) {
				location.href = '/rest/pl/fe/matter/signin?site=' + $scope.editing.siteid + '&id=' + rsp.data.id;
			});
		};
		$scope.addGroup = function() {
			var url = '/rest/pl/fe/matter/group/create?site=' + $scope.editing.siteid + '&mission=' + $scope.id + '&scenario=split',
				config = {
					proto: {
						title: $scope.editing.title + '-分组'
					}
				};
			http2.post(url, config, function(rsp) {
				location.href = '/rest/pl/fe/matter/group?site=' + $scope.editing.siteid + '&id=' + rsp.data.id;
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
					location.href = '/rest/pl/fe/matter/' + type + '?id=' + id + '&site=' + $scope.editing.siteid;
					break;
			}
		};
		$scope.removeMatter = function(evt, matter) {
			var type = matter.type || $scope.matterType,
				id = matter.id,
				title = matter.title,
				url = '/rest/pl/fe/matter/';

			evt.stopPropagation();
			if (window.confirm('确定删除：' + title + '？')) {
				switch (type) {
					case 'article':
					case 'addressbook':
						url += type + '/remove?id=' + id + '&site=' + $scope.editing.siteid;
						break;
					case 'enroll':
					case 'signin':
					case 'group':
						url += type + '/remove?app=' + id + '&site=' + $scope.editing.siteid;
						break;
				}
				http2.get(url, function(rsp) {
					$scope.matters.splice($scope.matters.indexOf(matter), 1);
				});
			}
		};
		$scope.copyMatter = function(evt, matter) {
			var type = (matter.type || $scope.matterType),
				id = matter.id,
				url = '/rest/pl/fe/matter/';

			evt.stopPropagation();
			switch (type) {
				case 'article':
					url += type + '/copy?id=' + id + '&site=' + $scope.editing.siteid + '&mission=' + $scope.id;
					break;
				case 'enroll':
				case 'signin':
				case 'group':
					url += type + '/copy?app=' + id + '&site=' + $scope.editing.siteid + '&mission=' + $scope.id;
					break;
			}
			http2.get(url, function(rsp) {
				location.href = '/rest/pl/fe/matter/' + type + '?site=' + $scope.editing.siteid + '&id=' + rsp.data.id;
			});
		};
		$scope.matterType = '';
		$scope.list = function(matterType) {
			var url;

			matterType === undefined && (matterType = '');
			$scope.matterType = matterType;

			if (matterType === '') {
				url = '/rest/pl/fe/matter/mission/matter/list?id=' + $scope.id;
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
				url += '/list?mission=' + $scope.id;
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
});