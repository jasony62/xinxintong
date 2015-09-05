lotApp = angular.module('app', ["ngSanitize"]).
config(['$controllerProvider', function($cp) {
    lotApp.register = {
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
    var mpid, lid;
    mpid = location.search.match(/mpid=([^&]*)/)[1];
    lid = location.search.match(/lid=([^&]*)/)[1];
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
    };
    $scope.awards = {};
    $scope.greeting = null;
    $http.get('/rest/app/lottery/get?mpid=' + mpid + '&lid=' + lid).success(function(rsp) {
        var i, l, award, params, awards, lot, page;
        params = rsp.data;
        awards = params.lottery.awards;
        for (i = 0, l = awards.length; i < l; i++) {
            award = awards[i];
            $scope.awards[award.aid] = award;
        }
        if (params.lottery.show_winners === 'Y') {
            $http.get('/rest/app/lottery/winnersList?lid=' + lid).success(function(rsp) {
                $scope.winners = rsp.data;
            });
        }
        $scope.lot = params.lottery;
        $scope.logs = params.logs || [];
        $scope.leftChance = params.leftChance;
        if (params.page && params.page.js && params.page.js.length) {
            (function dynamicjs() {
                eval(params.page.js);
            })();
        }
        $scope.params = params;
        $timeout(function() {
            $scope.$broadcast('xxt.app.lottery.ready', params);
        }, 0);
    });
    var playAfter = function(result) {
        var log;
        log = result.log;
        $scope.leftChance = result.leftChance;
        $scope.logs.push(log);
        if ($scope.lot.show_greeting === 'Y') {
            if (log.award_greeting && log.award_greeting.length)
                $scope.showGreeting(log);
        }
    };
    $scope.play = function(cbSuccess, cbError) {
        $scope.alert.empty();
        $http.get('/rest/app/lottery/play?mpid=' + mpid + '&lid=' + lid).success(function(rsp) {
            if (angular.isString(rsp)) {
                $scope.alert.error(rsp);
                return;
            }
            if (rsp.err_code === 302) {
                $scope.alert.nonfan(rsp.err_msg);
                return;
            }
            if (rsp.err_code === 301) {
                $scope.alert.nochance(rsp.err_msg);
                return;
            }
            if (rsp.err_code !== 0) {
                $scope.alert.error(rsp.err_msg);
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
                el.setAttribute('id', 'frmAuth');
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
        $scope.alert.empty();
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
        var award;
        if (log.prize_url && log.prize_url.length) {
            location.href = log.prize_url;
        } else {
            award = $scope.awards[log.aid];
            if (!award.get_prize_url) return false;
            $http.get(award.get_prize_url).success(function(rsp) {
                var prizeUrl;
                prizeUrl = {
                    mpid: mpid,
                    lid: lid,
                    draw_at: log.draw_at,
                    url: rsp.data.url
                }
                $http.post('/rest/app/lottery/prize', prizeUrl).success(function() {
                    location.href = rsp.data.url;
                })
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
        c = 'xxt_' + lid + '_precondition' + "=; expires=" + expdate.toGMTString() + "; path=/";
        document.cookie = c;
        alert('clean');
    };
}]);