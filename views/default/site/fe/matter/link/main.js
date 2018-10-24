'use strict';
require('../../../../../../asset/js/xxt.ui.favor.js');
require('../../../../../../asset/js/xxt.ui.share.js');

angular.module('app', ['ui.bootstrap', 'page.ui.xxt', 'favor.ui.xxt', 'snsshare.ui.xxt']).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', 'tmsFavor', 'tmsDynaPage', 'tmsSnsShare', function($scope, $location, $http, tmsFavor, tmsDynaPage, tmsSnsShare) {
    var siteId, linkId, invite_token, shareby;
    siteId = $location.search().site;
    linkId = $location.search().id;
    invite_token = $location.search().inviteToken;
    shareby = $location.search().shareby ? $location.search().shareby : '';
    $scope.isFull = false;
    $scope.isIE = window.ActiveXObject || "ActiveXObject" in window ? true : false;
    $scope.elSiteCard = angular.element(document.querySelector('#site-card'));
    var runPrefixMethod = function(element, method) {
        var usablePrefixMethod;
        ["webkit", "moz", "ms", "o", ""].forEach(function(prefix) {
            if (usablePrefixMethod) return;
            if (prefix === "") {
                // 无前缀，方法首字母小写
                method = method.slice(0, 1).toLowerCase() + method.slice(1);
            }
            var typePrefixMethod = typeof element[prefix + method];
            if (typePrefixMethod + "" !== "undefined") {
                if (typePrefixMethod === "function") {
                    usablePrefixMethod = element[prefix + method]();
                } else {
                    usablePrefixMethod = element[prefix + method];
                }
            }
        });
        return usablePrefixMethod;
    };
    if (typeof window.screenX === "number") {
        var eleFull = document.querySelector(".iframeWrap"),
            userAgent = navigator.userAgent;
        eleFull.addEventListener("click", function(event) {
            if (userAgent.indexOf('iPhone') > -1) {
                if(!$scope.isFull) {
                    document.querySelector('.col-md-3 ').style.display = 'none';
                    document.querySelector('.invite').style.display = 'none';
                    document.querySelector("button").innerText = "退出";
                    $scope.isFull = true;
                }else {
                    document.querySelector('.col-md-3 ').style.display = 'block';
                    document.querySelector('.invite').style.display = 'block';
                    document.querySelector("button").innerText = "全屏";
                    $scope.isFull = false;
                }
            } else {
                if (runPrefixMethod(document, "FullScreen") || runPrefixMethod(document, "IsFullScreen")) {
                    runPrefixMethod(document, "CancelFullScreen");
                    document.querySelector("button").innerText = "全屏";
                } else {
                    runPrefixMethod(this, "RequestFullScreen")
                    document.querySelector("button").innerText = "退出";
                }
            }
        });
    } else {
        alert("版本太低，请切换高版本浏览器");
    }
    var setShare = function() {
        var shareid, sharelink;
        shareid = $scope.user.uid + '_' + (new Date() * 1);
        tmsSnsShare.config({
            siteId: siteId,
            logger: function(shareto) {
                var url = "/rest/site/fe/matter/logShare";
                url += "?shareid=" + shareid;
                url += "&site=" + siteId;
                url += "&id=" + linkId;
                url += "&type=link";
                url += "&title=" + $scope.link.title;
                url += "&shareto=" + shareto;
                url += "&shareby=" + shareby;
                $http.get(url);
            },
            jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage']
        });
        if ($scope.link.invite) {
            sharelink = location.protocol + '//' + location.host + '/i/' + $scope.link.invite.code;
        } else {
            sharelink = location.href;
            if (/shareby=/.test(sharelink)) {
                sharelink = sharelink.replace(/shareby=[^&]*/, 'shareby=' + shareid);
            } else {
                sharelink += "&shareby=" + shareid;
            }
        }
        tmsSnsShare.set($scope.link.title, sharelink, $scope.link.summary, $scope.link.pic);
    }
    $scope.siteCardToggled = function(open) {
        var elDropdownMenu;
        if (open) {
            if (elDropdownMenu = document.querySelector('#site-card>.dropdown-menu')) {
                elDropdownMenu.style.left = 'auto';
                elDropdownMenu.style.right = 0;
            }
        }
    };
    $scope.favor = function(user, link) {
        if (!user.loginExpire) {
            tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                user.loginExpire = data.loginExpire;
                tmsFavor.open(link);
            });
        } else {
            tmsFavor.open(link);
        }
    };
    $scope.invite = function(user, link) {
        if (!user.loginExpire) {
            tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                user.loginExpire = data.loginExpire;
                location.href = "/rest/site/fe/invite?matter=link," + link.id + '&inviteToken=' + invite_token;
            });
        } else {
            location.href = "/rest/site/fe/invite?matter=link," + link.id + '&inviteToken=' + invite_token;
        }
    };
    $scope.siteUser = function(siteId) {
        var url = location.protocol + '//' + location.host;
        url += '/rest/site/fe/user';
        url += "?site=" + siteId;
        location.href = url;
    };
    $scope.gotoNavApp = function(oNavApp) {
        switch (oNavApp.type) {
            case 'enroll':
                location.href = '/rest/site/fe/matter/enroll?site=' + $scope.link.siteid + '&app=' + oNavApp.id;
                break;
            case 'article':
            case 'channel':
                location.href = '/rest/site/fe/matter?site=' + $scope.link.siteid + '&id=' + oNavApp.id + '&type=' + oNavApp.type;
                break;
            case 'link':
                location.href = '/rest/site/fe/matter/link?site=' + $scope.link.siteid + '&id=' + oNavApp.id + '&type=' + oNavApp.type;
                break;
            default:
                alert("不支持此类型");
                break;
        }
    };
    $http.get('/rest/site/home/get?site=' + siteId).success(function(rsp) {
        $scope.siteInfo = rsp.data;
        $http.get('/rest/site/fe/matter/link/get?site=' + siteId + '&id=' + linkId).success(function(rsp) {
            if (rsp.data) {
                $scope.link = rsp.data.link;
                $scope.user = rsp.data.user;
                $scope.qrcode = '/rest/site/fe/matter/link/qrcode?site=' + siteId + '&url=' + encodeURIComponent(location.href);
                if (Object.keys($scope.link).indexOf('invite') !== -1) {
                    var len = $scope.link.fullUrl.length;
                    if ($scope.link.fullUrl.charAt(len - 1) !== '?') {
                        $scope.link.fullUrl = $scope.link.fullUrl + '&inviteToken=' + invite_token;
                    } else {
                        $scope.link.fullUrl = $scope.link.fullUrl + 'inviteToken=' + invite_token;
                    }
                }
                if (/MicroMessenge|Yixin/i.test(navigator.userAgent)) {
                    setShare();
                }
                document.querySelector('#link>.iframeWrap>iframe').setAttribute('src', $scope.link.fullUrl);
                $http.post('/rest/site/fe/matter/logAccess?site=' + siteId, {
                    search: location.search.replace('?', ''),
                    referer: document.referrer,
                    id: linkId,
                    type: 'link',
                    title: $scope.link.title
                });
            }
        }).error(function(content, httpCode) {});
    });
}]);