define(["angular", "xxt-page"], function(angular, codeAssembler) {
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

    var ngApp = angular.module('enroll', ['ngSanitize']);
    ngApp.config(['$controllerProvider', 'lsProvider', function($cp, lsProvider) {
        ngApp.provider = {
            controller: $cp.register
        };
        lsProvider.params(['site', 'app', 'rid', 'page', 'ek', 'preview', 'newRecord', 'ignoretime']);
    }]);
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
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', 'ls', function($scope, $http, $timeout, LS) {
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
        var setShareData = function(scope, params, $http) {
            if (!window.xxt || !window.xxt.share) {
                return false;
            }
            try {
                var sharelink, summary;
                sharelink = 'http://' + location.host + LS.j('', 'site', 'app');
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
                window.xxt.share.options.logger = function(shareto) {
                    /*var app, url;
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
                    window.shareCounter++;*/
                    /* 是否需要自动登记 */
                    /*if (app.can_autoenroll === 'Y' && scope.Page.autoenroll_onshare === 'Y') {
                        $http.get(LS.j('emptyGet', 'mpid', 'aid') + '&once=Y');
                    }
                    window.onshare && window.onshare(window.shareCounter);*/
                };
            } catch (e) {
                alert(e.message);
            }
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
        $http.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            var params = rsp.data,
                site = params.site,
                app = params.app,
                mission = params.mission;
            app.data_schemas = JSON.parse(app.data_schemas);
            $scope.params = params;
            $scope.site = site;
            $scope.mission = mission;
            $scope.app = app;
            $scope.user = params.user;
            if (app.multi_rounds === 'Y') {
                $scope.activeRound = params.activeRound;
            }
            setShareData($scope, params, $http);
            if (app.use_site_header === 'Y' && site && site.header_page) {
                codeAssembler.loadCode(ngApp, site.header_page);
            }
            if (app.use_mission_header === 'Y' && mission && mission.header_page) {
                codeAssembler.loadCode(ngApp, mission.header_page);
            }
            if (app.use_mission_footer === 'Y' && mission && mission.footer_page) {
                codeAssembler.loadCode(ngApp, mission.footer_page);
            }
            if (app.use_site_footer === 'Y' && site && site.footer_page) {
                codeAssembler.loadCode(ngApp, site.footer_page);
            }
            codeAssembler.loadCode(ngApp, params.page).then(function() {
                $scope.appPage = params.page;
            });
            //setPage($scope, params.page);
            if (tasksOfOnReady.length) {
                angular.forEach(tasksOfOnReady, PG.exec);
            }
            $timeout(function() {
                $scope.$broadcast('xxt.app.enroll.ready', params);
            });
            window.loading.finish();
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

    return ngApp;
});