var app = angular.module('ngApp', []);
app.controller('ctrlNgApp', ['$scope', '$http', function($scope, $http){
    var matterId = location.search.match('matterId=(.*)')[1],
        matterType = location.search.match('matterType=(.*)')[1],
        siteId = location.search.match('site=(.*)')[1];
    $scope.page = {
        at: 1,
        size: 10,
        j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    }
    $scope.order = function(item) {
        var url = '/rest/site/fe/user/share/getMyShareLog';
            url += '?matterType=' + matterType + '&matterId=' + matterId;
            url += '&orderBy=' + item + $scope.page.j();
        $http.get(url).success(function(rsp) {
            $scope.results = rsp.data;
        });
    }
    $scope.more = function() {
        $scope.page.at++;
        $scope.order();
    }
    $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
        $scope.site = rsp.data;
        $scope.order('read');
    });
}]);
