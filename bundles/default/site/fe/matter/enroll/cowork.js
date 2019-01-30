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
/******/ 	return __webpack_require__(__webpack_require__.s = 129);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ (function(module, exports) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function(useSourceMap) {
	var list = [];

	// return the list of modules as css string
	list.toString = function toString() {
		return this.map(function (item) {
			var content = cssWithMappingToString(item, useSourceMap);
			if(item[2]) {
				return "@media " + item[2] + "{" + content + "}";
			} else {
				return content;
			}
		}).join("");
	};

	// import a list of modules into the list
	list.i = function(modules, mediaQuery) {
		if(typeof modules === "string")
			modules = [[null, modules, ""]];
		var alreadyImportedModules = {};
		for(var i = 0; i < this.length; i++) {
			var id = this[i][0];
			if(typeof id === "number")
				alreadyImportedModules[id] = true;
		}
		for(i = 0; i < modules.length; i++) {
			var item = modules[i];
			// skip already imported module
			// this implementation is not 100% perfect for weird media query combinations
			//  when a module is imported multiple times with different media queries.
			//  I hope this will never occur (Hey this way we have smaller bundles)
			if(typeof item[0] !== "number" || !alreadyImportedModules[item[0]]) {
				if(mediaQuery && !item[2]) {
					item[2] = mediaQuery;
				} else if(mediaQuery) {
					item[2] = "(" + item[2] + ") and (" + mediaQuery + ")";
				}
				list.push(item);
			}
		}
	};
	return list;
};

function cssWithMappingToString(item, useSourceMap) {
	var content = item[1] || '';
	var cssMapping = item[3];
	if (!cssMapping) {
		return content;
	}

	if (useSourceMap && typeof btoa === 'function') {
		var sourceMapping = toComment(cssMapping);
		var sourceURLs = cssMapping.sources.map(function (source) {
			return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */'
		});

		return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
	}

	return [content].join('\n');
}

// Adapted from convert-source-map (MIT)
function toComment(sourceMap) {
	// eslint-disable-next-line no-undef
	var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
	var data = 'sourceMappingURL=data:application/json;charset=utf-8;base64,' + base64;

	return '/*# ' + data + ' */';
}


/***/ }),
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/

var stylesInDom = {};

var	memoize = function (fn) {
	var memo;

	return function () {
		if (typeof memo === "undefined") memo = fn.apply(this, arguments);
		return memo;
	};
};

var isOldIE = memoize(function () {
	// Test for IE <= 9 as proposed by Browserhacks
	// @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
	// Tests for existence of standard globals is to allow style-loader
	// to operate correctly into non-standard environments
	// @see https://github.com/webpack-contrib/style-loader/issues/177
	return window && document && document.all && !window.atob;
});

var getElement = (function (fn) {
	var memo = {};

	return function(selector) {
		if (typeof memo[selector] === "undefined") {
			memo[selector] = fn.call(this, selector);
		}

		return memo[selector]
	};
})(function (target) {
	return document.querySelector(target)
});

var singleton = null;
var	singletonCounter = 0;
var	stylesInsertedAtTop = [];

var	fixUrls = __webpack_require__(4);

module.exports = function(list, options) {
	if (typeof DEBUG !== "undefined" && DEBUG) {
		if (typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
	}

	options = options || {};

	options.attrs = typeof options.attrs === "object" ? options.attrs : {};

	// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
	// tags it will allow on a page
	if (!options.singleton) options.singleton = isOldIE();

	// By default, add <style> tags to the <head> element
	if (!options.insertInto) options.insertInto = "head";

	// By default, add <style> tags to the bottom of the target
	if (!options.insertAt) options.insertAt = "bottom";

	var styles = listToStyles(list, options);

	addStylesToDom(styles, options);

	return function update (newList) {
		var mayRemove = [];

		for (var i = 0; i < styles.length; i++) {
			var item = styles[i];
			var domStyle = stylesInDom[item.id];

			domStyle.refs--;
			mayRemove.push(domStyle);
		}

		if(newList) {
			var newStyles = listToStyles(newList, options);
			addStylesToDom(newStyles, options);
		}

		for (var i = 0; i < mayRemove.length; i++) {
			var domStyle = mayRemove[i];

			if(domStyle.refs === 0) {
				for (var j = 0; j < domStyle.parts.length; j++) domStyle.parts[j]();

				delete stylesInDom[domStyle.id];
			}
		}
	};
};

function addStylesToDom (styles, options) {
	for (var i = 0; i < styles.length; i++) {
		var item = styles[i];
		var domStyle = stylesInDom[item.id];

		if(domStyle) {
			domStyle.refs++;

			for(var j = 0; j < domStyle.parts.length; j++) {
				domStyle.parts[j](item.parts[j]);
			}

			for(; j < item.parts.length; j++) {
				domStyle.parts.push(addStyle(item.parts[j], options));
			}
		} else {
			var parts = [];

			for(var j = 0; j < item.parts.length; j++) {
				parts.push(addStyle(item.parts[j], options));
			}

			stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
		}
	}
}

function listToStyles (list, options) {
	var styles = [];
	var newStyles = {};

	for (var i = 0; i < list.length; i++) {
		var item = list[i];
		var id = options.base ? item[0] + options.base : item[0];
		var css = item[1];
		var media = item[2];
		var sourceMap = item[3];
		var part = {css: css, media: media, sourceMap: sourceMap};

		if(!newStyles[id]) styles.push(newStyles[id] = {id: id, parts: [part]});
		else newStyles[id].parts.push(part);
	}

	return styles;
}

function insertStyleElement (options, style) {
	var target = getElement(options.insertInto)

	if (!target) {
		throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");
	}

	var lastStyleElementInsertedAtTop = stylesInsertedAtTop[stylesInsertedAtTop.length - 1];

	if (options.insertAt === "top") {
		if (!lastStyleElementInsertedAtTop) {
			target.insertBefore(style, target.firstChild);
		} else if (lastStyleElementInsertedAtTop.nextSibling) {
			target.insertBefore(style, lastStyleElementInsertedAtTop.nextSibling);
		} else {
			target.appendChild(style);
		}
		stylesInsertedAtTop.push(style);
	} else if (options.insertAt === "bottom") {
		target.appendChild(style);
	} else {
		throw new Error("Invalid value for parameter 'insertAt'. Must be 'top' or 'bottom'.");
	}
}

function removeStyleElement (style) {
	if (style.parentNode === null) return false;
	style.parentNode.removeChild(style);

	var idx = stylesInsertedAtTop.indexOf(style);
	if(idx >= 0) {
		stylesInsertedAtTop.splice(idx, 1);
	}
}

function createStyleElement (options) {
	var style = document.createElement("style");

	options.attrs.type = "text/css";

	addAttrs(style, options.attrs);
	insertStyleElement(options, style);

	return style;
}

function createLinkElement (options) {
	var link = document.createElement("link");

	options.attrs.type = "text/css";
	options.attrs.rel = "stylesheet";

	addAttrs(link, options.attrs);
	insertStyleElement(options, link);

	return link;
}

function addAttrs (el, attrs) {
	Object.keys(attrs).forEach(function (key) {
		el.setAttribute(key, attrs[key]);
	});
}

function addStyle (obj, options) {
	var style, update, remove, result;

	// If a transform function was defined, run it on the css
	if (options.transform && obj.css) {
	    result = options.transform(obj.css);

	    if (result) {
	    	// If transform returns a value, use that instead of the original css.
	    	// This allows running runtime transformations on the css.
	    	obj.css = result;
	    } else {
	    	// If the transform function returns a falsy value, don't add this css.
	    	// This allows conditional loading of css
	    	return function() {
	    		// noop
	    	};
	    }
	}

	if (options.singleton) {
		var styleIndex = singletonCounter++;

		style = singleton || (singleton = createStyleElement(options));

		update = applyToSingletonTag.bind(null, style, styleIndex, false);
		remove = applyToSingletonTag.bind(null, style, styleIndex, true);

	} else if (
		obj.sourceMap &&
		typeof URL === "function" &&
		typeof URL.createObjectURL === "function" &&
		typeof URL.revokeObjectURL === "function" &&
		typeof Blob === "function" &&
		typeof btoa === "function"
	) {
		style = createLinkElement(options);
		update = updateLink.bind(null, style, options);
		remove = function () {
			removeStyleElement(style);

			if(style.href) URL.revokeObjectURL(style.href);
		};
	} else {
		style = createStyleElement(options);
		update = applyToTag.bind(null, style);
		remove = function () {
			removeStyleElement(style);
		};
	}

	update(obj);

	return function updateStyle (newObj) {
		if (newObj) {
			if (
				newObj.css === obj.css &&
				newObj.media === obj.media &&
				newObj.sourceMap === obj.sourceMap
			) {
				return;
			}

			update(obj = newObj);
		} else {
			remove();
		}
	};
}

var replaceText = (function () {
	var textStore = [];

	return function (index, replacement) {
		textStore[index] = replacement;

		return textStore.filter(Boolean).join('\n');
	};
})();

function applyToSingletonTag (style, index, remove, obj) {
	var css = remove ? "" : obj.css;

	if (style.styleSheet) {
		style.styleSheet.cssText = replaceText(index, css);
	} else {
		var cssNode = document.createTextNode(css);
		var childNodes = style.childNodes;

		if (childNodes[index]) style.removeChild(childNodes[index]);

		if (childNodes.length) {
			style.insertBefore(cssNode, childNodes[index]);
		} else {
			style.appendChild(cssNode);
		}
	}
}

function applyToTag (style, obj) {
	var css = obj.css;
	var media = obj.media;

	if(media) {
		style.setAttribute("media", media)
	}

	if(style.styleSheet) {
		style.styleSheet.cssText = css;
	} else {
		while(style.firstChild) {
			style.removeChild(style.firstChild);
		}

		style.appendChild(document.createTextNode(css));
	}
}

function updateLink (link, options, obj) {
	var css = obj.css;
	var sourceMap = obj.sourceMap;

	/*
		If convertToAbsoluteUrls isn't defined, but sourcemaps are enabled
		and there is no publicPath defined then lets turn convertToAbsoluteUrls
		on by default.  Otherwise default to the convertToAbsoluteUrls option
		directly
	*/
	var autoFixUrls = options.convertToAbsoluteUrls === undefined && sourceMap;

	if (options.convertToAbsoluteUrls || autoFixUrls) {
		css = fixUrls(css);
	}

	if (sourceMap) {
		// http://stackoverflow.com/a/26603875
		css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + " */";
	}

	var blob = new Blob([css], { type: "text/css" });

	var oldSrc = link.href;

	link.href = URL.createObjectURL(blob);

	if(oldSrc) URL.revokeObjectURL(oldSrc);
}


/***/ }),
/* 2 */
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
        var frag, wrap, frm, html, body, deferred;
        deferred = $q.defer();
        if (!content) {
            console.log('参数为空');
            deferred.reject();
        }
        if (document.documentElement.clientWidth > 768) {
            document.documentElement.scrollTop = 0;
        } else {
            document.body.scrollTop = 0;
        }
        body = document.getElementsByTagName('body')[0];
        html = document.getElementsByTagName('html')[0];
        html.style.cssText = "height:100%;"
        body.style.cssText = "height:100%;overflow-y:hidden";
        frag = document.createDocumentFragment();
        wrap = document.createElement('div');
        wrap.setAttribute('id', 'frmPlugin');
        frm = document.createElement('iframe');
        wrap.appendChild(frm);
        wrap.onclick = function() {
            wrap.parentNode.removeChild(wrap);
            body.style.cssText = "overflow-y:auto";
        };
        frag.appendChild(wrap);
        document.body.appendChild(frag);
        if (content.indexOf('http') === 0) {
            window.onClosePlugin = function(result) {
                wrap.parentNode.removeChild(wrap);
                body.style.cssText = "overflow-y:auto";
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
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('http.ui.xxt', []);
ngMod.provider('tmsLocation', function() {
    var _baseUrl;

    this.config = function(baseUrl) {
        _baseUrl = baseUrl || location.pathname;
    };

    this.$get = ['$location', function($location) {
        if (!_baseUrl) {
            _baseUrl = location.pathname;
        }
        return {
            s: function() {
                return $location.search();
            },
            j: function(method) {
                var url = _baseUrl,
                    search = [];
                method && method.length && (url += '/' + method);
                for (var i = 1, l = arguments.length; i < l; i++) {
                    search.push(arguments[i] + '=' + ($location.search()[arguments[i]] || ''));
                };
                search.length && (url += '?' + search.join('&'));
                return url;
            }
        };
    }];
});
ngMod.service('http2', ['$rootScope', '$http', '$timeout', '$q', '$sce', '$compile', function($rootScope, $http, $timeout, $q, $sce, $compile) {
    function _fnCreateAlert(msg, type, keep) {
        var alertDomEl;
        /* backdrop */
        $sce.trustAsHtml(msg);
        alertDomEl = angular.element('<div></div>');
        alertDomEl.attr({
            'class': 'tms-notice-box alert alert-' + (type ? type : 'info'),
            'ng-style': '{\'z-index\':1099}'
        }).html(msg);
        if (!keep) {
            alertDomEl[0].addEventListener('click', function() {
                document.body.removeChild(alertDomEl[0]);
            }, true);
        }
        $compile(alertDomEl)($rootScope);
        document.body.appendChild(alertDomEl[0]);

        return alertDomEl[0];
    }

    function _fnRemoveAlert(alertDomEl) {
        if (alertDomEl) {
            document.body.removeChild(alertDomEl);
        }
    }

    function _requirePagination(oOptions) {
        if (oOptions.page && angular.isObject(oOptions.page)) {
            if (oOptions.page.at === undefined) oOptions.page.at = 1;
            if (oOptions.page.size === undefined) oOptions.page.size = 12;
            if (oOptions.page.j === undefined || !angular.isFunction(oOptions.page.j)) {
                oOptions.page.j = function() {
                    return 'page=' + this.at + '&size=' + this.size;
                };
            }
            return true;
        }
        return false;
    }

    /**
     * 合并两个对象
     * 解决将通过http获得的数据和本地数据合并的问题
     */
    function _fnMerge(oOld, oNew, aExcludeProps) {
        if (!oOld) {
            oOld = oNew;
        } else if (angular.isArray(oOld)) {
            if (oOld.length > oNew.length) {
                oOld.splice(oNew.length - 1, oOld.length - oNew.length);
            }
            for (var i = 0, ii = oNew.length; i < ii; i++) {
                if (i < oOld.length) {
                    _fnMerge(oOld[i], oNew[i]);
                } else {
                    oOld.push(oNew[i]);
                }
            }
        } else if (angular.isObject(oOld)) {
            for (var prop in oOld) {
                if (aExcludeProps && aExcludeProps.indexOf(prop) !== -1) {
                    continue;
                }
                if (oNew[prop] === undefined) {
                    delete oOld[prop];
                } else {
                    if (angular.isObject(oNew[prop]) && angular.isObject(oOld[prop])) {
                        _fnMerge(oOld[prop], oNew[prop]);
                    } else {
                        oOld[prop] = oNew[prop];
                    }
                }
            }
            for (var prop in oNew) {
                if (aExcludeProps && aExcludeProps.indexOf(prop) !== -1) {
                    continue;
                }
                if (oOld[prop] === undefined) {
                    oOld[prop] = oNew[prop];
                }
            }
        }

        return true;
    }

    this.get = function(url, oOptions) {
        var _alert, _timer, _defer = $q.defer();
        oOptions = angular.extend({
            'headers': {
                'accept': 'application/json'
            },
            'parseResponse': true,
            'autoBreak': true,
            'autoNotice': true,
            'showProgress': true,
            'showProgressDelay': 500,
            'showProgressText': '正在获取数据...',
        }, oOptions);
        if (oOptions.showProgress === true) {
            _timer = $timeout(function() {
                _timer = null;
                _alert = _fnCreateAlert(oOptions.showProgressText, 'info');
            }, oOptions.showProgressDelay);
        }
        if (_requirePagination(oOptions)) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + oOptions.page.j();
        }
        $http.get(url, oOptions).success(function(rsp) {
            if (oOptions.page && rsp.data.total !== undefined) {
                oOptions.page.total = rsp.data.total;
            }
            if (oOptions.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    _fnRemoveAlert(_alert);
                    _alert = null;
                }
            }
            if (!oOptions.parseResponse) {
                _defer.resolve(rsp);
            } else {
                if (angular.isString(rsp)) {
                    if (oOptions.autoNotice) {
                        _fnCreateAlert(rsp, 'warning');
                    }
                    if (oOptions.autoBreak) {
                        return
                    } else {
                        _defer.reject(rsp);
                    }
                } else if (rsp.err_code != 0) {
                    if (oOptions.autoNotice) {
                        var errmsg;
                        if (angular.isString(rsp.err_msg)) {
                            errmsg = rsp.err_msg;
                        } else if (angular.isArray(rsp.err_msg)) {
                            errmsg = rsp.err_msg.join('<br>');
                        } else {
                            errmsg = JSON.stringify(rsp.err_msg);
                        }
                        _fnCreateAlert(errmsg, 'warning');
                    }
                    if (oOptions.autoBreak) {
                        return
                    } else {
                        _defer.reject(rsp);
                    }
                } else {
                    _defer.resolve(rsp);
                }
            }
        }).error(function(data, status) {
            if (oOptions.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    _fnRemoveAlert(_alert);
                    _alert = null;
                }
            }
            _fnCreateAlert(data === null ? '网络不可用' : data, 'danger');
        });

        return _defer.promise;
    };
    this.post = function(url, posted, oOptions) {
        var _alert, _timer, _defer = $q.defer();
        oOptions = angular.extend({
            'headers': {
                'accept': 'application/json'
            },
            'parseResponse': true,
            'autoBreak': true,
            'autoNotice': true,
            'showProgress': true,
            'showProgressDelay': 500,
            'showProgressText': '正在获取数据...',
        }, oOptions);
        if (oOptions.showProgress === true) {
            _timer = $timeout(function() {
                _timer = null;
                _alert = _fnCreateAlert(oOptions.showProgressText, 'info');
            }, oOptions.showProgressDelay);
        }
        if (_requirePagination(oOptions)) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + oOptions.page.j();
        }
        $http.post(url, posted, oOptions).success(function(rsp) {
            if (oOptions.page && rsp.data.total !== undefined) {
                oOptions.page.total = rsp.data.total;
            }
            if (oOptions.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    _fnRemoveAlert(_alert);
                    _alert = null;
                }
            }
            if (!oOptions.parseResponse) {
                _defer.resolve(rsp);
            } else {
                if (angular.isString(rsp)) {
                    if (oOptions.autoNotice) {
                        _fnCreateAlert(rsp, 'warning');
                        _alert = null;
                    }
                    if (oOptions.autoBreak) {
                        return
                    } else {
                        _defer.reject(rsp);
                    }
                } else if (rsp.err_code != 0) {
                    if (oOptions.autoNotice) {
                        var errmsg;
                        if (angular.isString(rsp.err_msg)) {
                            errmsg = rsp.err_msg;
                        } else if (angular.isArray(rsp.err_msg)) {
                            errmsg = rsp.err_msg.join('<br>');
                        } else {
                            errmsg = JSON.stringify(rsp.err_msg);
                        }
                        _fnCreateAlert(errmsg, 'warning');
                    }
                    if (oOptions.autoBreak) {
                        return
                    } else {
                        _defer.reject(rsp);
                    }
                } else {
                    _defer.resolve(rsp);
                }
            }
        }).error(function(data, status) {
            if (oOptions.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    _fnRemoveAlert(_alert);
                    _alert = null;
                }
            }
            _fnCreateAlert(data === null ? '网络不可用' : data, 'danger');
        });

        return _defer.promise;
    };
    /**
     * 合并两个对象
     * 解决将通过http获得的数据和本地数据合并的问题
     */
    this.merge = function(oOld, oNew, aExcludeProps) {
        if (angular.equals(oOld, oNew)) {
            return false;
        }
        return _fnMerge(oOld, oNew, aExcludeProps);
    };
}]);

/***/ }),
/* 4 */
/***/ (function(module, exports) {


/**
 * When source maps are enabled, `style-loader` uses a link element with a data-uri to
 * embed the css on the page. This breaks all relative urls because now they are relative to a
 * bundle instead of the current page.
 *
 * One solution is to only use full urls, but that may be impossible.
 *
 * Instead, this function "fixes" the relative urls to be absolute according to the current page location.
 *
 * A rudimentary test suite is located at `test/fixUrls.js` and can be run via the `npm test` command.
 *
 */

module.exports = function (css) {
  // get current location
  var location = typeof window !== "undefined" && window.location;

  if (!location) {
    throw new Error("fixUrls requires window.location");
  }

	// blank or null?
	if (!css || typeof css !== "string") {
	  return css;
  }

  var baseUrl = location.protocol + "//" + location.host;
  var currentDir = baseUrl + location.pathname.replace(/\/[^\/]*$/, "/");

	// convert each url(...)
	/*
	This regular expression is just a way to recursively match brackets within
	a string.

	 /url\s*\(  = Match on the word "url" with any whitespace after it and then a parens
	   (  = Start a capturing group
	     (?:  = Start a non-capturing group
	         [^)(]  = Match anything that isn't a parentheses
	         |  = OR
	         \(  = Match a start parentheses
	             (?:  = Start another non-capturing groups
	                 [^)(]+  = Match anything that isn't a parentheses
	                 |  = OR
	                 \(  = Match a start parentheses
	                     [^)(]*  = Match anything that isn't a parentheses
	                 \)  = Match a end parentheses
	             )  = End Group
              *\) = Match anything and then a close parens
          )  = Close non-capturing group
          *  = Match anything
       )  = Close capturing group
	 \)  = Match a close parens

	 /gi  = Get all matches, not the first.  Be case insensitive.
	 */
	var fixedCss = css.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi, function(fullMatch, origUrl) {
		// strip quotes (if they exist)
		var unquotedOrigUrl = origUrl
			.trim()
			.replace(/^"(.*)"$/, function(o, $1){ return $1; })
			.replace(/^'(.*)'$/, function(o, $1){ return $1; });

		// already a full url? no change
		if (/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/)/i.test(unquotedOrigUrl)) {
		  return fullMatch;
		}

		// convert the url to a full url
		var newUrl;

		if (unquotedOrigUrl.indexOf("//") === 0) {
		  	//TODO: should we add protocol?
			newUrl = unquotedOrigUrl;
		} else if (unquotedOrigUrl.indexOf("/") === 0) {
			// path should be relative to the base url
			newUrl = baseUrl + unquotedOrigUrl; // already starts with '/'
		} else {
			// path should be relative to current directory
			newUrl = currentDir + unquotedOrigUrl.replace(/^\.\//, ""); // Strip leading './'
		}

		// send back the fixed url(...)
		return "url(" + JSON.stringify(newUrl) + ")";
	});

	// send back the fixed css
	return fixedCss;
};


/***/ }),
/* 5 */
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
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('notice.ui.xxt', ['ngSanitize']);
ngMod.service('noticebox', ['$timeout', '$interval', '$q', function($timeout, $interval, $q) {
    var _boxId = 'tmsbox' + (new Date * 1),
        _last = {
            type: '',
            timer: null
        },
        _getBox = function(type, msg) {
            var box;
            box = document.querySelector('#' + _boxId);
            if (box === null) {
                box = document.createElement('div');
                box.setAttribute('id', _boxId);
                box.classList.add('tms-notice-box', 'alert', 'alert-' + type);
                box.innerHTML = '<div>' + msg + '</div>';
                document.body.appendChild(box);
                _last.type = type;
            } else {
                if (_last.type !== type) {
                    box.classList.remove('alert-' + type);
                    _last.type = type;
                }
                box.childNodes[0].innerHTML = msg;
            }

            return box;
        };

    this.close = function() {
        var box;
        box = document.querySelector('#' + _boxId);
        if (box) {
            document.body.removeChild(box);
        }
    };
    this.error = function(msg) {
        var box, btn;

        /*取消自动关闭*/
        if (_last.timer) {
            $timeout.cancel(_last.timer);
            _last.timer = null;
        }
        /*显示消息框*/
        box = _getBox('danger', msg);
        /*手工关闭*/
        btn = document.createElement('button');
        btn.classList.add('close');
        btn.innerHTML = '<span>&times;</span>';
        box.insertBefore(btn, box.childNodes[0]);
        btn.addEventListener('click', function() {
            document.body.removeChild(box);
        });
    };
    this.warn = function(msg) {
        var box, btn;

        /*取消自动关闭*/
        if (_last.timer) {
            $timeout.cancel(_last.timer);
            _last.timer = null;
        }
        /*显示消息框*/
        box = _getBox('warning', msg);
        /*手工关闭*/
        btn = document.createElement('button');
        btn.classList.add('close');
        btn.innerHTML = '<span>&times;</span>';
        box.insertBefore(btn, box.childNodes[0]);
        btn.addEventListener('click', function() {
            document.body.removeChild(box);
        });
    };
    this.success = function(msg) {
        var box;
        /*取消自动关闭*/
        _last.timer && $timeout.cancel(_last.timer);
        /*显示消息框*/
        box = _getBox('success', msg);
        /*保持2秒钟后自动关闭*/
        _last.timer = $timeout(function() {
            if (box.parentNode && box.parentNode === document.body) {
                document.body.removeChild(box);
            }
            _last.timer = null;
        }, 2000);
    };
    this.info = function(msg) {
        var box;
        /*取消自动关闭*/
        _last.timer && $timeout.cancel(_last.timer);
        /*显示消息框*/
        box = _getBox('info', msg);
        /*保持2秒钟后自动关闭*/
        _last.timer = $timeout(function() {
            if (box.parentNode && box.parentNode === document.body) {
                document.body.removeChild(box);
            }
            _last.timer = null;
        }, 2000);
    };
    this.progress = function(msg) {
        /*显示消息框*/
        _getBox('progress', msg);
    };
    this.confirm = function(msg, buttons) {
        var defer, box, btn;
        defer = $q.defer();
        /*取消自动关闭*/
        if (_last.timer) {
            $timeout.cancel(_last.timer);
            _last.timer = null;
        }
        /*显示消息框*/
        box = _getBox('warning', msg);
        /*添加操作*/
        if (buttons && buttons.length) {
            buttons.forEach(function(oButton) {
                btn = document.createElement('button');
                btn.classList.add('btn', 'btn-default', 'btn-sm');
                btn.innerHTML = oButton.label;
                box.appendChild(btn, box.childNodes[0]);
                btn.addEventListener('click', function() {
                    document.body.removeChild(box);
                    defer.resolve(oButton.value);
                });
                if (oButton.execWait) {
                    var counter = Math.ceil(oButton.execWait / 500);
                    var countdown = document.createElement('span');
                    countdown.classList.add('countdown');
                    countdown.innerHTML = counter;
                    btn.appendChild(countdown);
                    $interval(function() {
                        countdown.innerHTML = --counter;
                    }, 500);
                    /* 自动关闭 */
                    _last.timer = $timeout(function() {
                        if (box.parentNode && box.parentNode === document.body) {
                            document.body.removeChild(box);
                        }
                        _last.timer = null;
                        defer.resolve(oButton.value);
                    }, oButton.execWait);
                }
            });
        } else {
            btn = document.createElement('button');
            btn.classList.add('btn', 'btn-default', 'btn-sm');
            btn.innerHTML = '是';
            box.appendChild(btn, box.childNodes[0]);
            btn.addEventListener('click', function() {
                document.body.removeChild(box);
                defer.resolve();
            });
            btn = document.createElement('button');
            btn.classList.add('btn', 'btn-default', 'btn-sm');
            btn.innerHTML = '否';
            box.appendChild(btn, box.childNodes[0]);
            btn.addEventListener('click', function() {
                document.body.removeChild(box);
                defer.reject();
            });
        }

        return defer.promise;
    };
}]);

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".dialog.mask{position:fixed;background:rgba(0,0,0,.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:1060}.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}.dialog .dlg-header{padding:15px 15px 0 15px}.dialog .dlg-body{padding:15px 15px 0 15px}.dialog .dlg-footer{text-align:right;padding:15px}.dialog .dlg-footer button{border-radius:0}div[wrap=filter] .detail{background:#ccc}div[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}div[wrap=filter] .detail .actions .btn{border-radius:0}.tms-act-toggle{position:fixed;right:15px;bottom:8px;width:48px;height:48px;line-height:48px;box-shadow:0 2px 6px rgba(18,27,32,.425);color:#fff;background:#ff8018;border:1px solid #ff8018;border-radius:24px;font-size:20px;text-align:center;cursor:pointer;z-index:1050}.tms-nav-target>*+*{margin-top:.5em}.tms-act-popover-wrap>div+div{margin-top:8px}#frmPlugin{position:absolute;top:0;bottom:0;left:0;right:0;width:100%;height:100%;border:none;z-index:1060;box-sizing:border-box;padding-bottom:48px;background:#fff}#frmPlugin iframe{width:100%;height:100%;border:0}#frmPlugin:after{content:'\\5173\\95ED';position:absolute;width:100px;text-align:center;left:50%;margin-left:-50px;bottom:4px;padding:5px 6px 3px;border:1px solid #ccc;border-radius:4px}div[wrap]>.description{word-wrap:break-word}", ""]);

