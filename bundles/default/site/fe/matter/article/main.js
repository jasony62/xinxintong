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
/******/ 	return __webpack_require__(__webpack_require__.s = 89);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
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

/***/ 1:
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

var	fixUrls = __webpack_require__(3);

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

/***/ 10:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".modal {\r\n    display: block;\r\n    overflow: hidden;\r\n    position: fixed;\r\n    top: 0;\r\n    right: 0;\r\n    bottom: 0;\r\n    left: 0;\r\n    outline: 0;\r\n    opacity: 1;\r\n    overflow-x: hidden;\r\n    overflow-y: auto;\r\n    opacity: 1;\r\n}\r\n\r\n.modal-backdrop {\r\n    position: fixed;\r\n    top: 0;\r\n    right: 0;\r\n    bottom: 0;\r\n    left: 0;\r\n    background-color: #000;\r\n    opacity: .5;\r\n}\r\n\r\n.modal-dialog {\r\n    position: relative;\r\n    z-index: 1055;\r\n    margin: 0;\r\n    position: relative;\r\n    width: auto;\r\n    margin: 10px;\r\n}\r\n\r\n.modal-content {\r\n    position: relative;\r\n    background-color: #fff;\r\n    -webkit-background-clip: padding-box;\r\n    background-clip: padding-box;\r\n    border: 1px solid #999;\r\n    border: 1px solid rgba(0, 0, 0, .2);\r\n    border-radius: 6px;\r\n    outline: 0;\r\n    -webkit-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);\r\n    box-shadow: 0 3px 9px rgba(0, 0, 0, .5);\r\n}\r\n\r\n.modal-header {\r\n    padding: 15px;\r\n    border-bottom: 1px solid #e5e5e5;\r\n}\r\n\r\n.modal-header .close {\r\n    margin-top: -2px;\r\n}\r\n\r\n.modal-title {\r\n    margin: 0;\r\n    line-height: 1.42857143;\r\n}\r\n\r\n.modal-body {\r\n    position: relative;\r\n    padding: 15px;\r\n}\r\n\r\n.modal-footer {\r\n    padding: 15px;\r\n    text-align: right;\r\n    border-top: 1px solid #e5e5e5;\r\n}\r\n\r\nbutton.close {\r\n    -webkit-appearance: none;\r\n    padding: 0;\r\n    cursor: pointer;\r\n    background: 0 0;\r\n    border: 0;\r\n}\r\n\r\n.close {\r\n    float: right;\r\n    font-size: 21px;\r\n    font-weight: 700;\r\n    line-height: 1;\r\n    color: #000;\r\n    text-shadow: 0 1px 0 #fff;\r\n    filter: alpha(opacity=20);\r\n    opacity: .2;\r\n}\r\n\r\n@media (min-width:768px) {\r\n    .modal-dialog {\r\n        width: 600px;\r\n        margin: 30px auto;\r\n    }\r\n    .modal-content {\r\n        -webkit-box-shadow: 0 5px 15px rgba(0, 0, 0, .5);\r\n        box-shadow: 0 5px 15px rgba(0, 0, 0, .5);\r\n    }\r\n}\r\n", ""]);

// exports


/***/ }),

/***/ 14:
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
            var url = 'http://' + location.host;
            url += '/rest/site/fe/coin/pay';
            url += "?site=" + siteId;
            url += "&matter=" + matter;
            openPlugin(url);
        }, true);
        document.body.appendChild(eSwitch);
    }
});


/***/ }),

/***/ 15:
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
            var url = 'http://' + location.host;
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

/***/ 23:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(5);

__webpack_require__(2);
__webpack_require__(4);

