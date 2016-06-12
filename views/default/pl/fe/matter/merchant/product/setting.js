(function() {
    ngApp.provider.controller('ctrlSetting', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/pl/fe/matter/merchant/product/update?product=' + $scope.editing.id, nv, function(rsp) {});
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.main_img = url + '?_=' + (new Date()) * 1;
                    $scope.update('main_img');
                }
            };
            $scope.$broadcast('mediagallery.open', options);
        };
        $scope.removePic = function() {
            $scope.editing.main_img = '';
            $scope.update('main_img');
        };
        $scope.setPropValue = function(prop) {
            $uibModal.open({
                templateUrl: 'propValueSetter.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.prop = prop;
                    if ($scope.editing.propValue[prop.id]) {
                        $scope2.data = angular.copy($scope.editing.propValue[prop.id]);
                    } else {
                        $scope2.data = {};
                    }
                    $scope2.options = $scope.editing.catelog.propValues[prop.id];
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function(data) {
                var url;
                url = '/rest/pl/fe/matter/merchant/product/propUpdate?product=' + $scope.editing.id;
                data.prop_id = prop.id;
                http2.post(url, data, function(rsp) {
                    $scope.editing.propValue[prop.id] = rsp.data;
                });
            });
        };
        $scope.activate = function() {
            http2.get('/rest/pl/fe/matter/merchant/product/activate?product=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'Y';
            });
        };
        $scope.deactivate = function() {
            http2.get('/rest/pl/fe/matter/merchant/product/deactivate?product=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'N';
            });
        };
        $scope.remove = function() {
            http2.get('/rest/pl/fe/matter/merchant/product/remove?product=' + $scope.editing.id, function(rsp) {
                if ($scope.$parent.catelogId) {
                    location.href = '/rest/pl/fe/matter/merchant/catelog/product?shop=' + $scope.$parent.shopId + '&catelog=' + $scope.$parent.catelogId;
                } else {
                    location.href = '/rest/pl/fe/matter/merchant/shop/product?shop=' + $scope.$parent.shopId;
                }
            });
        };
    }]);
    ngApp.provider.controller('ctrlSku', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
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
                    http2.get('/rest/pl/fe/matter/merchant/product/skuCreate?product=' + $scope.productId + '&cateSku=' + data.selected.id, function(rsp) {
                        $scope.skus.push(rsp.data);
                    });
                }
            });
        };
        $scope.updateSku = function(sku, prop) {
            var nv = {};
            nv[prop] = sku[prop];
            http2.post('/rest/pl/fe/matter/merchant/product/skuUpdate?sku=' + sku.id, nv);
        };
        $scope.activateSku = function(sku) {
            http2.get('/rest/pl/fe/matter/merchant/product/skuActivate?sku=' + sku.id, function(rsp) {
                sku.active = 'Y';
            });
        };
        $scope.deactivateSku = function(sku) {
            http2.get('/rest/pl/fe/matter/merchant/product/skuDeactivate?sku=' + sku.id, function(rsp) {
                sku.active = 'N';
            });
        };
        $scope.removeSku = function(index, sku) {
            if (window.confirm("确定删除？")) {
                http2.get('/rest/pl/fe/matter/merchant/product/skuRemove?sku=' + sku.id, function(rsp) {
                    $scope.skus.splice(index, 1);
                });
            }
        };
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            data.obj[data.state] = data.value;
            $scope.updateSku(data.obj, data.state);
        });
        http2.get('/rest/pl/fe/matter/merchant/product/skuList?product=' + $scope.productId, function(rsp) {
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
                http2.get('/rest/pl/fe/matter/merchant/catelog/skuList?catelog=' + $scope.editing.cate_id, function(rsp) {
                    $scope.cateSkus = rsp.data;
                });
            }
        });
    }]);
})();