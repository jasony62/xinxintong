(function() {
    xxtApp.register.controller('catelogCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'catelog';
        $scope.list = function() {
            http2.get('/rest/mp/app/merchant/catelog/list?shop=' + $scope.shopId, function(rsp) {
                $scope.catelogs = rsp.data;
            });
        };
        $scope.open = function(catelog) {
            location.href = "/rest/mp/app/merchant/catelog?shop=" + $scope.shopId + "&catelog=" + catelog.id;
        };
        $scope.create = function() {
            http2.get('/rest/mp/app/merchant/catelog/create?shop=' + $scope.shopId, function(rsp) {
                $scope.open(rsp.data);
            });
        };
        $scope.list();
    }]);
})();