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
ngApp.controller('ctrlMain', ['$scope', 'srvSite', 'mediagallery', 'http2', function($scope, srvSite, mediagallery, http2) {
    function fnChosenMschema(result) {
        var chosen;
        if (result && (chosen = result.chosen)) {
            if (!_oEntryRule.scope.member) {
                _oEntryRule.scope.member = true;
            }
            _oEntryRule.mschema = { id: chosen.id, title: chosen.title };
            _oBeforeProto = angular.copy(_oProto);
        }
    }

    function fnSetDefaultScopeSns() {
        if ($scope.snsNames.length) {
            if (_oEntryRule.sns === undefined) {
                _oEntryRule.sns = {};
            }
            _oEntryRule.sns[$scope.snsNames[0]] = 'Y';
            _oBeforeProto = angular.copy(_oProto);
        }
    }

    var _oProto, _oEntryRule, _oBeforeProto;
    $scope.proto = _oProto = {
        entryRule: { scope: {} },
        app: { enroll: {}, signin: {}, group: { source: '' } },
        userApp: ''
    };
    _oBeforeProto = angular.copy(_oProto);
    $scope.entryRule = _oEntryRule = _oProto.entryRule;
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
    $scope.changeUserApp = function() {
        switch (_oProto.userApp) {
            case 'mschema':
                if (!_oEntryRule.mschema) {
                    srvSite.chooseMschema({ id: '_pending', title: _oProto.title }).then(fnChosenMschema, function(reason) {
                        _oProto.userApp = _oBeforeProto.userApp;
                    });
                }
                break;
            case 'enroll':
                _oProto.app.enroll.create = 'Y';
                break;
            case 'signin':
                _oProto.app.signin.create = 'Y';
                break;
            case 'group':
                _oProto.app.group.create = 'Y';
                break;
        }
    };
    $scope.changeUserScope = function(userScope) {
        switch (userScope) {
            case 'member':
                if (_oEntryRule.scope.member) {
                    srvSite.chooseMschema({ id: '_pending', title: _oProto.title }).then(fnChosenMschema, function(reason) {
                        _oEntryRule.scope.member = false;
                    });
                }
                break;
            case 'sns':
                if (_oEntryRule.scope.sns) {
                    fnSetDefaultScopeSns();
                }
                break;
        }
    };
    $scope.chooseMschema = function() {
        srvSite.chooseMschema({ id: '_pending', title: _oProto.title }).then(fnChosenMschema);
    };
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
    srvSite.snsList().then(function(oSns) {
        $scope.sns = oSns;
        $scope.snsNames = Object.keys(oSns);
        if ($scope.snsNames.length) {
            _oEntryRule.scope.sns = true;
            fnSetDefaultScopeSns();
        }
    });
}]);
angular.bootstrap(document, ["app"]);