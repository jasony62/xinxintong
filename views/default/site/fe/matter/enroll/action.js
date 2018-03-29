'use strict';
require('./action.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlAction', ['$scope', '$sce', 'tmsLocation', 'http2', function($scope, $sce, LS, http2) {
    var _oApp, _aLogs, _oPage, _oFilter;
    $scope.page = _oPage = {
        at: 1,
        size: 30,
        j: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.filter = _oFilter = { scope: 'A' };
    $scope.search = function(pageAt) {
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
    $scope.gotoRemark = function(oLog) {
        var url;
        if (oLog.enroll_key) {
            url = LS.j('', 'site', 'app');
            url += '&ek=' + oLog.enroll_key;
            url += '&page=remark';
            location.href = url;
        }
    };
    $scope.$watch('filter', function(nv, ov) {
        if (nv && nv !== ov) {
            $scope.search(1);
        }
    }, true)
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
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
        $scope.search(1);
    });
}]);