var ngMod = angular.module('forward.ui.xxt', ['page.ui.xxt', 'modal.ui.xxt']);
ngMod.service('tmsForward', ['$rootScope', '$http', '$q', 'tmsDynaPage', 'tmsModal', function($rootScope, $http, $q, tmsDynaPage, tmsModal) {
    function bySite(oMatter) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/pl/fe/site/forward/sitesByUser?site=' + oMatter.siteid + '&id=' + oMatter.id + '&type=' + oMatter.type + '&_=' + (new Date() * 1);
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                return;
            }
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }
    this.open = function(oMatter) {
        var template;
        template = '<div class="modal-header"><span class="modal-title">转发到哪个团队和频道</span></div>';
        template += '<div class="modal-body">';
        template += '<div ng-repeat="site in mySites">';
        template += '<span>{{site.name}}</span>';
        template += '<div class="checkbox" ng-repeat="chn in site.homeChannels">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'chn._selected\' ng-change="choose(site,chn)">';
        template += '<span>{{chn.title}}</span>';
        template += '</label>';
        template += '</div>'
        template += '<div ng-if="site.homeChannels.length===0"><a href="" ng-click="createChannel(site)">创建</a>团队主页频道，转发内容到团队主页</div>';
        template += '</div>'
        template += '<div ng-if="mySites.length===0"><a href="" ng-click="createSite()">创建</a>团队，转发内容到团队主页</div>';
        template += '</div>';
        template += '<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button></div>';
        tmsModal.open({
            template: template,
            controller: ['$http', '$scope', '$tmsModalInstance', function($http, $scope2, $mi) {
                var aSelected = [];
                bySite(oMatter).then(function(sites) {
                    var mySites = sites;
                    mySites.forEach(function(site) {
                        site._selected = site._recommended;
                    });
                    $scope2.mySites = mySites;
                });
                $scope2.createChannel = function(site) {
                    $http.post('/rest/pl/fe/matter/channel/create?site=' + site.id, {}).success(function(rsp) {
                        var oChannel = rsp.data;
                        $http.post('/rest/pl/fe/site/setting/page/addHomeChannel?site=' + site.id, oChannel).success(function(rsp) {
                            site.homeChannels.push(rsp.data);
                        });
                    });
                };
                $scope2.createSite = function() {
                    $http.get('/rest/pl/fe/site/create').success(function(rsp) {
                        var site = rsp.data;
                        site._selected = 'N';
                        site.homeChannels = [];
                        $scope2.mySites = [site];
                    });
                };
                $scope2.choose = function(oSite, oChannel) {
                    if (oChannel._selected === 'Y') {
                        oChannel.siteid = oSite.id;
                        aSelected.push(oChannel);
                    } else {
                        aSelected.splice(aSelected.indexOf(oChannel), 1);
                    }
                };
                $scope2.ok = function() {
                    var aTargets = [];
                    if (aSelected.length) {
                        aSelected.forEach(function(oChannel) {
                            aTargets.push({ siteid: oChannel.siteid, channelId: oChannel.channel_id });
                        });
                        $http.post('/rest/pl/fe/site/forward/push?id=' + oMatter.id + '&type=' + oMatter.type, aTargets).success(function() {
                            $mi.close();
                        });
                    }
                };
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
            }]
        });
    };
    this.showSwitch = function(oUser, oMatter) {
        var _this = this,
            eSwitch;
        eSwitch = document.createElement('div');
        eSwitch.classList.add('tms-switch', 'tms-switch-forward');
        eSwitch.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $rootScope.$apply(function() {
                if (!oUser.loginExpire) {
                    tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                        oUser.loginExpire = data.loginExpire;
                        _this.open(oMatter);
                    });
                } else {
                    _this.open(oMatter);
                }
            });
        }, true);
        document.body.appendChild(eSwitch);
    };
}]);


/***/ }),

/***/ 24:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(5);

__webpack_require__(4);

