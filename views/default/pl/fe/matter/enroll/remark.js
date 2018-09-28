define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRemark', ['$scope', '$location', '$q', '$uibModal', 'http2', 'srvEnrollRecord', 'tmsSchema', function($scope, $location, $q, $uibModal, http2, srvEnrollRecord, tmsSchema) {
        function list(oPage) {
            var defer,
                url;

            defer = $q.defer();
            url = '/rest/pl/fe/matter/enroll/remark/byApp?site=' + $location.search().site + '&app=' + $location.search().id + '&' + oPage.j();
            http2.post(url, oCriteria).then(function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;

        }

        var oAgreedLabel, oPage, oCriteria;
        oAgreedLabel = { 'Y': '推荐', 'N': '屏蔽', 'A': '接受' };
        $scope.page = oPage = {
            at: 1,
            size: 30,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size
            }
        };
        $scope.criteria = oCriteria = {
            orderby: 'create_at',
            agreed: 'all'
        };
        // 选中的记录
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
            }
        };
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.remarks.length) {
                    $scope.rows.selected[index++] = true;
                }
            } else if (checked === 'N') {
                $scope.rows.selected = {};
            }
        });
        $scope.doSearch = function(pageAt) {
            if (pageAt) {
                oPage.at = pageAt;
            }
            list(oPage).then(function(result) {
                $scope.remarks = result.remarks;
                $scope.remarks.forEach(function(oRemark) {
                    oRemark._agreed = oAgreedLabel[oRemark.agreed] || '未表态';
                });
                for (var ek in result.records) {
                    tmsSchema.forTable(result.records[ek], $scope.app._schemasById);
                }
                $scope.records = result.records;

                oPage.total = result.total;
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
                $scope.doSearch(1);
            }
        });
    }]);
});