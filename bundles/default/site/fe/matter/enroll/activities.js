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
/******/ 	return __webpack_require__(__webpack_require__.s = 144);
/******/ })
/************************************************************************/
/******/ ({

/***/ 0:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function (useSourceMap) {
  var list = []; // return the list of modules as css string

  list.toString = function toString() {
    return this.map(function (item) {
      var content = cssWithMappingToString(item, useSourceMap);

      if (item[2]) {
        return '@media ' + item[2] + '{' + content + '}';
      } else {
        return content;
      }
    }).join('');
  }; // import a list of modules into the list


  list.i = function (modules, mediaQuery) {
    if (typeof modules === 'string') {
      modules = [[null, modules, '']];
    }

    var alreadyImportedModules = {};

    for (var i = 0; i < this.length; i++) {
      var id = this[i][0];

      if (id != null) {
        alreadyImportedModules[id] = true;
      }
    }

    for (i = 0; i < modules.length; i++) {
      var item = modules[i]; // skip already imported module
      // this implementation is not 100% perfect for weird media query combinations
      // when a module is imported multiple times with different media queries.
      // I hope this will never occur (Hey this way we have smaller bundles)

      if (item[0] == null || !alreadyImportedModules[item[0]]) {
        if (mediaQuery && !item[2]) {
          item[2] = mediaQuery;
        } else if (mediaQuery) {
          item[2] = '(' + item[2] + ') and (' + mediaQuery + ')';
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
      return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */';
    });
    return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
  }

  return [content].join('\n');
} // Adapted from convert-source-map (MIT)


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

var getTarget = function (target, parent) {
  if (parent){
    return parent.querySelector(target);
  }
  return document.querySelector(target);
};

var getElement = (function (fn) {
	var memo = {};

	return function(target, parent) {
                // If passing function in options, then use it for resolve "head" element.
                // Useful for Shadow Root style i.e
                // {
                //   insertInto: function () { return document.querySelector("#foo").shadowRoot }
                // }
                if (typeof target === 'function') {
                        return target();
                }
                if (typeof memo[target] === "undefined") {
			var styleTarget = getTarget.call(this, target, parent);
			// Special case to return head of iframe instead of iframe itself
			if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
				try {
					// This will throw an exception if access to iframe is blocked
					// due to cross-origin restrictions
					styleTarget = styleTarget.contentDocument.head;
				} catch(e) {
					styleTarget = null;
				}
			}
			memo[target] = styleTarget;
		}
		return memo[target]
	};
})();

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
	if (!options.singleton && typeof options.singleton !== "boolean") options.singleton = isOldIE();

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
	} else if (typeof options.insertAt === "object" && options.insertAt.before) {
		var nextSibling = getElement(options.insertAt.before, target);
		target.insertBefore(style, nextSibling);
	} else {
		throw new Error("[Style Loader]\n\n Invalid value for parameter 'insertAt' ('options.insertAt') found.\n Must be 'top', 'bottom', or Object.\n (https://github.com/webpack-contrib/style-loader#insertat)\n");
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

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}

	if(options.attrs.nonce === undefined) {
		var nonce = getNonce();
		if (nonce) {
			options.attrs.nonce = nonce;
		}
	}

	addAttrs(style, options.attrs);
	insertStyleElement(options, style);

	return style;
}

function createLinkElement (options) {
	var link = document.createElement("link");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}
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

function getNonce() {
	if (false) {
		return null;
	}

	return __webpack_require__.nc;
}

