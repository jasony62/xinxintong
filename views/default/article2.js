angular.module('xxt', ["ngSanitize"]).config(['$locationProvider', function ($lp) {
    $lp.html5Mode(true);
}]).controller('ctrl', ['$location', '$scope', '$http', '$sce', '$timeout', function ($location, $scope, $http, $sce, $timeout) {
    var mpid, id, mode;
    mpid = $location.search().mpid;
    id = $location.search().id;
    $scope.mode = $location.search().mode || false;
    $http.get('/rest/mi/article/get?mpid=' + mpid + '&id=' + id).success(function (rsp) {
        var params = rsp.data;
        params.body = $sce.trustAsHtml(params.body);
        $scope.article = params.article;
        $scope.user = params.user;
        params.mpaccount && ($scope.mpa = params.mpaccount);
    });
    $scope.newRemark = '';
    $scope.like = function () {
        if ($scope.mode === 'preview') return;
        var url = "/rest/mi/article/score?mpid=" + mpid + "&id=" + id;
        $http.get(url).success(function (rsp) {
            $scope.article.score = rsp.data[0];
            $scope.article.praised = rsp.data[1];
        });
    };
    $scope.remark = function () {
        var url = "/rest/mi/article/remark?mpid=" + mpid + "&id=" + id;
        if ($scope.newRemark === '') { alert('评论内容不允许为空！'); return; };
        var param = { remark: $scope.newRemark };
        $http.post(url, param).success(function (rsp) {
            if (rsp.err_code != 0) { alert(rsp.err_msg); return; };
            $scope.newRemark = '';
            $scope.article.remarks === false ? $scope.article.remarks = [rsp.data] : $scope.article.remarks.splice(0, 0, rsp.data);
            $timeout(function () {
                document.querySelector('#gotoRemarksHeader').click();
            });
        });
    };
    $scope.reply = function (remark) {
        $scope.newRemark += '@' + remark.nickname;
        $timeout(function () {
            document.querySelector('#gotoNewRemark').click();
        });
    };
}]);