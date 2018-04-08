'use strict';
require('./action.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlAction', ['$scope', '$q', 'tmsLocation', 'http2', function($scope, $q, LS, http2) {
    function fnCloseNotice(oNotice) {
        var url, defer;
        defer = $q.defer();
        url = LS.j('notice/close', 'site', 'app');
        url += '&notice=' + oNotice.id;
        http2.get(url).then(function(rsp) {
            $scope.notices.splice($scope.notices.indexOf(oNotice), 1);
            defer.resolve();
        });
        return defer.promise;
    }

    var _oApp, _aLogs, _oPage, _oFilter;
    $scope.page = _oPage = {
        at: 1,
        size: 30,
        j: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.filter = _oFilter = { scope: 'N' };
    $scope.searchEvent = function(pageAt) {
        var url;
        pageAt && (_oPage.at = pageAt);
        url = LS.j('event/timeline', 'site', 'app');
        url += '&scope=' + _oFilter.scope || 'A';
        url += '&' + _oPage.j();
        http2.get(url).then(function(rsp) {
            $scope.logs = _aLogs = rsp.data.logs;
            _oPage.total = rsp.data.total;
        });
    };
    $scope.searchNotice = function(pageAt) {
        var url;
        pageAt && (_oPage.at = pageAt);
        url = LS.j('notice/list', 'site', 'app');
        url += '&' + _oPage.j();
        http2.get(url).then(function(rsp) {
            $scope.notices = rsp.data.notices;
            _oPage.total = rsp.data.total;
        });
    };
    $scope.closeNotice = function(oNotice, bGotoCowork) {
        fnCloseNotice(oNotice).then(function() {
            if (bGotoCowork) {
                $scope.gotoCowork(oNotice.enroll_key);
            }
        });
    };
    $scope.gotoCowork = function(ek) {
        var url;
        if (ek) {
            url = LS.j('', 'site', 'app');
            url += '&ek=' + ek;
            url += '&page=cowork';
            location.href = url;
        }
    };
    $scope.$watch('filter', function(nv, ov) {
        if (nv && nv !== ov) {
            if (/N/.test(nv.scope)) {
                $scope.searchNotice(1);
            } else {
                $scope.searchEvent(1);
            }
        }
    }, true)
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        /* 活动任务 */
        if (_oApp.actionRule) {
            /* 设置活动任务提示 */
            var tasks = [];
            http2.get(LS.j('event/task', 'site', 'app')).then(function(rsp) {
                if (rsp.data && rsp.data.length) {
                    rsp.data.forEach(function(oRule) {
                        if (!oRule._ok) {
                            tasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, gap: oRule._no ? oRule._no[0] : 0, coin: oRule.coin ? oRule.coin : 0 });
                        }
                    });
                }
            });
            $scope.tasks = tasks;
        }
        /*设置页面导航*/
        var oAppNavs = {};
        if (_oApp.can_repos === 'Y') {
            oAppNavs.repos = {};
        }
        if (_oApp.can_rank === 'Y') {
            oAppNavs.rank = {};
        }
        if (Object.keys(oAppNavs)) {
            $scope.appNavs = oAppNavs;
        }
        //$scope.searchEvent(1);
        $scope.searchNotice(1);
    });
}]);