// exports


/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(7);
if(typeof content === 'string') content = [[module.i, content, '']];
// Prepare cssTransformation
var transform;

var options = {}
options.transform = transform
// add the styles to the DOM
var update = __webpack_require__(1)(content, options);
if(content.locals) module.exports = content.locals;
// Hot Module Replacement
if(false) {
	// When the styles change, update the <style> tags
	if(!content.locals) {
		module.hot.accept("!!../../../../../../node_modules/css-loader/index.js!./directive.css", function() {
			var newContent = require("!!../../../../../../node_modules/css-loader/index.js!./directive.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),
/* 9 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


window.__util = {};
window.__util.makeDialog = function(id, html) {
    var dlg, mask;

    mask = document.createElement('div');
    mask.setAttribute('id', id);
    mask.classList.add('dialog', 'mask');

    dlg = "<div class='dialog dlg'>";
    html.header && html.header.length && (dlg += "<div class='dlg-header'>" + html.header + "</div>");
    dlg += "<div class='dlg-body'>" + html.body + "</div>";
    html.footer && html.footer.length && (dlg += "<div class='dlg-footer'>" + html.footer + "</div>");
    dlg += "</div>";

    mask.innerHTML = dlg;

    document.body.appendChild(mask);

    return mask.children;
};

var ngMod = angular.module('directive.enroll', []);
ngMod.directive('tmsDate', ['$compile', function($compile) {
    return {
        restrict: 'A',
        scope: {
            value: '=tmsDateValue'
        },
        controller: ['$scope', function($scope) {
            $scope.close = function() {
                var mask;
                mask = document.querySelector('#' + $scope.dialogID);
                document.body.removeChild(mask);
                $scope.opened = false;
            };
            $scope.ok = function() {
                var dtObject;
                dtObject = new Date();
                dtObject.setTime(0);
                dtObject.setFullYear($scope.data.year);
                dtObject.setMonth($scope.data.month - 1);
                dtObject.setDate($scope.data.date);
                dtObject.setHours($scope.data.hour);
                dtObject.setMinutes($scope.data.minute);
                $scope.value = parseInt(dtObject.getTime() / 1000);
                $scope.close();
            };
        }],
        link: function(scope, elem, attrs) {
            var fnOpenPicker, dtObject, dtMinute, htmlBody;
            scope.value === undefined && (scope.value = (new Date() * 1) / 1000);
            dtObject = new Date();
            dtObject.setTime(scope.value * 1000);
            scope.options = {
                years: [2014, 2015, 2016, 2017, 2018, 2019, 2020],
                months: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                dates: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31],
                hours: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23],
                minutes: [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55],
            };
            dtMinute = Math.round(dtObject.getMinutes() / 5) * 5;
            scope.data = {
                year: dtObject.getFullYear(),
                month: dtObject.getMonth() + 1,
                date: dtObject.getDate(),
                hour: dtObject.getHours(),
                minute: dtMinute
            };
            scope.options.minutes.indexOf(dtMinute) === -1 && scope.options.minutes.push(dtMinute);
            htmlBody = '<div class="form-group"><select class="form-control" ng-model="data.year" ng-options="y for y in options.years"></select></div>';
            htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.month" ng-options="m for m in options.months"></select></div>';
            htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.date" ng-options="d for d in options.dates"></select></div>';
            htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.hour" ng-options="h for h in options.hours"></select></div>';
            htmlBody += '<div class="form-group"><select class="form-control" ng-model="data.minute" ng-options="mi for mi in options.minutes"></select></div>';
            fnOpenPicker = function(event) {
                event.preventDefault();
                event.stopPropagation();
                if (scope.opened) return;
                var html, id;
                id = '_dlg-' + (new Date() * 1);
                html = {
                    header: '',
                    body: htmlBody,
                    footer: '<button class="btn btn-default" ng-click="close()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button>'
                };
                html = __util.makeDialog(id, html);
                scope.opened = true;
                scope.dialogID = id;
                $compile(html)(scope);
            };
            elem[0].querySelector('[ng-bind]').addEventListener('click', fnOpenPicker);
        }
    }
}]);
ngMod.directive('flexImg', function() {
    return {
        restrict: 'A',
        replace: true,
        template: "<img ng-src='{{img.imgSrc}}'>",
        link: function(scope, elem, attrs) {
            angular.element(elem).on('load', function() {
                var w = this.clientWidth,
                    h = this.clientHeight,
                    sw, sh;
                if (w > h) {
                    sw = w / h * 80;
                    angular.element(this).css({
                        'height': '100%',
                        'width': sw + 'px',
                        'top': '0',
                        'left': '50%',
                        'margin-left': (-1 * sw / 2) + 'px'
                    });
                } else {
                    sh = h / w * 80;
                    angular.element(this).css({
                        'width': '100%',
                        'height': sh + 'px',
                        'left': '0',
                        'top': '50%',
                        'margin-top': (-1 * sh / 2) + 'px'
                    });
                }
            })
        }
    }
});
/**
 * 根据父元素的高度决定是否隐藏
 */
ngMod.directive('tmsHideParentHeight', function() {
    return {
        restrict: 'A',
        link: function(scope, elems, attrs) {
            var heightLimit, elem;
            if (attrs.tmsHideParentHeight) {
                heightLimit = attrs.tmsHideParentHeight;
                for (var i = 0, ii = elems.length; i < ii; i++) {
                    elem = elems[i];
                    if (elem.parentElement) {
                        window.addEventListener('resize', function() {
                            elem.classList.toggle('hidden', elem.parentElement.clientHeight < heightLimit);
                        });
                    }
                }
            }
        }
    }
});
/**
 * 监听元素的滚动事件并做出相应
 */
ngMod.directive('tmsScrollSpy', function() {
    return {
        restrict: 'A',
        scope: {
            selector: '@selector',
            offset: '@',
            onbottom: '&',
            toggleSpy: '='
        },
        link: function(scope, elems, attrs) {
            var eleListen = scope.selector === 'window' ? window : document.querySelector(scope.selector);
            eleListen.addEventListener('scroll', function(event) {
                var eleScrolling = eleListen === window ? event.target.documentElement : event.target;
                if (scope.toggleSpy) {
                        if (scope.onbottom && angular.isFunction(scope.onbottom)) {
                            if (eleScrolling.clientHeight + eleScrolling.scrollTop + parseInt(scope.offset) >= eleScrolling.scrollHeight) {
                                scope.$apply(function() {
                                    scope.toggleSpy = false;
                                    scope.onbottom();
                                });
                            }
                        }
                    }
            });
        }
    }
});

/***/ }),
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function openPlugin(content, cb) {
    var frag, wrap, frm;
    frag = document.createDocumentFragment();
    wrap = document.createElement('div');
    wrap.setAttribute('id', 'frmPlugin');
    frm = document.createElement('iframe');
    wrap.appendChild(frm);
    wrap.onclick = function() {
        wrap.parentNode.removeChild(wrap);
    };
    frag.appendChild(wrap);
    document.body.appendChild(frag);
    if (content.indexOf('http') === 0) {
        window.onClosePlugin = function() {
            wrap.parentNode.removeChild(wrap);
            cb && cb();
        };
        frm.setAttribute('src', content);
    } else {
        if (frm.contentDocument && frm.contentDocument.body) {
            frm.contentDocument.body.innerHTML = content;
        }
    }
}

var ngMod = angular.module('coinpay.ui.xxt', []);
ngMod.service('tmsCoinPay', function() {
    this.showSwitch = function(siteId, matter) {
        var eSwitch;
        eSwitch = document.createElement('div');
        eSwitch.classList.add('tms-switch', 'tms-switch-coinpay');
        eSwitch.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            var url = location.protocol + '//' + location.host;
            url += '/rest/site/fe/coin/pay';
            url += "?site=" + siteId;
            url += "&matter=" + matter;
            openPlugin(url);
        }, true);
        document.body.appendChild(eSwitch);
    }
});


/***/ }),
/* 11 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('picviewer.ui.xxt', []);
ngMod.factory('picviewer', ['$q', function($q) {
    /*私有方法*/
    var _method = {
        isArray: function(value) {
            return Object.prototype.toString.call(value) == '[object Array]';
        },
        all: function(selector, contextElement) {
            var nodeList,
                list = [];
            if (contextElement) {
                nodeList = contextElement.querySelectorAll(selector);
            } else {
                nodeList = document.querySelectorAll(selector);
            }
            if (nodeList && nodeList.length > 0) {
                list = Array.prototype.slice.call(nodeList);
            }
            return list;
        },
        delegate: function(ele, eventType, selector, fn) {
            var _this = this;
            if (!ele) { return; }
            ele.addEventListener(eventType, function(e) {
                var targets = _this.all(selector, ele);
                if (!targets) {
                    return;
                }
                for (var i = 0; i < targets.length; i++) {
                    var node = e.target;
                    while (node) {
                        if (node == targets[i]) {
                            fn.call(node, e);
                            break;
                        }
                        node = node.parentNode;
                        if (node == ele) {
                            break;
                        }
                    }
                }
            }, false);
        }
    }

    /*初始化*/
    var _picviewer = function() {
        this.winw = window.innerWidth || document.body.clientWidth; 
        this.winh = (window.innerHeight+1) || document.body.clientHeight;
        this.originWinw = this.winw;
        this.originWinh = this.winh;
        this.marginRight = 15;
        this.imageChageMoveX = this.marginRight + this.winw;
        this.imageChageNeedX = Math.floor(this.winw * (0.5));
        this.cssprefix = ["", "webkit", "Moz", "ms", "o"];
        this.imgLoadCache = new Object();
        this.scale = 1;
        this.maxScale = 4;
        this.maxOverScale = 6;
        this.openTime = 0.3;
        this.slipTime = 0.5;
        this.maxOverWidthPercent = 0.5;
        this.box = false;
        this.isPreview = false;
        this.container = document.createElement('div');
        this.container.setAttribute('id', 'previewImage-container');
        this.container.style.width = this.winw + 'px';
        this.container.style.height = this.winh + 'px'; 
        document.body.appendChild(this.container); 
        this.bind();  
    };

    /*绑定事件*/
    _picviewer.prototype.bind = function() {
        var _this = this;
        var container = this.container;

        var closePreview = function() {
            _this.setCloseStatus.call(_this);
        }
        var touchStartFun = function() {
            _this.touchStartFun.call(_this);
        }
        var touchMoveFun = function() {
            _this.touchMoveFun.call(_this);
        }
        var touchEndFun = function() {
            _this.touchEndFun.call(_this);
        }
        var reSizeFun = function() {
            var _this = this;
            _this.winw = window.innerWidth || document.body.clientWidth; 
            _this.winh = window.innerHeight || document.body.clientHeight;
            _this.originWinw = _this.winw; 
            _this.originWinh = _this.winh; 
            _this.container.style.width = _this.winw + 'px';
            _this.container.style.height = _this.winh + 'px'; 
            _this.imageChageMoveX = _this.marginRight + _this.winw;
            var offsetX = -_this.imageChageMoveX * _this.index; 
            try {
                _this.boxData.x = offsetX;
                _this.translateScale(_this.bIndex, 0);
            } catch (e) {}
        }.bind(this);
        var keyDownFun = function(){
            var _this = this;
            if (event.keyCode == 37) {
                this.prev &&  this.prev();
            } else if(event.keyCode == 39) {
                this.next && this.next();
            }
        }.bind(this);

        window.addEventListener("resize", reSizeFun, false);
        document.addEventListener("keydown", keyDownFun, false);
        _method.delegate(container, 'click', '.previewImage-item', closePreview);
        _method.delegate(container, 'touchstart', '.previewImage-item', touchStartFun);
        _method.delegate(container, 'touchmove', '.previewImage-item', touchMoveFun);
        _method.delegate(container, 'touchend', '.previewImage-item', touchEndFun);
        _method.delegate(container, 'touchcancel', '.previewImage-item', touchEndFun);
    };
    _picviewer.prototype.setCloseStatus = function() {
        if(this.winw > 992) {
            if(this.urls.length == 1 || this.index == this.maxLen) {
                this.closePreview();
            }else {
                this.next && this.next();
            }
        }else{
            this.closePreview();
        }
    };
    _picviewer.prototype.closePreview = function(){
        var _this = this;
        this.imgStatusCache[this.cIndex].x = this.winw;
        this.translateScale(this.cIndex,this.openTime);
        this.imgStatusRewrite();
        this.translateScale(this.index,this.slipTime);
        setTimeout(function(){
            _this.container.style.display = "none";
            document.body.style.overflow = 'auto';
        },this.slipTime*1000);
        _this.isPreview = false;
    };
    _picviewer.prototype.touchStartFun = function(imgitem){
        this.ts = this.getTouches();
        this.allowMove = true; 
        this.statusX = 0; 
        this.statusY = 0; 
    };
    _picviewer.prototype.touchMoveFun = function(imgitem){
        this.tm = this.getTouches();
        var tm = this.tm;
        var ts = this.ts;
        this.moveAction(ts,tm);
    };
    _picviewer.prototype.touchEndFun = function(imgitem){
        var container = this.container;
        this.te = this.getTouches();
        this.endAction(this.ts,this.te);
    };
    
    /*被调用的方法*/
    _picviewer.prototype.moveAction = function(ts,tm){
        if(!this.allowMove){ return false; }
        var imgStatus, maxWidth, x0_offset, y0_offset, imgPositionX, imgPositionY, allow, allowX, allowY;
        imgStatus = this.getIndexImage();
        maxWidth = this.winw*0.3/imgStatus.scale;
        x0_offset = tm.x0 - ts.x0;
        y0_offset = tm.y0 - ts.y0;
        if(Math.abs(y0_offset)>0){  
            event.preventDefault();
        }
        imgPositionX = imgStatus.x+x0_offset;
        imgPositionY = imgStatus.y+y0_offset;
        allow = this.getAllow(this.index);
        allowX = this.allowX = allow.x;
        allowY = this.allowY = allow.y0;
        if(x0_offset<=0){ 
            this.allowX = -allowX;
        }
        if(y0_offset<=0){   
            allowY = this.allowY = allow.y1;
        }
        if(tm.length==1){   
            if(imgStatus.scale>1){
                if(imgPositionY>=allow.y0){  
                    this.statusY = 1;
                    var overY = imgPositionY - allow.y0;
                    imgStatus.my = allow.y0-imgStatus.y+this.getSlowlyNum(overY,maxWidth);
                }else if(imgPositionY<=allow.y1){ 
                    this.statusY = 1;
                    var overY = imgPositionY - allow.y1;
                    imgStatus.my = allow.y1-imgStatus.y+this.getSlowlyNum(overY,maxWidth);
                }else{
                    this.statusY = 2;
                    imgStatus.my = y0_offset;
                }

            
                if(x0_offset<0&&imgStatus.x<=-allowX){ 
                    this.statusX = 1;
                    this.boxData.m = x0_offset; 
                    if(this.index==this.maxLen){ 
                        this.boxData.m = this.getSlowlyNum(x0_offset);  
                    }
                    this.translateScale(this.bIndex,0);
                    this.translateScale(this.index,0);
                }else if(x0_offset>0&&imgStatus.x>=allowX){   
                    this.statusX = 2;
                    this.boxData.m = x0_offset;
                    if(this.index==0){ 
                        this.boxData.m = this.getSlowlyNum(x0_offset); 
                    }
                    this.translateScale(this.bIndex,0);
                    this.translateScale(this.index,0);
                }else{  
                    if(x0_offset==0){
                        return
                    }
                    this.statusX = 3;
                    imgStatus.m = x0_offset;
                    if(imgPositionX>=allowX){   
                        this.statusX = 4;
                        var overX = imgPositionX - allowX;
                        imgStatus.m = allowX-imgStatus.x+this.getSlowlyNum(overX,maxWidth);
                    }
                    if(imgPositionX<=-allowX){  
                        this.statusX = 4;
                        var overX = imgPositionX + allowX;
                        imgStatus.m = -allowX-imgStatus.x+this.getSlowlyNum(overX,maxWidth);
                    }
                    this.translateScale(this.index,0);
                }
            }else{ 
                if(Math.abs(y0_offset)>5&&this.statusX != 5){  
                    var $img = this.getJqElem(this.index);
                    var imgBottom = $img.height-this.winh;
                    if(y0_offset>0&&imgPositionY>0){
                        this.statusX = 7;
                        this.allowY = 0;
                        imgStatus.my = - imgStatus.y + this.getSlowlyNum(imgPositionY,maxWidth);
                    }else if(y0_offset<0&&imgPositionY<-imgBottom){
                        this.statusX = 7;
                        if($img.height>this.winh){
                            var overY = imgPositionY + imgBottom;
                            this.allowY = -imgBottom;
                            imgStatus.my = -imgBottom - imgStatus.y + this.getSlowlyNum(overY,maxWidth);
                        }else{
                            this.allowY = 0;
                            imgStatus.my = - imgStatus.y + this.getSlowlyNum(imgPositionY,maxWidth);
                        }
                    }else{

                        this.statusX = 6;
                        imgStatus.my = y0_offset;
                    }
                    this.translateScale(this.index,0);
                }else{
                    if(this.statusX == 6){
                        return
                    }
                    this.statusX = 5;
                    if((this.index==0&&x0_offset>0)||(this.index==this.maxLen&&x0_offset<0)){
                        this.boxData.m = this.getSlowlyNum(x0_offset);
                    }else{
                        this.boxData.m = x0_offset;
                    }
                    this.translateScale(this.bIndex,0);
                }
            }
        }else{  
            var scalem = this.getScale(ts,tm)
            var scale = scalem*imgStatus.scale;
            if(scale>=this.maxScale){  
                var over = scale - this.maxScale;
                scale = this.maxScale+this.getSlowlyNum(over,this.maxOverScale);
                scalem = scale/imgStatus.scale;
            }
            imgStatus.scalem = scalem;
            this.translateScale(this.index,0);
        }
    };
    _picviewer.prototype.endAction = function(ts,te){
        var imgStatus, x0_offset, y0_offset, time, slipTime;
        imgStatus = this.getIndexImage();
        x0_offset = te.x0 - ts.x0;
        y0_offset = te.y0 - ts.y0;
        time = te.time - ts.time;
        slipTime = 0;
        this.allowMove = false; 
        if(ts.length==1){     
            if(Math.abs(x0_offset)>10){
                event.preventDefault();
            }
            switch(this.statusY){
                case 1:
                    imgStatus.y = this.allowY;
                    imgStatus.my = 0;
                    slipTime = this.slipTime;
                break
                case 2:
                    imgStatus.y = imgStatus.y+imgStatus.my;
                    imgStatus.my = 0;
                break
            }

            switch(this.statusX){
                case 1: 
                    if(this.index!=this.maxLen&&(x0_offset<=-this.imageChageNeedX||(time<200&&x0_offset<-30))){   
                        this.changeIndex(1);
                    }else{
                        this.changeIndex(0);
                        if(slipTime!=0){
                            this.translateScale(this.index,slipTime);
                        }
                    }
                break
                case 2: 
                    if(this.index!=0&&(x0_offset>=this.imageChageNeedX||(time<200&&x0_offset>30))){ 
                        this.changeIndex(-1);
                    }else{
                        this.changeIndex(0);
                        if(slipTime!=0){
                            this.translateScale(this.index,slipTime);
                        }
                    }
                break
                case 3: 
                    imgStatus.x = imgStatus.x+imgStatus.m;
                    imgStatus.m = 0;
                    this.translateScale(this.index,slipTime);
                break
                case 4:
                    imgStatus.x = this.allowX;
                    imgStatus.m = 0;
                    slipTime = this.slipTime;
                    this.translateScale(this.index,slipTime);
                break
                case 5: 
                    if(x0_offset>=this.imageChageNeedX||(time<200&&x0_offset>30)){    
                        this.changeIndex(-1);
                    }else if(x0_offset<=-this.imageChageNeedX||(time<200&&x0_offset<-30)){ 
                        this.changeIndex(1);
                    }else{
                        this.changeIndex(0);
                    }
                break
                case 6:
                    imgStatus.y = imgStatus.y+imgStatus.my;
                    imgStatus.my = 0;
                break
                case 7: 
                    imgStatus.y = this.allowY;
                    imgStatus.my = 0;
                    this.translateScale(this.index,this.slipTime);
                break
            }
        }else{  
            event.preventDefault();

            var scale = imgStatus.scale*imgStatus.scalem;
            var $img = this.getJqElem(this.index);
            imgStatus.scale = scale;
            var allow = this.getAllow(this.index);

            if(imgStatus.x>allow.x){
                slipTime = this.slipTime;
                imgStatus.x = allow.x;
            }else if(imgStatus.x<-allow.x){
                slipTime = this.slipTime;
                imgStatus.x = -allow.x;
            }

            if(imgStatus.y>allow.y0){
                slipTime = this.slipTime;
                imgStatus.y = allow.y0;
            }else if(imgStatus.y<allow.y1){
                slipTime = this.slipTime;
                imgStatus.y = allow.y1;
            }

            if($img.height*imgStatus.scale<=this.winh){
                imgStatus.y = 0;
            }

            if($img.width*imgStatus.scale<=this.winw){
                imgStatus.x = 0;
            }

            imgStatus.scalem = 1;
            if(scale>this.maxScale){     
                imgStatus.scale = this.maxScale;
                slipTime = this.slipTime;
            }else if(scale<1){
                this.imgStatusRewrite();
                slipTime = this.slipTime;
            }
            if(slipTime!=0){
                this.changeIndex(0);
                this.translateScale(this.index,slipTime);
            }
        }
    };
    _picviewer.prototype.changeIndex = function(x){
        var imgStatus, oldIndex, _this, hash, imgCache;
        imgStatus = this.getIndexImage();
        oldIndex = this.index;
        _this = this;

        if(this.index==0&&x==-1){
            this.index = this.index;
        }else if(this.index==this.maxLen&&x==1){
            this.index = this.index;
        }else{
            this.index+=x;
            this.ePage.innerHTML = (this.index + 1) + '/' + (this.maxLen + 1);
            hash = this.imgStatusCache[this.index].hash;
            imgCache = this.imgLoadCache[hash];
            if(!imgCache.isload){    
                imgCache.elem.src = this.urls[this.index];
                imgCache.elem.onload = function(){
                    imgCache.isload = true;
                }
            }
        }
        this.setActionStatus();
        this.boxData.x = -this.imageChageMoveX*this.index;
        this.boxData.m = 0;
        if(oldIndex!=this.index){
            this.imgStatusRewrite(oldIndex);
        }
        this.translateScale(this.bIndex,this.slipTime);
    };
    _picviewer.prototype.setActionStatus = function(){
        if (this.index==0) {
            this.ePrev.classList.add('hide');
            this.eNext.classList.remove('hide');
        } else if (this.index==this.maxLen) {
            this.ePrev.classList.remove('hide');
            this.eNext.classList.add('hide');
        } else {
            this.ePrev.classList.remove('hide');
            this.eNext.classList.remove('hide');
        }
    };
    _picviewer.prototype.getTouches = function(e){
        var touches = event.touches.length>0?event.touches:event.changedTouches;
        var obj = {touches:touches,length:touches.length};
            obj.x0 = touches[0].pageX
            obj.y0 = touches[0].pageY;
            obj.time = new Date().getTime();
        if(touches.length>=2){
            obj.x1 = touches[0].pageX
            obj.y1 = touches[1].pageY
        }
        return obj;
    };
    _picviewer.prototype.getIndexImage = function(index){
        var index = index==undefined?this.index:index;
        return  this.imgStatusCache[this.index];
    };
    _picviewer.prototype.getAllow = function(index){
        var $img, imgStatus, allowX, allowY0, allowY1;
        $img = this.getJqElem(index);
        imgStatus = this.getIndexImage(index);
        allowX = Math.floor(($img.width*imgStatus.scale-this.winw)/(2*imgStatus.scale));
        if($img.height*imgStatus.scale<=this.winh){
            allowY0 = 0;
            allowY1 = 0;
        }else if($img.height<=this.winh){
            allowY0 = Math.floor(($img.height*imgStatus.scale-this.winh)/(2*imgStatus.scale));
            allowY1 = -allowY0;
        }else{
            allowY0 = Math.floor($img.height*(imgStatus.scale-1)/(2*imgStatus.scale));
            allowY1 = -Math.floor(($img.height*(imgStatus.scale+1)-2*this.winh)/(2*imgStatus.scale));
        }
        return {
            x:allowX,
            y0:allowY0,
            y1:allowY1,
        };
    };
    _picviewer.prototype.getSlowlyNum = function(x,maxOver){
        var maxOver = maxOver||this.winw*this.maxOverWidthPercent;
        if(x<0){
            x = -x;
            return -(1-(x/(maxOver+x)))*x;
        }else{
            return (1-(x/(maxOver+x)))*x;
        }
    };
    _picviewer.prototype.getScale = function(ts,tm){
        var fingerRangeS, fingerRangeM, range;
        fingerRangeS = Math.sqrt(Math.pow((ts.x1 - ts.x0),2)+Math.pow((ts.y1-ts.y0),2)); 
        fingerRangeM = Math.sqrt(Math.pow((tm.x1 - tm.x0),2)+Math.pow((tm.y1-tm.y0),2));
        range = fingerRangeM/fingerRangeS;
        return range;
    };
    _picviewer.prototype.imgStatusRewrite = function(idx){
        var index=idx===undefined?this.index:idx;
        var imgStatus=this.imgStatusCache[index], currentScale=imgStatus.scale,
            currentX=imgStatus.x, currentY=imgStatus.y;
        imgStatus.x = 0;
        imgStatus.y = 0;
        imgStatus.m = 0;
        imgStatus.my = 0;
        imgStatus.scale = 1;
        imgStatus.scalem = 1;
        if(index!=this.index){
            if(this.winw > 992) {
                imgStatus.scale = currentScale;
                imgStatus.x = currentX;
                imgStatus.y = currentY;
            }
            this.translateScale(index,this.slipTime);
        }
    };
    _picviewer.prototype.translateScale = function (index,duration){
        var imgStatus, $elem, scale, offsetX, offsetY, tran_origin, tran_3d, transition;
        imgStatus = this.imgStatusCache[index];
        $elem = this.getJqElem(index);
        scale = imgStatus.scale*imgStatus.scalem;
        offsetX = imgStatus.x+imgStatus.m;
        offsetY = imgStatus.y+imgStatus.my;
        tran_origin = '0px 0px 0px';
        tran_3d='scale3d('+scale+','+scale+','+'1)' + ' translate3d(' + offsetX + 'px,' + offsetY + 'px,0px)';
        transition = 'transform '+duration+'s ease-out';
        if(this.winw > 992) {
            this.addCssPrefix($elem,'transform-origin',tran_origin);
            tran_3d='translate3d(' + offsetX + 'px,' + offsetY + 'px,0px)' + ' scale3d('+scale+','+scale+','+'1)';
        }
        this.addCssPrefix($elem,'transition',transition);
        this.addCssPrefix($elem,'transform',tran_3d);
    };
    _picviewer.prototype.getJqElem = function(index){
        var $elem, index, hash;
        index = index == undefined?this.index:index;
        if(index<=this.maxLen){
            hash = this.imgStatusCache[index].hash;
            $elem = this.imgLoadCache[hash].elem;
        }else{
            $elem = this.imgStatusCache[index].elem;
        }
        return $elem;
    };
    _picviewer.prototype.addCssPrefix = function(elem,prop,value){
        for(var i in this.cssprefix){
            var cssprefix = this.cssprefix[i];
            if(cssprefix===""){
                prop = prop.toLowerCase();
            }else{
                prop = prop.substr(0,1).toUpperCase()+prop.substr(1,prop.length).toLowerCase()
            }
            if(document.body.style[prop]!==undefined){
                elem.style[prop] = value;
                return false;
            }
        }
    };
    _picviewer.prototype.prev = function(){
        if (this.index > 0) {
            this.changeIndex(-1);
        }
    };
    _picviewer.prototype.next = function(){
        if (this.index < this.maxLen) {
            this.changeIndex(1);
        }
    };
    /*开始和渲染*/
    _picviewer.prototype.render = function(){
        var _this = this;
        document.body.style.overflow = 'hidden';
        if(this.box===false){ 
            this.box = document.createElement('div');
            this.box.setAttribute('class', 'previewImage-box'); 
        }else{
            this.box.innerHTML = ''; 
        }
        this.text = document.createElement('div');   
        this.text.setAttribute('class', 'previewImage-text');
        this.text.innerHTML = "<span class='page'>"+(this.index+1)+"/"+(this.maxLen+1)+"</span><span class='prev'><i class='glyphicon glyphicon-menu-left'></i></span><span class='next'><i class='glyphicon glyphicon-menu-right'></i></span><span class='exit'><i class='glyphicon glyphicon-remove'></i></span>";
        this.containerData = this.imgStatusCache[this.cIndex] = {elem:this.container,x:this.winw,y:0,m:0,my:0,scale:1,scalem:1}; 
        this.boxData = this.imgStatusCache[this.bIndex] = {elem:this.box,x:0,y:0,m:0,my:0,scale:1,scalem:1};   
        this.urls.forEach(function(v,i){    
            var div, hash, img, imgCache;
            div = document.createElement('div');
            hash = window.md5?md5(v+i):v+i;
            imgCache = _this.imgLoadCache[hash];
            if(imgCache&&imgCache.isload){   
                img = imgCache.elem;
            }else{  
                img = new Image();
                img.setAttribute('class', 'previewImage-image');
                _this.imgLoadCache[hash] = {isload:false,elem:img};
                if(i == _this.index){  
                    img.src = v;
                    img.onload = function(){
                        _this.imgLoadCache[hash].isload = true;
                    }
                }
            }
            _this.imgStatusCache[i] = {hash:hash,x:0,m:0,y:0,my:0,scale:_this.scale,scalem:1};
            div.setAttribute('class', 'previewImage-item');
            div.appendChild(img);
            _this.box.appendChild(div);
        })

        this.container.appendChild(this.box);  
        this.container.appendChild(this.text);   
        this.ePage = document.querySelector('.previewImage-text span.page');
        this.ePrev = document.querySelector('.previewImage-text span.prev');
        this.eNext = document.querySelector('.previewImage-text span.next');
        this.eCloser = document.querySelector('.previewImage-text span.exit');

        var offsetX = -this.imageChageMoveX*this.index;  
        this.boxData.x = offsetX;  
        this.containerData.x = 0;  
        this.container.style.display = "block";
        setTimeout(function(){
            _this.setActionStatus();
            if(_this.winw  > 992) {
                _this.urls.forEach(function(v, i) {
                    var that = _this, elImg, currentScale, toX, toY, overrideWidth, overrideHeight;
                    elImg = that.selectorEleAll[i];
                    currentScale = Math.min(that.winw / elImg.naturalWidth, that.winh / elImg.naturalHeight);
                    toX = toY = 0;
                    function imgHeight() {
                        return elImg.naturalHeight * currentScale;
                    }

                    function imgWidth() {
                        return elImg.naturalWidth * currentScale;
                    }

                    toX > 0 && (toX = 0);
                    overrideWidth = Math.round(that.winw - imgWidth());
                    if (toX < overrideWidth) {
                        toX = overrideWidth < 0 ? overrideWidth : overrideWidth / 2;
                    }
                    toY > 0 && (toY = 0);
                    overrideHeight = Math.round(that.winh - imgHeight());
                    if (toY < overrideHeight) {
                        toY = overrideHeight < 0 ? overrideHeight : overrideHeight / 2;
                    }
                    that.imgStatusCache[i].x = toX;
                    that.imgStatusCache[i].y = toY;

                    that.imgStatusCache[i].scale = currentScale;
                    that.translateScale(i, 0);
                });
            }
            _this.translateScale(_this.bIndex,0);
            _this.translateScale(_this.cIndex,_this.openTime);

            _this.isPreview = true;
            _this.ePrev.addEventListener('click', function(e) {
                e.preventDefault();
                _this.prev();
                return false;
            }, false);
            _this.eNext.addEventListener('click', function(e) {
                e.preventDefault();
                _this.next();
                return false;
            }, false);
            _this.eCloser.addEventListener('click', function(e) {
                e.preventDefault();
                _this.closePreview();
                return false;
            }, false);
        },50);
    };
    _picviewer.prototype.start = function(obj){
        this.container.innerHTML = '';
        if (!obj.urls || !_method.isArray(obj.urls) || obj.urls.length == 0) { 
            alert("urls must be a Array and the minimum length more than zero");
            return false;
        }
        if (!obj.current) {
            this.index = 0;
            console.warn("current is empty,it will be the first value of urls!");
        } else {
            var index = obj.urls.indexOf(obj.current);
            if (index < 0) {
                index = 0;
                console.warn("current isnot on urls,it will be the first value of urls!");
            }
            this.index = index; 
        }
        this.selectorEleAll = obj.elems;
        this.urls = obj.urls; 
        this.maxLen = obj.urls.length - 1;
        this.cIndex = this.maxLen + 1; 
        this.bIndex = this.maxLen + 2; 
        this.imgStatusCache = new Object(); 
        this.render(); 
    };
    _picviewer.prototype.init = function(selectorAll){
        var urls = [], _this = this;
        angular.forEach(selectorAll, function(selector, index) {
            urls.push(selector.src);
            selector.addEventListener('click', function() {
                var obj = {
                    elems: selectorAll,
                    urls: urls,
                    current: this.src
                }
                _this.start(obj);
            });
        });
    }
    var picviewer = new _picviewer();

    return picviewer;
}]);