function addStyle (obj, options) {
	var style, update, remove, result;

	// If a transform function was defined, run it on the css
	if (options.transform && obj.css) {
	    result = typeof options.transform === 'function'
		 ? options.transform(obj.css) 
		 : options.transform.default(obj.css);

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

/***/ 109:
/***/ (function(module, exports) {

module.exports = "<div class=\"app\" id=\"event\">\r\n    <div ng-if=\"tasks.length\">\r\n        <div uib-alert ng-repeat=\"task in tasks\" class='alert-info' close=\"closeTask($index)\" ng-switch=\"task.id\">\r\n            <span>{{task.msg}}</span>\r\n            <span ng-switch-when=\"record.submit.end\"><span ng-if=\"task.coin\">每条记录可获得【{{task.coin}}个】积分，</span><a href class=\"alert-link\" ng-click=\"addRecord($event)\">去添加</a></span>\r\n            <span ng-switch-when=\"record.like.pre\"></span>\r\n            <span ng-switch-when=\"record.like.end\"></span>\r\n        </div>\r\n    </div>\r\n    <div class='form-group'>\r\n        <label class='radio-inline'>\r\n            <input type='radio' name='scope' value='N' ng-model=\"filter.scope\">通知</label>\r\n        <label class='radio-inline'>\r\n            <input type='radio' name='scope' value='M' ng-model=\"filter.scope\">我的</label>\r\n        <label class='radio-inline'>\r\n            <input type='radio' name='scope' value='A' ng-model=\"filter.scope\">全部</label>\r\n    </div>\r\n    <div ng-include=\"subView\"></div>\r\n</div>\r\n<script type=\"text/ng-template\" id=\"timeline.html\">\r\n    <!--notice list-->\r\n    <div ng-if=\"filter.scope==='N'\">\r\n        <div class='list-group'>\r\n            <div class='notice list-group-item' ng-repeat=\"notice in notices\">\r\n                <div class='seq label label-default'>{{((page.at-1)*page.size)+$index+1}}</div>\r\n                <div class='event'>\r\n                    <div class='tms-flex-row text-muted event-user'>\r\n                        <div class='tms-flex-grow'>{{notice.event_nickname}}</div>\r\n                        <div>{{notice.event_at*1000|date:'MM-dd HH:mm'}}</div>\r\n                    </div>\r\n                    <div ng-switch on=\"notice.event_name\" ng-click=\"gotoCowork(notice.enroll_key)\">\r\n                        <div ng-switch-when=\"site.matter.enroll.submit\">\r\n                            <div ng-switch on=\"notice.notice_reason\">\r\n                                <div ng-switch-when=\"same.group\">在你们的分组下添加记录</div>\r\n                                <div ng-switch-when=\"as.editor\">添加记录</div>\r\n                                <div ng-switch-when=\"as.super\">添加记录</div>\r\n                                <div ng-switch-default> 添加记录\r\n                                    <span>（{{notice.notice_reason}}）</span>\r\n                                </div>\r\n                            </div>\r\n                        </div>\r\n                        <div ng-switch-when=\"site.matter.enroll.cowork.do.submit\">\r\n                            <div ng-switch on=\"notice.notice_reason\">\r\n                                <div ng-switch-when=\"record.owner\">在你的记录（问题）下添加了协作数据（答案）</div>\r\n                                <div ng-switch-when=\"other.cowork\">你们在同一记录（问题）下添加了协作数据（答案）</div>\r\n                                <div ng-switch-when=\"same.group\">在你们组的记录下（问题）添加了协作数据（答案）</div>\r\n                                <div ng-switch-default> 添加协作数据\r\n                                    <span>（{{notice.notice_reason}}）</span>\r\n                                </div>\r\n                            </div>\r\n                        </div>\r\n                        <div ng-switch-when=\"site.matter.enroll.do.remark\">\r\n                            <div ng-switch on=\"notice.notice_reason\">\r\n                                <div ng-switch-when=\"record.owner\">在你的记录（问题）下留言</div>\r\n                                <div ng-switch-when=\"record.data.owner\">在你的协作数据（答案）下留言</div>\r\n                                <div ng-switch-when=\"remark.owner\">在你的留言下留言</div>\r\n                                <div ng-switch-default>留言\r\n                                    <span>（{{notice.notice_reason}}）</span>\r\n                                </div>\r\n                            </div>\r\n                        </div>\r\n                        <div ng-switch-default>\r\n                            {{notice.event_name}}\r\n                        </div>\r\n                    </div>\r\n                    <div class='text-right event-action'>\r\n                        <a href ng-click=\"closeNotice(notice)\">关闭</a>\r\n                        <a href ng-click=\"closeNotice(notice,true)\">看看并关闭</a>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n        <div class='site-pagination text-center'>\r\n            <ul uib-pagination ng-if=\"page.total>page.size\" boundary-links=\"true\" total-items=\"page.total\" max-size=\"5\" items-per-page=\"page.size\" rotate=\"false\" ng-model=\"page.at\" previous-text=\"&lsaquo;\" next-text=\"&rsaquo;\" first-text=\"&laquo;\" last-text=\"&raquo;\" ng-change=\"searchNotice()\"></ul>\r\n        </div>\r\n    </div>\r\n    <!--end notice list-->\r\n    <!--log list-->\r\n    <div ng-if=\"filter.scope==='A'||filter.scope==='M'\">\r\n        <div class='list-group'>\r\n            <div class='action list-group-item' ng-repeat=\"log in logs\">\r\n                <div class='seq label label-default'>{{((page.at-1)*page.size)+$index+1}}</div>\r\n                <div class='event' ng-switch on=\"log.event_name\">\r\n                    <div ng-switch-when=\"site.matter.enroll.submit\">\r\n                        <span class='nickname'>{{log.nickname}}</span> 添加记录\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.cowork.do.submit\">\r\n                        <span class='nickname'>{{log.nickname}}</span> 在\r\n                        <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的记录下添加数据\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.do.remark\">\r\n                        <div ng-switch on=\"log.target_type\">\r\n                            <div ng-switch-when=\"record\">\r\n                                <div ng-switch on=\"log.event_op\">\r\n                                    <span class='nickname'>{{log.nickname}}</span> 在\r\n                                    <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的记录下<span ng-switch-when=\"New\">添加</span><span ng-switch-when=\"Upd\">修改</span>留言\r\n                                </div>\r\n                            </div>\r\n                            <div ng-switch-when=\"record.data\">\r\n                                <div ng-switch on=\"log.event_op\">\r\n                                    <span class='nickname'>{{log.nickname}}</span> 在\r\n                                    <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的数据下<span ng-switch-when=\"New\">添加</span><span ng-switch-when=\"Upd\">修改</span>留言\r\n                                </div>\r\n                            </div>\r\n                            <div ng-switch-when=\"remark\">\r\n                                <div ng-switch on=\"log.event_op\">\r\n                                    <span class='nickname'>{{log.nickname}}</span> 在\r\n                                    <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的留言下<span ng-switch-when=\"New\">添加</span><span ng-switch-when=\"Upd\">修改</span>留言\r\n                                </div>\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.data.do.like\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的记录点赞\r\n                            </div>\r\n                            <div ng-switch-when=\"N\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的记录取消点赞\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.data.do.dislike\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的记录点踩\r\n                            </div>\r\n                            <div ng-switch-when=\"N\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的记录取消点踩\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.cowork.do.like\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的数据点赞\r\n                            </div>\r\n                            <div ng-switch-when=\"N\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的数据取消点赞\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.cowork.do.dislike\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的数据点踩\r\n                            </div>\r\n                            <div ng-switch-when=\"N\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的数据取消点踩\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.remark.do.like\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的留言点赞\r\n                            </div>\r\n                            <div ng-switch-when=\"N\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的留言取消点赞\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.remark.do.dislike\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的留言点踩\r\n                            </div>\r\n                            <div ng-switch-when=\"N\">\r\n                                <span class='nickname'>{{log.nickname}}</span> 对\r\n                                <span class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</span> 的留言取消点踩\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.data.get.agree\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.owner_nickname}}</span> 的记录获得推荐\r\n                            </div>\r\n                            <div ng-switch-default>\r\n                                <span class='nickname'>{{log.owner_nickname}}</span> 的记录被修改了表态\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.cowork.get.agree\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.owner_nickname}}</span> 的数据获得推荐\r\n                            </div>\r\n                            <div ng-switch-default>\r\n                                <span class='nickname'>{{log.owner_nickname}}</span> 的数据被修改了表态\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.remark.get.agree\">\r\n                        <div ng-switch on=\"log.event_op\">\r\n                            <div ng-switch-when=\"Y\">\r\n                                <span class='nickname'>{{log.owner_nickname}}</span> 的留言获得推荐\r\n                            </div>\r\n                            <div ng-switch-default>\r\n                                <span class='nickname'>{{log.owner_nickname}}</span> 的留言被修改了表态\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-switch-when=\"site.matter.enroll.remark.as.cowork\">\r\n                        <span class='nickname'>{{log.nickname}}</span>将<span class='nickname'>{{log.owner_nickname}}</span> 的留言设置为协作数据（答案）\r\n                    </div>\r\n                    <div ng-switch-default>\r\n                        <div class='nickname'>{{log.nickname}}</div>\r\n                        <div class='eventname'>{{log.eventName}}</div>\r\n                        <div class='nickname' ng-if=\"log.owner_nickname\">{{log.owner_nickname}}</div>\r\n                        <div ng-if=\"log.event_op\">{{log.event_op}}</div>\r\n                    </div>\r\n                    <div class='coin' ng-if=\"log.earn_coin>0\">\r\n                        <span>{{log.nickname}}</span> 得到 <span>{{log.earn_coin}}</span> 个积分\r\n                    </div>\r\n                    <div class='coin' ng-if=\"log.owner_earn_coin>0\">\r\n                        <span>{{log.owner_nickname}}</span> 得到 <span>{{log.owner_earn_coin}}</span> 个积分\r\n                    </div>\r\n                </div>\r\n                <div class='footer'>\r\n                    <div class='datetime text-muted'>{{log.event_at*1000|date:'MM-dd HH:mm'}}</div>\r\n                    <div ng-if=\"log.canGotoCowork\">\r\n                        <a href ng-click=\"gotoCowork(log.enroll_key)\">查看<span class='glyphicon glyphicon-menu-right'></span></a>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n        <div class='site-pagination text-center'>\r\n            <ul uib-pagination ng-if=\"page.total>page.size\" boundary-links=\"true\" total-items=\"page.total\" max-size=\"5\" items-per-page=\"page.size\" rotate=\"false\" ng-model=\"page.at\" previous-text=\"&lsaquo;\" next-text=\"&rsaquo;\" first-text=\"&laquo;\" last-text=\"&raquo;\" ng-change=\"searchEvent()\"></ul>\r\n        </div>\r\n    </div>\r\n    <!--end log list-->\r\n</script>"

/***/ }),

/***/ 11:
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

/***/ 110:
/***/ (function(module, exports) {

module.exports = "<div class=\"app\" id='kanbanView'>\r\n    <div class='form-group'>\r\n        <div class='form-inline'>\r\n            <div ng-if=\"rounds.length>1\" class=\"btn-group\" uib-dropdown dropdown-append-to-body='true'>\r\n                <button class=\"btn btn-default dropdown-toggle\" uib-dropdown-toggle>\r\n                    <span ng-bind=\"filter.round.title\"></span>\r\n                    <span class=\"glyphicon glyphicon-filter\"></span>\r\n                </button>\r\n                <ul class=\"dropdown-menu\" uib-dropdown-menu>\r\n                    <li><a href ng-click=\"shiftRound({rid:'ALL',title:'全部'})\">全部</a></li>\r\n                    <li ng-repeat=\"rnd in rounds\"><a href ng-click=\"shiftRound(rnd)\">{{rnd.title}}</a></li>\r\n                </ul>\r\n            </div>\r\n            <div class=\"btn-group\" uib-dropdown dropdown-append-to-body='true'>\r\n                <button class=\"btn btn-default dropdown-toggle\" uib-dropdown-toggle>\r\n                    <span ng-if=\"criteria.orderby==='score'\">得分</span>\r\n                    <span ng-if=\"criteria.orderby==='user_total_coin'\">积分</span>\r\n                    <span ng-if=\"criteria.orderby==='entry_num'\">访问次数</span>\r\n                    <span ng-if=\"criteria.orderby==='total_elapse'\">投入时间</span>\r\n                    <span ng-if=\"criteria.orderby==='devote'\">贡献次数</span>\r\n                    <span class=\"glyphicon glyphicon-sort\"></span>\r\n                </button>\r\n                <ul class=\"dropdown-menu\" uib-dropdown-menu>\r\n                    <li><a href ng-click=\"shiftOrderby('score')\">得分</a></li>\r\n                    <li ng-if=\"app.scenarioConfig.can_coin==='Y'\"><a href ng-click=\"shiftOrderby('user_total_coin')\">积分</a></li>\r\n                    <li><a href ng-click=\"shiftOrderby('entry_num')\">访问次数</a></li>\r\n                    <li><a href ng-click=\"shiftOrderby('total_elapse')\">投入时间</a></li>\r\n                    <li><a href ng-click=\"shiftOrderby('devote')\">贡献次数</a></li>\r\n                </ul>\r\n            </div>\r\n            <div ng-if=\"userGroups.length\" class=\"btn-group\" uib-dropdown dropdown-append-to-body='true'>\r\n                <button class=\"btn btn-default dropdown-toggle\" uib-dropdown-toggle>\r\n                    <span ng-if=\"filter.group\">{{filter.group.title}}</span>\r\n                    <span ng-if=\"!filter.group\">全部分组</span>\r\n                    <span class=\"glyphicon glyphicon-filter\"></span>\r\n                </button>\r\n                <ul class=\"dropdown-menu\" uib-dropdown-menu>\r\n                    <li><a href ng-click=\"shiftUserGroup()\">全部</a></li>\r\n                    <li ng-repeat=\"ug in userGroups\"><a href ng-click=\"shiftUserGroup(ug)\">{{ug.title}}</a></li>\r\n                </ul>\r\n            </div>\r\n            <button ng-show=\"subView==='users'\" class='btn btn-default' ng-click=\"subView='undone'\">查看未完成<span ng-bind=\"kanban.undone.length\"></span>人</button>\r\n            <button ng-show=\"subView==='undone'\" class='btn btn-default' ng-click=\"subView='users'\">查看整体情况</button>\r\n        </div>\r\n    </div>\r\n    <div id='kanban' ng-if=\"subView==='users'\">\r\n        <!-- users -->\r\n        <div class='wrap'>\r\n            <div class='user list-group-item'>\r\n                <div>\r\n                </div>\r\n                <div class='data'>\r\n                    <div>\r\n                        <div></div>\r\n                        <div>\r\n                            <div>排名</div>\r\n                            <div>数值</div>\r\n                            <div>与最大值的比</div>\r\n                            <div>与平均值的比</div>\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n            <div class='user list-group-item' ng-repeat='u in kanban.users'>\r\n                <div class='who' ng-click=\"viewDetail(u)\">\r\n                    <div><span>{{u.nickname}}</span><span ng-if=\"user.uid===u.userid\">（我）</span></div>\r\n                    <div ng-if='app.entryRule.group.id'>\r\n                        <div class='text-muted small'>{{u.group.title}}</div>\r\n                    </div>\r\n                    <div ng-if=\"user.uid===u.userid\">\r\n                        <button class='btn btn-default btn-sm' ng-click=\"toggleProfilePublic($event,u)\">设置为<span ng-if=\"!u.custom.profile.public\">公开</span><span ng-if=\"u.custom.profile.public\">隐身</span></button>\r\n                    </div>\r\n                </div>\r\n                <div class='data'>\r\n                    <div ng-class=\"{'ordered':criteria.orderby==='score'}\">\r\n                        <div>得分</div>\r\n                        <div>\r\n                            <div><span class='pos'>{{u.score.pos}}</span></div>\r\n                            <div><span>{{u.score.val}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.score.max\">{{u.score.val/kanban.stat.score.max|number:2}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.score.mean\">{{u.score.val/kanban.stat.score.mean|number:2}}</span></div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-if=\"app.scenarioConfig.can_coin==='Y'\" ng-class=\"{'ordered':criteria.orderby==='user_total_coin'}\">\r\n                        <div>积分</div>\r\n                        <div>\r\n                            <div><span class='pos'>{{u.user_total_coin.pos}}</span></div>\r\n                            <div><span>{{u.user_total_coin.val}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.user_total_coin.max\">{{u.user_total_coin.val/kanban.stat.user_total_coin.max|number:2}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.user_total_coin.mean\">{{u.user_total_coin.val/kanban.stat.user_total_coin.mean|number:2}}</span></div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-class=\"{'ordered':criteria.orderby==='entry_num'}\">\r\n                        <div>访问次数</div>\r\n                        <div>\r\n                            <div><span class='pos'>{{u.entry_num.pos}}</span></div>\r\n                            <div><span>{{u.entry_num.val}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.entry_num.max\">{{u.entry_num.val/kanban.stat.entry_num.max|number:2}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.entry_num.mean\">{{u.entry_num.val/kanban.stat.entry_num.mean|number:2}}</span></div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-class=\"{'ordered':criteria.orderby==='total_elapse'}\">\r\n                        <div>投入时间</div>\r\n                        <div>\r\n                            <div><span class='pos'>{{u.total_elapse.pos}}</span></div>\r\n                            <div><span>{{u.total_elapse.val|filterTime}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.total_elapse.max\">{{u.total_elapse.val/kanban.stat.total_elapse.max|number:2}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.total_elapse.mean\">{{u.total_elapse.val/kanban.stat.total_elapse.mean|number:2}}</span></div>\r\n                        </div>\r\n                    </div>\r\n                    <div ng-class=\"{'ordered':criteria.orderby==='devote'}\">\r\n                        <div>贡献次数</div>\r\n                        <div>\r\n                            <div><span class='pos'>{{u.devote.pos}}</span></div>\r\n                            <div><span>{{u.devote.val}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.devote.max\">{{u.devote.val/kanban.stat.devote.max|number:2}}</span></div>\r\n                            <div><span ng-if=\"kanban.stat.devote.mean\">{{u.devote.val/kanban.stat.devote.mean|number:2}}</span></div>\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n        <!-- end users -->\r\n    </div>\r\n    <div class='table-responsive' ng-if=\"subView==='undone'\">\r\n        <table class='table table-bordered'>\r\n            <thead>\r\n                <tr>\r\n                    <th style='width:48px'>序号</th>\r\n                    <th>姓名</th>\r\n                    <th ng-if=\"app.entryRule.group.id\">分组</th>\r\n                </tr>\r\n            </thead>\r\n            <tbody>\r\n                <tr ng-repeat='u in kanban.undone'>\r\n                    <td>{{$index+1}}</td>\r\n                    <td>{{u.nickname}}</td>\r\n                    <td ng-if='app.entryRule.group.id'>\r\n                        <div ng-if='u.group'>{{u.group.title}}</div>\r\n                    </td>\r\n                </tr>\r\n            </tbody>\r\n        </table>\r\n    </div>\r\n</div>\r\n<script type=\"text/ng-template\" id=\"userDetail.html\">\r\n    <div class=\"modal-header\">\r\n        <button class=\"close\" ng-click=\"cancel()\">×</button>\r\n        <h5 class=\"modal-title\">详细</h5>\r\n    </div>\r\n    <div id='user-detail' class=\"modal-body\">\r\n        <div>\r\n            <div>姓名</div>\r\n            <div>{{user.nickname}}</div>\r\n        </div>\r\n        <div ng-if='app.entryRule.group.id'>\r\n            <div>分组</div>\r\n            <div>{{user.group.title}}</div>\r\n        </div>\r\n        <hr>\r\n        <div>\r\n            <div>得分</div>\r\n            <div><span>{{user.score.val}}</span></div>\r\n        </div>\r\n        <div ng-if=\"app.scenarioConfig.can_coin==='Y'\">\r\n            <div>积分</div>\r\n            <div><span>{{user.user_total_coin.val}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>访问次数</div>\r\n            <div><span>{{user.entry_num.val}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>投入时间</div>\r\n            <div><span>{{user.total_elapse.val|filterTime}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>贡献次数</div>\r\n            <div><span>{{user.devote.val}}</span></div>\r\n        </div>\r\n        <hr>\r\n        <div ng-class=\"{'undone':user.undone.enroll_num[0]===true}\">\r\n            <div>填写记录</div>\r\n            <div><span>{{user.enroll_num}}</span></div>\r\n        </div>\r\n        <div ng-class=\"{'undone':user.undone.revise_num[0]===true}\">\r\n            <div>跨轮次修改</div>\r\n            <div ng-if='user.revise_num'>{{user.revise_num}}</div>\r\n        </div>\r\n        <div>\r\n            <div>最后填写时间</div>\r\n            <div><span ng-if='user.last_enroll_at>0'>{{user.last_enroll_at*1000|date:'MM-dd HH:mm'}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>协作填写</div>\r\n            <div><span>{{user.do_cowork_num}}</span></div>\r\n        </div>\r\n        <div ng-class=\"{'undone':user.undone.do_remark_num[0]===true}\">\r\n            <div>发表留言</div>\r\n            <div><span>{{user.do_remark_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>发表点赞</div>\r\n            <div><span>{{user.do_like_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>获得推荐</div>\r\n            <div><span>{{user.agree_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>获得协作</div>\r\n            <div><span>{{user.cowork_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>获得留言</div>\r\n            <div><span>{{user.remark_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>获得赞同</div>\r\n            <div><span>{{user.like_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>浏览共享页次数</div>\r\n            <div><span>{{user.do_repos_read_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>浏览专题页次数</div>\r\n            <div><span>{{user.do_topic_read_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>专题页被浏览次数</div>\r\n            <div><span>{{user.topic_read_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>浏览讨论页次数</div>\r\n            <div><span>{{user.do_cowork_read_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>讨论页被浏览次数</div>\r\n            <div><span>{{user.cowork_read_num}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>共享页浏览累计时间</div>\r\n            <div><span>{{user.do_repos_read_elapse|filterTime}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>专题页浏览累计时间</div>\r\n            <div><span>{{user.do_topic_read_elapse|filterTime}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>专题页被浏览累计时间</div>\r\n            <div><span>{{user.topic_read_elapse|filterTime}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>讨论页浏览累计时间</div>\r\n            <div><span>{{user.do_cowork_read_elapse|filterTime}}</span></div>\r\n        </div>\r\n        <div>\r\n            <div>讨论页被浏览累计时间</div>\r\n            <div><span>{{user.cowork_read_elapse|filterTime}}</span></div>\r\n        </div>\r\n    </div>\r\n    <div class=\"modal-footer\">\r\n        <div class='text-center'>\r\n            <button class=\"btn btn-default\" ng-click=\"cancel()\">关闭</button>\r\n        </div>\r\n    </div>\r\n</script>"

/***/ }),

/***/ 111:
/***/ (function(module, exports) {

module.exports = "<div class=\"app\" id=\"tasks\">\r\n    <div class='form-group' ng-if=\"rounds.length>1\">\r\n        <div class=\"btn-group\" uib-dropdown dropdown-append-to-body='true'>\r\n            <button class=\"btn btn-default dropdown-toggle\" uib-dropdown-toggle>\r\n                <span ng-bind=\"selectedRound.title\"></span> <span class=\"glyphicon glyphicon-triangle-bottom\"></span>\r\n            </button>\r\n            <ul class=\"dropdown-menu\" uib-dropdown-menu>\r\n                <li ng-repeat=\"rnd in rounds\"><a href ng-click=\"shiftRound(rnd)\">{{rnd.title}}</a></li>\r\n            </ul>\r\n        </div>\r\n    </div>\r\n    <div class='tasks'>\r\n        <div class='task state-{{task.state}} list-group-item ' ng-repeat=\"task in tasks\">\r\n            <div ng-bind=\"::task\"></div>\r\n            <div>\r\n                <span ng-bind=\"::Label.task.state[task.state]\"></span>\r\n            </div>\r\n            <div ng-switch on=\"task.type\">\r\n                <a href class=\"alert-link\" ng-click=\"gotoTask(task)\">\r\n                        <span ng-switch-when=\"baseline\">去制定目标</span>\r\n                        <span ng-switch-when=\"question\">去提问</span>\r\n                        <span ng-switch-when=\"answer\">去回答</span>\r\n                        <span ng-switch-when=\"vote\">去投票</span>\r\n                        <span ng-switch-when=\"score\">去打分</span>\r\n                    </a>\r\n            </div>\r\n        </div>\r\n    </div>\r\n</div>"

/***/ }),

/***/ 12:
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

/***/ 121:
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(90);

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(1)(content, options);

if(content.locals) module.exports = content.locals;

if(false) {
	module.hot.accept("!!../../../../../../node_modules/css-loader/dist/cjs.js!./activities.css", function() {
		var newContent = require("!!../../../../../../node_modules/css-loader/dist/cjs.js!./activities.css");

		if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];

		var locals = (function(a, b) {
			var key, idx = 0;

			for(key in a) {
				if(!b || a[key] !== b[key]) return false;
				idx++;
			}

			for(key in b) idx--;

			return idx === 0;
		}(content.locals, newContent.locals));

		if(!locals) throw new Error('Aborting CSS HMR due to changed css-modules locals.');

		update(newContent);
	});

	module.hot.dispose(function() { update(); });
}

/***/ }),

