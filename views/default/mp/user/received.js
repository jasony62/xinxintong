xxtApp.controller('ReceCtrl',['$scope','http2',function($scope,http2){
    $scope.page = {current:1,size:30,keyword:''};
    $scope.doSearch = function(page) {
        if (page) {
            $scope.page.current = page;
        } else 
            page = $scope.page.current;
        var param = '?page='+page+'&size='+$scope.page.size;
        if ($scope.page.keyword && $scope.page.keyword.length > 0)
            param += '&keyword='+$scope.page.keyword;
        http2.get('/rest/mp/user/messages/get'+param, function(rsp) {
            $scope.messages = rsp.data[0];
            rsp.data[1] && ($scope.page.total = rsp.data[1]);
        });
    };
    $scope.viewUser = function(openid){
        location.href = '/rest/mp/user?openid='+openid;
    };
    $scope.doSearch();
}]);
