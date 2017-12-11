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
/******/ 	return __webpack_require__(__webpack_require__.s = 77);
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

/***/ 11:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "/*dialog*/\r\n.dialog.mask{position:fixed;background:rgba(0,0,0,0.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:1060}\r\n.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}\r\n.dialog .dlg-header{padding:15px 15px 0 15px}\r\n.dialog .dlg-body{padding:15px 15px 0 15px}\r\n.dialog .dlg-footer{text-align:right;padding:15px}\r\n.dialog .dlg-footer button{border-radius:0}\r\n\r\n/*filter*/\r\ndiv[wrap=filter] .detail{background:#ccc}\r\ndiv[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}\r\ndiv[wrap=filter] .detail .actions .btn{border-radius:0}\r\n\r\n/*switch*/\r\n.tms-switch{position:fixed;right:15px;width:70px;box-shadow:0px 2px 6px rgba(18,27,32,0.425);height:35px;color:#2994d0;background:#f8fcfe;border-radius:21px;font-size:24px;line-height:27px;text-align:center;cursor:pointer;z-index:1050;}\r\n.tms-switch:before{font-size:0.7em;}\r\n.tms-switch:nth-last-of-type(1){bottom:8px;}\r\n.tms-switch:nth-last-of-type(2){bottom:64px;}\r\n.tms-switch:nth-last-of-type(3){bottom:120px;}\r\n.tms-switch:nth-last-of-type(4){bottom:176px;}\r\n.tms-switch:nth-last-of-type(5){bottom:232px;}\r\n.tms-switch:nth-last-of-type(6){bottom:288px;}\r\n.tms-switch-back:before{content:'\\8FD4\\56DE';}\r\n.tms-switch-task:before{content:'\\4EFB\\52A1';}\r\n.tms-switch-save:before{content:'\\4FDD\\5B58';}\r\n.tms-switch-rank:before{content:'\\6392\\884C';}\r\n.tms-switch-repos:before{content:'\\5171\\4EAB';}\r\n.tms-switch-coinpay:before{content:'\\6253\\8D4F';}\r\n@media screen and (max-width:768px){\r\n\tbody{margin-bottom:60px;}\r\n\t.tms-switch:nth-last-of-type(1){right:8px;bottom:10px;}\r\n\t.tms-switch:nth-last-of-type(2){right:85px;bottom:10px;}\r\n\t.tms-switch:nth-last-of-type(3){right:165px;bottom:10px;}\r\n\t.tms-switch:nth-last-of-type(4){right:176px;bottom:10px;}\r\n\t.tms-switch:nth-last-of-type(5){right:232px;bottom:10px;}\r\n\t.tms-switch:nth-last-of-type(6){right:288px;bottom:10px;}\r\n}\r\n#frmPlugin{position:absolute;top:0;bottom:0;left:0;right:0;width:100%;height:100%;border:none;z-index:1060;box-sizing:border-box;padding-bottom:48px;background:#fff;}\r\n#frmPlugin iframe{width:100%;height:100%;border:0;}\r\n#frmPlugin:after{content:'\\5173\\95ED';position:absolute;width:100px;text-align:center;left:50%;margin-left:-50px;bottom:4px;padding:5px 6px 3px;border:1px solid #ccc;border-radius:4px;}\r\n\r\n/*input list view*/\r\ndiv[wrap]>.description{word-wrap:break-word;}\r\n", ""]);

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
                years: [2014, 2015, 2016, 2017],
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
ngMod.service('srvUserTask', ['$http', 'tmsModal', function($http, tmsModal) {
    this.open = function(oApp) {
        var oUserTask, template;
        template = '<div class="modal-body">';
        if (oApp.summary) {
            template += '<div class="form-group">活动说明：' + oApp.summary + '</div>';
        }
        oUserTask = oApp.userTask;
        template += '<table class="table table-bordered">';
        template += '<thead><tr><th>任务</th><th>要求</th><th>完成</th></tr></thead>';
        template += '<tbody>';
        if (oUserTask.minEnrollNum && parseInt(oUserTask.minEnrollNum) > 0) {
            template += '<tr>';
            template += '<td>至少填写记录条数</td>';
            template += '<td>' + oUserTask.minEnrollNum + '</td>';
            template += '<td>{{enrollee.enroll_num}}</td>';
            template += '</tr>';
        }
        if (oUserTask.minRemarkNum && parseInt(oUserTask.minRemarkNum) > 0) {
            template += '<tr>';
            template += '<td>至少发表评论条数</td>';
            template += '<td>' + oUserTask.minRemarkNum + '</td>';
            template += '<td>{{enrollee.remark_other_num}}</td>';
            template += '</tr>';
        }
        if (oUserTask.minLikeNum && parseInt(oUserTask.minLikeNum) > 0) {
            template += '<tr>';
            template += '<td>对记录至少发表赞同的条数</td>';
            template += '<td>' + oUserTask.minLikeNum + '</td>';
            template += '<td>{{enrollee.like_other_num}}</td>';
            template += '</tr>';
        }
        if (oUserTask.maxLikeNum && parseInt(oUserTask.maxLikeNum) > 0) {
            template += '<tr>';
            template += '<td>对记录最多发表赞同的条数</td>';
            template += '<td>' + oUserTask.maxLikeNum + '</td>';
            template += '<td>{{enrollee.like_other_num}}</td>';
            template += '</tr>';
        }
        template += '</tbody></table>';
        template += '</div>';
        template += '<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭任务说明</button></div>';
        tmsModal.open({
            template: template,
            controller: ['$scope', '$tmsModalInstance', function($scope2, $mi) {
                $http.get('/rest/site/fe/matter/enroll/user/task?site=' + oApp.siteid + '&app=' + oApp.id).success(function(rsp) {
                    $scope2.enrollee = rsp.data;
                });
                $scope2.cancel = function() {
                    $mi.dismiss();
                };
            }]
        });
    };
}]);

