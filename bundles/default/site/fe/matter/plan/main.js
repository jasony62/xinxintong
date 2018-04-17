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
/******/ 	return __webpack_require__(__webpack_require__.s = 104);
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

/***/ 104:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(49);


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

/***/ 21:
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

/***/ 22:
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

/***/ 27:
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

/***/ 49:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(82);
__webpack_require__(8);
__webpack_require__(6);
__webpack_require__(56);
__webpack_require__(7);
__webpack_require__(21);
__webpack_require__(22);

__webpack_require__(12);
__webpack_require__(13);

var ngApp = angular.module('app', ['ngSanitize', 'ngRoute', 'ui.bootstrap', 'directive.enroll', 'notice.ui.xxt', 'http.ui.xxt', 'date.ui.xxt', 'snsshare.ui.xxt']);
ngApp.oUtilSchema = __webpack_require__(20);
ngApp.oUtilSubmit = __webpack_require__(27);
ngApp.factory('Input', ['$q', '$timeout', 'http2', 'tmsLocation', function($q, $timeout, http2, LS) {
    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(oTask, oTaskData) {
        var oAction, oActionData, schemas, oSchema, value, sCheckResult;
        if (oTask.actions && oTask.actions.length) {
            for (var i = 0, ii = oTask.actions.length; i < ii; i++) {
                oAction = oTask.actions[i];
                if (oAction.checkSchemas && oAction.checkSchemas.length) {
                    schemas = oAction.checkSchemas;
                    oActionData = oTaskData[oAction.id];
                    for (var j = 0, jj = schemas.length; j < jj; j++) {
                        oSchema = schemas[j];
                        if (oSchema.type && oSchema.type !== 'html') {
                            value = oActionData ? oActionData[oSchema.id] : '';
                            if (true !== (sCheckResult = ngApp.oUtilSchema.checkValue(oSchema, value))) {
                                return sCheckResult;
                            }
                        }
                    }
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(oTask, oTaskData, oSupplement) {
        var posted, d, url;
        posted = {
            data: angular.copy(oTaskData),
            supplement: oSupplement
        };
        url = '/rest/site/fe/matter/plan/task/submit?site=' + LS.s().site + '&task=' + oTask.id;
        for (var i in posted.data) {
            d = posted.data[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                d.forEach(function(d2) {
                    delete d2.imgSrc;
                });
            }
        }
        return http2.post(url, posted, { autoNotice: false, autoBreak: false });
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
/**
 *上传图片
 */
ngApp.directive('tmsImageInput', ['$compile', '$q', 'noticebox', function($compile, $q, noticebox) {
    var aModifiedImgFields;
    aModifiedImgFields = [];
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', function($scope, $timeout) {
            $scope.chooseImage = function(oAction, oSchema, from) {
                var imgFieldName, aSchemaImgs, count;
                imgFieldName = oAction.id + '.' + oSchema.id;
                aModifiedImgFields.indexOf(imgFieldName) === -1 && aModifiedImgFields.push(imgFieldName);
                $scope.data[oAction.id] === undefined && ($scope.data[oAction.id] = {});
                $scope.data[oAction.id][oSchema.id] === undefined && ($scope.data[oAction.id][oSchema.id] = []);
                aSchemaImgs = $scope.data[oAction.id][oSchema.id];
                count = parseInt(oSchema.count) || 1;
                if (aSchemaImgs.length === count) {
                    noticebox.warn('最多允许上传（' + count + '）张图片');
                    return;
                }
                window.xxt.image.choose($q.defer(), from).then(function(imgs) {
                    var phase;
                    phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.data[oAction.id][oSchema.id] = aSchemaImgs.concat(imgs);
                    } else {
                        $scope.$apply(function() {
                            $scope.data[oAction.id][oSchema.id] = aSchemaImgs.concat(imgs);
                        });
                    }
                    $timeout(function() {
                        var i, j, img, eleImg;
                        for (i = 0, j = imgs.length; i < j; i++) {
                            img = imgs[i];
                            eleImg = document.querySelector('ul[name="' + imgFieldName + '"] li:nth-last-child(2) img');
                            if (eleImg) {
                                eleImg.setAttribute('src', img.imgSrc);
                            }
                        }
                        $scope.$broadcast('xxt.plan.image.choose.done', imgFieldName);
                    });
                });
            };
            $scope.removeImage = function(oAction, oSchema, index) {
                $scope.data[oAction.id][oSchema.id].splice(index, 1);
            };
        }]
    }
}]);
/**
 * 上传文件
 */
ngApp.directive('tmsFileInput', ['$q', 'tmsLocation', function($q, LS) {
    function onSubmit($scope) {
        var defer;

        defer = $q.defer();
        if (!oResumable.files || oResumable.files.length === 0) {
            defer.resolve('empty');
        }
        oResumable.on('progress', function() {
            var phase, p;
            p = oResumable.progress();
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = Math.ceil(p * 100);
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = Math.ceil(p * 100);
                });
            }
        });
        oResumable.on('complete', function() {
            var phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                $scope.progressOfUploadFile = '完成';
            } else {
                $scope.$apply(function() {
                    $scope.progressOfUploadFile = '完成';
                });
            }
            oResumable.cancel();
            defer.resolve('ok');
        });
        oResumable.upload();

        return defer.promise;
    };
    var oResumable;
    oResumable = new Resumable({
        target: '/rest/site/fe/matter/plan/task/uploadFile?site=' + LS.s().site + '&app=' + LS.s().app,
        testChunks: false,
        chunkSize: 512 * 1024
    });
    return {
        restrict: 'A',
        controller: ['$scope', function($scope) {
            $scope.progressOfUploadFile = 0;
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            $scope.chooseFile = function(oAction, oSchema, accept) {
                var fileFieldName, ele;
                fileFieldName = oAction.id + '.' + oSchema.id;
                ele = document.createElement('input');
                ele.setAttribute('type', 'file');
                accept !== undefined && ele.setAttribute('accept', accept);
                ele.addEventListener('change', function(evt) {
                    var i, cnt, f;
                    $scope.data[oAction.id] === undefined && ($scope.data[oAction.id] = {});
                    $scope.data[oAction.id][oSchema.id] === undefined && ($scope.data[oAction.id][oSchema.id] = []);
                    cnt = evt.target.files.length;
                    for (i = 0; i < cnt; i++) {
                        f = evt.target.files[i];
                        oResumable.addFile(f);
                        $scope.data[oAction.id][oSchema.id].push({
                            uniqueIdentifier: oResumable.files[oResumable.files.length - 1].uniqueIdentifier,
                            name: f.name,
                            size: f.size,
                            type: f.type,
                            url: ''
                        });
                    }
                    $scope.$apply('data', function() {
                        $scope.$broadcast('xxt.plan.file.choose.done', fileFieldName);
                    });
                }, false);
                ele.click();
            };
        }]
    }
}]);
ngApp.config(['$compileProvider', '$routeProvider', '$locationProvider', 'tmsLocationProvider', function($compileProvider, $routeProvider, $locationProvider, tmsLocationProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
    var RouteParam = function(name) {
        this.templateUrl = name + '.html';
        this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
        this.reloadOnSearch = false;
    };
    $routeProvider
        .when('/rest/site/fe/matter/plan/task', new RouteParam('task'))
        .when('/rest/site/fe/matter/plan/rank', new RouteParam('rank'))
        .otherwise(new RouteParam('plan'));
    $locationProvider.html5Mode(true);
    tmsLocationProvider.config('/rest/site/fe/matter/plan');
}]);
/**
 * 计划任务活动
 */
