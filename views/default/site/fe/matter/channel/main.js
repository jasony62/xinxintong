'use strict';
require('../../../../../../asset/js/xxt.ui.page.js');
require('../../../../../../asset/js/xxt.ui.share.js');

angular.module('app', ['ui.bootstrap', 'infinite-scroll', 'page.ui.xxt', 'snsshare.ui.xxt']).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', '$q', 'tmsDynaPage', 'tmsSnsShare', function($scope, $location, $http, $q, tmsDynaPage, tmsSnsShare) {
    var siteId, channelId, invite_token, shareby;
    siteId = $location.search().site;
    channelId = $location.search().id;
    invite_token = $location.search().inviteToken;
    shareby = $location.search().shareby ? $location.search().shareby : '';
    /* 设置分享 */
    var setShare = function() {
        var shareid, sharelink;
        shareid = $scope.user.uid + '_' + (new Date() * 1);
        tmsSnsShare.config({
            siteId: siteId,
            logger: function(shareto) {
                var url = "/rest/site/fe/matter/logShare";
                url += "?shareid=" + shareid;
                url += "&site=" + siteId;
                url += "&id=" + channelId;
                url += "&type=channel";
                url += "&title=" + $scope.channel.title;
                url += "&shareto=" + shareto;
                url += "&shareby=" + shareby;
                $http.get(url);
            },
            jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage']
        });
        if($scope.channel.invite) {
            sharelink = location.protocol + '//' + location.host + '/i/'+ $scope.channel.invite.code;
        }else {
            sharelink = location.href;
            if (/shareby=/.test(sharelink)) {
                sharelink = sharelink.replace(/shareby=[^&]*/, 'shareby=' + shareid);
            } else {
                sharelink += "&shareby=" + shareid;
            }
        }
        tmsSnsShare.set($scope.channel.title, sharelink, $scope.channel.summary, $scope.channel.pic);
    }
    function dealImgSrc(item) {
        if (Object.keys(item).indexOf('pic') !== -1 && item.pic == null) {
            item.src = item.pic = '';
        } else if (Object.keys(item).indexOf('thumbnail') !== -1 && item.thumbnail == null) {
            item.src = item.thumnail = '';
        } else {
            item.src = item.pic ? item.pic : item.thumbnail;
        }
        return item;
    };
    $scope.Matter = {
        matters: [],
        busy: false,
        page: 1,
        orderby: 'time',
        changeOrderby: function() {
            this.reset();
        },
        reset: function() {
            this.matters = [];
            this.busy = false;
            this.end = false;
            this.page = 1;
            this.nextPage();
        },
        nextPage: function() {
            var url, _this = this;

            if (this.end) return;

            this.busy = true;
            url = '/rest/site/fe/matter/channel/mattersGet';
            url += '?site=' + siteId;
            url += '&id=' + channelId;
            url += '&orderby=' + this.orderby;
            url += '&page=' + this.page;
            url += '&size=10';
            $http.get(url).success(function(rsp) {
                if (rsp.data.matters.length) {
                    var matters = rsp.data.matters;
                    for (var i = 0, l = matters.length; i < l; i++) {
                        dealImgSrc(matters[i]);
                        _this.matters.push(matters[i]);
                    }
                    _this.page++;
                } else {
                    _this.end = true;
                }
                _this.busy = false;
            });
        }
    };
    $scope.elSiteCard = angular.element(document.querySelector('#site-card'));
    $scope.siteCardToggled = function(open) {
        var elDropdownMenu;
        if (open) {
            if (elDropdownMenu = document.querySelector('#site-card>.dropdown-menu')) {
                elDropdownMenu.style.left = 'auto';
                elDropdownMenu.style.right = 0;
            }
        }
    };
    $scope.open = function(opened) {
        if ($scope.channel.invite) {
            location.href = opened.url + '&inviteToken=' + invite_token;
        } else {
            location.href = opened.url;
        }
    };
    $scope.siteUser = function(siteId) {
        var url = location.protocol + '//' + location.host;
        url += '/rest/site/fe/user';
        url += "?site=" + siteId;
        location.href = url;
    };
    $scope.invite = function(user, channel) {
        if (!user.loginExpire) {
            tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                user.loginExpire = data.loginExpire;
                location.href = "/rest/site/fe/invite?matter=channel," + channel.id + '&inviteToken=' + invite_token;
            });
        } else {
            location.href = "/rest/site/fe/invite?matter=channel," + channel.id + '&inviteToken=' + invite_token;
        }
    };
    var getChannel = function() {
        var deferred = $q.defer();
        $http.get('/rest/site/home/get?site=' + siteId).success(function(rsp) {
            $scope.siteInfo = rsp.data;
        });
        $http.get('/rest/site/fe/matter/channel/get?site=' + siteId + '&id=' + channelId).success(function(rsp) {
            $scope.user = rsp.data.user;
            $scope.channel = rsp.data.channel;
            $scope.qrcode = '/rest/site/fe/matter/channel/qrcode?site=' + siteId + '&url=' + encodeURIComponent(location.href);
            if (/MicroMessenge|Yixin/i.test(navigator.userAgent)) {
                setShare();
            }
            deferred.resolve();
            $http.post('/rest/site/fe/matter/logAccess?site=' + siteId, {
                id: channelId,
                type: 'channel',
                title: $scope.channel.title,
                shareby: shareby,
                search: location.search.replace('?', ''),
                referer: document.referrer
            });
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
                        getChannel();
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
    getChannel();
}]);