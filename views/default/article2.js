if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
angular.module('xxt', ["ngSanitize"]).config(['$locationProvider', function ($lp) {
    $lp.html5Mode(true);
}]).controller('ctrl', ['$location', '$scope', '$http', '$sce', '$timeout', function ($location, $scope, $http, $sce, $timeout) {
    var mpid, id, shareby, setShare, getArticle;
    mpid = $location.search().mpid;
    id = $location.search().id;
    shareby = $location.search().shareby ? $location.search().shareby : '';
    $scope.mode = $location.search().mode || false;
    setShare = function () {
        var shareid, sharelink;
        shareid = $scope.user.vid + (new Date()).getTime();
        window.xxt.share.options.logger = function (shareto) {
            var url = "/rest/mi/matter/logShare";
            url += "?shareid=" + shareid;
            url += "&mpid=" + mpid;
            url += "&id=" + id;
            url += "&type=article";
            url += "&title=" + $scope.article.title;
            url += "&shareto=" + shareto;
            url += "&shareby=" + shareby;
            $http.get(url);
        };
        sharelink = location.href;
        if (/shareby=/.test(sharelink))
            sharelink = sharelink.replace(/shareby=[^&]*/, 'shareby=' + shareid);
        else
            sharelink += "&shareby=" + shareid;
        window.xxt.share.set($scope.article.title, sharelink, $scope.article.summary, $scope.article.pic);
    };
    getArticle = function () {
        $http.get('/rest/mi/article/get?mpid=' + mpid + '&id=' + id).success(function (rsp) {
            var params;
            params = rsp.data;  
            params.body = $sce.trustAsHtml(params.body);
            $scope.article = params.article;
            $scope.user = params.user;
            params.mpaccount && ($scope.mpa = params.mpaccount);
            $http.get('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + id + '&type=article&title=' + $scope.article.title + '&shareby=' + shareby);
            if (/MicroMessenge|Yixin/i.test(navigator.userAgent)) {
                setShare();
            }
            if ($scope.article.can_picviewer === 'Y') {
                var hm, body;
                body = document.querySelector('body');
                hm = document.createElement("script");
                hm.src = "/static/js/hammer.min.js";
                body.appendChild(hm);
                hm = document.createElement("script");
                hm.src = "/static/js/picViewer.js";
                body.appendChild(hm);
            }
        }).error(function (content, httpCode) {
            if (httpCode === 401) {
                var el = document.querySelector('#frmAuth');
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function () {
                        getArticle();
                        el.style.display = 'none';
                    };
                    el.setAttribute('src', content);
                    el.style.display = 'block';
                } else {
                    if (el.contentDocument && el.contentDocument.body) {
                        el.contentDocument.body.innerHTML = content;
                        el.style.display = 'block';
                    }
                }
            } else {
                alert(content);
            }
        });
    };
    getArticle();
    $scope.like = function () {
        if ($scope.mode === 'preview') return;
        var url = "/rest/mi/article/score?mpid=" + mpid + "&id=" + id;
        $http.get(url).success(function (rsp) {
            $scope.article.score = rsp.data[0];
            $scope.article.praised = rsp.data[1];
        });
    };
    $scope.newRemark = '';
    $scope.remark = function () {
        var url, param;
        if ($scope.newRemark === '') { alert('评论内容不允许为空！'); return; };
        url = "/rest/mi/article/remark?mpid=" + mpid + "&id=" + id;
        param = { remark: $scope.newRemark };
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