var ngMod = angular.module('subscribe.ui.xxt', ['modal.ui.xxt']);
ngMod.service('tmsSubscribe', ['$http', 'tmsModal', function($http, tmsModal) {
    this.open = function(oUser, oSite) {
        var template;
        template = '<div class="modal-header"><span class="modal-title">关注团队，接收该团队发布的内容</span></div>';
        template += '<div class="modal-body">';
        template += '<div class="checkbox">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'atSite._selected\'>';
        template += '<span>个人账户</span>';
        template += '<span ng-if="atSite._subscribed===\'Y\'">（已关注）</span>';
        template += '</label>';
        template += '</div>';
        template += '<div class="checkbox" ng-repeat="site in mySites">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'site._selected\'>';
        template += '<span>{{site.name}}</span>';
        template += '<span ng-if="site._subscribed===\'Y\'">（已关注）</span>';
        template += '</label>';
        template += '</div>'
        template += '<div ng-if="mySites.length===0"><a href="" ng-click="createSite()">创建</a>团队进行关注，方便团队内共享信息</div>';
        template += '</div>';
        template += '<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button></div>';
        tmsModal.open({
            template: template,
            controller: ['$scope', '$tmsModalInstance', function($scope2, $mi) {
                $http.get('/rest/site/home/get?site=' + oSite.id + '&_=' + (new Date() * 1)).success(function(rsp) {
                    var atSite = rsp.data;
                    atSite._selected = atSite._subscribed;
                    $scope2.atSite = atSite;
                });
                $http.get('/rest/pl/fe/site/subscribe/sitesByUser?site=' + oSite.id + '&_=' + (new Date() * 1)).success(function(rsp) {
                    if (rsp.err_code != 0) {
                        return;
                    }
                    var mySites = rsp.data;
                    mySites.forEach(function(site) {
                        site._selected = site._subscribed;
                    });
                    $scope2.mySites = mySites;
                });
                $scope2.createSite = function() {
                    $http.get('/rest/pl/fe/site/create').success(function(rsp) {
                        var site = rsp.data;
                        site._subscribed = site._selected = 'N';
                        $scope2.mySites = [site];
                    })
                };
                $scope2.ok = function() {
                    var result;
                    result = {
                        atSite: $scope2.atSite,
                        mySites: $scope2.mySites
                    }
                    $mi.close(result);
                };
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
            }]
        }).result.then(function(result) {
            var url, atSite, mySites;
            atSite = result.atSite;
            if (atSite && atSite._selected !== atSite._subscribed) {
                if (atSite._selected === 'Y') {
                    url = '/rest/site/fe/user/site/subscribe?site=' + oSite.id + '&target=' + atSite.id;
                } else {
                    url = '/rest/site/fe/user/site/unsubscribe?site=' + oSite.id + '&target=' + atSite.id;
                }
                $http.get(url);
            }
            mySites = result.mySites;
            if (mySites) {
                var subscribed = [],
                    unsubscribed = [];
                mySites.forEach(function(site) {
                    if (site._selected !== site._subscribed) {
                        if (site._selected === 'Y') {
                            subscribed.push(site.id);
                        } else {
                            unsubscribed.push(site.id);
                        }
                    }
                });
                if (subscribed.length) {
                    var url = '/rest/pl/fe/site/subscribe/do?site=' + oSite.id;
                    url += '&subscriber=' + subscribed.join(',');
                    $http.get(url);
                }
            }
        });
    };
}]);


/***/ }),

/***/ 3:
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

/***/ 34:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


__webpack_require__(2);
__webpack_require__(15);
__webpack_require__(24);
__webpack_require__(9);
__webpack_require__(23);
__webpack_require__(14);
__webpack_require__(7);

