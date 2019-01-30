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
/******/ 	return __webpack_require__(__webpack_require__.s = 143);
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

/***/ 103:
/***/ (function(module, exports) {

module.exports = "<style>\r\n#scoreApp ul {\r\n    margin: 0;\r\n    padding: 0;\r\n    list-style: none\r\n}\r\n\r\n#scoreApp li {\r\n    margin: 0;\r\n    padding: 0\r\n}\r\n\r\n/* score schema */\r\n#scoreApp li[wrap=score] {\r\n    padding: 4px 4px 4px 0;\r\n}\r\n\r\n#scoreApp li[wrap=score] label {\r\n    padding: 3px;\r\n    font-weight: 400;\r\n}\r\n\r\n#scoreApp li[wrap=score]>.number {\r\n    display: inline-block;\r\n    margin-top: 6px;\r\n    border: 1px solid #CCC;\r\n}\r\n\r\n#scoreApp li[wrap=score]>.number>div {\r\n    display: inline-block;\r\n    width: 48px;\r\n    padding: 4px 4px;\r\n    margin: 4px 4px;\r\n    text-align: center;\r\n    border-bottom: 1px dotted #ddd\r\n}\r\n\r\n#scoreApp li[wrap=score]>.number>.in {\r\n    background: #33bb99;\r\n}\r\n</style>\r\n<div id='scoreApp' class=\"modal-body\">\r\n    <div wrap=\"input\" class=\"form-group form-group-lg\" ng-repeat=\"schema in schemas\" schema=\"{{schema.id}}\" schema-type=\"score\"><label>{{schema.title}}</label>\r\n        <ul>\r\n            <li class=\"score\" wrap=\"score\" ng-repeat=\"op in schema.ops\" opvalue=\"{{op.v}}\">\r\n                <div><label>{{op.l}}</label></div>\r\n                <div class=\"number\">\r\n                    <div ng-class=\"{'in':lessScore(schema,$index,1)}\" ng-click=\"score(schema,$index,1)\">1</div>\r\n                    <div ng-class=\"{'in':lessScore(schema,$index,2)}\" ng-click=\"score(schema,$index,2)\">2</div>\r\n                    <div ng-class=\"{'in':lessScore(schema,$index,3)}\" ng-click=\"score(schema,$index,3)\">3</div>\r\n                    <div ng-class=\"{'in':lessScore(schema,$index,4)}\" ng-click=\"score(schema,$index,4)\">4</div>\r\n                    <div ng-class=\"{'in':lessScore(schema,$index,5)}\" ng-click=\"score(schema,$index,5)\">5</div>\r\n                </div>\r\n            </li>\r\n        </ul>\r\n    </div>\r\n    <button class='btn btn-default' ng-click=\"submit()\">提交</button>\r\n</div>"

/***/ }),

/***/ 11:
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

/***/ 123:
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var ngMod = angular.module('score.ui.enroll', []);
ngMod.directive('tmsScoreRecordData', [function() {
    return {
        restrict: 'A',
        template: __webpack_require__(103),
        scope: {
            task: '='
        },
        controller: ['$scope', '$parse', 'tmsLocation', 'http2', 'noticebox', function($scope, $parse, LS, http2, noticebox) {
            var _oData, _oScoreRecord;
            $scope.data = _oData = {};
            $scope.score = function(oSchema, opIndex, number) {
                var oOption;

                if (!(oOption = oSchema.ops[opIndex])) return;

                if (_oData[oSchema.id] === undefined) {
                    _oData[oSchema.id] = {};
                    oSchema.ops.forEach(function(oOp) {
                        _oData[oSchema.id][oOp.v] = 0;
                    });
                }
                _oData[oSchema.id][oOption.v] = number;
            };
            $scope.lessScore = function(oSchema, opIndex, number) {
                var oOption;

                if (!(oOption = oSchema.ops[opIndex])) return false;
                if (_oData[oSchema.id] === undefined) {
                    return false;
                }
                return _oData[oSchema.id][oOption.v] >= number;
            };
            $scope.submit = function() {
                var url;
                url = LS.j('record/submit', 'site') + '&app=' + _oScoreApp.id + '&task=' + $scope.task.id;
                if (_oScoreRecord)
                    url += '&ek=' + _oScoreRecord.enroll_key;
                http2.post(url, { data: _oData }, { autoBreak: false }).then(function(rsp) {
                    http2.post(LS.j('marks/renewReferScore', 'site') + '&app=' + _oScoreApp.id, {}).then(function() {
                        noticebox.success('提交成功！');
                    });
                });
            };
            var _oScoreApp;
            _oScoreApp = $scope.task.scoreApp;
            http2.get(LS.j('get', 'site') + '&app=' + _oScoreApp.id + '&task=' + $scope.task.id).then(function(rsp) {
                _oScoreApp = rsp.data.app;
                $scope.schemas = _oScoreApp.dynaDataSchemas;
                http2.get(LS.j('record/get', 'site') + '&app=' + _oScoreApp.id + '&task=' + $scope.task.id).then(function(rsp) {
                    if (rsp.data.enroll_key) {
                        _oScoreRecord = rsp.data;
                        http2.merge(_oData, _oScoreRecord.data);
                    }
                });
            });
        }]
    };
}]);

/***/ }),

/***/ 13:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "html,body{font-family:Microsoft Yahei,Arial;width:100%;height:100%;}\r\nbody{position:relative;font-size:16px;padding:0;}\r\nheader img,footer img{max-width:100%}\r\n.ng-cloak{display:none;}\r\n.container{position:relative;}\r\n.site-navbar-default .navbar-default .navbar-nav>li>a,.navbar-default .navbar-brand{color:#fff;}\r\n.site-navbar-default .navbar-brand{padding:15px 15px;}\r\n.main-navbar .navbar-brand:hover{color:#fff;}\r\n@media screen and (min-width:768px){\r\n\t.site-navbar-default .navbar-nav>li>a{padding:15px 15px;line-height:1;}\r\n}\r\n@media screen and (max-width:768px){\r\n\t.site-navbar-default .navbar-brand{width:100%;text-align:center;}\r\n\t.site-navbar-default .navbar-brand>.icon-note{display:inline-block;width:124px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}\r\n\t.site-navbar-default .navbar-nav{margin:8px 0;position:absolute;top:0;right:0;}\r\n\t.site-navbar-default .nav>li>a{padding:10px 10px;}\r\n}\r\n.tms-flex-row{display:flex;align-items:center;}\r\n.tms-flex-row .tms-flex-grow{flex:1;}\r\n.dropdown-menu{min-width:auto;}\r\n.dropdown-menu-top{bottom:100%;top:auto;}\r\n\r\n/*picviewer*/\r\n#previewImage-container{-ms-touch-action:none;touch-action:none;-webkit-touch-action:none;line-height:100vh;background-color:#000;width:100vw;height:100vh;position:fixed;overflow:hidden;top:0;left:0;z-index:1050;transition:transform .3s;-ms-transition:transform .3s;-moz-transition:transform .3s;-webkit-transition:transform .3s;-o-transition:transform .3s;transform:translate3d(100%,0,0);-webkit-transform:translate3d(100%,0,0);-ms-transform:translate3d(100%,0,0);-o-transform:translate3d(100%,0,0);-moz-transform:translate3d(100%,0,0)}\r\n#previewImage-container .previewImage-text{position:absolute;bottom:5px;left:8px;right:8px;z-index:1060;height:36px}\r\n.previewImage-text span{display:inline-block;width:36px;height:36px;line-height:25px;border-radius:18px;font-size:25px;text-align:center;color:#bbb}\r\n.previewImage-text span.page{position:absolute;left:50%;margin-left:-18px;font-size:18px}\r\n.previewImage-text span.prev{position:absolute;left:50%;margin-left:-72px}\r\n.previewImage-text span.next{position:absolute;left:50%;margin-left:36px}\r\n.previewImage-text span.exit{position:absolute;right:0}\r\n.previewImage-text span.exit>i{text-shadow:0 0 .1em #fff,-0 -0 .1em #fff}\r\n#previewImage-container .previewImage-box{width:999999rem;height:100vh}\r\n#previewImage-container .previewImage-box .previewImage-item{width:100vw;height:100vh;margin-right:15px;float:left;text-align:center}\r\n@media screen and (min-width:992px){\r\n\t#previewImage-container .previewImage-box .previewImage-item .previewImage-image{display:block;}\r\n}\r\n@media screen and (max-width:992px){\r\n\t#previewImage-container .previewImage-box .previewImage-item .previewImage-image{width:100%}\r\n}\r\n", ""]);

// exports


/***/ }),

/***/ 14:
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

/***/ 143:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(77);


/***/ }),

/***/ 15:
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

/***/ 16:
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

/***/ 17:
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

/***/ 18:
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

/***/ 27:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "img{max-width:100%}hr{margin:12px 0 12px}p{word-break:break-all}blockquote{font-size:16px;margin-bottom:8px}button.option{padding:0}.navbar-header .page-title{display:inline-block}.navbar-header .page-title .notice-count{display:inline-block;background:red;color:#fff;line-height:14px;min-width:14px;border-radius:7px;font-size:8px;vertical-align:middle;margin-left:4px}.navbar-header .page-title .caret{margin-left:4px}.tms-nav-target .btn .notice-count{background:red;color:#fff;line-height:14px;min-width:14px;border-radius:7px;font-size:8px;vertical-align:middle;margin-left:4px}body.enroll-repos{display:flex;flex-direction:column}body.enroll-repos .app{flex-grow:1;display:flex;flex-direction:column}body.enroll-repos .app .row{flex-grow:1;display:flex}body.enroll-repos .app .row .main{flex-grow:1;display:flex}body.enroll-repos .app .row .main .wrap{flex-grow:1;display:flex;flex-direction:column}body.enroll-repos .app .row .main .wrap #repos{flex-grow:1;display:flex;flex-direction:column;overflow-y:auto}#advCriteria{width:200px;height:100%;padding:0;cursor:pointer;z-index:3}#advCriteria .tree{border-radius:3px;margin-bottom:8px;border:1px solid #d3d3d3}#advCriteria .tree .notClick{pointer-events:none;opacity:.5}#advCriteria .tree .tree-header{height:28px;padding:6px 10px;line-height:26px;font-weight:700;font-size:16px;background-color:#f1f1f1;border-bottom:1px solid #d3d3d3;box-sizing:content-box}#advCriteria .tree .tree-body{width:100%;height:45vh;color:#000;background-color:#fff;position:relative}#advCriteria .tree .tree-body *{box-sizing:content-box}#advCriteria .tree .tree-body .tree-wrap{height:100%;overflow:hidden}#advCriteria .tree .tree-body .tree-wrap .tree-inner{margin-right:-25px;padding-right:25px;overflow-y:auto;height:100%}#advCriteria .tree .tree-body .tree-wrap .item{height:26px;line-height:26px;font-size:16px;padding:6px 10px}#advCriteria .tree .tree-body .tree-wrap .item .item-label{width:90%;height:100%;float:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}#advCriteria .tree .tree-body .tree-wrap .item .item-icon{float:right;margin-top:4px}#advCriteria .tree .tree-body .tree-wrap .item.active{color:#ff8018}#advCriteria .tree .tree-body .tree-wrap .item-children{position:absolute;top:-1px;left:100%;width:200px;height:100%;background-color:#fff;border:1px solid #d3d3d3}#advCriteria .tree .tree-body .tree-wrap .tree-bottom{width:100%;text-align:center;position:absolute;bottom:0;background:#f1f1f1}#filterQuick{margin-bottom:3px;position:relative;background-color:#fff}#filterQuick>*{display:inline-block}#filterQuick.uib-dropdown-open>.tmsFilter .glyphicon-menu-up{transform:rotate(0)}#filterQuick .site-dropdown-list{width:100%!important;right:0!important;left:auto!important;background-color:#f5f5f5;padding:0}#filterQuick .site-dropdown-list .dropdown-search{position:relative}#filterQuick .site-dropdown-list .dropdown-search .btn{position:absolute;top:0;right:0}#filterQuick .site-dropdown-list .dropdown-list-wrapper{width:100%;height:25rem;overflow:hidden}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset{display:flex;height:100%}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset>*{overflow-y:auto;border:0 transparent}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills{width:8rem}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills a{color:#333}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills div.checked{color:#ff8018}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills div.checked:after{content:'.';color:#ff8018;position:absolute;top:0;left:5px;font-size:20px}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills li.active a,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills li.active a:focus,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .nav-pills li.active a:hover{color:#ff8018;background-color:#fff}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content{flex:1;background-color:#fff}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item{border:0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item:first-child{border-top-left-radius:0;border-top-right-radius:0}#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item.active,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item.active:focus,#filterQuick .site-dropdown-list .dropdown-list-wrapper .site-tabset .tab-content .site-list-group-item.active:hover{color:#ff8018;background-color:#fff;border-color:#fff}#filterQuick .site-dropdown-list .dropdown-list-btn{width:100%;display:flex}#filterQuick .site-dropdown-list .dropdown-list-btn button{flex:1}.site-dropdown{display:inline-block;background-color:#fff}.site-dropdown .site-dropdown-title{display:block;padding:0 10px;font-size:14px;color:#333;min-height:40px;line-height:40px}.site-dropdown .glyphicon-menu-up{transform:rotate(-180deg)}.site-dropdown.open .glyphicon-menu-up{transform:rotate(0)}.site-dropdown a:focus,.site-dropdown a:hover{text-decoration:none}.site-dropdown a.active{color:#ff8018}#filterTip{margin:4px 8px;padding:4px 0}#filterTip>*{display:inline-block;padding:4px 8px}#filterTip>* .close{margin-left:4px}#filterTip>*+*{margin-left:4px}.record,.topic{background:#fff;border-bottom:1px solid #ddd;padding:8px 16px}.record>*{margin:8px 0}.record .data blockquote .schema+.schema{margin-top:8px;padding-top:8px;border-top:1px dashed #ddd}.record .data blockquote .schema>div+div{margin-top:4px}.record .data .datetime{font-size:.8em}.record .tags>button+button{margin-left:4px}.record .remarks{font-size:.9em;border-top:1px dashed #ddd;margin-top:16px;padding:1rem 0 0 2rem;position:relative}.record .remarks:before{position:absolute;left:50%;margin-left:-2em;top:-.7em;color:#999}.record .remarks .remark .top-bar{display:flex}.record .remarks .remark .top-bar>:first-child{flex:1}.record .remarks .remark+.remark{margin-bottom:1em}.record .remarks.agreed:before{content:'\\63A8\\8350\\7559\\8A00'}.record .remarks.round:before{content:'\\8F6E\\6B21\\7559\\8A00'}.top-bar{display:flex}.top-bar .seq{margin-right:8px}.top-bar .label{padding-top:.3em}.top-bar .nickname{flex-grow:1;text-align:left;font-size:.9em}.top-bar>*+*{padding-left:8px}.data-title:before{content:' ';display:inline-block;width:12px;height:12px;border-top:6px solid #fff;border-right:6px solid #fff;border-bottom:6px solid #fff;border-left:6px solid #ff8018}.bottom-bar{display:flex;align-items:center;font-size:.9em;color:#777}.bottom-bar>*+*{margin-left:16px}.bottom-bar>*+* .like{color:#ff8018}.bottom-bar>:first-child{flex:1}.bottom-bar a{text-decoration:none;color:#777}.bottom-bar .btn-default{color:#777}.bottom-bar .dropdown button{border:0}.tag{background:#3af;padding:4px 6px;margin:4px;border-radius:2px;font-size:.8em;color:#fff}#favorGuide{position:fixed;align-items:center;z-index:1051;width:100%;bottom:0;display:flex;border:1px solid #bce8f1;background:#d9edf7;padding:8px 16px;color:#31708f}#favorGuide>:first-child{flex-grow:1}#favorGuide>:last-child{margin-left:4px}.navbar.site-navbar-tab{min-height:unset;border-bottom:0}.navbar.site-navbar-tab .navbar-nav{float:left;margin:0}.navbar.site-navbar-tab .navbar-nav>li{float:left}.navbar.site-navbar-tab .navbar-btn{margin:0}#cowork,#record,#remarks{padding:16px;background:#fff}#record .title{margin:0 -1rem .5rem -1rem;background:#ddd;padding:.5rem 1rem;border-bottom:1px solid #ccc}#record .title .dropdown-menu{right:0;left:auto;min-width:auto}#record .data blockquote>div+div{margin-top:8px}#record .assocs,#record .tags{margin-top:8px}#record .tags>button+button{margin-left:4px}#record .assocs>div{padding:8px 0}#record .assocs>div .assoc-reason{border:1px solid #ccc;border-radius:4px;margin-right:8px;padding:0 4px}#record .assocs>div .assoc-text{cursor:pointer}#cowork{position:relative;margin-top:1rem}#cowork .item{position:relative;transition:background 1s}#cowork .blink{background:#d9edf7}#cowork .assocs>div{padding:8px 0}#cowork .assocs>div .assoc-reason{border:1px solid #ccc;border-radius:4px;margin-right:8px;padding:0 4px}#cowork .assocs>div .assoc-text{cursor:pointer}#remarks{position:relative;margin-top:3rem}#remarks:before{content:'\\7559\\8A00';position:absolute;left:50%;margin-left:-1em;top:-2em;font-size:.7em;color:#eee;padding:.2em 1em;background:#666;border-radius:1em}#remarks .remarkList{background:#fff;min-height:167px;margin-bottom:30px}#remarks .remark{position:relative;background:#fff;border-bottom:1px solid #ddd;transition:background 1s;padding:8px 0}#remarks .remark:last-child{border-bottom:0}#remarks .remark>*{margin:1em 0 .2em}#remarks .blink{background:#d9edf7}#remarks .form-control{border-radius:0}.modal-edit-topic .record{padding-left:0;padding-right:0}.tms-editor{position:absolute;top:8px;bottom:8px;left:8px;right:8px;display:flex;flex-direction:column}.tms-editor>:first-child{position:relative;flex-grow:1;margin-bottom:8px;border:1px solid #ddd;border-radius:4px;overflow-y:auto}.tms-editor>:first-child iframe{display:block;width:100%;border:0}@media screen and (max-width:768px){.site-navbar-default{margin-bottom:0}#advCriteria{position:absolute;top:34px;right:0;margin-top:1px;width:300px;height:auto;background:#fff;padding:0 0 8px;border:1px solid #ccc;border-top:0;z-index:1000}#advCriteria .tree .tree-body .tree-wrap .item-children{position:static;left:0;width:100%;border:none}#advCriteria .tree .tree-body .item-2,#advCriteria .tree .tree-body .item-3,#advCriteria .tree .tree-body .item-4,#advCriteria .tree .tree-body .item-5{margin-left:1em}.app .main.col-xs-12,.app .tags.col-xs-12,.app .topics.col-xs-12{padding:0}#filterCriteria{margin-left:-1px;margin-right:-1px}#filterCriteria .form-control,#filterCriteria .input-group-btn .btn{border-radius:0}}", ""]);