/***/ 13:
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

/***/ 14:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(false);
// Module
exports.push([module.i, "html,body{width:100%;height:100%;}\r\nbody{position:relative;font-size:16px;padding:0;}\r\nheader img,footer img{max-width:100%}\r\n.ng-cloak{display:none;}\r\n.container{position:relative;}\r\n.site-navbar-default .navbar-default .navbar-nav>li>a,.navbar-default .navbar-brand{color:#fff;}\r\n.site-navbar-default .navbar-brand{padding:15px 15px;}\r\n.main-navbar .navbar-brand:hover{color:#fff;}\r\n@media screen and (min-width:768px){\r\n\t.site-navbar-default .navbar-nav>li>a{padding:15px 15px;line-height:1;}\r\n}\r\n@media screen and (max-width:768px){\r\n\t.site-navbar-default .navbar-brand>.icon-note{display:inline-block;width:124px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}\r\n\t.site-navbar-default .navbar-nav{margin:8px 0;position:absolute;top:0;right:0;}\r\n\t.site-navbar-default .nav>li>a{padding:10px 10px;}\r\n}\r\n.tms-flex-row{display:flex;align-items:center;}\r\n.tms-flex-row .tms-flex-grow{flex:1;}\r\n.dropdown-menu{min-width:auto;}\r\n.dropdown-menu-top{bottom:100%;top:auto;}\r\n\r\n/*picviewer*/\r\n#previewImage-container{-ms-touch-action:none;touch-action:none;-webkit-touch-action:none;line-height:100vh;background-color:#000;width:100vw;height:100vh;position:fixed;overflow:hidden;top:0;left:0;z-index:1050;transition:transform .3s;-ms-transition:transform .3s;-moz-transition:transform .3s;-webkit-transition:transform .3s;-o-transition:transform .3s;transform:translate3d(100%,0,0);-webkit-transform:translate3d(100%,0,0);-ms-transform:translate3d(100%,0,0);-o-transform:translate3d(100%,0,0);-moz-transform:translate3d(100%,0,0)}\r\n#previewImage-container .previewImage-text{position:absolute;bottom:5px;left:8px;right:8px;z-index:1060;height:36px}\r\n.previewImage-text span{display:inline-block;width:36px;height:36px;line-height:25px;border-radius:18px;font-size:25px;text-align:center;color:#bbb}\r\n.previewImage-text span.page{position:absolute;left:50%;margin-left:-18px;font-size:18px}\r\n.previewImage-text span.prev{position:absolute;left:50%;margin-left:-72px}\r\n.previewImage-text span.next{position:absolute;left:50%;margin-left:36px}\r\n.previewImage-text span.exit{position:absolute;right:0}\r\n.previewImage-text span.exit>i{text-shadow:0 0 .1em #fff,-0 -0 .1em #fff}\r\n#previewImage-container .previewImage-box{width:999999rem;height:100vh}\r\n#previewImage-container .previewImage-box .previewImage-item{width:100vw;height:100vh;margin-right:15px;float:left;text-align:center}\r\n@media screen and (min-width:992px){\r\n\t#previewImage-container .previewImage-box .previewImage-item .previewImage-image{display:block;}\r\n}\r\n@media screen and (max-width:992px){\r\n\t#previewImage-container .previewImage-box .previewImage-item .previewImage-image{width:100%}\r\n}\r\n", ""]);



