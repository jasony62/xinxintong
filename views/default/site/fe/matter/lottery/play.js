if (/MicroMessenger/.test(navigator.userAgent)) {
    if (window.signPackage) {
        //signPackage.debug = true;
        signPackage.jsApiList = ['onMenuShareTimeline', 'onMenuShareAppMessage'];
        wx.config(signPackage);
    }
}
lotApp = angular.module('app', ["ngSanitize"]).
config(['$controllerProvider', function($cp) {
    lotApp.provider = {
        controller: $cp.register
    };
}]).
directive('dynamicHtml', function($compile) {
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
}).
controller('lotCtrl', ['$scope', '$http', '$timeout', function($scope, $http, $timeout) {
    var ls, siteId, appId, ek;
    ls = location.search;
    siteId = ls.match(/site=([^&]*)/)[1];
    appId = ls.match(/app=([^&]*)/)[1];
    ek = ls.match(/enrollKey=([^&]*)/) ? ls.match(/enrollKey=([^&]*)/)[1] : '';
    $scope.alert = {
        type: '',
        msg: '',
        empty: function() {
            this.type = '';
            this.msg = '';
        },
        error: function(msg) {
            this.type = 'error';
            this.msg = msg;
        },
        nonfan: function(msg) {
            this.type = 'nonfan';
            this.msg = msg;
        },
        nochance: function(msg) {
            this.type = 'nochance';
            this.msg = msg;
        },
        pretask: function(msg) {
            this.type = 'pretask';
            this.msg = msg;
        },
    };
    var openAskFollow = function() {
        $http.get('/rest/site/fe/matter/lottery/askFollow?site=' + siteId).error(function(content) {
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
            el.setAttribute('src', '/rest/site/fe/matter/lottery/askFollow?site=' + siteId);
            el.style.display = 'block';
        });
    };
    var setShare = function(app, user) {
        var sharelink;
        sharelink = 'http://' + location.hostname + "/rest/site/fe/matter/lottery";
        sharelink += "?site=" + siteId;
        sharelink += "&app=" + appId;
        window.shareid = user.uid + (new Date()).getTime();
        sharelink += "&shareby=" + window.shareid;
        window.xxt.share.set(app.title, sharelink, app.summary, app.pic);
        window.xxt.share.options.logger = function(shareto) {
            var url;
            url = "/rest/site/fe/matter/logShare";
            url += "?shareid=" + window.shareid;
            url += "&site=" + siteId;
            url += "&id=" + app.id;
            url += "&type=lottery";
            url += "&title=" + app.title;
            url += "&shareby=" + window.shareid;
            url += "&shareto=" + shareto;
            $http.get(url).success(function() {
                $scope.$broadcast('xxt.app.lottery.share', shareto);
            });
        };
    };
    $scope.awards = {};
    $scope.greeting = null;
    $http.get('/rest/site/fe/matter/lottery/get?site=' + siteId + '&app=' + appId).success(function(rsp) {
        var params = rsp.data,
            lot = params.app,
            i, l, award,
            awards, sharelink;
        /*if (lot.fans_enter_only === 'Y' && params.user.openid.length === 0) {
            openAskFollow();
            return;
        }*/
        $scope.lot = lot;
        awards = lot.awards;
        for (i = 0, l = awards.length; i < l; i++) {
            award = awards[i];
            $scope.awards[award.aid] = award;
        }
        if (lot.show_winners === 'Y') {
            $http.get('/rest/site/fe/matter/lottery/winnersList?app=' + appId).success(function(rsp) {
                $scope.winners = rsp.data;
            });
        }
        $scope.logs = params.logs || [];
        $scope.leftChance = params.leftChance;
        if (params.page && params.page.js && params.page.js.length) {
            (function dynamicjs() {
                eval(params.page.js);
                $timeout(function() {
                    $scope.$broadcast('xxt.app.lottery.ready', params);
                }, 0);
            })();
        }
        $scope.params = params;
        setShare($scope.lot, params.user);
        //if (lot.pretask === 'Y' && lot._pretaskstate !== 'done') {
        //    $scope.alert.pretask(lot.pretaskdesc);
        //    return;
        //}
    });
    var playAfter = function(result) {
        var log;
        log = result.log;
        $scope.leftChance = result.leftChance;
        $scope.logs.splice(0, 0, log);
        if ($scope.lot.show_greeting === 'Y') {
            if (log.award_greeting && log.award_greeting.length) {
                $scope.showGreeting(log);
            }
        }
    };
    $scope.play = function(cbSuccess, cbFailure) {
        var url;
        $scope.alert.empty();
        url = '/rest/site/fe/matter/lottery/play?site=' + siteId + '&app=' + appId;
        if (ek && ek.length) {
            url += '&enrollKey=' + ek;
        }
        $http.get(url).success(function(rsp) {
            if (angular.isString(rsp)) {
                $scope.alert.error(rsp);
                cbFailure && cbFailure(rsp);
                return;
            }
            if (rsp.err_code === 302) {
                $scope.alert.nonfan(rsp.err_msg);
                cbFailure && cbFailure(rsp);
                return;
            }
            if (rsp.err_code === 301) {
                $scope.alert.nochance(rsp.err_msg);
                cbFailure && cbFailure(rsp);
                return;
            }
            if (rsp.err_code !== 0) {
                $scope.alert.error(rsp.err_msg);
                cbFailure && cbFailure(rsp);
                return;
            }
            if (cbSuccess) {
                if (true === cbSuccess(rsp.data, function() {
                        playAfter(rsp.data);
                    })) {
                    playAfter(rsp.data);
                }
            } else {
                playAfter(rsp.data);
            }
        }).error(function(content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmPopup');
                el.onload = function() {
                    this.height = document.documentElement.clientHeight;
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
                alert(content);
            }
        });
    };
    $scope.clickAlert = function(event) {
        event.preventDefault();
        event.stopPropagation();
        if ($scope.alert.type !== 'pretask') {
            $scope.alert.empty();
        }
    };
    $scope.validAward = function(award) {
        return award.type != 0 && award.type != 3;
    };
    $scope.canPrize = function(log) {
        var award;
        if (!log) return false;
        award = $scope.awards[log.aid];
        return (!!award.get_prize_url && award.get_prize_url.length);
    };
    $scope.prize = function(log) {
        var award, referrer;
        if (log.prize_url && log.prize_url.length) {
            location.replace(log.prize_url);
        } else {
            award = $scope.awards[log.aid];
            if (!award.get_prize_url) return false;
            referrer = '/rest/site/fe/matter/lottery/log/get?id=' + log.id;
            $http.post(award.get_prize_url, {
                referrer: referrer
            }).success(function(rsp) {
                var prizeUrl;
                prizeUrl = {
                    logid: log.id,
                    url: rsp.data.url
                }
                $http.post('/rest/site/fe/matter/lottery/prize?site=' + siteId, prizeUrl).success(function() {
                    location.replace(rsp.data.url);
                })
            }).error(function(content, httpCode) {
                if (httpCode === 401) {
                    var el = document.createElement('iframe');
                    el.setAttribute('id', 'frmPopup');
                    el.onload = function() {
                        this.height = document.documentElement.clientHeight;
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
                    alert(content);
                }
            });
        }
    };
    $scope.showGreeting = function(log) {
        $scope.greeting = log;
    };
    $scope.hideGreeting = function() {
        $scope.greeting = null;
    };
    $scope.debugReset = function() {
        var c, expdate = new Date();
        expdate.setTime(expdate.getTime() - (86400 * 1000 * 1));
        c = 'xxt_' + lid + '_pretask' + "=; expires=" + expdate.toGMTString() + "; path=/";
        document.cookie = c;
        alert('clean');
    };
}]);