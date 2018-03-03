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
require('../../../../../../asset/js/xxt.ui.notice.js');
require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.page.js');
require('../../../../../../asset/js/xxt.ui.siteuser.js');
require('../../../../../../asset/js/xxt.ui.favor.js');
require('../../../../../../asset/js/xxt.ui.coinpay.js');
require('../../../../../../asset/js/xxt.ui.url.js');

require('./directive.js');

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'notice.ui.xxt', 'http.ui.xxt', 'page.ui.xxt', 'snsshare.ui.xxt', 'siteuser.ui.xxt', 'favor.ui.xxt', 'url.ui.xxt', 'directive.enroll']);
ngApp.config(['$controllerProvider', '$uibTooltipProvider', '$locationProvider', function($cp, $uibTooltipProvider, $locationProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({ 'show': 'hide' });
    $locationProvider.html5Mode(true);
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
ngApp.controller('ctrlMain', ['$scope', '$q', 'http2', '$timeout', 'tmsLocation', 'tmsDynaPage', 'tmsSnsShare', 'tmsSiteUser', 'tmsFavor', function($scope, $q, http2, $timeout, LS, tmsDynaPage, tmsSnsShare, tmsSiteUser, tmsFavor) {
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
        if ($scope.app.entryRule && $scope.app.entryRule.scope.sns === 'Y') {
            url = LS.j('askFollow', 'site');
            url += '&sns=' + Object.keys($scope.app.entryRule.sns).join(',');
            openPlugin(url, refreshActionRule);
        }
    };
    $scope.askBecomeMember = function() {
        var url, mschemaIds;
        if ($scope.app.entry_rule && $scope.app.entryRule.scope.member === 'Y') {
            mschemaIds = Object.keys($scope.app.entryRule.member);
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
    $scope.gotoApp = function(event) {
        location.replace($scope.app.entryUrl);
    };
    $scope.gotoPage = function(event, page, ek, rid, newRecord) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var url = LS.j('', 'site', 'app');
        if (ek) {
            url += '&ek=' + ek;
        } else if (page === 'remark') {
            url += '&ek=' + LS.s().ek;
        }
        rid && (url += '&rid=' + rid);
        page && (url += '&page=' + page);
        newRecord && newRecord === 'Y' && (url += '&newRecord=Y');
        if (/remark|repos/.test(page)) {
            location = url;
        } else {
            location.replace(url);
        }
    };
    $scope.openMatter = function(id, type, replace, newWindow) {
        var url = '/rest/site/fe/matter?site=' + LS.s().site + '&id=' + id + '&type=' + type;
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
    /* 设置公众号分享信息 */
    $scope.setSnsShare = function(oRecord, oParams) {
        var oApp, oPage, oUser, sharelink, shareid, shareby, summary;
        oApp = $scope.app;
        oPage = $scope.page;
        oUser = $scope.user;
        /* 设置活动的当前链接 */
        sharelink = 'http://' + location.host + LS.j('', 'site', 'app', 'rid');
        if (oPage && oPage.share_page && oPage.share_page === 'Y') {
            sharelink += '&page=' + oPage.name;
            //if (!(/iphone|ipad/i.test(navigator.userAgent))) {
            /*ios下操作无效，且导致微信jssdk失败*/
            // if (window.history && window.history.replaceState) {
            //     window.history.replaceState({}, oApp.title, sharelink);
            // }
            //}
        } else if (LS.s().page) {
            sharelink += '&page=' + LS.s().page;
        }
        oRecord && oRecord.enroll_key && (sharelink += '&ek=' + oRecord.enroll_key);
        if (oParams) {
            angular.forEach(oParams, function(v, k) {
                if (v !== undefined) {
                    sharelink += '&' + k + '=' + v;
                }
            });
        }
        shareid = oUser.uid + '_' + (new Date * 1);
        sharelink += "&shareby=" + shareid;
        /* 设置分享 */
        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            summary = oApp.summary;
            if (oPage && oPage.share_summary && oPage.share_summary.length && oRecord) {
                summary = oRecord.data[oPage.share_summary];
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
                    http2.get(url);
                    window.shareCounter++;
                    window.onshare && window.onshare(window.shareCounter);
                },
                jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage', 'getLocation']
            });
            tmsSnsShare.set(oApp.title, sharelink, summary, oApp.pic);
        }
    };
    $scope.isSmallLayout = false;
    if (window.screen && window.screen.width < 992) {
        $scope.isSmallLayout = true;
    }
    http2.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).then(function success(rsp) {
        var params = rsp.data,
            oSite = params.site,
            oApp = params.app,
            oMission = params.mission,
            oPage = params.page,
            oUser = params.user,
            schemasById = {},
            tagsById = {},
            activeRid = '';

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
        if (oApp.multi_rounds === 'Y' && params.activeRound) {
            $scope.activeRound = params.activeRound;
            activeRid = params.activeRound.rid;
        }
        // if (params.record) {
        //     if (params.record.data_tag) {
        //         for (var schemaId in params.record.data_tag) {
        //             var dataTags = params.record.data_tag[schemaId],
        //                 converted = [];
        //             dataTags.forEach(function(tagId) {
        //                 tagsById[tagId] && converted.push(tagsById[tagId]);
        //             });
        //             params.record.data_tag[schemaId] = converted;
        //         }
        //     }
        // }
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
        if (tasksOfOnReady.length) {
            angular.forEach(tasksOfOnReady, execTask);
        }
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
        $timeout(function() {
            $scope.$broadcast('xxt.app.enroll.ready', params);
        });
        http2.post('/rest/site/fe/matter/logAccess?site=' + oApp.siteid + '&id=' + oApp.id + '&type=enroll&title=' + oApp.title, {
            search: location.search.replace('?', ''),
            referer: document.referrer,
            rid: activeRid,
            assignedNickname: oUser.nickname
        });
    });
}]);
module.exports = ngApp;