/***/ }),
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


function openPlugin(content, cb) {
    var frag, wrap, frm;
    frag = document.createDocumentFragment();
    wrap = document.createElement('div');
    wrap.setAttribute('id', 'frmPlugin');
    frm = document.createElement('iframe');
    wrap.appendChild(frm);
    wrap.onclick = function() {
        wrap.parentNode.removeChild(wrap);
    };
    frag.appendChild(wrap);
    document.body.appendChild(frag);
    if (content.indexOf('http') === 0) {
        window.onClosePlugin = function() {
            wrap.parentNode.removeChild(wrap);
            cb && cb();
        };
        frm.setAttribute('src', content);
    } else {
        if (frm.contentDocument && frm.contentDocument.body) {
            frm.contentDocument.body.innerHTML = content;
        }
    }
}

var ngMod = angular.module('siteuser.ui.xxt', []);
ngMod.service('tmsSiteUser', function() {
    this.showSwitch = function(siteId, redirect) {
        var eSwitch;
        eSwitch = document.createElement('div');
        eSwitch.classList.add('tms-switch', 'tms-switch-siteuser');
        eSwitch.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            var url = location.protocol + '//' + location.host;
            url += '/rest/site/fe/user';
            url += "?site=" + siteId;
            if (redirect) {
                location.href = url;
            } else {
                openPlugin(url);
            }
        }, true);
        document.body.appendChild(eSwitch);
    }
});

/***/ }),
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "html,body{font-family:Microsoft Yahei,Arial;width:100%;height:100%;}\r\nbody{position:relative;font-size:16px;padding:0;}\r\nheader img,footer img{max-width:100%}\r\n.ng-cloak{display:none;}\r\n.container{position:relative;}\r\n.site-navbar-default .navbar-default .navbar-nav>li>a,.navbar-default .navbar-brand{color:#fff;}\r\n.site-navbar-default .navbar-brand{padding:15px 15px;}\r\n.main-navbar .navbar-brand:hover{color:#fff;}\r\n@media screen and (min-width:768px){\r\n\t.site-navbar-default .navbar-nav>li>a{padding:15px 15px;line-height:1;}\r\n}\r\n@media screen and (max-width:768px){\r\n\t.site-navbar-default .navbar-brand{width:100%;text-align:center;}\r\n\t.site-navbar-default .navbar-brand>.icon-note{display:inline-block;width:124px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}\r\n\t.site-navbar-default .navbar-nav{margin:8px 0;position:absolute;top:0;right:0;}\r\n\t.site-navbar-default .nav>li>a{padding:10px 10px;}\r\n}\r\n.tms-flex-row{display:flex;align-items:center;}\r\n.tms-flex-row .tms-flex-grow{flex:1;}\r\n.dropdown-menu{min-width:auto;}\r\n.dropdown-menu-top{bottom:100%;top:auto;}\r\n\r\n/*picviewer*/\r\n#previewImage-container{-ms-touch-action:none;touch-action:none;-webkit-touch-action:none;line-height:100vh;background-color:#000;width:100vw;height:100vh;position:fixed;overflow:hidden;top:0;left:0;z-index:1050;transition:transform .3s;-ms-transition:transform .3s;-moz-transition:transform .3s;-webkit-transition:transform .3s;-o-transition:transform .3s;transform:translate3d(100%,0,0);-webkit-transform:translate3d(100%,0,0);-ms-transform:translate3d(100%,0,0);-o-transform:translate3d(100%,0,0);-moz-transform:translate3d(100%,0,0)}\r\n#previewImage-container .previewImage-text{position:absolute;bottom:5px;left:8px;right:8px;z-index:1060;height:36px}\r\n.previewImage-text span{display:inline-block;width:36px;height:36px;line-height:25px;border-radius:18px;font-size:25px;text-align:center;color:#bbb}\r\n.previewImage-text span.page{position:absolute;left:50%;margin-left:-18px;font-size:18px}\r\n.previewImage-text span.prev{position:absolute;left:50%;margin-left:-72px}\r\n.previewImage-text span.next{position:absolute;left:50%;margin-left:36px}\r\n.previewImage-text span.exit{position:absolute;right:0}\r\n.previewImage-text span.exit>i{text-shadow:0 0 .1em #fff,-0 -0 .1em #fff}\r\n#previewImage-container .previewImage-box{width:999999rem;height:100vh}\r\n#previewImage-container .previewImage-box .previewImage-item{width:100vw;height:100vh;margin-right:15px;float:left;text-align:center}\r\n@media screen and (min-width:992px){\r\n\t#previewImage-container .previewImage-box .previewImage-item .previewImage-image{display:block;}\r\n}\r\n@media screen and (max-width:992px){\r\n\t#previewImage-container .previewImage-box .previewImage-item .previewImage-image{width:100%}\r\n}\r\n", ""]);

// exports


/***/ }),
/* 14 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(13);
if(typeof content === 'string') content = [[module.i, content, '']];
// Prepare cssTransformation
var transform;

var options = {}
options.transform = transform
// add the styles to the DOM
var update = __webpack_require__(1)(content, options);
if(content.locals) module.exports = content.locals;
// Hot Module Replacement
if(false) {
	// When the styles change, update the <style> tags
	if(!content.locals) {
		module.hot.accept("!!../../../../../../node_modules/css-loader/index.js!./main.css", function() {
			var newContent = require("!!../../../../../../node_modules/css-loader/index.js!./main.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),
/* 15 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('act.ui.xxt', ['ui.bootstrap']);
ngMod.directive('tmsPopAct', ['$templateCache', '$timeout', function($templateCache, $timeout) {
    var html;
    html = "<div class='tms-act-popover-wrap'>";
    html += "<div ng-repeat=\"act in acts\" ng-if=\"!act.toggle||act.toggle()\"><button class='btn btn-default btn-block' ng-click=\"doAct($event,act)\">{{act.title}}</button></div>";
    html += '<div ng-if="custom" class=\"checkbox\"><label style=\"color:#000;\"" ng-click=\"setCustom($event)\"><input type=\"checkbox\" ng-model=\"custom.stopTip\" ng-click=\"setCustom($event)\"> 不再提示</label></div>';
    html += "</div>";
    $templateCache.put('popActTemplate.html', html);
    return {
        restrict: 'A',
        replace: true,
        transclude: true,
        scope: {
            acts: '=acts',
            custom: '=custom'
        },
        template: "<button uib-popover-template=\"'popActTemplate.html'\" popover-placement=\"top-right\" popover-trigger=\"'show'\" popover-append-to-body=\"true\" class=\"tms-act-toggle\" popover-class=\"tms-act-popover\"><span class='glyphicon glyphicon-option-vertical'></span></button>",
        link: function(scope, elem, attrs) {
            var elePopover, fnOpenPopover, fnClosePopover;
            fnOpenPopover = function() {
                var popoverEvt;
                elePopover = elem[0].children[0];
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('show', true, false);
                elePopover.dispatchEvent(popoverEvt);
            };
            fnClosePopover = function() {
                var popoverEvt;
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('hide', true, false);
                elePopover.dispatchEvent(popoverEvt);
                document.body.removeEventListener('click', fnClosePopover);
            };
            elem[0].addEventListener('click', function(event) {
                event.stopPropagation();
                event.preventDefault();
                fnOpenPopover();
                document.body.addEventListener('click', fnClosePopover);
            });
            scope.$watch('custom', function(nv) {
                if (nv && nv.stopTip === false) {
                    fnOpenPopover();
                    document.body.addEventListener('click', fnClosePopover);
                    if (attrs.closeAfter && parseInt(attrs.closeAfter)) {
                        $timeout(function() {
                            fnClosePopover();
                        }, attrs.closeAfter);
                    }
                }
            });
        },
        controller: ['$scope', function($scope) {
            $scope.setCustom = function($event, prop) {
                $event.stopPropagation();
            };
            $scope.doAct = function(event, oAct) {
                if (oAct.func) {
                    oAct.func(event);
                }
            };
        }]
    };
}]);

/***/ }),
/* 16 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('nav.ui.xxt', ['ui.bootstrap']);
ngMod.directive('tmsPopNav', ['$templateCache', '$timeout', function($templateCache, $timeout) {
    var html;
    html = "<div class='tms-nav-target'>";
    html += "<div ng-repeat=\"nav in navs\"><button class='btn btn-default btn-block' ng-click=\"navTo($event,nav)\">{{nav.title}}</button></div>";
    html += '<div ng-if="custom" class=\"checkbox\"><label style=\"color:#000;\"" ng-click=\"setCustom($event)\"><input type=\"checkbox\" ng-model=\"custom.stopTip\" ng-click=\"setCustom($event)\"> 不再提示</label></div>';
    html += "</div>";
    $templateCache.put('popNavTemplate.html', html);
    return {
        restrict: 'A',
        replace: true,
        transclude: true,
        scope: {
            navs: '=navs',
            custom: '=custom'
        },
        template: "<span><span ng-if=\"!navs||navs.length===0\" ng-transclude></span><span ng-if=\"navs.length\" uib-popover-template=\"'popNavTemplate.html'\" popover-placement=\"bottom\" popover-trigger=\"'show'\"><span ng-transclude></span><span class=\"caret\"></span></span></span>",
        link: function(scope, elem, attrs) {
            var elePopover, fnOpenPopover, fnClosePopover;
            fnOpenPopover = function() {
                var popoverEvt;
                elePopover = elem[0].children[0];
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('show', true, false);
                elePopover.dispatchEvent(popoverEvt);
            };
            fnClosePopover = function() {
                var popoverEvt;
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('hide', true, false);
                elePopover.dispatchEvent(popoverEvt);
                document.body.removeEventListener('click', fnClosePopover);
            };
            elem[0].addEventListener('click', function(event) {
                event.stopPropagation();
                event.preventDefault();
                fnOpenPopover();
                document.body.addEventListener('click', fnClosePopover);
            });
            scope.$watch('custom', function(nv) {
                if (nv && nv.stopTip === false) {
                    fnOpenPopover();
                    document.body.addEventListener('click', fnClosePopover);
                    if (attrs.closeAfter && parseInt(attrs.closeAfter)) {
                        $timeout(function() {
                            fnClosePopover();
                        }, attrs.closeAfter);
                    }
                }
            });
        },
        controller: ['$scope', function($scope) {
            $scope.setCustom = function($event, prop) {
                $event.stopPropagation();
            };
            $scope.navTo = function(event, oNav) {
                if (oNav.url) {
                    location.href = oNav.url;
                } else if ($scope.$parent.gotoNav) {
                    $scope.$parent.gotoNav(event, oNav);
                }
            };
        }]
    };
}]);

/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

/**
 * 页面事件追踪
 */
var ngMod = angular.module('trace.ui.xxt', ['http.ui.xxt']);
ngMod.directive('tmsTrace', ['$q', '$timeout', 'http2', function($q, $timeout, http2) {
    var EventInterval = 1000; // 有效的事件间隔
    var IdleInterval = 5000; // 有效的事件间隔
    var StoreKey = '/xxt/site/matter/enroll/trace';
    var TraceEvent = function(start, type, elapse, biz, text) {
        this.type = type;
        this.elapse = elapse || ((new Date * 1) - start);
        this.biz = biz;
        if (text) this.text = text;
    };
    var TraceStack = function() {
        function storeTrace(oTrace) {
            var oStorage, oCached;
            if (oTrace.sendUrl && (oStorage = window.localStorage)) {
                oCached = oStorage.getItem(StoreKey);
                oCached = oCached ? JSON.parse(oCached) : {};
                oCached[oTrace.sendUrl] = oTrace;
                oStorage.setItem(StoreKey, JSON.stringify(oCached));
            }
        }
        this.start = 0;
        this.events = [];
        this.setSendUrl = function(url) {
            this.sendUrl = url;
            storeTrace(this);
        };
        this.pushEvent = function(type, traceBiz, traceText) {
            var oNewEvent, oLastEvent;
            if (this.events.length === 0) {
                this.start = new Date * 1;
                oNewEvent = new TraceEvent(this.start, type, 0, traceBiz, traceText);
                this.events.push(oNewEvent)
                storeTrace(this);
            } else {
                oNewEvent = new TraceEvent(this.start, type, null, traceBiz, traceText);
                oLastEvent = this.events[this.events.length - 1];
                if (oLastEvent.type !== oNewEvent.type || (oNewEvent.elapse - oLastEvent.elapse > EventInterval)) {
                    this.events.push(oNewEvent)
                    storeTrace(this);
                }
            }
        };
        this.stop = function() {
            this.closing = 'Y';
            storeTrace(this);
            this.start = 0;
            this.events = [];
        };
    };
    var IdleWatcher = function(oTraceStack) {
        var _timer;
        this.begin = function() {
            this.cancel(_timer);
            _timer = $timeout(function() {
                /* 指定的时间段内没有发生用户的交互，自动停止事件追踪，并且提交数据 */
                var oStorage, oCached, oCachedTrack;
                oTraceStack.stop();
                if (oTraceStack.sendUrl) {
                    if (oStorage = window.localStorage) {
                        oCached = oStorage.getItem(StoreKey);
                        oCached = JSON.parse(oCached);
                        oCachedTrack = oCached[oTraceStack.sendUrl];
                        delete oCached[oTraceStack.sendUrl];
                        oCached = oStorage.setItem(StoreKey, JSON.stringify(oCached));
                        http2.post(oTraceStack.sendUrl, { start: oCachedTrack.start, events: oCachedTrack.events }, { showProgress: false });
                    }
                }
            }, IdleInterval);
        };
        this.cancel = function() {
            if (_timer) {
                $timeout.cancel(_timer);
                _timer = null;
            }
        }
    };
    /**
     * 如果有已经结束但是没有提交进行提交
     */
    var oStorage, oCached, oTrace;
    if (oStorage = window.localStorage) {
        oCached = oStorage.getItem(StoreKey);
        oCached = oCached ? JSON.parse(oCached) : {};
        if (oCached) {
            for (var i in oCached) {
                if (oCached && oCached[i]) {
                    oTrace = oCached[i];
                    if (oTrace.closing && oTrace.closing === 'Y') {
                        delete oCached[i];
                        oCached = oStorage.setItem(StoreKey, JSON.stringify(oCached));
                        http2.post(oTrace.sendUrl, { start: oTrace.start, events: oTrace.events }).then(function() {});
                    }
                }
            }
        }
    }

    return {
        restrict: 'A',
        link: function(scope, elem, attrs) {
            var oTraceStack = new TraceStack();
            var oIdleWatcher = new IdleWatcher(oTraceStack);
            if (!attrs.readySign && attrs.sendUrl) {
                oTraceStack.sendUrl = attrs.sendUrl;
            }
            /* 打开页面 */
            oTraceStack.pushEvent('load');
            /* 用户点击页面 */
            elem.on('click', function(event) {
                var evtTarget, traceBiz, traceText;
                evtTarget = event.target;
                if (evtTarget.hasAttribute('trace-biz')) {
                    traceBiz = evtTarget.getAttribute('trace-biz');
                    if (!traceBiz && evtTarget.hasAttribute('ng-click')) {
                        traceBiz = evtTarget.getAttribute('ng-click');
                    }
                    if (traceBiz) {
                        traceBiz = traceBiz.replace(/'|"/g, '');
                    }
                    traceText = evtTarget.innerText;
                }
                oTraceStack.pushEvent('click', traceBiz, traceText);
                oIdleWatcher.begin();
            });
            /* 用户点击页面 */
            elem.on('touchend', function(event) {
                oTraceStack.pushEvent('touchend');
                oIdleWatcher.begin();
            });
            /* 用户滚动页面 */
            window.addEventListener('scroll', function(event) {
                oTraceStack.pushEvent('scroll');
                oIdleWatcher.begin();
            });
            /* 离开页面 */
            window.addEventListener('beforeunload', function(event) {
                oTraceStack.pushEvent('beforeunload');
                oTraceStack.stop();
                oIdleWatcher.cancel();
            });
            if (attrs.readySign) {
                scope.$watch(attrs.readySign, function(oSign) {
                    if (oSign) {
                        $timeout(function() {
                            oTraceStack.setSendUrl(attrs.sendUrl);
                        });
                    }
                });
            }
            oIdleWatcher.begin();
        }
    };
}]);

/***/ }),
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(5);
if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage && window.wx) {
    window.wx.ready(function() {
        window.wx.showOptionMenu();
    });
} else if (/YiXin/i.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        YixinJSBridge.call('showOptionMenu');
    }, false);
}