/***/ }),

/***/ 14:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "html,body{font-family:Microsoft Yahei,Arial;width:100%;height:auto;}\r\nbody{position:relative;font-size:16px;padding:0;}\r\n.ng-cloak{display:none;}\r\n.container{position:relative;}\r\n.navbar-default .navbar-nav > li > a,.navbar-default .navbar-brand{color:#fff;}\r\n.navbar-brand{height:55px;padding:17.5px 15px;}\r\n.main-navbar .navbar-brand:hover{color:#fff;}\r\n@media screen and (min-width:768px){\r\n\t.navbar-nav>li>a{padding:17.5px 30px;font-size:18px;line-height:1;}\r\n}\r\n@media screen and (max-width:768px){\r\n\t.navbar-brand{width:100%;text-align:center;}\r\n\t.navbar-brand > .icon-note{display:inline-block;width:124px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}\r\n\t.navbar-nav{margin:8px 0;position:absolute;top:0;right:0;}\r\n\t.nav > li > a{padding:10px 10px;}\r\n}\r\n", ""]);

// exports


/***/ }),

/***/ 15:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(14);
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
        18810091009
    };
    this.openPlugin = function(content) {
        var frag, wrap, frm, body, deferred = $q.defer();
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

/***/ 37:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(15);
__webpack_require__(12);
__webpack_require__(63);

__webpack_require__(2);
__webpack_require__(10);
__webpack_require__(6);
__webpack_require__(9);

__webpack_require__(13);

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'page.ui.xxt', 'directive.enroll', 'siteuser.ui.xxt', 'favor.ui.xxt']);
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
ngApp.config(['$controllerProvider', 'lsProvider', function($cp, lsProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    lsProvider.params(['site', 'app', 'rid', 'page', 'ek', 'preview', 'newRecord', 'ignoretime']);
}]);
ngApp.factory('Round', ['$http', '$q', function($http, $q) {
    var Round = function(scenario, template) {
        this.scenario = scenario;
        this.template = template;
    };
    Round.prototype.list = function() {
        var _this, deferred, promise, url;
        _this = this;
        deferred = $q.defer();
        promise = deferred.promise;
        url = '/rest/site/fe/matter/enroll/template/round/list';
        url += '?scenario=' + _this.scenario;
        url += '&template=' + _this.template;
        $http.get(url).success(function(rsp) {
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            deferred.resolve(rsp.data);
        });
        return promise;
    };
    return Round;
}]);
var LS = (function() {
    function locationSearch() {
        var ls, search;
        ls = location.search;
        search = {};
        angular.forEach(['scenario', 'template', 'page', 'rid'], function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            search[q] = match ? match[1] : '';
        });
        return search;
    };
    /*join search*/
    function j(method) {
        var i, j, l, url = '/rest/site/fe/matter/enroll/template',
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
})();
ngApp.controller('ctrlRounds', ['$scope', 'Round', function($scope, Round) {
    var facRound, onDataReadyCallbacks;
    facRound = new Round(LS.p.scenario, LS.p.template);
    facRound.list().then(function(rounds) {
        $scope.rounds = rounds;
        angular.forEach(onDataReadyCallbacks, function(cb) {
            cb(rounds);
        });
    });
    onDataReadyCallbacks = [];
    $scope.onDataReady = function(callback) {
        onDataReadyCallbacks.push(callback);
    };
    $scope.match = function(matched) {
        var i, l, round;
        for (i = 0, l = $scope.rounds.length; i < l; i++) {
            round = $scope.rounds[i];
            if (matched.rid === $scope.rounds[i].rid) {
                return $scope.rounds[i];
            }
        }
        return false;
    };
}]);
ngApp.factory('Record', ['$http', '$q', function($http, $q) {
    var _ins, _running, Record;
    Record = function(scenario, template) {
        this.scenario = scenario;
        this.template = template;
        this.current = {
            data: {},
            enroll_at: 0
        };
    };
    _running = false;
    Record.prototype.get = function(config) {
        if (_running) return false;
        _running = true;
        var _this, url, deferred;
        _this = this;
        deferred = $q.defer();
        url = '/rest/site/fe/matter/enroll/template/record/get';
        url += '?scenario=' + _this.scenario;
        url += '&template=' + _this.template;
        $http.post(url, config).success(function(rsp) {
            var record;
            record = rsp.data;
            if (rsp.err_code == 0) {
                _this.current = record;
                deferred.resolve(record);
            }
            _running = false;
        });
        return deferred.promise;
    };
    Record.prototype.list = function(owner, rid) {
        var _this, url, deferred;
        _this = this;
        deferred = $q.defer();
        url = '/rest/site/fe/matter/enroll/template/record/list';
        url += '?scenario=' + _this.scenario;
        url += '&template=' + _this.template;
        rid !== undefined && rid.length && (url += '&rid=' + rid);
        owner !== undefined && owner.length && (url += '&owner=' + owner);
        $http.get(url).success(function(rsp) {
            var records, record;
            if (rsp.err_code == 0) {
                records = rsp.data.records;
                if (records && records.length) {
                    for (var i = 0; i < records.length; i++) {
                        record = records[i];
                        record.data.member && (record.data.member = JSON.parse(record.data.member));
                    }
                }
                deferred.resolve(records);
            }
        });
        return deferred.promise;
    };
    return {
        ins: function(scenario, template) {
            if (_ins) {
                return _ins;
            }
            _ins = new Record(scenario, template);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlRecord', ['$scope', 'Record', function($scope, Record) {
    var facRecord = Record.ins(LS.p.scenario, LS.p.template),
        schemas = [],
        i;
    facRecord.get($scope.CustomConfig);
    $scope.Record = facRecord;
    for (i in $scope.page.data_schemas) {
        schemas.push($scope.page.data_schemas[i].schema);
    }
    $scope.value2Label = function(key) {
        var val, i, j, s, aVal, aLab = [];
        if (schemas && facRecord.current.data) {
            val = facRecord.current.data[key];
            if (val === undefined) return '';
            for (i = 0, j = schemas.length; i < j; i++) {
                s = schemas[i];
                if (schemas[i].id === key) {
                    s = schemas[i];
                    break;
                }
            }
            if (s && s.ops && s.ops.length) {
                aVal = val.split(',');
                for (i = 0, j = s.ops.length; i < j; i++) {
                    aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].l);
                }
                if (aLab.length) return aLab.join(',');
            }
            return val;
        } else {
            return '';
        }
    };
}]);
ngApp.controller('ctrlRecords', ['$scope', 'Record', function($scope, Record) {
    var facRecord, options, fnFetch, rid;
    if (LS.p.rid === '' && $scope.$parent.ActiveRound) {
        rid = $scope.$parent.ActiveRound.rid;
    } else {
        rid = LS.p.rid;
    }
    facRecord = Record.ins(LS.p.scenario, LS.p.template);
    options = {
        owner: 'A',
        rid: rid
    };
    fnFetch = function() {
        facRecord.list(options.owner, options.rid).then(function(records) {
            $scope.records = records;
        });
    };
    $scope.$on('xxt.app.enroll.filter.rounds', function(event, data) {
        options.rid = data[0].rid;
        fnFetch();
    });
    $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
        options.owner = data[0].id;
        fnFetch();
    });
    $scope.fetch = fnFetch;
    $scope.options = options;
}]);
ngApp.controller('ctrlOwnerOptions', ['$scope', function($scope) {
    $scope.owners = {
        'A': {
            id: 'A',
            label: '全部'
        },
        'U': {
            id: 'U',
            label: '我的'
        }
    };
    $scope.match = function(owner) {
        return $scope.owners[owner.id];
    }
}]);
ngApp.controller('ctrlOrderbyOptions', ['$scope', function($scope) {
    $scope.orderbys = {
        time: {
            id: 'time',
            label: '最新'
        },
        score: {
            id: 'score',
            label: '点赞'
        },
        remark: {
            id: 'remark',
            label: '评论'
        }
    };
}]);
ngApp.controller('ctrlMain', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
    function renew(page, config) {
        $scope.CustomConfig = config;
        $http.post(LS.j('pageGet', 'scenario', 'template') + '&page=' + page, config).success(function(rsp) {
            var params;
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            params = rsp.data;
            $scope.params = params;
            $scope.page = params.page;
            $scope.User = params.user;
            $scope.ActiveRound = params.activeRound;
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
                    angular.forEach(page.ext_js, function(js) {
                        $.getScript(js.url);
                    });
                }
                if (page.js && page.js.length) {
                    (function dynamicjs() {
                        eval(page.js);
                    })();
                }
            })(params.page);
        });
    };
    window.renew = function(page, config) {
        var phase;
        phase = $scope.$root.$$phase;
        if (phase === '$digest' || phase === '$apply') {
            renew(page, config);
        } else {
            $scope.$apply(function() {
                renew(page, config);
            });
        }
    };
    $scope.errmsg = '';
    $scope.data = {
        member: {}
    };
    $scope.CustomConfig = {};
    $scope.gotoPage = function(event, page, ek, rid, newRecord) {};
    $scope.addRecord = function(event) {};
    $scope.editRecord = function(event, page) {};
    $scope.likeRecord = function(event) {};
    $scope.remarkRecord = function(event) {};
    $scope.openMatter = function(id, type) {};
    $scope.$on('xxt.app.enroll.filter.rounds', function(event, data) {
        if (event.targetScope !== $scope) {
            $scope.$broadcast('xxt.app.enroll.filter.rounds', data);
        }
    });
    $scope.$on('xxt.app.enroll.filter.owner', function(event, data) {
        if (event.targetScope !== $scope) {
            $scope.$broadcast('xxt.app.enroll.filter.owner', data);
        }
    });

    window.renew(LS.p.page, {});
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
var content = __webpack_require__(8);
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

