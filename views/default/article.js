if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
angular.module('xxt', ["ngSanitize"]).config(['$locationProvider', function ($lp) {
    $lp.html5Mode(true);
}]).controller('ctrl', ['$location', '$scope', '$http', '$sce', '$timeout', '$q', function ($location, $scope, $http, $sce, $timeout, $q) {
    var mpid, id, shareby;
    mpid = $location.search().mpid;
    id = $location.search().id;
    shareby = $location.search().shareby ? $location.search().shareby : '';
    $scope.mpid = mpid;
    $scope.articleId = id;
    $scope.mode = $location.search().mode || false;
    var setShare = function () {
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
    var getArticle = function () {
        var deferred = $q.defer();
        $http.get('/rest/mi/article/get?mpid=' + mpid + '&id=' + id).success(function (rsp) {
            var params;
            params = rsp.data;
            params.article.body = $sce.trustAsHtml(params.article.body);
            $scope.article = params.article;
            $scope.user = params.user;
            if (params.mpaccount.header_page) {
                params.mpaccount.header_page.html = $sce.trustAsHtml(params.mpaccount.header_page.html);
                (function () {
                    eval(params.mpaccount.header_page.js);
                })();
            }
            if (params.mpaccount.footer_page) {
                params.mpaccount.footer_page.html = $sce.trustAsHtml(params.mpaccount.footer_page.html);
                (function () {
                    eval(params.mpaccount.footer_page.js);
                })();
            }
            $scope.mpa = params.mpaccount;
            deferred.resolve();
            $http.get('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + id + '&type=article&title=' + $scope.article.title + '&shareby=' + shareby);
            if (/MicroMessenge|Yixin/i.test(navigator.userAgent)) {
                setShare();
            }
            if ($scope.article.can_picviewer === 'Y') {
                var eViewer, hm, body;
                eViewer = document.createElement('div');
                eViewer.setAttribute('id', 'picViewer');
                eViewer.innerHTML = "<span><i class='fa fa-times-circle-o'></i></span><img>";
                document.body.appendChild(eViewer);
                body = document.querySelector('body');
                hm = document.createElement("script");
                hm.src = "/static/js/hammer.min.js";
                body.appendChild(hm);
                hm = document.createElement("script");
                hm.src = "/static/js/picViewer.js";
                hm.onload = function () {
                    var oPicViewer = PicViewer('#picViewer img', {});
                    var clickImg = function (event) {
                        event.preventDefault();
                        var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
                        var height = document.documentElement.clientHeight;
                        var src = this.src;
                        document.body.style.overflow = 'hidden';
                        eViewer.style.top = top + 'px';
                        eViewer.style.height = height + 1 + 'px';
                        eViewer.style.display = 'block';
                        eViewer.querySelector('img').src = src;
                        oPicViewer.fresh();
                    };
                    var supportPicviewer = function () {
                        var eThumbs = document.querySelectorAll('.wrap img');
                        var eCloser = document.querySelector('#picViewer span');

                        eCloser.addEventListener('click', function (e) {
                            eViewer.style.display = 'none';
                            document.body.style.overflow = 'auto';
                            return false;
                        }, false);
                        eViewer.addEventListener('touchmove', function (e) {
                            e.preventDefault();
                        }, false);
                        for (var i = 0, l = eThumbs.length; i < l; i++) {
                            eThumbs[i].addEventListener('click', clickImg);
                        }
                        window.addEventListener('resize', function () {
                            if (eViewer.style.display === 'block') {
                                var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
                                var height = document.documentElement.clientHeight;
                                eViewer.style.top = top + 'px';
                                eViewer.style.height = height + 1 + 'px';
                                oPicViewer.fresh();
                            }
                        });
                    };
                    supportPicviewer();
                };
                body.appendChild(hm);
            }
        }).error(function (content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmAuth');
                el.onload = function () { this.height = document.documentElement.clientHeight; };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function () {
                        el.style.display = 'none';
                        getArticle().then(function () { $scope.loading = false });
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
        return deferred.promise;
    };
    $scope.loading = true;
    getArticle().then(function () {
        $scope.loading = false;
        $timeout(function () {
            var audios;
            audios = document.querySelectorAll('audio');
            audios.length > 0 && audios[0].play();
        });
    });
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
    $scope.followMp = function () {
        location.href = 'yixin://opencard?pid=' + $scope.mpa.yx_cardid;
    };
    window.openMatter = function (id, type) {
        location.href = '/rest/mi/matter?mpid=' + mpid + '&id=' + id + '&type=' + type + '&tpl=std';
    };
}]).filter('filesize', function () {
    return function (length) {
        var unit;
        if (length / 1024 < 1) {
            unit = 'B';
        } else {
            length = length / 1024;
            if (length / 1024 < 1) {
                unit = 'K';
            } else {
                length = length / 1024;
                unit = 'M';
            }
        }
        length = (new Number(length)).toFixed(2);

        return length + unit;
    };
});