__webpack_require__(8);
__webpack_require__(14);
__webpack_require__(17);
__webpack_require__(6);
__webpack_require__(3);
__webpack_require__(2);
__webpack_require__(12);
__webpack_require__(10);
__webpack_require__(11);
__webpack_require__(16);
__webpack_require__(15);

__webpack_require__(9);
__webpack_require__(19);

/* 公共加载的模块 */
var angularModules = ['ngSanitize', 'ui.bootstrap', 'notice.ui.xxt', 'http.ui.xxt', 'trace.ui.xxt', 'page.ui.xxt', 'snsshare.ui.xxt', 'siteuser.ui.xxt', 'directive.enroll', 'picviewer.ui.xxt', 'nav.ui.xxt', 'act.ui.xxt', 'service.enroll'];
/* 加载指定的模块 */
if (window.moduleAngularModules) {
    window.moduleAngularModules.forEach(function(m) {
        angularModules.push(m);
    });
}

var ngApp = angular.module('app', angularModules);
ngApp.config(['$controllerProvider', '$uibTooltipProvider', '$locationProvider', function($cp, $uibTooltipProvider, $locationProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({ 'show': 'hide' });
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlMain', ['$scope', '$q', '$parse', 'http2', '$timeout', 'tmsLocation', 'tmsDynaPage', 'tmsSnsShare', 'tmsSiteUser', 'enlService', function($scope, $q, $parse, http2, $timeout, LS, tmsDynaPage, tmsSnsShare, tmsSiteUser, enlService) {
    function refreshEntryRuleResult() {
        var url, defer;
        defer = $q.defer();
        url = LS.j('entryRule', 'site', 'app');
        return http2.get(url).then(function(rsp) {
            $scope.params.entryRuleResult = rsp.data;
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
            openPlugin(url, refreshEntryRuleResult);
        }
    };
    $scope.askBecomeMember = function() {
        var url, mschemaIds;
        if ($scope.app.entryRule && $scope.app.entryRule.scope.member === 'Y') {
            mschemaIds = Object.keys($scope.app.entryRule.member);
            if (mschemaIds.length === 1) {
                url = '/rest/site/fe/user/member?site=' + $scope.app.siteid;
                url += '&schema=' + mschemaIds[0];
            } else if (mschemaIds.length > 1) {
                url = '/rest/site/fe/user/memberschema?site=' + $scope.app.siteid;
                url += '&schema=' + mschemaIds.join(',');
            }
            openPlugin(url, refreshEntryRuleResult);
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
    $scope.siteUser = function() {
        var url = location.protocol + '//' + location.host;
        url += '/rest/site/fe/user';
        url += "?site=" + LS.s().site;
        location.href = url;
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
        } else if (page === 'cowork') {
            url += '&ek=' + LS.s().ek;
        }
        rid && (url += '&rid=' + rid);
        page && (url += '&page=' + page);
        newRecord && newRecord === 'Y' && (url += '&newRecord=Y');
        location = url;
        //location.replace(url);
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
    /* 设置限制通讯录访问时的状态*/
    $scope.setOperateLimit = function(operate) {
        if (!$scope.app.entryRule.exclude_action || $scope.app.entryRule.exclude_action[operate] !== "Y") {
            if ($scope.entryRuleResult.passed == 'N') {
                tmsDynaPage.openPlugin($scope.entryRuleResult.passUrl).then(function(data) {
                    location.reload();
                    return true;
                });
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
    /* 设置公众号分享信息 */
    $scope.setSnsShare = function(oRecord, oParams, oData) {
        function fnReadySnsShare() {
            if (window.__wxjs_environment === 'miniprogram') {
                return;
            }
            var oApp, oPage, oUser, sharelink, shareid, shareby, summary;
            oApp = $scope.app;
            oPage = $scope.page;
            oUser = $scope.user;
            /* 设置活动的当前链接 */
            sharelink = location.protocol + '//' + location.host + LS.j('', 'site', 'app', 'rid');
            if (oPage && oPage.share_page && oPage.share_page === 'Y') {
                sharelink += '&page=' + oPage.name;
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
            shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
            sharelink += "&shareby=" + shareid;
            /* 设置分享 */
            summary = oApp.summary;
            if (oPage && oPage.share_summary && oPage.share_summary.length && oRecord && oRecord.data && oRecord.data[oPage.share_summary]) {
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
                    if (oData && oData.title) {
                        url += "&title=" + oData.title;
                    } else {
                        url += "&title=" + oApp.title;
                    }
                    if (oData) {
                        url += "&target_type=" + oData.target_type;
                        url += "&target_id=" + oData.target_id;
                    }
                    url += "&shareby=" + shareby;
                    url += "&shareto=" + shareto;
                    http2.get(url);
                    window.shareCounter++;
                    window.onshare && window.onshare(window.shareCounter);
                },
                jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage', 'getLocation', 'startRecord', 'stopRecord', 'onVoiceRecordEnd', 'playVoice', 'pauseVoice', 'stopVoice', 'onVoicePlayEnd', 'uploadVoice', 'downloadVoice']
            });
            tmsSnsShare.set(oApp.title, sharelink, summary, oApp.pic);
        }
        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            if (!window.WeixinJSBridge || !WeixinJSBridge.invoke) {
                document.addEventListener('WeixinJSBridgeReady', fnReadySnsShare, false);
            } else {
                fnReadySnsShare();
            }
        }
    };
    /* 设置页面操作 */
    $scope.setPopAct = function(aNames, fromPage, oParamsByAct) {
        if (!fromPage || !aNames || aNames.length === 0) return;
        if ($scope.user) {
            var oEnlUser, oCustom;
            if (oEnlUser = $scope.user.enrollUser) {
                oCustom = $parse(fromPage + '.act')(oEnlUser.custom);
            }
            if (!oCustom) {
                oCustom = { stopTip: false };
            }
            $scope.popAct = {
                acts: [],
                custom: oCustom
            };
            $scope.$watch('popAct.custom', function(nv, ov) {
                var oCustom;
                if (oEnlUser) {
                    oCustom = oEnlUser.custom;
                    if (nv !== ov) {
                        if (!oCustom[fromPage]) { oCustom[fromPage] = {}; }
                        oCustom[fromPage].act = $scope.popAct.custom;
                        http2.post(LS.j('user/updateCustom', 'site', 'app'), oCustom).then(function(rsp) {});
                    }
                }
            }, true);
            aNames.forEach(function(name) {
                var oAct;
                switch (name) {
                    case 'save':
                        oAct = { title: '保存' };
                        break;
                    case 'addRecord':
                        if ($scope.app && oEnlUser) {
                            if (parseInt($scope.app.count_limit) === 0 || $scope.app.count_limit > oEnlUser.enroll_num) {
                                /* 允许添加记录 */
                                if ($parse('actionRule.record.submit.pre.editor')($scope.app)) {
                                    if (oEnlUser && oEnlUser.is_editor && oEnlUser.is_editor === 'Y') {
                                        oAct = { title: '添加记录', func: $scope.addRecord };
                                    }
                                } else {
                                    oAct = { title: '添加记录', func: $scope.addRecord };
                                }
                            }
                        }
                        break;
                    case 'newRecord':
                        oAct = { title: '添加记录' };
                        break;
                    case 'voteRecData':
                        oAct = { title: '题目投票' };
                        break;
                    case 'scoreSchema':
                        oAct = { title: '题目打分' };
                        break;
                }
                if (oAct) {
                    if (oParamsByAct) {
                        if (oParamsByAct.func)
                            if (oParamsByAct.func[name])
                                oAct.func = oParamsByAct.func[name];
                        if (!oAct.func && $scope[name])
                            oAct.func = $scope[name];
                        if (oParamsByAct.toggle)
                            if (oParamsByAct.toggle[name])
                                oAct.toggle = oParamsByAct.toggle[name];
                    }
                    $scope.popAct.acts.push(oAct);
                }
            });
        }
    };
    /* 设置弹出导航页 */
    $scope.setPopNav = function(aNames, fromPage, oUser) {
        if (!fromPage || !aNames || aNames.length === 0) return;
        if ($scope.user) {
            var oApp, oEnlUser, oCustom;
            oApp = $scope.app;
            oEnlUser = $scope.user.enrollUser;
            if (oEnlUser) {
                oCustom = $parse(fromPage + '.nav')(oEnlUser.custom);
            }
            if (!oCustom) {
                oCustom = { stopTip: false };
            }
            /*设置页面导航*/
            $scope.popNav = {
                navs: [],
                custom: oCustom
            };
            $scope.$watch('popNav.custom', function(nv, ov) {
                var oCustom;
                if (oEnlUser) {
                    oCustom = oEnlUser.custom;
                    if (nv !== ov) {
                        if (!oCustom[fromPage]) { oCustom[fromPage] = {}; }
                        oCustom[fromPage].nav = $scope.popNav.custom;
                        http2.post(LS.j('user/updateCustom', 'site', 'app'), oCustom).then(function(rsp) {});
                    }
                }
            }, true);
            if (oApp.scenario === 'voting' && aNames.indexOf('votes') !== -1) {
                $scope.popNav.navs.push({ name: 'votes', title: '投票榜', url: LS.j('', 'site', 'app') + '&page=votes' });
            }
            if (oApp.scenarioConfig) {
                if (oApp.scenarioConfig.can_repos === 'Y' && aNames.indexOf('repos') !== -1) {
                    $scope.popNav.navs.push({ name: 'repos', title: '共享页', url: LS.j('', 'site', 'app') + '&page=repos' });
                }
                if (oApp.scenarioConfig.can_rank === 'Y' && aNames.indexOf('rank') !== -1) {
                    $scope.popNav.navs.push({ name: 'rank', title: '排行页', url: LS.j('', 'site', 'app') + '&page=rank' });
                }
                if (oApp.scenarioConfig.can_stat === 'Y' && fromPage !== 'stat') {
                    $scope.popNav.navs.push({ name: 'stat', title: '统计页', url: LS.j('', 'site', 'app') + '&page=stat' });
                }
                if (oApp.scenarioConfig.can_kanban === 'Y' && aNames.indexOf('kanban') !== -1) {
                    $scope.popNav.navs.push({ name: 'kanban', title: '看板页', url: LS.j('', 'site', 'app') + '&page=kanban' });
                }
                if (oApp.scenarioConfig.can_action === 'Y' && aNames.indexOf('event') !== -1) {
                    $scope.popNav.navs.push({ name: 'event', title: '动态页', url: LS.j('', 'site', 'app') + '&page=event' });
                }
            }
            if (aNames.indexOf('favor') !== -1) {
                $scope.popNav.navs.push({ name: 'favor', title: '收藏页', url: LS.j('', 'site', 'app') + '&page=favor' });
            }
            if (aNames.indexOf('task') !== -1 && (oApp.questionConfig.length || oApp.answerConfig.length || oApp.voteConfig.length || oApp.scoreConfig.length)) {
                $scope.popNav.navs.push({ name: 'task', title: '任务页', url: LS.j('', 'site', 'app') + '&page=task' });
            }
            if ($scope.mission) {
                $scope.popNav.navs.push({ name: 'mission', title: '项目主页', url: '/rest/site/fe/matter/mission?site=' + oApp.siteid + '&mission=' + $scope.mission.id });
            }
        }
        // if (oApp.scenarioConfig.can_action === 'Y') {
        //        /* 设置活动事件提醒 */
        //        http2.get(LS.j('notice/count', 'site', 'app')).then(function(rsp) {
        //            $scope.noticeCount = rsp.data;
        //        });
        //        oAppNavs.event = {};
        //        oApp.length++;
        //    }
    };
    /* 设置记录阅读日志信息 */
    $scope.logAccess = function(oParams) {
        var oApp, oUser, activeRid, oData, shareby;
        oApp = $scope.app;
        oUser = $scope.user;
        activeRid = oApp.appRound.rid;
        shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
        oData = {
            search: location.search.replace('?', ''),
            referer: document.referrer,
            rid: activeRid,
            assignedNickname: oUser.nickname,
            id: oApp.id,
            type: 'enroll',
            title: oApp.title,
            shareby: shareby
        }

        if (oParams) {
            if (oParams.title) { oData.title = oParams.title; }
            oData.target_type = oParams.target_type;
            oData.target_id = oParams.target_id;
        }
        http2.post('/rest/site/fe/matter/logAccess?site=' + oApp.siteid, oData);
    };
    $scope.isSmallLayout = false;
    if (window.screen && window.screen.width < 992) {
        $scope.isSmallLayout = true;
    }
    http2.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).then(function success(rsp) {
        var params = rsp.data,
            oSite = params.site,
            oApp = params.app,
            oEntryRuleResult = params.entryRuleResult,
            oMission = params.mission,
            oPage = params.page,
            schemasById = {};

        oApp.dynaDataSchemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        oApp._schemasById = schemasById;
        $scope.params = params;
        $scope.site = oSite;
        $scope.mission = oMission;
        $scope.app = oApp;
        $scope.entryRuleResult = oEntryRuleResult;
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
            angular.forEach(tasksOfOnReady, execTask);
        }
        /* 用户信息 */
        enlService.user().then(function(data) {
            $scope.user = data;
            $timeout(function() {
                $scope.$broadcast('xxt.app.enroll.ready', params);
            });
            var eleLoading;
            if (eleLoading = document.querySelector('.loading')) {
                eleLoading.parentNode.removeChild(eleLoading);
            }
        });
    });
}]);
module.exports = ngApp;

/***/ }),
/* 19 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('service.enroll', []);
ngMod.service('enlService', ['$q', 'http2', 'tmsLocation', function($q, http2, LS) {
    var _self, _getUserDeferred;
	_self = this;
	_getUserDeferred = false;

	this.user = function() {
		if (_getUserDeferred) {
            return _getUserDeferred.promise;
        }
        _getUserDeferred = $q.defer();
        http2.get(LS.j('user/get2', 'site', 'app')).then(function(rsp) {
            _getUserDeferred.resolve(rsp.data);
        });

        return _getUserDeferred.promise;
	}
}]);

/***/ }),
/* 20 */,
/* 21 */,
/* 22 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('schema.ui.xxt', []);
ngMod.service('tmsSchema', ['$filter', '$sce', '$parse', function($filter, $sce, $parse) {
    var _that = this,
        _mapOfSchemas;
    this.config = function(schemas) {
        if (angular.isString(schemas)) {
            schemas = JSON.parse(schemas);
        }
        if (angular.isArray(schemas)) {
            _mapOfSchemas = {};
            schemas.forEach(function(schema) {
                _mapOfSchemas[schema.id] = schema;
            });
        } else {
            _mapOfSchemas = schemas;
        }
    };
    this.isEmpty = function(oSchema, value) {
        if (value === undefined) {
            return true;
        }
        switch (oSchema.type) {
            case 'multiple':
                for (var p in value) {
                    //至少有一个选项
                    if (value[p] === true) {
                        return false;
                    }
                }
                return true;
            default:
                return value.length === 0;
        }
    };
    this.checkRequire = function(oSchema, value) {
        if (value === undefined || this.isEmpty(oSchema, value)) {
            return '请填写必填题目［' + oSchema.title + '］';
        }
        return true;
    };
    this.checkFormat = function(oSchema, value) {
        if (oSchema.format === 'number') {
            if (!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
                return '题目［' + oSchema.title + '］请输入数值';
            }
        } else if (oSchema.format === 'name') {
            if (value.length < 2) {
                return '题目［' + oSchema.title + '］请输入正确的姓名（不少于2个字符）';
            }
        } else if (oSchema.format === 'mobile') {
            if (!/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9]|9[0-9])\d{8}$/.test(value)) {
                return '题目［' + oSchema.title + '］请输入正确的手机号（11位数字）';
            }
        } else if (oSchema.format === 'email') {
            if (!/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/.test(value)) {
                return '题目［' + oSchema.title + '］请输入正确的邮箱';
            }
        }
        return true;
    };
    this.checkCount = function(oSchema, value) {
        if (oSchema.count != 0 && oSchema.count !== undefined && value.length > oSchema.count) {
            return '［' + oSchema.title + '］超出上传数量（' + oSchema.count + '）限制';
        }
        return true;
    };
    this.checkValue = function(oSchema, value) {
        var sCheckResult;
        if (oSchema.required && oSchema.required === 'Y') {
            if (true !== (sCheckResult = this.checkRequire(oSchema, value))) {
                return sCheckResult;
            }
        }
        if (value) {
            if (oSchema.type === 'shorttext' && oSchema.format) {
                if (true !== (sCheckResult = this.checkFormat(oSchema, value))) {
                    return sCheckResult;
                }
            }
            if (oSchema.type === 'multiple' && oSchema.limitChoice === 'Y' && oSchema.range) {
                var opCount = 0;
                for (var i in value) {
                    if (value[i]) {
                        opCount++;
                    }
                }
                if (opCount < oSchema.range[0] || opCount > oSchema.range[1]) {
                    return '【' + oSchema.title + '】中最多只能选择(' + oSchema.range[1] + ')项，最少需要选择(' + oSchema.range[0] + ')项';
                }
            }
            if (/image|file/.test(oSchema.type) && oSchema.count) {
                if (true !== (sCheckResult = this.checkCount(oSchema, value))) {
                    return sCheckResult;
                }
            }
        }
        return true;
    };
    this.loadRecord = function(schemasById, dataOfPage, dataOfRecord) {
        if (!dataOfRecord) return false;
        var p, value;
        for (p in dataOfRecord) {
            if (p === 'member') {
                dataOfPage.member = angular.extend(dataOfPage.member, dataOfRecord.member);
            } else if (schemasById[p] !== undefined) {
                var schema = schemasById[p];
                if (/score|url/.test(schema.type)) {
                    dataOfPage[p] = dataOfRecord[p];
                } else if (dataOfRecord[p].length) {
                    if (schemasById[p].type === 'image') {
                        value = dataOfRecord[p].split(',');
                        dataOfPage[p] = [];
                        for (var i in value) {
                            dataOfPage[p].push({
                                imgSrc: value[i]
                            });
                        }
                    } else if (schemasById[p].type === 'multiple') {
                        value = dataOfRecord[p].split(',');
                        dataOfPage[p] = {};
                        for (var i in value) dataOfPage[p][value[i]] = true;
                    } else {
                        dataOfPage[p] = dataOfRecord[p];
                    }
                }
            }
        }
        return true;
    };
    /**
     * 给页面中的提交数据填充用户通讯录数据
     */
    this.autoFillMember = function(schemasById, oUser, oPageDataMember) {
        if (oUser.members) {
            angular.forEach(schemasById, function(oSchema) {
                if (oSchema.mschema_id && oUser.members[oSchema.mschema_id]) {
                    var oMember, attr, val;
                    oMember = oUser.members[oSchema.mschema_id];
                    attr = oSchema.id.split('.');
                    if (attr.length === 2) {
                        oPageDataMember[attr[1]] = oMember[attr[1]];
                    } else if (attr.length === 3 && oMember.extattr) {
                        if (!oPageDataMember.extattr) {
                            oPageDataMember.extattr = {};
                        }
                        switch (oSchema.type) {
                            case 'multiple':
                                val = oMember.extattr[attr[2]];
                                if (angular.isObject(val)) {
                                    oPageDataMember.extattr[attr[2]] = {};
                                    for (var p in val) {
                                        if (val[p]) {
                                            oPageDataMember.extattr[attr[2]][p] = true;
                                        }
                                    }
                                }
                                break;
                            default:
                                oPageDataMember.extattr[attr[2]] = oMember.extattr[attr[2]];
                        }
                    }
                }
            });
        }
    };
    /**
     * 给页面中的提交数据填充题目默认值
     */
    this.autoFillDefault = function(schemasById, oPageData) {
        angular.forEach(schemasById, function(oSchema) {
            if (oSchema.defaultValue && oPageData[oSchema.id] === undefined) {
                oPageData[oSchema.id] = oSchema.defaultValue;
            }
        });
    };
    this.value2Text = function(oSchema, value) {
        var label, aVal, aLab = [];

        if (label = value) {
            if (oSchema.ops && oSchema.ops.length) {
                if (oSchema.type === 'single') {
                    for (var i = 0, ii = oSchema.ops.length; i < ii; i++) {
                        if (oSchema.ops[i].v === label) {
                            label = oSchema.ops[i].l;
                            break;
                        }
                    }
                } else if (oSchema.type === 'multiple') {
                    aVal = [];
                    for (var k in label) {
                        if (label[k]) {
                            aVal.push(k);
                        }
                    }
                    oSchema.ops.forEach(function(op) {
                        aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                    });
                    label = aLab.join(',');
                }
            }
        } else {
            label = '';
        }
        return label;
    };
    this.value2Html = function(oSchema, val) {
        if (!val || !oSchema) return '';

        if (oSchema.ops && oSchema.ops.length) {
            if (oSchema.type === 'score') {
                var label = '';
                oSchema.ops.forEach(function(op, index) {
                    if (val[op.v] !== undefined) {
                        label += '<div>' + op.l + ':' + val[op.v] + '</div>';
                    }
                });
                label = label.replace(/\s\/\s$/, '');
                return label;
            } else if (angular.isString(val)) {
                var aVal, aLab = [];
                aVal = val.split(',');
                oSchema.ops.forEach(function(op, i) {
                    aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                });
                if (aLab.length) return aLab.join(',');
            } else if (angular.isObject(val) || angular.isArray(val)) {
                val = JSON.stringify(val);
            }
        }
        return val;
    };
    this.txtSubstitute = function(oTxtData) {
        return oTxtData.replace(/\n/g, '<br>');
    };
    this.urlSubstitute = function(oUrlData) {
        var text;
        text = '';
        if (oUrlData) {
            if (oUrlData.title) {
                text += '【' + oUrlData.title + '】';
            }
            if (oUrlData.description) {
                text += oUrlData.description;
            }
        }
        text += '<a href="' + oUrlData.url + '">网页链接</a>';

        return text;
    };
    this.optionsSubstitute = function(oSchema, value) {
        var val, aVal, aLab = [];
        if (val = value) {
            if (oSchema.ops && oSchema.ops.length) {
                if (oSchema.type === 'score') {
                    var label = '', flag = false;
                    oSchema.ops.forEach(function(op, index) {
                        if (val[op.v] !== undefined) {
                            label += '<div>' + op.l + ':' + val[op.v] + '</div>';
                            flag = false;
                        } else {
                            return flag = true;
                        }
                    });
                    label = flag ? val : label.replace(/\s\/\s$/, '');
                    return label;
                } else if (oSchema.type === 'single' || oSchema.type === 'multiple') {
                    if (angular.isString(val)) {
                        aVal = val.split(',');
                        oSchema.ops.forEach(function(op) {
                            aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                        });
                        val = aLab.join(',');
                    } else {
                        return val;
                    }
                } else if (angular.isObject(val) || angular.isArray(val)) {
                    val = JSON.stringify(val);
                }
            }
        } else {
            val = '';
        }
        return val;
    };
    this.forTable = function(record, mapOfSchemas) {
        function _memberAttr(oMember, oSchema) {
            var keys, originalValue, afterValue;
            if (oMember) {
                keys = oSchema.id.split('.');
                if (keys.length === 2) {
                    return oMember[keys[1]];
                } else if (keys.length === 3 && oMember.extattr) {
                    if (originalValue = oMember.extattr[keys[2]]) {
                        switch (oSchema.type) {
                            case 'single':
                                if (oSchema.ops && oSchema.ops.length) {
                                    for (var i = oSchema.ops.length - 1; i >= 0; i--) {
                                        if (originalValue === oSchema.ops[i].v) {
                                            afterValue = oSchema.ops[i].l;
                                        }
                                    }
                                }
                                break;
                            case 'multiple':
                                if (oSchema.ops && oSchema.ops.length) {
                                    afterValue = [];
                                    oSchema.ops.forEach(function(op) {
                                        originalValue[op.v] && afterValue.push(op.l);
                                    });
                                    afterValue = afterValue.join(',');
                                }
                                break;
                            default:
                                afterValue = originalValue;
                        }
                    }
                    return afterValue;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        function _forTable(oRecord, mapOfSchemas) {
            var oSchema, type, data = {};
            if (oRecord.data && mapOfSchemas) {
                for (var schemaId in mapOfSchemas) {
                    oSchema = mapOfSchemas[schemaId];
                    type = oSchema.type;
                    /* 分组活动导入数据时会将member题型改为shorttext题型 */
                    if (oSchema.mschema_id && oRecord.data.member) {
                        type = 'member';
                    }
                    switch (type) {
                        case 'image':
                            var imgs;
                            if (oRecord.data[oSchema.id]) {
                                if (angular.isString(oRecord.data[oSchema.id])) {
                                    imgs = oRecord.data[oSchema.id].split(',')
                                } else {
                                    imgs = oRecord.data[oSchema.id];
                                }
                            } else {
                                imgs = [];
                            }
                            data[oSchema.id] = imgs;
                            break;
                        case 'file':
                        case 'voice':
                            var files = oRecord.data[oSchema.id] ? oRecord.data[oSchema.id] : {};
                            data[oSchema.id] = files;
                            break;
                        case 'multitext':
                            var multitexts;
                            if (multitexts = oRecord.data[oSchema.id]) {
                                /* 为什么需要进行两次转换？ */
                                if (angular.isString(multitexts)) {
                                    multitexts = JSON.parse(multitexts);
                                    if (angular.isString(multitexts)) {
                                        multitexts = JSON.parse(multitexts);
                                    }
                                }
                            } else {
                                multitexts = [];
                            }
                            data[oSchema.id] = multitexts;
                            break;
                        case 'date':
                            data[oSchema.id] = (oRecord.data[oSchema.id] && angular.isNumber(oRecord.data[oSchema.id])) ? oRecord.data[oSchema.id] : 0;
                            break;
                        case 'url':
                            data[oSchema.id] = oRecord.data[oSchema.id];
                            if (data[oSchema.id]) {
                                data[oSchema.id]._text = '【' + data[oSchema.id].title + '】' + data[oSchema.id].description;
                            }
                            break;
                        default:
                            try {
                                if (/^member\./.test(oSchema.id)) {
                                    data[oSchema.id] = _memberAttr(oRecord.data.member, oSchema);
                                } else {
                                    var htmlVal = _that.value2Html(oSchema, oRecord.data[oSchema.id]);
                                    data[oSchema.id] = angular.isString(htmlVal) ? $sce.trustAsHtml(htmlVal) : htmlVal;
                                }
                            } catch (e) {
                                console.log(e, oSchema, oRecord.data[oSchema.id]);
                            }
                    }
                };
                oRecord._data = data;
            }
            return oRecord;
        }
        var map;
        if (mapOfSchemas && angular.isArray(mapOfSchemas)) {
            map = {};
            mapOfSchemas.forEach(function(oSchema) {
                map[oSchema.id] = oSchema;
            });
            mapOfSchemas = map;
        }
        return _forTable(record, mapOfSchemas ? mapOfSchemas : _mapOfSchemas);
    };
    this.forEdit = function(schema, data) {
        if (schema.type === 'file') {
            var files;
            if (data[schema.id] && data[schema.id].length) {
                files = data[schema.id];
                files.forEach(function(file) {
                    if (file.url && angular.isString(file.url)) {
                        file.url && $sce.trustAsUrl(file.url);
                    }
                });
            }
            data[schema.id] = files;
        } else if (schema.type === 'multiple') {
            var obj = {},
                value;
            if (data[schema.id] && data[schema.id].length) {
                value = data[schema.id].split(',')
                value.forEach(function(p) {
                    obj[p] = true;
                });
            }
            data[schema.id] = obj;
        } else if (schema.type === 'image') {
            var value = data[schema.id],
                obj = [];
            if (value && value.length) {
                value = value.split(',');
                value.forEach(function(p) {
                    obj.push({
                        imgSrc: p
                    });
                });
            }
            data[schema.id] = obj;
        }

        return data;
    };
    /* 将1条记录的所有指定题目的数据变成字符串 */
    this.strRecData = function(oRecData, schemas, oOptions) {
        var str, schemaData, fnSchemaFilter, fnDataFilter;

        if (!schemas || schemas.length === 0) {
            return '';
        }

        if (oOptions) {
            if (oOptions.fnSchemaFilter)
                fnSchemaFilter = oOptions.fnSchemaFilter;
            if (oOptions.fnDataFilter)
                fnDataFilter = oOptions.fnDataFilter;
        }

        str = '';
        schemas.forEach(function(oSchema) {
            if (!fnSchemaFilter || fnSchemaFilter(oSchema)) {
                schemaData = $parse(oSchema.id)(oRecData);
                switch (oSchema.type) {
                    case 'image':
                        if (schemaData && schemaData.length) {
                            str += '<span>';
                            schemaData.forEach(function(imgSrc) {
                                str += '<img src="' + imgSrc + '" />';
                            });
                            str += '</span>';
                        }
                        break;
                    case 'file':
                        if (schemaData && schemaData.length) {
                            schemaData.forEach(function(oFile) {
                                str += '<span><a href="' + oFile.url + '" target="_blank">' + oFile.name + '</a></span>';
                            });
                        }
                        break;
                    case 'date':
                        if (schemaData > 0) {
                            str = '<span>' + $filter('date')(schemaData * 1000, 'yy-MM-dd HH:mm') + '</span>';
                        }
                        break;
                    case 'shortext':
                    case 'longtext':
                        str += schemaData;
                        break;
                    case 'multitext':
                        if (schemaData && schemaData.length) {
                            for (var i = schemaData.length - 1; i >= 0; i--) {
                                if (!fnDataFilter || fnDataFilter(schemaData[i].id)) {
                                    str += schemaData[i].value;
                                }
                            }
                        }
                        break;
                }
            }
        });

        return str;
    };
    /**
     * 通信录记录中的扩展属性转化为用户可读内容
     */
    this.member = {
        getExtattrsUIValue: function(schemas, oMember) {
            var oExtattrUIValue = {};

            schemas.forEach(function(oExtAttr) {
                if (/single|multiple/.test(oExtAttr.type)) {
                    if (oMember.extattr[oExtAttr.id]) {
                        oExtattrUIValue[oExtAttr.id] = _that.value2Text(oExtAttr, oMember.extattr[oExtAttr.id]);
                    }
                } else {
                    oExtattrUIValue[oExtAttr.id] = oMember.extattr[oExtAttr.id];
                }
            });

            return oExtattrUIValue;
        }
    };
}]);

