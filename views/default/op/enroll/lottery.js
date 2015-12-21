app = angular.module('app', ['ngSanitize']);
app.config(['$controllerProvider', function($cp) {
    app.register = {
        controller: $cp.register
    };
}]);
app.filter("maskmobile", function() {
    return function(mobile) {
        if (mobile && mobile.length > 4) {
            var i, start = Math.round((mobile.length - 4) / 2);
            mobile = mobile.split('');
            for (i = 0; i < 4; i++)
                mobile[start + i] = '*';
            return mobile.join('');
        } else
            return '****';
    }
});
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
        var j, l, url = '/rest/op/enroll/lottery',
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
})(['aid', 'rid']);
app.controller('ctrl', ['$scope', '$http', '$timeout', '$interval', function($scope, $http, $timeout, $interval) {
    var mySwiper, timer, winnerIndex = -1;
    $scope.speed = 50;
    $scope.times = 0;
    $scope.stopping = false;
    $scope.winners = [];
    $scope.currentRound = null;
    var removePlayer = function() {
        $scope.players.splice(winnerIndex - 1, 1);
        mySwiper.removeSlide(winnerIndex - 1);
        mySwiper.updateSlidesSize();
        winnerIndex = -1;
    };
    var activePlayer = function() {
        var ai, player;
        ai = mySwiper.activeIndex;
        ai > $scope.players.length && (ai = 1);
        player = $scope.players[ai - 1];
        return player;
    };
    var setWinner = function() {
        var winner;
        winnerIndex = mySwiper.activeIndex;
        winnerIndex > $scope.players.length && (winnerIndex = 1);
        winner = $scope.players[winnerIndex - 1];
        if (winner) {
            $scope.winners.push(winner);
            $http.post(LS.j('/done', 'aid', 'rid') + '&ek=' + winner.enroll_key, {
                openid: winner.openid,
                nickname: winner.nickname
            });
        }
        $scope.stopping = false;
        $scope.running = false;
        $scope.times++;
        if ($scope.winners.length == $scope.currentRound.times) {
            removePlayer();
            $scope.$broadcast('xxt.app.enroll.lottery.round-finish');
            return;
        }
        if ($scope.currentRound.autoplay === 'Y' && $scope.times < $scope.currentRound.times)
            $scope.start();
    };
    $scope.init = function() {
        mySwiper = new Swiper('.swiper-container', {
            slidesPerView: 1,
            mode: 'horizontal',
            loop: true,
            speed: $scope.speed
        });
    };
    $scope.getUsers = function(callback) {
        $http.get(LS.j('/playersGet', 'aid', 'rid') + '&hasData=N').success(function(rsp) {
            $scope.players = rsp.data[0];
            $scope.winners = rsp.data[1];
            callback && $timeout(callback);
        });
    };
    $scope.matched = function(candidate, target) {
        var ctags, i, j;
        if (!candidate) return false;
        if (!target.tags || target.tags.length === 0) return true;
        ctags = candidate.tags;
        if (!ctags || ctags.length === 0) return false;
        ctags = ctags.split(',');
        for (i = 0, j = ctags.length; i < j; i++) {
            if (target.tags.indexOf(ctags[i]) !== -1) return true;
        }
        return false;
    };
    $scope.start = function() {
        if (winnerIndex !== -1) {
            removePlayer();
        }
        if ($scope.winners.length == $scope.currentRound.times) {
            $scope.$broadcast('xxt.app.enroll.lottery.round-finished');
            return;
        }
        if ($scope.players.length === 0) {
            $scope.$broadcast('xxt.app.enroll.lottery.players-empty');
            return;
        }
        $scope.running = true;
        timer = $interval(function() {
            mySwiper.slideNext();
        }, $scope.speed);
        if ($scope.currentRound.autoplay === 'Y')
            $timeout(function() {
                $scope.stop()
            }, 1000);
    };
    $scope.stop = function() {
        var timer2, step, steps;
        $scope.stopping = true;
        $interval.cancel(timer);
        step = 0;
        steps = Math.round(Math.random() * 10); //随机移动的步数
        timer2 = $interval(function calcWinner() {
            var currentRound, target;
            mySwiper.slideNext();
            if (step === steps) {
                $interval.cancel(timer2);
                currentRound = $scope.currentRound;
                if (currentRound.targets && currentRound.targets.length > 0) {
                    target = currentRound.targets[$scope.times % currentRound.targets.length];
                    if (target.tags && target.tags.length > 0) {
                        /**
                         * 检查规则
                         */
                        var candidate, checked, timer3;
                        candidate = activePlayer();
                        if (!$scope.matched(candidate, target)) {
                            /**
                             * 不匹配，继续找。有可能所有的候选人都不匹配。
                             */
                            checked = []; //已经匹配过的候选人
                            timer3 = $interval(function() {
                                candidate = activePlayer();
                                if ($scope.matched(candidate, target) || checked.length === $scope.players.length) {
                                    /**
                                     * 匹配了，或者所有的候选人都已经检查过。
                                     */
                                    $interval.cancel(timer3);
                                    setWinner();
                                } else {
                                    mySwiper.slideNext();
                                    if (checked.indexOf(mySwiper.activeIndex) === -1)
                                        checked.push(mySwiper.activeIndex);
                                }
                            }, $scope.speed);
                        } else {
                            setWinner();
                        }
                    } else {
                        setWinner();
                    }
                } else {
                    setWinner();
                }
            }
            step++;
        }, $scope.speed);
    };
    $scope.empty = function(fromBegin) {
        $http.get(LS.j('/empty', 'aid')).success(function(rsp) {
            if (fromBegin && fromBegin === 'Y') {
                var url, t;
                t = (new Date()).getTime();
                url = '/rest/op/enroll/lottery?aid=' + LS.p.aid + '&_=' + t;
                location.href = url;
            } else {
                location.reload();
            }
        });
    };
    $http.get(LS.j('/pageGet', 'aid')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params;
        params = rsp.data;
        (function setPage(page) {
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
        })(params.page);
    });
}]);