// exports


/***/ }),

/***/ 28:
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

/***/ 31:
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-body\">\r\n    <div class='form-group'>\r\n        <div class='input-group'>\r\n            <input type='text' class='form-control' ng-model=\"newTag.label\">\r\n            <div class='input-group-btn'>\r\n                <button class='btn btn-default' ng-click=\"addTag()\" ng-disabled=\"!newTag.label\">创建标签</button>\r\n            </div>\r\n        </div>\r\n    </div>\r\n    <div class='list-group'>\r\n        <div class='list-group-item' ng-repeat=\"tag in tags\">\r\n            <label class='checkbox-inline'>\r\n                <input type='checkbox' ng-model=\"tag.checked\" ng-change=\"checkTag(tag)\"> <span ng-bind=\"tag.label\"></span></label>\r\n        </div>\r\n    </div>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class='text-center'>\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">取消</button>\r\n        <button class=\"btn btn-primary\" ng-click=\"ok()\">保存</button>\r\n    </div>\r\n</div>"

/***/ }),

/***/ 32:
/***/ (function(module, exports) {

module.exports = "<div class=\"modal-body\">\r\n    <div class='panel panel-default' ng-repeat=\"topic in topics\">\r\n        <div class='panel-body'>\r\n            <div class='checkbox'>\r\n                <label>\r\n                    <input type='checkbox' ng-model=\"topic.checked\" ng-change=\"checkTopic(topic)\"> <span ng-bind=\"topic.title\"></span></label>\r\n            </div>\r\n            <div class='form-group'>\r\n                <div class='small text-muted' ng-bind=\"topic.summary\"></div>\r\n            </div>\r\n            <div class='bottom-bar small text-muted'>\r\n                <div ng-bind=\"topic.create_at*1000|date:'yy-MM-dd'\"></div>\r\n                <div><i class='glyphicon glyphicon-file'></i> <span ng-bind=\"topic.rec_num\"></span></div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n</div>\r\n<div class=\"modal-footer\">\r\n    <div class='text-center'>\r\n        <button class=\"btn btn-default\" ng-click=\"cancel()\">取消</button>\r\n        <button class=\"btn btn-primary\" ng-click=\"ok()\">确定</button>\r\n    </div>\r\n</div>"

/***/ }),

/***/ 33:
/***/ (function(module, exports) {

module.exports = "<div ng-if=\"rec\">\r\n    <div ng-if=\"rec.recordDir.length\"><span class=\"glyphicon glyphicon-folder-open\"></span>&nbsp;&nbsp;<span ng-repeat=\"dir in rec.recordDir track by $index\">{{dir}}<span ng-if=\"$index!==rec.recordDir.length-1\"> / </span></span></div>\r\n    <div ng-repeat=\"schema in schemas\" class='schema' ng-class=\"{'cowork':schema.cowork==='Y'}\" ng-if=\"rec.data[schema.id]||schema.cowork==='Y'\" ng-switch on=\"schema.type\">\r\n        <div class='text-muted data-title'><span>{{::schema.title}}</span></div>\r\n        <div ng-switch-when=\"file\">\r\n            <div ng-repeat=\"file in rec.data[schema.id]\" ng-switch on=\"file.type\">\r\n                <video ng-switch-when=\"video\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </video>\r\n                <audio ng-switch-when=\"audio\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </audio>\r\n                <audio ng-switch-when=\"audio/x-m4a\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </audio>\r\n                <audio ng-switch-when=\"audio/mp3\" controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{file.url}}\" type=\"{{file.type}}\" />\r\n                </audio>\r\n                <img ng-switch-when=\"image\" ng-src='{{file.url}}' style=\"width:40%\" />\r\n                <a ng-switch-default href ng-click=\"open(file)\">{{file.name}}</a>\r\n            </div>\r\n        </div>\r\n        <div ng-switch-when=\"voice\">\r\n            <div ng-repeat=\"voice in rec.data[schema.id]\">\r\n                <audio controls=\"controls\" preload=\"none\">\r\n                    <source src=\"{{voice.url}}\" type=\"{{voice.type}}\" />\r\n                </audio>\r\n            </div>\r\n        </div>\r\n        <div ng-switch-when=\"image\">\r\n            <ul class='list-unstyled'>\r\n                <li ng-repeat=\"img in rec.data[schema.id].split(',')\"><img ng-src=\"{{img}}\" /></li>\r\n            </ul>\r\n        </div>\r\n        <div ng-switch-when=\"multitext\">\r\n            <div ng-repeat=\"item in rec.data[schema.id]\">\r\n                <div class='top-bar tms-flex-row'>\r\n                    <div class='seq tms-flex-grow text-muted' ng-if=\"item.multitext_seq\">#<span ng-bind=\"item.multitext_seq\"></span></div>\r\n                    <span ng-if=\"item.voteResult&&item.voteResult.state!=='BS'\">得票：<span ng-bind=\"item.voteResult.vote_num\"></span>&nbsp;&nbsp;</span>\r\n                    <span ng-if=\"item.score>0\" style='font-size:80%'>得分：<span ng-bind=\"item.score\"></span></span>\r\n                </div>\r\n                <blockquote>\r\n                    <p dynamic-html=\"item.value\"></p>\r\n                    <footer>{{item.nickname}}</footer>\r\n                </blockquote>\r\n            </div>\r\n        </div>\r\n        <div ng-switch-when=\"single\"><span ng-bind=\"rec.data[schema.id]\"></span></div>\r\n        <div ng-switch-when=\"multiple\"><span ng-bind=\"rec.data[schema.id]\"></span></div>\r\n        <div ng-switch-when=\"longtext\">\r\n            <span ng-bind-html=\"rec.data[schema.id]\"></span>\r\n        </div>\r\n        <div ng-switch-when=\"url\">\r\n            <span ng-bind-html=\"rec.data[schema.id]._text\"></span>\r\n        </div>\r\n        <div ng-switch-default>\r\n            <span ng-bind-html=\"rec.data[schema.id]\"></span>\r\n        </div>\r\n        <div ng-if=\"schema.cowork==='Y'\">\r\n            <button class='btn btn-default btn-sm' ng-click=\"coworkRecord(rec)\"><span ng-if=\"rec.coworkState[schema.id].length\">查看全部（<span ng-bind=\"rec.coworkState[schema.id].length\"></span>）或</span>添加 <span class='glyphicon glyphicon-menu-right'></span> <span class='text-muted' ng-if=\"rec._coworkRequireLikeNum\">（还需要{{rec._coworkRequireLikeNum}}个<span class='glyphicon glyphicon-thumbs-up'></span>）</span>\r\n            </button>\r\n        </div>\r\n        <div ng-if=\"schema.supplement==='Y'&&rec.supplement[schema.id]\" class='supplement' ng-bind-html=\"rec.supplement[schema.id]\"></div>\r\n        <div ng-if=\"rec.voteResult[schema.id]\">\r\n            <span ng-if=\"rec.voteResult[schema.id].state!=='BS'\">得票：<span ng-bind=\"rec.voteResult[schema.id].vote_num\"></span></span>\r\n            <button class='btn btn-success btn-xs' ng-if=\"rec.voteResult[schema.id].state==='IP'&&rec.voteResult[schema.id].vote_at===0\" ng-click=\"vote(rec.voteResult[schema.id], $event)\"><span class='glyphicon glyphicon-triangle-top'></span> 投票</button>\r\n            <button class='btn btn-default btn-xs' ng-if=\"rec.voteResult[schema.id].state==='IP'&&rec.voteResult[schema.id].vote_at!==0\" ng-click=\"unvote(rec.voteResult[schema.id], $event)\"><span class='glyphicon glyphicon-triangle-bottom'></span> 撤销投票</button>\r\n        </div>\r\n    </div>\r\n</div>"

/***/ }),

/***/ 34:
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

/***/ 35:
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

/***/ 37:
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

