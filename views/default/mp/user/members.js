xxtApp.controller('memberCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.searchBys = [];
    $scope.page = {
        at: 1,
        size: 30,
        keyword: ''
    };
    $scope.doSearch = function(page) {
        page && ($scope.page.at = page);
        var url, filter = '';
        if ($scope.page.keyword !== '') {
            filter = '&kw=' + $scope.page.keyword;
            filter += '&by=' + $scope.page.searchBy;
        }
        url = '/rest/mp/user/member/list?authid=' + $scope.selectedAuthapi.authid;
        url += '&page=' + $scope.page.at + '&size=' + $scope.page.size + filter
        url += '&contain=total,memberAttrs';
        http2.get(url, function(rsp) {
            var i, member, members = rsp.data.members;
            for (i in members) {
                member = members[i];
                if (member.extattr) {
                    try {
                        member.extattr = JSON.parse(member.extattr);
                    } catch (e) {
                        member.extattr = {};
                    }
                }
            }
            $scope.roll = members;
            $scope.page.total = rsp.data.total;
            $scope.attrs = rsp.data.attrs;
        });
    };
    $scope.changeAuthapi = function() {
        $scope.attrs = undefined;
        $scope.doSearch();
    };
    $scope.$watch('attrs', function(nv) {
        if (!nv) return;
        nv.attr_name[0] == 0 && $scope.searchBys.push({
            n: '姓名',
            v: 'name'
        });
        nv.attr_mobile[0] == 0 && $scope.searchBys.push({
            n: '手机号',
            v: 'mobile'
        });
        nv.attr_email[0] == 0 && $scope.searchBys.push({
            n: '邮箱',
            v: 'email'
        });
        $scope.page.searchBy = $scope.searchBys[0].v;
    });
    $scope.keywordKeyup = function(evt) {
        if (evt.which === 13) $scope.doSearch();
    };
    $scope.viewUser = function(event, member) {
        event.preventDefault();
        event.stopPropagation();
        location.href = '/rest/mp/user?openid=' + member.openid;
    };
    http2.get('/rest/mp/authapi/get?valid=Y', function(rsp) {
        $scope.authapis = rsp.data;
        $scope.selectedAuthapi = $scope.authapis[0];
        $scope.doSearch();
    });
}]);