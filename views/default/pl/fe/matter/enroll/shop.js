'use strict';
var ngApp, ls, _siteId, _missionId;

ls = location.search;
_siteId = ls.match(/[\?&]site=([^&]*)/)[1];
_missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';

ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'pl.const', 'service.matter']);
ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
    $locationProvider.html5Mode(true);
    srvSiteProvider.config(_siteId);
}]);
ngApp.controller('ctrlMain', ['$scope', 'http2', 'srvSite', function($scope, http2, srvSite) {
    $scope.source = 'platform';
    $scope.switchSource = function(source) {
        $scope.source = source;
    };
    http2.post('/rest/script/time', { html: { 'entryRule': '/views/default/pl/fe/_module/entryRule' } }).then(function(rsp) {
        $scope.frameTemplates = { html: { 'entryRule': '/views/default/pl/fe/_module/entryRule.html?_=' + rsp.data.html.entryRule.time } };
    });
    srvSite.get().then(function(oSite) {
        $scope.site = oSite;
        if (_missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + _siteId + '&id=' + _missionId).then(function(rsp) {
                $scope.mission = rsp.data;
            });
        }
    });
}]);
ngApp.controller('ctrlSysTemplate', ['$scope', '$location', '$uibModal', 'http2', 'CstNaming', 'srvSite', 'tkEntryRule', function($scope, $location, $uibModal, http2, CstNaming, srvSite, tkEntryRule) {
    var assignedScenario, _oProto, _oEntryRule, _oResult;

    assignedScenario = $location.search().scenario;
    $scope.result = _oResult = {
        proto: {
            id: '_pending',
            siteid: _siteId,
            entryRule: {
                scope: {},
                member: {},
            }
        }
    };
    $scope.proto = _oProto = $scope.result.proto;
    _oEntryRule = _oProto.entryRule;
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        _oProto[data.state] = data.value;
    });
    $scope.doCreate = function() {
        var url, data;
        var oConfig;
        url = '/rest/pl/fe/matter/enroll/create/bySysTemplate?site=' + _siteId;
        if (_missionId) {
            url += '&mission=' + _missionId;
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
        http2.post(url, oConfig).then(function(rsp) {
            location.href = '/rest/pl/fe/matter/enroll/schema?site=' + _siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.doCreateUserScore = function() {
        var url;
        url = '/rest/pl/fe/matter/enroll/createByMissionUser?mission=' + _missionId;
        http2.get(url).then(function(rsp) {
            location.href = '/rest/pl/fe/matter/enroll/schema?site=' + _siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.chooseScenario = function() {
        var oTemplates, keys;

        oTemplates = _oResult.scenario.templates;
        keys = Object.keys(oTemplates);
        _oResult.template = oTemplates[keys[0]];
        $scope.chooseTemplate();
    };
    $scope.chooseTemplate = function() {
        if (!_oResult.template) return;
        var url;
        url = '/rest/pl/fe/matter/enroll/template/config';
        url += '?scenario=' + _oResult.scenario.name;
        url += '&template=' + _oResult.template.name;
        http2.get(url).then(function(rsp) {
            var oScenarioConfig, elSimulator, url;
            $scope.scenarioConfig = oScenarioConfig = rsp.data.scenarioConfig;
            oScenarioConfig.required = (oScenarioConfig.can_repos !== 'D' || oScenarioConfig.can_rank !== 'D' || oScenarioConfig.can_repos !== 'D');
            _oProto.can_repos = oScenarioConfig.can_repos === 'Y' ? 'Y' : 'N';
            _oProto.can_rank = oScenarioConfig.can_rank === 'Y' ? 'Y' : 'N';
            _oProto.can_rounds = oScenarioConfig.can_rounds === 'Y' ? 'Y' : 'N';
            $scope.pages = rsp.data.pages;
            _oResult.selectedPage = $scope.pages[0];
            elSimulator = document.querySelector('#simulator iframe');
            url = '/rest/site/fe/matter/enroll/template';
            url += '?scenario=' + _oResult.scenario.name;
            url += '&template=' + _oResult.template.name;
            url += '&page=' + _oResult.selectedPage.name;
            url += '&_=' + (new Date * 1);
            elSimulator.src = url;
        });
    };
    $scope.choosePage = function(oPage) {
        var elSimulator;
        if (oPage) {
            _oResult.selectedPage = oPage;
        } else {
            oPage = _oResult.selectedPage;
        }
        elSimulator = document.querySelector('#simulator iframe');
        if (elSimulator.contentWindow.renew) {
            elSimulator.contentWindow.renew(oPage.name, {});
        }
    };
    /*系统模版*/
    http2.get('/rest/pl/fe/matter/enroll/template/list').then(function(rsp) {
        var oScenarioes = rsp.data,
            oTemplates;

        $scope.templates2 = oScenarioes;
        if (assignedScenario) {
            if (oScenarioes[assignedScenario]) {
                _oResult.scenario = oScenarioes[assignedScenario];
                $scope.fixedScenario = true;
                oTemplates = _oResult.scenario.templates;
                _oResult.template = oTemplates[Object.keys(oTemplates)[0]];
                $scope.chooseTemplate();
            }
        } else {
            _oResult.scenario = oScenarioes.common;
            $scope.chooseScenario();
        }
    });
    srvSite.snsList().then(function(oSns) {
        $scope.tkEntryRule = new tkEntryRule(_oProto, oSns, true);
    });
    if (_missionId) {
        $scope.$watch('mission', function(oMission) {
            if (oMission) {
                _oProto.mission = { id: oMission.id, title: oMission.title };
                _oEntryRule.scope = {};
                if (oMission.entryRule.scope) {
                    if (oMission.entryRule.scope.member === 'Y') {
                        _oEntryRule.scope.member = 'Y';
                        srvSite.memberSchemaList(oMission).then(function(aMemberSchemas) {
                            var oMschemasById = {};
                            aMemberSchemas.forEach(function(mschema) {
                                oMschemasById[mschema.id] = mschema;
                            });
                            Object.keys(oMission.entryRule.member).forEach(function(mschemaId) {
                                _oEntryRule.member[mschemaId] = { title: oMschemasById[mschemaId].title };
                            });
                        });
                    }
                    if (oMission.entryRule.scope.sns === 'Y') {
                        _oEntryRule.scope.sns = 'Y';
                        _oResult.proto.sns = oMission.entryRule.sns;
                    }
                }
                _oProto.title = oMission.title + '-记录活动';
            }
        });
    } else if (_siteId) {
        $scope.$watch('site', function(oSite) {
            if (oSite) {
                _oProto.title = oSite.name + '-记录活动';
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
    $scope.doCreate = function() {
        var url, data;
        var data;
        data = $scope.templates[$scope.data.choose];
        url = '/rest/pl/fe/matter/enroll/createByOther?site=' + _siteId + '&template=' + data.id;
        if (_missionId) {
            url += '&mission=' + _missionId;
        }
        http2.get(url).then(function(rsp) {
            location.href = '/rest/pl/fe/matter/enroll/schema?site=' + _siteId + '&id=' + rsp.data.id;
        });
    };
    $scope.searchTemplate = function() {
        var url = '/rest/pl/fe/template/site/list?matterType=enroll&scope=P' + '&site=' + _siteId;

        http2.get(url).then(function(rsp) {
            $scope.templates = rsp.data.templates;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.searchShare2Me = function() {
        var url = '/rest/pl/fe/template/platform/share2Me?matterType=enroll';

        http2.get(url).then(function(rsp) {
            $scope.templates = rsp.data.templates;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.searchBySite = function() {
        var url = '/rest/pl/fe/template/site/list?site=' + _siteId + '&matterType=enroll&scope=S';

        http2.get(url).then(function(rsp) {
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
                target: '/rest/pl/fe/matter/enroll/uploadExcel4Create?site=' + _siteId,
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
                http2.post('/rest/pl/fe/matter/enroll/createByExcel?site=' + _siteId, posted).then(function(rsp) {
                    $mi.close({ source: 'file', app: rsp.data });
                });
            });
        }
    }

    var oNewApp;

    $scope.$on('doCreate.shop', function() {
        if (oNewApp) {
            location.href = '/rest/pl/fe/matter/enroll?site=' + _siteId + '&id=' + choice.app.id;
        }
    });
    window.chooseFile = function(file) {
        var fReader;
        fReader = new FileReader();
        fReader.onload = function(evt) {
            var template, url;
            template = evt.target.result;
            template = JSON.parse(template);
            url = '/rest/pl/fe/matter/enroll/createByConfig?site=' + _siteId;
            if (_missionId) {
                url += '&mission=' + _missionId;
            }
            http2.post(url, template).then(function(rsp) {
                oNewApp = rsp.data;
            });
        };
        fReader.readAsText(file);
    };
    $timeout(function() {
        _excelLoader();
    });
}]);