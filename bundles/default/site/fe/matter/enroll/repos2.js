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
/******/ 	return __webpack_require__(__webpack_require__.s = 98);
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

/***/ 11:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".dialog.mask{position:fixed;background:rgba(0,0,0,.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:1060}.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}.dialog .dlg-header{padding:15px 15px 0 15px}.dialog .dlg-body{padding:15px 15px 0 15px}.dialog .dlg-footer{text-align:right;padding:15px}.dialog .dlg-footer button{border-radius:0}div[wrap=filter] .detail{background:#ccc}div[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}div[wrap=filter] .detail .actions .btn{border-radius:0}.tms-act-toggle{position:fixed;right:15px;bottom:8px;width:48px;height:48px;line-height:48px;box-shadow:0 2px 6px rgba(18,27,32,.425);color:#fff;background:#ff8018;border:1px solid #ff8018;border-radius:24px;font-size:20px;text-align:center;cursor:pointer;z-index:1050}.tms-nav-target>*+*{margin-top:.5em}#frmPlugin{position:absolute;top:0;bottom:0;left:0;right:0;width:100%;height:100%;border:none;z-index:1060;box-sizing:border-box;padding-bottom:48px;background:#fff}#frmPlugin iframe{width:100%;height:100%;border:0}#frmPlugin:after{content:'\\5173\\95ED';position:absolute;width:100px;text-align:center;left:50%;margin-left:-50px;bottom:4px;padding:5px 6px 3px;border:1px solid #ccc;border-radius:4px}div[wrap]>.description{word-wrap:break-word}", ""]);

// exports


/***/ }),

/***/ 12:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(11);
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

/***/ 13:
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
ngMod.directive('tmsAppNav', ['$templateCache', function($templateCache) {
    var html;
    html = "<div class='tms-nav-target'>";
    html += "<div ng-if=\"navs.repos\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'repos')\">共享页</button></div>";
    html += "<div ng-if=\"navs.rank\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'rank')\">排行页</button></div>";
    html += "<div ng-if=\"navs.action\"><button class='btn btn-default btn-block' ng-click=\"goto($event,'action')\">动态页<span class='notice-count' ng-if=\"noticeCount\" ng-bind=\"noticeCount\"></span></button></div>";
    html += "</div>";
    $templateCache.put('appNavTemplate.html', html);
    return {
        restrict: 'A',
        replace: true,
        transclude: true,
        scope: {
            navs: '=appNavs',
            noticeCount: '=noticeCount'
        },
        template: "<span><span ng-if=\"!navs\"><span ng-transclude></span></span><span ng-if=\"navs\" uib-popover-template=\"'appNavTemplate.html'\" popover-placement=\"bottom\" popover-trigger=\"'outsideClick'\"><span ng-transclude></span><span class='notice-count' ng-if=\"noticeCount\" ng-bind=\"noticeCount\"></span><span class=\"caret\"></span></span></span>",
        controller: ['$scope', function($scope) {
            $scope.goto = function(event, page) {
                $scope.$parent.gotoPage(event, page);
            };
        }]
    };
}]);
ngMod.directive('tmsAppAct', ['$templateCache', function($templateCache) {
    var html;
    html = "<div>";
    html += "<div ng-if=\"acts.addRecord\"><button class='btn btn-default' ng-click=\"goto($event,'addRecord')\">添加记录</button></div>";
    html += "<div ng-if=\"acts.save\"><button class='btn btn-default' ng-click=\"goto($event,'save')\">保存</button></div>";
    html += "</div>";
    $templateCache.put('appActTemplate.html', html);
    return {
        restrict: 'A',
        replace: true,
        scope: {
            acts: '=appActs'
        },
        template: "<button uib-popover-template=\"'appActTemplate.html'\" popover-placement=\"top-right\" popover-trigger=\"'outsideClick'\" popover-append-to-body=\"true\" class=\"tms-act-toggle\" popover-class=\"tms-act-popover\"><span class='glyphicon glyphicon-option-vertical'></span></button>",
        controller: ['$scope', function($scope) {
            $scope.back = function() {
                history.back();
            };
            $scope.historyLen = function() {
                return history.length;
            };
            $scope.goto = function(event, page) {
                switch (page) {
                    case 'addRecord':
                        $scope.$parent.addRecord(event);
                        break;
                    case 'save':
                        $scope.$parent.save();
                        break;
                    default:
                        $scope.$parent.gotoPage(event, page);
                }
            };
        }]
    };
}]);
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
ngMod.directive('tmsCheckboxGroup', function() {
    return {
        restrict: 'A',
        link: function(scope, elem, attrs) {
            var groupName, model, options, upper;
            if (attrs.tmsCheckboxGroup && attrs.tmsCheckboxGroup.length) {
                groupName = attrs.tmsCheckboxGroup;
                if (attrs.tmsCheckboxGroupModel && attrs.tmsCheckboxGroupModel.length) {
                    model = attrs.tmsCheckboxGroupModel;
                    if (attrs.tmsCheckboxGroupUpper && attrs.tmsCheckboxGroupUpper.length) {
                        upper = attrs.tmsCheckboxGroupUpper;
                        options = document.querySelectorAll('[name=' + groupName + ']');
                        scope.$watch(model + '.' + groupName, function(data) {
                            var cnt;
                            cnt = 0;
                            angular.forEach(data, function(v, p) {
                                v && cnt++;
                            });
                            if (cnt >= upper) {
                                [].forEach.call(options, function(el) {
                                    if (el.checked === undefined) {
                                        !el.classList.contains('checked') && el.setAttribute('disabled', true);
                                    } else {
                                        !el.checked && (el.disabled = true);
                                    }
                                });
                            } else {
                                [].forEach.call(options, function(el) {
                                    if (el.checked === undefined) {
                                        el.removeAttribute('disabled');
                                    } else {
                                        el.disabled = false;
                                    }
                                });
                            }
                        }, true);
                    }
                }
            }
        }
    };
});
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

