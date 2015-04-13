xxtApp.controller('memberCtrl',['$scope','http2', function($scope,http2){
    $scope.searchBys = [];
    $scope.page = {at:1,size:30,keyword:''};
    $scope.doSearch = function(page) {
        page && ($scope.page.at=page); 
        var url,filter = '';
        if ($scope.page.keyword !== '') {
            filter = '&kw=' + $scope.page.keyword;
            filter += '&by=' + $scope.page.searchBy;
        }
        url = '/rest/mp/user/member?authid='+$scope.selectedAuthapi.authid;
        url += '&page='+$scope.page.at+'&size='+$scope.page.size+filter
        url += '&contain=total';
        if ($scope.attrs === undefined) url+=',memberAttrs';
        http2.get(url, function(rsp){
            var i,member,members = rsp.data[0];
            for (i in members) {
                member = members[i];
                if (member.extattr) member.extattr = JSON.parse(member.extattr);
            }
            $scope.roll = members;
            rsp.data[1] !== undefined && ($scope.page.total = rsp.data[1]);
            rsp.data[2] && ($scope.attrs = rsp.data[2]);
        });
    };
    $scope.changeAuthapi = function() {
        $scope.attrs = undefined;
        $scope.doSearch();
    };
    $scope.$watch('attrs',function(nv){
        if (!nv) return;
        nv.attr_name[0]==0 && $scope.searchBys.push({n:'姓名',v:'name'});
        nv.attr_mobile[0]==0 && $scope.searchBys.push({n:'手机号',v:'mobile'});
        nv.attr_email[0]==0 && $scope.searchBys.push({n:'邮箱',v:'email'});
        //nv.can_member_card==='Y' && $scope.searchBys.push({n:'会员卡号',v:'cardno'});
        $scope.page.searchBy = $scope.searchBys[0].v;
    });
    $scope.keywordKeyup = function(evt) {
        if (evt.which === 13) $scope.doSearch();
    };
    $scope.viewUser = function(event,fan){
        event.preventDefault();
        event.stopPropagation();
        location.href = '/rest/mp/user?fid='+fan.fid;
    };
    http2.get('/rest/mp/mpaccount/authapis?valid=Y', function(rsp){
        $scope.authapis = rsp.data;
        $scope.selectedAuthapi = $scope.authapis[0];
        $scope.doSearch();
    });
}]);