ngApp.controller('ctrlMain', ['$scope', '$location', 'http2', 'tmsLocation', 'tmsSnsShare', function($scope, $location, http2, LS, tmsSnsShare) {
    var _oApp, _oUser;
    $scope.subView = '';
    $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)\?/);
        $scope.subView = subView[1] === 'plan' ? 'plan' : subView[1];
    });
    $scope.toggleView = function(view, obj) {
        var oSearch = angular.copy($location.search());
        delete oSearch.task;
        switch (view) {
            case 'rank':
                $location.path('/rest/site/fe/matter/plan/rank').search(oSearch);
                break;
            case 'task':
                oSearch.task = obj.id;
                $location.path('/rest/site/fe/matter/plan/task').search(oSearch);
                break;
            default:
                $location.path('/rest/site/fe/matter/plan').search(oSearch);
        }
    };
    $scope.siteUser = function() {
        var url;
        url = '/rest/site/fe/user';
        url += "?site=" + LS.s().site;
        location.href = url;
    };
    $scope.invite = function() {
        if (!_oUser.loginExpire) {
            tmsDynaPage.openPlugin(location.protocol + '//' + location.host + '/rest/site/fe/user/access?site=platform#login').then(function(data) {
                _oUser.loginExpire = data.loginExpire;
                location.href = "/rest/site/fe/invite?matter=plan," + _oApp.id;
            });
        } else {
            location.href = "/rest/site/fe/invite?matter=plan," + _oApp.id;
        }
    };
    http2.get(LS.j('get', 'site', 'app')).then(function(rsp) {
        $scope.app = _oApp = rsp.data.app;
        $scope.user = _oUser = rsp.data.user;

        _oApp._taskSchemasById = {};
        _oApp.tasks.forEach(function(oTaskSchema) {
            _oApp._taskSchemasById[oTaskSchema.id] = oTaskSchema;
        });

        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            tmsSnsShare.config({
                siteId: _oApp.siteid,
                logger: function(shareto) {},
                jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage', 'getLocation']
            });
            tmsSnsShare.set(_oApp.title, _oApp.entryUrl, _oApp.summary, _oApp.pic);
        }
        http2.post('/rest/site/fe/matter/logAccess?site=' + _oApp.siteid + '&id=' + _oApp.id + '&type=plan&title=' + _oApp.title, {});
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
    });
}]);
/**
 * 任务列表
 */
