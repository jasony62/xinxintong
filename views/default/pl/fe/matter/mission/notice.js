define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlNotice', ['$scope', 'http2', 'srvTmplmsgNotice', function($scope, http2, srvTmplmsgNotice) {
        var oBatchPage, aBatches;
        $scope.tmsTableWrapReady = 'N';
        $scope.oBatchPage = oBatchPage = {};
        $scope.batches = aBatches = [];
        $scope.detail = function(batch) {
            var url;
            $scope.batchId = batch;
            url = '/rest/pl/fe/matter/mission/notice/logList?batch=' + batch.id;
            http2.get(url, function(rsp) {
                var noticeStatus, result;
                result = rsp.data;
                if (result.logs && result.logs.length) {
                    result.logs.forEach(function(oLog) {
                        if (noticeStatus = oLog.status) {
                            oLog._noticeStatus = noticeStatus.split(':');
                            oLog._noticeStatus[0] = oLog._noticeStatus[0] === 'success' ? '成功' : '失败';
                        }
                    });
                }
                $scope.logs = result.logs;
                $scope.activeBatch = batch;
            });
        };
        $scope.choose = 'N';
        $scope.fail = function(isCheck) {
            if (isCheck == 'Y') {
                $scope.logs.forEach(function(item, index) {
                    if (!(item.noticeStatus.indexOf('failed') < 0)) {
                        $scope.logs.splice(item, index);
                    }
                });
            } else {
                $scope.detail($scope.batchId);
            }
        }
        $scope.$watch('mission', function(mission) {
            if (!mission) return;
            srvTmplmsgNotice.init('mission:' + mission.id, oBatchPage, aBatches);
            srvTmplmsgNotice.list();
            $scope.tmsTableWrapReady = 'Y';
        });
    }]);
});