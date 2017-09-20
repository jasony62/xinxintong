define(['require'], function(require) {
    'use strict';
    var ngApp, ls, siteId, missionId;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';

    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', '$uibTooltipProvider', 'srvSiteProvider', function($locationProvider, $uibTooltipProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        $uibTooltipProvider.setTriggers({
            'show': 'hide'
        });
        srvSiteProvider.config(siteId);
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$location', '$timeout', 'http2', 'srvSite', function($scope, $location, $timeout, http2, srvSite) {
        $scope.source = 'platform';
        $scope.switchSource = function(source) {
            $scope.source = source;
        };
        $scope.blank = function() {
            var url;
            url = '/rest/pl/fe/matter/enroll/create?site=' + siteId;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            http2.post(url, {}, function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll?site=' + siteId + '&id=' + rsp.data.id;
            });
        };
        $scope.doCreate = function() {
            $scope.$broadcast('doCreate.shop');
        };
    }]);
    ngApp.controller('ctrlSysTemplate', ['$scope', '$location', '$uibModal', 'http2', 'srvSite', function($scope, $location, $uibModal, http2, srvSite) {
        var assignedScenario, _oProto, _oEntryRule;

        assignedScenario = $location.search().scenario;
        $scope.result = {
            proto: {
                entryRule: {
                    scope: '',
                    mschemas: [],
                }
            }
        };
        $scope.proto = _oProto = $scope.result.proto;
        $scope.entryRule = _oEntryRule = _oProto.entryRule;
        $scope.chooseMschema = function() {
            srvSite.chooseMschema().then(function(result) {
                var oChosen = result.chosen;
                _oEntryRule.mschemas.push({ id: oChosen.id, title: oChosen.title });
            });
        };
        $scope.removeMschema = function(oMschema) {
            var mschemas = _oEntryRule.mschemas;
            mschemas.splice(mschemas.indexOf(oMschema), 1);
        };
        $scope.chooseGroupApp = function() {
            $uibModal.open({
                templateUrl: 'chooseGroupApp.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = {
                        app: null,
                        round: null
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                    var url = '/rest/pl/fe/matter/group/list?site=' + siteId + '&size=999&cascaded=Y';
                    url += '&mission=' + missionId;
                    http2.get(url, function(rsp) {
                        $scope2.apps = rsp.data.apps;
                    });
                }],
                backdrop: 'static'
            }).result.then(function(result) {
                if (result.app) {
                    _oEntryRule.group = { id: result.app.id, title: result.app.title };
                    if (result.round) {
                        _oEntryRule.group.round = { id: result.round.round_id, title: result.round.title };
                    }
                }
            });
        };
        $scope.removeGroupApp = function() {
            delete _oEntryRule.group;
        };
        $scope.assignGroupApp = function() {
            srvSite.openGallery({
                matterTypes: [{
                    value: 'group',
                    title: '分组活动',
                    url: '/rest/pl/fe/matter'
                }],
                singleMatter: true,
                mission: _oProto.mission,
                onlySameMission: true
            }).then(function(result) {
                var oGroupApp, oChosen;
                if (result.matters.length === 1) {
                    oChosen = result.matters[0];
                    oGroupApp = { id: oChosen.id, title: oChosen.title };
                    _oProto.groupApp = oGroupApp;
                }
            });
        };
        $scope.assignEnrollApp = function() {
            srvSite.openGallery({
                matterTypes: [{
                    value: 'enroll',
                    title: '登记活动',
                    url: '/rest/pl/fe/matter'
                }],
                singleMatter: true,
                mission: _oProto.mission,
                onlySameMission: true
            }).then(function(result) {
                var oEnrollApp, oChosen;
                if (result.matters.length === 1) {
                    oChosen = result.matters[0];
                    oEnrollApp = { id: oChosen.id, title: oChosen.title };
                    _oProto.enrollApp = oEnrollApp;
                }
            });
        };
        $scope.$on('doCreate.shop', function() {
            var url, data;
            var oConfig;
            url = '/rest/pl/fe/matter/enroll/create?site=' + siteId;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            oConfig = {};
            data = $scope.result;
            url += '&scenario=' + data.scenario.name;
            url += '&template=' + data.template.name;
            if (data.simpleSchema && data.simpleSchema.length) {
                oConfig.simpleSchema = data.simpleSchema;
            }
            if (data.proto) {
                oConfig.proto = data.proto;
            }
            if (data.scenarioConfig) {
                oConfig.scenarioConfig = data.scenarioConfig;
            }
            http2.post(url, oConfig, function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll/schema?site=' + siteId + '&id=' + rsp.data.id;
            });
        });
        $scope.chooseScenario = function() {
            var oTemplates, keys;

            oTemplates = $scope.result.scenario.templates;
            keys = Object.keys(oTemplates);
            $scope.result.template = oTemplates[keys[0]];
            $scope.chooseTemplate();
        };
        $scope.chooseTemplate = function() {
            if (!$scope.result.template) return;
            var url;
            url = '/rest/pl/fe/matter/enroll/template/config';
            url += '?scenario=' + $scope.result.scenario.name;
            url += '&template=' + $scope.result.template.name;
            http2.get(url, function(rsp) {
                var elSimulator, url;
                $scope.pages = rsp.data.pages;
                $scope.result.selectedPage = $scope.pages[0];
                elSimulator = document.querySelector('#simulator');
                url = 'http://' + location.host;
                url += '/rest/site/fe/matter/enroll/template';
                url += '?scenario=' + $scope.result.scenario.name;
                url += '&template=' + $scope.result.template.name;
                url += '&page=' + $scope.result.selectedPage.name;
                url += '&_=' + (new Date() * 1);
                elSimulator.src = url;
            });
        };
        $scope.choosePage = function() {
            var elSimulator, page, config;
            elSimulator = document.querySelector('#simulator');
            config = {};
            page = $scope.result.selectedPage.name;
            if (elSimulator.contentWindow.renew) {
                elSimulator.contentWindow.renew(page, config);
            }
        };
        /*系统模版*/
        http2.get('/rest/pl/fe/matter/enroll/template/list', function(rsp) {
            var oScenarioes = rsp.data,
                oTemplates;

            $scope.templates2 = oScenarioes;
            if (assignedScenario) {
                if (oScenarioes[assignedScenario]) {
                    $scope.result.scenario = oScenarioes[assignedScenario];
                    $scope.fixedScenario = true;
                    oTemplates = $scope.result.scenario.templates;
                    $scope.result.template = oTemplates[Object.keys(oTemplates)];
                    $scope.chooseTemplate();
                }
            } else {
                $scope.result.scenario = oScenarioes.common;
                $scope.chooseScenario();
            }
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsCount = Object.keys(oSns).length;
            if (!missionId) {
                if ($scope.snsCount) {
                    _oEntryRule.scope = 'sns';
                } else {
                    _oEntryRule.scope = 'none';
                }
            }
        });
        if (missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId, function(rsp) {
                var oMission;
                oMission = rsp.data;
                _oProto.mission = { id: oMission.id, title: oMission.title };
                _oEntryRule.scope = oMission.entry_rule.scope || 'none';
                if ('member' === oMission.entry_rule.scope) {
                    srvSite.memberSchemaList(oMission).then(function(aMemberSchemas) {
                        var oMschemasById = {};
                        aMemberSchemas.forEach(function(mschema) {
                            oMschemasById[mschema.id] = mschema;
                        });
                        Object.keys(oMission.entry_rule.member).forEach(function(mschemaId) {
                            _oEntryRule.mschemas.push({ id: mschemaId, title: oMschemasById[mschemaId].title });
                        });
                    });
                } else if ('sns' === oMission.entry_rule.scope) {
                    $scope.result.proto.sns = oMission.entry_rule.sns;
                }
                if (assignedScenario === 'registration') {
                    _oProto.title = oMission.title + '-报名';
                } else if (assignedScenario === 'voting') {
                    _oProto.title = oMission.title + '-投票';
                } else if (assignedScenario === 'group_week_report') {
                    _oProto.title = oMission.title + '-周报';
                } else if (assignedScenario === 'score_sheet') {
                    _oProto.title = oMission.title + '-记分表';
                } else if (assignedScenario === 'quiz') {
                    _oProto.title = oMission.title + '-测验';
                } else if (assignedScenario === 'common' || assignedScenario === '') {
                    _oProto.title = oMission.title + '-登记';
                }
            });
        }
    }]);
    ngApp.controller('ctrlUserTemplate', ['$scope', 'http2', function($scope, http2) {
        $scope.criteria = {
            scope: 'P'
        };
        $scope.page = {
            size: 10,
            at: 1,
            total: 0
        };
        $scope.data = {
            choose: -1
        };
        $scope.$on('doCreate.shop', function() {
            var url, data;
            var data;
            data = $scope.templates[$scope.data.choose];
            url = '/rest/pl/fe/matter/enroll/createByOther?site=' + siteId + '&template=' + data.id;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/enroll/schema?site=' + siteId + '&id=' + rsp.data.id;
            });
        });
        $scope.searchTemplate = function() {
            var url = '/rest/pl/fe/template/site/list?matterType=enroll&scope=P' + '&site=' + siteId;

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.searchShare2Me = function() {
            var url = '/rest/pl/fe/template/platform/share2Me?matterType=enroll';

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.searchBySite = function() {
            var url = '/rest/pl/fe/template/site/list?site=' + siteId + '&matterType=enroll&scope=S';

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.searchTemplate();
    }]);
    ngApp.controller('ctrlFile', ['$scope', 'http2', function($scope, http2) {
        function _excelLoader() {
            if (Resumable) {
                var ele, r;
                ele = document.getElementById('btnCreateByExcel');
                r = new Resumable({
                    target: '/rest/pl/fe/matter/enroll/uploadExcel4Create?site=' + siteId,
                    testChunks: false,
                });
                r.assignBrowse(ele);
                r.on('fileAdded', function(file, event) {
                    r.upload();
                });
                r.on('complete', function() {
                    var f, lastModified, posted;
                    f = r.files.pop().file;
                    lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
                    posted = {
                        name: f.name,
                        size: f.size,
                        type: f.type,
                        lastModified: lastModified,
                        uniqueIdentifier: f.uniqueIdentifier,
                    };
                    http2.post('/rest/pl/fe/matter/enroll/createByExcel?site=' + siteId, posted, function(rsp) {
                        $mi.close({ source: 'file', app: rsp.data });
                    });
                });
            }
        }

        var oNewApp;

        $scope.$on('doCreate.shop', function() {
            if (oNewApp) {
                location.href = '/rest/pl/fe/matter/enroll?site=' + siteId + '&id=' + choice.app.id;
            }
        });
        window.chooseFile = function(file) {
            var fReader;
            fReader = new FileReader();
            fReader.onload = function(evt) {
                var template, url;
                template = evt.target.result;
                template = JSON.parse(template);
                url = '/rest/pl/fe/matter/enroll/createByConfig?site=' + siteId;
                if (missionId) {
                    url += '&mission=' + missionId;
                }
                http2.post(url, template, function(rsp) {
                    oNewApp = rsp.data;
                });
            };
            fReader.readAsText(file);
        };
        $timeout(function() {
            _excelLoader();
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});