/***/ 40:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "/*! @license\r\n*\r\n* Buttons\r\n* Copyright 2012-2014 Alex Wolfe and Rob Levin\r\n*\r\n* Licensed under the Apache License, Version 2.0 (the \"License\");\r\n* you may not use this file except in compliance with the License.\r\n* You may obtain a copy of the License at\r\n*\r\n*        http://www.apache.org/licenses/LICENSE-2.0\r\n*\r\n* Unless required by applicable law or agreed to in writing, software\r\n* distributed under the License is distributed on an \"AS IS\" BASIS,\r\n* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.\r\n* See the License for the specific language governing permissions and\r\n* limitations under the License.\r\n*/\r\n/*\r\n* Compass (optional)\r\n*\r\n* We recommend the use of autoprefixer instead of Compass\r\n* when using buttons. However, buttons does support Compass.\r\n* simply change $ubtn-use-compass to true and uncomment the\r\n* @import 'compass' code below to use Compass.\r\n*/\r\n/*\r\n* Required Files\r\n*\r\n* These files include the variables and options\r\n* and base css styles that are required to generate buttons.\r\n*/\r\n/*\r\n* $ubtn prefix (reserved)\r\n*\r\n* This prefix stands for Unicorn Button - ubtn\r\n* We provide a prefix to the Sass Variables to\r\n* prevent namespace collisions that could occur if\r\n* you import buttons as part of your Sass build process.\r\n* We kindly ask you not to use the prefix $ubtn in your project\r\n* in order to avoid possilbe name conflicts. Thanks!\r\n*/\r\n/*\r\n* Button Namespace (ex .button or .btn)\r\n*\r\n*/\r\n/*\r\n* Button Defaults\r\n*\r\n* Some default settings that are used throughout the button library.\r\n* Changes to these settings will be picked up by all of the other modules.\r\n* The colors used here are the default colors for the base button (gray).\r\n* The font size and height are used to set the base size for the buttons.\r\n* The size values will be used to calculate the larger and smaller button sizes.\r\n*/\r\n/*\r\n* Button Colors\r\n*\r\n* $ubtn-colors is used to generate the different button colors.\r\n* Edit or add colors to the list below and recompile.\r\n* Each block contains the (name, background, color)\r\n* The class is generated using the name: (ex .button-primary)\r\n*/\r\n/*\r\n* Button Shapes\r\n*\r\n* $ubtn-shapes is used to generate the different button shapes.\r\n* Edit or add shapes to the list below and recompile.\r\n* Each block contains the (name, border-radius).\r\n* The class is generated using the name: (ex .button-square).\r\n*/\r\n/*\r\n* Button Sizes\r\n*\r\n* $ubtn-sizes is used to generate the different button sizes.\r\n* Edit or add colors to the list below and recompile.\r\n* Each block contains the (name, size multiplier).\r\n* The class is generated using the name: (ex .button-giant).\r\n*/\r\n/*\r\n* Color Mixin\r\n*\r\n* Iterates through the list of colors and creates\r\n*\r\n*/\r\n/*\r\n* No Animation\r\n*\r\n* Sets animation property to none\r\n*/\r\n/*\r\n* Clearfix\r\n*\r\n* Clears floats inside the container\r\n*/\r\n/*\r\n* Base Button Style\r\n*\r\n* The default values for the .button class\r\n*/\r\n.button {\r\n  color: #666;\r\n  background-color: #EEE;\r\n  border-color: #EEE;\r\n  font-weight: 300;\r\n  font-size: 16px;\r\n  font-family: \"Helvetica Neue Light\", \"Helvetica Neue\", Helvetica, Arial, \"Lucida Grande\", sans-serif;\r\n  text-decoration: none;\r\n  text-align: center;\r\n  line-height: 40px;\r\n  height: 40px;\r\n  padding: 0 40px;\r\n  margin: 0;\r\n  display: inline-block;\r\n  appearance: none;\r\n  cursor: pointer;\r\n  border: none;\r\n  -webkit-box-sizing: border-box;\r\n     -moz-box-sizing: border-box;\r\n          box-sizing: border-box;\r\n  -webkit-transition-property: all;\r\n          transition-property: all;\r\n  -webkit-transition-duration: .3s;\r\n          transition-duration: .3s;\r\n  /*\r\n  * Disabled State\r\n  *\r\n  * The disabled state uses the class .disabled, is-disabled,\r\n  * and the form attribute disabled=\"disabled\".\r\n  * The use of !important is only added because this is a state\r\n  * that must be applied to all buttons when in a disabled state.\r\n  */ }\r\n  .button:visited {\r\n    color: #666; }\r\n  .button:hover, .button:focus {\r\n    background-color: #f6f6f6;\r\n    text-decoration: none;\r\n    outline: none; }\r\n  .button:active, .button.active, .button.is-active {\r\n    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.3);\r\n    text-decoration: none;\r\n    background-color: #eeeeee;\r\n    border-color: #cfcfcf;\r\n    color: #d4d4d4;\r\n    -webkit-transition-duration: 0s;\r\n            transition-duration: 0s;\r\n    -webkit-box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2); }\r\n  .button.disabled, .button.is-disabled, .button:disabled {\r\n    top: 0 !important;\r\n    background: #EEE !important;\r\n    border: 1px solid #DDD !important;\r\n    text-shadow: 0 1px 1px white !important;\r\n    color: #CCC !important;\r\n    cursor: default !important;\r\n    appearance: none !important;\r\n    -webkit-box-shadow: none !important;\r\n            box-shadow: none !important;\r\n    opacity: .8 !important; }\r\n\r\n/*\r\n* Base Button Tyography\r\n*\r\n*/\r\n.button-uppercase {\r\n  text-transform: uppercase; }\r\n\r\n.button-lowercase {\r\n  text-transform: lowercase; }\r\n\r\n.button-capitalize {\r\n  text-transform: capitalize; }\r\n\r\n.button-small-caps {\r\n  font-variant: small-caps; }\r\n\r\n.button-icon-txt-large {\r\n  font-size: 36px !important; }\r\n\r\n/*\r\n* Base padding\r\n*\r\n*/\r\n.button-width-small {\r\n  padding: 0 10px !important; }\r\n\r\n/*\r\n* Base Colors\r\n*\r\n* Create colors for buttons\r\n* (.button-primary, .button-secondary, etc.)\r\n*/\r\n.button-primary,\r\n.button-primary-flat {\r\n  background-color: #1B9AF7;\r\n  border-color: #1B9AF7;\r\n  color: #FFF; }\r\n  .button-primary:visited,\r\n  .button-primary-flat:visited {\r\n    color: #FFF; }\r\n  .button-primary:hover, .button-primary:focus,\r\n  .button-primary-flat:hover,\r\n  .button-primary-flat:focus {\r\n    background-color: #4cb0f9;\r\n    border-color: #4cb0f9;\r\n    color: #FFF; }\r\n  .button-primary:active, .button-primary.active, .button-primary.is-active,\r\n  .button-primary-flat:active,\r\n  .button-primary-flat.active,\r\n  .button-primary-flat.is-active {\r\n    background-color: #2798eb;\r\n    border-color: #2798eb;\r\n    color: #0880d7; }\r\n\r\n.button-plain,\r\n.button-plain-flat {\r\n  background-color: #FFF;\r\n  border-color: #FFF;\r\n  color: #1B9AF7; }\r\n  .button-plain:visited,\r\n  .button-plain-flat:visited {\r\n    color: #1B9AF7; }\r\n  .button-plain:hover, .button-plain:focus,\r\n  .button-plain-flat:hover,\r\n  .button-plain-flat:focus {\r\n    background-color: white;\r\n    border-color: white;\r\n    color: #1B9AF7; }\r\n  .button-plain:active, .button-plain.active, .button-plain.is-active,\r\n  .button-plain-flat:active,\r\n  .button-plain-flat.active,\r\n  .button-plain-flat.is-active {\r\n    background-color: white;\r\n    border-color: white;\r\n    color: #e6e6e6; }\r\n\r\n.button-inverse,\r\n.button-inverse-flat {\r\n  background-color: #222;\r\n  border-color: #222;\r\n  color: #EEE; }\r\n  .button-inverse:visited,\r\n  .button-inverse-flat:visited {\r\n    color: #EEE; }\r\n  .button-inverse:hover, .button-inverse:focus,\r\n  .button-inverse-flat:hover,\r\n  .button-inverse-flat:focus {\r\n    background-color: #3c3c3c;\r\n    border-color: #3c3c3c;\r\n    color: #EEE; }\r\n  .button-inverse:active, .button-inverse.active, .button-inverse.is-active,\r\n  .button-inverse-flat:active,\r\n  .button-inverse-flat.active,\r\n  .button-inverse-flat.is-active {\r\n    background-color: #222222;\r\n    border-color: #222222;\r\n    color: #090909; }\r\n\r\n.button-action,\r\n.button-action-flat {\r\n  background-color: #A5DE37;\r\n  border-color: #A5DE37;\r\n  color: #FFF; }\r\n  .button-action:visited,\r\n  .button-action-flat:visited {\r\n    color: #FFF; }\r\n  .button-action:hover, .button-action:focus,\r\n  .button-action-flat:hover,\r\n  .button-action-flat:focus {\r\n    background-color: #b9e563;\r\n    border-color: #b9e563;\r\n    color: #FFF; }\r\n  .button-action:active, .button-action.active, .button-action.is-active,\r\n  .button-action-flat:active,\r\n  .button-action-flat.active,\r\n  .button-action-flat.is-active {\r\n    background-color: #a1d243;\r\n    border-color: #a1d243;\r\n    color: #8bc220; }\r\n\r\n.button-highlight,\r\n.button-highlight-flat {\r\n  background-color: #FEAE1B;\r\n  border-color: #FEAE1B;\r\n  color: #FFF; }\r\n  .button-highlight:visited,\r\n  .button-highlight-flat:visited {\r\n    color: #FFF; }\r\n  .button-highlight:hover, .button-highlight:focus,\r\n  .button-highlight-flat:hover,\r\n  .button-highlight-flat:focus {\r\n    background-color: #fec04e;\r\n    border-color: #fec04e;\r\n    color: #FFF; }\r\n  .button-highlight:active, .button-highlight.active, .button-highlight.is-active,\r\n  .button-highlight-flat:active,\r\n  .button-highlight-flat.active,\r\n  .button-highlight-flat.is-active {\r\n    background-color: #f3ab26;\r\n    border-color: #f3ab26;\r\n    color: #e59501; }\r\n\r\n.button-caution,\r\n.button-caution-flat {\r\n  background-color: #FF4351;\r\n  border-color: #FF4351;\r\n  color: #FFF; }\r\n  .button-caution:visited,\r\n  .button-caution-flat:visited {\r\n    color: #FFF; }\r\n  .button-caution:hover, .button-caution:focus,\r\n  .button-caution-flat:hover,\r\n  .button-caution-flat:focus {\r\n    background-color: #ff7680;\r\n    border-color: #ff7680;\r\n    color: #FFF; }\r\n  .button-caution:active, .button-caution.active, .button-caution.is-active,\r\n  .button-caution-flat:active,\r\n  .button-caution-flat.active,\r\n  .button-caution-flat.is-active {\r\n    background-color: #f64c59;\r\n    border-color: #f64c59;\r\n    color: #ff1022; }\r\n\r\n.button-royal,\r\n.button-royal-flat {\r\n  background-color: #7B72E9;\r\n  border-color: #7B72E9;\r\n  color: #FFF; }\r\n  .button-royal:visited,\r\n  .button-royal-flat:visited {\r\n    color: #FFF; }\r\n  .button-royal:hover, .button-royal:focus,\r\n  .button-royal-flat:hover,\r\n  .button-royal-flat:focus {\r\n    background-color: #a49ef0;\r\n    border-color: #a49ef0;\r\n    color: #FFF; }\r\n  .button-royal:active, .button-royal.active, .button-royal.is-active,\r\n  .button-royal-flat:active,\r\n  .button-royal-flat.active,\r\n  .button-royal-flat.is-active {\r\n    background-color: #827ae1;\r\n    border-color: #827ae1;\r\n    color: #5246e2; }\r\n\r\n/*\r\n* Base Layout Styles\r\n*\r\n* Very Miminal Layout Styles\r\n*/\r\n.button-block,\r\n.button-stacked {\r\n  display: block; }\r\n\r\n/*\r\n* Button Types (optional)\r\n*\r\n* All of the files below represent the various button\r\n* types (including shapes & sizes). None of these files\r\n* are required. Simple remove the uneeded type below and\r\n* the button type will be excluded from the final build\r\n*/\r\n/*\r\n* Button Shapes\r\n*\r\n* This file creates the various button shapes\r\n* (ex. Circle, Rounded, Pill)\r\n*/\r\n.button-square {\r\n  border-radius: 0; }\r\n\r\n.button-box {\r\n  border-radius: 10px; }\r\n\r\n.button-rounded {\r\n  border-radius: 4px; }\r\n\r\n.button-pill {\r\n  border-radius: 200px; }\r\n\r\n.button-circle {\r\n  border-radius: 100%; }\r\n\r\n/*\r\n* Size Adjustment for equal height & widht buttons\r\n*\r\n* Remove padding and set a fixed width.\r\n*/\r\n.button-circle,\r\n.button-box,\r\n.button-square {\r\n  padding: 0 !important;\r\n  width: 40px; }\r\n  .button-circle.button-giant,\r\n  .button-box.button-giant,\r\n  .button-square.button-giant {\r\n    width: 70px; }\r\n  .button-circle.button-jumbo,\r\n  .button-box.button-jumbo,\r\n  .button-square.button-jumbo {\r\n    width: 60px; }\r\n  .button-circle.button-large,\r\n  .button-box.button-large,\r\n  .button-square.button-large {\r\n    width: 50px; }\r\n  .button-circle.button-normal,\r\n  .button-box.button-normal,\r\n  .button-square.button-normal {\r\n    width: 40px; }\r\n  .button-circle.button-small,\r\n  .button-box.button-small,\r\n  .button-square.button-small {\r\n    width: 30px; }\r\n  .button-circle.button-tiny,\r\n  .button-box.button-tiny,\r\n  .button-square.button-tiny {\r\n    width: 24px; }\r\n\r\n/*\r\n* Border Buttons\r\n*\r\n* These buttons have no fill they only have a\r\n* border to define their hit target.\r\n*/\r\n.button-border, .button-border-thin, .button-border-thick {\r\n  background: none;\r\n  border-width: 2px;\r\n  border-style: solid;\r\n  line-height: 36px; }\r\n  .button-border:hover, .button-border-thin:hover, .button-border-thick:hover {\r\n    background-color: rgba(255, 255, 255, 0.9); }\r\n  .button-border:active, .button-border-thin:active, .button-border-thick:active, .button-border.active, .active.button-border-thin, .active.button-border-thick, .button-border.is-active, .is-active.button-border-thin, .is-active.button-border-thick {\r\n    -webkit-box-shadow: none;\r\n            box-shadow: none;\r\n    text-shadow: none;\r\n    -webkit-transition-property: all;\r\n            transition-property: all;\r\n    -webkit-transition-duration: .3s;\r\n            transition-duration: .3s; }\r\n\r\n/*\r\n* Border Optional Sizes\r\n*\r\n* A slight variation in border thickness\r\n*/\r\n.button-border-thin {\r\n  border-width: 1px; }\r\n\r\n.button-border-thick {\r\n  border-width: 3px; }\r\n\r\n/*\r\n* Border Button Colors\r\n*\r\n* Create colors for buttons\r\n* (.button-primary, .button-secondary, etc.)\r\n*/\r\n.button-border, .button-border-thin, .button-border-thick,\r\n.button-border-thin,\r\n.button-border-thick {\r\n  /*\r\n  * Border Button Size Adjustment\r\n  *\r\n  * The line-height must be adjusted to compinsate for\r\n  * the width of the border.\r\n  */ }\r\n  .button-border.button-primary, .button-primary.button-border-thin, .button-primary.button-border-thick,\r\n  .button-border-thin.button-primary,\r\n  .button-border-thick.button-primary {\r\n    color: #1B9AF7; }\r\n    .button-border.button-primary:hover, .button-primary.button-border-thin:hover, .button-primary.button-border-thick:hover, .button-border.button-primary:focus, .button-primary.button-border-thin:focus, .button-primary.button-border-thick:focus,\r\n    .button-border-thin.button-primary:hover,\r\n    .button-border-thin.button-primary:focus,\r\n    .button-border-thick.button-primary:hover,\r\n    .button-border-thick.button-primary:focus {\r\n      background-color: rgba(76, 176, 249, 0.9);\r\n      color: rgba(255, 255, 255, 0.9); }\r\n    .button-border.button-primary:active, .button-primary.button-border-thin:active, .button-primary.button-border-thick:active, .button-border.button-primary.active, .button-primary.active.button-border-thin, .button-primary.active.button-border-thick, .button-border.button-primary.is-active, .button-primary.is-active.button-border-thin, .button-primary.is-active.button-border-thick,\r\n    .button-border-thin.button-primary:active,\r\n    .button-border-thin.button-primary.active,\r\n    .button-border-thin.button-primary.is-active,\r\n    .button-border-thick.button-primary:active,\r\n    .button-border-thick.button-primary.active,\r\n    .button-border-thick.button-primary.is-active {\r\n      background-color: rgba(39, 152, 235, 0.7);\r\n      color: rgba(255, 255, 255, 0.5);\r\n      opacity: .3; }\r\n  .button-border.button-plain, .button-plain.button-border-thin, .button-plain.button-border-thick,\r\n  .button-border-thin.button-plain,\r\n  .button-border-thick.button-plain {\r\n    color: #FFF; }\r\n    .button-border.button-plain:hover, .button-plain.button-border-thin:hover, .button-plain.button-border-thick:hover, .button-border.button-plain:focus, .button-plain.button-border-thin:focus, .button-plain.button-border-thick:focus,\r\n    .button-border-thin.button-plain:hover,\r\n    .button-border-thin.button-plain:focus,\r\n    .button-border-thick.button-plain:hover,\r\n    .button-border-thick.button-plain:focus {\r\n      background-color: rgba(255, 255, 255, 0.9);\r\n      color: rgba(27, 154, 247, 0.9); }\r\n    .button-border.button-plain:active, .button-plain.button-border-thin:active, .button-plain.button-border-thick:active, .button-border.button-plain.active, .button-plain.active.button-border-thin, .button-plain.active.button-border-thick, .button-border.button-plain.is-active, .button-plain.is-active.button-border-thin, .button-plain.is-active.button-border-thick,\r\n    .button-border-thin.button-plain:active,\r\n    .button-border-thin.button-plain.active,\r\n    .button-border-thin.button-plain.is-active,\r\n    .button-border-thick.button-plain:active,\r\n    .button-border-thick.button-plain.active,\r\n    .button-border-thick.button-plain.is-active {\r\n      background-color: rgba(255, 255, 255, 0.7);\r\n      color: rgba(27, 154, 247, 0.5);\r\n      opacity: .3; }\r\n  .button-border.button-inverse, .button-inverse.button-border-thin, .button-inverse.button-border-thick,\r\n  .button-border-thin.button-inverse,\r\n  .button-border-thick.button-inverse {\r\n    color: #222; }\r\n    .button-border.button-inverse:hover, .button-inverse.button-border-thin:hover, .button-inverse.button-border-thick:hover, .button-border.button-inverse:focus, .button-inverse.button-border-thin:focus, .button-inverse.button-border-thick:focus,\r\n    .button-border-thin.button-inverse:hover,\r\n    .button-border-thin.button-inverse:focus,\r\n    .button-border-thick.button-inverse:hover,\r\n    .button-border-thick.button-inverse:focus {\r\n      background-color: rgba(60, 60, 60, 0.9);\r\n      color: rgba(238, 238, 238, 0.9); }\r\n    .button-border.button-inverse:active, .button-inverse.button-border-thin:active, .button-inverse.button-border-thick:active, .button-border.button-inverse.active, .button-inverse.active.button-border-thin, .button-inverse.active.button-border-thick, .button-border.button-inverse.is-active, .button-inverse.is-active.button-border-thin, .button-inverse.is-active.button-border-thick,\r\n    .button-border-thin.button-inverse:active,\r\n    .button-border-thin.button-inverse.active,\r\n    .button-border-thin.button-inverse.is-active,\r\n    .button-border-thick.button-inverse:active,\r\n    .button-border-thick.button-inverse.active,\r\n    .button-border-thick.button-inverse.is-active {\r\n      background-color: rgba(34, 34, 34, 0.7);\r\n      color: rgba(238, 238, 238, 0.5);\r\n      opacity: .3; }\r\n  .button-border.button-action, .button-action.button-border-thin, .button-action.button-border-thick,\r\n  .button-border-thin.button-action,\r\n  .button-border-thick.button-action {\r\n    color: #A5DE37; }\r\n    .button-border.button-action:hover, .button-action.button-border-thin:hover, .button-action.button-border-thick:hover, .button-border.button-action:focus, .button-action.button-border-thin:focus, .button-action.button-border-thick:focus,\r\n    .button-border-thin.button-action:hover,\r\n    .button-border-thin.button-action:focus,\r\n    .button-border-thick.button-action:hover,\r\n    .button-border-thick.button-action:focus {\r\n      background-color: rgba(185, 229, 99, 0.9);\r\n      color: rgba(255, 255, 255, 0.9); }\r\n    .button-border.button-action:active, .button-action.button-border-thin:active, .button-action.button-border-thick:active, .button-border.button-action.active, .button-action.active.button-border-thin, .button-action.active.button-border-thick, .button-border.button-action.is-active, .button-action.is-active.button-border-thin, .button-action.is-active.button-border-thick,\r\n    .button-border-thin.button-action:active,\r\n    .button-border-thin.button-action.active,\r\n    .button-border-thin.button-action.is-active,\r\n    .button-border-thick.button-action:active,\r\n    .button-border-thick.button-action.active,\r\n    .button-border-thick.button-action.is-active {\r\n      background-color: rgba(161, 210, 67, 0.7);\r\n      color: rgba(255, 255, 255, 0.5);\r\n      opacity: .3; }\r\n  .button-border.button-highlight, .button-highlight.button-border-thin, .button-highlight.button-border-thick,\r\n  .button-border-thin.button-highlight,\r\n  .button-border-thick.button-highlight {\r\n    color: #FEAE1B; }\r\n    .button-border.button-highlight:hover, .button-highlight.button-border-thin:hover, .button-highlight.button-border-thick:hover, .button-border.button-highlight:focus, .button-highlight.button-border-thin:focus, .button-highlight.button-border-thick:focus,\r\n    .button-border-thin.button-highlight:hover,\r\n    .button-border-thin.button-highlight:focus,\r\n    .button-border-thick.button-highlight:hover,\r\n    .button-border-thick.button-highlight:focus {\r\n      background-color: rgba(254, 192, 78, 0.9);\r\n      color: rgba(255, 255, 255, 0.9); }\r\n    .button-border.button-highlight:active, .button-highlight.button-border-thin:active, .button-highlight.button-border-thick:active, .button-border.button-highlight.active, .button-highlight.active.button-border-thin, .button-highlight.active.button-border-thick, .button-border.button-highlight.is-active, .button-highlight.is-active.button-border-thin, .button-highlight.is-active.button-border-thick,\r\n    .button-border-thin.button-highlight:active,\r\n    .button-border-thin.button-highlight.active,\r\n    .button-border-thin.button-highlight.is-active,\r\n    .button-border-thick.button-highlight:active,\r\n    .button-border-thick.button-highlight.active,\r\n    .button-border-thick.button-highlight.is-active {\r\n      background-color: rgba(243, 171, 38, 0.7);\r\n      color: rgba(255, 255, 255, 0.5);\r\n      opacity: .3; }\r\n  .button-border.button-caution, .button-caution.button-border-thin, .button-caution.button-border-thick,\r\n  .button-border-thin.button-caution,\r\n  .button-border-thick.button-caution {\r\n    color: #FF4351; }\r\n    .button-border.button-caution:hover, .button-caution.button-border-thin:hover, .button-caution.button-border-thick:hover, .button-border.button-caution:focus, .button-caution.button-border-thin:focus, .button-caution.button-border-thick:focus,\r\n    .button-border-thin.button-caution:hover,\r\n    .button-border-thin.button-caution:focus,\r\n    .button-border-thick.button-caution:hover,\r\n    .button-border-thick.button-caution:focus {\r\n      background-color: rgba(255, 118, 128, 0.9);\r\n      color: rgba(255, 255, 255, 0.9); }\r\n    .button-border.button-caution:active, .button-caution.button-border-thin:active, .button-caution.button-border-thick:active, .button-border.button-caution.active, .button-caution.active.button-border-thin, .button-caution.active.button-border-thick, .button-border.button-caution.is-active, .button-caution.is-active.button-border-thin, .button-caution.is-active.button-border-thick,\r\n    .button-border-thin.button-caution:active,\r\n    .button-border-thin.button-caution.active,\r\n    .button-border-thin.button-caution.is-active,\r\n    .button-border-thick.button-caution:active,\r\n    .button-border-thick.button-caution.active,\r\n    .button-border-thick.button-caution.is-active {\r\n      background-color: rgba(246, 76, 89, 0.7);\r\n      color: rgba(255, 255, 255, 0.5);\r\n      opacity: .3; }\r\n  .button-border.button-royal, .button-royal.button-border-thin, .button-royal.button-border-thick,\r\n  .button-border-thin.button-royal,\r\n  .button-border-thick.button-royal {\r\n    color: #7B72E9; }\r\n    .button-border.button-royal:hover, .button-royal.button-border-thin:hover, .button-royal.button-border-thick:hover, .button-border.button-royal:focus, .button-royal.button-border-thin:focus, .button-royal.button-border-thick:focus,\r\n    .button-border-thin.button-royal:hover,\r\n    .button-border-thin.button-royal:focus,\r\n    .button-border-thick.button-royal:hover,\r\n    .button-border-thick.button-royal:focus {\r\n      background-color: rgba(164, 158, 240, 0.9);\r\n      color: rgba(255, 255, 255, 0.9); }\r\n    .button-border.button-royal:active, .button-royal.button-border-thin:active, .button-royal.button-border-thick:active, .button-border.button-royal.active, .button-royal.active.button-border-thin, .button-royal.active.button-border-thick, .button-border.button-royal.is-active, .button-royal.is-active.button-border-thin, .button-royal.is-active.button-border-thick,\r\n    .button-border-thin.button-royal:active,\r\n    .button-border-thin.button-royal.active,\r\n    .button-border-thin.button-royal.is-active,\r\n    .button-border-thick.button-royal:active,\r\n    .button-border-thick.button-royal.active,\r\n    .button-border-thick.button-royal.is-active {\r\n      background-color: rgba(130, 122, 225, 0.7);\r\n      color: rgba(255, 255, 255, 0.5);\r\n      opacity: .3; }\r\n  .button-border.button-giant, .button-giant.button-border-thin, .button-giant.button-border-thick,\r\n  .button-border-thin.button-giant,\r\n  .button-border-thick.button-giant {\r\n    line-height: 66px; }\r\n  .button-border.button-jumbo, .button-jumbo.button-border-thin, .button-jumbo.button-border-thick,\r\n  .button-border-thin.button-jumbo,\r\n  .button-border-thick.button-jumbo {\r\n    line-height: 56px; }\r\n  .button-border.button-large, .button-large.button-border-thin, .button-large.button-border-thick,\r\n  .button-border-thin.button-large,\r\n  .button-border-thick.button-large {\r\n    line-height: 46px; }\r\n  .button-border.button-normal, .button-normal.button-border-thin, .button-normal.button-border-thick,\r\n  .button-border-thin.button-normal,\r\n  .button-border-thick.button-normal {\r\n    line-height: 36px; }\r\n  .button-border.button-small, .button-small.button-border-thin, .button-small.button-border-thick,\r\n  .button-border-thin.button-small,\r\n  .button-border-thick.button-small {\r\n    line-height: 26px; }\r\n  .button-border.button-tiny, .button-tiny.button-border-thin, .button-tiny.button-border-thick,\r\n  .button-border-thin.button-tiny,\r\n  .button-border-thick.button-tiny {\r\n    line-height: 20px; }\r\n\r\n/*\r\n* Border Buttons\r\n*\r\n* These buttons have no fill they only have a\r\n* border to define their hit target.\r\n*/\r\n.button-borderless {\r\n  background: none;\r\n  border: none;\r\n  padding: 0 8px !important;\r\n  color: #EEE;\r\n  font-size: 20.8px;\r\n  font-weight: 200;\r\n  /*\r\n  * Borderless Button Colors\r\n  *\r\n  * Create colors for buttons\r\n  * (.button-primary, .button-secondary, etc.)\r\n  */\r\n  /*\r\n  * Borderles Size Adjustment\r\n  *\r\n  * The font-size must be large to compinsate for\r\n  * the lack of a hit target.\r\n  */ }\r\n  .button-borderless:hover, .button-borderless:focus {\r\n    background: none; }\r\n  .button-borderless:active, .button-borderless.active, .button-borderless.is-active {\r\n    -webkit-box-shadow: none;\r\n            box-shadow: none;\r\n    text-shadow: none;\r\n    -webkit-transition-property: all;\r\n            transition-property: all;\r\n    -webkit-transition-duration: .3s;\r\n            transition-duration: .3s;\r\n    opacity: .3; }\r\n  .button-borderless.button-primary {\r\n    color: #1B9AF7; }\r\n  .button-borderless.button-plain {\r\n    color: #FFF; }\r\n  .button-borderless.button-inverse {\r\n    color: #222; }\r\n  .button-borderless.button-action {\r\n    color: #A5DE37; }\r\n  .button-borderless.button-highlight {\r\n    color: #FEAE1B; }\r\n  .button-borderless.button-caution {\r\n    color: #FF4351; }\r\n  .button-borderless.button-royal {\r\n    color: #7B72E9; }\r\n  .button-borderless.button-giant {\r\n    font-size: 36.4px;\r\n    height: 52.4px;\r\n    line-height: 52.4px; }\r\n  .button-borderless.button-jumbo {\r\n    font-size: 31.2px;\r\n    height: 47.2px;\r\n    line-height: 47.2px; }\r\n  .button-borderless.button-large {\r\n    font-size: 26px;\r\n    height: 42px;\r\n    line-height: 42px; }\r\n  .button-borderless.button-normal {\r\n    font-size: 20.8px;\r\n    height: 36.8px;\r\n    line-height: 36.8px; }\r\n  .button-borderless.button-small {\r\n    font-size: 15.6px;\r\n    height: 31.6px;\r\n    line-height: 31.6px; }\r\n  .button-borderless.button-tiny {\r\n    font-size: 12.48px;\r\n    height: 28.48px;\r\n    line-height: 28.48px; }\r\n\r\n/*\r\n* Raised Buttons\r\n*\r\n* A classic looking button that offers\r\n* great depth and affordance.\r\n*/\r\n.button-raised {\r\n  border-color: #e1e1e1;\r\n  border-style: solid;\r\n  border-width: 1px;\r\n  line-height: 38px;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#f6f6f6), to(#e1e1e1));\r\n  background: linear-gradient(#f6f6f6, #e1e1e1);\r\n  -webkit-box-shadow: inset 0px 1px 0px rgba(255, 255, 255, 0.3), 0 1px 2px rgba(0, 0, 0, 0.15);\r\n          box-shadow: inset 0px 1px 0px rgba(255, 255, 255, 0.3), 0 1px 2px rgba(0, 0, 0, 0.15); }\r\n  .button-raised:hover, .button-raised:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(white), to(gainsboro));\r\n    background: linear-gradient(top, white, gainsboro); }\r\n  .button-raised:active, .button-raised.active, .button-raised.is-active {\r\n    background: #eeeeee;\r\n    -webkit-box-shadow: inset 0px 1px 3px rgba(0, 0, 0, 0.2), 0px 1px 0px white;\r\n            box-shadow: inset 0px 1px 3px rgba(0, 0, 0, 0.2), 0px 1px 0px white; }\r\n\r\n/*\r\n* Raised Button Colors\r\n*\r\n* Create colors for raised buttons\r\n*/\r\n.button-raised.button-primary {\r\n  border-color: #088ef0;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#34a5f8), to(#088ef0));\r\n  background: linear-gradient(#34a5f8, #088ef0); }\r\n  .button-raised.button-primary:hover, .button-raised.button-primary:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(#42abf8), to(#0888e6));\r\n    background: linear-gradient(top, #42abf8, #0888e6); }\r\n  .button-raised.button-primary:active, .button-raised.button-primary.active, .button-raised.button-primary.is-active {\r\n    border-color: #0880d7;\r\n    background: #2798eb; }\r\n.button-raised.button-plain {\r\n  border-color: #f2f2f2;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(white), to(#f2f2f2));\r\n  background: linear-gradient(white, #f2f2f2); }\r\n  .button-raised.button-plain:hover, .button-raised.button-plain:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(white), to(#ededed));\r\n    background: linear-gradient(top, white, #ededed); }\r\n  .button-raised.button-plain:active, .button-raised.button-plain.active, .button-raised.button-plain.is-active {\r\n    border-color: #e6e6e6;\r\n    background: white; }\r\n.button-raised.button-inverse {\r\n  border-color: #151515;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#2f2f2f), to(#151515));\r\n  background: linear-gradient(#2f2f2f, #151515); }\r\n  .button-raised.button-inverse:hover, .button-raised.button-inverse:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(#363636), to(#101010));\r\n    background: linear-gradient(top, #363636, #101010); }\r\n  .button-raised.button-inverse:active, .button-raised.button-inverse.active, .button-raised.button-inverse.is-active {\r\n    border-color: #090909;\r\n    background: #222222; }\r\n.button-raised.button-action {\r\n  border-color: #9ad824;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#afe24d), to(#9ad824));\r\n  background: linear-gradient(#afe24d, #9ad824); }\r\n  .button-raised.button-action:hover, .button-raised.button-action:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(#b5e45a), to(#94cf22));\r\n    background: linear-gradient(top, #b5e45a, #94cf22); }\r\n  .button-raised.button-action:active, .button-raised.button-action.active, .button-raised.button-action.is-active {\r\n    border-color: #8bc220;\r\n    background: #a1d243; }\r\n.button-raised.button-highlight {\r\n  border-color: #fea502;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#feb734), to(#fea502));\r\n  background: linear-gradient(#feb734, #fea502); }\r\n  .button-raised.button-highlight:hover, .button-raised.button-highlight:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(#febc44), to(#f49f01));\r\n    background: linear-gradient(top, #febc44, #f49f01); }\r\n  .button-raised.button-highlight:active, .button-raised.button-highlight.active, .button-raised.button-highlight.is-active {\r\n    border-color: #e59501;\r\n    background: #f3ab26; }\r\n.button-raised.button-caution {\r\n  border-color: #ff2939;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#ff5c69), to(#ff2939));\r\n  background: linear-gradient(#ff5c69, #ff2939); }\r\n  .button-raised.button-caution:hover, .button-raised.button-caution:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(#ff6c77), to(#ff1f30));\r\n    background: linear-gradient(top, #ff6c77, #ff1f30); }\r\n  .button-raised.button-caution:active, .button-raised.button-caution.active, .button-raised.button-caution.is-active {\r\n    border-color: #ff1022;\r\n    background: #f64c59; }\r\n.button-raised.button-royal {\r\n  border-color: #665ce6;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#9088ec), to(#665ce6));\r\n  background: linear-gradient(#9088ec, #665ce6); }\r\n  .button-raised.button-royal:hover, .button-raised.button-royal:focus {\r\n    background: -webkit-gradient(linear, left top, left bottom, from(#9c95ef), to(#5e53e4));\r\n    background: linear-gradient(top, #9c95ef, #5e53e4); }\r\n  .button-raised.button-royal:active, .button-raised.button-royal.active, .button-raised.button-royal.is-active {\r\n    border-color: #5246e2;\r\n    background: #827ae1; }\r\n\r\n/*\r\n* 3D Buttons\r\n*\r\n* These buttons have a heavy three dimensional\r\n* style that mimics the visual appearance of a\r\n* real life button.\r\n*/\r\n.button-3d {\r\n  position: relative;\r\n  top: 0;\r\n  -webkit-box-shadow: 0 7px 0 #bbbbbb, 0 8px 3px rgba(0, 0, 0, 0.2);\r\n          box-shadow: 0 7px 0 #bbbbbb, 0 8px 3px rgba(0, 0, 0, 0.2); }\r\n  .button-3d:hover, .button-3d:focus {\r\n    -webkit-box-shadow: 0 7px 0 #bbbbbb, 0 8px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 7px 0 #bbbbbb, 0 8px 3px rgba(0, 0, 0, 0.2); }\r\n  .button-3d:active, .button-3d.active, .button-3d.is-active {\r\n    top: 5px;\r\n    -webkit-transition-property: all;\r\n            transition-property: all;\r\n    -webkit-transition-duration: .15s;\r\n            transition-duration: .15s;\r\n    -webkit-box-shadow: 0 2px 0 #bbbbbb, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 #bbbbbb, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n\r\n/*\r\n* 3D Button Colors\r\n*\r\n* Create colors for buttons\r\n* (.button-primary, .button-secondary, etc.)\r\n*/\r\n.button-3d.button-primary {\r\n  -webkit-box-shadow: 0 7px 0 #0880d7, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n          box-shadow: 0 7px 0 #0880d7, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-primary:hover, .button-3d.button-primary:focus {\r\n    -webkit-box-shadow: 0 7px 0 #077ace, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n            box-shadow: 0 7px 0 #077ace, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-primary:active, .button-3d.button-primary.active, .button-3d.button-primary.is-active {\r\n    -webkit-box-shadow: 0 2px 0 #0662a6, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 #0662a6, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n.button-3d.button-plain {\r\n  -webkit-box-shadow: 0 7px 0 #e6e6e6, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n          box-shadow: 0 7px 0 #e6e6e6, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-plain:hover, .button-3d.button-plain:focus {\r\n    -webkit-box-shadow: 0 7px 0 #e0e0e0, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n            box-shadow: 0 7px 0 #e0e0e0, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-plain:active, .button-3d.button-plain.active, .button-3d.button-plain.is-active {\r\n    -webkit-box-shadow: 0 2px 0 #cccccc, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 #cccccc, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n.button-3d.button-inverse {\r\n  -webkit-box-shadow: 0 7px 0 #090909, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n          box-shadow: 0 7px 0 #090909, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-inverse:hover, .button-3d.button-inverse:focus {\r\n    -webkit-box-shadow: 0 7px 0 #030303, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n            box-shadow: 0 7px 0 #030303, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-inverse:active, .button-3d.button-inverse.active, .button-3d.button-inverse.is-active {\r\n    -webkit-box-shadow: 0 2px 0 black, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 black, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n.button-3d.button-action {\r\n  -webkit-box-shadow: 0 7px 0 #8bc220, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n          box-shadow: 0 7px 0 #8bc220, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-action:hover, .button-3d.button-action:focus {\r\n    -webkit-box-shadow: 0 7px 0 #84b91f, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n            box-shadow: 0 7px 0 #84b91f, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-action:active, .button-3d.button-action.active, .button-3d.button-action.is-active {\r\n    -webkit-box-shadow: 0 2px 0 #6b9619, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 #6b9619, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n.button-3d.button-highlight {\r\n  -webkit-box-shadow: 0 7px 0 #e59501, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n          box-shadow: 0 7px 0 #e59501, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-highlight:hover, .button-3d.button-highlight:focus {\r\n    -webkit-box-shadow: 0 7px 0 #db8e01, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n            box-shadow: 0 7px 0 #db8e01, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-highlight:active, .button-3d.button-highlight.active, .button-3d.button-highlight.is-active {\r\n    -webkit-box-shadow: 0 2px 0 #b27401, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 #b27401, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n.button-3d.button-caution {\r\n  -webkit-box-shadow: 0 7px 0 #ff1022, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n          box-shadow: 0 7px 0 #ff1022, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-caution:hover, .button-3d.button-caution:focus {\r\n    -webkit-box-shadow: 0 7px 0 #ff0618, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n            box-shadow: 0 7px 0 #ff0618, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-caution:active, .button-3d.button-caution.active, .button-3d.button-caution.is-active {\r\n    -webkit-box-shadow: 0 2px 0 #dc0010, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 #dc0010, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n.button-3d.button-royal {\r\n  -webkit-box-shadow: 0 7px 0 #5246e2, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n          box-shadow: 0 7px 0 #5246e2, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-royal:hover, .button-3d.button-royal:focus {\r\n    -webkit-box-shadow: 0 7px 0 #493de1, 0 8px 3px rgba(0, 0, 0, 0.3);\r\n            box-shadow: 0 7px 0 #493de1, 0 8px 3px rgba(0, 0, 0, 0.3); }\r\n  .button-3d.button-royal:active, .button-3d.button-royal.active, .button-3d.button-royal.is-active {\r\n    -webkit-box-shadow: 0 2px 0 #2f21d4, 0 3px 3px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 2px 0 #2f21d4, 0 3px 3px rgba(0, 0, 0, 0.2); }\r\n\r\n/*\r\n* Glowing Buttons\r\n*\r\n* A pulse like glow that appears\r\n* rythmically around the edges of\r\n* a button.\r\n*/\r\n/*\r\n* Glow animation mixin for Compass users\r\n*\r\n*/\r\n/*\r\n* Glowing Keyframes\r\n*\r\n*/\r\n@-webkit-keyframes glowing {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(44, 154, 219, 0.3);\r\n            box-shadow: 0 0 0 rgba(44, 154, 219, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(44, 154, 219, 0.8);\r\n            box-shadow: 0 0 20px rgba(44, 154, 219, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(44, 154, 219, 0.3);\r\n            box-shadow: 0 0 0 rgba(44, 154, 219, 0.3); } }\r\n@keyframes glowing {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(44, 154, 219, 0.3);\r\n            box-shadow: 0 0 0 rgba(44, 154, 219, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(44, 154, 219, 0.8);\r\n            box-shadow: 0 0 20px rgba(44, 154, 219, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(44, 154, 219, 0.3);\r\n            box-shadow: 0 0 0 rgba(44, 154, 219, 0.3); } }\r\n/*\r\n* Glowing Keyframes for various colors\r\n*\r\n*/\r\n@-webkit-keyframes glowing-primary {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(27, 154, 247, 0.3);\r\n            box-shadow: 0 0 0 rgba(27, 154, 247, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(27, 154, 247, 0.8);\r\n            box-shadow: 0 0 20px rgba(27, 154, 247, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(27, 154, 247, 0.3);\r\n            box-shadow: 0 0 0 rgba(27, 154, 247, 0.3); } }\r\n@keyframes glowing-primary {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(27, 154, 247, 0.3);\r\n            box-shadow: 0 0 0 rgba(27, 154, 247, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(27, 154, 247, 0.8);\r\n            box-shadow: 0 0 20px rgba(27, 154, 247, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(27, 154, 247, 0.3);\r\n            box-shadow: 0 0 0 rgba(27, 154, 247, 0.3); } }\r\n@-webkit-keyframes glowing-plain {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 255, 255, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 255, 255, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(255, 255, 255, 0.8);\r\n            box-shadow: 0 0 20px rgba(255, 255, 255, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 255, 255, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 255, 255, 0.3); } }\r\n@keyframes glowing-plain {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 255, 255, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 255, 255, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(255, 255, 255, 0.8);\r\n            box-shadow: 0 0 20px rgba(255, 255, 255, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 255, 255, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 255, 255, 0.3); } }\r\n@-webkit-keyframes glowing-inverse {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(34, 34, 34, 0.3);\r\n            box-shadow: 0 0 0 rgba(34, 34, 34, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(34, 34, 34, 0.8);\r\n            box-shadow: 0 0 20px rgba(34, 34, 34, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(34, 34, 34, 0.3);\r\n            box-shadow: 0 0 0 rgba(34, 34, 34, 0.3); } }\r\n@keyframes glowing-inverse {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(34, 34, 34, 0.3);\r\n            box-shadow: 0 0 0 rgba(34, 34, 34, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(34, 34, 34, 0.8);\r\n            box-shadow: 0 0 20px rgba(34, 34, 34, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(34, 34, 34, 0.3);\r\n            box-shadow: 0 0 0 rgba(34, 34, 34, 0.3); } }\r\n@-webkit-keyframes glowing-action {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(165, 222, 55, 0.3);\r\n            box-shadow: 0 0 0 rgba(165, 222, 55, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(165, 222, 55, 0.8);\r\n            box-shadow: 0 0 20px rgba(165, 222, 55, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(165, 222, 55, 0.3);\r\n            box-shadow: 0 0 0 rgba(165, 222, 55, 0.3); } }\r\n@keyframes glowing-action {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(165, 222, 55, 0.3);\r\n            box-shadow: 0 0 0 rgba(165, 222, 55, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(165, 222, 55, 0.8);\r\n            box-shadow: 0 0 20px rgba(165, 222, 55, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(165, 222, 55, 0.3);\r\n            box-shadow: 0 0 0 rgba(165, 222, 55, 0.3); } }\r\n@-webkit-keyframes glowing-highlight {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(254, 174, 27, 0.3);\r\n            box-shadow: 0 0 0 rgba(254, 174, 27, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(254, 174, 27, 0.8);\r\n            box-shadow: 0 0 20px rgba(254, 174, 27, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(254, 174, 27, 0.3);\r\n            box-shadow: 0 0 0 rgba(254, 174, 27, 0.3); } }\r\n@keyframes glowing-highlight {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(254, 174, 27, 0.3);\r\n            box-shadow: 0 0 0 rgba(254, 174, 27, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(254, 174, 27, 0.8);\r\n            box-shadow: 0 0 20px rgba(254, 174, 27, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(254, 174, 27, 0.3);\r\n            box-shadow: 0 0 0 rgba(254, 174, 27, 0.3); } }\r\n@-webkit-keyframes glowing-caution {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 67, 81, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 67, 81, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(255, 67, 81, 0.8);\r\n            box-shadow: 0 0 20px rgba(255, 67, 81, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 67, 81, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 67, 81, 0.3); } }\r\n@keyframes glowing-caution {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 67, 81, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 67, 81, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(255, 67, 81, 0.8);\r\n            box-shadow: 0 0 20px rgba(255, 67, 81, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(255, 67, 81, 0.3);\r\n            box-shadow: 0 0 0 rgba(255, 67, 81, 0.3); } }\r\n@-webkit-keyframes glowing-royal {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(123, 114, 233, 0.3);\r\n            box-shadow: 0 0 0 rgba(123, 114, 233, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(123, 114, 233, 0.8);\r\n            box-shadow: 0 0 20px rgba(123, 114, 233, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(123, 114, 233, 0.3);\r\n            box-shadow: 0 0 0 rgba(123, 114, 233, 0.3); } }\r\n@keyframes glowing-royal {\r\n  from {\r\n    -webkit-box-shadow: 0 0 0 rgba(123, 114, 233, 0.3);\r\n            box-shadow: 0 0 0 rgba(123, 114, 233, 0.3); }\r\n  50% {\r\n    -webkit-box-shadow: 0 0 20px rgba(123, 114, 233, 0.8);\r\n            box-shadow: 0 0 20px rgba(123, 114, 233, 0.8); }\r\n  to {\r\n    -webkit-box-shadow: 0 0 0 rgba(123, 114, 233, 0.3);\r\n            box-shadow: 0 0 0 rgba(123, 114, 233, 0.3); } }\r\n/*\r\n* Glowing Buttons Base Styes\r\n*\r\n* A pulse like glow that appears\r\n* rythmically around the edges of\r\n* a button.\r\n*/\r\n.button-glow {\r\n  -webkit-animation-duration: 3s;\r\n          animation-duration: 3s;\r\n  -webkit-animation-iteration-count: infinite;\r\n          animation-iteration-count: infinite;\r\n  -webkit-animation-name: glowing;\r\n          animation-name: glowing; }\r\n  .button-glow:active, .button-glow.active, .button-glow.is-active {\r\n    -webkit-animation-name: none;\r\n            animation-name: none; }\r\n\r\n/*\r\n* Glowing Button Colors\r\n*\r\n* Create colors for glowing buttons\r\n*/\r\n.button-glow.button-primary {\r\n  -webkit-animation-name: glowing-primary;\r\n          animation-name: glowing-primary; }\r\n.button-glow.button-plain {\r\n  -webkit-animation-name: glowing-plain;\r\n          animation-name: glowing-plain; }\r\n.button-glow.button-inverse {\r\n  -webkit-animation-name: glowing-inverse;\r\n          animation-name: glowing-inverse; }\r\n.button-glow.button-action {\r\n  -webkit-animation-name: glowing-action;\r\n          animation-name: glowing-action; }\r\n.button-glow.button-highlight {\r\n  -webkit-animation-name: glowing-highlight;\r\n          animation-name: glowing-highlight; }\r\n.button-glow.button-caution {\r\n  -webkit-animation-name: glowing-caution;\r\n          animation-name: glowing-caution; }\r\n.button-glow.button-royal {\r\n  -webkit-animation-name: glowing-royal;\r\n          animation-name: glowing-royal; }\r\n\r\n/*\r\n* Dropdown menu buttons\r\n*\r\n* A dropdown menu appears\r\n* when a button is pressed\r\n*/\r\n/*\r\n* Dropdown Container\r\n*\r\n*/\r\n.button-dropdown {\r\n  position: relative;\r\n  overflow: visible;\r\n  display: inline-block; }\r\n\r\n/*\r\n* Dropdown List Style\r\n*\r\n*/\r\n.button-dropdown-list {\r\n  display: none;\r\n  position: absolute;\r\n  padding: 0;\r\n  margin: 0;\r\n  top: 0;\r\n  left: 0;\r\n  z-index: 1000;\r\n  min-width: 100%;\r\n  list-style-type: none;\r\n  background: rgba(255, 255, 255, 0.95);\r\n  border-style: solid;\r\n  border-width: 1px;\r\n  border-color: #d4d4d4;\r\n  font-family: \"Helvetica Neue Light\", \"Helvetica Neue\", Helvetica, Arial, \"Lucida Grande\", sans-serif;\r\n  -webkit-box-shadow: 0 2px 7px rgba(0, 0, 0, 0.2);\r\n          box-shadow: 0 2px 7px rgba(0, 0, 0, 0.2);\r\n  border-radius: 3px;\r\n  -webkit-box-sizing: border-box;\r\n     -moz-box-sizing: border-box;\r\n          box-sizing: border-box;\r\n  /*\r\n  * Dropdown Below\r\n  *\r\n  */\r\n  /*\r\n  * Dropdown Above\r\n  *\r\n  */ }\r\n  .button-dropdown-list.is-below {\r\n    top: 100%;\r\n    border-top: none;\r\n    border-radius: 0 0 3px 3px; }\r\n  .button-dropdown-list.is-above {\r\n    bottom: 100%;\r\n    top: auto;\r\n    border-bottom: none;\r\n    border-radius: 3px 3px 0 0;\r\n    -webkit-box-shadow: 0 -2px 7px rgba(0, 0, 0, 0.2);\r\n            box-shadow: 0 -2px 7px rgba(0, 0, 0, 0.2); }\r\n\r\n/*\r\n* Dropdown Buttons\r\n*\r\n*/\r\n.button-dropdown-list > li {\r\n  padding: 0;\r\n  margin: 0;\r\n  display: block; }\r\n  .button-dropdown-list > li > a {\r\n    display: block;\r\n    line-height: 40px;\r\n    font-size: 12.8px;\r\n    padding: 5px 10px;\r\n    float: none;\r\n    color: #666;\r\n    text-decoration: none; }\r\n    .button-dropdown-list > li > a:hover {\r\n      color: #5e5e5e;\r\n      background: #f6f6f6;\r\n      text-decoration: none; }\r\n\r\n.button-dropdown-divider {\r\n  border-top: 1px solid #e6e6e6; }\r\n\r\n/*\r\n* Dropdown Colors\r\n*\r\n* Create colors for buttons\r\n* (.button-primary, .button-secondary, etc.)\r\n*/\r\n.button-dropdown.button-dropdown-primary .button-dropdown-list {\r\n  background: rgba(27, 154, 247, 0.95);\r\n  border-color: #0880d7; }\r\n  .button-dropdown.button-dropdown-primary .button-dropdown-list .button-dropdown-divider {\r\n    border-color: #0888e6; }\r\n  .button-dropdown.button-dropdown-primary .button-dropdown-list > li > a {\r\n    color: #FFF; }\r\n    .button-dropdown.button-dropdown-primary .button-dropdown-list > li > a:hover {\r\n      color: #f2f2f2;\r\n      background: #088ef0; }\r\n.button-dropdown.button-dropdown-plain .button-dropdown-list {\r\n  background: rgba(255, 255, 255, 0.95);\r\n  border-color: #e6e6e6; }\r\n  .button-dropdown.button-dropdown-plain .button-dropdown-list .button-dropdown-divider {\r\n    border-color: #ededed; }\r\n  .button-dropdown.button-dropdown-plain .button-dropdown-list > li > a {\r\n    color: #1B9AF7; }\r\n    .button-dropdown.button-dropdown-plain .button-dropdown-list > li > a:hover {\r\n      color: #088ef0;\r\n      background: #f2f2f2; }\r\n.button-dropdown.button-dropdown-inverse .button-dropdown-list {\r\n  background: rgba(34, 34, 34, 0.95);\r\n  border-color: #090909; }\r\n  .button-dropdown.button-dropdown-inverse .button-dropdown-list .button-dropdown-divider {\r\n    border-color: #101010; }\r\n  .button-dropdown.button-dropdown-inverse .button-dropdown-list > li > a {\r\n    color: #EEE; }\r\n    .button-dropdown.button-dropdown-inverse .button-dropdown-list > li > a:hover {\r\n      color: #e1e1e1;\r\n      background: #151515; }\r\n.button-dropdown.button-dropdown-action .button-dropdown-list {\r\n  background: rgba(165, 222, 55, 0.95);\r\n  border-color: #8bc220; }\r\n  .button-dropdown.button-dropdown-action .button-dropdown-list .button-dropdown-divider {\r\n    border-color: #94cf22; }\r\n  .button-dropdown.button-dropdown-action .button-dropdown-list > li > a {\r\n    color: #FFF; }\r\n    .button-dropdown.button-dropdown-action .button-dropdown-list > li > a:hover {\r\n      color: #f2f2f2;\r\n      background: #9ad824; }\r\n.button-dropdown.button-dropdown-highlight .button-dropdown-list {\r\n  background: rgba(254, 174, 27, 0.95);\r\n  border-color: #e59501; }\r\n  .button-dropdown.button-dropdown-highlight .button-dropdown-list .button-dropdown-divider {\r\n    border-color: #f49f01; }\r\n  .button-dropdown.button-dropdown-highlight .button-dropdown-list > li > a {\r\n    color: #FFF; }\r\n    .button-dropdown.button-dropdown-highlight .button-dropdown-list > li > a:hover {\r\n      color: #f2f2f2;\r\n      background: #fea502; }\r\n.button-dropdown.button-dropdown-caution .button-dropdown-list {\r\n  background: rgba(255, 67, 81, 0.95);\r\n  border-color: #ff1022; }\r\n  .button-dropdown.button-dropdown-caution .button-dropdown-list .button-dropdown-divider {\r\n    border-color: #ff1f30; }\r\n  .button-dropdown.button-dropdown-caution .button-dropdown-list > li > a {\r\n    color: #FFF; }\r\n    .button-dropdown.button-dropdown-caution .button-dropdown-list > li > a:hover {\r\n      color: #f2f2f2;\r\n      background: #ff2939; }\r\n.button-dropdown.button-dropdown-royal .button-dropdown-list {\r\n  background: rgba(123, 114, 233, 0.95);\r\n  border-color: #5246e2; }\r\n  .button-dropdown.button-dropdown-royal .button-dropdown-list .button-dropdown-divider {\r\n    border-color: #5e53e4; }\r\n  .button-dropdown.button-dropdown-royal .button-dropdown-list > li > a {\r\n    color: #FFF; }\r\n    .button-dropdown.button-dropdown-royal .button-dropdown-list > li > a:hover {\r\n      color: #f2f2f2;\r\n      background: #665ce6; }\r\n\r\n/*\r\n* Buton Groups\r\n*\r\n* A group of related buttons\r\n* displayed edge to edge\r\n*/\r\n.button-group {\r\n  position: relative;\r\n  display: inline-block; }\r\n  .button-group:after {\r\n    content: \" \";\r\n    display: block;\r\n    clear: both; }\r\n  .button-group .button,\r\n  .button-group .button-dropdown {\r\n    float: left; }\r\n    .button-group .button:not(:first-child):not(:last-child),\r\n    .button-group .button-dropdown:not(:first-child):not(:last-child) {\r\n      border-radius: 0;\r\n      border-right: none; }\r\n    .button-group .button:first-child,\r\n    .button-group .button-dropdown:first-child {\r\n      border-top-right-radius: 0;\r\n      border-bottom-right-radius: 0;\r\n      border-right: none; }\r\n    .button-group .button:last-child,\r\n    .button-group .button-dropdown:last-child {\r\n      border-top-left-radius: 0;\r\n      border-bottom-left-radius: 0; }\r\n\r\n/*\r\n* Button Wrapper\r\n*\r\n* A wrap around effect to highlight\r\n* the shape of the button and offer\r\n* a subtle visual effect.\r\n*/\r\n.button-wrap {\r\n  border: 1px solid #e3e3e3;\r\n  display: inline-block;\r\n  padding: 9px;\r\n  background: -webkit-gradient(linear, left top, left bottom, from(#f2f2f2), to(#FFF));\r\n  background: linear-gradient(#f2f2f2, #FFF);\r\n  border-radius: 200px;\r\n  -webkit-box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.04);\r\n          box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.04); }\r\n\r\n/*\r\n* Long Shadow Buttons\r\n*\r\n* A visual effect adding a flat shadow to the text of a button\r\n*/\r\n/*\r\n* Long Shadow Function\r\n*\r\n* Loops $length times building a long shadow. Defaults downward right\r\n*/\r\n/*\r\n* LONG SHADOW MIXIN\r\n*\r\n*/\r\n/*\r\n* Shadow Right\r\n*\r\n*/\r\n.button-longshadow,\r\n.button-longshadow-right {\r\n  overflow: hidden; }\r\n  .button-longshadow.button-primary,\r\n  .button-longshadow-right.button-primary {\r\n    text-shadow: 0px 0px #0880d7, 1px 1px #0880d7, 2px 2px #0880d7, 3px 3px #0880d7, 4px 4px #0880d7, 5px 5px #0880d7, 6px 6px #0880d7, 7px 7px #0880d7, 8px 8px #0880d7, 9px 9px #0880d7, 10px 10px #0880d7, 11px 11px #0880d7, 12px 12px #0880d7, 13px 13px #0880d7, 14px 14px #0880d7, 15px 15px #0880d7, 16px 16px #0880d7, 17px 17px #0880d7, 18px 18px #0880d7, 19px 19px #0880d7, 20px 20px #0880d7, 21px 21px #0880d7, 22px 22px #0880d7, 23px 23px #0880d7, 24px 24px #0880d7, 25px 25px #0880d7, 26px 26px #0880d7, 27px 27px #0880d7, 28px 28px #0880d7, 29px 29px #0880d7, 30px 30px #0880d7, 31px 31px #0880d7, 32px 32px #0880d7, 33px 33px #0880d7, 34px 34px #0880d7, 35px 35px #0880d7, 36px 36px #0880d7, 37px 37px #0880d7, 38px 38px #0880d7, 39px 39px #0880d7, 40px 40px #0880d7, 41px 41px #0880d7, 42px 42px #0880d7, 43px 43px #0880d7, 44px 44px #0880d7, 45px 45px #0880d7, 46px 46px #0880d7, 47px 47px #0880d7, 48px 48px #0880d7, 49px 49px #0880d7, 50px 50px #0880d7, 51px 51px #0880d7, 52px 52px #0880d7, 53px 53px #0880d7, 54px 54px #0880d7, 55px 55px #0880d7, 56px 56px #0880d7, 57px 57px #0880d7, 58px 58px #0880d7, 59px 59px #0880d7, 60px 60px #0880d7, 61px 61px #0880d7, 62px 62px #0880d7, 63px 63px #0880d7, 64px 64px #0880d7, 65px 65px #0880d7, 66px 66px #0880d7, 67px 67px #0880d7, 68px 68px #0880d7, 69px 69px #0880d7, 70px 70px #0880d7, 71px 71px #0880d7, 72px 72px #0880d7, 73px 73px #0880d7, 74px 74px #0880d7, 75px 75px #0880d7, 76px 76px #0880d7, 77px 77px #0880d7, 78px 78px #0880d7, 79px 79px #0880d7, 80px 80px #0880d7, 81px 81px #0880d7, 82px 82px #0880d7, 83px 83px #0880d7, 84px 84px #0880d7, 85px 85px #0880d7; }\r\n    .button-longshadow.button-primary:active, .button-longshadow.button-primary.active, .button-longshadow.button-primary.is-active,\r\n    .button-longshadow-right.button-primary:active,\r\n    .button-longshadow-right.button-primary.active,\r\n    .button-longshadow-right.button-primary.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow.button-plain,\r\n  .button-longshadow-right.button-plain {\r\n    text-shadow: 0px 0px #e6e6e6, 1px 1px #e6e6e6, 2px 2px #e6e6e6, 3px 3px #e6e6e6, 4px 4px #e6e6e6, 5px 5px #e6e6e6, 6px 6px #e6e6e6, 7px 7px #e6e6e6, 8px 8px #e6e6e6, 9px 9px #e6e6e6, 10px 10px #e6e6e6, 11px 11px #e6e6e6, 12px 12px #e6e6e6, 13px 13px #e6e6e6, 14px 14px #e6e6e6, 15px 15px #e6e6e6, 16px 16px #e6e6e6, 17px 17px #e6e6e6, 18px 18px #e6e6e6, 19px 19px #e6e6e6, 20px 20px #e6e6e6, 21px 21px #e6e6e6, 22px 22px #e6e6e6, 23px 23px #e6e6e6, 24px 24px #e6e6e6, 25px 25px #e6e6e6, 26px 26px #e6e6e6, 27px 27px #e6e6e6, 28px 28px #e6e6e6, 29px 29px #e6e6e6, 30px 30px #e6e6e6, 31px 31px #e6e6e6, 32px 32px #e6e6e6, 33px 33px #e6e6e6, 34px 34px #e6e6e6, 35px 35px #e6e6e6, 36px 36px #e6e6e6, 37px 37px #e6e6e6, 38px 38px #e6e6e6, 39px 39px #e6e6e6, 40px 40px #e6e6e6, 41px 41px #e6e6e6, 42px 42px #e6e6e6, 43px 43px #e6e6e6, 44px 44px #e6e6e6, 45px 45px #e6e6e6, 46px 46px #e6e6e6, 47px 47px #e6e6e6, 48px 48px #e6e6e6, 49px 49px #e6e6e6, 50px 50px #e6e6e6, 51px 51px #e6e6e6, 52px 52px #e6e6e6, 53px 53px #e6e6e6, 54px 54px #e6e6e6, 55px 55px #e6e6e6, 56px 56px #e6e6e6, 57px 57px #e6e6e6, 58px 58px #e6e6e6, 59px 59px #e6e6e6, 60px 60px #e6e6e6, 61px 61px #e6e6e6, 62px 62px #e6e6e6, 63px 63px #e6e6e6, 64px 64px #e6e6e6, 65px 65px #e6e6e6, 66px 66px #e6e6e6, 67px 67px #e6e6e6, 68px 68px #e6e6e6, 69px 69px #e6e6e6, 70px 70px #e6e6e6, 71px 71px #e6e6e6, 72px 72px #e6e6e6, 73px 73px #e6e6e6, 74px 74px #e6e6e6, 75px 75px #e6e6e6, 76px 76px #e6e6e6, 77px 77px #e6e6e6, 78px 78px #e6e6e6, 79px 79px #e6e6e6, 80px 80px #e6e6e6, 81px 81px #e6e6e6, 82px 82px #e6e6e6, 83px 83px #e6e6e6, 84px 84px #e6e6e6, 85px 85px #e6e6e6; }\r\n    .button-longshadow.button-plain:active, .button-longshadow.button-plain.active, .button-longshadow.button-plain.is-active,\r\n    .button-longshadow-right.button-plain:active,\r\n    .button-longshadow-right.button-plain.active,\r\n    .button-longshadow-right.button-plain.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow.button-inverse,\r\n  .button-longshadow-right.button-inverse {\r\n    text-shadow: 0px 0px #090909, 1px 1px #090909, 2px 2px #090909, 3px 3px #090909, 4px 4px #090909, 5px 5px #090909, 6px 6px #090909, 7px 7px #090909, 8px 8px #090909, 9px 9px #090909, 10px 10px #090909, 11px 11px #090909, 12px 12px #090909, 13px 13px #090909, 14px 14px #090909, 15px 15px #090909, 16px 16px #090909, 17px 17px #090909, 18px 18px #090909, 19px 19px #090909, 20px 20px #090909, 21px 21px #090909, 22px 22px #090909, 23px 23px #090909, 24px 24px #090909, 25px 25px #090909, 26px 26px #090909, 27px 27px #090909, 28px 28px #090909, 29px 29px #090909, 30px 30px #090909, 31px 31px #090909, 32px 32px #090909, 33px 33px #090909, 34px 34px #090909, 35px 35px #090909, 36px 36px #090909, 37px 37px #090909, 38px 38px #090909, 39px 39px #090909, 40px 40px #090909, 41px 41px #090909, 42px 42px #090909, 43px 43px #090909, 44px 44px #090909, 45px 45px #090909, 46px 46px #090909, 47px 47px #090909, 48px 48px #090909, 49px 49px #090909, 50px 50px #090909, 51px 51px #090909, 52px 52px #090909, 53px 53px #090909, 54px 54px #090909, 55px 55px #090909, 56px 56px #090909, 57px 57px #090909, 58px 58px #090909, 59px 59px #090909, 60px 60px #090909, 61px 61px #090909, 62px 62px #090909, 63px 63px #090909, 64px 64px #090909, 65px 65px #090909, 66px 66px #090909, 67px 67px #090909, 68px 68px #090909, 69px 69px #090909, 70px 70px #090909, 71px 71px #090909, 72px 72px #090909, 73px 73px #090909, 74px 74px #090909, 75px 75px #090909, 76px 76px #090909, 77px 77px #090909, 78px 78px #090909, 79px 79px #090909, 80px 80px #090909, 81px 81px #090909, 82px 82px #090909, 83px 83px #090909, 84px 84px #090909, 85px 85px #090909; }\r\n    .button-longshadow.button-inverse:active, .button-longshadow.button-inverse.active, .button-longshadow.button-inverse.is-active,\r\n    .button-longshadow-right.button-inverse:active,\r\n    .button-longshadow-right.button-inverse.active,\r\n    .button-longshadow-right.button-inverse.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow.button-action,\r\n  .button-longshadow-right.button-action {\r\n    text-shadow: 0px 0px #8bc220, 1px 1px #8bc220, 2px 2px #8bc220, 3px 3px #8bc220, 4px 4px #8bc220, 5px 5px #8bc220, 6px 6px #8bc220, 7px 7px #8bc220, 8px 8px #8bc220, 9px 9px #8bc220, 10px 10px #8bc220, 11px 11px #8bc220, 12px 12px #8bc220, 13px 13px #8bc220, 14px 14px #8bc220, 15px 15px #8bc220, 16px 16px #8bc220, 17px 17px #8bc220, 18px 18px #8bc220, 19px 19px #8bc220, 20px 20px #8bc220, 21px 21px #8bc220, 22px 22px #8bc220, 23px 23px #8bc220, 24px 24px #8bc220, 25px 25px #8bc220, 26px 26px #8bc220, 27px 27px #8bc220, 28px 28px #8bc220, 29px 29px #8bc220, 30px 30px #8bc220, 31px 31px #8bc220, 32px 32px #8bc220, 33px 33px #8bc220, 34px 34px #8bc220, 35px 35px #8bc220, 36px 36px #8bc220, 37px 37px #8bc220, 38px 38px #8bc220, 39px 39px #8bc220, 40px 40px #8bc220, 41px 41px #8bc220, 42px 42px #8bc220, 43px 43px #8bc220, 44px 44px #8bc220, 45px 45px #8bc220, 46px 46px #8bc220, 47px 47px #8bc220, 48px 48px #8bc220, 49px 49px #8bc220, 50px 50px #8bc220, 51px 51px #8bc220, 52px 52px #8bc220, 53px 53px #8bc220, 54px 54px #8bc220, 55px 55px #8bc220, 56px 56px #8bc220, 57px 57px #8bc220, 58px 58px #8bc220, 59px 59px #8bc220, 60px 60px #8bc220, 61px 61px #8bc220, 62px 62px #8bc220, 63px 63px #8bc220, 64px 64px #8bc220, 65px 65px #8bc220, 66px 66px #8bc220, 67px 67px #8bc220, 68px 68px #8bc220, 69px 69px #8bc220, 70px 70px #8bc220, 71px 71px #8bc220, 72px 72px #8bc220, 73px 73px #8bc220, 74px 74px #8bc220, 75px 75px #8bc220, 76px 76px #8bc220, 77px 77px #8bc220, 78px 78px #8bc220, 79px 79px #8bc220, 80px 80px #8bc220, 81px 81px #8bc220, 82px 82px #8bc220, 83px 83px #8bc220, 84px 84px #8bc220, 85px 85px #8bc220; }\r\n    .button-longshadow.button-action:active, .button-longshadow.button-action.active, .button-longshadow.button-action.is-active,\r\n    .button-longshadow-right.button-action:active,\r\n    .button-longshadow-right.button-action.active,\r\n    .button-longshadow-right.button-action.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow.button-highlight,\r\n  .button-longshadow-right.button-highlight {\r\n    text-shadow: 0px 0px #e59501, 1px 1px #e59501, 2px 2px #e59501, 3px 3px #e59501, 4px 4px #e59501, 5px 5px #e59501, 6px 6px #e59501, 7px 7px #e59501, 8px 8px #e59501, 9px 9px #e59501, 10px 10px #e59501, 11px 11px #e59501, 12px 12px #e59501, 13px 13px #e59501, 14px 14px #e59501, 15px 15px #e59501, 16px 16px #e59501, 17px 17px #e59501, 18px 18px #e59501, 19px 19px #e59501, 20px 20px #e59501, 21px 21px #e59501, 22px 22px #e59501, 23px 23px #e59501, 24px 24px #e59501, 25px 25px #e59501, 26px 26px #e59501, 27px 27px #e59501, 28px 28px #e59501, 29px 29px #e59501, 30px 30px #e59501, 31px 31px #e59501, 32px 32px #e59501, 33px 33px #e59501, 34px 34px #e59501, 35px 35px #e59501, 36px 36px #e59501, 37px 37px #e59501, 38px 38px #e59501, 39px 39px #e59501, 40px 40px #e59501, 41px 41px #e59501, 42px 42px #e59501, 43px 43px #e59501, 44px 44px #e59501, 45px 45px #e59501, 46px 46px #e59501, 47px 47px #e59501, 48px 48px #e59501, 49px 49px #e59501, 50px 50px #e59501, 51px 51px #e59501, 52px 52px #e59501, 53px 53px #e59501, 54px 54px #e59501, 55px 55px #e59501, 56px 56px #e59501, 57px 57px #e59501, 58px 58px #e59501, 59px 59px #e59501, 60px 60px #e59501, 61px 61px #e59501, 62px 62px #e59501, 63px 63px #e59501, 64px 64px #e59501, 65px 65px #e59501, 66px 66px #e59501, 67px 67px #e59501, 68px 68px #e59501, 69px 69px #e59501, 70px 70px #e59501, 71px 71px #e59501, 72px 72px #e59501, 73px 73px #e59501, 74px 74px #e59501, 75px 75px #e59501, 76px 76px #e59501, 77px 77px #e59501, 78px 78px #e59501, 79px 79px #e59501, 80px 80px #e59501, 81px 81px #e59501, 82px 82px #e59501, 83px 83px #e59501, 84px 84px #e59501, 85px 85px #e59501; }\r\n    .button-longshadow.button-highlight:active, .button-longshadow.button-highlight.active, .button-longshadow.button-highlight.is-active,\r\n    .button-longshadow-right.button-highlight:active,\r\n    .button-longshadow-right.button-highlight.active,\r\n    .button-longshadow-right.button-highlight.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow.button-caution,\r\n  .button-longshadow-right.button-caution {\r\n    text-shadow: 0px 0px #ff1022, 1px 1px #ff1022, 2px 2px #ff1022, 3px 3px #ff1022, 4px 4px #ff1022, 5px 5px #ff1022, 6px 6px #ff1022, 7px 7px #ff1022, 8px 8px #ff1022, 9px 9px #ff1022, 10px 10px #ff1022, 11px 11px #ff1022, 12px 12px #ff1022, 13px 13px #ff1022, 14px 14px #ff1022, 15px 15px #ff1022, 16px 16px #ff1022, 17px 17px #ff1022, 18px 18px #ff1022, 19px 19px #ff1022, 20px 20px #ff1022, 21px 21px #ff1022, 22px 22px #ff1022, 23px 23px #ff1022, 24px 24px #ff1022, 25px 25px #ff1022, 26px 26px #ff1022, 27px 27px #ff1022, 28px 28px #ff1022, 29px 29px #ff1022, 30px 30px #ff1022, 31px 31px #ff1022, 32px 32px #ff1022, 33px 33px #ff1022, 34px 34px #ff1022, 35px 35px #ff1022, 36px 36px #ff1022, 37px 37px #ff1022, 38px 38px #ff1022, 39px 39px #ff1022, 40px 40px #ff1022, 41px 41px #ff1022, 42px 42px #ff1022, 43px 43px #ff1022, 44px 44px #ff1022, 45px 45px #ff1022, 46px 46px #ff1022, 47px 47px #ff1022, 48px 48px #ff1022, 49px 49px #ff1022, 50px 50px #ff1022, 51px 51px #ff1022, 52px 52px #ff1022, 53px 53px #ff1022, 54px 54px #ff1022, 55px 55px #ff1022, 56px 56px #ff1022, 57px 57px #ff1022, 58px 58px #ff1022, 59px 59px #ff1022, 60px 60px #ff1022, 61px 61px #ff1022, 62px 62px #ff1022, 63px 63px #ff1022, 64px 64px #ff1022, 65px 65px #ff1022, 66px 66px #ff1022, 67px 67px #ff1022, 68px 68px #ff1022, 69px 69px #ff1022, 70px 70px #ff1022, 71px 71px #ff1022, 72px 72px #ff1022, 73px 73px #ff1022, 74px 74px #ff1022, 75px 75px #ff1022, 76px 76px #ff1022, 77px 77px #ff1022, 78px 78px #ff1022, 79px 79px #ff1022, 80px 80px #ff1022, 81px 81px #ff1022, 82px 82px #ff1022, 83px 83px #ff1022, 84px 84px #ff1022, 85px 85px #ff1022; }\r\n    .button-longshadow.button-caution:active, .button-longshadow.button-caution.active, .button-longshadow.button-caution.is-active,\r\n    .button-longshadow-right.button-caution:active,\r\n    .button-longshadow-right.button-caution.active,\r\n    .button-longshadow-right.button-caution.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow.button-royal,\r\n  .button-longshadow-right.button-royal {\r\n    text-shadow: 0px 0px #5246e2, 1px 1px #5246e2, 2px 2px #5246e2, 3px 3px #5246e2, 4px 4px #5246e2, 5px 5px #5246e2, 6px 6px #5246e2, 7px 7px #5246e2, 8px 8px #5246e2, 9px 9px #5246e2, 10px 10px #5246e2, 11px 11px #5246e2, 12px 12px #5246e2, 13px 13px #5246e2, 14px 14px #5246e2, 15px 15px #5246e2, 16px 16px #5246e2, 17px 17px #5246e2, 18px 18px #5246e2, 19px 19px #5246e2, 20px 20px #5246e2, 21px 21px #5246e2, 22px 22px #5246e2, 23px 23px #5246e2, 24px 24px #5246e2, 25px 25px #5246e2, 26px 26px #5246e2, 27px 27px #5246e2, 28px 28px #5246e2, 29px 29px #5246e2, 30px 30px #5246e2, 31px 31px #5246e2, 32px 32px #5246e2, 33px 33px #5246e2, 34px 34px #5246e2, 35px 35px #5246e2, 36px 36px #5246e2, 37px 37px #5246e2, 38px 38px #5246e2, 39px 39px #5246e2, 40px 40px #5246e2, 41px 41px #5246e2, 42px 42px #5246e2, 43px 43px #5246e2, 44px 44px #5246e2, 45px 45px #5246e2, 46px 46px #5246e2, 47px 47px #5246e2, 48px 48px #5246e2, 49px 49px #5246e2, 50px 50px #5246e2, 51px 51px #5246e2, 52px 52px #5246e2, 53px 53px #5246e2, 54px 54px #5246e2, 55px 55px #5246e2, 56px 56px #5246e2, 57px 57px #5246e2, 58px 58px #5246e2, 59px 59px #5246e2, 60px 60px #5246e2, 61px 61px #5246e2, 62px 62px #5246e2, 63px 63px #5246e2, 64px 64px #5246e2, 65px 65px #5246e2, 66px 66px #5246e2, 67px 67px #5246e2, 68px 68px #5246e2, 69px 69px #5246e2, 70px 70px #5246e2, 71px 71px #5246e2, 72px 72px #5246e2, 73px 73px #5246e2, 74px 74px #5246e2, 75px 75px #5246e2, 76px 76px #5246e2, 77px 77px #5246e2, 78px 78px #5246e2, 79px 79px #5246e2, 80px 80px #5246e2, 81px 81px #5246e2, 82px 82px #5246e2, 83px 83px #5246e2, 84px 84px #5246e2, 85px 85px #5246e2; }\r\n    .button-longshadow.button-royal:active, .button-longshadow.button-royal.active, .button-longshadow.button-royal.is-active,\r\n    .button-longshadow-right.button-royal:active,\r\n    .button-longshadow-right.button-royal.active,\r\n    .button-longshadow-right.button-royal.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n\r\n/*\r\n* Shadow Left\r\n*\r\n*/\r\n.button-longshadow-left {\r\n  overflow: hidden; }\r\n  .button-longshadow-left.button-primary {\r\n    text-shadow: 0px 0px #0880d7, -1px 1px #0880d7, -2px 2px #0880d7, -3px 3px #0880d7, -4px 4px #0880d7, -5px 5px #0880d7, -6px 6px #0880d7, -7px 7px #0880d7, -8px 8px #0880d7, -9px 9px #0880d7, -10px 10px #0880d7, -11px 11px #0880d7, -12px 12px #0880d7, -13px 13px #0880d7, -14px 14px #0880d7, -15px 15px #0880d7, -16px 16px #0880d7, -17px 17px #0880d7, -18px 18px #0880d7, -19px 19px #0880d7, -20px 20px #0880d7, -21px 21px #0880d7, -22px 22px #0880d7, -23px 23px #0880d7, -24px 24px #0880d7, -25px 25px #0880d7, -26px 26px #0880d7, -27px 27px #0880d7, -28px 28px #0880d7, -29px 29px #0880d7, -30px 30px #0880d7, -31px 31px #0880d7, -32px 32px #0880d7, -33px 33px #0880d7, -34px 34px #0880d7, -35px 35px #0880d7, -36px 36px #0880d7, -37px 37px #0880d7, -38px 38px #0880d7, -39px 39px #0880d7, -40px 40px #0880d7, -41px 41px #0880d7, -42px 42px #0880d7, -43px 43px #0880d7, -44px 44px #0880d7, -45px 45px #0880d7, -46px 46px #0880d7, -47px 47px #0880d7, -48px 48px #0880d7, -49px 49px #0880d7, -50px 50px #0880d7, -51px 51px #0880d7, -52px 52px #0880d7, -53px 53px #0880d7, -54px 54px #0880d7, -55px 55px #0880d7, -56px 56px #0880d7, -57px 57px #0880d7, -58px 58px #0880d7, -59px 59px #0880d7, -60px 60px #0880d7, -61px 61px #0880d7, -62px 62px #0880d7, -63px 63px #0880d7, -64px 64px #0880d7, -65px 65px #0880d7, -66px 66px #0880d7, -67px 67px #0880d7, -68px 68px #0880d7, -69px 69px #0880d7, -70px 70px #0880d7, -71px 71px #0880d7, -72px 72px #0880d7, -73px 73px #0880d7, -74px 74px #0880d7, -75px 75px #0880d7, -76px 76px #0880d7, -77px 77px #0880d7, -78px 78px #0880d7, -79px 79px #0880d7, -80px 80px #0880d7, -81px 81px #0880d7, -82px 82px #0880d7, -83px 83px #0880d7, -84px 84px #0880d7, -85px 85px #0880d7; }\r\n    .button-longshadow-left.button-primary:active, .button-longshadow-left.button-primary.active, .button-longshadow-left.button-primary.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow-left.button-plain {\r\n    text-shadow: 0px 0px #e6e6e6, -1px 1px #e6e6e6, -2px 2px #e6e6e6, -3px 3px #e6e6e6, -4px 4px #e6e6e6, -5px 5px #e6e6e6, -6px 6px #e6e6e6, -7px 7px #e6e6e6, -8px 8px #e6e6e6, -9px 9px #e6e6e6, -10px 10px #e6e6e6, -11px 11px #e6e6e6, -12px 12px #e6e6e6, -13px 13px #e6e6e6, -14px 14px #e6e6e6, -15px 15px #e6e6e6, -16px 16px #e6e6e6, -17px 17px #e6e6e6, -18px 18px #e6e6e6, -19px 19px #e6e6e6, -20px 20px #e6e6e6, -21px 21px #e6e6e6, -22px 22px #e6e6e6, -23px 23px #e6e6e6, -24px 24px #e6e6e6, -25px 25px #e6e6e6, -26px 26px #e6e6e6, -27px 27px #e6e6e6, -28px 28px #e6e6e6, -29px 29px #e6e6e6, -30px 30px #e6e6e6, -31px 31px #e6e6e6, -32px 32px #e6e6e6, -33px 33px #e6e6e6, -34px 34px #e6e6e6, -35px 35px #e6e6e6, -36px 36px #e6e6e6, -37px 37px #e6e6e6, -38px 38px #e6e6e6, -39px 39px #e6e6e6, -40px 40px #e6e6e6, -41px 41px #e6e6e6, -42px 42px #e6e6e6, -43px 43px #e6e6e6, -44px 44px #e6e6e6, -45px 45px #e6e6e6, -46px 46px #e6e6e6, -47px 47px #e6e6e6, -48px 48px #e6e6e6, -49px 49px #e6e6e6, -50px 50px #e6e6e6, -51px 51px #e6e6e6, -52px 52px #e6e6e6, -53px 53px #e6e6e6, -54px 54px #e6e6e6, -55px 55px #e6e6e6, -56px 56px #e6e6e6, -57px 57px #e6e6e6, -58px 58px #e6e6e6, -59px 59px #e6e6e6, -60px 60px #e6e6e6, -61px 61px #e6e6e6, -62px 62px #e6e6e6, -63px 63px #e6e6e6, -64px 64px #e6e6e6, -65px 65px #e6e6e6, -66px 66px #e6e6e6, -67px 67px #e6e6e6, -68px 68px #e6e6e6, -69px 69px #e6e6e6, -70px 70px #e6e6e6, -71px 71px #e6e6e6, -72px 72px #e6e6e6, -73px 73px #e6e6e6, -74px 74px #e6e6e6, -75px 75px #e6e6e6, -76px 76px #e6e6e6, -77px 77px #e6e6e6, -78px 78px #e6e6e6, -79px 79px #e6e6e6, -80px 80px #e6e6e6, -81px 81px #e6e6e6, -82px 82px #e6e6e6, -83px 83px #e6e6e6, -84px 84px #e6e6e6, -85px 85px #e6e6e6; }\r\n    .button-longshadow-left.button-plain:active, .button-longshadow-left.button-plain.active, .button-longshadow-left.button-plain.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow-left.button-inverse {\r\n    text-shadow: 0px 0px #090909, -1px 1px #090909, -2px 2px #090909, -3px 3px #090909, -4px 4px #090909, -5px 5px #090909, -6px 6px #090909, -7px 7px #090909, -8px 8px #090909, -9px 9px #090909, -10px 10px #090909, -11px 11px #090909, -12px 12px #090909, -13px 13px #090909, -14px 14px #090909, -15px 15px #090909, -16px 16px #090909, -17px 17px #090909, -18px 18px #090909, -19px 19px #090909, -20px 20px #090909, -21px 21px #090909, -22px 22px #090909, -23px 23px #090909, -24px 24px #090909, -25px 25px #090909, -26px 26px #090909, -27px 27px #090909, -28px 28px #090909, -29px 29px #090909, -30px 30px #090909, -31px 31px #090909, -32px 32px #090909, -33px 33px #090909, -34px 34px #090909, -35px 35px #090909, -36px 36px #090909, -37px 37px #090909, -38px 38px #090909, -39px 39px #090909, -40px 40px #090909, -41px 41px #090909, -42px 42px #090909, -43px 43px #090909, -44px 44px #090909, -45px 45px #090909, -46px 46px #090909, -47px 47px #090909, -48px 48px #090909, -49px 49px #090909, -50px 50px #090909, -51px 51px #090909, -52px 52px #090909, -53px 53px #090909, -54px 54px #090909, -55px 55px #090909, -56px 56px #090909, -57px 57px #090909, -58px 58px #090909, -59px 59px #090909, -60px 60px #090909, -61px 61px #090909, -62px 62px #090909, -63px 63px #090909, -64px 64px #090909, -65px 65px #090909, -66px 66px #090909, -67px 67px #090909, -68px 68px #090909, -69px 69px #090909, -70px 70px #090909, -71px 71px #090909, -72px 72px #090909, -73px 73px #090909, -74px 74px #090909, -75px 75px #090909, -76px 76px #090909, -77px 77px #090909, -78px 78px #090909, -79px 79px #090909, -80px 80px #090909, -81px 81px #090909, -82px 82px #090909, -83px 83px #090909, -84px 84px #090909, -85px 85px #090909; }\r\n    .button-longshadow-left.button-inverse:active, .button-longshadow-left.button-inverse.active, .button-longshadow-left.button-inverse.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow-left.button-action {\r\n    text-shadow: 0px 0px #8bc220, -1px 1px #8bc220, -2px 2px #8bc220, -3px 3px #8bc220, -4px 4px #8bc220, -5px 5px #8bc220, -6px 6px #8bc220, -7px 7px #8bc220, -8px 8px #8bc220, -9px 9px #8bc220, -10px 10px #8bc220, -11px 11px #8bc220, -12px 12px #8bc220, -13px 13px #8bc220, -14px 14px #8bc220, -15px 15px #8bc220, -16px 16px #8bc220, -17px 17px #8bc220, -18px 18px #8bc220, -19px 19px #8bc220, -20px 20px #8bc220, -21px 21px #8bc220, -22px 22px #8bc220, -23px 23px #8bc220, -24px 24px #8bc220, -25px 25px #8bc220, -26px 26px #8bc220, -27px 27px #8bc220, -28px 28px #8bc220, -29px 29px #8bc220, -30px 30px #8bc220, -31px 31px #8bc220, -32px 32px #8bc220, -33px 33px #8bc220, -34px 34px #8bc220, -35px 35px #8bc220, -36px 36px #8bc220, -37px 37px #8bc220, -38px 38px #8bc220, -39px 39px #8bc220, -40px 40px #8bc220, -41px 41px #8bc220, -42px 42px #8bc220, -43px 43px #8bc220, -44px 44px #8bc220, -45px 45px #8bc220, -46px 46px #8bc220, -47px 47px #8bc220, -48px 48px #8bc220, -49px 49px #8bc220, -50px 50px #8bc220, -51px 51px #8bc220, -52px 52px #8bc220, -53px 53px #8bc220, -54px 54px #8bc220, -55px 55px #8bc220, -56px 56px #8bc220, -57px 57px #8bc220, -58px 58px #8bc220, -59px 59px #8bc220, -60px 60px #8bc220, -61px 61px #8bc220, -62px 62px #8bc220, -63px 63px #8bc220, -64px 64px #8bc220, -65px 65px #8bc220, -66px 66px #8bc220, -67px 67px #8bc220, -68px 68px #8bc220, -69px 69px #8bc220, -70px 70px #8bc220, -71px 71px #8bc220, -72px 72px #8bc220, -73px 73px #8bc220, -74px 74px #8bc220, -75px 75px #8bc220, -76px 76px #8bc220, -77px 77px #8bc220, -78px 78px #8bc220, -79px 79px #8bc220, -80px 80px #8bc220, -81px 81px #8bc220, -82px 82px #8bc220, -83px 83px #8bc220, -84px 84px #8bc220, -85px 85px #8bc220; }\r\n    .button-longshadow-left.button-action:active, .button-longshadow-left.button-action.active, .button-longshadow-left.button-action.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow-left.button-highlight {\r\n    text-shadow: 0px 0px #e59501, -1px 1px #e59501, -2px 2px #e59501, -3px 3px #e59501, -4px 4px #e59501, -5px 5px #e59501, -6px 6px #e59501, -7px 7px #e59501, -8px 8px #e59501, -9px 9px #e59501, -10px 10px #e59501, -11px 11px #e59501, -12px 12px #e59501, -13px 13px #e59501, -14px 14px #e59501, -15px 15px #e59501, -16px 16px #e59501, -17px 17px #e59501, -18px 18px #e59501, -19px 19px #e59501, -20px 20px #e59501, -21px 21px #e59501, -22px 22px #e59501, -23px 23px #e59501, -24px 24px #e59501, -25px 25px #e59501, -26px 26px #e59501, -27px 27px #e59501, -28px 28px #e59501, -29px 29px #e59501, -30px 30px #e59501, -31px 31px #e59501, -32px 32px #e59501, -33px 33px #e59501, -34px 34px #e59501, -35px 35px #e59501, -36px 36px #e59501, -37px 37px #e59501, -38px 38px #e59501, -39px 39px #e59501, -40px 40px #e59501, -41px 41px #e59501, -42px 42px #e59501, -43px 43px #e59501, -44px 44px #e59501, -45px 45px #e59501, -46px 46px #e59501, -47px 47px #e59501, -48px 48px #e59501, -49px 49px #e59501, -50px 50px #e59501, -51px 51px #e59501, -52px 52px #e59501, -53px 53px #e59501, -54px 54px #e59501, -55px 55px #e59501, -56px 56px #e59501, -57px 57px #e59501, -58px 58px #e59501, -59px 59px #e59501, -60px 60px #e59501, -61px 61px #e59501, -62px 62px #e59501, -63px 63px #e59501, -64px 64px #e59501, -65px 65px #e59501, -66px 66px #e59501, -67px 67px #e59501, -68px 68px #e59501, -69px 69px #e59501, -70px 70px #e59501, -71px 71px #e59501, -72px 72px #e59501, -73px 73px #e59501, -74px 74px #e59501, -75px 75px #e59501, -76px 76px #e59501, -77px 77px #e59501, -78px 78px #e59501, -79px 79px #e59501, -80px 80px #e59501, -81px 81px #e59501, -82px 82px #e59501, -83px 83px #e59501, -84px 84px #e59501, -85px 85px #e59501; }\r\n    .button-longshadow-left.button-highlight:active, .button-longshadow-left.button-highlight.active, .button-longshadow-left.button-highlight.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow-left.button-caution {\r\n    text-shadow: 0px 0px #ff1022, -1px 1px #ff1022, -2px 2px #ff1022, -3px 3px #ff1022, -4px 4px #ff1022, -5px 5px #ff1022, -6px 6px #ff1022, -7px 7px #ff1022, -8px 8px #ff1022, -9px 9px #ff1022, -10px 10px #ff1022, -11px 11px #ff1022, -12px 12px #ff1022, -13px 13px #ff1022, -14px 14px #ff1022, -15px 15px #ff1022, -16px 16px #ff1022, -17px 17px #ff1022, -18px 18px #ff1022, -19px 19px #ff1022, -20px 20px #ff1022, -21px 21px #ff1022, -22px 22px #ff1022, -23px 23px #ff1022, -24px 24px #ff1022, -25px 25px #ff1022, -26px 26px #ff1022, -27px 27px #ff1022, -28px 28px #ff1022, -29px 29px #ff1022, -30px 30px #ff1022, -31px 31px #ff1022, -32px 32px #ff1022, -33px 33px #ff1022, -34px 34px #ff1022, -35px 35px #ff1022, -36px 36px #ff1022, -37px 37px #ff1022, -38px 38px #ff1022, -39px 39px #ff1022, -40px 40px #ff1022, -41px 41px #ff1022, -42px 42px #ff1022, -43px 43px #ff1022, -44px 44px #ff1022, -45px 45px #ff1022, -46px 46px #ff1022, -47px 47px #ff1022, -48px 48px #ff1022, -49px 49px #ff1022, -50px 50px #ff1022, -51px 51px #ff1022, -52px 52px #ff1022, -53px 53px #ff1022, -54px 54px #ff1022, -55px 55px #ff1022, -56px 56px #ff1022, -57px 57px #ff1022, -58px 58px #ff1022, -59px 59px #ff1022, -60px 60px #ff1022, -61px 61px #ff1022, -62px 62px #ff1022, -63px 63px #ff1022, -64px 64px #ff1022, -65px 65px #ff1022, -66px 66px #ff1022, -67px 67px #ff1022, -68px 68px #ff1022, -69px 69px #ff1022, -70px 70px #ff1022, -71px 71px #ff1022, -72px 72px #ff1022, -73px 73px #ff1022, -74px 74px #ff1022, -75px 75px #ff1022, -76px 76px #ff1022, -77px 77px #ff1022, -78px 78px #ff1022, -79px 79px #ff1022, -80px 80px #ff1022, -81px 81px #ff1022, -82px 82px #ff1022, -83px 83px #ff1022, -84px 84px #ff1022, -85px 85px #ff1022; }\r\n    .button-longshadow-left.button-caution:active, .button-longshadow-left.button-caution.active, .button-longshadow-left.button-caution.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n  .button-longshadow-left.button-royal {\r\n    text-shadow: 0px 0px #5246e2, -1px 1px #5246e2, -2px 2px #5246e2, -3px 3px #5246e2, -4px 4px #5246e2, -5px 5px #5246e2, -6px 6px #5246e2, -7px 7px #5246e2, -8px 8px #5246e2, -9px 9px #5246e2, -10px 10px #5246e2, -11px 11px #5246e2, -12px 12px #5246e2, -13px 13px #5246e2, -14px 14px #5246e2, -15px 15px #5246e2, -16px 16px #5246e2, -17px 17px #5246e2, -18px 18px #5246e2, -19px 19px #5246e2, -20px 20px #5246e2, -21px 21px #5246e2, -22px 22px #5246e2, -23px 23px #5246e2, -24px 24px #5246e2, -25px 25px #5246e2, -26px 26px #5246e2, -27px 27px #5246e2, -28px 28px #5246e2, -29px 29px #5246e2, -30px 30px #5246e2, -31px 31px #5246e2, -32px 32px #5246e2, -33px 33px #5246e2, -34px 34px #5246e2, -35px 35px #5246e2, -36px 36px #5246e2, -37px 37px #5246e2, -38px 38px #5246e2, -39px 39px #5246e2, -40px 40px #5246e2, -41px 41px #5246e2, -42px 42px #5246e2, -43px 43px #5246e2, -44px 44px #5246e2, -45px 45px #5246e2, -46px 46px #5246e2, -47px 47px #5246e2, -48px 48px #5246e2, -49px 49px #5246e2, -50px 50px #5246e2, -51px 51px #5246e2, -52px 52px #5246e2, -53px 53px #5246e2, -54px 54px #5246e2, -55px 55px #5246e2, -56px 56px #5246e2, -57px 57px #5246e2, -58px 58px #5246e2, -59px 59px #5246e2, -60px 60px #5246e2, -61px 61px #5246e2, -62px 62px #5246e2, -63px 63px #5246e2, -64px 64px #5246e2, -65px 65px #5246e2, -66px 66px #5246e2, -67px 67px #5246e2, -68px 68px #5246e2, -69px 69px #5246e2, -70px 70px #5246e2, -71px 71px #5246e2, -72px 72px #5246e2, -73px 73px #5246e2, -74px 74px #5246e2, -75px 75px #5246e2, -76px 76px #5246e2, -77px 77px #5246e2, -78px 78px #5246e2, -79px 79px #5246e2, -80px 80px #5246e2, -81px 81px #5246e2, -82px 82px #5246e2, -83px 83px #5246e2, -84px 84px #5246e2, -85px 85px #5246e2; }\r\n    .button-longshadow-left.button-royal:active, .button-longshadow-left.button-royal.active, .button-longshadow-left.button-royal.is-active {\r\n      text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); }\r\n\r\n/*\r\n* Button Sizes\r\n*\r\n* This file creates the various button sizes\r\n* (ex. .button-large, .button-small, etc.)\r\n*/\r\n.button-giant {\r\n  font-size: 28px;\r\n  height: 70px;\r\n  line-height: 70px;\r\n  padding: 0 70px; }\r\n\r\n.button-jumbo {\r\n  font-size: 24px;\r\n  height: 60px;\r\n  line-height: 60px;\r\n  padding: 0 60px; }\r\n\r\n.button-large {\r\n  font-size: 20px;\r\n  height: 50px;\r\n  line-height: 50px;\r\n  padding: 0 50px; }\r\n\r\n.button-normal {\r\n  font-size: 16px;\r\n  height: 40px;\r\n  line-height: 40px;\r\n  padding: 0 40px; }\r\n\r\n.button-small {\r\n  font-size: 12px;\r\n  height: 30px;\r\n  line-height: 30px;\r\n  padding: 0 30px; }\r\n\r\n.button-tiny {\r\n  font-size: 9.6px;\r\n  height: 24px;\r\n  line-height: 24px;\r\n  padding: 0 24px; }\r\n", ""]);