/***/ }),

/***/ 144:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(66);


/***/ }),

/***/ 15:
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(14);

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(1)(content, options);

if(content.locals) module.exports = content.locals;

if(false) {
	module.hot.accept("!!../../../../../../node_modules/css-loader/dist/cjs.js!./main.css", function() {
		var newContent = require("!!../../../../../../node_modules/css-loader/dist/cjs.js!./main.css");

		if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];

		var locals = (function(a, b) {
			var key, idx = 0;

			for(key in a) {
				if(!b || a[key] !== b[key]) return false;
				idx++;
			}

			for(key in b) idx--;

			return idx === 0;
		}(content.locals, newContent.locals));

		if(!locals) throw new Error('Aborting CSS HMR due to changed css-modules locals.');

		update(newContent);
	});

	module.hot.dispose(function() { update(); });
}

/***/ }),

/***/ 16:
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

/***/ 17:
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

/***/ 18:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(5);
if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage && window.wx) {
    window.wx.ready(function() {
        window.wx.showOptionMenu();
    });
}

__webpack_require__(9);
__webpack_require__(15);
__webpack_require__(11);
__webpack_require__(6);
__webpack_require__(2);
__webpack_require__(4);
__webpack_require__(7);
__webpack_require__(12);
__webpack_require__(13);
__webpack_require__(17);
__webpack_require__(16);