var ngApp = angular.module('app', ['ui.bootstrap', 'page.ui.xxt', 'snsshare.ui.xxt', 'siteuser.ui.xxt', 'subscribe.ui.xxt', 'favor.ui.xxt', 'forward.ui.xxt', 'coinpay.ui.xxt']);
ngApp.config(['$controllerProvider', function($cp) {
    ngApp.provider = {
        controller: $cp.register
    };
}]);
ngApp.directive('tmsScroll', [function() {
    function _endScroll(event, $scope) {
        var target = event.target,
            scrollTop = target.scrollTop;

        if (scrollTop === 0) {
            if ($scope.$parent.uppermost) {
                $scope.$parent.uppermost(target);
            }
        } else if (scrollTop === target.scrollHeight - target.clientHeight) {
            if ($scope.$parent.downmost) {
                $scope.$parent.downmost(target);
            }
        } else {
            if (target.__lastScrollTop === undefined || scrollTop > target.__lastScrollTop) {
                if ($scope.$parent.upward) {
                    $scope.$parent.upward(target);
                }
            } else {
                if ($scope.$parent.downward) {
                    $scope.$parent.downward(target);
                }
            }
        }
        target.__lastScrollTop = scrollTop;
    }

    function _domReady($scope, elems) {
        for (var i = elems.length - 1; i >= 0; i--) {
            if (elems[i].scrollHeight === elems[i].clientHeight) {
                if ($scope.downmost && angular.isString($scope.downmost) && $scope.$parent.downmost) {
                    $scope.$parent.downmost(elems[i]);
                }
            }
        }
    }

    return {
        restrict: 'EA',
        scope: {
            upward: '@',
            downward: '@',
            uppermost: '@',
            downmost: '@',
            ready: '=',
        },
        link: function($scope, elems, attrs) {
            if (attrs.ready) {
                $scope.$watch('ready', function(ready) {
                    if (ready === 'Y') {
                        _domReady($scope, elems);
                    }
                });
            } else {
                /* link发生在load之前 */
                window.addEventListener('load', function() {
                    _domReady($scope, elems);
                });
            }
            for (var i = elems.length - 1; i >= 0; i--) {
                elems[i].onscroll = function(event) {
                    var target = event.target;
                    if (target.__timer) {
                        clearTimeout(target.__timer);
                    }
                    target.__timer = setTimeout(function() {
                        _endScroll(event, $scope);
                    }, 35);
                };
            }
        }
    };
}]);
ngApp.filter('filesize', function() {
    return function(length) {
        var unit;
        if (length / 1024 < 1) {
            unit = 'B';
        } else {
            length = length / 1024;
            if (length / 1024 < 1) {
                unit = 'K';
            } else {
                length = length / 1024;
                unit = 'M';
            }
        }
        length = (new Number(length)).toFixed(2);

        return length + unit;
    };
});
ngApp.controller('ctrlMain', ['$scope', '$http', '$timeout', '$q', 'tmsDynaPage', 'tmsSubscribe', 'tmsSnsShare', 'tmsCoinPay', 'tmsFavor', 'tmsForward', 'tmsSiteUser', function($scope, $http, $timeout, $q, tmsDynaPage, tmsSubscribe, tmsSnsShare, tmsCoinPay, tmsFavor, tmsForward, tmsSiteUser) {
    var width = document.body.clientWidth;
    $scope.width = width;

    function finish() {
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
    }

    function articleLoaded() {
        finish();
        $timeout(function() {
            var audios;
            audios = document.querySelectorAll('audio');
            audios.length > 0 && audios[0].play();
        });
        $scope.code = '/rest/site/fe/matter/article/qrcode?site=' + siteId + '&url=' + encodeURIComponent(location.href);
        if (window.sessionStorage) {
            var pendingMethod;
            if (pendingMethod = window.sessionStorage.getItem('xxt.site.fe.matter.article.auth.pending')) {
                window.sessionStorage.removeItem('xxt.site.fe.matter.article.auth.pending');
                if ($scope.user.loginExpire) {
                    pendingMethod = JSON.parse(pendingMethod);
                    $scope[pendingMethod.name].apply($scope, pendingMethod.args || []);
                }
            }
        }
    }

    function loadArticle() {
        var deferred = $q.defer();
        $http.get('/rest/site/fe/matter/article/get?site=' + siteId + '&id=' + id).success(function(rsp) {
            var site = rsp.data.site,
                mission = rsp.data.mission,
                oArticle = rsp.data.article,
                channels = oArticle.channels;

            if (oArticle.use_site_header === 'Y' && site && site.header_page) {
                tmsDynaPage.loadCode(ngApp, site.header_page);
            }
            if (oArticle.use_mission_header === 'Y' && mission && mission.header_page) {
                tmsDynaPage.loadCode(ngApp, mission.header_page);
            }
            if (oArticle.use_mission_footer === 'Y' && mission && mission.footer_page) {
                tmsDynaPage.loadCode(ngApp, mission.footer_page);
            }
            if (oArticle.use_site_footer === 'Y' && site && site.footer_page) {
                tmsDynaPage.loadCode(ngApp, site.footer_page);
            }
            if (channels && channels.length) {
                for (var i = 0, l = channels.length, channel; i < l; i++) {
                    channel = channels[i];
                    if (channel.style_page) {
                        tmsDynaPage.loadCode(ngApp, channel.style_page);
                    }
                }
            }
            $scope.site = site;
            $scope.mission = mission;
            $scope.article = oArticle;
            $scope.user = rsp.data.user;
            /* 设置分享 */
            if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
                var shareid, sharelink, shareby;
                shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
                shareid = $scope.user.uid + '_' + (new Date() * 1);
                sharelink = location.protocol + '//' + location.hostname + '/rest/site/fe/matter';
                sharelink += '?site=' + siteId;
                sharelink += '&type=article';
                sharelink += '&id=' + id;
                sharelink += "&shareby=" + shareid;
                tmsSnsShare.config({
                    siteId: siteId,
                    logger: function(shareto) {
                        var url = "/rest/site/fe/matter/logShare";
                        url += "?shareid=" + shareid;
                        url += "&site=" + siteId;
                        url += "&id=" + id;
                        url += "&type=article";
                        url += "&title=" + oArticle.title;
                        url += "&shareto=" + shareto;
                        url += "&shareby=" + shareby;
                        $http.get(url);
                    },
                    jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage']
                });
                tmsSnsShare.set(oArticle.title, sharelink, oArticle.summary, oArticle.pic);
            }

            if (oArticle.can_picviewer === 'Y') {
                tmsDynaPage.loadScript(['/static/js/hammer.min.js', '/asset/js/xxt.ui.picviewer.js']);
            }
            //if (!document.querySelector('.tms-switch-favor')) {
            //tmsFavor.showSwitch($scope.user, oArticle);
            //} else {
            // $scope.favor = function(user, article) {
            //     if (!user.loginExpire) {
            //         tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
            //             user.loginExpire = data.loginExpire;
            //             tmsFavor.open(article);
            //         });
            //     } else {
            //         tmsFavor.open(article);
            //     }
            // };
            //}
            // if (!document.querySelector('.tms-switch-forward')) {
            //     tmsForward.showSwitch($scope.user, oArticle);
            // } else {
            //     $scope.forward = function(user, article) {
            //         if (!user.loginExpire) {
            //             tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/login?site=' + article.siteid).then(function(data) {
            //                 user.loginExpire = data.loginExpire;
            //                 tmsForward.open(article);
            //             });
            //         } else {
            //             tmsForward.open(article);
            //         }
            //     };
            // }
            // if (oArticle.can_coinpay === 'Y') {
            //     if (!document.querySelector('.tms-switch-coinpay')) {
            //         tmsCoinPay.showSwitch(oArticle.siteid, 'article,' + oArticle.id);
            //     }
            // }
            if (oArticle.can_siteuser === 'Y') {
                //if (!document.querySelector('.tms-switch-siteuser')) {
                //    tmsSiteUser.showSwitch(oArticle.siteid, true);
                //} else {
                $scope.siteUser = function(id) {
                    var url = location.protocol + '//' + location.host;
                    url += '/rest/site/fe/user';
                    url += "?site=" + siteId;
                    location.href = url;
                };
                //}
            }
            if (!_bPreview) {
                $http.post('/rest/site/fe/matter/logAccess?site=' + siteId + '&id=' + id + '&type=article&title=' + oArticle.title + '&shareby=' + shareby, {
                    search: location.search.replace('?', ''),
                    referer: document.referrer
                });
            }
            $scope.dataReady = 'Y';
            deferred.resolve();
        }).error(function(content, httpCode) {
            finish();
            if (httpCode === 401) {
                tmsDynaPage.openPlugin(content).then(function() {
                    loadArticle().then(articleLoaded);
                });
            } else {
                alert(content);
            }
        });
        return deferred.promise;
    };

    var ls, siteId, id, _bPreview;

    ls = location.search;
    siteId = ls.match(/[\?&]site=([^&]*)/)[1];
    id = ls.match(/[\?|&]id=([^&]*)/)[1];
    _bPreview = ls.match(/[\?|&]preview=Y/);

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
    $scope.openChannel = function(ch) {
        location.href = '/rest/site/fe/matter?site=' + siteId + '&type=channel&id=' + ch.id;
    };
    $scope.searchByTag = function(tag) {
        location.href = '/rest/site/fe/matter/article?site=' + siteId + '&tagid=' + tag.id;
    };
    $scope.openMatter = function(evt, id, type) {
        evt.preventDefault();
        evt.stopPropagation();
        if (/article|custom|news|channel|link/.test(type)) {
            location.href = '/rest/site/fe/matter?site=' + siteId + '&id=' + id + '&type=' + type;
        } else {
            location.href = '/rest/site/fe/matter/' + type + '?site=' + siteId + '&app=' + id;
        }
    };
    $scope.gotoNavApp = function(oNavApp) {
        if (oNavApp.id) {
            location.href = '/rest/site/fe/matter/enroll?site=' + $scope.article.siteid + '&app=' + oNavApp.id;
        }
    };
    $scope.subscribeSite = function() {
        if (!$scope.user.loginExpire) {
            if (window.sessionStorage) {
                var method = JSON.stringify({
                    name: 'subscribeSite',
                });
                window.sessionStorage.setItem('xxt.site.fe.matter.article.auth.pending', method);
            }
            location.href = '/rest/site/fe/user/access?site=platform#login';
        } else {
            tmsSubscribe.open($scope.user, $scope.site);
        }
    };
    loadArticle().then(articleLoaded);
}]);