/***/ 51:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "html,body{min-height:100%;width:100%;font-family:Microsoft Yahei,Arial;}\r\nbody{position:relative;font-size:16px;padding:0 15px;}\r\n.app{margin-bottom:64px;margin-top:15px;}\r\nimg{max-width:100%}\r\nul{margin:0;padding:0;list-style:none}\r\nli{margin:0;padding:0}\r\n#errmsg{display:block;opacity:0;height:0;overflow:hidden;width:300px;position:fixed;top:0;left:50%;margin:0 0 0 -150px;text-align:center;transition:opacity 1s;z-index:-1;word-break:break-all}\r\n#errmsg.active{opacity:1;height:auto;z-index:999}\r\n\r\n/* img tiles */\r\nul.img-tiles li{position:relative;display:inline-block;overflow:hidden;width:80px;height:80px;margin:4px;float:left}\r\nul.img-tiles li.img-thumbnail img{display:inline-block;position:absolute;max-width:none;}\r\nul.img-tiles li.img-thumbnail button{position:absolute;top:0;right:0}\r\nul.img-tiles li.img-picker button{position:auto;width:100%;height:100%}\r\nul.img-tiles li.img-picker button span{font-size:36px}\r\n\r\n/* default form style*/\r\ndiv[wrap].wrap-splitline{padding-bottom:0.5em;border-bottom:1px solid #fff;}\r\ndiv[wrap].wrap-inline>*{display:inline-block;vertical-align:middle;margin:0 1em 0 0;}\r\ndiv[wrap].wrap-inline>label{width:6em;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}\r\n\r\n/*list*/\r\nli .wrap-inline>label{padding:7px 0;color:#444;}\r\nli .wrap-inline{border-bottom:1px dashed #efefef;}\r\nli .wrap-inline:last-child{border-bottom:0;}\r\n", ""]);

