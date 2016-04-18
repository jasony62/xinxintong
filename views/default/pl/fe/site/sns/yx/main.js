var ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt']);
ngApp.config(['$locationProvider', '$controllerProvider', '$routeProvider', function($lp, $cp, $rp) {
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
    ngApp.provider = {
        controller: $cp.register
    };
    $rp.when('/rest/pl/fe/site/sns/yx/setting', {
        templateUrl: '/views/default/pl/fe/site/sns/yx/setting.html?_=2',
        controller: 'ctrlSet',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/setting.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/site/sns/yx/text', {
        templateUrl: '/views/default/pl/fe/site/sns/yx/text.html?_=2',
        controller: 'ctrlText',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/text.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/site/sns/yx/menu', {
        templateUrl: '/views/default/pl/fe/site/sns/yx/menu.html?_=2',
        controller: 'ctrlMenu',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/menu.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/site/sns/yx/qrcode', {
        templateUrl: '/views/default/pl/fe/site/sns/yx/qrcode.html?_=2',
        controller: 'ctrlQrcode',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/qrcode.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/site/sns/yx/other', {
        templateUrl: '/views/default/pl/fe/site/sns/yx/other.html?_=2',
        controller: 'ctrlOther',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/other.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/site/sns/yx/relay', {
        templateUrl: '/views/default/pl/fe/site/sns/yx/relay.html?_=2',
        controller: 'ctrlRelay',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/relay.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).when('/rest/pl/fe/site/sns/yx/page', {
        templateUrl: '/views/default/pl/fe/site/sns/yx/page.html?_=2',
        controller: 'ctrlPage',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/page.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    }).otherwise({
        templateUrl: '/views/default/pl/fe/site/sns/yx/setting.html?_=2',
        controller: 'ctrlSet',
        resolve: {
            load: function($q) {
                var defer = $q.defer();
                loadJs('/views/default/pl/fe/site/sns/yx/setting.js', function() {
                    defer.resolve();
                });
                return defer.promise;
            }
        }
    });
}]);
ngApp.controller('ctrlYx', ['$scope', '$location', 'http2', function($scope, $location, http2) {
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
    http2.get('/rest/pl/fe/site/sns/yx/get?site=' + $scope.siteId, function(rsp) {
        $scope.yx = rsp.data;
    });
}]);