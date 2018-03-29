'use strict';
require('./action.css');

var ngApp = require('./main.js');
ngApp.controller('ctrlAction', ['$scope', '$sce', 'tmsLocation', 'http2', function($scope, $sce, LS, http2) {
    var _oApp, _aLogs, _oPage;
    $scope.page = _oPage = { at: 1, size: 30 };
    $scope.search = function() {
        http2.get(LS.j('event/timeline', 'app')).then(function(rsp) {
            $scope.logs = _aLogs = rsp.data.logs;
        });
    };
    $scope.gotoRemark = function(oLog) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oLog.enroll_key;
        url += '&page=remark';
        location.href = url;
    };
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
        $scope.search();
    });
}]);