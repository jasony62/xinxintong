define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRemark', ['$scope', '$parse', '$filter', '$location', '$q', '$uibModal', 'http2', 'srvEnrollRecord', 'tmsSchema', 'tmsRowPicker', function($scope, $parse, $filter, $location, $q, $uibModal, http2, srvEnrollRecord, tmsSchema, tmsRowPicker) {
        function list(oPage) {
            var defer,
                url;

            defer = $q.defer();
            url = '/rest/pl/fe/matter/enroll/remark/byApp?site=' + $location.search().site + '&app=' + $location.search().id;
            http2.post(url, oCriteria, { page: oPage }).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;

        }

        var oAgreedLabel, _oApp, oPage, oCriteria;
        oAgreedLabel = { 'Y': '推荐', 'N': '屏蔽', 'A': '接受' };
        $scope.page = oPage = { size: 30 };
        $scope.criteria = oCriteria = {
            orderby: 'create_at',
            agreed: 'all'
        };
        // 选中的记录
        $scope.rows = new tmsRowPicker();
        $scope.$watch('rows.allSelected', function(checked) {
            $scope.rows.setAllSelected(checked, $scope.remarks);
        });
        $scope.doSearch = function(pageAt) {
            if (pageAt) {
                oPage.at = pageAt;
            }
            list(oPage).then(function(oResult) {
                var oAssocRecords, aRemarks;
                oAssocRecords = oResult.records;
                angular.forEach(oAssocRecords, function(oRecord) {
                    tmsSchema.forTable(oRecord, $scope.app._schemasById);
                });
                aRemarks = oResult.remarks;
                aRemarks.forEach(function(oRemark) {
                    var oRemarkTarget, oTargetSchema, targetContent, targetData;
                    oRemark._agreed = oAgreedLabel[oRemark.agreed] || '未表态';
                    if (oAssocRecords[oRemark.enroll_key]) {
                        oRemark._target = oRemarkTarget = {};
                        if (oRemark.data_id > 0) {
                            if (_oApp._schemasById[oRemark.schema_id]) {
                                oTargetSchema = _oApp._schemasById[oRemark.schema_id];
                                oRemarkTarget.title = oTargetSchema.title;
                                targetContent = tmsSchema.strRecData(oAssocRecords[oRemark.enroll_key]._data, [oTargetSchema], { fnDataFilter: function(dataId) { return dataId == oRemark.data_id; } });
                                oRemarkTarget.content = targetContent;
                            }
                        } else {
                            /* 对记录进行评论 */
                            targetContent = tmsSchema.strRecData(oAssocRecords[oRemark.enroll_key]._data, _oApp.dynaDataSchemas, { fnSchemaFilter: function(oSchema) { return oSchema.shareable === 'Y'; } });
                            oRemarkTarget.content = targetContent;
                        }
                        oRemarkTarget.nickname = oAssocRecords[oRemark.enroll_key].nickname;
                    }
                });
                $scope.remarks = aRemarks;
            });
        };
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/remarkFilter.html?_=1',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    http2.get('/rest/pl/fe/matter/enroll/user/enrollee?site=' + $location.search().site + '&app=' + $location.search().id).then(function(rsp) {
                        $scope2.enrollees = rsp.data.users;
                    });
                    http2.get('/rest/pl/fe/matter/enroll/user/remarker?site=' + $location.search().site + '&app=' + $location.search().id).then(function(rsp) {
                        $scope2.remarkers = rsp.data.users;
                    });
                    $scope2.criteria = oCriteria;
                    $scope2.ok = function() {
                        $mi.close($scope2.criteria);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                }],
                windowClass: 'auto-height',
                backdrop: 'static',
            }).result.then(function(oCriteria) {
                $scope.doSearch(1);
            });
        };
        $scope.chooseOrderby = function(orderby) {
            oCriteria.orderby = orderby;
            $scope.doSearch(1);
        };
        $scope.setAgreed = function(result) {
            var remarkIds = [];
            for (var i in $scope.rows.selected) {
                if ($scope.rows.selected[i]) {
                    remarkIds.push($scope.remarks[i].id);
                }
            }
            srvEnrollRecord.agreeRemark(remarkIds, result).then(function(rsp) {
                for (var i in $scope.rows.selected) {
                    if ($scope.rows.selected[i]) {
                        $scope.remarks[i].agreed = result;
                        $scope.remarks[i]._agreed = oAgreedLabel[result] || '未表态';
                    }
                }
            });
        };
        $scope.gotoRemark = function(oRemark) {
            var oSearch = $location.search();
            oSearch.ek = oRemark.enroll_key;
            oSearch.schema = oRemark.schema_id;
            oSearch.remark = oRemark.id;
            $location.path('/rest/pl/fe/matter/enroll/editor');
        };
        $scope.$watch('app', function(oApp) {
            if (oApp) {
                _oApp = oApp;
                $scope.doSearch(1);
            }
        });
    }]);
});