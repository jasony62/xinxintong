if (/MicroMessenger/.test(navigator.userAgent)) {
    if (window.signPackage) {
        //signPackage.debug = true;
        signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
        wx.config(signPackage);
    }
}
app = angular.module('xxt', ["ngSanitize"]);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
    var ls, mpid, id, shareby;
    ls = location.search;
    mpid = ls.match(/[\?&]mpid=([^&]*)/)[1];
    id = ls.match(/[\?&]id=([^&]*)/)[1];
    shareby = ls.match(/shareby=([^&]*)/) ? ls.match(/shareby=([^&]*)/)[1] : '';
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
        sharelink = 'http://' + location.hostname + '/rest/mi/matter';
        sharelink += '?mpid=' + mpid;
        sharelink += '&type=article';
        sharelink += '&id=' + id;
        sharelink += '&tpl=cus';
        sharelink += "&shareby=" + shareid;
        window.xxt.share.set($scope.article.title, sharelink, $scope.article.summary, $scope.article.pic);
    };
    var getArticle = function() {
        var deferred = $q.defer();
        $http.get('/rest/mi/article/get?mpid=' + mpid + '&id=' + id).success(function(rsp) {
            var params, page, jslength;
            params = rsp.data;
            $scope.article = params.article;
            $http.post('/rest/mi/matter/logAccess?mpid=' + mpid + '&id=' + id + '&type=article&title=' + $scope.article.title + '&shareby=' + shareby, {
                search: location.search.replace('?', ''),
                referer: document.referrer
            });
            page = params.article.page;
            $scope.user = params.user;
            $scope.mpa = params.mpaccount;
            if (page.ext_css && page.ext_css.length) {
                angular.forEach(page.ext_css, function(css) {
                    var link, head;
                    link = document.createElement('link');
                    link.href = css.url;
                    link.rel = 'stylesheet';
                    head = document.querySelector('head');
                    head.appendChild(link);
                });
            }
            if (page.ext_js && page.ext_js.length) {
                jslength = page.ext_js.length;
                angular.forEach(page.ext_js, function(js) {
                    $.getScript(js.url, function() {
                        jslength--;
                        if (jslength === 0) {
                            if (page.js && page.js.length) {
                                $timeout(function dynamicjs() {
                                    eval(page.js);
                                });
                            }
                        }
                    });
                });
            } else {
                if (page.js && page.js.length) {
                    $timeout(function dynamicjs() {
                        eval(page.js);
                    });
                }
            }
            deferred.resolve();
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
        /MicroMessenge|Yixin/i.test(navigator.userAgent) && setShare();
        $scope.loading = false;
    });
}]);
app.directive('dynamicHtml', function($compile) {
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
});