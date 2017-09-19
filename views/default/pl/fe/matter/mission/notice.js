define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlNotice', ['$scope', 'http2', 'srvTmplmsgNotice', 'srvRecordConverter', function($scope, http2, srvTmplmsgNotice, srvRecordConverter) {
        var oBatchPage, aBatches;
        $scope.tmsTableWrapReady = 'N';
        $scope.oBatchPage = oBatchPage = {};
        $scope.batches = aBatches = [];
        $scope.detail = function(batch) {
            var url;
            $scope.batchId = batch;
            url = '/rest/pl/fe/matter/mission/notice/logList?batch=' + batch.id;
            http2.get(url, function(rsp) {
                var records, noticeStatus, result;
                result = rsp.data;
                $scope.logs = result.logs;
                if (result.records && result.records.length) {
                    records = result.records;
                    records.forEach(function(record) {
                        if (noticeStatus = record.noticeStatus) {
                            record._noticeStatus = noticeStatus.split(':');
                            record._noticeStatus[0] = record._noticeStatus[0] === 'success' ? '成功' : '失败';
                        }
                    });
                    $scope.records = records;
                }
                $scope.activeBatch = batch;
            });
        };
        $scope.choose = 'N';
        $scope.fail = function(isCheck) {
            if (isCheck == 'Y') {
                $scope.records.forEach(function(item, index) {
                    if (!(item.noticeStatus.indexOf('failed') < 0)) {
                        $scope.records.splice(item, index);
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
