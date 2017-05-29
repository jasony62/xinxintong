'use strict';
if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage && window.wx) {
    window.wx.ready(function() {
        window.wx.showOptionMenu();
    });
} else if (/YiXin/i.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        YixinJSBridge.call('showOptionMenu');
    }, false);
}

require('./directive.css');

require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.page.js');
require('../../../../../../asset/js/xxt.ui.siteuser.js');
require('../../../../../../asset/js/xxt.ui.favor.js');
require('../../../../../../asset/js/xxt.ui.coinpay.js');
require('../../../../../../asset/js/xxt.ui.share.js');

require('./directive.js');

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'http.ui.xxt', 'page.ui.xxt', 'snsshare.ui.xxt', 'directive.enroll', 'siteuser.ui.xxt', 'favor.ui.xxt']);
ngApp.provider('ls', function() {
    var _baseUrl = '/rest/site/fe/matter/enroll',
        _params = {};

    this.params = function(params) {
        var ls;
        ls = location.search;
        angular.forEach(params, function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            _params[q] = match ? match[1] : '';
        });
        return _params;
    };

    this.$get = function() {
        return {
            p: _params,
            j: function(method) {
                var i = 1,
                    l = arguments.length,
                    url = _baseUrl,
                    _this = this,
                    search = [];
                method && method.length && (url += '/' + method);
                for (; i < l; i++) {
                    search.push(arguments[i] + '=' + _params[arguments[i]]);
                };
                search.length && (url += '?' + search.join('&'));
                return url;
            }
        };
    };
});
ngApp.config(['$controllerProvider', '$uibTooltipProvider', 'lsProvider', function($cp, $uibTooltipProvider, lsProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({ 'show': 'hide' });
    lsProvider.params(['site', 'app', 'rid', 'page', 'ek', 'preview', 'newRecord', 'ignoretime']);
}]);
ngApp.controller('ctrlAppTip', ['$scope', '$interval', function($scope, $interval) {
    var timer;
    $scope.autoCloseTime = 6;
    $scope.domId = '';
    $scope.closeTip = function() {
        var domTip = document.querySelector($scope.domId);
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent("hide", false, false);
        domTip.dispatchEvent(evt);
    };
    timer = $interval(function() {
        $scope.autoCloseTime--;
        if ($scope.autoCloseTime === 0) {
            $interval.cancel(timer);
            $scope.closeTip();
        }
    }, 1000);
}]);
ngApp.controller('ctrlMain', ['$scope', '$http', '$timeout', 'ls', 'tmsDynaPage', 'tmsSnsShare', 'tmsSiteUser', 'tmsFavor', function($scope, $http, $timeout, LS, tmsDynaPage, tmsSnsShare, tmsSiteUser, tmsFavor) {
    var tasksOfOnReady = [];
    $scope.errmsg = '';
    $scope.closePreviewTip = function() {
        $scope.preview = 'N';
    };
    var openAskFollow = function() {
        $http.get(LS.j('askFollow', 'site')).error(function(content) {
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
            el.setAttribute('src', LS.j('askFollow', 'site'));
            el.style.display = 'block';
        });
    };
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
            }
        };
    })();
    $scope.back = function() {
        history.back();
    };
    $scope.closeWindow = function() {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            window.wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            window.YixinJSBridge.call('closeWebView');
        }
    };
    $scope.addRecord = function(event, page) {
        if (page) {
            $scope.gotoPage(event, page, null, null, false, 'Y');
        } else {
            for (var i in $scope.app.pages) {
                var oPage = $scope.app.pages[i];
                if (oPage.type === 'I') {
                    $scope.gotoPage(event, oPage.name, null, null, false, 'Y');
                    break;
                }
            }
        }
    };
    $scope.gotoPage = function(event, page, ek, rid, fansOnly, newRecord) {
        event.preventDefault();
        event.stopPropagation();
        if (fansOnly && !$scope.User.fan) {
            openAskFollow();
            return;
        }
        var url = LS.j('', 'site', 'app');
        if (ek !== undefined && ek !== null && ek.length) {
            url += '&ek=' + ek;
        }
        rid !== undefined && rid !== null && rid.length && (url += '&rid=' + rid);
        page !== undefined && page !== null && page.length && (url += '&page=' + page);
        newRecord !== undefined && newRecord === 'Y' && (url += '&newRecord=Y');
        if (/remark|repos/.test(page)) {
            location = url;
        } else {
            location.replace(url);
        }
    };
    $scope.openMatter = function(id, type, replace, newWindow) {
        var url = '/rest/site/fe/matter?site=' + LS.p.site + '&id=' + id + '&type=' + type;
        if (replace) {
            location.replace(url);
        } else {
            if (newWindow === false) {
                location.href = url;
            } else {
                window.open(url);
            }
        }
    };
    $scope.onReady = function(task) {
        if ($scope.params) {
            PG.exec(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    $http.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params = rsp.data,
            oSite = params.site,
            oApp = params.app,
            oMission = params.mission,
            oPage = params.page,
            oUser = params.user,
            schemasById = {};

        oApp.dataSchemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        oApp._schemasById = schemasById;
        $scope.params = params;
        $scope.site = oSite;
        $scope.mission = oMission;
        $scope.app = oApp;
        $scope.user = oUser;
        if (oApp.multi_rounds === 'Y') {
            $scope.activeRound = params.activeRound;
        }
        /* 设置分享 */
        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            var shareid, sharelink, shareby, summary;

            shareid = oUser.uid + '_' + (new Date() * 1);
            sharelink = 'http://' + location.host + LS.j('', 'site', 'app');
            if (oPage && oPage.share_page && oPage.share_page === 'Y') {
                sharelink += '&page=' + oPage.name;
                params.enrollKey && (sharelink += '&ek=' + params.enrollKey);
            }
            sharelink += "&shareby=" + shareid;

            summary = oApp.summary;
            if (oPage && oPage.share_summary && oPage.share_summary.length && params.record) {
                summary = params.record.data[oPage.share_summary];
            }
            /* 分享次数计数器 */
            window.shareCounter = 0;
            tmsSnsShare.config({
                siteId: oApp.siteid,
                logger: function(shareto) {
                    var url;
                    url = "/rest/site/fe/matter/logShare";
                    url += "?shareid=" + shareid;
                    url += "&site=" + oApp.siteid;
                    url += "&id=" + oApp.id;
                    url += "&type=enroll";
                    url += "&title=" + oApp.title;
                    url += "&shareby=" + shareid;
                    url += "&shareto=" + shareto;
                    $http.get(url);
                    window.shareCounter++;
                    if (oApp.can_autoenroll === 'Y' && oPage.autoenroll_onshare === 'Y') {
                        $http.get(LS.j('emptyGet', 'site', 'app') + '&once=Y');
                    }
                    window.onshare && window.onshare(window.shareCounter);
                },
                jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage', 'getLocation']
            });
            tmsSnsShare.set(oApp.title, sharelink, summary, oApp.pic);
        }

        if (oApp.use_site_header === 'Y' && oSite && oSite.header_page) {
            tmsDynaPage.loadCode(ngApp, oSite.header_page);
        }
        if (oApp.use_mission_header === 'Y' && oMission && oMission.header_page) {
            tmsDynaPage.loadCode(ngApp, oMission.header_page);
        }
        if (oApp.use_mission_footer === 'Y' && oMission && oMission.footer_page) {
            tmsDynaPage.loadCode(ngApp, oMission.footer_page);
        }
        if (oApp.use_site_footer === 'Y' && oSite && oSite.footer_page) {
            tmsDynaPage.loadCode(ngApp, oSite.footer_page);
        }
        if (params.page) {
            tmsDynaPage.loadCode(ngApp, params.page).then(function() {
                $scope.page = params.page;
            });
        }
        if (tasksOfOnReady.length) {
            angular.forEach(tasksOfOnReady, PG.exec);
        }
        tmsFavor.showSwitch($scope.user, oApp);
        if (oApp.can_siteuser === 'Y') {
            tmsSiteUser.showSwitch(oApp.siteid, true);
        }
        $timeout(function() {
            $scope.$broadcast('xxt.app.enroll.ready', params);
        });
        //
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
        //
        $http.post('/rest/site/fe/matter/logAccess?site=' + oApp.siteid + '&id=' + oApp.id + '&type=enroll&title=' + oApp.title + '&shareby=', {
            search: location.search.replace('?', ''),
            referer: document.referrer
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
module.exports = ngApp;
