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
/******/ 	return __webpack_require__(__webpack_require__.s = 149);
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

/***/ 100:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".ng-cloak {\r\n\tdisplay: none;\r\n}\r\n\r\nhtml, body {\r\n\theight: 100%;\r\n\twidth: 100%;\r\n\tbackground: #efefef;\r\n\tfont-family: Microsoft Yahei, Arial;\r\n}\r\n\r\nbody {\r\n\tposition: relative;\r\n\tpadding: 15px;\r\n\tfont-size: 16px;\r\n}\r\n\r\nimg {\r\n\tmax-width: 100%\r\n}\r\n\r\nul {\r\n\tmargin: 0;\r\n\tpadding: 0;\r\n\tlist-style: none\r\n}\r\n\r\nli {\r\n\tmargin: 0;\r\n\tpadding: 0\r\n}\r\n\r\n.app {\r\n\tmargin-bottom: 65px;\r\n}\r\n\r\n/* img tiles */\r\nul.img-tiles li {\r\n\tposition: relative;\r\n\tdisplay: inline-block;\r\n\toverflow: hidden;\r\n\twidth: 80px;\r\n\theight: 80px;\r\n\tmargin: 0px;\r\n\tpadding: 0px;\r\n\tfloat: left\r\n}\r\n\r\nul.img-tiles li.img-thumbnail img {\r\n\tdisplay: inline-block;\r\n\tposition: absolute;\r\n\tmax-width: none;\r\n}\r\n\r\nul.img-tiles li.img-thumbnail button {\r\n\tposition: absolute;\r\n\ttop: 0;\r\n\tright: 0\r\n}\r\n\r\nul.img-tiles li.img-picker button {\r\n\tposition: auto;\r\n\twidth: 100%;\r\n\theight: 100%\r\n}\r\n\r\nul.img-tiles li.img-picker button span {\r\n\tfont-size: 36px\r\n}\r\n\r\n/* default form style*/\r\ndiv[wrap].wrap-splitline {\r\n\tpadding-bottom: 0.5em;\r\n\tborder-bottom: 1px solid #fff;\r\n}\r\n\r\ndiv[wrap].wrap-inline>* {\r\n\tdisplay: inline-block;\r\n\tvertical-align: middle;\r\n\tmargin: 0 1em 0 0;\r\n}\r\n\r\ndiv[wrap].wrap-inline>label {\r\n\twidth: 6em;\r\n\toverflow: hidden;\r\n\twhite-space: nowrap;\r\n\ttext-overflow: ellipsis;\r\n}\r\n\r\ndiv[wrap=matter]>span {\r\n\tcursor: pointer;\r\n\ttext-decoration: underline;\r\n}\r\n\r\n/* auth */\r\n#frmPopup {\r\n\tposition: absolute;\r\n\ttop: 0;\r\n\tleft: 0;\r\n\tright: 0;\r\n\tbottom: 0;\r\n\tborder: none;\r\n\twidth: 100%;\r\n\tz-index: 999;\r\n\tbox-sizing: border-box;\r\n}\r\n", ""]);

// exports


/***/ }),

