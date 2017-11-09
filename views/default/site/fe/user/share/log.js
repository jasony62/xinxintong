var app = angular.module('ngApp', []);
app.controller('ctrlNgApp', ['$scope', '$http', '$location', function($scope, $http, $location){
    var matterId = location.search.match(/matterId=([^&]*)/)[1],
        matterType = location.search.match(/matterType=([^&]*)/)[1],
        siteId = location.search.match(/site=([^&]*)/)[1],
        uid = location.search.match(/uid=([^&]*)/)[1];
    $scope.page = {
        at: 1,
        size: 10,
        j: function() {
            return '&page=' + this.at + '&size=' + this.size;
        }
    }
    $scope.order = function(item, append, at = 1) {
        $scope.filter = item;
        $scope.page.at = at;
        var url = '/rest/site/fe/user/share/getMyShareLog';
            url += '?userid=' + uid + '&matterType=' + matterType + '&matterId=' + matterId;
            url += '&orderBy=' + item + $scope.page.j();
        $http.get(url).success(function(rsp) {
            if(rsp.data.users.length > 0) {
                angular.element('.note').css('display','none');
                if(append) {
                    $scope.results =  $scope.results.concat(rsp.data.users);
                }else{
                    $scope.results = rsp.data.users;
                }
                $scope.page.total = rsp.data.total;
            }else {
                $scope.results = [];
                $scope.page.total = rsp.data.total;
                angular.element('.note').css('display','block');
            }
        });
    }
    $scope.more = function() {
        $scope.page.at++;
        $scope.order($scope.filter, true, $scope.page.at);
    }
    $http.get('/rest/site/fe/get?site=' + siteId).success(function(rsp) {
        $scope.site = rsp.data;
        $scope.order('read');
    });
}]);