/***/ }),
/* 23 */,
/* 24 */,
/* 25 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

window.xxt === undefined && (window.xxt = {});
window.xxt.image = {
    options: {},
    choose: function(deferred, from) {
        var promise, imgs = [];
        promise = deferred.promise;
        // if (window.wx !== undefined) {
        //     window.wx.chooseImage({
        //         success: function(res) {
        //             var i, img;
        //             for (i in res.localIds) {
        //                 img = {
        //                     imgSrc: res.localIds[i]
        //                 };
        //                 imgs.push(img);
        //             }
        //             deferred.resolve(imgs);
        //         }
        //     });
        // } else
        if (window.YixinJSBridge) {
            window.YixinJSBridge.invoke(
                'pickImage', {
                    type: from,
                    quality: 100
                },
                function(result) {
                    var img;
                    if (result.data && result.data.length) {
                        img = {
                            imgSrc: 'data:' + result.mime + ';base64,' + result.data
                        };
                        imgs.push(img);
                    }
                    deferred.resolve(imgs);
                });
        } else {
            var ele = document.createElement('input');
            ele.setAttribute('type', 'file');
            ele.addEventListener('change', function(evt) {
                var i, cnt, f, type;
                cnt = evt.target.files.length;
                for (i = 0; i < cnt; i++) {
                    f = evt.target.files[i];
                    type = {
                        ".jp": "image/jpeg",
                        ".pn": "image/png",
                        ".gi": "image/gif"
                    }[f.name.match(/\.(\w){2}/g)[0] || ".jp"];
                    f.type2 = f.type || type;
                    var reader = new FileReader();
                    reader.onload = (function(theFile) {
                        return function(e) {
                            var img = {};
                            img.imgSrc = e.target.result.replace(/^.+(,)/, "data:" + theFile.type2 + ";base64,");
                            imgs.push(img);
                            document.body.removeChild(ele);
                            deferred.resolve(imgs);
                        };
                    })(f);
                    reader.readAsDataURL(f);
                }
            }, false);
            ele.style.opacity = 0;
            document.body.appendChild(ele);
            ele.click();
        }
        return promise;
    },
    paste: function(oDiv, deferred, from) {
        var promise, imgs=[];
        promise = deferred.promise;
        oDiv.focus();
        function imgReader(item) {
            var blob = item.getAsFile(), reader = new FileReader();
            reader.onload = function(e){
                var img = {};
                img.imgSrc = e.target.result;
                imgs.push(img);
                deferred.resolve(imgs);
            };
            reader.readAsDataURL(blob);
        };
        oDiv.addEventListener('paste', function(event) {
            // 通过事件对象访问系统剪贴板
            var clipboardData = event.clipboardData, items, item;
            if (clipboardData) {
                items = clipboardData.items;
                if(items && items.length) {
                    for(var i=0; i<clipboardData.types.length; i++) {
                        if(clipboardData.types[i] === 'Files') {
                            item = items[i];
                            break;
                        }
                    }
                    if( item && item.kind === 'file' && item.type.match(/^image\//i) ){ 
                        imgReader(item); 
                    } 
                }
            }
        });
        return promise;
    },
    wxUpload: function(deferred, img) {
        var promise;
        promise = deferred.promise;
        if (0 === img.imgSrc.indexOf('weixin://') || 0 === img.imgSrc.indexOf('wxLocalResource://')) {
            window.wx.uploadImage({
                localId: img.imgSrc,
                isShowProgressTips: 1,
                success: function(res) {
                    img.serverId = res.serverId;
                    deferred.resolve(img);
                }
            });
        } else {
            deferred.resolve(img);
        }
        return promise;
    }
};


/***/ }),
/* 26 */,
/* 27 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "img{max-width:100%}hr{margin:12px 0 12px}p{word-break:break-all}blockquote{font-size:16px;margin-bottom:8px}button.option{padding:0}.navbar-header .page-title{display:inline-block}.navbar-header .page-title .notice-count{display:inline-block;background:red;color:#fff;line-height:14px;min-width:14px;border-radius:7px;font-size:8px;vertical-align:middle;margin-left:4px}.navbar-header .page-title .caret{margin-left:4px}.tms-nav-target .btn .notice-count{background:red;color:#fff;line-height:14px;min-width:14px;border-radius:7px;font-size:8px;vertical-align:middle;margin-left:4px}body.enroll-repos{display:flex;flex-direction:column}body.enroll-repos .app{flex-grow:1;display:flex;flex-direction:column}body.enroll-repos .app .row{flex-grow:1;display:flex}body.enroll-repos .app .row .main{flex-grow:1;display:flex}body.enroll-repos .app .row .main .wrap{flex-grow:1;display:flex;flex-direction:column}body.enroll-repos .app .row .main .wrap #repos{flex-grow:1;display:flex;flex-direction:column;overflow-y:auto}#advCriteria{width:200px;height:100%;padding:0;cursor:pointer;z-index:3}#advCriteria .tree{border-radius:3px;margin-bottom:8px;border:1px solid #d3d3d3}#advCriteria .tree .notClick{pointer-events:none;opacity:.5}#advCriteria .tree .tree-header{height:28px;padding:6px 10px;line-height:26px;font-weight:700;font-size:16px;background-color:#f1f1f1;border-bottom:1px solid #d3d3d3;box-sizing:content-box}#advCriteria .tree .tree-body{width:100%;height:45vh;color:#000;background-color:#fff;position:relative}#advCriteria .tree .tree-body *{box-sizing:content-box}#advCriteria .tree .tree-body .tree-wrap{height:100%;overflow:hidden}#advCriteria .tree .tree-body .tree-wrap .tree-inner{margin-right:-25px;padding-right:25px;overflow-y:auto;height:100%}#advCriteria .tree .tree-body .tree-wrap .item{height:26px;line-height:26px;font-size:16px;padding:6px 10px}#advCriteria .tree .tree-body .tree-wrap .item .item-label{width:90%;height:100%;float:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}#advCriteria .tree .tree-body .tree-wrap .item .item-icon{float:right;margin-top:4px}#advCriteria .tree .tree-body .tree-wrap .item.active{color:#ff8018}#advCriteria .tree .tree-body .tree-wrap .item-children{position:absolute;top:-1px;left:100%;width:200px;height:100%;background-color:#fff;border:1px solid #d3d3d3}#advCriteria .tree .tree-body .tree-wrap .tree-bottom{width:100%;text-align:center;position:absolute;bottom:0;background:#f1f1f1}#filterQuick{margin-bottom:3px;position:relative;background-color:#fff}#filterQuick>*{display:inline-block}#filterQuick.uib-dropdown-open>.tmsFilter .glyphicon-menu-up{transform:rotate(0)}#filterQuick .site-dropdown-list{width:100%!important;right:0!important;left:auto!important;background-color:#f5f5f5;padding:0}#filterQuick .site-dropdown-list .dropdown-search{position:relative}#filterQuick .site-dropdown-list .dropdown-search .btn{position:absolute;top:0;right:0}#filterQuick .site-dropdown-list .dropdown-list-wrapper{width:100%;height:25rem;overflow:hidden}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset{display:flex;height:100%}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset>*{overflow-y:auto;border:0 transparent}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills{width:8rem}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills a{color:#333}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills div.checked{color:#ff8018}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills div.checked:after{content:'.';color:#ff8018;position:absolute;top:0;left:5px;font-size:20px}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills li.active a,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills li.active a:focus,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills li.active a:hover{color:#ff8018;background-color:#fff}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content{flex:1;background-color:#fff}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item{border:0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item:first-child{border-top-left-radius:0;border-top-right-radius:0}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item.active,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item.active:focus,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item.active:hover{color:#ff8018;background-color:#fff;border-color:#fff}#filterQuick .site-dropdown-list .dropdown-list-btn{width:100%;display:flex}#filterQuick .site-dropdown-list .dropdown-list-btn button{flex:1}.site-dropdown{display:inline-block;background-color:#fff}.site-dropdown .site-dropdown-title{display:block;padding:0 10px;font-size:14px;color:#333;min-height:40px;line-height:40px}.site-dropdown .glyphicon-menu-up{transform:rotate(-180deg)}.site-dropdown.open .glyphicon-menu-up{transform:rotate(0)}.site-dropdown a:focus,.site-dropdown a:hover{text-decoration:none}.site-dropdown a.active{color:#ff8018}#filterTip{margin:4px 8px;padding:4px 0}#filterTip>*{display:inline-block;padding:4px 8px}#filterTip>* .close{margin-left:4px}#filterTip>*+*{margin-left:4px}.record,.topic{background:#fff;border-bottom:1px solid #ddd;padding:8px 16px}.record>*{margin:8px 0}.record .data blockquote .schema+.schema{margin-top:8px;padding-top:8px;border-top:1px dashed #ddd}.record .data blockquote .schema>div+div{margin-top:4px}.record .data .datetime{font-size:.8em}.record .tags>button+button{margin-left:4px}.record .remarks{font-size:.9em;border-top:1px dashed #ddd;margin-top:16px;padding:1rem 0 0 2rem;position:relative}.record .remarks:before{position:absolute;left:50%;margin-left:-2em;top:-.7em;color:#999}.record .remarks .remark .top-bar{display:flex}.record .remarks .remark .top-bar>:first-child{flex:1}.record .remarks .remark+.remark{margin-bottom:1em}.record .remarks.agreed:before{content:'\\63A8\\8350\\7559\\8A00'}.record .remarks.round:before{content:'\\8F6E\\6B21\\7559\\8A00'}.top-bar{display:flex}.top-bar .seq{margin-right:8px}.top-bar .label{padding-top:.3em}.top-bar .nickname{flex-grow:1;text-align:left;font-size:.9em}.top-bar>*+*{padding-left:8px}.data-title:before{content:' ';display:inline-block;width:12px;height:12px;border-top:6px solid #fff;border-right:6px solid #fff;border-bottom:6px solid #fff;border-left:6px solid #ff8018}.bottom-bar{display:flex;align-items:center;font-size:.9em;color:#777}.bottom-bar>*+*{margin-left:16px}.bottom-bar>*+* .like{color:#ff8018}.bottom-bar>:first-child{flex:1}.bottom-bar a{text-decoration:none;color:#777}.bottom-bar .btn-default{color:#777}.bottom-bar .dropdown button{border:0}.tag{background:#3af;padding:4px 6px;margin:4px;border-radius:2px;font-size:.8em;color:#fff}#favorGuide{position:fixed;align-items:center;z-index:1051;width:100%;bottom:0;display:flex;border:1px solid #bce8f1;background:#d9edf7;padding:8px 16px;color:#31708f}#favorGuide>:first-child{flex-grow:1}#favorGuide>:last-child{margin-left:4px}.navbar.site-navbar-tab{min-height:unset;border-bottom:0}.navbar.site-navbar-tab .navbar-nav{float:left;margin:0}.navbar.site-navbar-tab .navbar-nav>li{float:left}.navbar.site-navbar-tab .navbar-btn{margin:0}#cowork,#record,#remarks{padding:16px;background:#fff}#record .title{margin:0 -1rem .5rem -1rem;background:#ddd;padding:.5rem 1rem;border-bottom:1px solid #ccc}#record .title .dropdown-menu{right:0;left:auto;min-width:auto}#record .data blockquote>div+div{margin-top:8px}#record .assocs,#record .tags{margin-top:8px}#record .tags>button+button{margin-left:4px}#record .assocs>div{padding:8px 0}#record .assocs>div .assoc-reason{border:1px solid #ccc;border-radius:4px;margin-right:8px;padding:0 4px}#record .assocs>div .assoc-text{cursor:pointer}#cowork{position:relative;margin-top:1rem}#cowork .item{position:relative;transition:background 1s}#cowork .blink{background:#d9edf7}#cowork .assocs>div{padding:8px 0}#cowork .assocs>div .assoc-reason{border:1px solid #ccc;border-radius:4px;margin-right:8px;padding:0 4px}#cowork .assocs>div .assoc-text{cursor:pointer}#remarks{position:relative;margin-top:3rem}#remarks:before{content:'\\7559\\8A00';position:absolute;left:50%;margin-left:-1em;top:-2em;font-size:.7em;color:#eee;padding:.2em 1em;background:#666;border-radius:1em}#remarks .remarkList{background:#fff;min-height:167px;margin-bottom:30px}#remarks .remark{position:relative;background:#fff;border-bottom:1px solid #ddd;transition:background 1s;padding:8px 0}#remarks .remark:last-child{border-bottom:0}#remarks .remark>*{margin:1em 0 .2em}#remarks .blink{background:#d9edf7}#remarks .form-control{border-radius:0}.modal-edit-topic .record{padding-left:0;padding-right:0}.tms-editor{position:absolute;top:8px;bottom:8px;left:8px;right:8px;display:flex;flex-direction:column}.tms-editor>:first-child{position:relative;flex-grow:1;margin-bottom:8px;border:1px solid #ddd;border-radius:4px;overflow-y:auto}.tms-editor>:first-child iframe{display:block;width:100%;border:0}@media screen and (max-width:768px){.site-navbar-default{margin-bottom:0}#advCriteria{position:absolute;top:34px;right:0;margin-top:1px;width:300px;height:auto;background:#fff;padding:0 0 8px;border:1px solid #ccc;border-top:0;z-index:1000}#advCriteria .tree .tree-body .tree-wrap .item-children{position:static;left:0;width:100%;border:none}#advCriteria .tree .tree-body .item-2,#advCriteria .tree .tree-body .item-3,#advCriteria .tree .tree-body .item-4,#advCriteria .tree .tree-body .item-5{margin-left:1em}.app .main.col-xs-12,.app .tags.col-xs-12,.app .topics.col-xs-12{padding:0}#filterCriteria{margin-left:-1px;margin-right:-1px}#filterCriteria .form-control,#filterCriteria .input-group-btn .btn{border-radius:0}}", ""]);

// exports


/***/ }),
/* 28 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(27);
if(typeof content === 'string') content = [[module.i, content, '']];
// Prepare cssTransformation
var transform;

var options = {}
options.transform = transform
// add the styles to the DOM
var update = __webpack_require__(1)(content, options);
if(content.locals) module.exports = content.locals;
// Hot Module Replacement
if(false) {
	// When the styles change, update the <style> tags
	if(!content.locals) {
		module.hot.accept("!!../../../../../../node_modules/css-loader/index.js!./enroll.public.css", function() {
			var newContent = require("!!../../../../../../node_modules/css-loader/index.js!./enroll.public.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),
/* 29 */,
/* 30 */,
/* 31 */
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-body\">\r\n    <div class='form-group'>\r\n        <div class='input-group'>\r\n            <input type='text' class='form-control' ng-model=\"newTag.label\">\r\n            <div class='input-group-btn'>\r\n                <button class='btn btn-default' ng-click=\"addTag()\" ng-disabled=\"!newTag.label\">创建标签</button>\r\n            </div>\r\n        </div>\r\n    </div>\r\n    <div class='list-group'>\r\n        <div class='list-group-item' ng-repeat=\"tag in tags\">\r\n            <label class='checkbox-inline'>\r\n                <input type='checkbox' ng-model=\"tag.checked\" ng-change=\"checkTag(tag)\"> <span ng-bind=\"tag.label\"></span></label>\r\n        </div>\r\n    </div>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class='text-center'>\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">取消</button>\r\n        <button class=\"btn btn-primary\" ng-click=\"ok()\">保存</button>\r\n    </div>\r\n</div>"

/***/ }),
/* 32 */
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-body\">\r\n    <div class='panel panel-default' ng-repeat=\"topic in topics\">\r\n        <div class='panel-body'>\r\n            <div class='checkbox'>\r\n                <label>\r\n                    <input type='checkbox' ng-model=\"topic.checked\" ng-change=\"checkTopic(topic)\"> <span ng-bind=\"topic.title\"></span></label>\r\n            </div>\r\n            <div class='form-group'>\r\n                <div class='small text-muted' ng-bind=\"topic.summary\"></div>\r\n            </div>\r\n            <div class='bottom-bar small text-muted'>\r\n                <div ng-bind=\"topic.create_at*1000|date:'yy-MM-dd'\"></div>\r\n                <div><i class='glyphicon glyphicon-file'></i> <span ng-bind=\"topic.rec_num\"></span></div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class='text-center'>\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">取消</button>\r\n        <button class=\"btn btn-primary\" ng-click=\"ok()\">确定</button>\r\n    </div>\r\n</div>"

