(function() {
    ngApp.provider.controller('ctrlSetting', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.open = function(catelog) {
            if (catelog.properties === undefined) {
                http2.get('/rest/pl/fe/matter/merchant/catelog/cascaded?id=' + catelog.id, function(rsp) {
                    catelog.properties = rsp.data.properties;
                    $scope.editing = catelog;
                });
            } else {
                $scope.editing = catelog;
            }
        };
        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/pl/fe/matter/merchant/shop/update?id=' + $scope.shopId, nv, function(rsp) {});
        };
        $scope.create = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/create?shopId=' + $scope.shopId, function(rsp) {
                $scope.catelogs.push(rsp.data);
            });
        };
        $scope.addProp = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/propCreate?id=' + $scope.editing.id, function(rsp) {
                var len = $scope.editing.properties.push(rsp.data);
                $scope.editProp(rsp.data, len - 1);
            });
        };
        $scope.editProp = function(prop, index) {
            $uibModal.open({
                templateUrl: 'propEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.prop = angular.copy(prop);
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.remove = function() {
                        $mi.close({
                            name: 'remove',
                            data: $scope.prop
                        });
                    };
                    $scope.ok = function() {
                        $mi.close({
                            name: 'update',
                            data: $scope.prop
                        });
                    };
                }]
            }).result.then(function(action) {
                if (action.name === 'update')
                    http2.post('/rest/pl/fe/matter/merchant/catelog/propUpdate', action.data, function(rsp) {
                        prop.name = rsp.data.name;
                    });
                else if (action.name === 'remove')
                    http2.get('/rest/pl/fe/matter/merchant/catelog/propRemove?property=' + prop.id, function(rsp) {
                        $scope.editing.properties.splice(index, 1);
                    });
            });
        };
        $scope.addOrderProp = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/orderPropCreate?id=' + $scope.editing.id, function(rsp) {
                var len = $scope.editing.orderProperties.push(rsp.data);
                $scope.editOrderProp(rsp.data, len - 1);
            });
        };
        $scope.editOrderProp = function(prop, index) {
            $uibModal.open({
                templateUrl: 'propEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.prop = angular.copy(prop);
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.remove = function() {
                        $mi.close({
                            name: 'remove',
                            data: $scope.prop
                        });
                    };
                    $scope.ok = function() {
                        $mi.close({
                            name: 'update',
                            data: $scope.prop
                        });
                    };
                }]
            }).result.then(function(action) {
                if (action.name === 'update')
                    http2.post('/rest/pl/fe/matter/merchant/catelog/orderPropUpdate', action.data, function(rsp) {
                        prop.name = rsp.data.name;
                    });
                else if (action.name === 'remove')
                    http2.get('/rest/pl/fe/matter/merchant/catelog/orderPropRemove?id=' + prop.id, function(rsp) {
                        $scope.editing.orderProperties.splice(index, 1);
                    });
            });
        };
        $scope.addFeedbackProp = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/feedbackPropCreate?id=' + $scope.editing.id, function(rsp) {
                var len = $scope.editing.feedbackProperties.push(rsp.data);
                $scope.editFeedbackProp(rsp.data, len - 1);
            });
        };
        $scope.editFeedbackProp = function(prop, index) {
            $uibModal.open({
                templateUrl: 'propEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.prop = angular.copy(prop);
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.remove = function() {
                        $mi.close({
                            name: 'remove',
                            data: $scope.prop
                        });
                    };
                    $scope.ok = function() {
                        $mi.close({
                            name: 'update',
                            data: $scope.prop
                        });
                    };
                }]
            }).result.then(function(action) {
                if (action.name === 'update')
                    http2.post('/rest/pl/fe/matter/merchant/catelog/feedbackPropUpdate', action.data, function(rsp) {
                        prop.name = rsp.data.name;
                    });
                else if (action.name === 'remove')
                    http2.get('/rest/pl/fe/matter/merchant/catelog/feedbackPropRemove?id=' + prop.id, function(rsp) {
                        $scope.editing.feedbackProperties.splice(index, 1);
                    });
            });
        };
        $scope.activate = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/activate?catelog=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'Y';
            });
        };
        $scope.deactivate = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/deactivate?catelog=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'N';
            });
        };
        $scope.remove = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/remove?catelog=' + $scope.editing.id, function(rsp) {
                location.href = '/rest/pl/fe/matter/merchant/shop/catelog?shop=' + $scope.$parent.shopId;
            });
        };
        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/pl/fe/matter/merchant/catelog/update?catelog=' + $scope.editing.id, nv, function(rsp) {});
        };
        $scope.get();
    }]);
    ngApp.provider.controller('ctrlProduct', ['$scope', 'http2', function($scope, http2) {
        $scope.search = function() {
            var url;
            url = '/rest/pl/fe/matter/merchant/product/list';
            url += '?shop=' + $scope.$parent.shopId;
            url += '&catelog=' + $scope.$parent.catelogId;
            http2.get(url, function(rsp) {
                $scope.products = rsp.data;
            });
        };
        $scope.open = function(product) {
            location.href = "/rest/pl/fe/matter/merchant/product?shop=" + $scope.shopId + "&product=" + product.id;
        };
        $scope.create = function() {
            var url = '/rest/pl/fe/matter/merchant/product/create';
            url += '?site=' + $scope.siteId + '&catelog=' + $scope.$parent.catelogId;
            http2.get(url, function(rsp) {
                var prod = rsp.data;
                $scope.open(prod);
                prod.prop_value2 = rsp.data.propValue2;
                $scope.products.push(prod);
                $scope.open(prod);
            });
        };
        $scope.search();
    }]);
    ngApp.provider.controller('ctrlSku', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.updateSku = function(sku, prop) {
            var nv = {};
            nv[prop] = sku[prop];
            http2.post('/rest/pl/fe/matter/merchant/catelog/skuUpdate?sku=' + sku.id, nv);
        };
        $scope.addSku = function() {
            http2.get('/rest/pl/fe/matter/merchant/catelog/skuCreate?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&catelog=' + $scope.catelogId, function(rsp) {
                $scope.skus.push(rsp.data);
            });
        };
        $scope.removeSku = function(index, sku) {
            http2.get('/rest/pl/fe/matter/merchant/catelog/skuRemove?sku=' + sku.id, function(rsp) {
                $scope.skus.splice(index, 1);
            });
        };
        $scope.setCrontab = function(sku) {
            if (!sku.autogen_rule) {
                sku.autogen_rule = {};
            }
            $uibModal.open({
                templateUrl: 'crontabEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    var crontab;
                    crontab = sku.autogen_rule.crontab || '*_*_*_*_*';
                    $scope2.data = crontab.split('_');
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function(data) {
                sku.autogen_rule.crontab = data.join('_');
                $scope.updateSku(sku, 'autogen_rule');
            });
        };
        http2.get('/rest/pl/fe/matter/merchant/catelog/skuList?shop=' + $scope.shopId + '&catelog=' + $scope.catelogId, function(rsp) {
            $scope.skus = rsp.data;
        });
    }]);
    ngApp.provider.controller('ctrlPage', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.pageTypes = [{
            type: 'product',
            name: '用户.商品'
        }, {
            type: 'ordernew.skus',
            name: '用户.新建订单.库存'
        }, {
            type: 'order.skus',
            name: '用户.查看订单.库存'
        }, {
            type: 'cart.skus',
            name: '用户.购物车.库存'
        }, {
            type: 'op.order.skus',
            name: '客服.查看订单.库存'
        }];
        http2.get('/rest/pl/fe/matter/merchant/page/byCatelog?catelog=' + $scope.$parent.catelogId, function(rsp) {
            $scope.pages = {};
            angular.forEach(rsp.data, function(page) {
                $scope.pages[page.type] = page;
            });
        });
        $scope.createCode = function(pageType) {
            var url;
            url = '/rest/pl/fe/matter/merchant/page/createByCatelog?catelog=' + $scope.$parent.catelogId;
            url += '&type=' + pageType;
            http2.get(url, function(rsp) {
                $scope.pages[pageType] = rsp.data;
            });
        };
        $scope.removeCode = function(page, index) {
            if (window.confirm('确定删除？')) {
                var url;
                url = '/rest/pl/fe/matter/merchant/page/remove?page=' + page.id;
                http2.get(url, function(rsp) {
                    delete $scope.pages[page.type];
                });
            }
        };
        $scope.config = function(page) {
            $uibModal.open({
                templateUrl: 'pageEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope2) {
                    $scope2.page = {
                        title: page.title
                    };
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.page);
                    };
                }]
            }).result.then(function(newPage) {
                var url;
                url = '/rest/pl/fe/matter/merchant/page/update';
                url += '?shop=' + $scope.$parent.shopId;
                url += '&page=' + page.id;
                http2.post(url, newPage, function(rsp) {
                    page.title = newPage.title;
                });
            });
        };
        $scope.gotoCode = function(page) {
            window.open('/rest/pl/fe/code?site=' + $scope.siteId + '&name=' + page.code_name, '_self');
        };
        $scope.resetCode = function(page) {
            if (window.confirm('重置后将丢失已经做过的修改，确定操作？')) {
                http2.get('/rest/pl/fe/matter/merchant/page/reset?page=' + page.id, function(rsp) {
                    $scope.gotoCode(page);
                });
            }
        }
    }]);
})();