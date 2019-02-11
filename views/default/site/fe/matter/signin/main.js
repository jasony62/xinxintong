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

require('../../../../../../asset/js/xxt.ui.notice.js');
require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.page.js');
require('../../../../../../asset/js/xxt.ui.image.js');
require('../../../../../../asset/js/xxt.ui.geo.js');

require('./directive.js');

var setShareData = function(scope, params) {
    if (!window.xxt || !window.xxt.share) {
        return false;
    }
    var sharelink, summary;
    sharelink = location.protocol + '//' + location.host + LS.j('', 'site', 'app');
    if (params.page.share_page && params.page.share_page === 'Y') {
        sharelink += '&page=' + params.page.name;
        sharelink += '&ek=' + params.enrollKey;
    }
    //window.shareid = params.user.vid + (new Date()).getTime();
    //sharelink += "&shareby=" + window.shareid;
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
    window.xxt.share.options.logger = function(shareto) {};
};

/* 公共加载的模块 */
var angularModules = ['ngSanitize', 'notice.ui.xxt', 'http.ui.xxt', 'page.ui.xxt', 'directive.signin', 'snsshare.ui.xxt'];
/* 加载指定的模块 */
if (window.moduleAngularModules) {
    window.moduleAngularModules.forEach(function(m) {
        angularModules.push(m);
    });
}

var ngApp = angular.module('app', angularModules);
ngApp.config(['$controllerProvider', '$locationProvider', function($cp, $locationProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlMain', ['$scope', '$timeout', 'http2', 'tmsLocation', 'tmsDynaPage', function($scope, $timeout, http2, LS, tmsDynaPage) {
    function fnHidePageActions() {
        var domActs, domAct;
        if (domActs = document.querySelectorAll('[wrap=button]')) {
            angular.forEach(domActs, function(domAct) {
                domAct.style.display = 'none';
            });
        }
    }

    function openAskFollow() {
        http2.get(LS.j('askFollow', 'site')).then(function() {}, function(content) {
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
        var url = LS.j('', 'site', 'app');
        if (ek !== undefined && ek !== null && ek.length) {
            url += '&ek=' + ek;
        }
        rid !== undefined && rid !== null && rid.length && (url += '&rid=' + rid);
        page !== undefined && page !== null && page.length && (url += '&page=' + page);
        newRecord !== undefined && newRecord === 'Y' && (url += '&newRecord=Y');
        location.replace(url);
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
            execTask(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    http2.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).then(function(rsp) {
        var params = rsp.data,
            oSite = params.site,
            oApp = params.app,
            oMission = params.mission,
            schemasById = {};

        oApp.dataSchemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        oApp._schemasById = schemasById;
        $scope.params = params;
        $scope.site = oSite;
        $scope.mission = oMission;
        $scope.app = oApp;
        $scope.user = params.user;
        $scope.activeRound = params.activeRound;
        setShareData($scope, params);
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
        $timeout(function() {
            fnHidePageActions();
            $scope.$broadcast('xxt.app.signin.ready', params);
        });
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
    });
}]);
module.exports = ngApp;