/***/ }),

/***/ 4:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('modal.ui.xxt', []);
ngMod.service('tmsModal', ['$rootScope', '$compile', '$q', '$controller', function($rootScope, $compile, $q, $controller) {
    this.open = function(modalOptions) {
        var modalResultDeferred = $q.defer(),
            modalClosedDeferred = $q.defer();

        var modalInstance = {
            result: modalResultDeferred.promise,
            closed: modalClosedDeferred.promise,
            close: function(result) {
                document.body.removeChild(modalDomEl[0]);
                modalResultDeferred.resolve(result);
            },
            dismiss: function(reason) {
                document.body.removeChild(modalDomEl[0]);
                modalClosedDeferred.resolve(reason);
            }
        };

        var modalScope;
        modalScope = $rootScope.$new(true);
        if (modalOptions.controller) {
            $controller(modalOptions.controller, { $scope: modalScope, $tmsModalInstance: modalInstance });
        }

        var contentDomEl, dialogDomEl, backdropDomEl, modalDomEl;
        /* content */
        contentDomEl = angular.element('<div></div>');
        contentDomEl.attr({
            'class': 'modal-content',
            'ng-style': '{\'z-index\':1060}'
        }).append(modalOptions.template);

        /* dialog */
        dialogDomEl = angular.element('<div></div>');
        dialogDomEl.attr({
            'class': 'modal-dialog'
        }).append(contentDomEl);

        /* backdrop */
        backdropDomEl = angular.element('<div></div>');
        backdropDomEl.attr({
            'class': 'modal-backdrop',
            'ng-style': '{\'z-index\':1040}'
        });

        /* modal */
        modalDomEl = angular.element('<div></div>');
        modalDomEl.attr({
            'class': 'modal',
            'ng-style': '{\'z-index\':1050}',
            'tabindex': -1
        }).append(dialogDomEl).append(backdropDomEl);

        $compile(modalDomEl)(modalScope);
        document.body.appendChild(modalDomEl[0]);

        return modalInstance;
    };
}]);


