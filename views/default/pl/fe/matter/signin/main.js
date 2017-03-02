define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', 'srvSigninApp', function($scope, http2, srvSigninApp) {
        $scope.assignMission = function() {
            srvSigninApp.assignMission();
        };
        $scope.quitMission = function() {
            srvSigninApp.quitMission();
        };
        $scope.choosePhase = function() {
            srvSigninApp.choosePhase();
        };
        $scope.remove = function() {
            if (window.confirm('确定删除？')) {
                http2.get('/rest/pl/fe/matter/signin/remove?site=' + $scope.app.siteid + '&app=' + $scope.app.id, function(rsp) {
                    if ($scope.app.mission) {
                        location = "/rest/pl/fe/matter/mission?site=" + $scope.app.siteid + "&id=" + $scope.app.mission.id;
                    } else {
                        location = '/rest/pl/fe/site/console?site=' + $scope.app.siteid;
                    }
                });
            }
        };
    }]);
});