__webpack_require__(10);
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
ngApp.config(['$controllerProvider', '$uibTooltipProvider', '$locationProvider', 'tmsLocationProvider', function($cp, $uibTooltipProvider, $locationProvider, tmsLocationProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({ 'show': 'hide' });
    $locationProvider.html5Mode(true);
    (function() {
        var baseUrl;
        baseUrl = '/rest/site/fe/matter/enroll'
        //
        tmsLocationProvider.config(baseUrl);
    })();
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
        if (/MicroMessenger/i.test(navigator.userAgent)) {
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
                                oAct = { title: '添加记录', func: $scope.addRecord };
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

/***/ 19:
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

/***/ 2:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('http.ui.xxt', ['ng']);
ngMod.provider('tmsLocation', function () {
    var _baseUrl;

    this.config = function (baseUrl) {
        _baseUrl = baseUrl || location.pathname;
    };

    this.$get = ['$location', function ($location) {
        var myLoc;
        if (!_baseUrl) {
            _baseUrl = location.pathname;
        }
        myLoc = {
            s: function () {
                var ls = $location.search();
                if (arguments.length) {
                    var ss = [];
                    for (var i = 0, l = arguments.length; i < l; i++) {
                        ss.push(arguments[i] + '=' + (ls[arguments[i]] || ''));
                    };
                    return ss.join('&');
                }
                return ls;
            },
            j: function (method) {
                var url = _baseUrl,
                    search = [];
                method && method.length && (url += '/' + method);
                for (var i = 1, l = arguments.length; i < l; i++) {
                    search.push(arguments[i] + '=' + ($location.search()[arguments[i]] || ''));
                };
                search.length && (url += '?' + search.join('&'));
                return url;
            },
            path: function () {
                return arguments.length ? $location.path(arguments[0]) : $location.path();
            }
        };

        return myLoc;
    }];
});
ngMod.service('http2', ['$rootScope', '$http', '$timeout', '$q', '$sce', '$compile', function ($rootScope, $http, $timeout, $q, $sce, $compile) {
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
            alertDomEl[0].addEventListener('click', function () {
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
                oOptions.page.j = function () {
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
        if (!oNew) return;
        if (!oOld) {
            oOld = oNew;
        } else if (angular.isArray(oOld)) {
            if (oOld.length > oNew.length) {
                oOld.splice(oNew.length - 1, oOld.length - oNew.length);
            }
            for (var i = 0, ii = oNew.length; i < ii; i++) {
                if (i < oOld.length) {
                    _fnMerge(oOld[i], oNew[i], aExcludeProps);
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
                        _fnMerge(oOld[prop], oNew[prop], aExcludeProps);
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

    this.get = function (url, oOptions) {
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
            _timer = $timeout(function () {
                _timer = null;
                _alert = _fnCreateAlert(oOptions.showProgressText, 'info');
            }, oOptions.showProgressDelay);
        }
        if (_requirePagination(oOptions)) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + oOptions.page.j();
        }
        $http.get(url, oOptions).success(function (rsp) {
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
        }).error(function (data, status) {
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
    this.post = function (url, posted, oOptions) {
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
            _timer = $timeout(function () {
                _timer = null;
                _alert = _fnCreateAlert(oOptions.showProgressText, 'info');
            }, oOptions.showProgressDelay);
        }
        if (_requirePagination(oOptions)) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + oOptions.page.j();
        }
        $http.post(url, posted, oOptions).success(function (rsp) {
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
        }).error(function (data, status) {
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
    this.merge = function (oOld, oNew, aExcludeProps) {
        if (angular.equals(oOld, oNew)) {
            return false;
        }
        return _fnMerge(oOld, oNew, aExcludeProps);
    };
}]);

/***/ }),

/***/ 25:
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-header\">\r\n    <button class=\"close\" type=\"button\" ng-click=\"cancel()\">×</button>\r\n    <h5 class=\"modal-title\">选择轮次</h5>\r\n</div>\r\n<div class=\"modal-body\">\r\n    <div class='form-group'>\r\n        <button class='btn btn-default btn-sm' ng-click=\"clean()\">清除<span>已选的{{countOfChecked}}项</span></button>\r\n    </div>\r\n    <div class='form-group' style='height:230px;overflow-y:auto'>\r\n        <div ng-if=\"!options.excludeAll\">\r\n            <label class='checkbox-inline'>\r\n                <input type='checkbox' ng-model=\"checkedRounds.ALL\" ng-change=\"toggleCheckedRound('ALL')\">全部轮次</label>\r\n        </div>\r\n        <div ng-if=\"activeRound\">\r\n            <label class='checkbox-inline'>\r\n                <input type='checkbox' ng-model=\"checkedRounds[activeRound.rid]\" ng-change=\"toggleCheckedRound(activeRound.rid)\">{{activeRound.title}}<span>（启用）</span></label>\r\n        </div>\r\n        <div ng-repeat=\"rnd in rounds\">\r\n            <label class='checkbox-inline'>\r\n                <input type='checkbox' ng-model=\"checkedRounds[rnd.rid]\" ng-change=\"toggleCheckedRound(rnd.id)\">{{rnd.title}}</label>\r\n        </div>\r\n    </div>\r\n    <div ng-show=\"pageOfRound.total>pageOfRound.size\">\r\n        <span class='hidden-xs' style='line-height:30px'>总数：{{pageOfRound.total}}</span>\r\n        <ul uib-pagination class='pagination-sm' style=\"margin:0;vertical-align:bottom;cursor:pointer\" boundary-links=\"true\" total-items=\"pageOfRound.total\" max-size=\"5\" items-per-page=\"pageOfRound.size\" rotate=\"false\" ng-model=\"pageOfRound.at\" previous-text=\"&lsaquo;\" next-text=\"&rsaquo;\" first-text=\"&laquo;\" last-text=\"&raquo;\" ng-change=\"doSearch()\"></ul>\r\n    </div>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <button class=\"btn btn-primary\" ng-click=\"ok()\">确定</button>\r\n</div>"

/***/ }),

/***/ 26:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('round.ui.enroll', []);
ngMod.factory('enlRound', ['http2', '$q', '$uibModal', 'tmsLocation', function(http2, $q, $uibModal, LS) {
    var Round;
    Round = function(oApp) {
        this.app = oApp;
        this.page = {};
    };
    Round.prototype.get = function(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve({ rid: 'ALL', title: '全部轮次' });
        } else {
            http2.get(LS.j('round/get', 'site', 'app') + '&rid=' + aRids).then(function(rsp) {
                defer.resolve(rsp.data);
            });
        }

        return defer.promise;
    };
    Round.prototype.list = function() {
        var deferred = $q.defer();
        http2.get(LS.j('round/list', 'site', 'app'), { page: this.page }).then(function(rsp) {
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    Round.prototype.getRoundTitle = function(aRids) {
        var defer;
        defer = $q.defer();
        if (aRids.indexOf('ALL') !== -1) {
            defer.resolve('全部轮次');
        } else {
            var titles;
            http2.get(LS.j('round/get', 'site', 'app') + '&rid=' + aRids).then(function(rsp) {
                if (rsp.data.length === 1) {
                    titles = rsp.data[0].title;
                } else if (rsp.data.length === 2) {
                    titles = rsp.data[0].title + ',' + rsp.data[1].title;
                } else if (rsp.data.length > 2) {
                    titles = rsp.data[0].title + '-' + rsp.data[rsp.data.length - 1].title;
                }
                defer.resolve(titles);
            });
        }

        return defer.promise;
    };
    Round.prototype.pick = function(aCheckedRounds, oOptions) {
        var _self = this;
        return $uibModal.open({
            template: __webpack_require__(25),
            backdrop: 'static',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var oCheckedRounds;
                $scope2.pageOfRound = _self.page;
                $scope2.checkedRounds = oCheckedRounds = {};
                $scope2.countOfChecked = 0;
                $scope2.options = {};
                if (oOptions) angular.extend($scope2.options, oOptions);
                $scope2.toggleCheckedRound = function(rid) {
                    if (rid === 'ALL') {
                        if (oCheckedRounds.ALL) {
                            $scope2.checkedRounds = oCheckedRounds = { ALL: true };
                        } else {
                            $scope2.checkedRounds = oCheckedRounds = {};
                        }
                    } else {
                        if (oCheckedRounds[rid]) {
                            delete oCheckedRounds.ALL;
                        } else {
                            delete oCheckedRounds[rid];
                        }
                    }
                    $scope2.countOfChecked = Object.keys(oCheckedRounds).length;
                };
                $scope2.clean = function() {
                    $scope2.checkedRounds = oCheckedRounds = {};
                };
                $scope2.ok = function() {
                    var checkedRoundIds = [];
                    if (Object.keys(oCheckedRounds).length) {
                        angular.forEach(oCheckedRounds, function(v, k) {
                            if (v) {
                                checkedRoundIds.push(k);
                            }
                        });
                    }
                    _self.getRoundTitle(checkedRoundIds).then(function(titles) {
                        $mi.close({ ids: checkedRoundIds, titles: titles });
                    });
                };
                $scope2.cancel = function() {
                    $mi.dismiss('cancel');
                };
                $scope2.doSearch = function() {
                    _self.list().then(function(result) {
                        $scope2.activeRound = result.active;
                        if ($scope2.activeRound) {
                            var otherRounds = [];
                            result.rounds.forEach(function(oRound) {
                                if (oRound.rid !== $scope2.activeRound.rid) {
                                    otherRounds.push(oRound);
                                }
                            });
                            $scope2.rounds = otherRounds;
                        } else {
                            $scope2.rounds = result.rounds;
                        }

                    });
                };
                if (angular.isArray(aCheckedRounds)) {
                    if (aCheckedRounds.length) {
                        aCheckedRounds.forEach(function(rid) {
                            oCheckedRounds[rid] = true;;
                        });
                    }
                }
                $scope2.countOfChecked = Object.keys(oCheckedRounds).length;
                $scope2.doSearch();
            }]
        }).result;
    };

    return Round;
}]);

/***/ }),

/***/ 27:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('task.ui.enroll', []);
ngMod.factory('enlTask', ['http2', '$q', '$parse', '$filter', '$uibModal', 'tmsLocation', function (http2, $q, $parse, $filter, $uibModal, LS) {
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
                strs.push('，完成' + limit + '投票。');
                break;
            case 'score':
                strs.push('，完成打分。');
                break;
        }
        return strs.join('');
    }

    function fnTaskTimeFormat() {
        var oTask = this,
            strs = {},
            str, weekday, oDateFilter;

        oDateFilter = $filter('date');
        if (oTask.start_at) {
            str = oDateFilter(oTask.start_at * 1000, 'M月d日(EEE)H:mm');
            weekday = oDateFilter(oTask.start_at * 1000, 'EEE');
            str = str.replace(weekday, i18n.weekday[weekday]);
            strs.start_at = str;
        } else {
            strs.start_at = 0;
        }

        if (oTask.end_at) {
            str = oDateFilter(oTask.end_at * 1000, 'M月d日(EEE)H:mm');
            weekday = oDateFilter(oTask.end_at * 1000, 'EEE');
            str = str.replace(weekday, i18n.weekday[weekday]);
            strs.end_at = str;
        } else {
            strs.end_at = 0;
        }

        return strs;
    }
    var Task;
    Task = function (oApp) {
        this.app = oApp;
    };
    Task.prototype.list = function (type, state, rid, ek) {
        var deferred, url;
        deferred = $q.defer();
        url = LS.j('task/list', 'site', 'app');
        if (type) url += '&type=' + type;
        if (state) url += '&state=' + state;
        if (rid) url += '&rid=' + rid;
        if (ek) url += '&ek=' + ek;
        http2.get(url).then(function (rsp) {
            if (rsp.data && rsp.data.length) {
                rsp.data.forEach(function (oTask) {
                    oTask.toString = fnTaskToString;
                    oTask.timeFormat = fnTaskTimeFormat;
                });
            }
            deferred.resolve(rsp.data);
        });
        return deferred.promise;
    };
    Task.prototype.enhance = function (oTask) {
        if (oTask) {
            oTask.toString = fnTaskToString;
            oTask.timeFormat = fnTaskTimeFormat;
        }
        return oTask;
    };

    return Task;
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
		if (/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/|\s*$)/i.test(unquotedOrigUrl)) {
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
/***/ (function(module, exports) {

module.exports = "<nav class=\"navbar site-navbar-light navbar-fixed-bottom\">\r\n    <div class=\"container\">\r\n        <ul class='nav site-nav'>\r\n            <li ng-repeat=\"nav in navs\" ng-class=\"{'active': activeNav.type===nav.type}\" ng-switch on=\"nav.type\" ng-click=\"switchNav($event, nav)\">\r\n                <a href ng-switch-when=\"mission\">\r\n                    <i class=\"glyphicon glyphicon-th-list\"></i><span ng-bind=\"nav.title\"></span>\r\n                </a>\r\n                <a href ng-switch-when=\"repos\">\r\n                    <i class=\"glyphicon glyphicon-home\"></i><span ng-bind=\"nav.title\"></span>\r\n                </a>\r\n                <a href ng-switch-when=\"activities\">\r\n                    <i class=\"glyphicon glyphicon-tasks\"></i><span ng-bind=\"nav.title\"></span>\r\n                </a>\r\n                <a href ng-switch-when=\"summary\">\r\n                    <i class=\"glyphicon glyphicon-stats\"></i><span ng-bind=\"nav.title\"></span>\r\n                </a>\r\n                <a href ng-switch-when=\"people\">\r\n                    <i class=\"glyphicon glyphicon-user\"></i><span ng-bind=\"nav.title\"></span>\r\n                </a>\r\n            </li>\r\n        </ul>\r\n    </div>\r\n</nav>"

/***/ }),

/***/ 37:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('nav.bottom.ui', []);
ngMod.directive('tmsBottomNav', ['$templateCache', function($templateCache) {
    return {
        restrict: 'E',
        replace: true,
        template: __webpack_require__(34),
        scope: {
            navs: '=',
            activeNav: '=',
            type: '@'
        },
        link: function(scope, elems, attrs) {
            scope.switchNav = function($event, nav) {
                location.href = nav.url;
            };
            scope.$watch('navs', function(navs) {
                if (!navs) { return false; }
                navs.forEach(function(nav) {
                    if (nav.type === scope.type) {
                        scope.activeNav = nav;
                    }
                });
            });
        }
    };
}]);

/***/ }),

/***/ 4:
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
             } else if (fnOther && typeof fnOther === 'function') {
                 fnOther(title, link, desc, img);
                 _isReady = true;
             }
         }
     };
 }]);

