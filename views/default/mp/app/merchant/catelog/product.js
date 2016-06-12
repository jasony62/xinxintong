(function() {
    xxtApp.register.controller('productCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'product';
        $scope.search = function() {
            var url;
            url = '/rest/mp/app/merchant/product/list';
            url += '?shop=' + $scope.$parent.shopId;
            url += '&catelog=' + $scope.$parent.catelogId;
            http2.get(url, function(rsp) {
                $scope.products = rsp.data;
            });
        };
        $scope.open = function(product) {
            location.href = "/rest/mp/app/merchant/product?shop=" + $scope.shopId + "&product=" + product.id;
        };
        $scope.create = function() {
            var url = '/rest/mp/app/merchant/product/create';
            url += '?catelog=' + $scope.$parent.catelogId;
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
})();