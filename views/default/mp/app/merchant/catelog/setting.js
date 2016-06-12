(function() {
    xxtApp.register.controller('settingCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$parent.subView = 'setting';
        $scope.open = function(catelog) {
            if (catelog.properties === undefined) {
                http2.get('/rest/mp/app/merchant/catelog/cascaded?id=' + catelog.id, function(rsp) {
                    catelog.properties = rsp.data.properties;
                    $scope.editing = catelog;
                });
            } else
                $scope.editing = catelog;
        };
        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/mp/app/merchant/shop/update?id=' + $scope.shopId, nv, function(rsp) {});
        };
        $scope.create = function() {
            http2.get('/rest/mp/app/merchant/catelog/create?shopId=' + $scope.shopId, function(rsp) {
                $scope.catelogs.push(rsp.data);
            });
        };
        $scope.addProp = function() {
            http2.get('/rest/mp/app/merchant/catelog/propCreate?id=' + $scope.editing.id, function(rsp) {
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
                    http2.post('/rest/mp/app/merchant/catelog/propUpdate', action.data, function(rsp) {
                        prop.name = rsp.data.name;
                    });
                else if (action.name === 'remove')
                    http2.get('/rest/mp/app/merchant/catelog/propRemove?property=' + prop.id, function(rsp) {
                        $scope.editing.properties.splice(index, 1);
                    });
            });
        };
        $scope.addOrderProp = function() {
            http2.get('/rest/mp/app/merchant/catelog/orderPropCreate?id=' + $scope.editing.id, function(rsp) {
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
                    http2.post('/rest/mp/app/merchant/catelog/orderPropUpdate', action.data, function(rsp) {
                        prop.name = rsp.data.name;
                    });
                else if (action.name === 'remove')
                    http2.get('/rest/mp/app/merchant/catelog/orderPropRemove?id=' + prop.id, function(rsp) {
                        $scope.editing.orderProperties.splice(index, 1);
                    });
            });
        };
        $scope.addFeedbackProp = function() {
            http2.get('/rest/mp/app/merchant/catelog/feedbackPropCreate?id=' + $scope.editing.id, function(rsp) {
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
                    http2.post('/rest/mp/app/merchant/catelog/feedbackPropUpdate', action.data, function(rsp) {
                        prop.name = rsp.data.name;
                    });
                else if (action.name === 'remove')
                    http2.get('/rest/mp/app/merchant/catelog/feedbackPropRemove?id=' + prop.id, function(rsp) {
                        $scope.editing.feedbackProperties.splice(index, 1);
                    });
            });
        };
        $scope.activate = function() {
            http2.get('/rest/mp/app/merchant/catelog/activate?catelog=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'Y';
            });
        };
        $scope.deactivate = function() {
            http2.get('/rest/mp/app/merchant/catelog/deactivate?catelog=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'N';
            });
        };
        $scope.remove = function() {
            http2.get('/rest/mp/app/merchant/catelog/remove?catelog=' + $scope.editing.id, function(rsp) {
                location.href = '/rest/mp/app/merchant/shop/catelog?shop=' + $scope.$parent.shopId;
            });
        };
        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/mp/app/merchant/catelog/update?catelog=' + $scope.editing.id, nv, function(rsp) {});
        };
        $scope.get();
    }]);
})();