/***/ 118:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(100);
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
		module.hot.accept("!!../../../../../../node_modules/css-loader/index.js!./signin.css", function() {
			var newContent = require("!!../../../../../../node_modules/css-loader/index.js!./signin.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),

/***/ 149:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(83);


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

/***/ 22:
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
                    var label = '';
                    oSchema.ops.forEach(function(op, index) {
                        if (val[op.v] !== undefined) {
                            label += '<div>' + op.l + ':' + val[op.v] + '</div>';
                        }
                    });
                    label = label.replace(/\s\/\s$/, '');
                    return label;
                } else if (angular.isString(val)) {
                    aVal = val.split(',');
                    oSchema.ops.forEach(function(op) {
                        aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                    });
                    val = aLab.join(',');
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

/***/ 25:
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

/***/ 26:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

window.xxt === undefined && (window.xxt = {});
window.xxt.geo = {
    options: {},
    getAddress: function(http2, deferred, siteId) {
        var promise;
        promise = deferred.promise;
        if (window.wx) {
            window.wx.getLocation({
                success: function(res) {
                    var url = '/rest/site/fe/matter/enroll/locationGet';
                    url += '?site=' + siteId;
                    url += '&lat=' + res.latitude;
                    url += '&lng=' + res.longitude;
                    http2.get(url).then(function(rsp) {
                        deferred.resolve({
                            errmsg: 'ok',
                            lat: res.latitude,
                            lng: res.longitude,
                            address: rsp.data.address
                        });
                    });
                }
            });
        } else {
            try {
                var nav = window.navigator;
                if (nav !== null) {
                    var geoloc = nav.geolocation;
                    if (geoloc !== null) {
                        geoloc.getCurrentPosition(function(position) {
                            var url = '/rest/site/fe/matter/enroll/locationGet';
                            url += '?site=' + siteId;
                            url += '&lat=' + position.coords.latitude;
                            url += '&lng=' + position.coords.longitude;
                            http2.get(url).then(function(rsp) {
                                deferred.resolve({
                                    errmsg: 'ok',
                                    lat: position.coords.latitude,
                                    lng: position.coords.longitude,
                                    address: rsp.data.address
                                });
                            });
                        }, function() {
                            deferred.resolve({
                                errmsg: '获取地理位置失败'
                            })
                        });
                    } else {
                        deferred.resolve({
                            errmsg: "无法获取地理位置"
                        });
                    }
                } else {
                    deferred.resolve({
                        errmsg: "无法获取地理位置"
                    });
                }
            } catch (e) {
                alert('exception:' + e.message);
            }
        }
        return promise;
    },
};


/***/ }),

/***/ 3:
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

/***/ 4:
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

/***/ 41:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "/*dialog*/\r\n.dialog.mask{position:fixed;background:rgba(0,0,0,0.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:998}\r\n.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}\r\n.dialog .dlg-header{padding:15px 15px 0 15px}\r\n.dialog .dlg-body{padding:15px 15px 0 15px}\r\n.dialog .dlg-footer{text-align:right;padding:15px}\r\n.dialog .dlg-footer button{border-radius:0}\r\n\r\n/*filter*/\r\ndiv[wrap=filter] .detail{background:#ccc}\r\ndiv[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}\r\ndiv[wrap=filter] .detail .actions .btn{border-radius:0}", ""]);

// exports


/***/ }),

/***/ 46:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(41);
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

/***/ 47:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var utilSubmit = {};
utilSubmit.state = {
    modified: false,
    state: 'waiting',
    _cacheKey: false,
    start: function(event, cacheKey, type) {
        var submitButton;
        if (event) {
            submitButton = event.target;
            if (submitButton.tagName === 'BUTTON' || ((submitButton = submitButton.parentNode) && submitButton.tagName === 'BUTTON')) {
                if (/submit\(.*\)/.test(submitButton.getAttribute('ng-click'))) {
                    var span;
                    this.button = submitButton;
                    span = submitButton.querySelector('span');
                    span.setAttribute('data-label', span.innerHTML);
                    span.innerHTML = '正在提交数据...';
                    this.button.classList.add('submit-running');
                }
            }
        }
        this.state = type == 'save' ? 'waiting' : 'running';
        this._cacheKey = cacheKey ? cacheKey : (new Date * 1);
    },
    finish: function(keep) {
        this.state = 'waiting';
        this.modified = false;
        if (this.button) {
            var span;
            span = this.button.querySelector('span');
            span.innerHTML = span.getAttribute('data-label');
            span.removeAttribute('data-label');
            this.button.classList.remove('submit-running');
            this.button = null;
        }
        if (window.localStorage && !keep) {
            window.localStorage.removeItem(this._cacheKey);
        }
    },
    isRunning: function() {
        return this.state === 'running';
    },
    cache: function(cachedData) {
        if (window.localStorage) {
            var key, val;
            key = this._cacheKey;
            val = angular.copy(cachedData);
            val._cacheAt = (new Date * 1);
            val = JSON.stringify(val);
            window.localStorage.setItem(key, val);
        }
    },
    fromCache: function(keep) {
        if (window.localStorage) {
            var key, val;
            key = this._cacheKey;
            val = window.localStorage.getItem(key);
            if (!keep) window.localStorage.removeItem(key);
            if (val) {
                val = JSON.parse(val);
                /*if (val._cacheAt && (val._cacheAt + 1800000) < (new Date * 1)) {
                    val = false;
                }
                delete val._cacheAt;*/
            }
        }
        return val;
    }
};
module.exports = utilSubmit;