// exports


/***/ }),

/***/ 45:
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(40);
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
		module.hot.accept("!!../../node_modules/css-loader/index.js!./buttons.css", function() {
			var newContent = require("!!../../node_modules/css-loader/index.js!./buttons.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}

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

/***/ 7:
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".dialog.mask{position:fixed;background:rgba(0,0,0,.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:1060}.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}.dialog .dlg-header{padding:15px 15px 0 15px}.dialog .dlg-body{padding:15px 15px 0 15px}.dialog .dlg-footer{text-align:right;padding:15px}.dialog .dlg-footer button{border-radius:0}div[wrap=filter] .detail{background:#ccc}div[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}div[wrap=filter] .detail .actions .btn{border-radius:0}.tms-act-toggle{position:fixed;right:15px;bottom:8px;width:48px;height:48px;line-height:48px;box-shadow:0 2px 6px rgba(18,27,32,.425);color:#fff;background:#ff8018;border:1px solid #ff8018;border-radius:24px;font-size:20px;text-align:center;cursor:pointer;z-index:1050}.tms-nav-target>*+*{margin-top:.5em}.tms-act-popover-wrap>div+div{margin-top:8px}#frmPlugin{position:absolute;top:0;bottom:0;left:0;right:0;width:100%;height:100%;border:none;z-index:1060;box-sizing:border-box;padding-bottom:48px;background:#fff}#frmPlugin iframe{width:100%;height:100%;border:0}#frmPlugin:after{content:'\\5173\\95ED';position:absolute;width:100px;text-align:center;left:50%;margin-left:-50px;bottom:4px;padding:5px 6px 3px;border:1px solid #ccc;border-radius:4px}div[wrap]>.description{word-wrap:break-word}", ""]);

// exports


/***/ }),

