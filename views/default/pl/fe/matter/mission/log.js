define(['frame'], function (ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', function ($scope, http2) {
        var page;
        $scope.page = page = {
            size: 12,
            at: 1
        };
        $scope.list = function () {
            var oMission
            if (oMission = $scope.mission) {
                http2.get('/rest/pl/fe/matter/mission/log/list?mission=' + oMission.id + '&page=' + page.at + '&size=' + page.size).then(function (rsp) {
                    $scope.logs = rsp.data.logs;
                    page.total = rsp.data.total;
                });
            }
        }
        $scope.$watch('mission', function (oMission) {
            if (oMission) {
                $scope.list();
            }
        });
    }])
})