/***/ }),

/***/ 5:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(10);
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
		module.hot.accept("!!../../node_modules/css-loader/index.js!./xxt.ui.modal.css", function() {
			var newContent = require("!!../../node_modules/css-loader/index.js!./xxt.ui.modal.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),

/***/ 7:
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

     this.config = function(options) {
         this.options = options;
     };
     this.set = function(title, link, desc, img, fnOther) {
         var _this = this;
         // 将图片的相对地址改为绝对地址
         img && img.indexOf(location.protocol) === -1 && (img = location.protocol + '//' + location.host + img);
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
                 }, false);
             } else {
                 setYxShare(title, link, desc, img, _this.options);
             }
         } else if (fnOther && typeof fnOther === 'function') {
             fnOther(title, link, desc, img);
         }
     };
 }]);

/***/ }),

/***/ 89:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(34);


/***/ }),

/***/ 9:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(5);

__webpack_require__(2);
__webpack_require__(4);

var ngMod = angular.module('favor.ui.xxt', ['page.ui.xxt', 'modal.ui.xxt']);
ngMod.service('tmsFavor', ['$rootScope', '$http', '$q', 'tmsDynaPage', 'tmsModal', function($rootScope, $http, $q, tmsDynaPage, tmsModal) {
    function byPerson(oMatter) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/site/fe/user/favor/byUser';
        url += "?site=" + oMatter.siteid;
        url += "&id=" + oMatter.id;
        url += "&type=" + oMatter.type;
        $http.get(url).success(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function favorByPerson(oMatter) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/site/fe/user/favor/add';
        url += "?site=" + oMatter.siteid;
        url += "&id=" + oMatter.id;
        url += "&type=" + oMatter.type;
        $http.get(url).success(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function unfavorByPerson(oMatter) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/site/fe/user/favor/remove';
        url += "?site=" + oMatter.siteid;
        url += "&id=" + oMatter.id;
        url += "&type=" + oMatter.type;
        $http.get(url).success(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function bySite(oMatter) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/pl/fe/site/favor/sitesByUser?site=' + oMatter.siteid + '&id=' + oMatter.id + '&type=' + oMatter.type + '&_=' + (new Date() * 1);
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                return;
            }
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function favorBySite(oMatter, $aTargetSiteIds) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/pl/fe/site/favor/add?id=' + oMatter.id + '&type=' + oMatter.type;
        $http.post(url, $aTargetSiteIds).success(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    function unfavorBySite(oMatter, $aTargetSiteIds) {
        var url, defer;
        defer = $q.defer();
        url = '/rest/pl/fe/site/favor/remove?id=' + oMatter.id + '&type=' + oMatter.type;
        $http.post(url, $aTargetSiteIds).success(function(rsp) {
            defer.resolve(rsp.data);
        });
        return defer.promise;
    }

    this.open = function(oMatter) {
        var template;
        template = '<div class="modal-header"><span class="modal-title">指定收藏位置</span></div>';
        template += '<div class="modal-body">';
        template += '<div class="checkbox">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'person._selected\'>';
        template += '<span>个人账户</span>';
        template += '<span ng-if="person._favored===\'Y\'">（已收藏）</span>';
        template += '</label>';
        template += '</div>';
        template += '<div class="checkbox" ng-repeat="site in mySites">';
        template += '<label>';
        template += '<input type=\'checkbox\' ng-true-value="\'Y\'" ng-false-value="\'N\'" ng-model=\'site._selected\'>';
        template += '<span>{{site.name}}</span>';
        template += '<span ng-if="site._favored===\'Y\'">（已收藏）</span>';
        template += '</label>';
        template += '</div>'
        template += '<div ng-if="mySites.length===0"><a href="" ng-click="createSite()">创建</a>团队进行收藏，方便团队内共享信息</div>';
        template += '</div>';
        template += '<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button></div>';
        tmsModal.open({
            template: template,
            controller: ['$scope', '$tmsModalInstance', function($scope2, $mi) {
                byPerson(oMatter).then(function(log) {
                    $scope2.person = {
                        _favored: log ? 'Y' : 'N'
                    };
                    $scope2.person._selected = $scope2.person._favored;
                });
                bySite(oMatter).then(function(sites) {
                    var mySites = sites;
                    mySites.forEach(function(site) {
                        site._selected = site._favored;
                    });
                    $scope2.mySites = mySites;
                });
                $scope2.createSite = function() {
                    $http.get('/rest/pl/fe/site/create').success(function(rsp) {
                        var site = rsp.data;
                        site._favored = site._selected = 'N';
                        $scope2.mySites = [site];
                    })
                };
                $scope2.ok = function() {
                    var result;
                    result = {
                        person: $scope2.person,
                        mySites: $scope2.mySites
                    }
                    $mi.close(result);
                };
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
            }]
        }).result.then(function(result) {
            var url, oPerson, mySites;
            oPerson = result.person;
            if (oPerson && oPerson._selected !== oPerson._favored) {
                if (oPerson._selected === 'Y') {
                    favorByPerson(oMatter);
                } else {
                    unfavorByPerson(oMatter);
                }
            }
            mySites = result.mySites;
            if (mySites) {
                var favored = [],
                    unfavored = [];
                mySites.forEach(function(site) {
                    if (site._selected !== site._favored) {
                        if (site._selected === 'Y') {
                            favored.push(site.id);
                        } else {
                            unfavored.push(site.id);
                        }
                    }
                });
                if (favored.length) {
                    favorBySite(oMatter, favored);
                }
                if (unfavored.length) {
                    unfavorBySite(oMatter, unfavored);
                }
            }
        });
    };
    this.showSwitch = function(oUser, oMatter) {
        var _this = this,
            eSwitch;
        eSwitch = document.createElement('div');
        eSwitch.classList.add('tms-switch', 'tms-switch-favor');
        eSwitch.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            $rootScope.$apply(function() {
                if (!oUser.loginExpire) {
                    tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                        oUser.loginExpire = data.loginExpire;
                        _this.open(oMatter);
                    });
                } else {
                    _this.open(oMatter);
                }
            })
        }, true);
        document.body.appendChild(eSwitch);
    };
}]);


/***/ })

/******/ });