ngApp.controller('ctrlPlan', ['$scope', '$filter', 'http2', 'tmsLocation', function($scope, $filter, http2, LS) {
    function getUserPlan() {
        http2.get(LS.j('overview', 'site', 'app')).then(function(rsp) {
            $scope.overview = _oOverview = rsp.data;
            http2.get(LS.j('task/listByUser', 'site', 'app')).then(function(rsp) {
                var userTasks, mockTasks;
                userTasks = rsp.data.tasks;
                mockTasks = rsp.data.mocks;
                userTasks.forEach(function(oTask) {
                    oTask.bornAt = $filter('tmsDate')(oTask.born_at * 1000, 'yy-MM-dd HH:mm,EEE');
                    if (_oApp._taskSchemasById[oTask.task_schema_id]) {
                        _oApp._taskSchemasById[oTask.task_schema_id].userTask = oTask;
                    }
                });
                if (mockTasks) {
                    mockTasks.forEach(function(oMock) {
                        oMock.bornAt = $filter('tmsDate')(oMock.born_at * 1000, 'yy-MM-dd HH:mm,EEE');
                        if (_oApp._taskSchemasById[oMock.id]) {
                            _oApp._taskSchemasById[oMock.id].mockTask = oMock;
                        }
                    });
                }
                _oApp.tasks.forEach(function(oTaskSchema) {
                    if (oTaskSchema.as_placeholder === 'N' && !oTaskSchema.userTask && oTaskSchema.mockTask) {
                        if (_oOverview.nowTaskSchema && oTaskSchema.task_seq < _oOverview.nowTaskSchema.task_seq) {
                            oTaskSchema.isDelayed = 'Y';
                        } else if (_oOverview.lastUserTask && oTaskSchema.task_seq < _oOverview.lastUserTask.task_seq) {
                            oTaskSchema.isDelayed = 'Y';
                        }
                    }
                });
            });
        });
    }
    var _oApp, _oOverview;
    $scope.$on('xxt.tms-datepicker.change', function(event, data) {
        http2.post(LS.j('config', 'site', 'app'), { 'start_at': data.value }).then(function(rsp) {
            getUserPlan();
        });
    });
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        getUserPlan();
    });
}]);
/**
 * 单个任务
 */