/***/ 16:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "html,body{font-family:Microsoft Yahei,Arial;width:100%;height:auto;}\r\nbody{position:relative;font-size:16px;padding:0;}\r\n.ng-cloak{display:none;}\r\n.container{position:relative;}\r\n.navbar-default .navbar-nav > li > a,.navbar-default .navbar-brand{color:#fff;}\r\n.navbar-brand{padding:17.5px 15px;}\r\n.main-navbar .navbar-brand:hover{color:#fff;}\r\n@media screen and (min-width:768px){\r\n\t.navbar-nav>li>a{padding:17.5px 30px;font-size:18px;line-height:1;}\r\n}\r\n@media screen and (max-width:768px){\r\n\t.navbar-brand{width:100%;text-align:center;}\r\n\t.navbar-brand > .icon-note{display:inline-block;width:124px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}\r\n\t.navbar-nav{margin:8px 0;position:absolute;top:0;right:0;}\r\n\t.nav > li > a{padding:10px 10px;}\r\n}\r\n.tms-flex-row{display:flex;align-items:center;}\r\n.tms-flex-row .tms-flex-grow{flex:1;}\r\n/*picviewer*/\r\n#picViewer {position: absolute;left: 0px;right: 0px;top: 0px;bottom: 0px;z-index: 1051;display: none;margin: 0 auto;overflow: hidden;background: black}\r\n#picViewer>div {position: absolute;bottom: 6px;left: 8px;right: 8px;z-index: 1999;height: 36px}\r\n#picViewer span {display: inline-block;width: 36px;height: 36px;line-height: 25px;border-radius: 18px;font-size: 25px;text-align: center}\r\n#picViewer span.page {position: absolute;left: 50%;margin-left: -18px;font-size: 18px}\r\n#picViewer span.prev {position: absolute;left: 50%;margin-left: -72px}\r\n#picViewer span.next {position: absolute;left: 50%;margin-left: 36px}\r\n#picViewer span.exit {position: absolute;right: 0}\r\n#picViewer span.exit > i {text-shadow: 0 0 0.1em #fff, -0 -0 0.1em #fff;}", ""]);

// exports


/***/ }),

/***/ 17:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(16);
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

