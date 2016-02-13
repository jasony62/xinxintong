angular.module('xxt', ['ui.tms']).
controller('MpCtrl', ['$scope', 'http2', function($scope, http2) {
    var t = (new Date()).getTime();
    $scope.selectedPmp = false;
    $scope.selectpmp = function(p) {
        $scope.selectedPmp = p;
        $scope.getmps();
    };
    $scope.createpmp = function() {
        http2.get('/rest/pl/fe/main/createmp?asparent=Y', function(rsp) {
            location.replace('/rest/mp/mpaccount?mpid=' + rsp.data);
        });
    };
    $scope.createmp = function() {
        var url = '/rest/pl/fe/main/createmp';
        $scope.selectedPmp && (url += '?pmpid=' + $scope.selectedPmp.mpid);
        http2.get(url, function(rsp) {
            location.replace('/rest/mp/mpaccount?mpid=' + rsp.data);
        });
    };
    $scope.getmps = function() {
        var url = '/rest/pl/fe/mpaccounts?_=' + t;
        $scope.selectedPmp && (url += '&pmpid=' + $scope.selectedPmp.mpid);
        http2.get(url, function(rsp) {
            $scope.mps = rsp.data;
        });
    };
    $scope.openmp = function(event, mp) {
        event.preventDefault();
        event.stopPropagation();
        location.href = '/rest/mp?mpid=' + mp.mpid + '&_=' + t;
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
    $scope.getmps();
    http2.get('/rest/pl/fe/mpaccounts?asparent=Y&_=' + t, function(rsp) {
        $scope.pmps = rsp.data;
    });
}]);