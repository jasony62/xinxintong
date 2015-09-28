xxtApp.controller('productCtrl', ['$scope', '$location', '$modal', 'http2', function($scope, $location, $modal, http2) {
    $scope.shopId = $location.search().shopId;
    $scope.id = $location.search().id;
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
    });
    http2.get('/rest/mp/app/merchant/product/get?id=' + $scope.id, function(rsp) {
        $scope.editing = rsp.data;
    });
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/merchant/product/update?id=' + $scope.editing.id, nv, function(rsp) {});
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
        $modal.open({
            templateUrl: 'propValueSetter.html',
            backdrop: 'static',
            controller: ['$modalInstance', '$scope', function($mi, $scope2) {
                $scope2.prop = prop;
                if ($scope.editing.propValue2[prop.id]) {
                    $scope2.data = angular.copy($scope.editing.propValue2[prop.id]);
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
            url = '/rest/mp/app/merchant/product/propUpdate?id=' + $scope.editing.id;
            data.prop_id = prop.id;
            http2.post(url, data, function(rsp) {
                $scope.editing.propValue2[prop.id] = rsp.data;
            });
        });
    };
    $scope.addSKU = function() {
        http2.get('/rest/mp/app/merchant/product/skuCreate?id=' + $scope.editing.id, function(rsp) {
            $scope.editing.skus.push(rsp.data);
        });
    };
    $scope.updateSku = function(sku, prop) {
        var nv = {};
        nv[prop] = sku[prop];
        http2.post('/rest/mp/app/merchant/product/skuUpdate?id=' + sku.id, nv, function(rsp) {});
    };
}]);