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
            var url = '/rest/pl/fe/matter/article/create?mission=' + $scope.mission.id,
                config = {
                    proto: {
                        title: $scope.mission.title + '-资料'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/article?id=' + rsp.data + '&site=' + $scope.mission.siteid;
            });
        };
        $scope.addEnroll = function(assignedScenario) {
            templateShop.choose($scope.mission.siteid, 'enroll', assignedScenario).then(function(choice) {
                var url, config = {
                    proto: {}
                };
                if (assignedScenario === 'registration') {
                    config.proto.title = $scope.mission.title + '-报名';
                } else if (assignedScenario === 'voting') {
                    config.proto.title = $scope.mission.title + '-评价';
                } else if (assignedScenario === 'group_week_report') {
                    config.proto.title = $scope.mission.title + '-周报';
                } else if (assignedScenario === 'common') {
                    config.proto.title = $scope.mission.title + '-登记';
                }
                if (choice) {
                    var data = choice.data;
                    if (choice.source === 'share') {
                        url = '/rest/pl/fe/matter/enroll/createByOther?site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id + '&template=' + data.id;
                    } else if (choice.source === 'platform') {
                        url = '/rest/pl/fe/matter/enroll/create?site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id;
                        url += '&scenario=' + data.scenario.name;
                        url += '&template=' + data.template.name;
                        if (data.simpleSchema && data.simpleSchema.length) {
                            config.simpleSchema = data.simpleSchema;
                        }
                    }
                } else {
                    url = '/rest/pl/fe/matter/enroll/create?site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id;
                }
                http2.post(url, config, function(rsp) {
                    location.href = '/rest/pl/fe/matter/enroll?site=' + $scope.mission.siteid + '&id=' + rsp.data.id;
                });
            });
        };
        $scope.addSignin = function() {
            var url = '/rest/pl/fe/matter/signin/create?site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id,
                config = {
                    proto: {
                        title: $scope.mission.title + '-签到'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/signin?site=' + $scope.mission.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.addGroup = function() {
            var url = '/rest/pl/fe/matter/group/create?site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id + '&scenario=split',
                config = {
                    proto: {
                        title: $scope.mission.title + '-分组'
                    }
                };
            http2.post(url, config, function(rsp) {
                location.href = '/rest/pl/fe/matter/group/main?site=' + $scope.mission.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.addMatter = function(matterType) {
            $('body').click();
            if (/voting|registration|group_week_report|common/.test(matterType)) {
                $scope.addEnroll(matterType);
            } else {
                $scope['add' + matterType[0].toUpperCase() + matterType.substr(1)]();
            }
        };
        $scope.open = function(matter, subView) {
            var url = '/rest/pl/fe/matter/',
                type = matter.type || $scope.matterType,
                id = matter.id;
            url += type;
            if (subView) {
                url += '/' + subView;
            }
            switch (type) {
                case 'article':
                case 'enroll':
                case 'group':
                case 'signin':
                    location.href = url + '?id=' + id + '&site=' + $scope.mission.siteid;
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
                        url += type + '/remove?id=' + id + '&site=' + $scope.mission.siteid;
                        break;
                    case 'enroll':
                    case 'signin':
                    case 'group':
                        url += type + '/remove?app=' + id + '&site=' + $scope.mission.siteid;
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
                    url += type + '/copy?id=' + id + '&site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id;
                    break;
                case 'enroll':
                case 'signin':
                case 'group':
                    url += type + '/copy?app=' + id + '&site=' + $scope.mission.siteid + '&mission=' + $scope.mission.id;
                    break;
            }
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/' + type + '?site=' + $scope.mission.siteid + '&id=' + rsp.data.id;
            });
        };
        $scope.list = function(matterType) {
            var url;

            matterType === undefined && (matterType = '');

            if (matterType === '') {
                url = '/rest/pl/fe/matter/mission/matter/list?id=' + $scope.mission.id;
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
                if ('enroll' === matterType) {
                    url += 'enroll';
                    scenario = '';
                } else if (/registration|voting|group_week_report|common/.test(matterType)) {
                    url += 'enroll'
                    scenario = $scope.matterType;
                } else {
                    url += matterType;
                }
                url += '/list?mission=' + $scope.mission.id;
                scenario !== undefined && (url += '&scenario=' + scenario);
                url += '&_=' + (new Date() * 1);
                http2.get(url, function(rsp) {
                    $scope.indicators = [];
                    if (/article/.test(matterType)) {
                        $scope.matters = rsp.data.articles;
                        if (rsp.data.total == 0) {
                            indicators.article && $scope.indicators.push(indicators.article);
                        }
                    } else if (/enroll|voting|registration|group_week_report|common|signin|group/.test(matterType)) {
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
        $scope.$watch('mission', function(nv) {
            $scope.$watch('matterType', function(matterType) {
                if (matterType === undefined) return;
                $scope.list(matterType);
            });
            if (!nv) return;
            $scope.matterType = location.hash ? location.hash.substr(1) : '';
        });
    }]);
});
