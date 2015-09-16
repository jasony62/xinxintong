if (/MicroMessenger/.test(navigator.userAgent)) {
    if (window.signPackage) {
        //signPackage.debug = true;
        signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
        wx.config(signPackage);
    }
}
angular.module('xxt', ["ngSanitize"]).controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
    var ls, mpid, id, shareby;
    ls = location.search;
    mpid = ls.match(/mpid=([^&]*)/)[1];
    id = ls.match(/(\?|&)id=([^&]*)/)[2];
    shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : '';
    $scope.mpid = mpid;
    $scope.articleId = id;
    $scope.mode = ls.match(/mode=([^&]*)/) ? ls.match(/mode=([^&]*)/)[1] : '';
    var setShare = function() {
        var shareid, sharelink;
        shareid = $scope.user.vid + (new Date()).getTime();
        window.xxt.share.options.logger = function(shareto) {
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
    var getArticle = function() {
        var deferred = $q.defer();
        $http.get('/rest/mi/article/get?mpid=' + mpid + '&id=' + id).success(function(rsp) {
            var params;
            params = rsp.data;
            $scope.article = params.article;
            $scope.user = params.user;
            if (params.mpaccount.header_page) {
                (function() {
                    eval(params.mpaccount.header_page.js);
                })();
            }
            if (params.mpaccount.footer_page) {
                (function() {
                    eval(params.mpaccount.footer_page.js);
                })();
            }
            $scope.mpa = params.mpaccount;
            deferred.resolve();
            $http.post('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + id + '&type=article&title=' + $scope.article.title + '&shareby=' + shareby, {
                search: location.search.replace('?', ''),
                referer: document.referrer
            });
            if (/MicroMessenge|Yixin/i.test(navigator.userAgent)) {
                setShare();
            }
            if ($scope.article.can_picviewer === 'Y') {
                var eViewer, js, body;
                eViewer = document.createElement('div');
                eViewer.setAttribute('id', 'picViewer');
                eViewer.innerHTML = "<div><span class='page'></span><span class='prev'><i class='fa fa-angle-left'></i></span><span class='next'><i class='fa fa-angle-right'></i></span><span class='exit'><i class='fa fa-times-circle-o'></i></span></div><img>";
                document.body.appendChild(eViewer);
                body = document.querySelector('body');
                js = document.createElement("script");
                js.src = "/static/js/hammer.min.js";
                body.appendChild(js);
                js = document.createElement("script");
                js.src = "/static/js/picViewer.js?_=1";
                js.onload = function() {
                    var eImgs, aImgs, currentIndex;
                    aImgs = [];
                    eImgs = document.querySelectorAll('.wrap img');

                    (function() {
                        var oPicViewer, eCloser, ePage, ePrev, eNext, fnClickImg, fnSetActionStatus;
                        ePage = document.querySelector('#picViewer span.page');
                        ePrev = document.querySelector('#picViewer span.prev');
                        eNext = document.querySelector('#picViewer span.next');
                        eCloser = document.querySelector('#picViewer span.exit');

                        function next() {
                            if (currentIndex < aImgs.length - 1) {
                                currentIndex++;
                                eViewer.querySelector('img').src = aImgs[currentIndex].src;
                                fnSetActionStatus();
                            }
                        };

                        function prev() {
                            if (currentIndex > 0) {
                                currentIndex--;
                                eViewer.querySelector('img').src = aImgs[currentIndex].src;
                                fnSetActionStatus();
                            }
                        };

                        function fnClose() {
                            eViewer.style.display = 'none';
                            document.body.style.overflow = 'auto';
                            document.body.removeEventListener('touchmove', fnStopMove, false);
                            return false;
                        };

                        function fnStopMove(e) {
                            e.preventDefault();
                        };
                        oPicViewer = PicViewer('#picViewer img', {
                            next: next,
                            prev: prev,
                            close: fnClose
                        });

                        fnClickImg = function(event) {
                            var top, height, src;
                            event.preventDefault();
                            currentIndex = aImgs.indexOf(this);
                            top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
                            height = document.documentElement.clientHeight;
                            src = this.src;
                            document.body.style.overflow = 'hidden';
                            eViewer.style.top = top + 'px';
                            eViewer.style.height = height + 1 + 'px';
                            eViewer.style.display = 'block';
                            eViewer.querySelector('img').src = src;
                            oPicViewer.fresh();
                            fnSetActionStatus();
                            document.body.addEventListener('touchmove', fnStopMove, false);
                        };
                        fnSetActionStatus = function() {
                            if (currentIndex === 0) {
                                ePrev.classList.add('hide');
                                eNext.classList.remove('hide');
                            } else if (currentIndex === aImgs.length - 1) {
                                ePrev.classList.remove('hide');
                                eNext.classList.add('hide');
                            } else {
                                ePrev.classList.remove('hide');
                                eNext.classList.remove('hide');
                            }
                            ePage.innerHTML = currentIndex + 1 + '/' + aImgs.length;
                        };
                        ePrev.addEventListener('click', function(e) {
                            e.preventDefault();
                            prev();
                            return false;
                        }, false);
                        eNext.addEventListener('click', function(e) {
                            e.preventDefault();
                            next();
                            return false;
                        }, false);
                        eCloser.addEventListener('click', fnClose, false);
                        var img, i, l, indicator;
                        for (i = 0, l = eImgs.length; i < l; i++) {
                            img = eImgs[i];
                            img.addEventListener('click', fnClickImg);
                            indicator = document.createElement('i');
                            indicator.classList.add('fa');
                            indicator.classList.add('fa-search');
                            img.parentNode.appendChild(indicator);
                            img.parentNode.classList.add('wrap-img');
                            aImgs.push(img);
                        }
                        window.addEventListener('resize', function() {
                            var top = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
                            var height = document.documentElement.clientHeight;
                            eViewer.style.top = top + 'px';
                            eViewer.style.height = height + 1 + 'px';
                            if (eViewer.style.display === 'block') {
                                oPicViewer.fresh();
                            }
                        });
                    })();
                };
                body.appendChild(js);
            }
        }).error(function(content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmAuth');
                el.onload = function() {
                    this.height = document.documentElement.clientHeight;
                };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function() {
                        el.style.display = 'none';
                        getArticle().then(function() {
                            $scope.loading = false
                        });
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
    getArticle().then(function() {
        $scope.loading = false;
        $timeout(function() {
            var audios;
            audios = document.querySelectorAll('audio');
            audios.length > 0 && audios[0].play();
        });
    });
    $scope.like = function() {
        if ($scope.mode === 'preview') return;
        var url = "/rest/mi/article/score?mpid=" + mpid + "&id=" + id;
        $http.get(url).success(function(rsp) {
            $scope.article.score = rsp.data[0];
            $scope.article.praised = rsp.data[1];
        });
    };
    $scope.newRemark = '';
    $scope.remark = function() {
        var url, param;
        if ($scope.newRemark === '') {
            alert('评论内容不允许为空！');
            return;
        };
        url = "/rest/mi/article/remark?mpid=" + mpid + "&id=" + id;
        param = {
            remark: $scope.newRemark
        };
        $http.post(url, param).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.err_msg);
                return;
            };
            $scope.newRemark = '';
            $scope.article.remarks === false ? $scope.article.remarks = [rsp.data] : $scope.article.remarks.splice(0, 0, rsp.data);
        });
    };
    $scope.reply = function(remark) {
        $scope.newRemark += '@' + remark.nickname;
        $timeout(function() {
            document.querySelector('#gotoNewRemark').click();
        });
    };
    $scope.followMp = function() {
        location.href = 'yixin://opencard?pid=' + $scope.mpa.yx_cardid;
    };
    $scope.openChannel = function(ch) {
        location.href = '/rest/mi/matter?mpid=' + mpid + '&type=channel&id=' + ch.id;
    };
    $scope.searchByTag = function(tag) {
        location.href = '/rest/mi/article?mpid=' + mpid + '&tagid=' + tag.id;
    };
    $scope.openMatter = function(event, id, type) {
        event.preventDefault();
        event.stopPropagation();
        location.href = '/rest/mi/matter?mpid=' + mpid + '&id=' + id + '&type=' + type + '&tpl=std';
    };
}]).directive('dynamicHtml', function($compile) {
    return {
        restrict: 'EA',
        replace: true,
        link: function(scope, ele, attrs) {
            scope.$watch(attrs.dynamicHtml, function(html) {
                if (html && html.length) {
                    ele.html(html);
                    $compile(ele.contents())(scope);
                }
            });
        }
    };
}).filter('filesize', function() {
    return function(length) {
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