/***/ }),

/***/ 6:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('notice.ui.xxt', ['ng', 'ngSanitize']);
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

/***/ 66:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(121);
__webpack_require__(37);
__webpack_require__(26);
__webpack_require__(27);

window.moduleAngularModules = ['nav.bottom.ui', 'round.ui.enroll', 'task.ui.enroll', 'ngRoute'];

var ngApp = __webpack_require__(18);
ngApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider
        .when('/rest/site/fe/matter/enroll/activities/kanban', { template: __webpack_require__(110), controller: 'ctrlActivitiesKanban' })
        .when('/rest/site/fe/matter/enroll/activities/event', { template: __webpack_require__(109), controller: 'ctrlActivitiesEvent' })
        .otherwise({ template: __webpack_require__(111), controller: 'ctrlActivitiesTask' });
}]);
ngApp.filter('filterTime', function() {
    return function(e) {
        var result, h, m, s, time = e * 1;
        h = Math.floor(time / 3600);
        m = Math.floor((time / 60 % 60));
        s = Math.floor((time % 60));
        return result = h + ":" + m + ":" + s;
    }
});
ngApp.controller('ctrlActivities', ['$scope', '$location', 'tmsLocation', 'http2', function($scope, $location, LS, http2) {
    $scope.activeNav = '';
    $scope.viewTo = function(event, subView) {
        $scope.activeView = subView;
        var url = '/rest/site/fe/matter/enroll/activities/' + subView.type;
        LS.path(url);
    };
    $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)\?/);
        $scope.subView = subView[1] === 'task' ? 'task' : subView[1];
    });
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        /* 请求导航 */
        http2.get(LS.j('navs', 'site', 'app')).then(function(rsp) {
            $scope.navs = rsp.data;
        });
    });
}]);
ngApp.controller('ctrlActivitiesTask', ['$scope', '$parse', '$q', '$uibModal', 'http2', 'tmsLocation', 'noticebox', 'enlRound', 'enlTask', function($scope, $parse, $q, $uibModal, http2, LS, noticebox, enlRound, enlTask) {
    function fnGetTasks(oRound) {
        _tasks.splice(0, _tasks.length);
        _enlTask.list(null, null, oRound.rid).then(function(roundTasks) {
            if (roundTasks.length) {
                roundTasks.forEach(function(oTask) {
                    _tasks.push(oTask);
                });
            }
        });
    }
    var _oApp, _tasks, _enlTask;
    $scope.tasks = _tasks = [];
    $scope.Label = { task: { state: { 'IP': '进行中', 'BS': '未开始', 'AE': '已结束' } } };
    $scope.shiftRound = function(oRound) {
        $scope.selectedRound = oRound;
        fnGetTasks(oRound);
    };
    $scope.gotoTask = function(oTask) {
        if (oTask) {
            if (oTask.type === 'baseline') {
                location.href = LS.j('', 'site', 'app') + '&rid=' + oTask.rid + '&page=enroll';
            } else if (oTask.topic && oTask.topic.id) {
                location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.topic.id + '&page=topic';
            }
        }
    };
    
    $scope.$watch('app', function(oApp) {
        if (!oApp) { return; }
        _oApp = oApp;
        _enlTask = new enlTask(_oApp);
        var facRound = new enlRound(_oApp);
        facRound.list().then(function(oResult) {
            $scope.rounds = oResult.rounds;
            if ($scope.rounds.length) $scope.shiftRound($scope.rounds[0]);
        });
    });
}]);
ngApp.controller('ctrlActivitiesEvent', ['$scope', '$q', 'http2', 'tmsLocation', function($scope, $q, http2, LS) {
    function fnCloseNotice(oNotice) {
        var url, defer;
        defer = $q.defer();
        url = LS.j('notice/close', 'site', 'app');
        url += '&notice=' + oNotice.id;
        http2.get(url).then(function(rsp) {
            $scope.notices.splice($scope.notices.indexOf(oNotice), 1);
            defer.resolve();
        });
        return defer.promise;
    }

    var _oApp, _aLogs, _oPage, _oFilter;
    $scope.page = _oPage = { size: 30 };
    $scope.subView = 'timeline.html';
    $scope.filter = _oFilter = { scope: 'N' };
    $scope.searchEvent = function(pageAt) {
        var url, defer;
        pageAt && (_oPage.at = pageAt);
        defer = $q.defer();
        url = LS.j('event/timeline', 'site', 'app');
        url += '&scope=' + _oFilter.scope;
        http2.get(url, { page: _oPage }).then(function(rsp) {
            $scope.logs = _aLogs = rsp.data.logs;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    $scope.searchNotice = function(pageAt) {
        var url, defer;
        pageAt && (_oPage.at = pageAt);
        defer = $q.defer();
        url = LS.j('notice/list', 'site', 'app');
        http2.get(url, { page: _oPage }).then(function(rsp) {
            $scope.notices = rsp.data.notices;
            defer.resolve(rsp.data);
        });
        return defer.promise;
    };
    $scope.closeNotice = function(oNotice, bGotoCowork) {
        fnCloseNotice(oNotice).then(function() {
            if (bGotoCowork) {
                $scope.gotoCowork(oNotice.enroll_key);
            }
        });
    };
    $scope.gotoCowork = function(ek) {
        var url;
        if (ek) {
            url = LS.j('', 'site', 'app');
            url += '&ek=' + ek;
            url += '&page=cowork';
            location.href = url;
        }
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) { return; }
        _oApp = oApp;
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
        }
        $scope.$watch('filter', function(nv, ov) {
            if (nv) {
                if (/N/.test(nv.scope)) {
                    $scope.subView = 'timeline.html';
                    $scope.searchNotice(1);
                } else {
                    $scope.subView = 'timeline.html';
                    $scope.searchEvent(1);
                }
            }
        }, true);
    });
}]);
ngApp.controller('ctrlActivitiesKanban', ['$scope', '$parse', '$q', '$uibModal', 'http2', 'tmsLocation', 'enlRound', function($scope, $parse, $q, $uibModal, http2, LS, enlRound) {
    function fnGetKanban() {
        var url, defer;
        defer = $q.defer();
        url = LS.j('user/kanban', 'site', 'app');
        url += '&rid=' + _oFilter.round.rid;
        _oFilter.group && (url += '&gid=' + _oFilter.group.team_id);
        http2.get(url).then(function(rsp) {
            var oUndoneByUserid = {};
            if (rsp.data.users && rsp.data.users.length) {
                if (rsp.data.undone && rsp.data.undone.length) {
                    rsp.data.undone.forEach(function(oUndone) {
                        oUndoneByUserid[oUndone.userid] = oUndone;
                    });
                }
                rsp.data.users.forEach(function(oUser) {
                    if (oUndoneByUserid[oUser.userid]) {
                        if (oUndoneByUserid[oUser.userid].tasks) {
                            oUser.undone = oUndoneByUserid[oUser.userid].tasks;
                        }
                        delete oUndoneByUserid[oUser.userid];
                    }
                });
            }
            $scope.kanban.stat = rsp.data.stat;
            $scope.kanban.users = rsp.data.users;
            $scope.kanban.undone = rsp.data.undone;

            defer.resolve($scope.kanban);
        });
        return defer.promise;
    }
    var _oApp, _oCriteria, _oFilter;
    $scope.criteria = _oCriteria = {};
    $scope.filter = _oFilter = {};
    $scope.subView = location.hash === '#undone' ? 'undone' : 'users';
    $scope.kanban = {};
    $scope.shiftRound = function(oRound) {
        _oFilter.round = oRound;
        fnGetKanban().then(function() {
            $scope.shiftOrderby();
        });
    };
    $scope.shiftUserGroup = function(oUserGroup) {
        _oFilter.group = oUserGroup;
        fnGetKanban().then(function() {
            $scope.shiftOrderby();
        });
    };
    $scope.shiftOrderby = function(orderby) {
        if (orderby) {
            _oCriteria.orderby = orderby;
        } else {
            orderby = _oCriteria.orderby;
        }
        $scope.kanban.users.sort(function(a, b) {
            return a[orderby].pos - b[orderby].pos;
        });
    };
    $scope.viewDetail = function(oUser) {
        $uibModal.open({
            templateUrl: 'userDetail.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.app = $scope.app;
                $scope2.user = oUser;
                $scope2.cancel = function() { $mi.dismiss(); };
            }],
            backdrop: 'static',
            windowClass: 'auto-height'
        });
    };
    $scope.toggleProfilePublic = function(event, oEnlUser) {
        event.stopPropagation();
        var bPublic;
        bPublic = $parse('custom.profile.public')(oEnlUser) === true ? false : true;
        http2.post(LS.j('user/updateCustom', 'site', 'app'), { profile: { public: bPublic } }).then(function() {
            if (bPublic) {
                http2.get(LS.j('user/get', 'site', 'app') + '&rid=' + _oFilter.round.rid).then(function(rsp) {
                    oEnlUser.nickname = rsp.data.nickname;
                });
            } else {
                oEnlUser.nickname = '隐身';
            }
            $parse('custom.profile.public').assign(oEnlUser, bPublic);
        });
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) { return; }
        _oApp = oApp;
        _oFilter.round = _oApp.appRound;
        (new enlRound(_oApp)).list().then(function(result) {
            $scope.rounds = result.rounds;
        });
        fnGetKanban().then(function() {
            $scope.shiftOrderby('score');
        });
    });
}]);

