define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRemark', ['$scope', '$location', '$q', '$uibModal', 'http2', function($scope, $location, $q, $uibModal, http2) {
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
        oCriteria = {};
        $scope.doSearch = function(pageAt) {
            if (pageAt) {
                oPage.at = pageAt;
            }
            list(oPage).then(function(result) {
                $scope.remarks = result.remarks;
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
        $scope.$watch('app', function(nv) {
            if (nv) {
                $scope.doSearch(1);
            }
        });
    }]);
});
