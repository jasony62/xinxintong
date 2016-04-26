ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$controllerProvider', '$routeProvider', '$locationProvider', '$compileProvider', function($cp, $rp, $lp, $compileProvider) {
    $lp.html5Mode(true);
    ngApp.provider = {
        controller: $cp.register,
        directive: $compileProvider.directive
    };
    $rp.otherwise({
        templateUrl: '/views/default/pl/fe/matter/merchant/product/setting.html?_=1',
        controller: 'ctrlSetting',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                (function() {
                    $.getScript('/views/default/pl/fe/matter/merchant/product/setting.js', function() {
                        defer.resolve();
                    });
                })();
                return defer.promise;
            }
        }
    });
}]);
ngApp.controller('ctrlProduct', ['$scope', 'http2', '$location', function($scope, http2, $location) {
    $scope.siteId = $location.search().site;
    $scope.shopId = $location.search().shop;
    $scope.productId = $location.search().product;
    $scope.gotoCatelog = function() {
        location.href = '/rest/pl/fe/matter/merchant/catelog?site=' + $scope.siteId + '&shop=' + $scope.shopId + '&catelog=' + $scope.editing.catelog.id;
    };
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    $scope.get = function() {
        http2.get('/rest/pl/fe/matter/merchant/product/get?product=' + $scope.productId, function(rsp) {
            var url;
            $scope.editing = rsp.data;
            url = 'http://' + location.host;
            url += "/rest/site/fe/matter/merchant/product";
            url += "?site=" + $scope.siteId;
            url += "&shop=" + $scope.shopId;
            url += "&catelog=" + $scope.editing.cate_id;
            url += "&product=" + $scope.productId;
            $scope.entry = {
                url: url,
            };
        });
    };
    $scope.get();
}]);
ngApp.controller('orderCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'order';
}]);