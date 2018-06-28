define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlNotice', ['$scope', 'srvTmplmsgNotice', 'srvMschemaNotice', 'tmsSchema', function($scope, srvTmplmsgNotice, srvMschemaNotice, tmsSchema) {
        var oBatchPage, aBatches;
        $scope.tmsTableWrapReady = 'N';
        $scope.oBatchPage = oBatchPage = {};
        $scope.batches = aBatches = [];
        $scope.detail = function(batch) {
            $scope.batchId = batch;
            srvMschemaNotice.detail(batch).then(function(result) {
                var records, noticeStatus;
                $scope.logs = result.logs;
                if (result.records && result.records.length) {
                    records = result.records;
                    if (records.length) {
                        records.forEach(function(record) {
                            if ($scope.choosedSchema.extAttrs.length) {
                                record._extattr = tmsSchema.member.getExtattrsUIValue($scope.choosedSchema.extAttrs, record);
                            }
                            if (noticeStatus = record.noticeStatus) {
                                record._noticeStatus = noticeStatus.split(':');
                                record._noticeStatus[0] = record._noticeStatus[0] === 'success' ? '成功' : '失败';
                            }
                        });
                    }
                    $scope.records = records;
                }
                $scope.activeBatch = batch;
            })
        };
        $scope.choose = 'N';
        $scope.fail = function(isCheck) {
            if(isCheck == 'Y') {
                $scope.records.forEach(function(item,index) {
                    if(!(item.noticeStatus.indexOf('failed') < 0)) {
                        $scope.records.splice(item,index);
                    }
                });
            }else {
                $scope.detail($scope.batchId);
            }
        }
        $scope.$watch('choosedSchema', function(mschema) {
            if(!mschema) return;
            srvTmplmsgNotice.init('schema:' + mschema.id, oBatchPage, aBatches);
            srvTmplmsgNotice.list();
            $scope.tmsTableWrapReady = 'Y';
        })
    }]);
});
