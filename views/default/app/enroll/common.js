if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage !== undefined) {
    signPackage.jsApiList = ['hideOptionMenu', 'showOptionMenu', 'closeWindow', 'chooseImage', 'uploadImage', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'getLocation'];
    signPackage.debug = false;
    wx.config(signPackage);
    wx.ready(function() {
        wx.showOptionMenu();
    });
} else if (/YiXin/i.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        YixinJSBridge.call('showOptionMenu');
    }, false);
}
var LS = (function(fields) {
    function locationSearch() {
        var ls, search;
        ls = location.search;
        search = {};
        angular.forEach(fields, function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            search[q] = match ? match[1] : '';
        });
        return search;
    };
    /*join search*/
    function j(method) {
        var j, l, url = '/rest/app/enroll',
            _this = this,
            search = [];
        method && method.length && (url += '/' + method);
        if (arguments.length > 1) {
            for (i = 1, l = arguments.length; i < l; i++) {
                search.push(arguments[i] + '=' + _this.p[arguments[i]]);
            };
            url += '?' + search.join('&');
        }
        return url;
    };
    return {
        p: locationSearch(),
        j: j
    };
})(['mpid', 'aid', 'rid', 'page', 'ek', 'preview', 'newRecord']);
var PG = (function() {
    return {
        exec: function(task) {
            var obj, fn, args, valid;
            valid = true;
            obj = $scope;
            args = task.match(/\((.*?)\)/)[1].replace(/'|"/g, "").split(',');
            angular.forEach(task.replace(/\(.*?\)/, '').split('.'), function(attr) {
                if (fn) obj = fn;
                if (!obj[attr]) {
                    valid = false;
                    return;
                }
                fn = obj[attr];
            });
            if (valid) {
                fn.apply(obj, args);
            }
        },
        setMember: function(params, member) {
            if (params && member && member.authid && params.user.members && params.user.members.length) {
                angular.forEach(params.user.members, function(member2) {
                    if (member2.authapi_id == member.authid) {
                        $("[ng-model^='data.member']").each(function() {
                            var attr;
                            attr = $(this).attr('ng-model');
                            attr = attr.replace('data.member.', '');
                            attr = attr.split('.');
                            if (attr.length == 2) {
                                !member.extattr && (member.extattr = {});
                                member.extattr[attr[1]] = member2.extattr[attr[1]];
                            } else {
                                member[attr[0]] = member2[attr[0]];
                            }
                        });
                    }
                });
            }
        }
    };
})();
var setPage = function($scope, page) {
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
        var i, l, loadJs;
        i = 0;
        l = page.ext_js.length;
        loadJs = function() {
            var js;
            js = page.ext_js[i];
            $.getScript(js.url, function() {
                i++;
                if (i === l) {
                    if (page.js && page.js.length) {
                        $scope.$apply(
                            function dynamicjs() {
                                eval(page.js);
                                $scope.Page = page;
                            }
                        );
                    }
                } else {
                    loadJs();
                }
            });
        };
        loadJs();
    } else if (page.js && page.js.length) {
        (function dynamicjs() {
            eval(page.js);
            $scope.Page = page;
        })();
    } else {
        $scope.Page = page;
    }
};
var setShareData = function(scope, params, $http) {
    try {
        var sharelink, summary;
        sharelink = 'http://' + location.hostname + LS.j('', 'mpid', 'aid');
        if (params.page.share_page && params.page.share_page === 'Y') {
            sharelink += '&page=' + params.page.name;
            sharelink += '&ek=' + params.enrollKey;
        }
        window.shareid = params.user.vid + (new Date()).getTime();
        sharelink += "&shareby=" + window.shareid;
        summary = params.app.summary;
        if (params.page.share_summary && params.page.share_summary.length && params.record)
            summary = params.record.data[params.page.share_summary];
        scope.shareData = {
            title: params.app.title,
            link: sharelink,
            desc: summary,
            pic: params.app.pic
        };
        window.xxt.share.set(params.app.title, sharelink, summary, params.app.pic);
        window.shareCounter = 0;
        window.xxt.share.options.logger = function(shareto) {
            var app, url;
            app = scope.App;
            url = "/rest/mi/matter/logShare";
            url += "?shareid=" + window.shareid;
            url += "&mpid=" + LS.p.mpid;
            url += "&id=" + app.id;
            url += "&type=enroll";
            url += "&title=" + app.title;
            url += "&shareby=" + scope.params.shareby;
            url += "&shareto=" + shareto;
            $http.get(url);
            window.shareCounter++;
            /* 是否需要自动登记 */
            if (app.can_autoenroll === 'Y' && scope.Page.autoenroll_onshare === 'Y') {
                $http.get(LS.j('emptyGet', 'mpid', 'aid') + '&once=Y');
            }
            window.onshare && window.onshare(window.shareCounter);
        };
    } catch (e) {
        alert(e.message);
    }
};
app = angular.module('app', ['ngSanitize', 'infinite-scroll']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.controller('ctrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var tasksOfOnReady = [];
    $scope.errmsg = '';
    $scope.closePreviewTip = function() {
        $scope.preview = 'N';
    };
    var openAskFollow = function() {
        $http.get('/rest/app/enroll/askFollow?mpid=' + LS.p.mpid).error(function(content) {
            var body, el;;
            body = document.body;
            el = document.createElement('iframe');
            el.setAttribute('id', 'frmPopup');
            el.height = body.clientHeight;
            body.scrollTop = 0;
            body.appendChild(el);
            window.closeAskFollow = function() {
                el.style.display = 'none';
            };
            el.setAttribute('src', '/rest/app/enroll/askFollow?mpid=' + LS.p.mpid);
            el.style.display = 'block';
        });
    };
    $scope.closeWindow = function() {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            window.wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            window.YixinJSBridge.call('closeWebView');
        }
    };
    $scope.addRecord = function(event, page) {
        page ? $scope.gotoPage(event, page, null, null, false, 'Y') : alert('没有指定登记编辑页');
    };
    $scope.gotoPage = function(event, page, ek, rid, fansOnly, newRecord) {
        event.preventDefault();
        event.stopPropagation();
        if (fansOnly && !$scope.User.fan) {
            openAskFollow();
            return;
        }
        var url = LS.j('', 'mpid', 'aid');
        if (ek !== undefined && ek !== null && ek.length) {
            url += '&ek=' + ek;
        }
        rid !== undefined && rid !== null && rid.length && (url += '&rid=' + rid);
        page !== undefined && page !== null && page.length && (url += '&page=' + page);
        newRecord !== undefined && newRecord === 'Y' && (url += '&newRecord=Y');
        location.replace(url);
    };
    $scope.openMatter = function(id, type) {
        location.replace('/rest/mi/matter?mpid=' + LS.p.mpid + '&id=' + id + '&type=' + type);
    };
    $scope.gotoLottery = function(event, lottery, ek) {
        event.preventDefault();
        event.stopPropagation();
        location.replace('/rest/app/lottery?mpid=' + LS.p.mpid + '&lottery=' + lottery + '&enrollKey=' + ek);
    };
    $scope.followMp = function(event, page) {
        if (/YiXin/i.test(navigator.userAgent)) {
            location.href = 'yixin://opencard?pid=' + $scope.mpa.yx_cardid;
        } else if (page !== undefined && page.length) {
            $scope.gotoPage(event, page);
        } else {
            alert('请在易信中打开页面');
        }
    };
    $scope.onReady = function(task) {
        if ($scope.params) {
            PG.exec(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    $http.get(LS.j('get', 'mpid', 'aid', 'rid', 'page', 'ek', 'newRecord')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        $scope.params = params;
        $scope.mpa = params.mpaccount;
        $scope.App = params.app;
        $scope.User = params.user;
        if (params.app.multi_rounds === 'Y') {
            $scope.ActiveRound = params.activeRound;
        }
        setShareData($scope, params, $http);
        setPage($scope, params.page);
        if (tasksOfOnReady.length) {
            angular.forEach(tasksOfOnReady, PG.exec);
        }
        $timeout(function() {
            $scope.$broadcast('xxt.app.enroll.ready', params);
        });
    }).error(function(content, httpCode) {
        if (httpCode === 401) {
            var el = document.createElement('iframe');
            el.setAttribute('id', 'frmPopup');
            el.onload = function() {
                this.height = document.querySelector('body').clientHeight;
            };
            document.body.appendChild(el);
            if (content.indexOf('http') === 0) {
                window.onAuthSuccess = function() {
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
            $scope.errmsg = content;
        }
    });
}]);