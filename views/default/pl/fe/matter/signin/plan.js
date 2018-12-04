'use strict';
var ngApp, ls, siteId, missionId;

ls = location.search;
siteId = ls.match(/[\?&]site=([^&]*)/)[1];
missionId = ls.match(/[\?&]mission=([^&]*)/) ? ls.match(/[\?&]mission=([^&]*)/)[1] : '';
ngApp = angular.module('app', ['ngRoute', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter']);
ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
    $locationProvider.html5Mode(true);
    srvSiteProvider.config(siteId);
}]);
ngApp.controller('ctrlPlan', ['$scope', 'http2', 'srvSite', 'tkEntryRule', function($scope, http2, srvSite, tkEntryRule) {
    var _oProto, _oEntryRule;
    $scope.proto = _oProto = {
        entryRule: {
            siteid: siteId,
            scope: {},
            member: {},
        }
    };
    $scope.entryRule = _oEntryRule = _oProto.entryRule;
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        _oProto[data.state] = data.value;
    });
    http2.post('/rest/script/time', { html: { 'entryRule': '/views/default/pl/fe/_module/entryRule' } }).then(function(rsp) {
        $scope.frameTemplates = { html: { 'entryRule': '/views/default/pl/fe/_module/entryRule.html?_=' + rsp.data.html.entryRule.time } };
    });
    srvSite.get().then(function(oSite) {
        $scope.site = oSite;
    });
    srvSite.snsList().then(function(oSns) {
        $scope.tkEntryRule = new tkEntryRule(_oProto, oSns, true);
    });
    if (missionId) {
        http2.get('/rest/pl/fe/matter/mission/get?site=' + siteId + '&id=' + missionId).then(function(rsp) {
            var oMission;
            $scope.mission = oMission = rsp.data;
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
            _oProto.title = oMission.title + '-签到';
        });
    }
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
        http2.post(url, oConfig).then(function(rsp) {
            location.href = '/rest/pl/fe/matter/signin/schema?site=' + siteId + '&id=' + rsp.data.id;
        });
    };
}]);