/***/ 18:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('url.ui.xxt', ['http.ui.xxt']);
ngMod.service('tmsUrl', ['$q', '$uibModal', function($q, $uibModal) {
    function validateUrl(url) {
        return true;
    }
    var HtmlTemplate;
    HtmlTemplate = '<div class="modal-header">';
    HtmlTemplate += '<h5 class="modal-title">上传链接</h5>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="modal-body">';
    HtmlTemplate += '<form>';
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<div class="input-group">';
    HtmlTemplate += '<input type="text" ng-paste="crawlUrl($event)" class="form-control" placeholder="1、请将链接粘贴到这里或输入" ng-model="data.url">';
    HtmlTemplate += '<div class="input-group-btn"><button class="btn btn-default" ng-click="crawlUrl()">刷新</button></div>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<input type="text" class="form-control" placeholder="2、复制链接后这里将显示页面的标题，可进行修改" ng-model="data.summary.title">';
    HtmlTemplate += '</div>'
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<textarea class="form-control" placeholder="3、复制链接后这里将显示页面的摘要描述（如果提供），可进行修改" ng-model="data.summary.description" rows="4"></textarea>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="form-group">';
    HtmlTemplate += '<div class="form-control" ng-bind-html="data.text" style="height:auto;min-height:34px;"></div>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '</form>';
    HtmlTemplate += '</div>';
    HtmlTemplate += '<div class="modal-footer">';
    HtmlTemplate += '<button class="btn btn-default" ng-click="cancel()">关闭</button>';
    HtmlTemplate += '<button class="btn btn-default" ng-click="ok()">完成</button>';
    HtmlTemplate += '</div>';
    this.fetch = function(oBeforeUrlData) {
        var defer;
        defer = $q.defer();
        $uibModal.open({
            template: HtmlTemplate,
            controller: ['$scope', '$uibModalInstance', 'http2', function($scope, $mi, http2) {
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
                        console.log('ttt', text);
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

/***/ 19:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(7);
if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage && window.wx) {
    window.wx.ready(function() {
        window.wx.showOptionMenu();
    });
} else if (/YiXin/i.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        YixinJSBridge.call('showOptionMenu');
    }, false);
}

__webpack_require__(12);
__webpack_require__(17);
__webpack_require__(8);
__webpack_require__(6);
__webpack_require__(2);
__webpack_require__(15);
__webpack_require__(9);
__webpack_require__(14);
__webpack_require__(18);

__webpack_require__(13);

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'notice.ui.xxt', 'http.ui.xxt', 'page.ui.xxt', 'snsshare.ui.xxt', 'siteuser.ui.xxt', 'favor.ui.xxt', 'url.ui.xxt', 'directive.enroll']);
ngApp.config(['$controllerProvider', '$uibTooltipProvider', '$locationProvider', function($cp, $uibTooltipProvider, $locationProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({ 'show': 'hide' });
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlMain', ['$scope', '$q', 'http2', '$timeout', 'tmsLocation', 'tmsDynaPage', 'tmsSnsShare', 'tmsSiteUser', 'tmsFavor', function($scope, $q, http2, $timeout, LS, tmsDynaPage, tmsSnsShare, tmsSiteUser, tmsFavor) {
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
        if ($scope.app.entry_rule && $scope.app.entryRule.scope.member === 'Y') {
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
    /* 设置公众号分享信息 */
    $scope.setSnsShare = function(oRecord, oParams) {
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
            sharelink += "&shareby=" + shareid;
            /* 设置分享 */
            summary = oApp.summary;
            if (oPage && oPage.share_summary && oPage.share_summary.length && oRecord) {
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
                    url += "&title=" + oApp.title;
                    url += "&shareby=" + shareid;
                    url += "&shareto=" + shareto;
                    http2.get(url);
                    window.shareCounter++;
                    window.onshare && window.onshare(window.shareCounter);
                },
                jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage', 'getLocation']
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
    $scope.isSmallLayout = false;
    if (window.screen && window.screen.width < 992) {
        $scope.isSmallLayout = true;
    }
    http2.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).then(function success(rsp) {
        var params = rsp.data,
            oSite = params.site,
            oApp = params.app,
            oMission = params.mission,
            oPage = params.page,
            oUser = params.user,
            schemasById = {},
            tagsById = {},
            activeRid = '';

        oApp.dataSchemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        oApp._schemasById = schemasById;
        oApp.dataTags.forEach(function(oTag) {
            tagsById[oTag.id] = oTag;
        });
        oApp._tagsById = tagsById;
        $scope.params = params;
        $scope.site = oSite;
        $scope.mission = oMission;
        $scope.app = oApp;
        $scope.user = oUser;
        if (oApp.multi_rounds === 'Y' && params.activeRound) {
            $scope.activeRound = params.activeRound;
            activeRid = params.activeRound.rid;
        }
        // if (params.record) {
        //     if (params.record.data_tag) {
        //         for (var schemaId in params.record.data_tag) {
        //             var dataTags = params.record.data_tag[schemaId],
        //                 converted = [];
        //             dataTags.forEach(function(tagId) {
        //                 tagsById[tagId] && converted.push(tagsById[tagId]);
        //             });
        //             params.record.data_tag[schemaId] = converted;
        //         }
        //     }
        // }
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

        $scope.favor = function(user, article) {
            if (!user.loginExpire) {
                tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                    user.loginExpire = data.loginExpire;
                    tmsFavor.open(article);
                });
            } else {
                tmsFavor.open(article);
            }
        };
        if (oApp.can_siteuser === 'Y') {
            $scope.siteUser = function(id) {
                var url = location.protocol + '//' + location.host;
                url += '/rest/site/fe/user';
                url += "?site=" + id;
                location.href = url;
            };
        }
        if (tasksOfOnReady.length) {
            angular.forEach(tasksOfOnReady, execTask);
        }
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
        $timeout(function() {
            $scope.$broadcast('xxt.app.enroll.ready', params);
        });
        http2.post('/rest/site/fe/matter/logAccess?site=' + oApp.siteid + '&id=' + oApp.id + '&type=enroll&title=' + oApp.title, {
            search: location.search.replace('?', ''),
            referer: document.referrer,
            rid: activeRid,
            assignedNickname: oUser.nickname
        });
    });
}]);
module.exports = ngApp;

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

/***/ 20:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var utilSchema = {};
utilSchema.isEmpty = function(oSchema, value) {
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
utilSchema.checkRequire = function(oSchema, value) {
    if (value === undefined || this.isEmpty(oSchema, value)) {
        return '请填写必填题目［' + oSchema.title + '］';
    }
    return true;
};
utilSchema.checkFormat = function(oSchema, value) {
    if (oSchema.format === 'number') {
        if (!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
            return '题目［' + oSchema.title + '］请输入数值';
        }
    } else if (oSchema.format === 'name') {
        if(/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
            return '题目［' + oSchema.title + '］请输入字母或者汉字，并不少于2个字符';
        }
        if (value.length < 2) {
            return '题目［' + oSchema.title + '］请输入正确的姓名（不少于2个字符）';
        }
    } else if (oSchema.format === 'mobile') {
        if (!/^1(3[0-9]|4[57]|5[0-35-9]|7[0135678]|8[0-9])\d{8}$/.test(value)) {
            return '题目［' + oSchema.title + '］请输入正确的手机号（11位数字）';
        }
    } else if (oSchema.format === 'email') {
        if (!/^[A-Za-z\d]+([-_.][A-Za-z\d]+)*@([A-Za-z\d]+[-.])+[A-Za-z\d]{2,4}$/.test(value)) {
            return '题目［' + oSchema.title + '］请输入正确的邮箱';
        }
    }
    return true;
};
utilSchema.checkCount = function(oSchema, value) {
    if (oSchema.count !== undefined && value.length > oSchema.count) {
        return '［' + oSchema.title + '］超出上传数量（' + oSchema.count + '）限制';
    }
    return true;
};
utilSchema.checkValue = function(oSchema, value) {
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
utilSchema.loadRecord = function(schemasById, dataOfPage, dataOfRecord) {
    if (!dataOfRecord) return false;
    var schemaId, oSchema, value;
    for (schemaId in dataOfRecord) {
        if (schemaId === 'member') {
            dataOfPage.member = angular.extend(dataOfPage.member, dataOfRecord.member);
        } else if (oSchema = schemasById[schemaId]) {
            if (/score|url/.test(oSchema.type)) {
                dataOfPage[schemaId] = dataOfRecord[schemaId];
                if ('url' === oSchema.type) {
                    dataOfPage[schemaId]._text = utilSchema.urlSubstitute(dataOfPage[schemaId]);
                }
            } else if (dataOfRecord[schemaId].length) {
                if (oSchema.type === 'image') {
                    value = dataOfRecord[schemaId].split(',');
                    dataOfPage[schemaId] = [];
                    for (var i in value) {
                        dataOfPage[schemaId].push({
                            imgSrc: value[i]
                        });
                    }
                } else if (oSchema.type === 'multiple') {
                    value = dataOfRecord[schemaId].split(',');
                    dataOfPage[schemaId] = {};
                    for (var i in value) dataOfPage[schemaId][value[i]] = true;
                } else {
                    dataOfPage[schemaId] = dataOfRecord[schemaId];
                }
            }
        }
    }
    return true;
};
/**
 * 给页面中的提交数据填充题目默认值
 */
utilSchema.autoFillDefault = function(schemasById, oPageData) {
    angular.forEach(schemasById, function(oSchema) {
        if (oSchema.defaultValue && oPageData[oSchema.id] === undefined) {
            oPageData[oSchema.id] = oSchema.defaultValue;
        }
    });
};
/**
 * 给页面中的提交数据填充用户通讯录数据
 */
utilSchema.autoFillMember = function(schemasById, oUser, oPageDataMember) {
    if (oUser.members) {
        angular.forEach(schemasById, function(oSchema) {
            if (oSchema.schema_id && oUser.members[oSchema.schema_id]) {
                var oMember, attr, val;
                oMember = oUser.members[oSchema.schema_id];
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
utilSchema.txtSubstitute = function(oTxtData) {
    return oTxtData.replace(/\n/g, '<br>');
};
utilSchema.urlSubstitute = function(oUrlData) {
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
module.exports = utilSchema;

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

/***/ 30:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "body{margin-bottom:64px}blockquote{font-size:16px;margin-bottom:8px}img{max-width:100%}.dropdown-menu{min-width:auto}.navbar-header .page-title{display:inline-block}.navbar-header .page-title .notice-count{display:inline-block;background:red;color:#fff;line-height:14px;min-width:14px;border-radius:7px;font-size:8px;vertical-align:middle;margin-left:4px}.navbar-header .page-title .caret{margin-left:4px}.tms-nav-target .btn .notice-count{background:red;color:#fff;line-height:14px;min-width:14px;border-radius:7px;font-size:8px;vertical-align:middle;margin-left:4px}#advCriteria .panel-group{margin-bottom:8px}#advCriteria .form-control{float:none}#advCriteria .form-group{display:block}#advCriteria .form-group:last-child{margin-bottom:0}#advCriteria .dir-level-2{margin-left:1em}#advCriteria .dir-level-3{margin-left:1em}#advCriteria .dir.active{background-color:#ddd}#filterQuick{margin:4px 8px}#filterQuick .checkbox{display:inline-block}#repos .record{background:#fff;border-bottom:1px solid #ddd;padding:8px 16px}#repos .record>*{margin:8px 0}#repos .record .header{display:flex}#repos .record .header .seq{margin-right:8px}#repos .record .header .label{padding-top:.3em}#repos .record .header .nickname{flex-grow:1;text-align:left;font-size:.9em}#repos .record .data blockquote>div+div{margin-top:8px}#repos .record .data blockquote .schema>div+div{margin-top:8px}#repos .record .data .data-title:before{content:' ';display:inline-block;width:12px;height:12px;border-top:6px solid #fff;border-right:6px solid #fff;border-bottom:6px solid #fff;border-left:6px solid #ff8018}#repos .record .data .datetime{font-size:.8em}#repos .record .bottom-bar{display:flex;font-size:.9em;color:#777}#repos .record .bottom-bar>*+*{margin-left:16px}#repos .record .bottom-bar>*+* .like{color:#ff8018}#repos .record .bottom-bar .datetime{flex:1}#repos .record .bottom-bar a{text-decoration:none;color:#777}#repos .record .remarks{font-size:.9em;border-top:1px dashed #ddd;margin-top:16px;padding:1rem 0 0 2rem;position:relative}#repos .record .remarks:before{content:'\\63A8\\8350\\7559\\8A00';position:absolute;left:50%;margin-left:-2em;top:-.7em;color:#999}#repos .record .remarks .remark .top-bar{display:flex}#repos .record .remarks .remark .top-bar>:first-child{flex:1}#repos .record .remarks .remark+.remark{margin-bottom:1em}#repos .tag{background:#3af;padding:4px 6px;margin:4px;border-radius:2px;font-size:.8em;color:#fff}@media screen and (max-width:768px){.site-navbar-default{margin-bottom:0}.app .records.col-xs-12{padding:0}#filterCriteria{margin-left:-1px;margin-right:-1px}#filterCriteria .form-control,#filterCriteria .input-group-btn .btn{border-radius:0}#advCriteria{position:absolute;top:0;right:0;margin-top:1px;width:300px;background:#fff;padding:0 0 8px;border:1px solid #ccc;border-top:0;z-index:1000}#advCriteria .panel-group .panel{margin:0 -1px;border-radius:0}}", ""]);

// exports


/***/ }),

/***/ 31:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(30);
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
		module.hot.accept("!!../../../../../../node_modules/css-loader/index.js!./repos.css", function() {
			var newContent = require("!!../../../../../../node_modules/css-loader/index.js!./repos.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

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

/***/ 43:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(31);

var ngApp = __webpack_require__(19);
ngApp.oUtilSchema = __webpack_require__(20);
ngApp.factory('Round', ['http2', '$q', function(http2, $q) {
    var Round, _ins;
    Round = function(oApp) {
        this.oApp = oApp;
        this.oPage = {
            at: 1,
            size: 10,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
    };
    Round.prototype.list = function() {
        var _this = this,
            deferred = $q.defer(),
            url;

        url = '/rest/site/fe/matter/enroll/round/list?site=' + this.oApp.siteid + '&app=' + this.oApp.id;
        url += this.oPage.j();
        http2.get(url).then(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.oPage.total = rsp.data.total;
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    return {
        ins: function(oApp) {
            _ins = _ins ? _ins : new Round(oApp);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRepos', ['$scope', '$sce', 'http2', 'tmsLocation', 'Round', '$timeout', 'tmsDynaPage', function($scope, $sce, http2, LS, srvRound, $timeout, tmsDynaPage) {
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
    var _oApp, _facRound, _oPage, _oCriteria, _oShareableSchemas, _coworkRequireLikeNum;
    _coworkRequireLikeNum = 0; // 记录获得多少个赞，才能开启协作填写
    $scope.page = _oPage = { at: 1, size: 12 };
    $scope.criteria = _oCriteria = { creator: false, agreed: 'all', orderby: 'agreed' };
    $scope.schemas = _oShareableSchemas = {}; // 支持分享的题目
    $scope.repos = []; // 分享的记录
    $scope.recordList = function(pageAt) {
        var url;
        _oPage.total = 0;
        if (pageAt) {
            _oPage.at = pageAt;
        } else {
            _oPage.at++;
        }
        if (_oPage.at == 1) {
            $scope.repos = [];
        }
        url = LS.j('repos/recordList', 'site', 'app');
        url += '&page=' + _oPage.at + '&size=' + _oPage.size;
        http2.post(url, _oCriteria).then(function(result) {
            _oPage.total = result.data.total;
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    var oSchema, schemaData;
                    for (var schemaId in _oShareableSchemas) {
                        oSchema = _oShareableSchemas[schemaId];
                        if (schemaData = oRecord.data[oSchema.id]) {
                            switch (oSchema.type) {
                                case 'longtext':
                                    oRecord.data[oSchema.id] = ngApp.oUtilSchema.txtSubstitute(schemaData);
                                    break;
                                case 'url':
                                    schemaData._text = ngApp.oUtilSchema.urlSubstitute(schemaData);
                                    break;
                            }
                        }
                    }
                    if (_coworkRequireLikeNum > oRecord.like_num) {
                        oRecord._coworkRequireLikeNum = (_coworkRequireLikeNum > oRecord.like_num ? _coworkRequireLikeNum - oRecord.like_num : 0);
                    }
                    oRecord._canAgree = fnCanAgreeRecord(oRecord, $scope.user);
                    $scope.repos.push(oRecord);
                });
            }
            tmsDynaPage.loadScript(['/static/js/hammer.min.js', '/asset/js/xxt.ui.picviewer.js']);
        });
    }
    $scope.likeRecord = function(oRecord) {
        var url;
        url = LS.j('record/like', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.remarkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork#remarks';
        location.href = url;
    };
    $scope.coworkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork';
        url += '#cowork';
        location.href = url;
    };
    $scope.setAgreed = function(oRecord, value) {
        var url;
        if (oRecord.agreed !== value) {
            url = LS.j('record/agree', 'site');
            url += '&ek=' + oRecord.enroll_key;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.value2Label = function(oSchema, value) {
        var val, aVal, aLab = [];
        if (val = value) {
            if (oSchema.ops && oSchema.ops.length) {
                aVal = val.split(',');
                oSchema.ops.forEach(function(op) {
                    aVal.indexOf(op.v) !== -1 && aLab.push(op.l);
                });
                val = aLab.join(',');
            }
        } else {
            val = '';
        }
        return $sce.trustAsHtml(val);
    };
    $scope.shiftRound = function() {
        $scope.recordList(1);
    };
    $scope.shiftUserGroup = function() {
        $scope.recordList(1);
    };
    $scope.shiftOwner = function() {
        $scope.recordList(1);
    };
    $scope.shiftAgreed = function(agreed) {
        _oCriteria.agreed = agreed;
        $scope.recordList(1);
    };
    $scope.shiftOrderby = function(orderby) {
        _oCriteria.orderby = orderby;
        $scope.recordList(1);
    };
    $scope.shiftDir = function(oDir) {
        _oCriteria.data = {};
        if (oDir) {
            _oCriteria.data[oDir.schema_id] = oDir.op.v;
        }
        $scope.activeDir = oDir;
        $scope.recordList(1);
    };
    /* 关闭任务提示 */
    $scope.closeTask = function(index) {
        $scope.tasks.splice(index, 1);
    };
    $scope.advCriteriaStatus = {
        opened: !$scope.isSmallLayout,
        dirOpen: false,
        filterOpen: true
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        /* 活动任务 */
        if (_oApp.actionRule) {
            /* 设置活动任务提示 */
            var tasks = [];
            http2.get(LS.j('event/task', 'site', 'app')).then(function(rsp) {
                if (rsp.data && rsp.data.length) {
                    rsp.data.forEach(function(oRule) {
                        if (!oRule._ok) {
                            tasks.push({ type: 'info', msg: oRule.desc, id: oRule.id, gap: oRule._no ? oRule._no[0] : 0, coin: oRule.coin ? oRule.coin : 0 });
                        }
                    });
                }
            });
            $scope.tasks = tasks;
            /* 开启协作填写需要的点赞数 */
            if (_oApp.actionRule.record && _oApp.actionRule.record.cowork && _oApp.actionRule.record.cowork.pre) {
                if (_oApp.actionRule.record.cowork.pre.record && _oApp.actionRule.record.cowork.pre.record.likeNum !== undefined) {
                    _coworkRequireLikeNum = parseInt(_oApp.actionRule.record.cowork.pre.record.likeNum);
                }
            }
        }
        _oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                _oShareableSchemas[schema.id] = schema;
            }
        });
        $scope.userGroups = params.groups;
        $scope.groupUser = params.groupUser;
        var groupOthersById = {};
        if (params.groupOthers && params.groupOthers.length) {
            params.groupOthers.forEach(function(oOther) {
                groupOthersById[oOther.userid] = oOther;
            });
        }
        $scope.groupOthers = groupOthersById;
        $scope.recordList(1);
        $scope.facRound = _facRound = srvRound.ins(_oApp);
        if (_oApp.multi_rounds === 'Y') {
            _facRound.list().then(function(result) {
                if (result.active) {
                    for (var i = 0, ii = result.rounds.length; i < ii; i++) {
                        if (result.rounds[i].rid === result.active.rid) {
                            _oCriteria.rid = result.active.rid;
                            break;
                        }
                    }
                }
                $scope.rounds = result.rounds;
            });
        }
        http2.get(LS.j('repos/dirSchemasGet', 'site', 'app')).then(function(rsp) {
            $scope.dirSchemas = rsp.data;
            if ($scope.dirSchemas && $scope.dirSchemas.length) {
                $scope.advCriteriaStatus.dirOpen = true;
            }
        });
        /*设置页面分享信息*/
        $scope.setSnsShare();
        /*设置页面操作*/
        $scope.appActs = {
            addRecord: {}
        };
        /*设置页面导航*/
        var oAppNavs = {};
        if (_oApp.can_rank === 'Y') {
            oAppNavs.rank = {};
        }
        if (_oApp.scenarioConfig && _oApp.scenarioConfig.can_action === 'Y') {
            /* 设置活动事件提醒 */
            http2.get(LS.j('notice/count', 'site', 'app')).then(function(rsp) {
                $scope.noticeCount = rsp.data;
            });
            oAppNavs.action = {};
        }
        if (Object.keys(oAppNavs).length) {
            $scope.appNavs = oAppNavs;
        }
    });
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

/***/ 6:
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
    function createAlert(msg, type, keep) {
        var alertDomEl;
        /* backdrop */
        alertDomEl = angular.element('<div></div>');
        alertDomEl.attr({
            'class': 'tms-notice alert alert-' + (type ? type : 'info'),
            'ng-style': '{\'z-index\':1099}'
        }).html($sce.trustAsHtml(msg));
        if (!keep) {
            alertDomEl[0].addEventListener('click', function() {
                document.body.removeChild(alertDomEl[0]);
            }, true);
        }
        $compile(alertDomEl)($rootScope);
        document.body.appendChild(alertDomEl[0]);

        return alertDomEl[0];
    }

    function removeAlert(alertDomEl) {
        if (alertDomEl) {
            document.body.removeChild(alertDomEl);
        }
    }

    this.get = function(url, options) {
        var _alert, _timer, _defer = $q.defer();
        options = angular.extend({
            'headers': {
                'accept': 'application/json'
            },
            'autoBreak': true,
            'autoNotice': true,
            'showProgress': true,
            'showProgressDelay': 500,
            'showProgressText': '正在获取数据...',
        }, options);
        if (options.showProgress === true) {
            _timer = $timeout(function() {
                _timer = null;
                _alert = createAlert(options.showProgressText, 'info');
            }, options.showProgressDelay);
        }
        $http.get(url, options).success(function(rsp) {
            if (options.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    removeAlert(_alert);
                    _alert = null;
                }
            }
            if (angular.isString(rsp)) {
                if (options.autoNotice) {
                    createAlert(rsp, 'warning');
                }
                if (options.autoBreak) {
                    return
                } else {
                    _defer.reject(rsp);
                }
            } else if (rsp.err_code != 0) {
                if (options.autoNotice) {
                    var errmsg;
                    if (angular.isString(rsp.err_msg)) {
                        errmsg = rsp.err_msg;
                    } else if (angular.isArray(rsp.err_msg)) {
                        errmsg = rsp.err_msg.join('<br>');
                    } else {
                        errmsg = JSON.stringify(rsp.err_msg);
                    }
                    createAlert(errmsg, 'warning');
                }
                if (options.autoBreak) {
                    return
                } else {
                    _defer.reject(rsp);
                }
            } else {
                _defer.resolve(rsp);
            }
        }).error(function(data, status) {
            if (options.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    removeAlert(_alert);
                    _alert = null;
                }
            }
            createAlert(data === null ? '网络不可用' : data, 'danger');
        });

        return _defer.promise;
    };
    this.post = function(url, posted, options) {
        var _alert, _timer, _defer = $q.defer();
        options = angular.extend({
            'headers': {
                'accept': 'application/json'
            },
            'autoBreak': true,
            'autoNotice': true,
            'showProgress': true,
            'showProgressDelay': 500,
            'showProgressText': '正在获取数据...',
        }, options);
        if (options.showProgress === true) {
            _timer = $timeout(function() {
                _timer = null;
                _alert = createAlert(options.showProgressText, 'info');
            }, options.showProgressDelay);
        }
        $http.post(url, posted, options).success(function(rsp) {
            if (options.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    removeAlert(_alert);
                    _alert = null;
                }
            }
            if (angular.isString(rsp)) {
                if (options.autoNotice) {
                    createAlert(rsp, 'warning');
                    _alert = null;
                }
                if (options.autoBreak) {
                    return
                } else {
                    _defer.reject(rsp);
                }
            } else if (rsp.err_code != 0) {
                if (options.autoNotice) {
                    var errmsg;
                    if (angular.isString(rsp.err_msg)) {
                        errmsg = rsp.err_msg;
                    } else if (angular.isArray(rsp.err_msg)) {
                        errmsg = rsp.err_msg.join('<br>');
                    } else {
                        errmsg = JSON.stringify(rsp.err_msg);
                    }
                    createAlert(errmsg, 'warning');
                }
                if (options.autoBreak) {
                    return
                } else {
                    _defer.reject(rsp);
                }
            } else {
                _defer.resolve(rsp);
            }
        }).error(function(data, status) {
            if (options.showProgress === true) {
                _timer && $timeout.cancel(_timer);
                if (_alert) {
                    removeAlert(_alert);
                    _alert = null;
                }
            }
            createAlert(data === null ? '网络不可用' : data, 'danger');
        });

        return _defer.promise;
    };
}]);

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

/***/ 8:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('notice.ui.xxt', ['ngSanitize']);
ngMod.service('noticebox', ['$timeout', '$q', function($timeout, $q) {
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
                box.classList.add('notice-box');
                box.classList.add('alert');
                box.classList.add('alert-' + type);
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
    this.confirm = function(msg) {
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

        return defer.promise;
    };
}]);

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


/***/ }),

/***/ 98:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(43);


/***/ })

/******/ });