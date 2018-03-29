'use strict';
var ngApp = require('./main.js');
ngApp.controller('ctrlAction', ['$scope', '$sce', 'tmsLocation', 'http2', function($scope, $sce, LS, http2) {
    var _oApp;
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
    });
}]);