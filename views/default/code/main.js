xxtApp.controller('mainCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.page = {
        at: 1,
        size: 30
    }
    $scope.doSearch = function() {
        var url = '/rest/code?page=' + $scope.page.at + '&size=' + $scope.page.size;
        http2.get(url, function(rsp) {
            $scope.pages = rsp.data[0];
            $scope.page.total = rsp.data[1];
        }, {
            'headers': {
                'Accept': 'application/json'
            }
        });
    };
    $scope.open = function(pid) {
        if (pid)
            location.href = '/rest/code?pid=' + pid;
        else {
            http2.get('/rest/code/create', function(rsp) {
                location.href = '/rest/code?pid=' + rsp.data.id;
            });
        }
    };
    $scope.removePage = function(page) {};
    $scope.doSearch();
}]);