/***/ }),

/***/ 49:
/***/ (function(module, exports) {

var __util = {};
__util.makeDialog = function(id, html) {
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
var ngMod = angular.module('directive.signin', []);
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

/***/ }),

/***/ 5:
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

/***/ 50:
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

__webpack_require__(46);

__webpack_require__(6);
__webpack_require__(3);
__webpack_require__(2);
__webpack_require__(25);
__webpack_require__(26);

__webpack_require__(49);

var setShareData = function(scope, params) {
    if (!window.xxt || !window.xxt.share) {
        return false;
    }
    var sharelink, summary;
    sharelink = location.protocol + '//' + location.host + LS.j('', 'site', 'app');
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
    window.xxt.share.options.logger = function(shareto) {};
};

/* 公共加载的模块 */
var angularModules = ['ngSanitize', 'notice.ui.xxt', 'http.ui.xxt', 'page.ui.xxt', 'directive.signin', 'snsshare.ui.xxt'];
/* 加载指定的模块 */
if (window.moduleAngularModules) {
    window.moduleAngularModules.forEach(function(m) {
        angularModules.push(m);
    });
}

var ngApp = angular.module('app', angularModules);
ngApp.config(['$controllerProvider', '$locationProvider', function($cp, $locationProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlMain', ['$scope', '$timeout', 'http2', 'tmsLocation', 'tmsDynaPage', function($scope, $timeout, http2, LS, tmsDynaPage) {
    function fnHidePageActions() {
        var domActs, domAct;
        if (domActs = document.querySelectorAll('[wrap=button]')) {
            angular.forEach(domActs, function(domAct) {
                domAct.style.display = 'none';
            });
        }
    }

    function openAskFollow() {
        http2.get(LS.j('askFollow', 'site')).then(function() {}, function(content) {
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
    $scope.gotoLottery = function(event, lottery, ek) {
        event.preventDefault();
        event.stopPropagation();
        location.replace('/rest/app/lottery?mpid=' + LS.s().mpid + '&lottery=' + lottery + '&enrollKey=' + ek);
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
            execTask(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    http2.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).then(function(rsp) {
        var params = rsp.data,
            oSite = params.site,
            oApp = params.app,
            oMission = params.mission,
            schemasById = {};

        oApp.dataSchemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        oApp._schemasById = schemasById;
        $scope.params = params;
        $scope.site = oSite;
        $scope.mission = oMission;
        $scope.app = oApp;
        $scope.user = params.user;
        $scope.activeRound = params.activeRound;
        setShareData($scope, params);
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
            angular.forEach(tasksOfOnReady, PG.exec);
        }
        $timeout(function() {
            fnHidePageActions();
            $scope.$broadcast('xxt.app.signin.ready', params);
        });
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
    });
}]);
module.exports = ngApp;

/***/ }),

/***/ 6:
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

/***/ 83:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(118);

__webpack_require__(22);

window.moduleAngularModules = ['schema.ui.xxt'];

var ngApp = __webpack_require__(50);
ngApp.oUtilSubmit = __webpack_require__(47);
ngApp.config(['$compileProvider', function($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
}]);
ngApp.factory('Input', ['http2', '$q', '$timeout', 'tmsLocation', 'tmsSchema', function(http2, $q, $timeout, LS, tmsSchema) {
    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(data, app, page) {
        var dataSchemas, oItem, oSchema, value, sCheckResult;
        if (page.data_schemas && page.data_schemas.length) {
            dataSchemas = JSON.parse(page.data_schemas);
            for (var i = 0, ii = dataSchemas.length; i < ii; i++) {
                oItem = dataSchemas[i];
                oSchema = oItem.schema;
                //定义value
                if (oSchema.id.indexOf('member.') === 0) {
                    var memberSchema = oSchema.id.substr(7);
                    if (memberSchema.indexOf('.') === -1) {
                        value = data.member[memberSchema];
                    } else {
                        memberSchema = memberSchema.split('.');
                        value = data.member.extattr[memberSchema[1]];
                    }
                } else {
                    value = data[oSchema.id];
                }
                /* 为了兼容老版本 */
                if (oSchema.required === undefined && oItem.config.required === 'Y') {
                    oSchema.required = 'Y';
                }
                if (oSchema.type && oSchema.type !== 'html') {
                    if (true !== (sCheckResult = tmsSchema.checkValue(oSchema, value))) {
                        return sCheckResult;
                    }
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(data, ek) {
        var url, d, posted;
        posted = angular.copy(data);
        if (Object.keys && Object.keys(posted.member).length === 0) {
            delete posted.member;
        }
        url = LS.j('record/submit', 'site', 'app');
        ek && ek.length && (url += '&ek=' + ek);
        for (var i in posted) {
            d = posted[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                d.forEach(function(d2) {
                    delete d2.imgSrc;
                });
            }
        }
        return http2.post(url, posted, { autoBreak: false });
    };
    return {
        ins: function() {
            if (!_ins) {
                _ins = new Input();
            }
            return _ins;
        }
    }
}]);
ngApp.directive('tmsImageInput', ['$compile', '$q', function($compile, $q) {
    var modifiedImgFields, openPickFrom, onSubmit;
    modifiedImgFields = [];
    openPickFrom = function(scope) {
        var html;
        html = "<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'camera')\">拍照</button></div>";
        html += "<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'album')\">相册</button></div>";
        html = __util.makeDialog('pickImageFrom', {
            body: html
        });
        $compile(html)(scope);
    };
    onSubmit = function(data) {
        var defer = $q.defer(),
            i = 0,
            j = 0,
            nextWxImage;
        defer.resolve('ok');
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', 'noticebox', function($scope, $timeout, noticebox) {
            $scope.beforeSubmit(function() {
                return onSubmit($scope.data);
            });
            $scope.chooseImage = function(imgFieldName, count, from) {
                if (imgFieldName !== null) {
                    modifiedImgFields.indexOf(imgFieldName) === -1 && modifiedImgFields.push(imgFieldName);
                    $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
                    if (count !== null && $scope.data[imgFieldName].length === count) {
                        noticebox.warn('最多允许上传' + count + '张图片');
                        return;
                    }
                }
                if (window.YixinJSBridge) {
                    if (from === undefined) {
                        $scope.cachedImgFieldName = imgFieldName;
                        openPickFrom($scope);
                        return;
                    }
                    imgFieldName = $scope.cachedImgFieldName;
                    $scope.cachedImgFieldName = null;
                    angular.element('#pickImageFrom').remove();
                }
                window.xxt.image.choose($q.defer(), from).then(function(imgs) {
                    var phase, i, j, img;
                    phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
                    } else {
                        $scope.$apply(function() {
                            $scope.data[imgFieldName] = $scope.data[imgFieldName].concat(imgs);
                        });
                    }
                    $timeout(function() {
                        for (i = 0, j = imgs.length; i < j; i++) {
                            img = imgs[i];
                            //if (window.wx !== undefined) {
                            document.querySelector('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img').setAttribute('src', img.imgSrc);
                            //}
                        }
                        $scope.$broadcast('xxt.signin.image.choose.done', imgFieldName);
                    });
                });
            };
            $scope.removeImage = function(imgField, index) {
                imgField.splice(index, 1);
            };
        }]
    }
}]);
ngApp.directive('tmsFileInput', ['$q', function($q) {
    var r, onSubmit;
    //require(['resumable'], function(Resumable) {
    r = new Resumable({
        target: '/rest/site/fe/matter/signin/record/uploadFile?site=' + LS.s().site + '&aid=' + LS.s().app,
        testChunks: false,
        chunkSize: 512 * 1024
    });
    //});
    onSubmit = function($scope) {
        var defer;
        defer = $q.defer();
        if (!r.files || r.files.length === 0)
            defer.resolve('empty');
        r.on('progress', function() {
            var phase, p;
            p = r.progress();
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = Math.ceil(p * 100);
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = Math.ceil(p * 100);
                });
            }
        });
        r.on('complete', function() {
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = '完成';
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = '完成';
                });
            }
            r.cancel();
            defer.resolve('ok');
        });
        r.upload();
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', function($scope) {
            $scope.progressOfUploadFile = 0;
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            $scope.chooseFile = function(fileFieldName, count, accept) {
                var ele = document.createElement('input');
                ele.setAttribute('type', 'file');
                accept !== undefined && ele.setAttribute('accept', accept);
                ele.addEventListener('change', function(evt) {
                    var i, cnt, f;
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        r.addFile(f);
                        $scope.data[fileFieldName] === undefined && ($scope.data[fileFieldName] = []);
                        $scope.data[fileFieldName].push({
                            uniqueIdentifier: r.files[0].uniqueIdentifier,
                            name: f.name,
                            size: f.size,
                            type: f.type,
                            url: ''
                        });
                    }
                    $scope.$apply('data.' + fileFieldName);
                    $scope.$broadcast('xxt.signin.file.choose.done', fileFieldName);
                }, false);
                ele.click();
            };
        }]
    }
}]);
ngApp.controller('ctrlSignin', ['$scope', 'Input', 'tmsLocation', 'noticebox', 'tmsSchema', function($scope, Input, LS, noticebox, tmsSchema) {
    function doSubmit(nextAction) {
        var ek, btnSubmit;
        ek = $scope.record ? $scope.record.enroll_key : undefined;
        facInput.submit($scope.data, ek).then(function(rsp) {
            var url;
            if (rsp.data.forword) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + rsp.data.forword;
                url += '&ek=' + rsp.data.ek;
                location.replace(url);
                noticebox.success('完成提交');
            } else if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction === '_autoForward') {
                // 根据指定的进入规则自动跳转到对应页面
                url = LS.j('', 'site', 'app');
                location.replace(url);
                noticebox.success('完成提交');
            } else if (nextAction && nextAction.length) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + nextAction;
                url += '&ek=' + rsp.data.ek;
                location.replace(url);
                noticebox.success('完成提交');
            } else {
                noticebox.success('完成提交');
                if (ek === undefined) {
                    $scope.record = {
                        enroll_key: rsp.data.ek
                    }
                }
                $scope.$broadcast('xxt.app.signin.submit.done', rsp.data);
            }
        }, function(rsp) {
            submitState.finish();
        });
    }

    function doTask(seq, nextAction) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq, nextAction) : doSubmit(nextAction);
        });
    }
    window.onbeforeunload = function() {
        // 保存未提交数据
        submitState.modified && submitState.cache($scope.data);
    };

    var facInput, submitState, tasksOfBeforeSubmit;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {
        member: {}
    };
    $scope.supplement = {};
    $scope.submitState = submitState = ngApp.oUtilSubmit.state;
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    $scope.submit = function(event, nextAction) {
        var sCheckResult, cacheKey, oApp, oRecord;
        oApp = $scope.app;
        oRecord = $scope.record;
        if (!submitState.isRunning()) {
            cacheKey = '/site/' + oApp.siteid + '/app/signin/' + oApp.id + '/record/' + (oRecord ? oRecord.enroll_key : '') + '/submit';
            submitState.start(event, cacheKey);
            if (true === (sCheckResult = facInput.check($scope.data, oApp, $scope.page))) {
                tasksOfBeforeSubmit.length ? doTask(0, nextAction) : doSubmit(nextAction);
            } else {
                submitState.finish();
                noticebox.warn(sCheckResult);
            }
        }
    };
    $scope.doAction = function(event, oAction) {
        switch (oAction.name) {
            case 'submit':
                $scope.submit(event, oAction.next);
                break;
            case 'gotoPage':
                $scope.gotoPage(event, oAction.next);
                break;
            case 'closeWindow':
                $scope.closeWindow();
                break;
        }
    };
    $scope.$on('xxt.app.signin.ready', function(event, params) {
        if (params.record) {
            tmsSchema.loadRecord(params.app._schemasById, $scope.data, params.record.data);
            $scope.record = params.record;
        }
        /* 恢复用户未提交的数据 */
        if (window.localStorage) {
            var cached = submitState.fromCache();
            if (cached) {
                if (cached.member) {
                    delete cached.member;
                }
                angular.extend($scope.data, cached);
                submitState.modified = true;
            }
        }
        // 跟踪数据变化
        $scope.$watch('data', function(nv, ov) {
            if (nv !== ov) {
                submitState.modified = true;
            }
        }, true);
    });
    var hasAutoFillMember = false;
    $scope.$watch('data.member.schema_id', function(schemaId) {
        if (false === hasAutoFillMember && schemaId && $scope.user) {
            tmsSchema.autoFillMember($scope.app._schemasById, $scope.user, $scope.data.member);
            hasAutoFillMember = true;
        }
    });
}]);

/***/ })

/******/ });