ngApp.controller('ctrlTask', ['$scope', '$filter', 'noticebox', 'http2', 'Input', 'tmsLocation', function($scope, $filter, noticebox, http2, Input, LS) {
    function doSubmit() {
        facInput.submit($scope.activeTask, $scope.data, $scope.supplement).then(function(rsp) {
            _oSubmitState.finish();
            noticebox.success('完成提交');
            $scope.$emit('xxt.app.plan.submit.done', rsp.data);
        }, function(rsp) {
            _oSubmitState.finish();
            if (rsp && typeof rsp === 'string') {
                noticebox.error(rsp);
                return;
            }
            if (rsp && rsp.err_msg) {
                noticebox.error(rsp.err_msg);
                return;
            }
            noticebox.error('网络异常，提交失败');
        }, function(rsp) {
            _oSubmitState.finish();
        });
    }

    function doTask(seq) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq) : doSubmit();
        });
    }

    window.onbeforeunload = function() {
        // 保存未提交数据
        _oSubmitState.modified && _oSubmitState.cache($scope.data);
    };

    var facInput, _oSubmitState, tasksOfBeforeSubmit;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {};
    $scope._oSubmitState = _oSubmitState = ngApp.oUtilSubmit.state;
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    $scope.submit = function(event) {
        var sCheckResult;
        if (!_oSubmitState.isRunning()) {
            _oSubmitState.start(event);
            if (true === (sCheckResult = facInput.check($scope.activeTask, $scope.data))) {
                tasksOfBeforeSubmit.length ? doTask(0) : doSubmit();
            } else {
                _oSubmitState.finish();
                noticebox.error(sCheckResult);
            }
        }
    };
    http2.get(LS.j('task/get', 'site', 'task')).then(function(rsp) {
        var oTask;
        oTask = rsp.data;
        /* 任务数据 */
        if (oTask.actions) {
            oTask.actions.forEach(function(oAction) {
                if ($scope.app.checkSchemas.length) {
                    var pos = 0;
                    $scope.app.checkSchemas.forEach(function(oSchema) {
                        oAction.checkSchemas.splice(pos++, 0, oSchema);
                    });
                }
                if (oTask.userTask) {
                    var schemasById, oUserTask;
                    oUserTask = oTask.userTask;
                    /* 处理任务时间 */
                    oUserTask.bornAt = $filter('tmsDate')(oUserTask.born_at * 1000, 'yy-MM-dd HH:mm,EEE');
                    if (oUserTask.patch_at > 0) {
                        oUserTask.patchAt = $filter('tmsDate')(oUserTask.patch_at * 1000, 'yy-MM-dd HH:mm,EEE');
                    }
                    /* 处理任务数据 */
                    if (oUserTask.data[oAction.id]) {
                        schemasById = {};
                        oAction.checkSchemas.forEach(function(oSchema) {
                            schemasById[oSchema.id] = oSchema;
                        });
                        $scope.data[oAction.id] = {};
                        ngApp.oUtilSchema.loadRecord(schemasById, $scope.data[oAction.id], oUserTask.data[oAction.id]);
                    }
                }
            });
        }
        // 数据补充说明
        if (oTask.userTask && oTask.userTask.supplement) {
            $scope.supplement = oTask.userTask.supplement;
        } else {
            $scope.supplement = {};
        }
        $scope.activeTask = oTask;
        $scope.userTask = oTask.userTask;
    });
}]);
/**
 * 排行
 */
ngApp.controller('ctrlRank', ['$scope', 'http2', '$q', 'tmsLocation', function($scope, http2, $q, LS) {
    function list() {
        var defer = $q.defer();
        switch (oAppState.criteria.obj) {
            case 'user':
                http2.post(LS.j('rank/byUser', 'site', 'app'), oAppState.criteria).then(function(rsp) {
                    defer.resolve(rsp.data);
                });
                break;
        }
        return defer.promise;
    }
    $scope.doSearch = function() {
        list().then(function(data) {
            var oSchema;
            switch (oAppState.criteria.obj) {
                case 'user':
                    if (data) {
                        data.users.forEach(function(user) {
                            $scope.users.push(user);
                        });
                    }
                    break;
            }
            oAppState.page.total = data.total;
        });
    };
    $scope.changeCriteria = function() {
        $scope.users = [];
        $scope.groups = [];
        $scope.doSearch(1);
    };
    var _oApp, oAppState;
    $scope.rankView = {
        obj: 'user'
    };
    if (!oAppState) {
        oAppState = {
            criteria: {
                obj: 'user',
                orderby: 'task_num',
            },
            page: {
                at: 1,
                size: 12
            }
        };
    }
    $scope.appState = oAppState;
    $scope.$watch('appState.criteria.obj', function(oNew, oOld) {
        if (oNew && oOld && oNew !== oOld) {
            switch (oNew) {
                case 'user':
                    oAppState.criteria.orderby = 'task_num';
                    break;
            }
            $scope.changeCriteria();
        }
    });
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        $scope.changeCriteria();
    });
}]);

