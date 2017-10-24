define(['require'], function(require) {
    'use strict';
    var ngApp, ls, siteId, missionId;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';

    ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        srvSiteProvider.config(siteId);
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$location', '$timeout', 'http2', 'srvSite', function($scope, $location, $timeout, http2, srvSite) {
        $scope.source = 'platform';
        $scope.switchSource = function(source) {
            $scope.source = source;
        };
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        if (missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId, function(rsp) {
                $scope.mission = rsp.data;
            });
        }
    }]);
    ngApp.controller('ctrlSysTemplate', ['$scope', '$location', '$uibModal', 'http2', 'srvSite', function($scope, $location, $uibModal, http2, srvSite) {
        var assignedScenario;

        assignedScenario = $location.search().scenario;
        $scope.result = {
            proto: {}
        };
        $scope.proto = $scope.result.proto;
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            $scope.proto[data.state] = data.value;
        });
        $scope.doCreate = function() {
            var url, data;
            var oConfig;
            url = '/rest/pl/fe/matter/wall/create?site=' + siteId;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            oConfig = {};
            data = $scope.result;
            console.log($scope.result.proto);
            url += '&scenario=' + data.scenario.name;
            url += '&template=' + data.template.name;
            if (data.proto) {
                oConfig.proto = data.proto;
            }
            http2.post(url, oConfig, function(rsp) {
                location.href = '/rest/pl/fe/matter/wall?site=' + siteId + '&id=' + rsp.data.id;
            });
        };
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
            url = '/rest/pl/fe/matter/wall/template/config';
            url += '?scenario=' + $scope.result.scenario.name;
            url += '&template=' + $scope.result.template.name;
            http2.get(url, function(rsp) {
                $scope.result.proto.scenario_config = rsp.data;
            });
        };
        /*系统模版*/
        http2.get('/rest/pl/fe/matter/wall/template/list', function(rsp) {
            var oScenarioes = rsp.data,
                oTemplates;

            $scope.templates2 = oScenarioes;
            if (assignedScenario) {
                if (oScenarioes[assignedScenario]) {
                    $scope.result.scenario = oScenarioes[assignedScenario];
                    $scope.fixedScenario = true;
                    oTemplates = $scope.result.scenario.templates;
                    $scope.result.template = oTemplates[Object.keys(oTemplates)[0]];
                    $scope.chooseTemplate();
                }
            } else {
                $scope.result.scenario = oScenarioes.discuss;
                $scope.chooseScenario();
            }
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
        });
        $scope.$watch('mission', function(oMission) {
            if (oMission) {
                $scope.proto.mission = { id: oMission.id, title: oMission.title };
                if (assignedScenario === 'interact') {
                    $scope.proto.title = oMission.title + '-互动';
                } else {
                    $scope.proto.title = oMission.title + '-讨论';
                }
            }
        });
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
            url = '/rest/pl/fe/matter/wall/createByOther?site=' + siteId + '&template=' + data.id;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            http2.get(url, function(rsp) {
                location.href = '/rest/pl/fe/matter/wall/schema?site=' + siteId + '&id=' + rsp.data.id;
            });
        };
        $scope.searchTemplate = function() {
            var url = '/rest/pl/fe/template/site/list?matterType=wall&scope=P' + '&site=' + siteId;

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.searchShare2Me = function() {
            var url = '/rest/pl/fe/template/platform/share2Me?matterType=wall';

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.searchBySite = function() {
            var url = '/rest/pl/fe/template/site/list?site=' + siteId + '&matterType=wall&scope=S';

            http2.get(url, function(rsp) {
                $scope.templates = rsp.data.templates;
                $scope.page.total = rsp.data.total;
            });
        };
        $scope.searchTemplate();
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});