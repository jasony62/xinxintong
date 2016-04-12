var app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
app.config(['$locationProvider', '$controllerProvider', '$routeProvider', function($lp, $cp, $rp) {
    var loadJs = function(url, callback) {
        var script;
        script = document.createElement('script');
        script.src = url;
        callback && (script.onload = function() {
            callback()
        });
        document.body.appendChild(script);
    };
    $lp.html5Mode(true);
    app.provider = {
        controller: $cp.register
    };
    $rp.when('/rest/pl/fe/site/sns/qy/setting', {
        templateUrl: '/views/default/pl/fe/site/sns/qy/setting.html?_=2',
        controller: 'ctrlSet',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/qy/setting.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/site/sns/qy/relay', {
        templateUrl: '/views/default/pl/fe/site/sns/qy/relay.html?_=2',
        controller: 'ctrlRelay',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/qy/relay.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).otherwise({
        templateUrl: '/views/default/pl/fe/site/sns/qy/setting.html?_=2',
        controller: 'ctrlSet',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/qy/setting.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    });
}]);
app.controller('ctrlQy', ['$scope', '$location', 'http2', function($scope, $location, http2) {
    $scope.subView = '';
    $scope.siteId = $location.search().site;
    $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+)\?/);
        if (subView) {
            $scope.subView = subView[1];
        } else {
            $scope.subView = '';
        }
    });
    http2.get('/rest/pl/fe/site/sns/qy/get?site=' + $scope.siteId, function(rsp) {
        $scope.qy = rsp.data;
    });
}]);