/***/ }),

/***/ 56:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('date.ui.xxt', []);
ngMod.filter('tmsDate', ['$filter', function($filter) {
    var i18n = {
        weekday: {
            'Mon': '星期一',
            'Tue': '星期二',
            'Wed': '星期三',
            'Thu': '星期四',
            'Fri': '星期五',
            'Sat': '星期六',
            'Sun': '星期日',
        }
    };

    return function(timestamp, format) {
        var str, weekday;

        if (!format) return timestamp;

        str = $filter('date')(timestamp, format);
        if (format.indexOf('EEE') !== -1) {
            weekday = $filter('date')(timestamp, 'EEE');
            str = str.replace(weekday, i18n.weekday[weekday]);
        }

        return str;
    }
}]);
ngMod.directive('tmsDatepicker', function() {
    var _version = 7;
    return {
        restrict: 'EA',
        scope: {
            date: '=tmsData',
            defaultDate: '@tmsDefaultData',
            mask: '@tmsMask', //y,m,d,h,i
            title: '@tmsTitle',
            state: '@tmsState',
            obj: '=tmsObj'
        },
        templateUrl: '/static/template/datepicker.html?_=' + _version,
        controller: ['$scope', '$uibModal', function($scope, $uibModal) {
            var mask, format = [];
            if ($scope.mask === undefined) {
                mask = {
                    y: true,
                    m: true,
                    d: true,
                    h: true,
                    i: true
                };
                $scope.format = 'yy-MM-dd HH:mm';
            } else {
                mask = (function(mask1) {
                    var mask2, mask1 = mask1.split(',');
                    /*date*/
                    mask2 = {
                        y: mask1[0] === 'y' ? true : mask1[0],
                        m: mask1[1] === 'm' ? true : mask1[1],
                        d: mask1[2] === 'd' ? true : mask1[2],
                    };
                    $scope.format = 'yy-MM-dd';
                    /*time*/
                    if (mask1.length === 5) {
                        if (mask1[3] === 'h') {
                            mask2.h = true;
                            $scope.format += ' HH';
                            if (mask1[4] === 'i') {
                                mask2.i = true;
                                $scope.format += ':mm';
                            } else {
                                mask2.i = mask1[4];
                            }
                        } else {
                            mask2.h = mask1[3];
                            mask2.i = mask1[4] === 'i' ? true : mask1[4];
                        }
                    } else {
                        mask2.h = 0;
                        mask2.i = 0;
                    }
                    return mask2;
                })($scope.mask);
            }
            $scope.open = function() {
                $uibModal.open({
                    templateUrl: 'tmsModalDatepicker.html',
                    resolve: {
                        date: function() {
                            return $scope.date;
                        },
                        defaultDate: function() {
                            return $scope.defaultDate;
                        },
                        mask: function() {
                            return mask;
                        }
                    },
                    controller: ['$scope', '$filter', '$uibModalInstance', 'date', 'defaultDate', 'mask', function($scope, $filter, $mi, date, defaultDate, mask) {
                        date = (function() {
                            var d = new Date();
                            if (defaultDate) {
                                d.setTime(defaultDate ? defaultDate * 1000 : d.getTime());
                            } else {
                                d.setTime(date ? date * 1000 : d.getTime());
                            }
                            d.setMilliseconds(0);
                            d.setSeconds(0);
                            if (mask.i !== true) {
                                d.setMinutes(mask.i);
                            }
                            if (mask.h !== true) {
                                d.setHours(mask.h);
                            }
                            return d;
                        })();
                        $scope.mask = mask;
                        $scope.years = [2015, 2016, 2017, 2018, 2019, 2020];
                        $scope.months = [];
                        $scope.days = [];
                        $scope.hours = [];
                        $scope.minutes = [];
                        $scope.date = {
                            year: date.getFullYear(),
                            month: date.getMonth() + 1,
                            mday: date.getDate(),
                            hour: date.getHours(),
                            minute: date.getMinutes()
                        };
                        for (var i = 1; i <= 12; i++)
                            $scope.months.push(i);
                        for (var i = 1; i <= 31; i++)
                            $scope.days.push(i);
                        for (var i = 0; i <= 23; i++)
                            $scope.hours.push(i);
                        for (var i = 0; i <= 59; i++)
                            $scope.minutes.push(i);
                        $scope.today = function() {
                            var d = new Date();
                            $scope.date = {
                                year: d.getFullYear(),
                                month: d.getMonth() + 1,
                                mday: d.getDate(),
                                hour: d.getHours(),
                                minute: d.getMinutes()
                            };
                        };
                        $scope.reset = function(field) {
                            $scope.date[field] = 0;
                        };
                        $scope.next = function(field, options) {
                            var max = options[options.length - 1];

                            if ($scope.date[field] < max) {
                                $scope.date[field]++;
                            }
                        };
                        $scope.prev = function(field, options) {
                            var min = options[0];

                            if ($scope.date[field] > min) {
                                $scope.date[field]--;
                            }
                        };
                        $scope.ok = function() {
                            $mi.close($scope.date);
                        };
                        $scope.empty = function() {
                            $mi.close(null);
                        };
                        $scope.cancel = function() {
                            $mi.dismiss('cancel');
                        };
                    }],
                    backdrop: 'static',
                    size: 'sm'
                }).result.then(function(result) {
                    var d;
                    d = result === null ? 0 : Date.parse(result.year + '/' + result.month + '/' + result.mday + ' ' + result.hour + ':' + result.minute) / 1000;
                    $scope.date = d;
                    $scope.$emit('xxt.tms-datepicker.change', {
                        obj: $scope.obj,
                        state: $scope.state,
                        value: d
                    });
                });
            };
        }],
        replace: true
    };
});

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