/***/ }),
/* 33 */
/***/ (function(module, exports) {

module.exports = "<div ng-if=\"rec\">\r\n    <div ng-if=\"rec.recordDir.length\"><span class=\"glyphicon glyphicon-folder-open\"></span>&nbsp;&nbsp;<span ng-repeat=\"dir in rec.recordDir track by $index\">{{dir}}<span ng-if=\"$index!==rec.recordDir.length-1\"> / </span></span></div>\r\n    <div ng-repeat=\"schema in schemas\" class='schema' ng-class=\"{'cowork':schema.cowork==='Y'}\" ng-if=\"rec.data[schema.id]||schema.cowork==='Y'\" ng-switch on=\"schema.type\">\r\n        <div class='text-muted data-title'><span>{{::schema.title}}</span></div>\r\n        <div ng-switch-when=\"file\">\r\n            <div ng-repeat=\"file in rec.data[schema.id]\" ng-switch on=\"file.type\">\r\n                <video ng-switch-when=\"video\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </video>\r\n                <audio ng-switch-when=\"audio\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </audio>\r\n                <audio ng-switch-when=\"audio/x-m4a\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </audio>\r\n                <audio ng-switch-when=\"audio/mp3\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </audio>\r\n                <img ng-switch-when=\"image\" ng-src='{{file.url}}' style=\"width:40%\" />\r\n                <a ng-switch-default href ng-click=\"open(file)\">{{file.name}}</a>\r\n            </div>\r\n        </div>\r\n        <div ng-switch-when=\"voice\">\r\n            <div ng-repeat=\"voice in rec.data[schema.id]\">\r\n                <audio controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{voice.url}}\" type=\"{{voice.type}}\" />\r\n                </audio>\r\n            </div>\r\n        </div>\r\n        <div ng-switch-when=\"image\">\r\n            <ul class='list-unstyled'>\r\n                <li ng-repeat=\"img in rec.data[schema.id].split(',')\"><img ng-src=\"{{img}}\" /></li>\r\n            </ul>\r\n        </div>\r\n        <div ng-switch-when=\"multitext\">\r\n            <div ng-repeat=\"item in rec.data[schema.id]\">\r\n                <div class='top-bar tms-flex-row'>\r\n                    <div class='seq tms-flex-grow text-muted' ng-if=\"item.multitext_seq\">#<span ng-bind=\"item.multitext_seq\"></span></div>\r\n                    <span ng-if=\"item.voteResult&&item.voteResult.state!=='BS'\">得票：<span ng-bind=\"item.voteResult.vote_num\"></span>&nbsp;&nbsp;</span>\r\n                    <span ng-if=\"item.score>0\" style='font-size:80%'>得分：<span ng-bind=\"item.score\"></span></span>\r\n                </div>\r\n                <blockquote>\r\n                    <p dynamic-html=\"item.value\"></p>\r\n                    <footer>{{item.nickname}}</footer>\r\n                </blockquote>\r\n            </div>\r\n        </div>\r\n        <div ng-switch-when=\"single\"><span ng-bind=\"rec.data[schema.id]\"></span></div>\r\n        <div ng-switch-when=\"multiple\"><span ng-bind=\"rec.data[schema.id]\"></span></div>\r\n        <div ng-switch-when=\"longtext\">\r\n            <span ng-bind-html=\"rec.data[schema.id]\"></span>\r\n        </div>\r\n        <div ng-switch-when=\"url\">\r\n            <span ng-bind-html=\"rec.data[schema.id]._text\"></span>\r\n        </div>\r\n        <div ng-switch-default>\r\n            <span ng-bind-html=\"rec.data[schema.id]\"></span>\r\n        </div>\r\n        <div ng-if=\"schema.cowork==='Y'\">\r\n            <button class='btn btn-default btn-sm' ng-click=\"coworkRecord(rec)\"><span ng-if=\"rec.coworkState[schema.id].length\">查看全部（<span ng-bind=\"rec.coworkState[schema.id].length\"></span>）或</span>添加 <span class='glyphicon glyphicon-menu-right'></span> <span class='text-muted' ng-if=\"rec._coworkRequireLikeNum\">（还需要{{rec._coworkRequireLikeNum}}个<span class='glyphicon glyphicon-thumbs-up'></span>）</span>\r\n            </button>\r\n        </div>\r\n        <div ng-if=\"schema.supplement==='Y'&&rec.supplement[schema.id]\" class='supplement' ng-bind-html=\"rec.supplement[schema.id]\"></div>\r\n        <div ng-if=\"rec.voteResult[schema.id]\">\r\n            <span ng-if=\"rec.voteResult[schema.id].state!=='BS'\">得票：<span ng-bind=\"rec.voteResult[schema.id].vote_num\"></span></span>\r\n            <button class='btn btn-success btn-xs' ng-if=\"rec.voteResult[schema.id].state==='IP'&&rec.voteResult[schema.id].vote_at===0\" ng-click=\"vote(rec.voteResult[schema.id], $event)\"><span class='glyphicon glyphicon-triangle-top'></span> 投票</button>\r\n            <button class='btn btn-default btn-xs' ng-if=\"rec.voteResult[schema.id].state==='IP'&&rec.voteResult[schema.id].vote_at!==0\" ng-click=\"unvote(rec.voteResult[schema.id], $event)\"><span class='glyphicon glyphicon-triangle-bottom'></span> 撤销投票</button>\r\n        </div>\r\n    </div>\r\n</div>"

/***/ }),
/* 34 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(22);

var ngMod = angular.module('repos.ui.enroll', ['schema.ui.xxt']);
ngMod.directive('tmsReposRecordData', ['$templateCache', function($templateCache) {
    return {
        restrict: 'A',
        template: __webpack_require__(33),
        scope: {
            schemas: '=',
            rec: '=record',
            task: '=task'
        },
        controller: ['$scope', '$sce', '$location', 'tmsLocation', 'http2', 'noticebox', 'tmsSchema', function($scope, $sce, $location, LS, http2, noticebox, tmsSchema) {
            $scope.coworkRecord = function(oRecord) {
                var url;
                url = LS.j('', 'site', 'app');
                url += '&ek=' + oRecord.enroll_key;
                url += '&page=cowork';
                url += '#cowork';
                location.href = url;
            };
            $scope.vote = function(oRecData, event) {
                event.preventDefault();
                event.stopPropagation();

                if ($scope.task) {
                    http2.get(LS.j('task/vote', 'site') + '&data=' + oRecData.id + '&task=' + $scope.task.id).then(function(rsp) {
                        if (oRecData.voteResult) {
                            oRecData.voteResult.vote_num++;
                            oRecData.voteResult.vote_at = rsp.data[0].vote_at;
                        } else {
                            oRecData.vote_num++;
                            oRecData.vote_at = rsp.data[0].vote_at;
                        }
                        var remainder = rsp.data[1][0] - rsp.data[1][1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                }
            };
            $scope.unvote = function(oRecData, event) {
                event.preventDefault();
                event.stopPropagation();
                
                if ($scope.task) {
                    http2.get(LS.j('task/unvote', 'site') + '&data=' + oRecData.id + '&task=' + $scope.task.id).then(function(rsp) {
                        if (oRecData.voteResult) {
                            oRecData.voteResult.vote_num--;
                            oRecData.voteResult.vote_at = 0;
                        } else {
                            oRecData.vote_num--;
                            oRecData.vote_at = 0;
                        }
                        var remainder = rsp.data[0] - rsp.data[1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                }
            };
            $scope.open = function(file) {
                var url, appID, data;
                appID = $location.search().app;
                data = {
                    name: file.name,
                    size: file.size,
                    url: file.oUrl,
                    type: file.type
                }
                url = '/rest/site/fe/matter/enroll/attachment/download?app=' + appID;
                url += '&file=' + JSON.stringify(data);
                window.open(url);
            }
            $scope.$watch('rec', function(oRecord) {
                if (!oRecord) { return; }
                $scope.$watch('schemas', function(schemas) {
                    if (!schemas) { return; }
                    var oSchema, schemaData;
                    for (var schemaId in $scope.schemas) {
                        oSchema = $scope.schemas[schemaId];
                        if (schemaData = oRecord.data[oSchema.id]) {
                            switch (oSchema.type) {
                                case 'longtext':
                                    oRecord.data[oSchema.id] = tmsSchema.txtSubstitute(schemaData);
                                    break;
                                case 'url':
                                    schemaData._text = tmsSchema.urlSubstitute(schemaData);
                                    break;
                                case 'file':
                                case 'voice':
                                    schemaData.forEach(function(oFile) {
                                        if (oFile.url && !angular.isObject(oFile.url)) {
                                            oFile.oUrl = oFile.url;
                                            oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                        }
                                    });
                                    break;
                                case 'single':
                                case 'multiple':
                                case 'score':
                                    var _result = tmsSchema.optionsSubstitute(oSchema, schemaData);
                                    oRecord.data[oSchema.id] = angular.isObject(_result) ? _result : $sce.trustAsHtml(_result);
                                    break;
                            }
                        }

                    }
                });
            });
        }]
    };
}]);

/***/ }),
/* 35 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('tag.ui.enroll', []);
ngMod.factory('enlTag', ['$q', '$uibModal', 'http2', 'tmsLocation', function($q, $uibModal, http2, LS) {
    var _oInstance = {};
    _oInstance.assignTag = function(oRecord) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: __webpack_require__(31),
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                var _aCheckedTagIds;
                _aCheckedTagIds = [];
                $scope.newTag = {};
                $scope.checkTag = function(oTag) {
                    oTag.checked ? _aCheckedTagIds.push(oTag.tag_id) : _aCheckedTagIds.splice(_aCheckedTagIds.indexOf(oTag.tag_id), 1);
                };
                $scope.addTag = function() {
                    http2.post(LS.j('tag/submit', 'site', 'app'), $scope.newTag).then(function(rsp) {
                        var oNewTag;
                        $scope.newTag = {};
                        oNewTag = rsp.data;
                        $scope.tags.splice(0, 0, rsp.data);
                        oNewTag.checked = true;
                        $scope.checkTag(oNewTag);
                    });
                };
                $scope.cancel = function() { $mi.dismiss(); };
                $scope.ok = function() { $mi.close(_aCheckedTagIds); };
                http2.get(LS.j('tag/byRecord', 'site') + '&record=' + oRecord.id).then(function(rsp) {
                    rsp.data.user.forEach(function(oTag) {
                        _aCheckedTagIds.push(oTag.tag_id);
                    });
                    http2.get(LS.j('tag/list', 'site', 'app') + '&public=Y').then(function(rsp) {
                        rsp.data.forEach(function(oTag) {
                            oTag.checked = _aCheckedTagIds.indexOf(oTag.tag_id) !== -1;
                        });
                        $scope.tags = rsp.data;
                    });
                });
            }],
            backdrop: 'static',
            windowClass: 'modal-opt-topic auto-height',
        }).result.then(function(aCheckedTagIds) {
            http2.post(LS.j('tag/assign', 'site'), { record: oRecord.id, tag: aCheckedTagIds }).then(function(rsp) {
                oDeferred.resolve(rsp);
            });
        });
        return oDeferred.promise;
    };

    return _oInstance;
}]);

/***/ }),
/* 36 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('task.ui.enroll', []);
ngMod.factory('enlTask', ['http2', '$q', '$parse', '$filter', '$uibModal', 'tmsLocation', function(http2, $q, $parse, $filter, $uibModal, LS) {
    var i18n = {
        weekday: {
            'Mon': '周一',
            'Tue': '周二',
            'Wed': '周三',
            'Thu': '周四',
            'Fri': '周五',
            'Sat': '周六',
            'Sun': '周日',
        }
    };

    function fnTaskToString() {
        var oTask = this,
            strs = [],
            min, max, limit, str, weekday, oDateFilter;

        oDateFilter = $filter('date');
        str = oDateFilter(oTask.start_at * 1000, 'M月d日（EEE）H:mm');
        weekday = oDateFilter(oTask.start_at * 1000, 'EEE');
        str = str.replace(weekday, i18n.weekday[weekday]);
        strs.push(str, '到');
        str = oDateFilter(oTask.end_at * 1000, 'M月d日（EEE）H:mm');
        weekday = oDateFilter(oTask.end_at * 1000, 'EEE');
        str = str.replace(weekday, i18n.weekday[weekday]);
        strs.push(str);
        min = parseInt($parse('limit.min')(oTask));
        max = parseInt($parse('limit.max')(oTask));
        if (min && max)
            limit = min + '-' + max + '个';
        else if (min)
            limit = '不少于' + min + '个';
        else if (max)
            limit = '不多于' + min + '个';
        else
            limit = '';

        switch (oTask.type) {
            case 'question':
                strs.push('，完成' + limit + '提问。');
                break;
            case 'answer':
                strs.push('，完成' + limit + '回答。');
                break;
            case 'vote':
                strs.push('，完成投票。');
                break;
            case 'score':
                strs.push('，完成打分。');
                break;
        }
        return strs.join('');
    };
    var Task;
    Task = function(oApp) {
        this.app = oApp;
    };
    Task.prototype.list = function(type, state, rid, ek) {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('task/list', 'site', 'app');
        if (type) url += '&type=' + type;
        if (state) url += '&state=' + state;
        if (rid) url += '&rid=' + rid;
        if (ek) url += '&ek=' + ek;
        http2.get(url).then(function(rsp) {
            if (rsp.data && rsp.data.length) {
                rsp.data.forEach(function(oTask) { oTask.toString = fnTaskToString; });
            }
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };

    return Task;
}]);

/***/ }),
/* 37 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('topic.ui.enroll', []);
ngMod.factory('enlTopic', ['$q', '$uibModal', 'http2', 'tmsLocation', function($q, $uibModal, http2, LS) {
    var _oInstance = {};
    _oInstance.assignTopic = function(oRecord, topics) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: __webpack_require__(32),
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _aCheckedTopicIds;
                _aCheckedTopicIds = [];
                $scope2.checkTopic = function(oTopic) {
                    oTopic.checked ? _aCheckedTopicIds.push(oTopic.id) : _aCheckedTopicIds.splice(_aCheckedTopicIds.indexOf(oTopic.id), 1);
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close(_aCheckedTopicIds); };
                http2.get(LS.j('topic/byRecord', 'site') + '&record=' + oRecord.id).then(function(rsp) {
                    rsp.data.forEach(function(oTopic) {
                        _aCheckedTopicIds.push(oTopic.topic_id);
                    });
                    var oDeferredTopics = $q.defer();
                    oDeferredTopics.promise.then(function(topics) {
                        topics.forEach(function(oTopic) {
                            oTopic.checked = _aCheckedTopicIds.indexOf(oTopic.id) !== -1;
                        });
                        $scope2.topics = topics;
                    });
                    if (topics) {
                        oDeferredTopics.resolve(topics);
                    } else {
                        http2.get(LS.j('topic/list', 'site', 'app')).then(function(rsp) {
                            oDeferredTopics.resolve(rsp.data.topics);
                        });
                    }
                });
            }],
            backdrop: 'static',
            windowClass: 'modal-opt-topic auto-height',
        }).result.then(function(aCheckedTopicIds) {
            http2.post(LS.j('topic/assign', 'site') + '&record=' + oRecord.id, { topic: aCheckedTopicIds }).then(function(rsp) {
                oDeferred.resolve(rsp);
            });
        });
        return oDeferred.promise;
    };

    return _oInstance;
}]);

/***/ }),
/* 38 */,
/* 39 */,
/* 40 */,
/* 41 */,
/* 42 */
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-body\">\r\n    <div class='help-block'>内容来源：<span ng-bind=\"cache.app.title\"></span></div>\r\n    <div class='form-group'>\r\n        <label>关联对象</label>\r\n        <input type='input' class='form-control' ng-model=\"assoc.text\">\r\n    </div>\r\n    <div class='form-group'>\r\n        <label>关联理由</label>\r\n        <input type='input' class='form-control' ng-model=\"assoc.reason\">\r\n    </div>\r\n    <div class='form-group' ng-if=\"user.is_editor==='Y'||user.is_leader==='Y'||user.is_leader==='S'\">\r\n        <label class='radio-inline'>\r\n            <input type='radio' name='public' value='N' ng-model=\"assoc.public\">仅自己可见</label>\r\n        <label class='radio-inline'>\r\n            <input type='radio' name='public' value='Y' ng-model=\"assoc.public\">所有人可见</label>\r\n    </div>\r\n    <div class='checkbox'>\r\n        <label>\r\n            <input type='checkbox' ng-model=\"assoc.retainCopied\">粘贴后不清除复制内容</label>\r\n    </div>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class='text-center'>\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">取消</button>\r\n        <button class=\"btn btn-primary\" ng-click=\"ok()\">保存</button>\r\n    </div>\r\n</div>"

/***/ }),
/* 43 */
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-body\">\r\n    <form class=\"form-horizontal\">\r\n        <div class='form-group'>\r\n            <label class=\"col-md-3 control-label\">类型</label>\r\n            <div class=\"col-md-9 \">\r\n                <select disabled class=\"form-control\" ng-model=\"result.type\">\r\n                    <option value='article'>单图文</option>\r\n                    <option value='channel'>频道</option>\r\n                    <option value='link'>链接</option>\r\n                </select>\r\n            </div>\r\n        </div>\r\n        <div class='form-group'>\r\n            <label class=\"col-md-3 control-label\">名称</label>\r\n            <div class=\"col-md-9 \">\r\n                <div class='input-group'>\r\n                    <input type='text' class=\"form-control\" ng-model=\"result.title\" placeholder='输入素材名称' autofocus>\r\n                    <div class='input-group-btn'>\r\n                        <button class='btn btn-default' ng-click=\"doSearch()\"><span class='glyphicon glyphicon-search'></span></button>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n        <div class='form-group'>\r\n            <label class=\"col-md-3 control-label\">目标</label>\r\n            <div class=\"col-md-9 \">\r\n                <select class=\"form-control\" ng-model=\"result.matter\" ng-options=\"matter.title for matter in matters\" size='12'></select>\r\n                <div class='form-group'></div>\r\n                <div ng-if=\"page.total>page.size\">\r\n                    <div class='pl-pagination'>\r\n                        <ul class='pagination-sm' uib-pagination boundary-links=\"false\" total-items=\"page.total\" max-size=\"7\" items-per-page=\"page.size\" rotate=\"false\" ng-model=\"page.at\" previous-text=\"&lsaquo;\" next-text=\"&rsaquo;\" first-text=\"&laquo;\" last-text=\"&raquo;\" ng-change=\"doSearch()\"></ul>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </form>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class='text-center'>\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">取消</button>\r\n        <button class=\"btn btn-primary\" ng-click=\"ok()\">关联</button>\r\n    </div>\r\n</div>"

/***/ }),
/* 44 */
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-body\">\r\n    <div class='form-group'>\r\n        <label>关联对象</label>\r\n        <input type='input' class='form-control' ng-model=\"assoc.text\" ng-change=\"update('text')\">\r\n    </div>\r\n    <div class='form-group'>\r\n        <label>关联理由</label>\r\n        <input type='input' class='form-control' ng-model=\"assoc.reason\" ng-change=\"update('reason')\">\r\n    </div>\r\n    <div class='form-group' ng-if=\"user.is_editor==='Y'||user.is_leader==='Y'||user.is_leader==='S'\">\r\n        <label class='radio-inline'>\r\n            <input type='radio' name='public' value='N' ng-model=\"assoc.public\" ng-change=\"update('public')\"> 仅自己可见</label>\r\n        <label class='radio-inline'>\r\n            <input type='radio' name='public' value='Y' ng-model=\"assoc.public\" ng-change=\"update('public')\"> 所有人可见</label>\r\n    </div>\r\n    <div class='checkbox' ng-if=\"user.is_editor==='Y'||user.is_leader==='Y'||user.is_leader==='S'\">\r\n        <hr>\r\n        <label>\r\n            <input type='checkbox' ng-model=\"assoc.updatePublic\" ng-disabled=\"countUpdated===0\">更新结果所有人可见</label>\r\n    </div>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class='text-center'>\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">取消</button>\r\n        <button class=\"btn btn-primary\" ng-click=\"ok()\" ng-disabled=\"countUpdated===0\">保存</button>\r\n    </div>\r\n</div>"

/***/ }),
/* 45 */,
/* 46 */,
/* 47 */,
/* 48 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

/**
 * 复制的时候保存在本地存储中，黏贴的时候取出
 * 支持跨活动进行复制
 */
var ngMod = angular.module('assoc.ui.enroll', []);
ngMod.service('enlAssoc', ['$q', '$uibModal', 'noticebox', 'http2', 'tmsLocation', function($q, $uibModal, noticebox, http2, LS) {
    function fnGetEntitySketch(oEntity) {
        var defer, url;
        defer = $q.defer();
        if (oEntity.type === 'record') {
            url = LS.j('record/sketch', 'site') + '&record=' + oEntity.id;
        } else if (oEntity.type === 'topic') {
            url = LS.j('topic/sketch', 'site') + '&topic=' + oEntity.id
        }
        if (url) {
            http2.get(url).then(function(rsp) {
                defer.resolve(rsp.data)
            });
        } else {
            defer.reject();
        }
        return defer.promise;
    }

    var _self, _cacheKey;
    _self = this;
    _cacheKey = '/xxt/site/app/enroll/assoc';
    this.isSupport = function() {
        return !!window.sessionStorage;
    };
    this.hasCache = function() {
        return !!window.sessionStorage.getItem(_cacheKey);
    };
    this.copy = function(oApp, oEntity) {
        var oDeferred, oCache;
        oDeferred = $q.defer();
        if (window.sessionStorage) {
            oCache = {
                app: {
                    id: oApp.id,
                    title: oApp.title
                },
                entity: {
                    id: oEntity.id,
                    type: oEntity.type
                }
            };
            oCache.entity = oEntity;
            window.sessionStorage.setItem(_cacheKey, JSON.stringify(oCache));
            noticebox.info('完成复制');
            oDeferred.resolve();
        }
        return oDeferred.promise;
    };
    this.paste = function(oUser, oRecord, oEntity) {
        var oDeferred, oCache;
        oDeferred = $q.defer();
        if (window.sessionStorage) {
            if (oCache = window.sessionStorage.getItem(_cacheKey)) {
                oCache = JSON.parse(oCache);
                $uibModal.open({
                    template: __webpack_require__(42),
                    controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                        var _oAssoc;
                        $scope.user = oUser;
                        $scope.cache = oCache;
                        $scope.assoc = _oAssoc = { public: 'N' };
                        $scope.cancel = function() { $mi.dismiss(); };
                        $scope.ok = function() {
                            var oPosted = {};
                            oPosted.assoc = _oAssoc;
                            oPosted.entityA = { id: oEntity.id, type: oEntity.type };
                            oPosted.entityB = oCache.entity;
                            http2.post(LS.j('assoc/link', 'site') + '&ek=' + oRecord.enroll_key, oPosted).then(function(rsp) {
                                if (!_oAssoc.retainCopied) {
                                    window.sessionStorage.removeItem(_cacheKey);
                                }
                                $mi.close(rsp.data);
                            });
                        };
                        fnGetEntitySketch(oCache.entity).then(function(oSketch) {
                            _oAssoc.text = oSketch.title;
                        });
                    }],
                    backdrop: 'static',
                    windowClass: 'auto-height',
                }).result.then(function(oNewAssoc) {
                    oDeferred.resolve(oNewAssoc);
                });
            } else {
                noticebox.warn('没有粘贴的内容。可在共享页或讨论页【复制】内容，然后通过【粘贴】建立数据间的关联。');
                oDeferred.reject();
            }
        }
        return oDeferred.promise;
    };
    this.update = function(oUser, oAssoc) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: __webpack_require__(44),
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                var oCache, oUpdated;
                oUpdated = {};
                $scope.user = oUser;
                $scope.assoc = oCache = { text: oAssoc.assoc_text, reason: oAssoc.assoc_reason, public: oAssoc.public };
                $scope.countUpdated = 0;
                $scope.update = function(prop) {
                    if (!oUpdated[prop]) $scope.countUpdated++;
                    oUpdated[prop] = oCache[prop];
                };
                $scope.ok = function() {
                    if (oCache.updatePublic) oUpdated.updatePublic = true;
                    http2.post(LS.j('assoc/update', 'site') + '&assoc=' + oAssoc.id, oUpdated).then(function(rsp) {
                        oAssoc.assoc_text = oCache.text;
                        oAssoc.assoc_reason = oCache.reason;
                        oAssoc.public = oCache.public;
                        $mi.close();
                    });
                };
                $scope.cancel = function() { $mi.dismiss(); };
            }],
            backdrop: 'static',
            windowClass: 'auto-height',
        }).result.then(function() {
            oDeferred.resolve();
        });
        return oDeferred.promise;
    };
    /* 关联应用内素材 */
    this.assocMatter = function(oUser, oRecord, oEntity) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: __webpack_require__(43),
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                var _oResult, _oPage, _oAssoc;
                $scope.result = _oResult = { type: 'article' };
                $scope.page = _oPage = {};
                $scope.assoc = _oAssoc = { public: 'Y' };
                $scope.doSearch = function() {
                    var url;
                    url = '/rest/pl/fe/matter/article/list';
                    http2.post(url, { byTitle: _oResult.title }, { page: _oPage }).then(function(rsp) {
                        $scope.matters = rsp.data.docs;
                        if ($scope.matters.length)
                            _oResult.matter = $scope.matters[0];
                    });
                };
                $scope.ok = function() {
                    var oPosted, oMatter;
                    if (oMatter = _oResult.matter) {
                        _oAssoc.text = oMatter.title;
                        oPosted = {};
                        oPosted.assoc = _oAssoc;
                        oPosted.entityA = { id: oEntity.id, type: oEntity.type };
                        oPosted.entityB = { id: oMatter.id, type: oMatter.type };
                        http2.post(LS.j('assoc/link', 'site') + '&ek=' + oRecord.enroll_key, oPosted).then(function(rsp) {
                            $mi.close(rsp.data);
                        });
                    }
                };
                $scope.cancel = function() { $mi.dismiss(); };
            }],
            backdrop: 'static',
            windowClass: 'auto-height',
        }).result.then(function(oAssoc) {
            oDeferred.resolve(oAssoc);
        });
        return oDeferred.promise;
    };
}]);

/***/ }),
/* 49 */,
/* 50 */,
/* 51 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('paste.ui.xxt', ['ngSanitize', 'notice.ui.xxt']);
ngMod.service('tmsPaste', ['$timeout', '$q', 'noticebox', function($timeout, $q, noticebox) {
    this.onpaste = function(originalText, oOptions) {
        function fnDoPaste(text) {
            if (oOptions && oOptions.doc) {
                oOptions.doc.execCommand("insertHTML", false, text);
            } else {
                document.execCommand("insertHTML", false, text);
            }
            defer.resolve(text);
        }

        var defer, actions, cleanEmptyText, cleanHtmlText, newText;
        defer = $q.defer();
        actions = [
            { label: '跳过', value: 'cancel', execWait: 5000 }
        ];
        /* 是否存在空字符 */
        if (oOptions.filter && oOptions.filter.whiteSpace) {
            cleanEmptyText = originalText.replace(/\s/gm, '');
            if (cleanEmptyText.length !== originalText.length) {
                actions.splice(0, 0, { label: '清除空字符', value: 'cleanEmpty' });
            }
        }
        cleanHtmlText = originalText.replace(/<(style|script|iframe)[^>]*?>[\s\S]+?<\/\1\s*>/gi, '').replace(/<[^>]+?>/g, '').replace(/\s+/g, ' ').replace(/ /g, ' ').replace(/>/g, ' ');
        if (cleanHtmlText.length !== originalText.length) {
            actions.splice(0, 0, { label: '清除HTML', value: 'cleanHtml' });
        }
        if (actions.length > 1) {
            noticebox.confirm('清理粘贴内容格式？', actions).then(function(confirmValue) {
                switch (confirmValue) {
                    case 'cleanHtml':
                        newText = cleanHtmlText;
                        break;
                    case 'cleanEmpty':
                        newText = cleanEmptyText;
                        break;
                    default:
                        newText = originalText;
                }
                fnDoPaste(newText);
                defer.resolve(newText);
            }, function() {
                fnDoPaste(originalText);
            });
        } else {
            fnDoPaste(originalText);
        }
        return defer.promise;
    }
}]);

