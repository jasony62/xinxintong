'use strict';
var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'http.ui.xxt', 'notice.ui.xxt', 'service.matter']);
ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
    $locationProvider.html5Mode(true);
    var ls, siteId;
    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    //
    srvSiteProvider.config(siteId);
}]);
ngApp.controller('ctrlMain', ['$scope', '$location', 'srvSite', 'mediagallery', 'http2', 'tkEntryRule', function($scope, $location, srvSite, mediagallery, http2, tkEntryRule) {
    var _oProto, _oBeforeProto;
    $scope.proto = _oProto = {
        id: '_pending',
        siteid: $location.search().site,
        entryRule: { scope: {} },
        app: { enroll: {}, signin: {}, group: { source: '' } },
        userApp: ''
    };
    _oBeforeProto = angular.copy(_oProto);
    $scope.entryRule = _oProto.entryRule;
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                _oProto.pic = url + '?_=' + (new Date * 1);
            }
        };
        mediagallery.open($scope.site.id, options);
    };
    $scope.removePic = function() {
        _oProto.pic = '';
    };
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        var prop;
        if (data.state.indexOf('proto.') === 0) {
            prop = data.state.substr(8);
            _oProto[prop] = data.value;
        }
    });
    $scope.doCreate = function() {
        http2.post('/rest/pl/fe/matter/mission/create?site=' + $scope.site.id, _oProto).then(function(rsp) {
            location.href = '/rest/pl/fe/matter/mission?site=' + $scope.site.id + '&id=' + rsp.data.id;
        });
    };
    srvSite.get().then(function(oSite) {
        $scope.site = oSite;
        _oProto.pic = oSite.heading_pic;
    });
    srvSite.getLoginUser().then(function(oUser) {
        $scope.loginUser = oUser;
        _oProto.title = oUser.nickname + '的项目';
    });
    http2.post('/rest/script/time', { html: { 'entryRule': '/views/default/pl/fe/_module/entryRule' } }).then(function(rsp) {
        $scope.frameTemplates = { html: { 'entryRule': '/views/default/pl/fe/_module/entryRule.html?_=' + rsp.data.html.entryRule.time } };
    });
    srvSite.snsList().then(function(oSns) {
        $scope.tkEntryRule = new tkEntryRule(_oProto, oSns, true, ['group', 'enroll']);
    });
}]);