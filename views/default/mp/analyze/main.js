xxtApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/page/mp/analyze/mp', {
        templateUrl: '/views/default/mp/analyze/mp.html',
        controller: 'mpCtrl',
    }).when('/page/mp/analyze/user', {
        templateUrl: '/views/default/mp/analyze/user.html',
        controller: 'userCtrl',
    }).when('/page/mp/analyze/matter', {
        templateUrl: '/views/default/mp/analyze/matter.html',
        controller: 'matterCtrl'
    }).when('/page/mp/analyze/coin', {
        templateUrl: '/views/default/mp/analyze/coin.html',
        controller: 'coinCtrl'
    }).otherwise({
        templateUrl: '/views/default/mp/analyze/mp.html',
        controller: 'mpCtrl'
    });
}]);
xxtApp.controller('analyzeCtrl', ['$scope', function($scope) {
    var current, startAt, endAt;
    current = new Date();
    startAt = {
        year: current.getFullYear(),
        month: current.getMonth() + 1,
        mday: current.getDate(),
        getTime: function() {
            var d = new Date(this.year, this.month - 1, this.mday, 0, 0, 0, 0);
            return d.getTime();
        }
    };
    endAt = {
        year: current.getFullYear(),
        month: current.getMonth() + 1,
        mday: current.getDate(),
        getTime: function() {
            var d = new Date(this.year, this.month - 1, this.mday, 23, 59, 59, 0);
            return d.getTime();
        }
    };
    $scope.startAt = startAt.getTime() / 1000;
    $scope.endAt = endAt.getTime() / 1000;
    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.opened = true;
    };
    $scope.subView = '';
}]);
xxtApp.controller('mpCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'mp';
    $scope.page = {
        at: 1,
        size: 30,
        total: 0,
        param: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.fetch = function(page) {
        var url;
        page && ($scope.page.at = page);
        url = '/rest/mp/analyze/mpActions';
        url += '?startAt=' + $scope.startAt;
        url += '&endAt=' + $scope.endAt;
        url += '&' + $scope.page.param();
        http2.get(url, function(rsp) {
            $scope.logs = rsp.data.logs;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        $scope[data.state] = data.value;
        $scope.fetch(1);
    });
    $scope.fetch(1);
}]);
xxtApp.controller('userCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'user';
    $scope.page = {
        at: 1,
        size: 30,
        total: 0,
        param: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.orderby = 'read';
    $scope.fetch = function(page) {
        var url;
        page && ($scope.page.at = page);
        url = '/rest/mp/analyze/userActions';
        url += '?orderby=' + $scope.orderby;
        url += '&startAt=' + $scope.startAt;
        url += '&endAt=' + $scope.endAt;
        url += '&' + $scope.page.param();
        http2.get(url, function(rsp) {
            $scope.users = rsp.data.users;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.viewUser = function(openid) {
        location.href = '/rest/mp/user?openid=' + openid;
    };
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        $scope[data.state] = data.value;
        $scope.fetch(1);
    });
    $scope.fetch(1);
}]);
xxtApp.controller('matterCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'matter';
    $scope.page = {
        at: 1,
        size: 30,
        total: 0,
        param: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.orderby = 'read';
    $scope.fetch = function(page) {
        var url;
        page && ($scope.page.at = page);
        url = '/rest/mp/analyze/matterActions';
        url += '?orderby=' + $scope.orderby;
        url += '&startAt=' + $scope.startAt;
        url += '&endAt=' + $scope.endAt;
        url += '&' + $scope.page.param();
        http2.get(url, function(rsp) {
            $scope.matters = rsp.data.matters;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        $scope[data.state] = data.value;
        $scope.fetch(1);
    });
    $scope.fetch(1);
}]);
xxtApp.controller('coinCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$parent.subView = 'coin';
    $scope.period = 'A';
    $scope.page = {
        at: 1,
        size: 30,
        total: 0,
        param: function() {
            return 'page=' + this.at + '&size=' + this.size;
        }
    };
    $scope.fetch = function(page) {
        var url;
        page && ($scope.page.at = page);
        url = '/rest/mp/analyze/coin';
        url += '?period=' + $scope.period;
        url += '&' + $scope.page.param();
        http2.get(url, function(rsp) {
            $scope.fans = rsp.data.fans;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.$watch('period', function(nv) {
        if (nv) {
            $scope.fetch(1);
        }
    });
}]);