/***/ }),
/* 52 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('url.ui.xxt', ['http.ui.xxt']);
ngMod.service('tmsUrl', ['$q', '$uibModal', function($q, $uibModal) {
    function validateUrl(url) {
        return true;
    }
    this.fetch = function(oBeforeUrlData, oOptions) {
        var defer;
        defer = $q.defer();
        $uibModal.open({
            template: __webpack_require__(56),
            controller: ['$scope', '$uibModalInstance', 'http2', 'noticebox', function($scope, $mi, http2, noticebox) {
                var _oData;
                $scope.data = _oData = {
                    text: '结果预览'
                };
                if (oBeforeUrlData) {
                    _oData.summary = {
                        title: oBeforeUrlData.title,
                        description: oBeforeUrlData.description,
                        url: oBeforeUrlData.url
                    };
                    _oData.url = oBeforeUrlData.url;
                }
                $scope.options = oOptions;
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close(_oData);
                };
                $scope.crawlUrl = function(event) {
                    var url;
                    if (event && event.clipboardData) {
                        url = event.clipboardData.getData('Text');
                    } else {
                        url = _oData.url;
                    }
                    if (validateUrl(url)) {
                        http2.post('/rest/site/fe/matter/enroll/url', { url: url }).then(function(rsp) {
                            if(Object.keys(rsp.data).indexOf('url')===-1||!rsp.data.url) {
                                noticebox.error('请点击“刷新”按钮，重新获取解析值');
                                return false;
                            }
                            _oData.summary = rsp.data;
                        });
                    }
                };
                $scope.$watch('data.summary', function(nv) {
                    if (nv) {
                        var text;
                        text = '';
                        if (nv.title) {
                            text += '【' + nv.title + '】';
                        }
                        if (nv.description) {
                            text += nv.description;
                        }
                        text += '<a href="' + _oData.url + '">网页链接</a>';
                        _oData.text = text;
                    }
                }, true);
            }],
            backdrop: 'static'
        }).result.then(function(data) {
            defer.resolve(data);
        });

        return defer.promise;
    };
}]);

/***/ }),
/* 53 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ResizeSensor = __webpack_require__(58);
__webpack_require__(51);
__webpack_require__(52);

var ngMod = angular.module('editor.ui.xxt', ['ui.bootstrap', 'url.ui.xxt', 'paste.ui.xxt']);
ngMod.controller('tmsEditorController', ['$scope', 'tmsUrl', function($scope, tmsUrl) {
    /* 插入链接 */
    $scope.insertLink = function() {
        tmsUrl.fetch().then(function(oResult) {
            var oUrl;
            if (oUrl = oResult.summary) {
                $scope.iframeDoc.execCommand('insertHTML', false, '<a href="' + oUrl.url + '" target="_blank">' + (oUrl.title || '链接') + '</a>');
            }
        });
    };
    /* 开启关闭设置样式 */
    $scope.toggleDesignMode = function() {
        $scope.designMode = !$scope.designMode;
        $scope.iframeDoc.getSelection().removeAllRanges();
    };
}]);
ngMod.directive('tmsEditor', ['$q', '$timeout', 'http2', 'tmsPaste', function($q, $timeout, http2, tmsPaste) {
    function _calcTextWidth(text) {
        var divMock, height, width;
        divMock = document.createElement('DIV');
        divMock.style.position = 'absolute';
        divMock.style.visibility = 'hidden';
        divMock.style.height = 'auto';
        divMock.style.width = 'auto';
        divMock.style.whiteSpace = 'nowrap';
        divMock.innerHTML = text;
        _iframeDoc.querySelector('body').appendChild(divMock);
        height = divMock.clientHeight;
        width = divMock.clientWidth;
        _iframeDoc.querySelector('body').removeChild(divMock);

        return { height: height, width: width, charWidth: parseInt(width / text.length) };
    }
    /**
     * 根据触屏事件，设置选中的内容
     */
    function _setSelectionByTouch(oTarget, oTouchTracks) {
        var oParam, oSelection, oRange, oStartNode;
        oParam = {
            start: {
                touch: oTouchTracks.start
            },
            end: {
                touch: oTouchTracks.end
            }
        };
        if (oTarget.childNodes.length) {
            for (var i = 0, ii = oTarget.childNodes.length; i < ii; i++) {
                if (oTarget.childNodes[i].nodeType === Node.TEXT_NODE) {
                    oStartNode = oTarget.childNodes[i];
                    break;
                }
            }
        }
        if (oStartNode) {
            oParam.start.element = {
                top: oStartNode.parentElement.offsetTop,
                left: oStartNode.parentElement.offsetLeft,
                width: oStartNode.parentElement.offsetWidth,
                height: oStartNode.parentElement.offsetHeight,
            }
            oParam.text = _calcTextWidth(oStartNode.nodeValue);
            oParam.startCharAt = parseInt((oTouchTracks.start.x - oStartNode.parentElement.offsetLeft) / oParam.text.charWidth);
            oParam.endCharAt = parseInt((oTouchTracks.end.x - oStartNode.parentElement.offsetLeft) / oParam.text.charWidth);
            oRange = document.createRange();
            oRange.setStart(oStartNode, oParam.startCharAt);
            oRange.setEnd(oStartNode, oParam.endCharAt);
        } else {
            oRange = document.createRange();
            oRange.selectNodeContents(oTarget);
        }
        oSelection = _iframeDoc.getSelection();
        oSelection.removeAllRanges();
        oSelection.addRange(oRange);

        return oRange;
    }

    var _iframeDoc, _divContent;
    /**
     * 外部服务接口
     */
    window.tmsEditor = (function() {
        return {
            finish: function() {
                _divContent.blur();
                return _divContent.innerHTML;
            }
        }
    })();
    return {
        restrict: 'EA',
        scope: { id: '@', content: '=', cmds: '=' },
        replace: true,
        controller: 'tmsEditorController',
        template: __webpack_require__(55),
        link: function($scope, elem, attrs) {
            var iframeHTML, iframeNode;
            /* 初始化 */
            iframeHTML = '<!DOCTYPE html><html><head>';
            iframeHTML += '<meta charset="utf-8"></head>';
            iframeHTML += '<style>';
            iframeHTML += 'html,body,body>div{width:100%;height:100%;}body{font-size:16px;margin:0}.tms-editor-content:empty::before{color:lightgrey;content:attr(placeholder);}.tms-editor-content img{max-width:100%;}';
            iframeHTML += '</style>';
            iframeHTML += '<body>';
            iframeHTML += '<div class="tms-editor-content " contentEditable="true" placeholder="添加内容...">' + $scope.content + '</div>';
            iframeHTML += '</body></html>';
            iframeNode = document.querySelector('#' + $scope.id + ' iframe');
            if (iframeNode.parentElement) {
                new ResizeSensor(iframeNode.parentElement, function() {
                    iframeNode.height = iframeNode.parentElement.offsetHeight - 2;
                });
            }
            if (iframeNode.contentDocument) {
                _iframeDoc = iframeNode.contentDocument
            } else if (iframeNode.contentWindow) {
                _iframeDoc = iframeNode.contentWindow.iframeDocument;
            }
            _iframeDoc.open();
            _iframeDoc.write(iframeHTML);
            _iframeDoc.close();
            $scope.iframeDoc = _iframeDoc;
            _divContent = _iframeDoc.querySelector('body>div');
            /* 页面加载完成后进行初始化 */
            _iframeDoc.querySelector('body').onload = function() {
                _divContent.contentEditable = true;
            };
            $scope.mobileAgent = /Android|iPhone|iPad/i.test(navigator.userAgent);
            $scope.designMode = !$scope.mobileAgent;
            /* 触屏事件处理 */
            var oTouchTracks = {};
            _iframeDoc.oncontextmenu = function(e) {
                if ($scope.designMode) {
                    e.preventDefault();
                }
            };
            _iframeDoc.ontouchstart = function(event) {
                var oTouch;
                if ($scope.designMode) {
                    if (event.targetTouches.length === 1) {
                        oTouch = event.targetTouches[0];
                        oTouchTracks.start = { x: oTouch.pageX, y: oTouch.pageY };
                        event.preventDefault();
                        _iframeDoc.getSelection().removeAllRanges();
                        _divContent.contentEditable = false;
                    }
                }
            };
            _iframeDoc.ontouchmove = function(event) {
                var oTouch;
                if ($scope.designMode) {

                    if (event.targetTouches.length === 1) {
                        oTouch = event.targetTouches[0];
                        oTouchTracks.end = { x: oTouch.pageX, y: oTouch.pageY };
                        event.preventDefault();
                    }
                }
            };
            _iframeDoc.ontouchend = function(event) {
                if ($scope.designMode) {
                    if (oTouchTracks.start && oTouchTracks.end) {
                        /* 是否进行了有效的移动 */
                        if (Math.abs(oTouchTracks.start.x - oTouchTracks.end.x) >= 16) {
                            _setSelectionByTouch(event.target, oTouchTracks);
                        }
                        _divContent.contentEditable = true;
                        event.preventDefault();
                    }
                }
            };
            /* 粘贴时处理格式 */
            _divContent.addEventListener('paste', function(e) {
                var text;
                e.preventDefault();
                text = e.clipboardData.getData('text/plain');
                tmsPaste.onpaste(text, { doc: _iframeDoc, filter: { whiteSpace: true } });
            });
            /* 设置基本样式 */
            $timeout(function() {
                var btns = document.querySelectorAll('#' + $scope.id + ' button[command]');
                angular.forEach(btns, function(eleBtn) {
                    eleBtn.addEventListener('click', function() {
                        var cmd, args;
                        cmd = this.getAttribute('command').toLowerCase();
                        switch (cmd) {
                            case 'backcolor':
                                args = 'yellow';
                                break;
                        }
                        _iframeDoc.execCommand(cmd, false, args);
                    });
                });
            });
            /* 插入图片操作 */
            if (window.xxt && window.xxt.image) {
                var eleBtnInsertImage, eleIframe, eleDivContent;
                eleIframe = document.querySelector('iframe').contentDocument;
                eleDivContent = eleIframe.getElementsByClassName('tms-editor-content ')[0];
                if (eleBtnInsertImage = document.querySelector('#' + $scope.id + ' button[action=InsertImage]')) {
                    eleBtnInsertImage.addEventListener('click', function() {
                        eleDivContent.focus();
                        window.xxt.image.choose($q.defer()).then(function(imgs) {
                            imgs.forEach(function(oImg) {
                                http2.post('/rest/site/fe/matter/upload/image?site=platform', oImg).then(function(rsp) {
                                    _iframeDoc.execCommand('InsertImage', false, rsp.data.url);
                                });
                            });
                        });
                    });
                }
            }
        }
    }
}]);

/***/ }),
/* 54 */,
/* 55 */
/***/ (function(module, exports) {

module.exports = "<div class=\"tms-editor\">\r\n    <div>\r\n        <iframe src=\"javascript:void(0);\"></iframe>\r\n    </div>\r\n    <div class=\"btn-toolbar btn-sm\">\r\n        <div class=\"btn-group\">\r\n            <button ng-if=\"mobileAgent\" class=\"btn btn-default\" ng-click=\"toggleDesignMode()\"><span ng-if=\"!designMode\">设置样式</span><span ng-if=\"designMode\" class=\"glyphicon glyphicon-menu-left\"></span></button>\r\n            <button ng-if=\"designMode\" class=\"btn btn-default\" command=\"bold\"><strong>B</strong></button>\r\n            <button ng-if=\"designMode\" class=\"btn btn-default\" command=\"italic\"><i>I</i></button>\r\n            <button ng-if=\"designMode\" class=\"btn btn-default\" command=\"underline\"><span style=\"text-decoration:underline;\">U</span></button>\r\n            <button ng-if=\"designMode\" class=\"btn btn-default\" command=\"BackColor\"><i class=\"glyphicon glyphicon-text-background\"></i></button>\r\n        </div>\r\n        <div class=\"btn-group\">\r\n            <button class=\"btn btn-default\" action=\"InsertImage\"><i class=\"glyphicon glyphicon-picture\"></i></button>\r\n            <button class=\"btn btn-default\" ng-click=\"insertLink()\"><i class=\"glyphicon glyphicon-link\"></i></button>\r\n        </div>\r\n        <div class=\"btn-group\">\r\n            <button class=\"btn btn-default\" command=\"undo\"><i class=\"glyphicon glyphicon-backward\"></i></button>\r\n        </div>\r\n    </div>\r\n</div>"

/***/ }),
/* 56 */
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-header\">\r\n    <h5 class=\"modal-title\">上传链接</h5>\r\n</div>\r\n<div class=\"modal-body\">\r\n    <form>\r\n        <div class=\"form-group\">\r\n            <div class=\"input-group\">\r\n                <input type=\"text\" ng-paste=\"crawlUrl($event)\" class=\"form-control\" placeholder=\"1、请将链接粘贴到这里或输入\" ng-model=\"data.url\">\r\n                <div class=\"input-group-btn\">\r\n                    <button class=\"btn btn-default\" ng-click=\"crawlUrl()\">刷新</button>\r\n                </div>\r\n            </div>\r\n        </div>\r\n        <div class=\"form-group\">\r\n            <input type=\"text\" class=\"form-control\" placeholder=\"2、复制链接或手动输入后这里将显示页面的标题，可进行修改\" ng-model=\"data.summary.title\">\r\n        </div>\r\n        <div class=\"form-group\" ng-if=\"options.description\">\r\n            <textarea class=\"form-control\" placeholder=\"3、复制链接或手动输入后这里将显示页面的摘要描述（如果提供），可进行修改\" ng-model=\"data.summary.description\" rows=\"4\"></textarea>\r\n        </div>\r\n        <div class=\"form-group\" ng-if=\"options.text\">\r\n            <div class=\"form-control\" ng-bind-html=\"data.text\" style=\"height:auto;min-height:34px;\"></div>\r\n        </div>\r\n    </form>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class=\"text-center\">\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">关闭</button>\r\n        <button class=\"btn btn-default\" ng-click=\"ok()\">完成</button>\r\n    </div>\r\n</div>"

/***/ }),
/* 57 */,
/* 58 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_RESULT__;

/**
 * Copyright Marc J. Schmidt. See the LICENSE file at the top-level
 * directory of this distribution and at
 * https://github.com/marcj/css-element-queries/blob/master/LICENSE.
 */
(function (root, factory) {
    if (true) {
        !(__WEBPACK_AMD_DEFINE_FACTORY__ = (factory),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.call(exports, __webpack_require__, exports, module)) :
				__WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    } else if (typeof exports === "object") {
        module.exports = factory();
    } else {
        root.ResizeSensor = factory();
    }
}(typeof window !== 'undefined' ? window : this, function () {

    // Make sure it does not throw in a SSR (Server Side Rendering) situation
    if (typeof window === "undefined") {
        return null;
    }
    // Only used for the dirty checking, so the event callback count is limited to max 1 call per fps per sensor.
    // In combination with the event based resize sensor this saves cpu time, because the sensor is too fast and
    // would generate too many unnecessary events.
    var requestAnimationFrame = window.requestAnimationFrame ||
        window.mozRequestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        function (fn) {
            return window.setTimeout(fn, 20);
        };

    /**
     * Iterate over each of the provided element(s).
     *
     * @param {HTMLElement|HTMLElement[]} elements
     * @param {Function}                  callback
     */
    function forEachElement(elements, callback){
        var elementsType = Object.prototype.toString.call(elements);
        var isCollectionTyped = ('[object Array]' === elementsType
            || ('[object NodeList]' === elementsType)
            || ('[object HTMLCollection]' === elementsType)
            || ('[object Object]' === elementsType)
            || ('undefined' !== typeof jQuery && elements instanceof jQuery) //jquery
            || ('undefined' !== typeof Elements && elements instanceof Elements) //mootools
        );
        var i = 0, j = elements.length;
        if (isCollectionTyped) {
            for (; i < j; i++) {
                callback(elements[i]);
            }
        } else {
            callback(elements);
        }
    }

    /**
    * Get element size
    * @param {HTMLElement} element
    * @returns {Object} {width, height}
    */
    function getElementSize(element) {
        if (!element.getBoundingClientRect) {
            return {
                width: element.offsetWidth,
                height: element.offsetHeight
            }
        }

        var rect = element.getBoundingClientRect();
        return {
            width: Math.round(rect.width),
            height: Math.round(rect.height)
        }
    }

    /**
     * Class for dimension change detection.
     *
     * @param {Element|Element[]|Elements|jQuery} element
     * @param {Function} callback
     *
     * @constructor
     */
    var ResizeSensor = function(element, callback) {
       
        var observer;
       
        /**
         *
         * @constructor
         */
        function EventQueue() {
            var q = [];
            this.add = function(ev) {
                q.push(ev);
            };

            var i, j;
            this.call = function(sizeInfo) {
                for (i = 0, j = q.length; i < j; i++) {
                    q[i].call(this, sizeInfo);
                }
            };

            this.remove = function(ev) {
                var newQueue = [];
                for(i = 0, j = q.length; i < j; i++) {
                    if(q[i] !== ev) newQueue.push(q[i]);
                }
                q = newQueue;
            };

            this.length = function() {
                return q.length;
            }
        }

        /**
         *
         * @param {HTMLElement} element
         * @param {Function}    resized
         */
        function attachResizeEvent(element, resized) {
            if (!element) return;
            if (element.resizedAttached) {
                element.resizedAttached.add(resized);
                return;
            }

            element.resizedAttached = new EventQueue();
            element.resizedAttached.add(resized);

            element.resizeSensor = document.createElement('div');
            element.resizeSensor.dir = 'ltr';
            element.resizeSensor.className = 'resize-sensor';
            var style = 'position: absolute; left: -10px; top: -10px; right: 0; bottom: 0; overflow: hidden; z-index: -1; visibility: hidden;';
            var styleChild = 'position: absolute; left: 0; top: 0; transition: 0s;';

            element.resizeSensor.style.cssText = style;
            element.resizeSensor.innerHTML =
                '<div class="resize-sensor-expand" style="' + style + '">' +
                    '<div style="' + styleChild + '"></div>' +
                '</div>' +
                '<div class="resize-sensor-shrink" style="' + style + '">' +
                    '<div style="' + styleChild + ' width: 200%; height: 200%"></div>' +
                '</div>';
            element.appendChild(element.resizeSensor);

            var position = window.getComputedStyle(element).getPropertyValue('position');
            if ('absolute' !== position && 'relative' !== position && 'fixed' !== position) {
                element.style.position = 'relative';
            }

            var expand = element.resizeSensor.childNodes[0];
            var expandChild = expand.childNodes[0];
            var shrink = element.resizeSensor.childNodes[1];

            var dirty, rafId;
            var size = getElementSize(element);
            var lastWidth = size.width;
            var lastHeight = size.height;
            var initialHiddenCheck = true, resetRAF_id;
            
            
            var resetExpandShrink = function () {
                expandChild.style.width = '100000px';
                expandChild.style.height = '100000px';
        
                expand.scrollLeft = 100000;
                expand.scrollTop = 100000;
        
                shrink.scrollLeft = 100000;
                shrink.scrollTop = 100000;
            };

            var reset = function() {
                // Check if element is hidden
                if (initialHiddenCheck) {
                    if (!expand.scrollTop && !expand.scrollLeft) {

                        // reset
                        resetExpandShrink();

                        // Check in next frame
                        if (!resetRAF_id){
                            resetRAF_id = requestAnimationFrame(function(){
                                resetRAF_id = 0;
                                
                                reset();
                            });
                        }
                        
                        return;
                    } else {
                        // Stop checking
                        initialHiddenCheck = false;
                    }
                }

                resetExpandShrink();
            };
            element.resizeSensor.resetSensor = reset;

            var onResized = function() {
                rafId = 0;

                if (!dirty) return;

                lastWidth = size.width;
                lastHeight = size.height;

                if (element.resizedAttached) {
                    element.resizedAttached.call(size);
                }
            };

            var onScroll = function() {
                size = getElementSize(element);
                dirty = size.width !== lastWidth || size.height !== lastHeight;

                if (dirty && !rafId) {
                    rafId = requestAnimationFrame(onResized);
                }

                reset();
            };

            var addEvent = function(el, name, cb) {
                if (el.attachEvent) {
                    el.attachEvent('on' + name, cb);
                } else {
                    el.addEventListener(name, cb);
                }
            };

            addEvent(expand, 'scroll', onScroll);
            addEvent(shrink, 'scroll', onScroll);
            
            // Fix for custom Elements
            requestAnimationFrame(reset);
        }
         
        if (typeof ResizeObserver !== "undefined") {
            observer = new ResizeObserver(function(element){
                forEachElement(element, function (elem) {
                    callback.call(
                        this,
                        {
                            width: elem.contentRect.width,
                            height: elem.contentRect.height
                        }
                   );
                });
            });
            if (element !== undefined) {
                forEachElement(element, function(elem){
                   observer.observe(elem);
                });
            }
        }
        else {
            forEachElement(element, function(elem){
                attachResizeEvent(elem, callback);
            });
        }

        this.detach = function(ev) {
            if (typeof ResizeObserver != "undefined") {
                forEachElement(element, function(elem){
                    observer.unobserve(elem);
                });
            }
            else {
                ResizeSensor.detach(element, ev);
            }
        };

        this.reset = function() {
            element.resizeSensor.resetSensor();
        };
    };

    ResizeSensor.reset = function(element, ev) {
        forEachElement(element, function(elem){
            elem.resizeSensor.resetSensor();
        });
    };

    ResizeSensor.detach = function(element, ev) {
        forEachElement(element, function(elem){
            if (!elem) return;
            if(elem.resizedAttached && typeof ev === "function"){
                elem.resizedAttached.remove(ev);
                if(elem.resizedAttached.length()) return;
            }
            if (elem.resizeSensor) {
                if (elem.contains(elem.resizeSensor)) {
                    elem.removeChild(elem.resizeSensor);
                }
                delete elem.resizeSensor;
                delete elem.resizedAttached;
            }
        });
    };

    return ResizeSensor;

}));


