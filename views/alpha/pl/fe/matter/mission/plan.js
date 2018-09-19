define(['require'], function(require) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
    ngApp.config(['$locationProvider', 'srvSiteProvider', function($locationProvider, srvSiteProvider) {
        $locationProvider.html5Mode(true);
        var ls, siteId;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        //
        srvSiteProvider.config(siteId);
    }]);
    ngApp.controller('ctrlMain', ['$scope', '$location', 'srvSite', 'mediagallery', 'http2', function($scope, $location, srvSite, mediagallery, http2) {
        function fnChosenMschema(result) {
            var chosen;
            if (result && (chosen = result.chosen)) {
                if (_oEntryRule.scope !== 'member') {
                    _oEntryRule.scope = 'member';
                }
                _oEntryRule.mschema = { id: chosen.id, title: chosen.title };
                _oBeforeProto = angular.copy(_oProto);
            }
        }

        var _oProto, _oEntryRule, _oBeforeProto;
        $scope.proto = _oProto = {
            entryRule: { scope: 'none' },
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
        $scope.changeUserScope = function() {
            if (_oEntryRule.scope === 'member') {
                srvSite.chooseMschema({ id: '_pending', title: _oProto.title }).then(fnChosenMschema, function(reason) {
                    _oEntryRule.scope = _oBeforeProto.entryRule.scope;
                });
            } else if (_oEntryRule.scope === 'sns') {
                if ($scope.snsNames.length === 1) {
                    if (_oEntryRule.sns === undefined) {
                        _oEntryRule.sns = {};
                    }
                    _oEntryRule.sns[Object.keys($scope.sns)[0]] = 'Y';
                    _oBeforeProto = angular.copy(_oProto);
                }
            }
        };
        $scope.chooseMschema = function() {
            srvSite.chooseMschema({ id: '_pending', title: _oProto.title }).then(fnChosenMschema);
        };
        $scope.doCreate = function() {
            http2.post('/rest/pl/fe/matter/mission/create?site=' + $scope.site.id, _oProto, function(rsp) {
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
        });
    }]);
    /***/
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
    /***/
    return ngApp;
});