define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlNotice', ['$scope', 'srvTmplmsgNotice', 'srvEnrollNotice', 'srvRecordConverter', function($scope, srvTmplmsgNotice, srvEnrollNotice, srvRecordConverter) {
        var oBatchPage, aBatches;
        $scope.tmsTableWrapReady = 'N';
        $scope.oBatchPage = oBatchPage = {};
        $scope.batches = aBatches = [];
        $scope.detail = function(batch) {
            $scope.batchId = batch;
            srvEnrollNotice.detail(batch).then(function(result) {
                var records, noticeStatus;
                $scope.logs = result.logs;
                if (result.records && result.records.length) {
                    records = result.records;
                    records.forEach(function(record) {
                        srvRecordConverter.forTable(record, $scope.app._unionSchemasById);
                        if (noticeStatus = record.noticeStatus) {
                            record._noticeStatus = noticeStatus.split(':');
                            record._noticeStatus[0] = record._noticeStatus[0] === 'success' ? '成功' : '失败';
                        }
                    });
                    $scope.records = records;
                }
                $scope.activeBatch = batch;
            })
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
        $scope.$watch('app', function(app) {
            var recordSchemas;
            if (!app) return;
            recordSchemas = [];
            app.data_schemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    recordSchemas.push(schema);
                }
            });
            srvTmplmsgNotice.init('enroll:' + app.id, oBatchPage, aBatches);
            srvTmplmsgNotice.list();
            $scope.tmsTableWrapReady = 'Y';
            $scope.recordSchemas = recordSchemas;
        });
    }]);
});
