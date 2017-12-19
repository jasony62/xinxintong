'use strict';
require('../../../../../../asset/js/xxt.ui.share.js');
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
require('./main.css');

require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.page.js');
require('../../../../../../asset/js/xxt.ui.siteuser.js');
require('../../../../../../asset/js/xxt.ui.favor.js');
require('../../../../../../asset/js/xxt.ui.coinpay.js');

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
ngApp.controller('ctrlMain', ['$scope', '$q', '$http', '$timeout', 'srvUserTask', 'ls', 'tmsDynaPage', 'tmsSnsShare', 'tmsSiteUser', 'tmsFavor', function($scope, $q, $http, $timeout, srvUserTask, LS, tmsDynaPage, tmsSnsShare, tmsSiteUser, tmsFavor) {
    function refreshActionRule() {
        var url, defer;
        defer = $q.defer();
        url = LS.j('actionRule', 'site', 'app');
        $http.get(url).success(function(rsp) {
            $scope.params.actionRule = rsp.data;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function openPlugin(url, fnCallback) {
        var body, elWrap, elIframe;
        body = document.body;
        elWrap = document.createElement('div');
        elWrap.setAttribute('id', 'frmPlugin');
        elWrap.height = body.clientHeight;
        elIframe = document.createElement('iframe');
        elWrap.appendChild(elIframe);
        body.scrollTop = 0;
        body.appendChild(elWrap);
        window.onClosePlugin = function() {
            if (fnCallback) {
                fnCallback().then(function(data) {
                    elWrap.parentNode.removeChild(elWrap);
                });
            } else {
                elWrap.parentNode.removeChild(elWrap);
            }
        };
        elWrap.onclick = function() {
            onClosePlugin();
        };
        if (url) {
            elIframe.setAttribute('src', url);
        }
        elWrap.style.display = 'block';
    }

    function execTask(task) {
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
    var tasksOfOnReady = [];
    // 消息提醒
    $scope.notice = {
        msg: '',
        set: function(msg, type) {
            this.msg = msg;
            this.type = type || 'error'
        }
    };
    $scope.back = function() {
        history.back();
    };
    $scope.historyLen = function() {
        return history.length;
    };
    $scope.closeWindow = function() {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            window.wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            window.YixinJSBridge.call('closeWebView');
        }
    };
    $scope.askFollowSns = function() {
        var url;
        if ($scope.app.entry_rule && $scope.app.entry_rule.scope === 'sns') {
            url = LS.j('askFollow', 'site');
            url += '&sns=' + Object.keys($scope.app.entry_rule.sns).join(',');
            openPlugin(url, refreshActionRule);
        }
    };
    $scope.askBecomeMember = function() {
        var url, mschemaIds;
        if ($scope.app.entry_rule && $scope.app.entry_rule.scope === 'member') {
            mschemaIds = Object.keys($scope.app.entry_rule.member);
            if (mschemaIds.length === 1) {
                url = '/rest/site/fe/user/member?site=' + $scope.app.siteid;
                url += '&schema=' + mschemaIds[0];
            } else if (mschemaIds.length > 1) {
                url = '/rest/site/fe/user/memberschema?site=' + $scope.app.siteid;
                url += '&schema=' + mschemaIds.join(',');
            }
            openPlugin(url, refreshActionRule);
        }
    };
    $scope.addRecord = function(event, page) {
        if (page) {
            $scope.gotoPage(event, page, null, null, 'Y');
        } else {
            for (var i in $scope.app.pages) {
                var oPage = $scope.app.pages[i];
                if (oPage.type === 'I') {
                    $scope.gotoPage(event, oPage.name, null, null, 'Y');
                    break;
                }
            }
        }
    };
    $scope.showUserTask = function() {
        srvUserTask.open($scope.app);
    };
    $scope.gotoPage = function(event, page, ek, rid, newRecord) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var url = LS.j('', 'site', 'app');
        if (ek) {
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
            execTask(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    $scope.save = function() {
        $scope.$broadcast('xxt.app.enroll.save');
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
            schemasById = {},
            tagsById = {},
            assignedNickname = '',
            activeRid = '',
            shareid, sharelink, shareby, summary;

        oApp.dataSchemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        oApp._schemasById = schemasById;
        oApp.dataTags.forEach(function(oTag) {
            tagsById[oTag.id] = oTag;
        });
        oApp._tagsById = tagsById;
        $scope.params = params;
        $scope.site = oSite;
        $scope.mission = oMission;
        $scope.app = oApp;
        $scope.user = oUser;
        if (oApp.multi_rounds === 'Y') {
            $scope.activeRound = params.activeRound;
            activeRid = params.activeRound.rid;
        }
        if (params.record) {
            if (params.record.data_tag) {
                for (var schemaId in params.record.data_tag) {
                    var dataTags = params.record.data_tag[schemaId],
                        converted = [];
                    dataTags.forEach(function(tagId) {
                        tagsById[tagId] && converted.push(tagsById[tagId]);
                    });
                    params.record.data_tag[schemaId] = converted;
                }
            }
            if(oApp.assignedNickname.schema.id=='member.name'|| oApp.assignedNickname.schema.id=='name') {
                assignedNickname = params.record.data[oApp.assignedNickname.schema.id];
            }
        }

        /* 设置活动的当前链接 */
        shareid = oUser.uid + '_' + (new Date() * 1);
        sharelink = 'http://' + location.host + LS.j('', 'site', 'app', 'rid', 'newRecord');
        sharelink += "&shareby=" + shareid;
        if (oPage && oPage.share_page && oPage.share_page === 'Y') {
            sharelink += '&page=' + oPage.name;
            params.record && params.record.enroll_key && (sharelink += '&ek=' + params.record.enroll_key);
            if (!(/iphone|ipad/i.test(navigator.userAgent))) {
                /*ios下操作无效，且导致微信jssdk失败*/
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, oApp.title, sharelink);
                }
            }
        }
        /* 设置分享 */
        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
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
        if (!document.querySelector('.tms-switch-favor')) {
            tmsFavor.showSwitch($scope.user, oApp);
        } else {
            $scope.favor = function(user, article) {
                event.preventDefault();
                event.stopPropagation();

                if (!user.loginExpire) {
                    tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                        user.loginExpire = data.loginExpire;
                        tmsFavor.open(article);
                    });
                } else {
                    tmsFavor.open(article);
                }
            }
        }
        if (oApp.can_siteuser === 'Y') {
            if (!document.querySelector('.tms-switch-siteuser')) {
                tmsSiteUser.showSwitch(oApp.siteid, true);
            } else {
                $scope.siteUser = function(id) {
                    event.preventDefault();
                    event.stopPropagation();

                    var url = 'http://' + location.host;
                    url += '/rest/site/fe/user';
                    url += "?site=" + id;
                    location.href = url;
                }
            }
        }
        $scope.isSmallLayout = false;
        if (window.screen && window.screen.width < 992) {
            $scope.isSmallLayout = true;
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
            referer: document.referrer,
            rid: activeRid,
            assignedNickname: assignedNickname
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