// exports


/***/ }),

/***/ 6:
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
                    tmsDynaPage.openPlugin('http://' + location.host + '/rest/site/fe/user/login?site=' + oMatter.siteid).then(function(data) {
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

/***/ 63:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(51);
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
		module.hot.accept("!!../../../../../../node_modules/css-loader/index.js!./template.css", function() {
			var newContent = require("!!../../../../../../node_modules/css-loader/index.js!./template.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

/***/ }),

/***/ 77:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(37);


/***/ }),

/***/ 8:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".modal {\r\n    display: block;\r\n    overflow: hidden;\r\n    position: fixed;\r\n    top: 0;\r\n    right: 0;\r\n    bottom: 0;\r\n    left: 0;\r\n    outline: 0;\r\n    opacity: 1;\r\n    overflow-x: hidden;\r\n    overflow-y: auto;\r\n    opacity: 1;\r\n}\r\n\r\n.modal-backdrop {\r\n    position: fixed;\r\n    top: 0;\r\n    right: 0;\r\n    bottom: 0;\r\n    left: 0;\r\n    background-color: #000;\r\n    opacity: .5;\r\n}\r\n\r\n.modal-dialog {\r\n    position: relative;\r\n    z-index: 1055;\r\n    margin: 0;\r\n    position: relative;\r\n    width: auto;\r\n    margin: 10px;\r\n}\r\n\r\n.modal-content {\r\n    position: relative;\r\n    background-color: #fff;\r\n    -webkit-background-clip: padding-box;\r\n    background-clip: padding-box;\r\n    border: 1px solid #999;\r\n    border: 1px solid rgba(0, 0, 0, .2);\r\n    border-radius: 6px;\r\n    outline: 0;\r\n    -webkit-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);\r\n    box-shadow: 0 3px 9px rgba(0, 0, 0, .5);\r\n}\r\n\r\n.modal-header {\r\n    padding: 15px;\r\n    border-bottom: 1px solid #e5e5e5;\r\n}\r\n\r\n.modal-header .close {\r\n    margin-top: -2px;\r\n}\r\n\r\n.modal-title {\r\n    margin: 0;\r\n    line-height: 1.42857143;\r\n}\r\n\r\n.modal-body {\r\n    position: relative;\r\n    padding: 15px;\r\n}\r\n\r\n.modal-footer {\r\n    padding: 15px;\r\n    text-align: right;\r\n    border-top: 1px solid #e5e5e5;\r\n}\r\n\r\nbutton.close {\r\n    -webkit-appearance: none;\r\n    padding: 0;\r\n    cursor: pointer;\r\n    background: 0 0;\r\n    border: 0;\r\n}\r\n\r\n.close {\r\n    float: right;\r\n    font-size: 21px;\r\n    font-weight: 700;\r\n    line-height: 1;\r\n    color: #000;\r\n    text-shadow: 0 1px 0 #fff;\r\n    filter: alpha(opacity=20);\r\n    opacity: .2;\r\n}\r\n\r\n@media (min-width:768px) {\r\n    .modal-dialog {\r\n        width: 600px;\r\n        margin: 30px auto;\r\n    }\r\n    .modal-content {\r\n        -webkit-box-shadow: 0 5px 15px rgba(0, 0, 0, .5);\r\n        box-shadow: 0 5px 15px rgba(0, 0, 0, .5);\r\n    }\r\n}\r\n", ""]);

// exports


/***/ }),

/***/ 9:
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


/***/ })

/******/ });