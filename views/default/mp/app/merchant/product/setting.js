(function() {
    xxtApp.register.controller('settingCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
        $scope.$parent.subView = 'setting';
        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/mp/app/merchant/product/update?product=' + $scope.editing.id, nv, function(rsp) {});
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
                url = '/rest/mp/app/merchant/product/propUpdate?product=' + $scope.editing.id;
                data.prop_id = prop.id;
                http2.post(url, data, function(rsp) {
                    $scope.editing.propValue[prop.id] = rsp.data;
                });
            });
        };
        $scope.activate = function() {
            http2.get('/rest/mp/app/merchant/product/activate?product=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'Y';
            });
        };
        $scope.deactivate = function() {
            http2.get('/rest/mp/app/merchant/product/deactivate?product=' + $scope.editing.id, function(rsp) {
                $scope.editing.active = 'N';
            });
        };
        $scope.remove = function() {
            http2.get('/rest/mp/app/merchant/product/remove?product=' + $scope.editing.id, function(rsp) {
                if ($scope.$parent.catelogId) {
                    location.href = '/rest/mp/app/merchant/catelog/product?shop=' + $scope.$parent.shopId + '&catelog=' + $scope.$parent.catelogId;
                } else {
                    location.href = '/rest/mp/app/merchant/shop/product?shop=' + $scope.$parent.shopId;
                }
            });
        };
    }]);
})();