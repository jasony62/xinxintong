define(['angular', 'xxt-page'], function(angular, uiPage) {
    'use strict';
    var ngApp = angular.module('mission', []);
    ngApp.config(['$controllerProvider', 'lsProvider', function($cp, lsProvider) {
        ngApp.provider = {
            controller: $cp.register
        };
        lsProvider.params(['site', 'mission']);
    }]);
    ngApp.provider('ls', function() {
        var _baseUrl = '/rest/site/op/matter/mission',
            _params = {};

        this.params = function(params) {
            var ls;
            ls = location.search;
            angular.forEach(params, function(q) {
                var match, pattern;
                pattern = new RegExp(q + '=([^&]*)');
                match = ls.match(pattern);
                _params[q] = match ? match[1] : '';
            });
            return _params;
        };

        this.$get = function() {
            return {
                p: _params,
                j: function(method) {
                    var i = 1,
                        l = arguments.length,
                        url = _baseUrl,
                        _this = this,
                        search = [];
                    method && method.length && (url += '/' + method);
                    for (; i < l; i++) {
                        search.push(arguments[i] + '=' + _params[arguments[i]]);
                    };
                    search.length && (url += '?' + search.join('&'));
                    return url;
                }
            };
        };
    });
    ngApp.controller('ctrl', ['$scope', '$http', 'ls', function($scope, $http, LS) {
        $scope.openMatter = function(matter) {
            if (/article|custom|news|channel|link/.test(matter.type)) {
                location.href = '/rest/site/fe/matter?site=' + LS.p.site + '&id=' + matter.id + '&type=' + matter.type;
            } else if (/enroll|signin|group/.test(matter.type) && matter.op_short_url_code) {
                location.href = 'http://' + location.host + '/q/' + matter.op_short_url_code;
            }
        };
        $scope.openReport = function(matter) {
            if (/enroll/.test(matter.type) && matter.rp_short_url_code) {
                location.href = 'http://' + location.host + '/q/' + matter.rp_short_url_code;
            }
        };
        $http.get('/rest/site/fe/main/get?site=' + LS.p.site).success(function(rsp) {
            $scope.site = rsp.data;
        });
        $http.get(LS.j('get', 'site', 'mission')).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var page;
            $scope.mission = rsp.data.mission;
            page = rsp.data.page;
            uiPage.loadCode(ngApp, page).then(function() {
                $scope.page = page;
                window.loading.finish();
            });
        });
        $http.get(LS.j('matterList', 'site', 'mission')).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.matters = rsp.data;
        });
    }]);
    /***/
    angular._lazyLoadModule('mission');
    /***/
    return ngApp;
});
