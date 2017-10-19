define(['require'], function(require) {
    'use strict';
    var ngApp, ls, siteId, missionId;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';
    ngApp = angular.module('app', ['ngRoute', 'service.matter']);
    ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        srvSiteProvider.config(siteId);
    }]);
    ngApp.controller('ctrlPlan', ['$scope', 'http2', 'srvSite', function($scope, http2, srvSite) {
        var _oProto, _oEntryRule;
        $scope.proto = _oProto = {
            entryRule: {
                scope: '',
                mschemas: [],
            }
        };
        $scope.entryRule = _oEntryRule = _oProto.entryRule;
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            _oProto[data.state] = data.value;
        });
        srvSite.get().then(function(oSite) {
            $scope.site = oSite;
        });
        srvSite.snsList().then(function(oSns) {
            $scope.sns = oSns;
            $scope.snsNames = Object.keys(oSns);
        });
        if (missionId) {
            http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId, function(rsp) {
                var oMission;
                $scope.mission = oMission = rsp.data;
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
                    $scope.proto.sns = oMission.entry_rule.sns;
                }
                _oProto.title = oMission.title + '-签到';
            });
        }
        $scope.chooseMschema = function() {
            srvSite.chooseMschema({ id: '_pending', title: _oProto.title }).then(function(result) {
                var oChosen = result.chosen;
                _oEntryRule.mschemas.push({ id: oChosen.id, title: oChosen.title });
            });
        };
        $scope.removeMschema = function(oMschema) {
            var mschemas = _oEntryRule.mschemas;
            mschemas.splice(mschemas.indexOf(oMschema), 1);
        };
        $scope.changeUserScope = function() {
            switch (_oEntryRule.scope) {
                case 'member':
                    if (!_oEntryRule.mschemas || _oEntryRule.mschemas.length === 0) {
                        $scope.chooseMschema();
                    }
                    break;
                case 'sns':
                    _oEntryRule.sns = {};
                    $scope.snsNames.forEach(function(snsName) {
                        _oEntryRule.sns[snsName] = true;
                    });
                    break;
            }
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
        $scope.doCreate = function() {
            var url, data;
            var oConfig;
            url = '/rest/pl/fe/matter/signin/create?site=' + siteId;
            if (missionId) {
                url += '&mission=' + missionId;
            }
            oConfig = {
                proto: $scope.proto
            };
            http2.post(url, oConfig, function(rsp) {
                location.href = '/rest/pl/fe/matter/signin/schema?site=' + siteId + '&id=' + rsp.data.id;
            });
        };
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});