angular.module('xxt', ['ui.tms']).
controller('ctrlMain', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date()).getTime();
    $scope.selectedPmp = false;
    $scope.selectpmp = function(p) {
        $scope.selectedPmp = p;
        $scope.getmps();
    };
    $scope.createpmp = function() {
        http2.get('/rest/pl/fe/site/create?asparent=Y', function(rsp) {
            location.replace('/rest/mp/mpaccount?mpid=' + rsp.data);
        });
    };
    $scope.create = function() {
        var url = '/rest/pl/fe/site/create';
        http2.get(url, function(rsp) {
            location.href = '/rest/pl/fe/site/setting?id=' + rsp.data.id;
        });
    };
    $scope.list = function() {
        var url = '/rest/pl/fe/site/list?_=' + t;
        http2.get(url, function(rsp) {
            $scope.sites = rsp.data;
        });
    };
    $scope.open = function(event, site) {
        event.preventDefault();
        event.stopPropagation();
        location.href = '/rest/pl/fe/site?id=' + site.id + '&_=' + t;
    };
    $scope.removemp = function(event, mp) {
        event.preventDefault();
        event.stopPropagation();
        if (mp.yx_joined === 'Y' || mp.wx_joined === 'Y' || mp.qy_joined === 'Y') {
            $scope.errmsg = '公众号已经开通不允许删除！';
            return;
        }
        http2.get('/rest/pl/fe/removemp?mpid=' + mp.mpid, function(rsp) {
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var i = $scope.mps.indexOf(mp);
            $scope.mps.splice(i, 1);
            if (mp.asparent === 'Y') {
                for (i = $scope.mps.length - 1; i >= 0; i--) {
                    if ($scope.mps[i].parent_mpid === mp.mpid) {
                        $scope.mps.splice(i, 1);
                    }
                }
            }
        });
    };
    $scope.$on('xxt.notice-box.timeout', function(event, name) {
        if (name === 'info') $scope.infomsg = '';
        else if (name === 'err') $scope.errmsg = '';
    });
    $scope.list();
}]);