/***/ }),

/***/ 7:
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

/***/ 8:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(false);
// Module
exports.push([module.i, ".dialog.mask{position:fixed;background:rgba(0,0,0,.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:1060}.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}.dialog .dlg-header{padding:15px 15px 0 15px}.dialog .dlg-body{padding:15px 15px 0 15px}.dialog .dlg-footer{text-align:right;padding:15px}.dialog .dlg-footer button{border-radius:0}div[wrap=filter] .detail{background:#ccc}div[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}div[wrap=filter] .detail .actions .btn{border-radius:0}.tms-act-toggle{position:fixed;right:15px;bottom:8px;width:48px;height:48px;line-height:48px;box-shadow:0 2px 6px rgba(18,27,32,.425);color:#fff;background:#ff8018;border:1px solid #ff8018;border-radius:24px;font-size:20px;text-align:center;cursor:pointer;z-index:1045}.tms-nav-target>*+*{margin-top:.5em}.tms-act-popover-wrap>div+div{margin-top:8px}#frmPlugin{position:absolute;top:0;bottom:0;left:0;right:0;width:100%;height:100%;border:none;z-index:1060;box-sizing:border-box;padding-bottom:48px;background:#fff}#frmPlugin iframe{width:100%;height:100%;border:0}#frmPlugin:after{content:'关闭';position:absolute;width:100px;text-align:center;left:50%;margin-left:-50px;bottom:4px;padding:5px 6px 3px;border:1px solid #ccc;border-radius:4px}div[wrap]>.description{word-wrap:break-word}", ""]);



