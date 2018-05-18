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
/******/ 	return __webpack_require__(__webpack_require__.s = 100);
/******/ })
/************************************************************************/
/******/ ({

/***/ 100:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(42);


/***/ }),

/***/ 4:
/***/ (function(module, exports, __webpack_require__) {

"use strict";
 
 var ngMod = angular.module('snsshare.ui.xxt', []);
 ngMod.service('tmsSnsShare', ['$http', function($http) {
     function setWxShare(title, link, desc, img, options) {
         var _this = this;
         window.wx.onMenuShareTimeline({
             title: options.descAsTitle ? desc : title,
             link: link,
             imgUrl: img,
             success: function() {
                 try {
                     options.logger && options.logger('T');
                 } catch (ex) {
                     alert('share failed:' + ex.message);
                 }
             },
             cancel: function() {},
             fail: function() {
                 alert('shareT: fail');
             }
         });
         window.wx.onMenuShareAppMessage({
             title: title,
             desc: desc,
             link: link,
             imgUrl: img,
             success: function() {
                 try {
                     options.logger && options.logger('F');
                 } catch (ex) {
                     alert('share failed:' + ex.message);
                 }
             },
             cancel: function() {},
             fail: function() {
                 alert('shareF: fail');
             }
         });
     }

     function setYxShare(title, link, desc, img, options) {
         var _this = this,
             shareData = {
                 'img_url': img,
                 'link': link,
                 'title': title,
                 'desc': desc
             };

         window.YixinJSBridge.on('menu:share:appmessage', function(argv) {
             try {
                 options.logger && options.logger('F');
             } catch (ex) {
                 alert('share failed:' + ex.message);
             }
             window.YixinJSBridge.invoke('sendAppMessage', shareData, function(res) {});
         });
         window.YixinJSBridge.on('menu:share:timeline', function(argv) {
             try {
                 options.logger && options.logger('T');
             } catch (ex) {
                 alert('share failed:' + ex.message);
             }
             window.YixinJSBridge.invoke('shareTimeline', shareData, function(res) {});
         });
     }
     var _isReady = false;
     this.config = function(options) {
         this.options = options;
     };
     this.set = function(title, link, desc, img, fnOther) {
         var _this = this;
         // 将图片的相对地址改为绝对地址
         img && img.indexOf(location.protocol) === -1 && (img = location.protocol + '//' + location.host + img);
         if (_isReady) {
             if (/MicroMessenger/i.test(navigator.userAgent)) {
                 setWxShare(title, link, desc, img, _this.options);
             } else if (/Yixin/i.test(navigator.userAgent)) {
                 setYxShare(title, link, desc, img, _this.options);
             } else if (fnOther && typeof fnOther === 'function') {
                 fnOther(title, link, desc, img);
             }
         } else {
             if (/MicroMessenger/i.test(navigator.userAgent)) {
                 var script;
                 script = document.createElement('script');
                 script.src = location.protocol + '//res.wx.qq.com/open/js/jweixin-1.0.0.js';
                 script.onload = function() {
                     var xhr, url;
                     xhr = new XMLHttpRequest();
                     url = "/rest/site/fe/wxjssdksignpackage?site=" + _this.options.siteId + "&url=" + encodeURIComponent(location.href.split('#')[0]);
                     xhr.open('GET', url, true);
                     xhr.onreadystatechange = function() {
                         if (xhr.readyState == 4) {
                             if (xhr.status >= 200 && xhr.status < 400) {
                                 var signPackage;
                                 try {
                                     eval("(" + xhr.responseText + ')');
                                     if (signPackage) {
                                         signPackage.debug = false;
                                         signPackage.jsApiList = _this.options.jsApiList;
                                         wx.config(signPackage);
                                         wx.ready(function() {
                                             setWxShare(title, link, desc, img, _this.options);
                                             _isReady = true;
                                         });
                                         wx.error(function(res) {
                                             alert(JSON.stringify(res));
                                         });
                                     }
                                 } catch (e) {
                                     alert('local error:' + e.toString());
                                 }
                             } else {
                                 alert('http error:' + xhr.statusText);
                             }
                         };
                     }
                     xhr.send();
                 };
                 document.body.appendChild(script);
             } else if (/Yixin/i.test(navigator.userAgent)) {
                 if (window.YixinJSBridge === undefined) {
                     document.addEventListener('YixinJSBridgeReady', function() {
                         setYxShare(title, link, desc, img, _this.options);
                         _isReady = true;
                     }, false);
                 } else {
                     setYxShare(title, link, desc, img, _this.options);
                     _isReady = true;
                 }
             } else if (fnOther && typeof fnOther === 'function') {
                 fnOther(title, link, desc, img);
                 _isReady = true;
             }
         }
     };
 }]);

/***/ }),

/***/ 42:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(4);

var ngApp = angular.module('app', ['ui.bootstrap', 'ui.tms', 'snsshare.ui.xxt']);
ngApp.controller('ctrlMain', ['$scope', 'http2', 'tmsSnsShare', function($scope, http2, tmsSnsShare) {
    $scope.requireLogin = false;
    $scope.data = {};
    $scope.submit = function() {
        http2.post('/rest/i/matterUrl?invite=' + $scope.invite.id, $scope.data, function(rsp) {
            location.href = rsp.data;
        });
    };
    http2.get('/rest/site/fe/user/get', function(rsp) {
        var oUser, unameType;
        $scope.loginUser = oUser = rsp.data;
        if (oUser.unionid && oUser.uname) {
            if (/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9])\d{8}$/.test(oUser.uname)) {
                unameType = 'mobile';
            } else if (/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/.test(oUser.uname)) {
                unameType = 'email';
            }
        }
        http2.get('/rest/site/fe/invite/get' + location.search, function(rsp) {
            var oInvite = rsp.data;
            $scope.invite = oInvite;
            /* 设置分享 */
            if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
                var shareid, sharelink, shareby;
                shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
                shareid = $scope.loginUser.uid + '_' + (new Date() * 1);
                sharelink = location.href + "&shareby=" + shareid;
                tmsSnsShare.config({
                    siteId: oInvite.matter_siteid,
                    jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage']
                });
                tmsSnsShare.set(oInvite.matter_title, sharelink, oInvite.matter_summary, oInvite.matter_pic);
            }
            if (oInvite.entryRule) {
                $scope.entryRule = oInvite.entryRule;
                if (oInvite.entryRule.scope === 'member') {
                    if (oInvite.entryRule.mschemas && oInvite.entryRule.mschemas.length) {
                        $scope.mschema = oInvite.entryRule.mschemas[0];
                        $scope.data.member = { schema_id: $scope.mschema.id };
                        if (unameType === 'mobile' && $scope.mschema.attrs.mobile) {
                            $scope.data.member.mobile = oUser.uname;
                        } else if (unameType === 'email' && $scope.mschema.attrs.email) {
                            $scope.data.member.email = oUser.uname;
                        }
                        $scope.requireLogin = true;
                    }
                }
            }
        });
    });
}]);

/***/ })

/******/ });