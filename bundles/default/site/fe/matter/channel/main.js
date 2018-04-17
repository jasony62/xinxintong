/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 90);
/******/ })
/************************************************************************/
/******/ ({

/***/ 2:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('page.ui.xxt', []);
ngMod.directive('dynamicHtml', ['$compile', function($compile) {
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
}]);
ngMod.service('tmsDynaPage', ['$q', function($q) {
    this.loadCss = function(css) {
        var style, head;
        style = document.createElement('style');
        style.innerHTML = css;
        head = document.querySelector('head');
        head.appendChild(style);
    };
    this.loadExtCss = function(url) {
        var link, head;
        link = document.createElement('link');
        link.href = url;
        link.rel = 'stylesheet';
        head = document.querySelector('head');
        head.appendChild(link);
    };
    this.loadJs = function(ngApp, js) {
        (function(ngApp) {
            eval(js);
        })(ngApp);
    };
    this.loadScript = function(urls) {
        var index, fnLoad, deferred = $q.defer();
        fnLoad = function() {
            var script;
            script = document.createElement('script');
            script.src = urls[index];
            script.onload = function() {
                index++;
                if (index < urls.length) {
                    fnLoad();
                } else {
                    deferred.resolve();
                }
            };
            document.body.appendChild(script);
        };
        if (urls) {
            angular.isString(urls) && (urls = [urls]);
            if (urls.length) {
                index = 0;
                fnLoad();
            }
        }

        return deferred.promise;
    };
    this.loadExtJs = function(ngApp, code) {
        var _self = this,
            deferred = $q.defer(),
            jslength = code.ext_js.length,
            loadScript2;
        loadScript2 = function(js) {
            var script;
            script = document.createElement('script');
            script.src = js.url;
            script.onload = function() {
                jslength--;
                if (jslength === 0) {
                    if (code.js && code.js.length) {
                        _self.loadJs(ngApp, code.js);
                    }
                    deferred.resolve();
                }
            };
            document.body.appendChild(script);
        };
        if (code.ext_js && code.ext_js.length) {
            code.ext_js.forEach(loadScript2);
        }
        return deferred.promise;
    };
    this.loadCode = function(ngApp, code) {
        var _self = this,
            deferred = $q.defer();
        if (code.ext_css && code.ext_css.length) {
            code.ext_css.forEach(function(css) {
                _self.loadExtCss(css.url);
            });
        }
        if (code.css && code.css.length) {
            this.loadCss(code.css);
        }
        if (code.ext_js && code.ext_js.length) {
            _self.loadExtJs(ngApp, code).then(function() {
                deferred.resolve();
            });
        } else {
            if (code.js && code.js.length) {
                _self.loadJs(ngApp, code.js);
            }
            deferred.resolve();
        }
        return deferred.promise;
    };
    this.openPlugin = function(content) {
        var frag, wrap, frm, body, deferred = $q.defer();
        if(document.documentElement.clientWidth > 768) {
            document.documentElement.scrollTop  = 0;
        } else {
            document.body.scrollTop  = 0;
        }
        body = document.getElementsByTagName('body')[0];
        body.style.cssText="overflow-y:hidden";
        frag = document.createDocumentFragment();
        wrap = document.createElement('div');
        wrap.setAttribute('id', 'frmPlugin');
        frm = document.createElement('iframe');
        wrap.appendChild(frm);
        wrap.onclick = function() {
            wrap.parentNode.removeChild(wrap);
            body.style.cssText="overflow-y:auto";
        };
        frag.appendChild(wrap);
        document.body.appendChild(frag);
        if (content.indexOf('http') === 0) {
            window.onClosePlugin = function(result) {
                wrap.parentNode.removeChild(wrap);
                body.style.cssText="overflow-y:auto";
                deferred.resolve(result);
            };
            frm.setAttribute('src', content);
        } else {
            if (frm.contentDocument && frm.contentDocument.body) {
                frm.contentDocument.body.innerHTML = content;
            }
        }
        return deferred.promise;
    };
}]);


/***/ }),

/***/ 35:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(2);

if (/MicroMessenger/.test(navigator.userAgent)) {
    //signPackage.debug = true;
    signPackage.jsApiList = ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage'];
    wx.config(signPackage);
}
angular.module('app', ['ui.bootstrap', 'infinite-scroll', 'page.ui.xxt']).config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]).controller('ctrl', ['$scope', '$location', '$http', '$q', 'tmsDynaPage', function($scope, $location, $http, $q, tmsDynaPage) {
    var siteId, channelId, invite_token, shareby;
    siteId = $location.search().site;
    channelId = $location.search().id;
    invite_token = $location.search().inviteToken;
    shareby = $location.search().shareby ? $location.search().shareby : '';
    var setShare = function() {
        var shareid, sharelink;
        shareid = $scope.user.uid + (new Date()).getTime();
        window.xxt.share.options.logger = function(shareto) {
            var url = "/rest/site/fe/matter/logShare";
            url += "?shareid=" + shareid;
            url += "&site=" + siteId;
            url += "&id=" + channelId;
            url += "&type=channel";
            url += "&title=" + $scope.channel.title;
            url += "&shareto=" + shareto;
            url += "&shareby=" + shareby;
            $http.get(url);
        };
        sharelink = location.href;
        if (/shareby=/.test(sharelink)) {
            sharelink = sharelink.replace(/shareby=[^&]*/, 'shareby=' + shareid);
        } else {
            sharelink += "&shareby=" + shareid;
        }
        window.xxt.share.set($scope.channel.title, sharelink, $scope.channel.summary, $scope.channel.pic, '');
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
    $scope.siteUser = function(id) {
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
            $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + channelId + '&type=channel&title=' + $scope.channel.title + '&shareby=' + shareby, {
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

/***/ }),

/***/ 90:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(35);


/***/ })

/******/ });