/***/ 67:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".ng-cloak{display:none;}\r\nhtml,body{height:100%;width:100%;background:#efefef;font-family:Microsoft Yahei,Arial;}\r\nbody{position:relative;font-size:16px;}\r\nul{margin:0;padding:0;list-style:none}\r\nli{margin:0;padding:0}\r\n.panel-body .form-group:last-child{margin-bottom:0;}\r\n@media screen and (min-width:768px){\r\n\t.navbar-nav>li>a{padding:17.5px 30px;font-size:18px;line-height:1;}\r\n}\r\n@media screen and (max-width:768px){\r\n\t.navbar-brand{width:100%;text-align:center;}\r\n\t.navbar-brand>.icon-note{display:inline-block;width:124px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}\r\n\t.navbar-nav{margin:8px 0;position:absolute;top:0;right:0;}\r\n\t.nav>li>a{padding:10px 10px;}\r\n}\r\n#plan .matter-pic{width:100%;}\r\n#plan .matter-pic>div{width:100%;height:0;padding-bottom:56%;background-size:contain;background-repeat:no-repeat;background-position:center;}\r\n\r\n/* img tiles */\r\nul.img-tiles li{position:relative;display:inline-block;overflow:hidden;width:80px;height:80px;margin:0px;padding:0px;float:left}\r\nul.img-tiles li.img-thumbnail img{display:inline-block;position:absolute;max-width:none;}\r\nul.img-tiles li.img-thumbnail button{position:absolute;top:0;right:0}\r\nul.img-tiles li.img-picker button{position:auto;width:100%;height:100%}\r\nul.img-tiles li.img-picker button span{font-size:36px}\r\n\r\n/* default form style*/\r\ndiv[wrap].wrap-splitline{padding-bottom:0.5em;border-bottom:1px solid #fff;}\r\ndiv[wrap].wrap-inline>*{display:inline-block;vertical-align:middle;margin:0 1em 0 0;}\r\ndiv[wrap].wrap-inline>label{width:6em;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}\r\ndiv[wrap=matter]>span{cursor:pointer;text-decoration:underline;}\r\n\r\n/* auth */\r\n#frmPopup{position:absolute;top:0;left:0;right:0;bottom:0;border:none;width:100%;z-index:999;box-sizing:border-box;}\r\n\r\n/* rank */\r\n.result{margin-top: 10px;}\r\n.result ul, .result li{overflow: hidden; margin-bottom: 10px;}", ""]);

// exports


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

/***/ 82:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(67);
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

/***/ })

/******/ });