/***/ }),
/* 59 */,
/* 60 */,
/* 61 */,
/* 62 */,
/* 63 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(28);
__webpack_require__(25);
__webpack_require__(53);
__webpack_require__(34);
__webpack_require__(35);
__webpack_require__(37);
__webpack_require__(48);

__webpack_require__(36);

window.moduleAngularModules = ['task.ui.enroll', 'editor.ui.xxt', 'repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll', 'assoc.ui.enroll'];

var ngApp = __webpack_require__(18);
ngApp.controller('ctrlCowork', ['$scope', '$q', '$timeout', '$location', '$anchorScroll', '$sce', '$uibModal', 'tmsLocation', 'http2', 'noticebox', 'tmsDynaPage', 'enlTag', 'enlTopic', 'enlAssoc', 'enlTask', function($scope, $q, $timeout, $location, $anchorScroll, $sce, $uibModal, LS, http2, noticebox, tmsDynaPage, enlTag, enlTopic, enlAssoc, enlTask) {
    /**
     * 加载整条记录
     */
    function fnLoadRecord(aCoworkSchemas, oUser) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get(LS.j('repos/recordGet', 'site', 'app', 'ek')).then(function(rsp) {
            var oRecord;
            oRecord = rsp.data;
            oRecord._canAgree = fnCanAgreeRecord(oRecord, _oUser);
            $scope.record = oRecord;
            /* 设置页面分享信息 */
            $scope.setSnsShare(oRecord, null, { target_type: 'cowork', target_id: oRecord.id });
            /*页面阅读日志*/
            $scope.logAccess({ target_type: 'cowork', target_id: oRecord.id });
            /* 加载协作填写数据 */
            if (aCoworkSchemas.length) {
                oRecord.verbose = {};
                fnLoadCowork(oRecord, aCoworkSchemas);
            }
            //
            oDeferred.resolve(oRecord);
        });

        return oDeferred.promise;
    }
    /**
     * 加载关联数据
     */
    function fnLoadAssoc(oRecord, oCachedAssoc) {
        var oDeferred;
        oDeferred = $q.defer();
        http2.get(LS.j('assoc/byRecord', 'site', 'ek')).then(function(rsp) {
            if (rsp.data.length) {
                oRecord.assocs = [];
                rsp.data.forEach(function(oAssoc) {
                    if (oCachedAssoc[oAssoc.entity_a_type] === undefined)
                        oCachedAssoc[oAssoc.entity_a_type] = {};

                    switch (oAssoc.entity_a_type) {
                        case 'record':
                            if (oAssoc.entity_a_id == oRecord.id) {
                                if (oAssoc.log && oAssoc.log.assoc_text) {
                                    oAssoc.assoc_text = oAssoc.log.assoc_text;
                                }
                                oRecord.assocs.push(oAssoc);
                            }
                            break;
                        case 'data':
                            if (oCachedAssoc.data[oAssoc.entity_a_id] === undefined)
                                oCachedAssoc.data[oAssoc.entity_a_id] = [];
                            oCachedAssoc.data[oAssoc.entity_a_id].push(oAssoc);
                            break;
                    }
                });
            }
            oDeferred.resolve();
        });

        return oDeferred.promise;
    }
    /**
     * 加载协作填写数据
     */
    function fnLoadCowork(oRecord, aCoworkSchemas, bJumpTask) {
        var url, anchorItemId;
        if (/item-.+/.test($location.hash())) {
            anchorItemId = $location.hash().substr(5);
        }
        aCoworkSchemas.forEach(function(oSchema) {
            url = LS.j('data/get', 'site', 'ek') + '&schema=' + oSchema.id + '&cascaded=Y';
            http2.get(url, { autoBreak: false, autoNotice: false }).then(function(rsp) {
                var bRequireAnchorScroll;
                oRecord.verbose[oSchema.id] = rsp.data.verbose[oSchema.id];
                oRecord.verbose[oSchema.id].items.forEach(function(oItem) {
                    if (oItem.userid !== $scope.user.uid) {
                        oItem._others = true;
                    }
                    if (anchorItemId && oItem.id === anchorItemId) {
                        bRequireAnchorScroll = true;
                    }
                });
                if (bRequireAnchorScroll) {
                    $timeout(function() {
                        var elItem;
                        $anchorScroll();
                        elItem = document.querySelector('#item-' + anchorItemId);
                        elItem.classList.toggle('blink', true);
                        $timeout(function() {
                            elItem.classList.toggle('blink', false);
                        }, 1000);
                    });
                }
            });
        });
    }

    function fnAfterRecordLoad(oRecord, oUser) {
        /*设置页面导航*/
        $scope.setPopNav(['repos', 'favor', 'rank', 'kanban', 'event'], 'cowork');
    }
    /* 是否可以对记录进行表态 */
    function fnCanAgreeRecord(oRecord, oUser) {
        if (oUser.is_leader) {
            if (oUser.is_leader === 'S') {
                return true;
            }
            if (oUser.is_leader === 'Y') {
                if (oUser.group_id === oRecord.group_id) {
                    return true;
                } else if (oUser.is_editor && oUser.is_editor === 'Y') {
                    return true;
                }
            }
        }
        return false;
    }

    function fnAppendRemark(oNewRemark, oUpperRemark) {
        var oNewRemark;
        oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
        if (oUpperRemark) {
            oNewRemark.reply = '<a href="#remark-' + oUpperRemark.id + '">回复' + oUpperRemark.nickname + '的留言 #' + oUpperRemark.seq_in_record + '</a>';
        }
        $scope.remarks.push(oNewRemark);
        if (!oUpperRemark) {
            $scope.record.rec_remark_num++;
        }
        $timeout(function() {
            var elRemark;
            $location.hash('remark-' + oNewRemark.id);
            $anchorScroll();
            elRemark = document.querySelector('#remark-' + oNewRemark.id);
            elRemark.classList.toggle('blink', true);
            $timeout(function() {
                elRemark.classList.toggle('blink', false);
            }, 1000);
        });
    }

    function fnAssignTag(oRecord) {
        enlTag.assignTag(oRecord).then(function(rsp) {
            if (rsp.data.user && rsp.data.user.length) {
                oRecord.userTags = rsp.data.user;
            } else {
                delete oRecord.userTags;
            }
        });
    }

    if (!LS.s().ek) {
        noticebox.error('参数不完整');
        return;
    }
    var _oApp, _oUser, _oAssocs, _shareby;
    _shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
    $scope.options = { forQuestionTask: false, forAnswerTask: false };
    $scope.newRemark = {};
    $scope.assocs = _oAssocs = {};
    $scope.canSubmitCowork = true; // 是否允许提交协作数据
    $scope.favorStack = {
        guiding: false,
        start: function(record, timer) {
            this.guiding = true;
            this.record = record;
            this.timer = timer;
        },
        end: function() {
            this.guiding = false;
            delete this.record;
            delete this.timer;
        }
    };
    $scope.copyRecord = function(oRecord) {
        enlAssoc.copy($scope.app, { id: oRecord.id, type: 'record' });
    };
    $scope.pasteRecord = function(oRecord) {
        enlAssoc.paste($scope.user, oRecord, { id: oRecord.id, type: 'record' }).then(function(oNewAssoc) {
            if (!oRecord.assocs) oRecord.assocs = [];
            if (oNewAssoc.log) oNewAssoc.assoc_text = oNewAssoc.log.assoc_text;
            oRecord.assocs.push(oNewAssoc);
        });

    };
    $scope.removeAssoc = function(oAssoc) {
        noticebox.confirm('取消关联，确定？').then(function() {
            http2.get(LS.j('assoc/unlink', 'site') + '&assoc=' + oAssoc.id).then(function() {
                $scope.record.assocs.splice($scope.record.assocs.indexOf(oAssoc), 1);
            });
        });
    };
    $scope.editAssoc = function(oAssoc) {
        enlAssoc.update($scope.user, oAssoc);
    };
    $scope.favorRecord = function(oRecord) {
        var url;
        if (!oRecord.favored) {
            url = LS.j('favor/add', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.favored = true;
                $scope.favorStack.start(oRecord, $timeout(function() {
                    $scope.favorStack.end();
                }, 3000));
            });
        } else {
            noticebox.confirm('取消收藏，确定？').then(function() {
                url = LS.j('favor/remove', 'site');
                url += '&ek=' + oRecord.enroll_key;
                http2.get(url).then(function(rsp) {
                    delete oRecord.favored;
                });
            });
        }
    };
    $scope.assignTag = function(oRecord) {
        if (oRecord) {
            fnAssignTag(oRecord);
        } else {
            $scope.favorStack.timer && $timeout.cancel($scope.favorStack.timer);
            if (oRecord = $scope.favorStack.record) {
                fnAssignTag(oRecord);
            }
            $scope.favorStack.end();
        }
    };

    function fnAssignTopic(oRecord) {
        http2.get(LS.j('topic/list', 'site', 'app')).then(function(rsp) {
            var topics;
            if (rsp.data.total === 0) {
                location.href = LS.j('', 'site', 'app') + '&page=favor#topic';
            } else {
                topics = rsp.data.topics;
                enlTopic.assignTopic(oRecord);
            }
        });
    }
    $scope.assignTopic = function(oRecord) {
        if (oRecord) {
            fnAssignTopic(oRecord);
        } else {
            $scope.favorStack.timer && $timeout.cancel($scope.favorStack.timer);
            if (oRecord = $scope.favorStack.record) {
                fnAssignTopic(oRecord);
            }
            $scope.favorStack.end();
        }
    };
    $scope.setAgreed = function(value) {
        var url, oRecord;
        oRecord = $scope.record;
        if (oRecord.agreed !== value) {
            url = LS.j('record/agree', 'site', 'ek');
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.coworkAsRemark = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        noticebox.confirm('将填写项转为留言，确定？').then(function() {
            http2.get(LS.j('cowork/asRemark', 'site') + '&item=' + oItem.id).then(function(rsp) {
                oRecData.items.splice(index, 1);
                fnAppendRemark(rsp.data);
            });
        });
    };
    $scope.remarkAsCowork = function(oRemark) {
        var url, oSchema;
        url = LS.j('remark/asCowork', 'site');
        url += '&remark=' + oRemark.id;
        if ($scope.coworkSchemas.length === 1) {
            oSchema = $scope.coworkSchemas[0];
            url += '&schema=' + oSchema.id;
            http2.get(url).then(function(rsp) {
                var oItem;
                oItem = rsp.data;
                $scope.record.verbose[oSchema.id].items.push(oItem);
                $location.hash('item-' + oItem.id);
                $timeout(function() {
                    var elItem;
                    $anchorScroll();
                    elItem = document.querySelector('#item-' + oItem.id);
                    elItem.classList.toggle('blink', true);
                    $timeout(function() {
                        elItem.classList.toggle('blink', false);
                    }, 1000);
                });
            });
        } else {
            alert('需要指定对应的题目！');
        }
    };
    $scope.listRemark = function(oRecord) {
        $scope.transferParam = { 0: 'record', 1: oRecord };
        $scope.selectedView.url = '/views/default/site/fe/matter/enroll/template/cowork-remark.html';
    };
    $scope.likeRecord = function() {
        if ($scope.setOperateLimit('like')) {
            var oRecord;
            oRecord = $scope.record;
            http2.get(LS.j('record/like', 'site', 'ek')).then(function(rsp) {
                oRecord.like_log = rsp.data.like_log;
                oRecord.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.dislikeRecord = function() {
        if ($scope.setOperateLimit('like')) {
            var oRecord;
            oRecord = $scope.record;
            http2.get(LS.j('record/dislike', 'site', 'ek')).then(function(rsp) {
                oRecord.dislike_log = rsp.data.dislike_log;
                oRecord.dislike_num = rsp.data.dislike_num;
            });
        }
    };
    $scope.editRecord = function(event) {
        if ($scope.record.userid !== $scope.user.uid && $scope.user.is_editor !== 'Y') {
            noticebox.warn('不允许编辑其他用户提交的记录');
            return;
        }
        for (var i in $scope.app.pages) {
            var oPage = $scope.app.pages[i];
            if (oPage.type === 'I') {
                $scope.gotoPage(event, oPage.name, $scope.record.enroll_key);
                break;
            }
        }
    };
    $scope.shareRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        if (_shareby) url += '&shareby=' + _shareby;
        location.href = url;
    };
    $scope.doQuestionTask = function(oRecord) {
        //if ($scope.questionTasks && $scope.questionTasks.length) {
        if ($scope.questionTasks.length === 1) {
            http2.post(LS.j('topic/assign', 'site') + '&record=' + oRecord.id + '&task=' + $scope.questionTasks[0].id, {}).then(function() {
                noticebox.success('操作成功！');
            });
        }
        //}
    };
    $scope.transmitRecord = function(oRecord) {
        $uibModal.open({
            templateUrl: 'transmitRecord.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.result = {};
                $scope2.transmitConfig = _oApp.transmitConfig;
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    if ($scope2.result.config) {
                        $mi.close($scope2.result);
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(oResult) {
            var oConfig;
            if ((oConfig = oResult.config) && oConfig.id) {
                http2.get(LS.j('record/transmit', 'site') + '&ek=' + oRecord.enroll_key + '&transmit=' + oConfig.id).then(function(rsp) {
                    var oNewRec;
                    if (oResult.gotoNewRecord) {
                        oNewRec = rsp.data;
                        location.href = LS.j() + '?site=' + oNewRec.site + '&app=' + oNewRec.aid + '&ek=' + oNewRec.enroll_key + '&page=cowork';
                    } else {
                        noticebox.success('记录转发成功！');
                    }
                });
            }
        });
    };
    $scope.gotoUpper = function(upperId) {
        var elRemark, offsetTop, parentNode;
        elRemark = document.querySelector('#remark-' + upperId);
        offsetTop = elRemark.offsetTop;
        parentNode = elRemark.parentNode;
        while (parentNode && parentNode.tagName !== 'BODY') {
            offsetTop += parentNode.offsetTop;
            parentNode = parentNode.parentNode;
        }
        document.body.scrollTop = offsetTop - 40;
        elRemark.classList.add('blink');
        $timeout(function() {
            elRemark.classList.remove('blink');
        }, 1000);
    };
    /* 关闭任务提示 */
    $scope.closeCoworkTask = function(index) {
        $scope.coworkTasks.splice(index, 1);
    };
    $scope.closeRemarkTask = function(index) {
        $scope.remarkTasks.splice(index, 1);
    };
    $scope.gotoAssoc = function(oEntity) {
        var url;
        switch (oEntity.type) {
            case 'record':
                if (oEntity.enroll_key) url = LS.j('', 'site', 'app', 'page') + '&ek=' + oEntity.enroll_key;
                break;
            case 'topic':
                url = LS.j('', 'site', 'app') + '&page=topic' + '&topic=' + oEntity.id;
                break;
            case 'article':
                if (oEntity.entryUrl) url = oEntity.entryUrl;
                break;
        }
        if (url) location.href = url;
    };
    $scope.$on('transfer.param', function(event, data) {
        $scope.transferParam = data;
    });
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oSchemasById, aCoworkSchemas, aVisibleSchemas, templateUrl;
        _oApp = params.app;
        _oUser = params.user;
        aVisibleSchemas = [];
        aCoworkSchemas = [];
        oSchemasById = {};
        _oApp.dynaDataSchemas.forEach(function(oSchema) {
            if (oSchema.cowork === 'Y') {
                aCoworkSchemas.push(oSchema);
            } else if (oSchema.shareable && oSchema.shareable === 'Y') {
                aVisibleSchemas.push(oSchema);
            }
            oSchemasById[oSchema.id] = oSchema;
        });
        $scope.schemasById = oSchemasById;
        $scope.visibleSchemas = aVisibleSchemas;
        $scope.coworkSchemas = aCoworkSchemas;
        if (aCoworkSchemas.length) {
            $scope.fileName = 'coworkData';
        } else {
            $scope.fileName = 'remark';
        }
        templateUrl = '/views/default/site/fe/matter/enroll/template/cowork-record-' + $scope.fileName + '.html'
        $scope.selectedView = { 'url': templateUrl };
        fnLoadRecord(aCoworkSchemas).then(function(oRecord) {
            /* 通过留言完成提问任务 */
            new enlTask($scope.app).list('question', 'IP').then(function(tasks) {
                $scope.questionTasks = tasks;
            });
            new enlTask($scope.app).list('answer', 'IP', null, oRecord.enroll_key).then(function(tasks) {
                $scope.answerTasks = tasks;
            });
            if (_oApp.scenarioConfig && _oApp.scenarioConfig.can_assoc === 'Y') {
                fnLoadAssoc(oRecord, _oAssocs).then(function() {
                    fnAfterRecordLoad(oRecord, _oUser);
                });
            } else {
                fnAfterRecordLoad(oRecord, _oUser);
            }
        });
    });
}]);
/**
 * 协作题
 */
ngApp.controller('ctrlCoworkData', ['$scope', '$timeout', '$anchorScroll', '$uibModal', 'tmsLocation', 'http2', 'noticebox', 'enlAssoc', function($scope, $timeout, $anchorScroll, $uibModal, LS, http2, noticebox, enlAssoc) {
    $scope.canSubmitCowork = true; // 是否允许提交协作数据
    $scope.addItem = function(oSchema) {
        if ($scope.setOperateLimit('add_cowork')) {
            $uibModal.open({
                templateUrl: 'writeItem.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = {
                        content: ''
                    };
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var content;
                        if (window.tmsEditor && window.tmsEditor.finish) {
                            content = window.tmsEditor.finish();
                            $scope2.data.content = content;
                            $mi.close({ content: content });
                        }
                    };
                }],
                windowClass: 'modal-remark auto-height',
                backdrop: 'static',
            }).result.then(function(data) {
                if (!data.content) return;
                var oRecData, oNewItem, url;
                oRecData = $scope.record.verbose[oSchema.id];
                oNewItem = {
                    value: data.content
                };
                url = LS.j('cowork/add', 'site');
                url += '&ek=' + $scope.record.enroll_key + '&schema=' + oSchema.id;
                if ($scope.options.forAnswerTask) url += '&task=' + $scope.options.forAnswerTask;
                http2.post(url, oNewItem).then(function(rsp) {
                    var oNewItem;
                    oNewItem = rsp.data.oNewItem;
                    oNewItem.nickname = '我';
                    if (oRecData) {
                        oRecData.items.push(oNewItem);
                    } else if (rsp.data.oRecData) {
                        oRecData = $scope.record.verbose[oSchema.id] = rsp.data.oRecData;
                        oRecData.items = [oNewItem];
                    }
                    if (rsp.data.coworkResult.user_total_coin) {
                        noticebox.info('您获得【' + rsp.data.coworkResult.user_total_coin + '】分');
                    }
                });
            });
        }
    };
    $scope.editItem = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        $uibModal.open({
            templateUrl: 'writeItem.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: oItem.value
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({ content: content });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(data) {
            if (!data.content) return;
            var oNewItem;
            oNewItem = {
                value: data.content
            };
            http2.post(LS.j('cowork/update', 'site') + '&data=' + oRecData.id + '&item=' + oItem.id, oNewItem).then(function(rsp) {
                oItem.value = data.content;
            });
        });
    };
    $scope.removeItem = function(oSchema, index) {
        var oRecData, oItem;
        oRecData = $scope.record.verbose[oSchema.id];
        oItem = oRecData.items[index];
        noticebox.confirm('删除填写项，确定？').then(function() {
            http2.get(LS.j('cowork/remove', 'site') + '&item=' + oItem.id).then(function(rsp) {
                oRecData.items.splice(index, 1);
            });
        });
    };
    $scope.agreeItem = function(oItem, value) {
        var url;
        if (oItem.agreed !== value) {
            url = LS.j('data/agree', 'site', 'ek') + '&data=' + oItem.id + '&schema=' + oItem.schema_id;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oItem.agreed = value;
            });
        }
    };
    $scope.likeItem = function(oItem) {
        if ($scope.setOperateLimit('like')) {
            http2.get(LS.j('data/like', 'site') + '&data=' + oItem.id).then(function(rsp) {
                oItem.like_log = rsp.data.like_log;
                oItem.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.dislikeItem = function(oItem) {
        if ($scope.setOperateLimit('like')) {
            http2.get(LS.j('data/dislike', 'site') + '&data=' + oItem.id).then(function(rsp) {
                oItem.dislike_log = rsp.data.dislike_log;
                oItem.dislike_num = rsp.data.dislike_num;
            });
        }
    };
    $scope.listItemRemark = function(oItem) {
        $scope.$emit('transfer.param', { 0: 'coworkData', 1: oItem });
        $scope.selectedView.url = '/views/default/site/fe/matter/enroll/template/cowork-remark.html';
    };
    $scope.shareItem = function(oItem) {
        var url, shareby;
        url = LS.j('', 'site', 'app', 'ek') + '&data=' + oItem.id + '&page=share';
        shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.assocMatter = function(oItem) {
        enlAssoc.assocMatter($scope.user, $scope.record, { id: oItem.id, type: 'data' }).then(function(oAssoc) {
            var oCachedAssoc;
            oCachedAssoc = $scope.assocs;
            if (oCachedAssoc.data === undefined)
                oCachedAssoc.data = {};
            if (oCachedAssoc.data[oItem.id] === undefined)
                oCachedAssoc.data[oItem.id] = [];
            oCachedAssoc.data[oItem.id].push(oAssoc);
        });
    };
    $scope.removeItemAssoc = function(oItem, oAssoc) {
        noticebox.confirm('取消关联，确定？').then(function() {
            http2.get(LS.j('assoc/unlink', 'site') + '&assoc=' + oAssoc.id).then(function() {
                $scope.assocs.data[oItem.id].splice($scope.assocs.data[oItem.id].indexOf(oAssoc), 1);
            });
        });
    };
    $scope.doAnswerTask = function(oItem) {
        if ($scope.answerTasks && $scope.answerTasks.length) {
            if ($scope.answerTasks.length === 1) {
                http2.post(LS.j('topic/assign', 'site') + '&record=' + $scope.record.id + '&data=' + oItem.id + '&task=' + $scope.answerTasks[0].id, {}).then(function() {
                    noticebox.success('操作成功！');
                });
            }
        }
    };
    $scope.$watch('record', function(oRecord) {
        var oActionRule;
        if ($scope.app) {
            if (oActionRule = $scope.app.actionRule) {
                if (oActionRule.cowork && oActionRule.cowork.submit && oActionRule.cowork.submit.pre && oActionRule.cowork.submit.pre.editor) {
                    if (!$scope.user.is_editor || $scope.user.is_editor !== 'Y') {
                        $scope.canSubmitCowork = false;
                    }
                }
            }
        }
    }, true);
}]);
/**
 * 留言
 */
ngApp.controller('ctrlRemark', ['$scope', '$q', '$location', '$uibModal', '$anchorScroll', '$timeout', 'http2', 'tmsLocation', 'noticebox', function($scope, $q, $location, $uibModal, $anchorScroll, $timeout, http2, LS, noticebox) {
    function addRemark(content, oRemark) {
        var url;
        url = LS.j('remark/add', 'site', 'ek', 'data');
        if (oRemark) url += '&remark=' + oRemark.id;
        if ($scope.options.forQuestionTask) url += '&task=' + $scope.options.forQuestionTask;

        return http2.post(url, { content: content });
    }

    function fnAppendRemark(oNewRemark, oUpperRemark) {
        var oNewRemark;
        oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
        if (oUpperRemark) {
            oNewRemark.reply = '<a href="#remark-' + oUpperRemark.id + '">回复' + oUpperRemark.nickname + '的留言 #' + oUpperRemark.seq_in_record + '</a>';
        }
        $scope.remarks.push(oNewRemark);
        if (!oUpperRemark) {
            $scope.record.rec_remark_num++;
        }
        $timeout(function() {
            var elRemark;
            $location.hash('remark-' + oNewRemark.id);
            $anchorScroll();
            elRemark = document.querySelector('#remark-' + oNewRemark.id);
            elRemark.classList.toggle('blink', true);
            $timeout(function() {
                elRemark.classList.toggle('blink', false);
            }, 1000);
        });
    }

    function listRemarks(type, data) {
        var url;
        url = LS.j('remark/list', 'site', 'ek', 'schema', 'data');

        if (type == 'record') {
            url += '&onlyRecord=true';
        } else if (type == 'coworkData') {
            url += data.id;
        }

        http2.get(url).then(function(rsp) {
            var remarks, oRemark, oUpperRemark, oCoworkRemark, oRemarks;
            remarks = rsp.data.remarks;
            if (remarks && remarks.length) {
                oRemarks = {};
                remarks.forEach(function(oRemark) {
                    oRemarks[oRemark.id] = oRemark;
                });
                for (var i = remarks.length - 1; i >= 0; i--) {
                    oRemark = remarks[i];
                    if (oRemark.content) {
                        oRemark.content = oRemark.content.replace(/\n/g, '<br/>');
                    }
                    if (oRemark.remark_id !== '0') {
                        if (oUpperRemark = oRemarks[oRemark.remark_id]) {
                            oRemark.reply = '<a href="#remark-' + oRemark.remark_id + '">回复' + oUpperRemark.nickname + '的留言 #' + (oRemark.data_id === '0' ? oUpperRemark.seq_in_record : oUpperRemark.seq_in_data) + '</a>';
                        }
                    }
                }
            }
            $scope.remarks = remarks;
            if ($location.hash() === 'remarks') {
                $timeout(function() {
                    $anchorScroll.yOffset = 30;
                    $anchorScroll();
                });
            } else if (/remark-.+/.test($location.hash())) {
                $timeout(function() {
                    var elRemark;
                    if (elRemark = document.querySelector('#' + $location.hash())) {
                        $anchorScroll();
                        elRemark.classList.toggle('blink', true);
                        $timeout(function() {
                            elRemark.classList.toggle('blink', false);
                        }, 1000);
                    }
                });
            }
        });
    }

    function writeRemark(oUpperRemark) {
        if ($scope.setOperateLimit('add_remark')) {
            $uibModal.open({
                templateUrl: 'writeRemark.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.data = { content: '' };
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var content;
                        if (window.tmsEditor && window.tmsEditor.finish) {
                            content = window.tmsEditor.finish();
                            $scope2.data.content = content;
                            $mi.close({ content: content });
                        }
                    };
                }],
                windowClass: 'modal-remark auto-height',
                backdrop: 'static',
            }).result.then(function(data) {
                if (!data.content) return;
                addRemark(data.content, oUpperRemark).then(function(rsp) {
                    fnAppendRemark(rsp.data, oUpperRemark);
                    if (rsp.data.remarkResult.user_total_coin) {
                        noticebox.info('您获得【' + rsp.data.remarkResult.user_total_coin + '】分');
                    }
                });
            });
        }
    }

    function writeItemRemark(oItem) {
        if ($scope.setOperateLimit('add_remark')) {
            var itemRemarks;
            if ($scope.remarks && $scope.remarks.length) {
                itemRemarks = [];
                $scope.remarks.forEach(function(oRemark) {
                    if (oRemark.data_id && oRemark.data_id === oItem.id) {
                        itemRemarks.push(oRemark);
                    }
                });
            }
            $uibModal.open({
                templateUrl: 'writeRemark.html',
                controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                    $scope2.remarks = itemRemarks;
                    $scope2.data = {
                        content: ''
                    };
                    $scope2.cancel = function() { $mi.dismiss(); };
                    $scope2.ok = function() {
                        var content;
                        if (window.tmsEditor && window.tmsEditor.finish) {
                            content = window.tmsEditor.finish();
                            $scope2.data.content = content;
                            $mi.close({ content: content });
                        }
                    };
                }],
                windowClass: 'modal-remark auto-height',
                backdrop: 'static',
            }).result.then(function(data) {
                if (!data.content) return;
                http2.post(LS.j('remark/add', 'site', 'ek') + '&data=' + oItem.id, { content: data.content }).then(function(rsp) {
                    var oNewRemark;
                    oNewRemark = rsp.data;
                    oNewRemark.data = oItem;
                    oNewRemark.content = oNewRemark.content.replace(/\\n/g, '<br/>');
                    $scope.remarks.splice(0, 0, oNewRemark);
                    $timeout(function() {
                        var elRemark, parentNode, offsetTop;
                        elRemark = document.querySelector('#remark-' + oNewRemark.id);
                        parentNode = elRemark.parentNode;
                        while (parentNode && parentNode.tagName !== 'BODY') {
                            offsetTop += parentNode.offsetTop;
                            parentNode = parentNode.parentNode;
                        }
                        document.body.scrollTop = offsetTop - 40;
                        elRemark.classList.add('blink');
                        if (rsp.data.remarkResult.user_total_coin) {
                            noticebox.info('您获得【' + rsp.data.remarkResult.user_total_coin + '】分');
                        }
                        $timeout(function() {
                            elRemark.classList.remove('blink');
                        }, 1000);
                    });
                });
            });
        }
    }

    $scope.goback = function() {
        var templateUrl = '/views/default/site/fe/matter/enroll/template/cowork-record-' + $scope.fileName + '.html';
        $scope.selectedView.url = templateUrl;
    };
    $scope.editRemark = function(oRemark) {
        $uibModal.open({
            templateUrl: 'writeRemark.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {
                    content: oRemark.content
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var content;
                    if (window.tmsEditor && window.tmsEditor.finish) {
                        content = window.tmsEditor.finish();
                        $scope2.data.content = content;
                        $mi.close({ content: content });
                    }
                };
            }],
            windowClass: 'modal-remark auto-height',
            backdrop: 'static',
        }).result.then(function(data) {
            http2.post(LS.j('remark/update', 'site') + '&remark=' + oRemark.id, { content: data.content }).then(function(rsp) {
                oRemark.content = data.content;
            });
        });
    };
    $scope.removeRemark = function(oRemark) {
        noticebox.confirm('撤销留言，确定？').then(function() {
            http2.post(LS.j('remark/remove', 'site') + '&remark=' + oRemark.id).then(function(rsp) {
                $scope.remarks.splice($scope.remarks.indexOf(oRemark), 1);
            });
        });
    };
    $scope.agreeRemark = function(oRemark, value) {
        var url;
        if (oRemark.agreed !== value) {
            url = LS.j('remark/agree', 'site');
            url += '&remark=' + oRemark.id;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRemark.agreed = rsp.data;
            });
        }
    };
    $scope.likeRemark = function(oRemark) {
        if ($scope.setOperateLimit('like')) {
            var url;
            url = LS.j('remark/like', 'site');
            url += '&remark=' + oRemark.id;
            http2.get(url).then(function(rsp) {
                oRemark.like_log = rsp.data.like_log;
                oRemark.like_num = rsp.data.like_num;
            });
        }
    };
    $scope.dislikeRemark = function(oRemark) {
        if ($scope.setOperateLimit('like')) {
            var url;
            url = LS.j('remark/dislike', 'site');
            url += '&remark=' + oRemark.id;
            http2.get(url).then(function(rsp) {
                oRemark.dislike_log = rsp.data.dislike_log;
                oRemark.dislike_num = rsp.data.dislike_num;
            });
        }
    };
    $scope.shareRemark = function(oRemark) {
        var url;
        url = LS.j('', 'site', 'app', 'ek') + '&remark=' + oRemark.id + '&page=share';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.writeRemark = function(oUpperRemark) {
        if (oUpperRemark) {
            writeRemark(oUpperRemark);
        } else {
            if (!oType) {
                writeRemark();
            } else {
                switch (oType) {
                    case 'record':
                        writeRemark();
                        break;
                    case 'coworkData':
                        writeItemRemark(oData);
                        break;
                    default:
                        break;
                }
            }
        }
    };
    var oType, oData;
    $scope.$watch('transferParam', function(nv) {
        if (!nv) { return false; }
        $scope.transferType = oType = nv[0];
        $scope.transferData = oData = nv[1];
        switch (oType) {
            case 'record':
                listRemarks('record');
                break;
            case 'coworkData':
                listRemarks('coworkData', oData);
                break;
            default:
                break;
        }
    });
    if ($scope.fileName == 'remark') {
        $scope.$watch('record', function(oRecord) {
            if (oRecord) {
                listRemarks();
            }
        }, true);
    }
}]);

/***/ }),
/* 64 */,
/* 65 */,
/* 66 */,
/* 67 */,
/* 68 */,
/* 69 */,
/* 70 */,
/* 71 */,
/* 72 */,
/* 73 */,
/* 74 */,
/* 75 */,
/* 76 */,
/* 77 */,
/* 78 */,
/* 79 */,
/* 80 */,
/* 81 */,
/* 82 */,
/* 83 */,
/* 84 */,
/* 85 */,
/* 86 */,
/* 87 */,
/* 88 */,
/* 89 */,
/* 90 */,
/* 91 */,
/* 92 */,
/* 93 */,
/* 94 */,
/* 95 */,
/* 96 */,
/* 97 */,
/* 98 */,
/* 99 */,
/* 100 */,
/* 101 */,
/* 102 */,
/* 103 */,
/* 104 */,
/* 105 */,
/* 106 */,
/* 107 */,
/* 108 */,
/* 109 */,
/* 110 */,
/* 111 */,
/* 112 */,
/* 113 */,
/* 114 */,
/* 115 */,
/* 116 */,
/* 117 */,
/* 118 */,
/* 119 */,
/* 120 */,
/* 121 */,
/* 122 */,
/* 123 */,
/* 124 */,
/* 125 */,
/* 126 */,
/* 127 */,
/* 128 */,
/* 129 */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(63);


/***/ })
/******/ ]);