/***/ 77:
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(45);
__webpack_require__(28);

__webpack_require__(34);
__webpack_require__(123);
__webpack_require__(35);
__webpack_require__(37);

window.moduleAngularModules = ['repos.ui.enroll', 'score.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll'];

var ngApp = __webpack_require__(18);
ngApp.controller('ctrlTopic', ['$scope', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'picviewer', 'noticebox', function($scope, $sce, $q, $uibModal, http2, LS, $timeout, picviewer, noticebox) {
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

    function fnGetTopic() {
        var url;
        url = LS.j('topic/get', 'site', 'app', 'topic');
        return http2.get(url);
    }

    $scope.shareTopic = function() {
        var url, shareby;
        url = LS.j('', 'site', 'app') + '&topic=' + $scope.topic.id + '&page=share';
        if (_shareby) {
            url += '&shareby=' + _shareby;
        }
        location.href = url;
    };
    $scope.quitTopic = function(oRecord) {
        http2.post(LS.j('topic/removeRec', 'site') + '&topic=' + $scope.topic.id, { id_in_topic: oRecord.id_in_topic }).then(function(rsp) {
            $scope.repos.splice($scope.repos.indexOf(oRecord), 1);
            _oPage.total--;
        });
    };
    $scope.coworkRecord = function(event, oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork';
        location.href = url;
    };

    var _oApp, _oPage, _shareby;
    _shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
    $scope.page = _oPage = {};
    $scope.repos = []; // 分享的记录
    $scope.reposLoading = false;
    $scope.recordList = function(pageAt) {
        var url, deferred;
        deferred = $q.defer();
        if (pageAt) {
            _oPage.at = pageAt;
        } else {
            _oPage.at++;
        }
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/recordByTopic', 'site', 'app', 'topic');
        $scope.reposLoading = true;
        http2.get(url, { page: _oPage }).then(function(result) {
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    oRecord._canAgree = fnCanAgreeRecord(oRecord, $scope.user);
                    $scope.repos.push(oRecord);
                });
            }
            $timeout(function() {
                var imgs;
                if (imgs = document.querySelectorAll('.data img')) {
                    picviewer.init(imgs);
                }
            });
            $scope.reposLoading = false;
            deferred.resolve(result);
        });

        return deferred.promise;
    };
    $scope.likeRecord = function(oRecord) {
        var url;
        url = LS.j('record/like', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.dislikeRecord = function(oRecord) {
        var url;
        url = LS.j('record/dislike', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.dislike_log = rsp.data.dislike_log;
            oRecord.dislike_num = rsp.data.dislike_num;
        });
    };
    $scope.remarkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork#remarks';
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
    $scope.favorRecord = function(oRecord) {
        var url;
        if (!oRecord.favored) {
            url = LS.j('favor/add', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.favored = true;
                noticebox.info('收藏成功');
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
    $scope.shareRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        if (_shareby) {
            url += '&shareby=' + _shareby;
        }
        location.href = url;
    };
    $scope.editRecord = function(event, oRecord) {
        if (oRecord.userid !== $scope.user.uid) {
            noticebox.warn('不允许编辑其他用户提交的记录');
            return;
        }
        var page;
        for (var i in $scope.app.pages) {
            var oPage = $scope.app.pages[i];
            if (oPage.type === 'I') {
                page = oPage.name;
                break;
            }
        }
        $scope.gotoPage(event, page, oRecord.enroll_key);
    };
    $scope.spyRecordsScroll = true; // 监控滚动事件
    $scope.recordsScrollToBottom = function() {
        if ($scope.repos.length < $scope.page.total) {
            $scope.recordList().then(function() {
                $timeout(function() {
                    if ($scope.repos.length < $scope.page.total) {
                        $scope.spyRecordsScroll = true;
                    }
                });
            });
        }
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var oApp;
        _oApp = oApp = params.app;
        $scope.schemas = oApp.dynaDataSchemas.filter(function(oSchema) { return oSchema.shareable && oSchema.shareable === 'Y'; });
        /*设置页面操作*/
        $scope.setPopAct(['addRecord'], 'topic');
        /*设置页面导航*/
        $scope.setPopNav(['repos'], 'topic');
        fnGetTopic().then(function(rsp) {
            $scope.topic = rsp.data;
            /* 设置页面分享信息 */
            $scope.setSnsShare(null, { topic: LS.s().topic }, { target_type: 'topic', target_id: rsp.data.id, title: rsp.data.title }); // 应该禁止分享
            /*页面阅读日志*/
            $scope.logAccess({ target_type: 'topic', target_id: rsp.data.id, title: rsp.data.title });
            $scope.recordList(1);
        });
    });
}]);

/***/ }),

/***/ 8:
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

/***/ 9:
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

/***/ })

/******/ });