/***/ }),

/***/ 9:
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(8);

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(1)(content, options);

if(content.locals) module.exports = content.locals;

if(false) {
	module.hot.accept("!!../../../../../../node_modules/css-loader/dist/cjs.js!./directive.css", function() {
		var newContent = require("!!../../../../../../node_modules/css-loader/dist/cjs.js!./directive.css");

		if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];

		var locals = (function(a, b) {
			var key, idx = 0;

			for(key in a) {
				if(!b || a[key] !== b[key]) return false;
				idx++;
			}

			for(key in b) idx--;

			return idx === 0;
		}(content.locals, newContent.locals));

		if(!locals) throw new Error('Aborting CSS HMR due to changed css-modules locals.');

		update(newContent);
	});

	module.hot.dispose(function() { update(); });
}

/***/ }),

/***/ 90:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(false);
// Module
exports.push([module.i, ".site-navbar-light.navbar{height:50px;padding-top:8px;padding-bottom:8px}.site-navbar-light{background-color:#fff;border-color:#fff}.site-navbar-light .site-nav{display:flex;line-height:1}.site-navbar-light .site-nav>li{flex-grow:1}.site-navbar-light .site-nav>li>a{text-align:center;letter-spacing:2px;color:#333;padding:0}.site-navbar-light .site-nav>li>a:focus,.site-navbar-light .site-nav>li>a:hover{background-color:#fff}.site-navbar-light .site-nav>li>a>i{display:block;height:18px;margin-bottom:4px;font-size:18px;top:0;left:-1px}.site-navbar-light .site-nav>li>a>span{display:block;font-size:12px}.site-navbar-light .site-nav>li.active>a{color:#ff8018}.site-navbar-orange.nav{height:44px;padding-top:12px}.site-navbar-orange{background-color:#ff8018;border-color:#ff8018}.site-navbar-orange .col-md-12.col-xs-12{width:100%;overflow:hidden;overflow-x:auto}.site-navbar-orange .col-md-12.col-xs-12::-webkit-scrollbar{display:none}.site-navbar-orange .col-md-12.col-xs-12 .site-nav{margin-right:-15px;white-space:nowrap;font-size:14px;line-height:1}.site-navbar-orange .col-md-12.col-xs-12 .site-nav>li{display:inline-block;margin-right:10%}.site-navbar-orange .col-md-12.col-xs-12 .site-nav>li>a{height:14px;color:#ffdcb7;letter-spacing:2px;padding:0}.site-navbar-orange .col-md-12.col-xs-12 .site-nav>li>a:focus,.site-navbar-orange .col-md-12.col-xs-12 .site-nav>li>a:hover{background-color:#ff8018}.site-navbar-orange .col-md-12.col-xs-12 .site-nav>li>span{display:none;width:60%;height:2px;background-color:#fff;margin:auto;margin-top:4px}.site-navbar-orange .col-md-12.col-xs-12 .site-nav>li.active>a{color:#fff;font-weight:600}.site-navbar-orange .col-md-12.col-xs-12 .site-nav>li.active>span{display:block}.app{padding:54px 0 60px 0}#tasks .tasks .state-IP{background-color:#FFDF25}#event .notice{position:relative}#event .notice .seq{position:absolute;top:-.8em;left:-.5em}#event .notice .seq.label{padding-bottom:.2em;font-weight:400;border-radius:.5em}#event .notice .event>*+*{margin-top:.5em}#event .notice .event .event-action,#event .notice .event .event-user{font-size:.9em}#event .notice .event .event-action a{display:inline-block;color:#777}#event .notice .event .event-action>*+*{margin-left:1em}#event .action{position:relative}#event .action .seq{position:absolute;top:-.8em;left:-.5em}#event .action .seq.label{padding-bottom:.2em;font-weight:400;border-radius:.5em}#event .action .event>*+*{margin-top:.5em}#event .action .footer{display:flex;margin-top:.5em;font-size:.9em}#event .action .footer .datetime{flex:1}#kanbanView #kanban{overflow-x:auto}#kanbanView #kanban .wrap{min-width:640px}#kanbanView #kanban .user{display:flex}#kanbanView #kanban .user>div:first-child{width:6em}#kanbanView #kanban .who>div{margin-bottom:.5em}#kanbanView #kanban .data{flex-grow:1;display:flex;flex-direction:column}#kanbanView #kanban .data>div{display:flex;order:5}#kanbanView #kanban .data>div>div:first-child{width:6em}#kanbanView #kanban .data>div>div:last-child{flex:1;display:flex}#kanbanView #kanban .data>div>div:last-child>div{flex:1}#kanbanView #kanban .data>div{margin-bottom:.5em}#kanbanView #kanban .data .pos{display:inline-block;padding:0 .25em;border-radius:.5em;border:1px solid #ddd}#kanbanView #kanban .data .ordered{color:red;order:1}#kanbanView #kanban .data .ordered>div:nth-child(2)>div:first-child>span{border-color:red}#kanbanView .table-responsive{overflow-x:auto;min-height:.01%;width:100%;margin-bottom:15px;overflow-y:hidden;-ms-overflow-style:-ms-autohiding-scrollbar;border:1px solid #ddd;background-color:#fff}#kanbanView .table-responsive>.table{margin-bottom:0}#kanbanView .table-responsive>.table>tbody>tr>td,#kanbanView .table-responsive>.table>tbody>tr>th,#kanbanView .table-responsive>.table>tfoot>tr>td,#kanbanView .table-responsive>.table>tfoot>tr>th,#kanbanView .table-responsive>.table>thead>tr>td,#kanbanView .table-responsive>.table>thead>tr>th{white-space:nowrap}#kanbanView .table-responsive>.table-bordered{border:0}#kanbanView .table-responsive>.table-bordered>tbody>tr>td:first-child,#kanbanView .table-responsive>.table-bordered>tbody>tr>th:first-child,#kanbanView .table-responsive>.table-bordered>tfoot>tr>td:first-child,#kanbanView .table-responsive>.table-bordered>tfoot>tr>th:first-child,#kanbanView .table-responsive>.table-bordered>thead>tr>td:first-child,#kanbanView .table-responsive>.table-bordered>thead>tr>th:first-child{border-left:0}#kanbanView .table-responsive>.table-bordered>tbody>tr>td:last-child,#kanbanView .table-responsive>.table-bordered>tbody>tr>th:last-child,#kanbanView .table-responsive>.table-bordered>tfoot>tr>td:last-child,#kanbanView .table-responsive>.table-bordered>tfoot>tr>th:last-child,#kanbanView .table-responsive>.table-bordered>thead>tr>td:last-child,#kanbanView .table-responsive>.table-bordered>thead>tr>th:last-child{border-right:0}#kanbanView .table-responsive>.table-bordered>tbody>tr:last-child>td,#kanbanView .table-responsive>.table-bordered>tbody>tr:last-child>th,#kanbanView .table-responsive>.table-bordered>tfoot>tr:last-child>td,#kanbanView .table-responsive>.table-bordered>tfoot>tr:last-child>th{border-bottom:0}#kanbanView tr.undone td:nth-child(2){color:red}#kanbanView td.undone{color:red}#kanbanView #user-detail>div{display:flex}#kanbanView #user-detail>div>div{padding:.25em 0}#kanbanView #user-detail>div>div:first-child{width:12em}#kanbanView #user-detail>div>div:nth-child(2){flex:1}", ""]);



/***/ })

/******/ });