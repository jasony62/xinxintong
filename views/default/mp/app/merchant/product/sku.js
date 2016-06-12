(function() {
    xxtApp.register.controller('skuCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$parent.subView = 'sku';
        $scope.addSku = function() {
            $uibModal.open({
                templateUrl: 'cateSkuSelector.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.cateSkus = $scope.cateSkus;
                    $scope2.data = {
                        selected: null
                    };
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function(data) {
                if (data && data.selected) {
                    http2.get('/rest/mp/app/merchant/product/skuCreate?product=' + $scope.productId + '&cateSku=' + data.selected.id, function(rsp) {
                        $scope.skus.push(rsp.data);
                    });
                }
            });
        };
        $scope.updateSku = function(sku, prop) {
            var nv = {};
            nv[prop] = sku[prop];
            http2.post('/rest/mp/app/merchant/product/skuUpdate?sku=' + sku.id, nv);
        };
        $scope.activateSku = function(sku) {
            http2.get('/rest/mp/app/merchant/product/skuActivate?sku=' + sku.id, function(rsp) {
                sku.active = 'Y';
            });
        };
        $scope.deactivateSku = function(sku) {
            http2.get('/rest/mp/app/merchant/product/skuDeactivate?sku=' + sku.id, function(rsp) {
                sku.active = 'N';
            });
        };
        $scope.removeSku = function(index, sku) {
            if (window.confirm("确定删除？")) {
                http2.get('/rest/mp/app/merchant/product/skuRemove?sku=' + sku.id, function(rsp) {
                    $scope.skus.splice(index, 1);
                });
            }
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            data.obj[data.state] = data.value;
            $scope.updateSku(data.obj, data.state);
        });
        http2.get('/rest/mp/app/merchant/product/skuList?product=' + $scope.productId, function(rsp) {
            var i, j, cateSkus, cateSku, sku, skus;
            cateSkus = rsp.data;
            skus = [];
            for (i in cateSkus) {
                cateSku = cateSkus[i];
                for (j in cateSku.skus) {
                    sku = cateSku.skus[j];
                    sku.cateSku = cateSku;
                    skus.push(sku);
                }
            }
            $scope.skus = skus;
        });
        $scope.$watch('editing', function(nv) {
            if (nv) {
                http2.get('/rest/mp/app/merchant/catelog/skuList?catelog=' + $scope.editing.cate_id, function(rsp) {
                    $scope.cateSkus = rsp.data;
                });
            }
        });
    }]);
})();