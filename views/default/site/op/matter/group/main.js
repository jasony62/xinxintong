'use strict';
define(['angular', 'xxt-page'], function(angular, uiPage) {
    var ngApp = angular.module('group', []);
    ngApp.config(['$controllerProvider', 'lsProvider', function($cp, lsProvider) {
        ngApp.provider = {
            controller: $cp.register
        };
        lsProvider.params(['site', 'app', 'rid']);
    }]);
    ngApp.provider('ls', function() {
        var _baseUrl = '/rest/site/op/matter/group',
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
    ngApp.filter("maskmobile", function() {
        return function(mobile) {
            if (mobile && mobile.length > 4) {
                var i, start = Math.round((mobile.length - 4) / 2);
                mobile = mobile.split('');
                for (i = 0; i < 4; i++)
                    mobile[start + i] = '*';
                return mobile.join('');
            } else
                return '****';
        }
    });
    ngApp.controller('ctrl', ['$scope', '$http', '$q', 'ls', function($scope, $http, $q, LS) {
        $scope.matched = function(candidate, target) {
            var k, v;
            if (!candidate) return false;
            if (Object.keys(target).length === 0) return true;
            for (k in target) {
                v = target[k];
                if (candidate.data[k] === v) return true;
            }
            return false;
        };
        /*清空结果*/
        $scope.empty = function(fromBegin) {
            $http.get(LS.j('empty', 'site', 'app')).success(function(rsp) {
                if (fromBegin && fromBegin === 'Y') {
                    var url, t;
                    t = (new Date()).getTime();
                    url = '/rest/site/op/matter/group?site=' + LS.p.site + '&app=' + LS.p.app + '&_=' + t;
                    location.href = url;
                } else {
                    location.reload();
                }
            });
        };
        /*在指定轮次中添加选中的用户*/
        $scope.submit = function(winners) {
            var deferred = $q.defer(),
                url = LS.j('done', 'site', 'app'),
                posted = [];
            angular.forEach(winners, function(winner) {
                posted.push({
                    uid: winner.userid,
                    nickname: winner.nickname,
                    ek: winner.enroll_key,
                    rid: winner.round_id,
                });
            });
            $http.post(url, posted).success(function(rsp) {
                deferred.resolve(rsp.data);
            });
            return deferred.promise;
        };
        $http.get(LS.j('pageGet', 'site', 'app')).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params;
            params = rsp.data;
            uiPage.loadCode(ngApp, params.page).then(function() {
                $scope.page = params.page;
            });
        });
    }]);
    /***/
    angular._lazyLoadModule('group');
    /***/
    return ngApp;
});