xxtApp.controller('analyzeCtrl', ['$scope', '$http', function ($scope, $http) {
    var date, current, startAt, endAt;
    current = new Date();
    startAt = {
        year: current.getFullYear(),
        month: current.getMonth() + 1,
        mday: current.getDate(),
        getTime: function () {
            var d = new Date(this.year, this.month - 1, this.mday, 0, 0, 0, 0);
            return d.getTime();
        }
    };
    endAt = {
        year: current.getFullYear(),
        month: current.getMonth() + 1,
        mday: current.getDate(),
        getTime: function () {
            var d = new Date(this.year, this.month - 1, this.mday, 23, 59, 59, 0);
            return d.getTime();
        }
    };
    $scope.startAt = startAt.getTime() / 1000;
    $scope.endAt = endAt.getTime() / 1000;
    $scope.open = function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        $scope.opened = true;
    };
}]);
xxtApp.controller('useractionCtrl', ['$scope', '$http', function ($scope, $http) {
    $scope.page = { size: 30 };
    $scope.orderby = 'read';
    $scope.getData = function (page) {
        if (page) $scope.page.current = page;
        var url = '/rest/mp/analyze/userActions';
        url += '?orderby=' + $scope.orderby;
        url += '&startAt=' + $scope.startAt;
        url += '&endAt=' + $scope.endAt;
        url += '&page=' + $scope.page.current;
        url += '&size=' + $scope.page.size;
        $http.get(url).
            success(function (rsp) {
                $scope.users = rsp.data[0];
                $scope.page.total = rsp.data[1];
            });
    };
    $scope.viewUser = function (fid) {
        location.href = '/rest/mp/user?fid=' + fid;
    };
    $scope.$on('xxt.tms-datepicker.change', function (event, data) {
        $scope[data.state] = data.value;
        $scope.getData(1);
    });
    $scope.getData(1);
}]);
xxtApp.controller('matteractionCtrl', ['$scope', '$http', function ($scope, $http) {
    $scope.page = { size: 30 };
    $scope.orderby = 'read';
    $scope.getData = function (page) {
        if (page) $scope.page.current = page;
        var url = '/rest/mp/analyze/matterActions';
        url += '?orderby=' + $scope.orderby;
        url += '&startAt=' + $scope.startAt;
        url += '&endAt=' + $scope.endAt;
        url += '&page=' + $scope.page.current;
        url += '&size=' + $scope.page.size;
        $http.get(url).
            success(function (rsp) {
                $scope.matters = rsp.data[0];
                $scope.page.total = rsp.data[1];
            });
    };
    $scope.$on('xxt.tms-datepicker.change', function (event, data) {
        $scope[data.state] = data.value;
        $scope.getData(1);
    });
    $scope.getData(1);
}]);
