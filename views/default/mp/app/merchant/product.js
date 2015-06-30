(function () {
    xxtApp.register.controller('productCtrl', ['$scope', '$modal', '$q', 'http2', function ($scope, $modal, $q, http2) {
        var cascade = function (prod) {
            var defer = $q.defer();
            if (prod.catelog === undefined) {
                var url = '/rest/mp/app/merchant/product/cascaded';
                url += '?id=' + prod.id;
                http2.get(url, function (rsp) {
                    prod.catelog = rsp.data.catelog;
                    prod.prop_value2 = rsp.data.propValue2;
                    prod.skus = rsp.data.skus;
                    defer.resolve(prod);
                });
            } else
                defer.resolve(prod);
            return defer.promise;
        };
        $scope.$parent.subView = 'product';
        $scope.get = function () {
            http2.get('/rest/mp/app/merchant/product/get?shopId=' + $scope.shopId, function (rsp) {
                $scope.products = rsp.data;
            });
        };
        $scope.open = function (product) {
            cascade(product).then(function (prod) {
                $scope.editing = prod;
            });
        };
        $scope.create = function () {
            $modal.open({
                templateUrl: 'catelogSelector.html',
                backdrop: 'static',
                controller: ['$modalInstance', '$scope', function ($modalInstance, $scope2) {
                    $scope2.data = {};
                    $scope2.close = function () {
                        $modalInstance.dismiss();
                    };
                    $scope2.ok = function () {
                        $modalInstance.close($scope2.data.selected);
                    };
                    http2.get('/rest/mp/app/merchant/catelog/get?shopId=' + $scope.shopId, function (rsp) {
                        $scope2.catelogs = rsp.data;
                    });
                }]
            }).result.then(function (catelog) {
                if (catelog !== null) {
                    var url = '/rest/mp/app/merchant/product/create';
                    url += '?cateId=' + catelog.id;
                    http2.get(url, function (rsp) {
                        $scope.products.push(rsp.data);
                    });
                }
            });
        };
        $scope.remove = function () {

        };
        $scope.update = function (name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/mp/app/merchant/product/update?id=' + $scope.editing.id, nv, function (rsp) {
            });
        };
        $scope.setPropValue = function (prop) {
            $modal.open({
                templateUrl: 'propValueSetter.html',
                backdrop: 'static',
                controller: ['$modalInstance', '$scope', function ($modalInstance, $scope2) {
                    $scope2.prop = prop;
                    if ($scope.editing.prop_value2[prop.id]) {
                        $scope2.data = angular.copy($scope.editing.prop_value2[prop.id]);
                    } else {
                        $scope2.data = {};
                    }
                    $scope2.close = function () {
                        $modalInstance.dismiss();
                    };
                    $scope2.ok = function () {
                        $modalInstance.close($scope2.data);
                    };
                }]
            }).result.then(function (data) {
                var url;
                url = '/rest/mp/app/merchant/product/propUpdate?id=' + $scope.editing.id;
                data.prop_id = prop.id;
                http2.post(url, data, function (rsp) {
                    $scope.editing.prop_value2[prop.id] = rsp.data;
                });
            });
        };
        $scope.addSKU = function () {
            http2.get('/rest/mp/app/merchant/product/skuCreate?id=' + $scope.editing.id, function (rsp) {
                $scope.editing.skus.push(rsp.data);
            });
        };
        $scope.updateSku = function (sku, prop) {
            var nv = {};
            nv[prop] = sku[prop];
            http2.post('/rest/mp/app/merchant/product/skuUpdate?id=' + sku.id, nv, function (rsp) {
            });
        };
        $scope.get();
    }]);
})();