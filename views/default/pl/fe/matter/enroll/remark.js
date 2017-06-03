define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRemark', ['$scope', '$location', '$q', '$uibModal', 'http2', 'srvRecordConverter', function($scope, $location, $q, $uibModal, http2, srvRecordConverter) {
        function list(oPage) {
            var defer,
                url;

            defer = $q.defer();
            url = '/rest/pl/fe/matter/enroll/remark/byApp?site=' + $location.search().site + '&app=' + $location.search().id + '&' + oPage.j();
            http2.post(url, oCriteria, function(rsp) {
                defer.resolve(rsp.data);
            });
            return defer.promise;

        }

        var oPage, oCriteria;
        $scope.page = oPage = {
            at: 1,
            size: 30,
            j: function() {
                return 'page=' + this.at + '&size=' + this.size
            }
        };
        $scope.criteria = oCriteria = {
            orderby: 'create_at'
        };
        $scope.doSearch = function(pageAt) {
            if (pageAt) {
                oPage.at = pageAt;
            }
            list(oPage).then(function(result) {
                $scope.remarks = result.remarks;
                for (var ek in result.records) {
                    srvRecordConverter.forTable(result.records[ek], $scope.app._schemasById);
                }
                $scope.records = result.records;

                oPage.total = result.total;
            });
        };
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/remarkFilter.html?_=1',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    http2.get('/rest/pl/fe/matter/enroll/user/enrollee?site=' + $location.search().site + '&app=' + $location.search().id, function(rsp) {
                        $scope2.enrollees = rsp.data.users;
                    });
                    http2.get('/rest/pl/fe/matter/enroll/user/remarker?site=' + $location.search().site + '&app=' + $location.search().id, function(rsp) {
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
        $scope.gotoRemark = function(oRemark) {
            var oSearch = $location.search();
            oSearch.schema = oRemark.schema_id;
            oSearch.remark = oRemark.id;
            $location.path('/rest/pl/fe/matter/enroll/editor');
        };
        $scope.$watch('app', function(nv) {
            if (nv) {
                $scope.doSearch(1);
            }
        });
    }]);
});
