!function(t){function e(r){if(n[r])return n[r].exports;var i=n[r]={i:r,l:!1,exports:{}};return t[r].call(i.exports,i,i.exports,e),i.l=!0,i.exports}var n={};e.m=t,e.c=n,e.i=function(t){return t},e.d=function(t,n,r){e.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:r})},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="",e(e.s=67)}([function(t,e,n){(function(e){function n(t,e){var n=t[1]||"",i=t[3];if(!i)return n;if(e){var o=r(i);return[n].concat(i.sources.map(function(t){return"/*# sourceURL="+i.sourceRoot+t+" */"})).concat([o]).join("\n")}return[n].join("\n")}function r(t){return"/*# sourceMappingURL=data:application/json;charset=utf-8;base64,"+new e(JSON.stringify(t)).toString("base64")+" */"}t.exports=function(t){var e=[];return e.toString=function(){return this.map(function(e){var r=n(e,t);return e[2]?"@media "+e[2]+"{"+r+"}":r}).join("")},e.i=function(t,n){"string"==typeof t&&(t=[[null,t,""]]);for(var r={},i=0;i<this.length;i++){var o=this[i][0];"number"==typeof o&&(r[o]=!0)}for(i=0;i<t.length;i++){var a=t[i];"number"==typeof a[0]&&r[a[0]]||(n&&!a[2]?a[2]=n:n&&(a[2]="("+a[2]+") and ("+n+")"),e.push(a))}},e}}).call(e,n(4).Buffer)},function(t,e,n){function r(t,e){for(var n=0;n<t.length;n++){var r=t[n],i=h[r.id];if(i){i.refs++;for(var o=0;o<i.parts.length;o++)i.parts[o](r.parts[o]);for(;o<r.parts.length;o++)i.parts.push(l(r.parts[o],e))}else{for(var a=[],o=0;o<r.parts.length;o++)a.push(l(r.parts[o],e));h[r.id]={id:r.id,refs:1,parts:a}}}}function i(t){for(var e=[],n={},r=0;r<t.length;r++){var i=t[r],o=i[0],a=i[1],s=i[2],c=i[3],u={css:a,media:s,sourceMap:c};n[o]?n[o].parts.push(u):e.push(n[o]={id:o,parts:[u]})}return e}function o(t,e){var n=m(t.insertInto);if(!n)throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");var r=w[w.length-1];if("top"===t.insertAt)r?r.nextSibling?n.insertBefore(e,r.nextSibling):n.appendChild(e):n.insertBefore(e,n.firstChild),w.push(e);else{if("bottom"!==t.insertAt)throw new Error("Invalid value for parameter 'insertAt'. Must be 'top' or 'bottom'.");n.appendChild(e)}}function a(t){t.parentNode.removeChild(t);var e=w.indexOf(t);e>=0&&w.splice(e,1)}function s(t){var e=document.createElement("style");return t.attrs.type="text/css",u(e,t.attrs),o(t,e),e}function c(t){var e=document.createElement("link");return t.attrs.type="text/css",t.attrs.rel="stylesheet",u(e,t.attrs),o(t,e),e}function u(t,e){Object.keys(e).forEach(function(n){t.setAttribute(n,e[n])})}function l(t,e){var n,r,i;if(e.singleton){var o=y++;n=v||(v=s(e)),r=d.bind(null,n,o,!1),i=d.bind(null,n,o,!0)}else t.sourceMap&&"function"==typeof URL&&"function"==typeof URL.createObjectURL&&"function"==typeof URL.revokeObjectURL&&"function"==typeof Blob&&"function"==typeof btoa?(n=c(e),r=p.bind(null,n,e),i=function(){a(n),n.href&&URL.revokeObjectURL(n.href)}):(n=s(e),r=f.bind(null,n),i=function(){a(n)});return r(t),function(e){if(e){if(e.css===t.css&&e.media===t.media&&e.sourceMap===t.sourceMap)return;r(t=e)}else i()}}function d(t,e,n,r){var i=n?"":r.css;if(t.styleSheet)t.styleSheet.cssText=x(e,i);else{var o=document.createTextNode(i),a=t.childNodes;a[e]&&t.removeChild(a[e]),a.length?t.insertBefore(o,a[e]):t.appendChild(o)}}function f(t,e){var n=e.css,r=e.media;if(r&&t.setAttribute("media",r),t.styleSheet)t.styleSheet.cssText=n;else{for(;t.firstChild;)t.removeChild(t.firstChild);t.appendChild(document.createTextNode(n))}}function p(t,e,n){var r=n.css,i=n.sourceMap,o=void 0===e.convertToAbsoluteUrls&&i;(e.convertToAbsoluteUrls||o)&&(r=b(r)),i&&(r+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(i))))+" */");var a=new Blob([r],{type:"text/css"}),s=t.href;t.href=URL.createObjectURL(a),s&&URL.revokeObjectURL(s)}var h={},g=function(t){var e;return function(){return void 0===e&&(e=t.apply(this,arguments)),e}}(function(){return window&&document&&document.all&&!window.atob}),m=function(t){var e={};return function(n){return void 0===e[n]&&(e[n]=t.call(this,n)),e[n]}}(function(t){return document.querySelector(t)}),v=null,y=0,w=[],b=n(7);t.exports=function(t,e){if("undefined"!=typeof DEBUG&&DEBUG&&"object"!=typeof document)throw new Error("The style-loader cannot be used in a non-browser environment");e=e||{},e.attrs="object"==typeof e.attrs?e.attrs:{},void 0===e.singleton&&(e.singleton=g()),void 0===e.insertInto&&(e.insertInto="head"),void 0===e.insertAt&&(e.insertAt="bottom");var n=i(t);return r(n,e),function(t){for(var o=[],a=0;a<n.length;a++){var s=n[a],c=h[s.id];c.refs--,o.push(c)}if(t){r(i(t),e)}for(var a=0;a<o.length;a++){var c=o[a];if(0===c.refs){for(var u=0;u<c.parts.length;u++)c.parts[u]();delete h[c.id]}}}};var x=function(){var t=[];return function(e,n){return t[e]=n,t.filter(Boolean).join("\n")}}()},function(module,exports,__webpack_require__){"use strict";var ngMod=angular.module("page.ui.xxt",[]);ngMod.directive("dynamicHtml",["$compile",function(t){return{restrict:"EA",replace:!0,link:function(e,n,r){e.$watch(r.dynamicHtml,function(r){r&&r.length&&(n.html(r),t(n.contents())(e))})}}}]),ngMod.service("tmsDynaPage",["$q",function($q){this.loadCss=function(t){var e,n;e=document.createElement("style"),e.innerHTML=t,n=document.querySelector("head"),n.appendChild(e)},this.loadExtCss=function(t){var e,n;e=document.createElement("link"),e.href=t,e.rel="stylesheet",n=document.querySelector("head"),n.appendChild(e)},this.loadJs=function(ngApp,js){!function(ngApp){eval(js)}(ngApp)},this.loadScript=function(t){var e,n,r=$q.defer();return n=function(){var i;i=document.createElement("script"),i.src=t[e],i.onload=function(){e++,e<t.length?n():r.resolve()},document.body.appendChild(i)},t&&(angular.isString(t)&&(t=[t]),t.length&&(e=0,n())),r.promise},this.loadExtJs=function(t,e){var n,r=this,i=$q.defer(),o=e.ext_js.length;return n=function(n){var a;a=document.createElement("script"),a.src=n.url,a.onload=function(){0===--o&&(e.js&&e.js.length&&r.loadJs(t,e.js),i.resolve())},document.body.appendChild(a)},e.ext_js&&e.ext_js.length&&e.ext_js.forEach(n),i.promise},this.loadCode=function(t,e){var n=this,r=$q.defer();return e.ext_css&&e.ext_css.length&&e.ext_css.forEach(function(t){n.loadExtCss(t.url)}),e.css&&e.css.length&&this.loadCss(e.css),e.ext_js&&e.ext_js.length?n.loadExtJs(t,e).then(function(){r.resolve()}):(e.js&&e.js.length&&n.loadJs(t,e.js),r.resolve()),r.promise},this.openPlugin=function(t){var e,n,r,i=$q.defer();return e=document.createDocumentFragment(),n=document.createElement("div"),n.setAttribute("id","frmPlugin"),r=document.createElement("iframe"),n.appendChild(r),n.onclick=function(){n.parentNode.removeChild(n)},e.appendChild(n),document.body.appendChild(e),0===t.indexOf("http")?(window.onClosePlugin=function(t){n.parentNode.removeChild(n),i.resolve(t)},r.setAttribute("src",t)):r.contentDocument&&r.contentDocument.body&&(r.contentDocument.body.innerHTML=t),i.promise}}])},function(t,e,n){"use strict";function r(t){var e=t.length;if(e%4>0)throw new Error("Invalid string. Length must be a multiple of 4");return"="===t[e-2]?2:"="===t[e-1]?1:0}function i(t){return 3*t.length/4-r(t)}function o(t){var e,n,i,o,a,s,c=t.length;a=r(t),s=new d(3*c/4-a),i=a>0?c-4:c;var u=0;for(e=0,n=0;e<i;e+=4,n+=3)o=l[t.charCodeAt(e)]<<18|l[t.charCodeAt(e+1)]<<12|l[t.charCodeAt(e+2)]<<6|l[t.charCodeAt(e+3)],s[u++]=o>>16&255,s[u++]=o>>8&255,s[u++]=255&o;return 2===a?(o=l[t.charCodeAt(e)]<<2|l[t.charCodeAt(e+1)]>>4,s[u++]=255&o):1===a&&(o=l[t.charCodeAt(e)]<<10|l[t.charCodeAt(e+1)]<<4|l[t.charCodeAt(e+2)]>>2,s[u++]=o>>8&255,s[u++]=255&o),s}function a(t){return u[t>>18&63]+u[t>>12&63]+u[t>>6&63]+u[63&t]}function s(t,e,n){for(var r,i=[],o=e;o<n;o+=3)r=(t[o]<<16)+(t[o+1]<<8)+t[o+2],i.push(a(r));return i.join("")}function c(t){for(var e,n=t.length,r=n%3,i="",o=[],a=0,c=n-r;a<c;a+=16383)o.push(s(t,a,a+16383>c?c:a+16383));return 1===r?(e=t[n-1],i+=u[e>>2],i+=u[e<<4&63],i+="=="):2===r&&(e=(t[n-2]<<8)+t[n-1],i+=u[e>>10],i+=u[e>>4&63],i+=u[e<<2&63],i+="="),o.push(i),o.join("")}e.byteLength=i,e.toByteArray=o,e.fromByteArray=c;for(var u=[],l=[],d="undefined"!=typeof Uint8Array?Uint8Array:Array,f="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",p=0,h=f.length;p<h;++p)u[p]=f[p],l[f.charCodeAt(p)]=p;l["-".charCodeAt(0)]=62,l["_".charCodeAt(0)]=63},function(t,e,n){"use strict";(function(t){function r(){return o.TYPED_ARRAY_SUPPORT?2147483647:1073741823}function i(t,e){if(r()<e)throw new RangeError("Invalid typed array length");return o.TYPED_ARRAY_SUPPORT?(t=new Uint8Array(e),t.__proto__=o.prototype):(null===t&&(t=new o(e)),t.length=e),t}function o(t,e,n){if(!(o.TYPED_ARRAY_SUPPORT||this instanceof o))return new o(t,e,n);if("number"==typeof t){if("string"==typeof e)throw new Error("If encoding is specified then the first argument must be a string");return u(this,t)}return a(this,t,e,n)}function a(t,e,n,r){if("number"==typeof e)throw new TypeError('"value" argument must not be a number');return"undefined"!=typeof ArrayBuffer&&e instanceof ArrayBuffer?f(t,e,n,r):"string"==typeof e?l(t,e,n):p(t,e)}function s(t){if("number"!=typeof t)throw new TypeError('"size" argument must be a number');if(t<0)throw new RangeError('"size" argument must not be negative')}function c(t,e,n,r){return s(e),e<=0?i(t,e):void 0!==n?"string"==typeof r?i(t,e).fill(n,r):i(t,e).fill(n):i(t,e)}function u(t,e){if(s(e),t=i(t,e<0?0:0|h(e)),!o.TYPED_ARRAY_SUPPORT)for(var n=0;n<e;++n)t[n]=0;return t}function l(t,e,n){if("string"==typeof n&&""!==n||(n="utf8"),!o.isEncoding(n))throw new TypeError('"encoding" must be a valid string encoding');var r=0|m(e,n);t=i(t,r);var a=t.write(e,n);return a!==r&&(t=t.slice(0,a)),t}function d(t,e){var n=e.length<0?0:0|h(e.length);t=i(t,n);for(var r=0;r<n;r+=1)t[r]=255&e[r];return t}function f(t,e,n,r){if(e.byteLength,n<0||e.byteLength<n)throw new RangeError("'offset' is out of bounds");if(e.byteLength<n+(r||0))throw new RangeError("'length' is out of bounds");return e=void 0===n&&void 0===r?new Uint8Array(e):void 0===r?new Uint8Array(e,n):new Uint8Array(e,n,r),o.TYPED_ARRAY_SUPPORT?(t=e,t.__proto__=o.prototype):t=d(t,e),t}function p(t,e){if(o.isBuffer(e)){var n=0|h(e.length);return t=i(t,n),0===t.length?t:(e.copy(t,0,0,n),t)}if(e){if("undefined"!=typeof ArrayBuffer&&e.buffer instanceof ArrayBuffer||"length"in e)return"number"!=typeof e.length||X(e.length)?i(t,0):d(t,e);if("Buffer"===e.type&&Z(e.data))return d(t,e.data)}throw new TypeError("First argument must be a string, Buffer, ArrayBuffer, Array, or array-like object.")}function h(t){if(t>=r())throw new RangeError("Attempt to allocate Buffer larger than maximum size: 0x"+r().toString(16)+" bytes");return 0|t}function g(t){return+t!=t&&(t=0),o.alloc(+t)}function m(t,e){if(o.isBuffer(t))return t.length;if("undefined"!=typeof ArrayBuffer&&"function"==typeof ArrayBuffer.isView&&(ArrayBuffer.isView(t)||t instanceof ArrayBuffer))return t.byteLength;"string"!=typeof t&&(t=""+t);var n=t.length;if(0===n)return 0;for(var r=!1;;)switch(e){case"ascii":case"latin1":case"binary":return n;case"utf8":case"utf-8":case void 0:return z(t).length;case"ucs2":case"ucs-2":case"utf16le":case"utf-16le":return 2*n;case"hex":return n>>>1;case"base64":return G(t).length;default:if(r)return z(t).length;e=(""+e).toLowerCase(),r=!0}}function v(t,e,n){var r=!1;if((void 0===e||e<0)&&(e=0),e>this.length)return"";if((void 0===n||n>this.length)&&(n=this.length),n<=0)return"";if(n>>>=0,e>>>=0,n<=e)return"";for(t||(t="utf8");;)switch(t){case"hex":return U(this,e,n);case"utf8":case"utf-8":return R(this,e,n);case"ascii":return T(this,e,n);case"latin1":case"binary":return B(this,e,n);case"base64":return P(this,e,n);case"ucs2":case"ucs-2":case"utf16le":case"utf-16le":return $(this,e,n);default:if(r)throw new TypeError("Unknown encoding: "+t);t=(t+"").toLowerCase(),r=!0}}function y(t,e,n){var r=t[e];t[e]=t[n],t[n]=r}function w(t,e,n,r,i){if(0===t.length)return-1;if("string"==typeof n?(r=n,n=0):n>2147483647?n=2147483647:n<-2147483648&&(n=-2147483648),n=+n,isNaN(n)&&(n=i?0:t.length-1),n<0&&(n=t.length+n),n>=t.length){if(i)return-1;n=t.length-1}else if(n<0){if(!i)return-1;n=0}if("string"==typeof e&&(e=o.from(e,r)),o.isBuffer(e))return 0===e.length?-1:b(t,e,n,r,i);if("number"==typeof e)return e&=255,o.TYPED_ARRAY_SUPPORT&&"function"==typeof Uint8Array.prototype.indexOf?i?Uint8Array.prototype.indexOf.call(t,e,n):Uint8Array.prototype.lastIndexOf.call(t,e,n):b(t,[e],n,r,i);throw new TypeError("val must be string, number or Buffer")}function b(t,e,n,r,i){function o(t,e){return 1===a?t[e]:t.readUInt16BE(e*a)}var a=1,s=t.length,c=e.length;if(void 0!==r&&("ucs2"===(r=String(r).toLowerCase())||"ucs-2"===r||"utf16le"===r||"utf-16le"===r)){if(t.length<2||e.length<2)return-1;a=2,s/=2,c/=2,n/=2}var u;if(i){var l=-1;for(u=n;u<s;u++)if(o(t,u)===o(e,-1===l?0:u-l)){if(-1===l&&(l=u),u-l+1===c)return l*a}else-1!==l&&(u-=u-l),l=-1}else for(n+c>s&&(n=s-c),u=n;u>=0;u--){for(var d=!0,f=0;f<c;f++)if(o(t,u+f)!==o(e,f)){d=!1;break}if(d)return u}return-1}function x(t,e,n,r){n=Number(n)||0;var i=t.length-n;r?(r=Number(r))>i&&(r=i):r=i;var o=e.length;if(o%2!=0)throw new TypeError("Invalid hex string");r>o/2&&(r=o/2);for(var a=0;a<r;++a){var s=parseInt(e.substr(2*a,2),16);if(isNaN(s))return a;t[n+a]=s}return a}function _(t,e,n,r){return W(z(e,t.length-n),t,n,r)}function A(t,e,n,r){return W(J(e),t,n,r)}function E(t,e,n,r){return A(t,e,n,r)}function S(t,e,n,r){return W(G(e),t,n,r)}function k(t,e,n,r){return W(H(e,t.length-n),t,n,r)}function P(t,e,n){return 0===e&&n===t.length?K.fromByteArray(t):K.fromByteArray(t.slice(e,n))}function R(t,e,n){n=Math.min(t.length,n);for(var r=[],i=e;i<n;){var o=t[i],a=null,s=o>239?4:o>223?3:o>191?2:1;if(i+s<=n){var c,u,l,d;switch(s){case 1:o<128&&(a=o);break;case 2:c=t[i+1],128==(192&c)&&(d=(31&o)<<6|63&c)>127&&(a=d);break;case 3:c=t[i+1],u=t[i+2],128==(192&c)&&128==(192&u)&&(d=(15&o)<<12|(63&c)<<6|63&u)>2047&&(d<55296||d>57343)&&(a=d);break;case 4:c=t[i+1],u=t[i+2],l=t[i+3],128==(192&c)&&128==(192&u)&&128==(192&l)&&(d=(15&o)<<18|(63&c)<<12|(63&u)<<6|63&l)>65535&&d<1114112&&(a=d)}}null===a?(a=65533,s=1):a>65535&&(a-=65536,r.push(a>>>10&1023|55296),a=56320|1023&a),r.push(a),i+=s}return C(r)}function C(t){var e=t.length;if(e<=Q)return String.fromCharCode.apply(String,t);for(var n="",r=0;r<e;)n+=String.fromCharCode.apply(String,t.slice(r,r+=Q));return n}function T(t,e,n){var r="";n=Math.min(t.length,n);for(var i=e;i<n;++i)r+=String.fromCharCode(127&t[i]);return r}function B(t,e,n){var r="";n=Math.min(t.length,n);for(var i=e;i<n;++i)r+=String.fromCharCode(t[i]);return r}function U(t,e,n){var r=t.length;(!e||e<0)&&(e=0),(!n||n<0||n>r)&&(n=r);for(var i="",o=e;o<n;++o)i+=F(t[o]);return i}function $(t,e,n){for(var r=t.slice(e,n),i="",o=0;o<r.length;o+=2)i+=String.fromCharCode(r[o]+256*r[o+1]);return i}function I(t,e,n){if(t%1!=0||t<0)throw new RangeError("offset is not uint");if(t+e>n)throw new RangeError("Trying to access beyond buffer length")}function M(t,e,n,r,i,a){if(!o.isBuffer(t))throw new TypeError('"buffer" argument must be a Buffer instance');if(e>i||e<a)throw new RangeError('"value" argument is out of bounds');if(n+r>t.length)throw new RangeError("Index out of range")}function Y(t,e,n,r){e<0&&(e=65535+e+1);for(var i=0,o=Math.min(t.length-n,2);i<o;++i)t[n+i]=(e&255<<8*(r?i:1-i))>>>8*(r?i:1-i)}function L(t,e,n,r){e<0&&(e=4294967295+e+1);for(var i=0,o=Math.min(t.length-n,4);i<o;++i)t[n+i]=e>>>8*(r?i:3-i)&255}function O(t,e,n,r,i,o){if(n+r>t.length)throw new RangeError("Index out of range");if(n<0)throw new RangeError("Index out of range")}function D(t,e,n,r,i){return i||O(t,e,n,4,3.4028234663852886e38,-3.4028234663852886e38),V.write(t,e,n,r,23,4),n+4}function j(t,e,n,r,i){return i||O(t,e,n,8,1.7976931348623157e308,-1.7976931348623157e308),V.write(t,e,n,r,52,8),n+8}function N(t){if(t=q(t).replace(tt,""),t.length<2)return"";for(;t.length%4!=0;)t+="=";return t}function q(t){return t.trim?t.trim():t.replace(/^\s+|\s+$/g,"")}function F(t){return t<16?"0"+t.toString(16):t.toString(16)}function z(t,e){e=e||1/0;for(var n,r=t.length,i=null,o=[],a=0;a<r;++a){if((n=t.charCodeAt(a))>55295&&n<57344){if(!i){if(n>56319){(e-=3)>-1&&o.push(239,191,189);continue}if(a+1===r){(e-=3)>-1&&o.push(239,191,189);continue}i=n;continue}if(n<56320){(e-=3)>-1&&o.push(239,191,189),i=n;continue}n=65536+(i-55296<<10|n-56320)}else i&&(e-=3)>-1&&o.push(239,191,189);if(i=null,n<128){if((e-=1)<0)break;o.push(n)}else if(n<2048){if((e-=2)<0)break;o.push(n>>6|192,63&n|128)}else if(n<65536){if((e-=3)<0)break;o.push(n>>12|224,n>>6&63|128,63&n|128)}else{if(!(n<1114112))throw new Error("Invalid code point");if((e-=4)<0)break;o.push(n>>18|240,n>>12&63|128,n>>6&63|128,63&n|128)}}return o}function J(t){for(var e=[],n=0;n<t.length;++n)e.push(255&t.charCodeAt(n));return e}function H(t,e){for(var n,r,i,o=[],a=0;a<t.length&&!((e-=2)<0);++a)n=t.charCodeAt(a),r=n>>8,i=n%256,o.push(i),o.push(r);return o}function G(t){return K.toByteArray(N(t))}function W(t,e,n,r){for(var i=0;i<r&&!(i+n>=e.length||i>=t.length);++i)e[i+n]=t[i];return i}function X(t){return t!==t}/*!
 * The buffer module from node.js, for the browser.
 *
 * @author   Feross Aboukhadijeh <feross@feross.org> <http://feross.org>
 * @license  MIT
 */
var K=n(3),V=n(5),Z=n(6);e.Buffer=o,e.SlowBuffer=g,e.INSPECT_MAX_BYTES=50,o.TYPED_ARRAY_SUPPORT=void 0!==t.TYPED_ARRAY_SUPPORT?t.TYPED_ARRAY_SUPPORT:function(){try{var t=new Uint8Array(1);return t.__proto__={__proto__:Uint8Array.prototype,foo:function(){return 42}},42===t.foo()&&"function"==typeof t.subarray&&0===t.subarray(1,1).byteLength}catch(t){return!1}}(),e.kMaxLength=r(),o.poolSize=8192,o._augment=function(t){return t.__proto__=o.prototype,t},o.from=function(t,e,n){return a(null,t,e,n)},o.TYPED_ARRAY_SUPPORT&&(o.prototype.__proto__=Uint8Array.prototype,o.__proto__=Uint8Array,"undefined"!=typeof Symbol&&Symbol.species&&o[Symbol.species]===o&&Object.defineProperty(o,Symbol.species,{value:null,configurable:!0})),o.alloc=function(t,e,n){return c(null,t,e,n)},o.allocUnsafe=function(t){return u(null,t)},o.allocUnsafeSlow=function(t){return u(null,t)},o.isBuffer=function(t){return!(null==t||!t._isBuffer)},o.compare=function(t,e){if(!o.isBuffer(t)||!o.isBuffer(e))throw new TypeError("Arguments must be Buffers");if(t===e)return 0;for(var n=t.length,r=e.length,i=0,a=Math.min(n,r);i<a;++i)if(t[i]!==e[i]){n=t[i],r=e[i];break}return n<r?-1:r<n?1:0},o.isEncoding=function(t){switch(String(t).toLowerCase()){case"hex":case"utf8":case"utf-8":case"ascii":case"latin1":case"binary":case"base64":case"ucs2":case"ucs-2":case"utf16le":case"utf-16le":return!0;default:return!1}},o.concat=function(t,e){if(!Z(t))throw new TypeError('"list" argument must be an Array of Buffers');if(0===t.length)return o.alloc(0);var n;if(void 0===e)for(e=0,n=0;n<t.length;++n)e+=t[n].length;var r=o.allocUnsafe(e),i=0;for(n=0;n<t.length;++n){var a=t[n];if(!o.isBuffer(a))throw new TypeError('"list" argument must be an Array of Buffers');a.copy(r,i),i+=a.length}return r},o.byteLength=m,o.prototype._isBuffer=!0,o.prototype.swap16=function(){var t=this.length;if(t%2!=0)throw new RangeError("Buffer size must be a multiple of 16-bits");for(var e=0;e<t;e+=2)y(this,e,e+1);return this},o.prototype.swap32=function(){var t=this.length;if(t%4!=0)throw new RangeError("Buffer size must be a multiple of 32-bits");for(var e=0;e<t;e+=4)y(this,e,e+3),y(this,e+1,e+2);return this},o.prototype.swap64=function(){var t=this.length;if(t%8!=0)throw new RangeError("Buffer size must be a multiple of 64-bits");for(var e=0;e<t;e+=8)y(this,e,e+7),y(this,e+1,e+6),y(this,e+2,e+5),y(this,e+3,e+4);return this},o.prototype.toString=function(){var t=0|this.length;return 0===t?"":0===arguments.length?R(this,0,t):v.apply(this,arguments)},o.prototype.equals=function(t){if(!o.isBuffer(t))throw new TypeError("Argument must be a Buffer");return this===t||0===o.compare(this,t)},o.prototype.inspect=function(){var t="",n=e.INSPECT_MAX_BYTES;return this.length>0&&(t=this.toString("hex",0,n).match(/.{2}/g).join(" "),this.length>n&&(t+=" ... ")),"<Buffer "+t+">"},o.prototype.compare=function(t,e,n,r,i){if(!o.isBuffer(t))throw new TypeError("Argument must be a Buffer");if(void 0===e&&(e=0),void 0===n&&(n=t?t.length:0),void 0===r&&(r=0),void 0===i&&(i=this.length),e<0||n>t.length||r<0||i>this.length)throw new RangeError("out of range index");if(r>=i&&e>=n)return 0;if(r>=i)return-1;if(e>=n)return 1;if(e>>>=0,n>>>=0,r>>>=0,i>>>=0,this===t)return 0;for(var a=i-r,s=n-e,c=Math.min(a,s),u=this.slice(r,i),l=t.slice(e,n),d=0;d<c;++d)if(u[d]!==l[d]){a=u[d],s=l[d];break}return a<s?-1:s<a?1:0},o.prototype.includes=function(t,e,n){return-1!==this.indexOf(t,e,n)},o.prototype.indexOf=function(t,e,n){return w(this,t,e,n,!0)},o.prototype.lastIndexOf=function(t,e,n){return w(this,t,e,n,!1)},o.prototype.write=function(t,e,n,r){if(void 0===e)r="utf8",n=this.length,e=0;else if(void 0===n&&"string"==typeof e)r=e,n=this.length,e=0;else{if(!isFinite(e))throw new Error("Buffer.write(string, encoding, offset[, length]) is no longer supported");e|=0,isFinite(n)?(n|=0,void 0===r&&(r="utf8")):(r=n,n=void 0)}var i=this.length-e;if((void 0===n||n>i)&&(n=i),t.length>0&&(n<0||e<0)||e>this.length)throw new RangeError("Attempt to write outside buffer bounds");r||(r="utf8");for(var o=!1;;)switch(r){case"hex":return x(this,t,e,n);case"utf8":case"utf-8":return _(this,t,e,n);case"ascii":return A(this,t,e,n);case"latin1":case"binary":return E(this,t,e,n);case"base64":return S(this,t,e,n);case"ucs2":case"ucs-2":case"utf16le":case"utf-16le":return k(this,t,e,n);default:if(o)throw new TypeError("Unknown encoding: "+r);r=(""+r).toLowerCase(),o=!0}},o.prototype.toJSON=function(){return{type:"Buffer",data:Array.prototype.slice.call(this._arr||this,0)}};var Q=4096;o.prototype.slice=function(t,e){var n=this.length;t=~~t,e=void 0===e?n:~~e,t<0?(t+=n)<0&&(t=0):t>n&&(t=n),e<0?(e+=n)<0&&(e=0):e>n&&(e=n),e<t&&(e=t);var r;if(o.TYPED_ARRAY_SUPPORT)r=this.subarray(t,e),r.__proto__=o.prototype;else{var i=e-t;r=new o(i,void 0);for(var a=0;a<i;++a)r[a]=this[a+t]}return r},o.prototype.readUIntLE=function(t,e,n){t|=0,e|=0,n||I(t,e,this.length);for(var r=this[t],i=1,o=0;++o<e&&(i*=256);)r+=this[t+o]*i;return r},o.prototype.readUIntBE=function(t,e,n){t|=0,e|=0,n||I(t,e,this.length);for(var r=this[t+--e],i=1;e>0&&(i*=256);)r+=this[t+--e]*i;return r},o.prototype.readUInt8=function(t,e){return e||I(t,1,this.length),this[t]},o.prototype.readUInt16LE=function(t,e){return e||I(t,2,this.length),this[t]|this[t+1]<<8},o.prototype.readUInt16BE=function(t,e){return e||I(t,2,this.length),this[t]<<8|this[t+1]},o.prototype.readUInt32LE=function(t,e){return e||I(t,4,this.length),(this[t]|this[t+1]<<8|this[t+2]<<16)+16777216*this[t+3]},o.prototype.readUInt32BE=function(t,e){return e||I(t,4,this.length),16777216*this[t]+(this[t+1]<<16|this[t+2]<<8|this[t+3])},o.prototype.readIntLE=function(t,e,n){t|=0,e|=0,n||I(t,e,this.length);for(var r=this[t],i=1,o=0;++o<e&&(i*=256);)r+=this[t+o]*i;return i*=128,r>=i&&(r-=Math.pow(2,8*e)),r},o.prototype.readIntBE=function(t,e,n){t|=0,e|=0,n||I(t,e,this.length);for(var r=e,i=1,o=this[t+--r];r>0&&(i*=256);)o+=this[t+--r]*i;return i*=128,o>=i&&(o-=Math.pow(2,8*e)),o},o.prototype.readInt8=function(t,e){return e||I(t,1,this.length),128&this[t]?-1*(255-this[t]+1):this[t]},o.prototype.readInt16LE=function(t,e){e||I(t,2,this.length);var n=this[t]|this[t+1]<<8;return 32768&n?4294901760|n:n},o.prototype.readInt16BE=function(t,e){e||I(t,2,this.length);var n=this[t+1]|this[t]<<8;return 32768&n?4294901760|n:n},o.prototype.readInt32LE=function(t,e){return e||I(t,4,this.length),this[t]|this[t+1]<<8|this[t+2]<<16|this[t+3]<<24},o.prototype.readInt32BE=function(t,e){return e||I(t,4,this.length),this[t]<<24|this[t+1]<<16|this[t+2]<<8|this[t+3]},o.prototype.readFloatLE=function(t,e){return e||I(t,4,this.length),V.read(this,t,!0,23,4)},o.prototype.readFloatBE=function(t,e){return e||I(t,4,this.length),V.read(this,t,!1,23,4)},o.prototype.readDoubleLE=function(t,e){return e||I(t,8,this.length),V.read(this,t,!0,52,8)},o.prototype.readDoubleBE=function(t,e){return e||I(t,8,this.length),V.read(this,t,!1,52,8)},o.prototype.writeUIntLE=function(t,e,n,r){if(t=+t,e|=0,n|=0,!r){M(this,t,e,n,Math.pow(2,8*n)-1,0)}var i=1,o=0;for(this[e]=255&t;++o<n&&(i*=256);)this[e+o]=t/i&255;return e+n},o.prototype.writeUIntBE=function(t,e,n,r){if(t=+t,e|=0,n|=0,!r){M(this,t,e,n,Math.pow(2,8*n)-1,0)}var i=n-1,o=1;for(this[e+i]=255&t;--i>=0&&(o*=256);)this[e+i]=t/o&255;return e+n},o.prototype.writeUInt8=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,1,255,0),o.TYPED_ARRAY_SUPPORT||(t=Math.floor(t)),this[e]=255&t,e+1},o.prototype.writeUInt16LE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,2,65535,0),o.TYPED_ARRAY_SUPPORT?(this[e]=255&t,this[e+1]=t>>>8):Y(this,t,e,!0),e+2},o.prototype.writeUInt16BE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,2,65535,0),o.TYPED_ARRAY_SUPPORT?(this[e]=t>>>8,this[e+1]=255&t):Y(this,t,e,!1),e+2},o.prototype.writeUInt32LE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,4,4294967295,0),o.TYPED_ARRAY_SUPPORT?(this[e+3]=t>>>24,this[e+2]=t>>>16,this[e+1]=t>>>8,this[e]=255&t):L(this,t,e,!0),e+4},o.prototype.writeUInt32BE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,4,4294967295,0),o.TYPED_ARRAY_SUPPORT?(this[e]=t>>>24,this[e+1]=t>>>16,this[e+2]=t>>>8,this[e+3]=255&t):L(this,t,e,!1),e+4},o.prototype.writeIntLE=function(t,e,n,r){if(t=+t,e|=0,!r){var i=Math.pow(2,8*n-1);M(this,t,e,n,i-1,-i)}var o=0,a=1,s=0;for(this[e]=255&t;++o<n&&(a*=256);)t<0&&0===s&&0!==this[e+o-1]&&(s=1),this[e+o]=(t/a>>0)-s&255;return e+n},o.prototype.writeIntBE=function(t,e,n,r){if(t=+t,e|=0,!r){var i=Math.pow(2,8*n-1);M(this,t,e,n,i-1,-i)}var o=n-1,a=1,s=0;for(this[e+o]=255&t;--o>=0&&(a*=256);)t<0&&0===s&&0!==this[e+o+1]&&(s=1),this[e+o]=(t/a>>0)-s&255;return e+n},o.prototype.writeInt8=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,1,127,-128),o.TYPED_ARRAY_SUPPORT||(t=Math.floor(t)),t<0&&(t=255+t+1),this[e]=255&t,e+1},o.prototype.writeInt16LE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,2,32767,-32768),o.TYPED_ARRAY_SUPPORT?(this[e]=255&t,this[e+1]=t>>>8):Y(this,t,e,!0),e+2},o.prototype.writeInt16BE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,2,32767,-32768),o.TYPED_ARRAY_SUPPORT?(this[e]=t>>>8,this[e+1]=255&t):Y(this,t,e,!1),e+2},o.prototype.writeInt32LE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,4,2147483647,-2147483648),o.TYPED_ARRAY_SUPPORT?(this[e]=255&t,this[e+1]=t>>>8,this[e+2]=t>>>16,this[e+3]=t>>>24):L(this,t,e,!0),e+4},o.prototype.writeInt32BE=function(t,e,n){return t=+t,e|=0,n||M(this,t,e,4,2147483647,-2147483648),t<0&&(t=4294967295+t+1),o.TYPED_ARRAY_SUPPORT?(this[e]=t>>>24,this[e+1]=t>>>16,this[e+2]=t>>>8,this[e+3]=255&t):L(this,t,e,!1),e+4},o.prototype.writeFloatLE=function(t,e,n){return D(this,t,e,!0,n)},o.prototype.writeFloatBE=function(t,e,n){return D(this,t,e,!1,n)},o.prototype.writeDoubleLE=function(t,e,n){return j(this,t,e,!0,n)},o.prototype.writeDoubleBE=function(t,e,n){return j(this,t,e,!1,n)},o.prototype.copy=function(t,e,n,r){if(n||(n=0),r||0===r||(r=this.length),e>=t.length&&(e=t.length),e||(e=0),r>0&&r<n&&(r=n),r===n)return 0;if(0===t.length||0===this.length)return 0;if(e<0)throw new RangeError("targetStart out of bounds");if(n<0||n>=this.length)throw new RangeError("sourceStart out of bounds");if(r<0)throw new RangeError("sourceEnd out of bounds");r>this.length&&(r=this.length),t.length-e<r-n&&(r=t.length-e+n);var i,a=r-n;if(this===t&&n<e&&e<r)for(i=a-1;i>=0;--i)t[i+e]=this[i+n];else if(a<1e3||!o.TYPED_ARRAY_SUPPORT)for(i=0;i<a;++i)t[i+e]=this[i+n];else Uint8Array.prototype.set.call(t,this.subarray(n,n+a),e);return a},o.prototype.fill=function(t,e,n,r){if("string"==typeof t){if("string"==typeof e?(r=e,e=0,n=this.length):"string"==typeof n&&(r=n,n=this.length),1===t.length){var i=t.charCodeAt(0);i<256&&(t=i)}if(void 0!==r&&"string"!=typeof r)throw new TypeError("encoding must be a string");if("string"==typeof r&&!o.isEncoding(r))throw new TypeError("Unknown encoding: "+r)}else"number"==typeof t&&(t&=255);if(e<0||this.length<e||this.length<n)throw new RangeError("Out of range index");if(n<=e)return this;e>>>=0,n=void 0===n?this.length:n>>>0,t||(t=0);var a;if("number"==typeof t)for(a=e;a<n;++a)this[a]=t;else{var s=o.isBuffer(t)?t:z(new o(t,r).toString()),c=s.length;for(a=0;a<n-e;++a)this[a+e]=s[a%c]}return this};var tt=/[^+\/0-9A-Za-z-_]/g}).call(e,n(8))},function(t,e){e.read=function(t,e,n,r,i){var o,a,s=8*i-r-1,c=(1<<s)-1,u=c>>1,l=-7,d=n?i-1:0,f=n?-1:1,p=t[e+d];for(d+=f,o=p&(1<<-l)-1,p>>=-l,l+=s;l>0;o=256*o+t[e+d],d+=f,l-=8);for(a=o&(1<<-l)-1,o>>=-l,l+=r;l>0;a=256*a+t[e+d],d+=f,l-=8);if(0===o)o=1-u;else{if(o===c)return a?NaN:1/0*(p?-1:1);a+=Math.pow(2,r),o-=u}return(p?-1:1)*a*Math.pow(2,o-r)},e.write=function(t,e,n,r,i,o){var a,s,c,u=8*o-i-1,l=(1<<u)-1,d=l>>1,f=23===i?Math.pow(2,-24)-Math.pow(2,-77):0,p=r?0:o-1,h=r?1:-1,g=e<0||0===e&&1/e<0?1:0;for(e=Math.abs(e),isNaN(e)||e===1/0?(s=isNaN(e)?1:0,a=l):(a=Math.floor(Math.log(e)/Math.LN2),e*(c=Math.pow(2,-a))<1&&(a--,c*=2),e+=a+d>=1?f/c:f*Math.pow(2,1-d),e*c>=2&&(a++,c/=2),a+d>=l?(s=0,a=l):a+d>=1?(s=(e*c-1)*Math.pow(2,i),a+=d):(s=e*Math.pow(2,d-1)*Math.pow(2,i),a=0));i>=8;t[n+p]=255&s,p+=h,s/=256,i-=8);for(a=a<<i|s,u+=i;u>0;t[n+p]=255&a,p+=h,a/=256,u-=8);t[n+p-h]|=128*g}},function(t,e){var n={}.toString;t.exports=Array.isArray||function(t){return"[object Array]"==n.call(t)}},function(t,e){t.exports=function(t){var e="undefined"!=typeof window&&window.location;if(!e)throw new Error("fixUrls requires window.location");if(!t||"string"!=typeof t)return t;var n=e.protocol+"//"+e.host,r=n+e.pathname.replace(/\/[^\/]*$/,"/");return t.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi,function(t,e){var i=e.trim().replace(/^"(.*)"$/,function(t,e){return e}).replace(/^'(.*)'$/,function(t,e){return e});if(/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/)/i.test(i))return t;var o;return o=0===i.indexOf("//")?i:0===i.indexOf("/")?n+i:r+i.replace(/^\.\//,""),"url("+JSON.stringify(o)+")"})}},function(t,e){var n;n=function(){return this}();try{n=n||Function("return this")()||(0,eval)("this")}catch(t){"object"==typeof window&&(n=window)}t.exports=n},function(t,e,n){"use strict";angular.module("modal.ui.xxt",[]).service("tmsModal",["$rootScope","$compile","$q","$controller",function(t,e,n,r){this.open=function(i){var o,a=n.defer(),s=n.defer(),c={result:a.promise,closed:s.promise,close:function(t){document.body.removeChild(f[0]),a.resolve(t)},dismiss:function(t){document.body.removeChild(f[0]),s.resolve(t)}};o=t.$new(!0),i.controller&&r(i.controller,{$scope:o,$tmsModalInstance:c});var u,l,d,f;return u=angular.element("<div></div>"),u.attr({class:"modal-content","ng-style":"{'z-index':1060}"}).append(i.template),l=angular.element("<div></div>"),l.attr({class:"modal-dialog"}).append(u),d=angular.element("<div></div>"),d.attr({class:"modal-backdrop","ng-style":"{'z-index':1040}"}),f=angular.element("<div></div>"),f.attr({class:"modal","ng-style":"{'z-index':1050}",tabindex:-1}).append(l).append(d),e(f)(o),document.body.appendChild(f[0]),c}}])},function(t,e,n){var r=n(13);"string"==typeof r&&(r=[[t.i,r,""]]);n(1)(r,{});r.locals&&(t.exports=r.locals)},function(module,exports,__webpack_require__){"use strict";var ngMod=angular.module("snsshare.ui.xxt",[]);ngMod.service("tmsSnsShare",["$http",function($http){function setWxShare(t,e,n,r,i){window.wx.onMenuShareTimeline({title:i.descAsTitle?n:t,link:e,imgUrl:r,success:function(){try{i.logger&&i.logger("T")}catch(t){alert("share failed:"+t.message)}},cancel:function(){}}),window.wx.onMenuShareAppMessage({title:t,desc:n,link:e,imgUrl:r,success:function(){try{i.logger&&i.logger("F")}catch(t){alert("share failed:"+t.message)}},cancel:function(){}})}function setYxShare(t,e,n,r,i){var o={img_url:r,link:e,title:t,desc:n};window.YixinJSBridge.on("menu:share:appmessage",function(t){try{i.logger&&i.logger("F")}catch(t){alert("share failed:"+t.message)}window.YixinJSBridge.invoke("sendAppMessage",o,function(t){})}),window.YixinJSBridge.on("menu:share:timeline",function(t){try{i.logger&&i.logger("T")}catch(t){alert("share failed:"+t.message)}window.YixinJSBridge.invoke("shareTimeline",o,function(t){})})}this.config=function(t){this.options=t},this.set=function(title,link,desc,img,fnOther){var _this=this;if(img&&-1===img.indexOf("http")&&(img="http://"+location.host+img),/MicroMessenger/i.test(navigator.userAgent)){var script;script=document.createElement("script"),script.src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js",script.onload=function(){var xhr,url;xhr=new XMLHttpRequest,url="/rest/site/fe/wxjssdksignpackage?site="+_this.options.siteId+"&url="+encodeURIComponent(location.href.split("#")[0]),xhr.open("GET",url,!0),xhr.onreadystatechange=function(){if(4==xhr.readyState)if(xhr.status>=200&&xhr.status<400){var signPackage;try{eval("("+xhr.responseText+")"),signPackage&&(signPackage.debug=!1,signPackage.jsApiList=_this.options.jsApiList,wx.config(signPackage),setWxShare(title,link,desc,img,_this.options))}catch(t){alert("local error:"+t.toString())}}else alert("http error:"+xhr.statusText)},xhr.send()},document.body.appendChild(script)}else/Yixin/i.test(navigator.userAgent)?void 0===window.YixinJSBridge?document.addEventListener("YixinJSBridgeReady",function(){setYxShare(title,link,desc,img,_this.options)},!1):setYxShare(title,link,desc,img,_this.options):fnOther&&"function"==typeof fnOther&&fnOther(title,link,desc,img)}}])},function(t,e,n){"use strict";n(10),n(2),n(9),angular.module("favor.ui.xxt",["page.ui.xxt","modal.ui.xxt"]).service("tmsFavor",["$rootScope","$http","$q","tmsDynaPage","tmsModal",function(t,e,n,r,i){function o(t){var r,i;return i=n.defer(),r="/rest/site/fe/user/favor/byUser",r+="?site="+t.siteid,r+="&id="+t.id,r+="&type="+t.type,e.get(r).success(function(t){i.resolve(t.data)}),i.promise}function a(t){var r,i;return i=n.defer(),r="/rest/site/fe/user/favor/add",r+="?site="+t.siteid,r+="&id="+t.id,r+="&type="+t.type,e.get(r).success(function(t){i.resolve(t.data)}),i.promise}function s(t){var r,i;return i=n.defer(),r="/rest/site/fe/user/favor/remove",r+="?site="+t.siteid,r+="&id="+t.id,r+="&type="+t.type,e.get(r).success(function(t){i.resolve(t.data)}),i.promise}function c(t){var r,i;return i=n.defer(),r="/rest/pl/fe/site/favor/sitesByUser?site="+t.siteid+"&id="+t.id+"&type="+t.type+"&_="+1*new Date,e.get(r).success(function(t){0==t.err_code&&i.resolve(t.data)}),i.promise}function u(t,r){var i,o;return o=n.defer(),i="/rest/pl/fe/site/favor/add?id="+t.id+"&type="+t.type,e.post(i,r).success(function(t){o.resolve(t.data)}),o.promise}function l(t,r){var i,o;return o=n.defer(),i="/rest/pl/fe/site/favor/remove?id="+t.id+"&type="+t.type,e.post(i,r).success(function(t){o.resolve(t.data)}),o.promise}this.open=function(t){var n;n='<div class="modal-header"><span class="modal-title">指定收藏位置</span></div>',n+='<div class="modal-body">',n+='<div class="checkbox">',n+="<label>",n+="<input type='checkbox' ng-true-value=\"'Y'\" ng-false-value=\"'N'\" ng-model='person._selected'>",n+="<span>个人账户</span>",n+="<span ng-if=\"person._favored==='Y'\">（已收藏）</span>",n+="</label>",n+="</div>",n+='<div class="checkbox" ng-repeat="site in mySites">',n+="<label>",n+="<input type='checkbox' ng-true-value=\"'Y'\" ng-false-value=\"'N'\" ng-model='site._selected'>",n+="<span>{{site.name}}</span>",n+="<span ng-if=\"site._favored==='Y'\">（已收藏）</span>",n+="</label>",n+="</div>",n+='<div ng-if="mySites.length===0"><a href="" ng-click="createSite()">创建</a>团队进行收藏，方便团队内共享信息</div>',n+="</div>",n+='<div class="modal-footer"><button class="btn btn-default" ng-click="cancel()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button></div>',i.open({template:n,controller:["$scope","$tmsModalInstance",function(n,r){o(t).then(function(t){n.person={_favored:t?"Y":"N"},n.person._selected=n.person._favored}),c(t).then(function(t){var e=t;e.forEach(function(t){t._selected=t._favored}),n.mySites=e}),n.createSite=function(){e.get("/rest/pl/fe/site/create").success(function(t){var e=t.data;e._favored=e._selected="N",n.mySites=[e]})},n.ok=function(){var t;t={person:n.person,mySites:n.mySites},r.close(t)},n.cancel=function(){r.dismiss()}}]}).result.then(function(e){var n,r;if(n=e.person,n&&n._selected!==n._favored&&("Y"===n._selected?a(t):s(t)),r=e.mySites){var i=[],o=[];r.forEach(function(t){t._selected!==t._favored&&("Y"===t._selected?i.push(t.id):o.push(t.id))}),i.length&&u(t,i),o.length&&l(t,o)}})},this.showSwitch=function(e,n){var i,o=this;i=document.createElement("div"),i.classList.add("tms-switch","tms-switch-favor"),i.addEventListener("click",function(i){i.preventDefault(),i.stopPropagation(),t.$apply(function(){e.loginExpire?o.open(n):r.openPlugin("http://"+location.host+"/rest/site/fe/user/login?site="+n.siteid).then(function(t){e.loginExpire=t.loginExpire,o.open(n)})})},!0),document.body.appendChild(i)}}])},function(t,e,n){e=t.exports=n(0)(void 0),e.push([t.i,".modal{display:block;overflow:hidden;outline:0;overflow-x:hidden;overflow-y:auto;opacity:1}.modal,.modal-backdrop{position:fixed;top:0;right:0;bottom:0;left:0}.modal-backdrop{background-color:#000;opacity:.5}.modal-dialog{z-index:1055;margin:0;position:relative;width:auto;margin:10px}.modal-content{position:relative;background-color:#fff;-webkit-background-clip:padding-box;background-clip:padding-box;border:1px solid #999;border:1px solid rgba(0,0,0,.2);border-radius:6px;outline:0;-webkit-box-shadow:0 3px 9px rgba(0,0,0,.5);box-shadow:0 3px 9px rgba(0,0,0,.5)}.modal-header{padding:15px;border-bottom:1px solid #e5e5e5}.modal-header .close{margin-top:-2px}.modal-title{margin:0;line-height:1.42857143}.modal-body{position:relative;padding:15px}.modal-footer{padding:15px;text-align:right;border-top:1px solid #e5e5e5}button.close{-webkit-appearance:none;padding:0;cursor:pointer;background:0 0;border:0}.close{float:right;font-size:21px;font-weight:700;line-height:1;color:#000;text-shadow:0 1px 0 #fff;filter:alpha(opacity=20);opacity:.2}@media (min-width:768px){.modal-dialog{width:600px;margin:30px auto}.modal-content{-webkit-box-shadow:0 5px 15px rgba(0,0,0,.5);box-shadow:0 5px 15px rgba(0,0,0,.5)}}",""])},function(t,e,n){"use strict";function r(t,e){var n,r,i;n=document.createDocumentFragment(),r=document.createElement("div"),r.setAttribute("id","frmPlugin"),i=document.createElement("iframe"),r.appendChild(i),r.onclick=function(){r.parentNode.removeChild(r)},n.appendChild(r),document.body.appendChild(n),0===t.indexOf("http")?(window.onClosePlugin=function(){r.parentNode.removeChild(r),e&&e()},i.setAttribute("src",t)):i.contentDocument&&i.contentDocument.body&&(i.contentDocument.body.innerHTML=t)}angular.module("coinpay.ui.xxt",[]).service("tmsCoinPay",function(){this.showSwitch=function(t,e){var n;n=document.createElement("div"),n.classList.add("tms-switch","tms-switch-coinpay"),n.addEventListener("click",function(n){n.preventDefault(),n.stopPropagation();var i="http://"+location.host;i+="/rest/site/fe/coin/pay",i+="?site="+t,i+="&matter="+e,r(i)},!0),document.body.appendChild(n)}})},function(t,e,n){"use strict";function r(t,e){var n,r,i;n=document.createDocumentFragment(),r=document.createElement("div"),r.setAttribute("id","frmPlugin"),i=document.createElement("iframe"),r.appendChild(i),r.onclick=function(){r.parentNode.removeChild(r)},n.appendChild(r),document.body.appendChild(n),0===t.indexOf("http")?(window.onClosePlugin=function(){r.parentNode.removeChild(r),e&&e()},i.setAttribute("src",t)):i.contentDocument&&i.contentDocument.body&&(i.contentDocument.body.innerHTML=t)}angular.module("siteuser.ui.xxt",[]).service("tmsSiteUser",function(){this.showSwitch=function(t,e){var n;n=document.createElement("div"),n.classList.add("tms-switch","tms-switch-siteuser"),n.addEventListener("click",function(n){n.preventDefault(),n.stopPropagation();var i="http://"+location.host;i+="/rest/site/fe/user",i+="?site="+t,e?location.href=i:r(i)},!0),document.body.appendChild(n)}})},function(t,e,n){e=t.exports=n(0)(void 0),e.push([t.i,'.dialog.mask{position:fixed;background:rgba(0,0,0,.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:998}.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}.dialog .dlg-body,.dialog .dlg-header{padding:15px 15px 0}.dialog .dlg-footer{text-align:right;padding:15px}.dialog .dlg-footer button{border-radius:0}div[wrap=filter] .detail{background:#ccc}div[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}div[wrap=filter] .detail .actions .btn{border-radius:0}.tms-switch{position:fixed;right:15px;width:48px;height:48px;background:hsla(0,0%,75%,.5);border-radius:4px;color:#666;font-size:24px;line-height:48px;text-align:center;cursor:pointer}.tms-switch:before{font-size:.7em}.tms-switch:nth-of-type(2){bottom:8px}.tms-switch:nth-of-type(3){bottom:64px}.tms-switch:nth-of-type(4){bottom:120px}.tms-switch:nth-of-type(5){bottom:176px}.tms-switch:nth-of-type(6){bottom:236px}.tms-switch-favor:before{content:"\\6536\\85CF"}.tms-switch-favor.favored{background:rgba(132,255,192,.5)}.tms-switch-coinpay:before{content:"\\6253\\8D4F"}.tms-switch-siteuser:before{content:"\\6211"}.tms-discuss-switch{position:fixed;bottom:48px;right:15px;width:48px;height:48px;background:hsla(0,0%,75%,.5);border-radius:4px;color:#666;font-size:24px;line-height:48px;text-align:center}@media screen and (max-width:719px){body{margin-bottom:128px}.tms-switch{bottom:8px}.tms-switch:nth-of-type(2){right:16px;bottom:8px}.tms-switch:nth-of-type(3){right:72px;bottom:8px}.tms-switch:nth-of-type(4){right:128px;bottom:8px}.tms-switch:nth-of-type(5){right:184px;bottom:8px}.tms-switch:nth-of-type(6){right:244px;bottom:8px}}#frmPlugin{position:absolute;top:0;bottom:0;left:0;right:0;border:none;z-index:999;box-sizing:border-box;padding-bottom:48px;background:#fff}#frmPlugin,#frmPlugin iframe{width:100%;height:100%}#frmPlugin:after{content:"\\5173\\95ED";position:absolute;width:100px;text-align:center;left:50%;margin-left:-50px;bottom:4px;padding:5px 6px 3px;border:1px solid #ccc;border-radius:4px}div[wrap]>.description{word-wrap:break-word}',""])},function(t,e,n){var r=n(16);"string"==typeof r&&(r=[[t.i,r,""]]);n(1)(r,{});r.locals&&(t.exports=r.locals)},function(t,e,n){"use strict";var r={};r.makeDialog=function(t,e){var n,r;return r=document.createElement("div"),r.setAttribute("id",t),r.classList.add("dialog","mask"),n="<div class='dialog dlg'>",e.header&&e.header.length&&(n+="<div class='dlg-header'>"+e.header+"</div>"),n+="<div class='dlg-body'>"+e.body+"</div>",e.footer&&e.footer.length&&(n+="<div class='dlg-footer'>"+e.footer+"</div>"),n+="</div>",r.innerHTML=n,document.body.appendChild(r),r.children};var i=angular.module("directive.enroll",[]);i.directive("tmsDate",["$compile",function(t){return{restrict:"A",scope:{value:"=tmsDateValue"},controller:["$scope",function(t){t.close=function(){var e;e=document.querySelector("#"+t.dialogID),document.body.removeChild(e),t.opened=!1},t.ok=function(){var e;e=new Date,e.setTime(0),e.setFullYear(t.data.year),e.setMonth(t.data.month-1),e.setDate(t.data.date),e.setHours(t.data.hour),e.setMinutes(t.data.minute),t.value=parseInt(e.getTime()/1e3),t.close()}}],link:function(e,n,i){var o,a,s,c;void 0===e.value&&(e.value=1*new Date/1e3),a=new Date,a.setTime(1e3*e.value),e.options={years:[2014,2015,2016,2017],months:[1,2,3,4,5,6,7,8,9,10,11,12],dates:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31],hours:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23],minutes:[0,5,10,15,20,25,30,35,40,45,50,55]},s=5*Math.round(a.getMinutes()/5),e.data={year:a.getFullYear(),month:a.getMonth()+1,date:a.getDate(),hour:a.getHours(),minute:s},-1===e.options.minutes.indexOf(s)&&e.options.minutes.push(s),c='<div class="form-group"><select class="form-control" ng-model="data.year" ng-options="y for y in options.years"></select></div>',c+='<div class="form-group"><select class="form-control" ng-model="data.month" ng-options="m for m in options.months"></select></div>',c+='<div class="form-group"><select class="form-control" ng-model="data.date" ng-options="d for d in options.dates"></select></div>',c+='<div class="form-group"><select class="form-control" ng-model="data.hour" ng-options="h for h in options.hours"></select></div>',c+='<div class="form-group"><select class="form-control" ng-model="data.minute" ng-options="mi for mi in options.minutes"></select></div>',o=function(n){if(n.preventDefault(),n.stopPropagation(),!e.opened){var i,o;o="_dlg-"+1*new Date,i={header:"",body:c,footer:'<button class="btn btn-default" ng-click="close()">关闭</button><button class="btn btn-success" ng-click="ok()">确定</button>'},i=r.makeDialog(o,i),e.opened=!0,e.dialogID=o,t(i)(e)}},n[0].querySelector("[ng-bind]").addEventListener("click",o)}}}]),i.directive("tmsCheckboxGroup",function(){return{restrict:"A",link:function(t,e,n){var r,i,o,a;n.tmsCheckboxGroup&&n.tmsCheckboxGroup.length&&(r=n.tmsCheckboxGroup,n.tmsCheckboxGroupModel&&n.tmsCheckboxGroupModel.length&&(i=n.tmsCheckboxGroupModel,n.tmsCheckboxGroupUpper&&n.tmsCheckboxGroupUpper.length&&(a=n.tmsCheckboxGroupUpper,o=document.querySelectorAll("[name="+r+"]"),t.$watch(i+"."+r,function(t){var e;e=0,angular.forEach(t,function(t,n){t&&e++}),e>=a?[].forEach.call(o,function(t){void 0===t.checked?!t.classList.contains("checked")&&t.setAttribute("disabled",!0):!t.checked&&(t.disabled=!0)}):[].forEach.call(o,function(t){void 0===t.checked?t.removeAttribute("disabled"):t.disabled=!1})},!0))))}}}),i.directive("flexImg",function(){return{restrict:"A",replace:!0,template:"<img ng-src='{{img.imgSrc}}'>",link:function(t,e,n){angular.element(e).on("load",function(){var t,e,n=this.clientWidth,r=this.clientHeight;n>r?(t=n/r*80,angular.element(this).css({height:"100%",width:t+"px",top:"0",left:"50%","margin-left":-1*t/2+"px"})):(e=r/n*80,angular.element(this).css({width:"100%",height:e+"px",left:"0",top:"50%","margin-top":-1*e/2+"px"}))})}}})},function(t,e,n){"use strict";angular.module("http.ui.xxt",[]).service("http2",["$rootScope","$http","$timeout","$q","$sce","$compile",function(t,e,n,r,i,o){function a(e,n,r){var a;return a=angular.element("<div></div>"),a.attr({class:"tms-notice alert alert-"+(n||"info"),"ng-style":"{'z-index':1040}"}).html(i.trustAsHtml(e)),r||a[0].addEventListener("click",function(){document.body.removeChild(a[0])},!0),o(a)(t),document.body.appendChild(a[0]),a[0]}function s(t){t&&document.body.removeChild(t)}this.get=function(t,i){var o,c,u=r.defer();return i=angular.extend({headers:{accept:"application/json"},autoBreak:!0,autoNotice:!0,showProgress:!0,showProgressDelay:500,showProgressText:"正在获取数据..."},i),!0===i.showProgress&&(c=n(function(){c=null,o=a(i.showProgressText,"info")},i.showProgressDelay)),e.get(t,i).success(function(t){if(!0===i.showProgress&&(c&&n.cancel(c),o&&(s(o),o=null)),angular.isString(t))return void(i.autoNotice&&a(t,"warning"));0!=t.err_code&&(i.autoNotice&&a(t.err_msg,"warning"),i.autoBreak)||u.resolve(t)}).error(function(t,e){!0===i.showProgress&&(c&&n.cancel(c),o&&(s(o),o=null)),a(null===t?"网络不可用":t,"danger")}),u.promise},this.post=function(t,i,o){var c,u,l=r.defer();return o=angular.extend({headers:{accept:"application/json"},autoBreak:!0,autoNotice:!0,showProgress:!0,showProgressDelay:500,showProgressText:"正在获取数据..."},o),!0===o.showProgress&&(u=n(function(){u=null,c=a(o.showProgressText,"info")},o.showProgressDelay)),e.post(t,i,o).success(function(t){if(!0===o.showProgress&&(u&&n.cancel(u),c&&(s(c),c=null)),angular.isString(t))return void(o.autoNotice&&(a(t,"warning"),c=null));0!=t.err_code&&(o.autoNotice&&a(t.err_msg,"warning"),o.autoBreak)||l.resolve(t)}).error(function(t,e){!0===o.showProgress&&(u&&n.cancel(u),c&&(s(c),c=null)),a(null===t?"网络不可用":t,"danger")}),l.promise}}])},function(t,e,n){"use strict";n(11),/MicroMessenger/i.test(navigator.userAgent)&&window.signPackage&&window.wx?window.wx.ready(function(){window.wx.showOptionMenu()}):/YiXin/i.test(navigator.userAgent)&&document.addEventListener("YixinJSBridgeReady",function(){YixinJSBridge.call("showOptionMenu")},!1),n(17),n(19),n(2),n(15),n(12),n(14),n(18);var r=angular.module("app",["ngSanitize","ui.bootstrap","http.ui.xxt","page.ui.xxt","snsshare.ui.xxt","directive.enroll","siteuser.ui.xxt","favor.ui.xxt"]);r.provider("ls",function(){var t={};this.params=function(e){var n;return n=location.search,angular.forEach(e,function(e){var r,i;i=new RegExp(e+"=([^&]*)"),r=n.match(i),t[e]=r?r[1]:""}),t},this.$get=function(){return{p:t,j:function(e){var n=1,r=arguments.length,i="/rest/site/fe/matter/enroll",o=[];for(e&&e.length&&(i+="/"+e);n<r;n++)o.push(arguments[n]+"="+t[arguments[n]]);return o.length&&(i+="?"+o.join("&")),i}}}}),r.config(["$controllerProvider","$uibTooltipProvider","lsProvider",function(t,e,n){r.provider={controller:t.register},e.setTriggers({show:"hide"}),n.params(["site","app","rid","page","ek","preview","newRecord","ignoretime"])}]),r.controller("ctrlAppTip",["$scope","$interval",function(t,e){var n;t.autoCloseTime=6,t.domId="",t.closeTip=function(){var e=document.querySelector(t.domId),n=document.createEvent("HTMLEvents");n.initEvent("hide",!1,!1),e.dispatchEvent(n)},n=e(function(){0===--t.autoCloseTime&&(e.cancel(n),t.closeTip())},1e3)}]),r.controller("ctrlMain",["$scope","$http","$timeout","ls","tmsDynaPage","tmsSnsShare","tmsSiteUser","tmsFavor",function(t,e,n,i,o,a,s,c){var u=[];t.errmsg="",t.closePreviewTip=function(){t.preview="N"};var l=function(){e.get(i.j("askFollow","site")).error(function(t){var e,n;e=document.body,n=document.createElement("iframe"),n.setAttribute("id","frmPopup"),n.height=e.clientHeight,e.scrollTop=0,e.appendChild(n),window.closeAskFollow=function(){n.style.display="none"},n.setAttribute("src",i.j("askFollow","site")),n.style.display="block"})},d=function(){return{exec:function(e){var n,r,i,o;o=!0,n=t,i=e.match(/\((.*?)\)/)[1].replace(/'|"/g,"").split(","),angular.forEach(e.replace(/\(.*?\)/,"").split("."),function(t){if(r&&(n=r),!n[t])return void(o=!1);r=n[t]}),o&&r.apply(n,i)}}}();t.back=function(){history.back()},t.closeWindow=function(){/MicroMessenger/i.test(navigator.userAgent)?window.wx.closeWindow():/YiXin/i.test(navigator.userAgent)&&window.YixinJSBridge.call("closeWebView")},t.addRecord=function(e,n){if(n)t.gotoPage(e,n,null,null,!1,"Y");else for(var r in t.app.pages){var i=t.app.pages[r];if("I"===i.type){t.gotoPage(e,i.name,null,null,!1,"Y");break}}},t.gotoPage=function(e,n,r,o,a,s){if(e&&(e.preventDefault(),e.stopPropagation()),a&&!t.User.fan)return void l();var c=i.j("","site","app");void 0!==r&&null!==r&&r.length&&(c+="&ek="+r),void 0!==o&&null!==o&&o.length&&(c+="&rid="+o),void 0!==n&&null!==n&&n.length&&(c+="&page="+n),void 0!==s&&"Y"===s&&(c+="&newRecord=Y"),/remark|repos/.test(n)?location=c:location.replace(c)},t.openMatter=function(t,e,n,r){var o="/rest/site/fe/matter?site="+i.p.site+"&id="+t+"&type="+e;n?location.replace(o):!1===r?location.href=o:window.open(o)},t.onReady=function(e){t.params?d.exec(e):u.push(e)},e.get(i.j("get","site","app","rid","page","ek","newRecord")).success(function(l){if(0!==l.err_code)return void(t.errmsg=l.err_msg);var f,p,h,g=l.data,m=g.site,v=g.app,y=g.mission,w=g.page,b=g.user,x={};v.dataSchemas.forEach(function(t){x[t.id]=t}),v._schemasById=x,t.params=g,t.site=m,t.mission=y,t.app=v,t.user=b,"Y"===v.multi_rounds&&(t.activeRound=g.activeRound),f=b.uid+"_"+1*new Date,p="http://"+location.host+i.j("","site","app","rid","newRecord"),p+="&shareby="+f,w&&w.share_page&&"Y"===w.share_page&&(p+="&page="+w.name,g.record&&(p+="&ek="+g.record.enroll_key),/iphone|ipad/i.test(navigator.userAgent)||window.history&&window.history.replaceState&&window.history.replaceState({},v.title,p)),/MicroMessenger|Yixin/i.test(navigator.userAgent)&&(h=v.summary,w&&w.share_summary&&w.share_summary.length&&g.record&&(h=g.record.data[w.share_summary]),window.shareCounter=0,a.config({siteId:v.siteid,logger:function(t){var n;n="/rest/site/fe/matter/logShare",n+="?shareid="+f,n+="&site="+v.siteid,n+="&id="+v.id,n+="&type=enroll",n+="&title="+v.title,n+="&shareby="+f,n+="&shareto="+t,e.get(n),window.shareCounter++,"Y"===v.can_autoenroll&&"Y"===w.autoenroll_onshare&&e.get(i.j("emptyGet","site","app")+"&once=Y"),window.onshare&&window.onshare(window.shareCounter)},jsApiList:["hideOptionMenu","onMenuShareTimeline","onMenuShareAppMessage","chooseImage","uploadImage","getLocation"]}),a.set(v.title,p,h,v.pic)),"Y"===v.use_site_header&&m&&m.header_page&&o.loadCode(r,m.header_page),"Y"===v.use_mission_header&&y&&y.header_page&&o.loadCode(r,y.header_page),"Y"===v.use_mission_footer&&y&&y.footer_page&&o.loadCode(r,y.footer_page),"Y"===v.use_site_footer&&m&&m.footer_page&&o.loadCode(r,m.footer_page),g.page&&o.loadCode(r,g.page).then(function(){t.page=g.page}),u.length&&angular.forEach(u,d.exec),c.showSwitch(t.user,v),"Y"===v.can_siteuser&&s.showSwitch(v.siteid,!0),n(function(){t.$broadcast("xxt.app.enroll.ready",g)});var _;(_=document.querySelector(".loading"))&&_.parentNode.removeChild(_),e.post("/rest/site/fe/matter/logAccess?site="+v.siteid+"&id="+v.id+"&type=enroll&title="+v.title+"&shareby=",{search:location.search.replace("?",""),referer:document.referrer})}).error(function(e,n){if(401===n){var r=document.createElement("iframe");r.setAttribute("id","frmPopup"),r.onload=function(){this.height=document.querySelector("body").clientHeight},document.body.appendChild(r),0===e.indexOf("http")?(window.onAuthSuccess=function(){r.style.display="none"},r.setAttribute("src",e),r.style.display="block"):r.contentDocument&&r.contentDocument.body&&(r.contentDocument.body.innerHTML=e,r.style.display="block")}else t.errmsg=e})}]),t.exports=r},function(t,e,n){"use strict";void 0===window.xxt&&(window.xxt={}),window.xxt.geo={options:{},getAddress:function(t,e,n){var r;if(r=e.promise,window.wx)window.wx.getLocation({success:function(r){var i="/rest/site/fe/matter/enroll/locationGet";i+="?site="+n,i+="&lat="+r.latitude,i+="&lng="+r.longitude,t.get(i).success(function(t){0===t.err_code?e.resolve({errmsg:"ok",lat:r.latitude,lng:r.longitude,address:t.data.address}):e.resolve({errmsg:t.err_msg})})}});else try{var i=window.navigator;if(null!==i){var o=i.geolocation;null!==o?o.getCurrentPosition(function(r){var i="/rest/site/fe/matter/enroll/locationGet";i+="?site="+n,i+="&lat="+r.coords.latitude,i+="&lng="+r.coords.longitude,t.get(i).success(function(t){0===t.err_code?e.resolve({errmsg:"ok",lat:r.coords.latitude,lng:r.coords.longitude,address:t.data.address}):e.resolve({errmsg:t.err_msg})})},function(){e.resolve({errmsg:"获取地理位置失败"})}):e.resolve({errmsg:"无法获取地理位置"})}else e.resolve({errmsg:"无法获取地理位置"})}catch(t){alert("exception:"+t.message)}return r}}},function(t,e,n){"use strict";void 0===window.xxt&&(window.xxt={}),window.xxt.image={options:{},choose:function(t,e){var n,r=[];if(n=t.promise,window.YixinJSBridge)window.YixinJSBridge.invoke("pickImage",{type:e,quality:100},function(e){var n;e.data&&e.data.length&&(n={imgSrc:"data:"+e.mime+";base64,"+e.data},r.push(n)),t.resolve(r)});else{var i=document.createElement("input");i.setAttribute("type","file"),i.addEventListener("change",function(e){var n,o,a,s;for(o=e.target.files.length,n=0;n<o;n++){a=e.target.files[n],s={".jp":"image/jpeg",".pn":"image/png",".gi":"image/gif"}[a.name.match(/\.(\w){2}/g)[0]||".jp"],a.type2=a.type||s;var c=new FileReader;c.onload=function(e){return function(n){var o={};o.imgSrc=n.target.result.replace(/^.+(,)/,"data:"+e.type2+";base64,"),r.push(o),document.body.removeChild(i),t.resolve(r)}}(a),c.readAsDataURL(a)}},!1),i.style.opacity=0,document.body.appendChild(i),i.click()}return n},wxUpload:function(t,e){var n;return n=t.promise,0===e.imgSrc.indexOf("weixin://")||0===e.imgSrc.indexOf("wxLocalResource://")?window.wx.uploadImage({localId:e.imgSrc,isShowProgressTips:1,success:function(n){e.serverId=n.serverId,t.resolve(e)}}):t.resolve(e),n}}},,,,,,,,,function(t,e,n){"use strict";n(55),n(22),n(21);var r=n(20);r.config(["$compileProvider",function(t){t.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/)}]),r.factory("Input",["$http","$q","$timeout","ls",function(t,e,n,r){function i(t,e){if(void 0===e)return!0;switch(t.type){case"multiple":for(var n in e)if(!0===e[n])return!1;return!0;default:return 0===e.length}}var o,a;return o=function(){},o.prototype.check=function(t,e,n){var r,o,a,s;if(n.data_schemas&&n.data_schemas.length){r=JSON.parse(n.data_schemas);for(var c=r.length-1;c>=0;c--){if(o=r[c],a=o.schema,0===a.id.indexOf("member.")){var u=a.id.substr(7);-1===u.indexOf(".")?s=t.member[u]:(u=u.split("."),s=t.member.extattr[u[1]])}else s=t[a.id];if("Y"===o.config.required&&(void 0===s||i(a,s)))return"请填写必填题目［"+a.title+"］";if(s){if("mobile"===a.type&&!/^(\+86|0086)?\s*1[3|4|5|7|8]\d{9}$/.test(s))return"题目［"+a.title+"］只能填写手机号（11位数字）";if("name"===a.type&&s.length<2)return"题目［"+a.title+"］请输入正确的姓名（不少于2个字符）";if("email"===a.type&&!/^\w+@\w+/.test(s))return"题目［"+a.title+"］请输入正确的邮箱";if("shorttext"===a.type&&a.number&&"Y"===a.number&&(s=t[a.id],!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(s)))return"题目［"+a.title+"］请输入数值";if(a.format)if("number"===a.format){if(!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(s))return"题目［"+a.title+"］请输入数值"}else if("name"===a.format){if(s.length<2)return"题目［"+a.title+"］请输入正确的姓名（不少于2个字符）"}else if("mobile"===a.format){if(!/^(\+86|0086)?\s*1[3|4|5|7|8]\d{9}$/.test(s))return"题目［"+a.title+"］请输入正确的手机号（11位数字）"}else if("email"===a.format&&!/^\w+@\w+/.test(s))return"题目［"+a.title+"］请输入正确的邮箱"}if(/image|file/.test(a.type)&&a.count&&0!=a.count&&t[a.id]&&t[a.id].length>a.count)return"题目［"+a.title+"］超出上传数量（"+a.count+"）限制"}}return!0},o.prototype.submit=function(n,i,o){var a,s,c,u,l;a=e.defer(),l=angular.copy(i),Object.keys&&0===Object.keys(l.member).length&&delete l.member,s=r.j("record/submit","site","app"),n&&n.length&&(s+="&ek="+n);for(var d in l)if(c=l[d],angular.isArray(c)&&c.length&&void 0!==c[0].imgSrc&&void 0!==c[0].serverId)for(var f in c)u=c[f],delete u.imgSrc;return t.post(s,{data:l,supplement:o}).success(function(t){"string"==typeof t||0!=t.err_code?a.reject(t):a.resolve(t)}).error(function(t,e){if(401===e){var n=document.createElement("iframe");n.setAttribute("id","frmPopup"),n.onload=function(){this.height=document.querySelector("body").clientHeight},document.body.appendChild(n),0===t.indexOf("http")?(window.onAuthSuccess=function(){n.style.display="none"},n.setAttribute("src",t),n.style.display="block"):n.contentDocument&&n.contentDocument.body&&(n.contentDocument.body.innerHTML=t,n.style.display="block"),a.notify(e)}else a.reject(t)}),a.promise},{ins:function(){return a||(a=new o),a}}}]),r.directive("tmsImageInput",["$compile","$q",function(t,e){var n,r;return n=[],r=function(e){var n;n="<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'camera')\">拍照</button></div>",n+="<div class='form-group'><button class='btn btn-default btn-lg btn-block' ng-click=\"chooseImage(null,null,'album')\">相册</button></div>",n=__util.makeDialog("pickImageFrom",{body:n}),t(n)(e)},function(t){var n=e.defer();return n.resolve("ok"),n.promise},{restrict:"A",controller:["$scope","$timeout",function(t,i){t.chooseImage=function(o,a,s){if(null!==o&&(-1===n.indexOf(o)&&n.push(o),void 0===t.data[o]&&(t.data[o]=[]),null!==a&&t.data[o].length===a&&0!=a))return void(t.$parent.errmsg="最多允许上传"+a+"张图片");if(window.YixinJSBridge){if(void 0===s)return t.cachedImgFieldName=o,void r(t);o=t.cachedImgFieldName,t.cachedImgFieldName=null,angular.element("#pickImageFrom").remove()}window.xxt.image.choose(e.defer(),s).then(function(e){var n,r,a,s;n=t.$root.$$phase,"$digest"===n||"$apply"===n?t.data[o]=t.data[o].concat(e):t.$apply(function(){t.data[o]=t.data[o].concat(e)}),i(function(){for(r=0,a=e.length;r<a;r++)s=e[r],document.querySelector('ul[name="'+o+'"] li:nth-last-child(2) img').setAttribute("src",s.imgSrc);t.$broadcast("xxt.enroll.image.choose.done",o)})})},t.removeImage=function(t,e){t.splice(e,1)}}]}}]),r.directive("tmsFileInput",["$q","ls","tmsDynaPage",function(t,e,n){var r,i;return n.loadScript(["/static/js/resumable.js"]).then(function(){r=new Resumable({target:e.j("record/uploadFile","site","app"),testChunks:!1,chunkSize:524288})}),i=function(e){var n;return n=t.defer(),r.files&&0!==r.files.length||n.resolve("empty"),r.on("progress",function(){var t,n;n=r.progress();var t=e.$root.$$phase;"$digest"===t||"$apply"===t?e.progressOfUploadFile=Math.ceil(100*n):e.$apply(function(){e.progressOfUploadFile=Math.ceil(100*n)})}),r.on("complete",function(){var t=e.$root.$$phase;"$digest"===t||"$apply"===t?e.progressOfUploadFile="完成":e.$apply(function(){e.progressOfUploadFile="完成"}),r.cancel(),n.resolve("ok")}),r.upload(),n.promise},{restrict:"A",controller:["$scope",function(t){t.progressOfUploadFile=0,t.beforeSubmit(function(){return i(t)}),t.chooseFile=function(e,n,i){var o=document.createElement("input");o.setAttribute("type","file"),void 0!==i&&o.setAttribute("accept",i),o.addEventListener("change",function(n){var i,a,s;for(a=n.target.files.length,i=0;i<a;i++)s=n.target.files[i],r.addFile(s),t.$apply(function(){void 0===t.data[e]&&(t.data[e]=[]),t.data[e].push({uniqueIdentifier:r.files[r.files.length-1].uniqueIdentifier,name:s.name,size:s.size,type:s.type,url:""}),t.$broadcast("xxt.enroll.file.choose.done",e)});o=null},!0),o.click()}}]}}]),r.controller("ctrlInput",["$scope","$http","$q","$uibModal","$timeout","Input","ls","http2",function(t,e,n,r,i,o,a,s){function c(t,e){var n,r;t&&e&&e.schema_id&&t.members&&(n=t.members[e.schema_id])&&(angular.isString(n.extattr)&&(n.extattr.length?n.extattr=JSON.parse(n.extattr):n.extattr={}),r=document.querySelectorAll("[ng-model^='data.member']"),angular.forEach(r,function(t){var r;r=t.getAttribute("ng-model"),r=r.replace("data.member.",""),r=r.split("."),2==r.length?(!e.extattr&&(e.extattr={}),e.extattr[r[1]]=n.extattr[r[1]]):e[r[0]]=n[r[0]]}))}function u(t,e){(0,f[t])().then(function(n){t++,t<f.length?u(t,e):l(e)})}function l(e){var n;n=t.record?t.record.enroll_key:void 0,d.submit(n,t.data,t.supplement).then(function(r){var i;p.finish(),"closeWindow"===e?t.closeWindow():"_autoForward"===e?(i=a.j("","site","app"),location.replace(i)):e&&e.length?(i=a.j("","site","app"),i+="&page="+e,i+="&ek="+r.data,location.replace(i)):(void 0===n&&(t.record={enroll_key:r.data}),t.$broadcast("xxt.app.enroll.submit.done",r.data))},function(e){"string"==typeof e?t.$parent.errmsg=e:(t.$parent.errmsg=e.err_msg,p.finish())},function(e){"string"==typeof e?t.$parent.errmsg=e:(t.$parent.errmsg=e.err_msg,p.finish())})}window.onbeforeunload=function(){p.modified&&p.cache()};var d,f,p;f=[],d=o.ins(),t.data={member:{}},t.supplement={},t.submitState=p={modified:!1,state:"waiting",start:function(t){var e;if(t&&(e=t.target,("BUTTON"===e.tagName||(e=e.parentNode)&&"BUTTON"===e.tagName)&&/submit\(.*\)/.test(e.getAttribute("ng-click")))){var n;this.button=e,n=e.querySelector("span"),n.setAttribute("data-label",n.innerHTML),n.innerHTML="正在提交数据...",this.button.classList.add("submit-running")}this.state="running"},finish:function(){var t;if(this.state="waiting",this.modified=!1,this.button){var e;e=this.button.querySelector("span"),e.innerHTML=e.getAttribute("data-label"),e.removeAttribute("data-label"),this.button.classList.remove("submit-running"),this.button=null}window.localStorage&&(t=this._cacheKey(),window.localStorage.removeItem(t))},isRunning:function(){return"running"===this.state},_cacheKey:function(){var e=t.app;return"/site/"+e.siteid+"/app/"+e.id+"/record/"+(t.record?t.record.enroll_key:"")+"/unsubmit"},cache:function(){if(window.localStorage){var e,n;e=this._cacheKey(),n=angular.copy(t.data),n._cacheAt=1*new Date,n=JSON.stringify(n),window.localStorage.setItem(e,n)}},fromCache:function(t){if(window.localStorage){var e,n;e=this._cacheKey(),n=window.localStorage.getItem(e),t||window.localStorage.removeItem(e),n&&(n=JSON.parse(n),n._cacheAt&&n._cacheAt+18e5<1*new Date&&(n=!1),delete n._cacheAt)}return n}},t.beforeSubmit=function(t){-1===f.indexOf(t)&&f.push(t)};var h=!1;t.$on("xxt.app.enroll.ready",function(e,n){var r,i,o,a;if(t.schemasById=r=n.app._schemasById,n.record){i=n.record.data;for(o in i)if("member"===o)angular.isString(i.member)&&(i.member=JSON.parse(i.member)),t.data.member=angular.extend(t.data.member,i.member);else if(void 0!==r[o]){var s=r[o];if("score"===s.type)t.data[o]=i[o];else if(i[o].length)if("image"===s.type){a=i[o].split(","),t.data[o]=[];for(var c in a)t.data[o].push({imgSrc:a[c]})}else if("file"===s.type)a=i[o],t.data[o]=a;else if("multiple"===s.type){a=i[o].split(","),t.data[o]={};for(var c in a)t.data[o][a[c]]=!0}else t.data[o]=i[o]}t.record=n.record}if(window.localStorage){var u=p.fromCache();u&&(u.member&&delete u.member,angular.extend(t.data,u),p.modified=!0)}if(t.$watch("data",function(t,e){t!==e&&(p.modified=!0)},!0),!n.user.unionid){var l=document.querySelector("#appLoginTip"),d=document.createEvent("HTMLEvents");d.initEvent("show",!1,!1),l.dispatchEvent(d)}}),t.$watch("data.member.schema_id",function(e){!1===h&&e&&t.user&&(c(t.user,t.data.member),h=!0)}),t.submit=function(e,n){var r;p.isRunning()||(p.start(e),!0===(r=d.check(t.data,t.app,t.page))?f.length?u(0,n):l(n):(p.finish(),t.$parent.errmsg=r))},t.getMyLocation=function(r){window.xxt.geo.getAddress(e,n.defer(),a.p.site).then(function(e){"ok"===e.errmsg?t.data[r]=e.address:t.$parent.errmsg=e.errmsg})},t.dataBySchema=function(e){var n=t.app;r.open({templateUrl:"dataBySchema.html",controller:["$scope","$uibModalInstance",function(t,r){t.data={},t.cancel=function(){r.dismiss()},t.ok=function(){r.close(t.data)},s.get("/rest/site/fe/matter/enroll/repos/dataBySchema?site="+n.siteid+"&app="+n.id+"&schema="+e).then(function(e){t.records=e.data.records})}],windowClass:"auto-height",backdrop:"static"}).result.then(function(n){t.data[e]=n.selected.value})},t.score=function(e,n,r){var i=t.schemasById[e],o=i.ops[n];void 0===t.data[e]&&(t.data[e]={},i.ops.forEach(function(e){t.data[i.id][e.v]=0})),t.data[e][o.v]=r},t.lessScore=function(e,n,r){if(!t.schemasById)return!1;var i=t.schemasById[e],o=i.ops[n];return void 0!==t.data[e]&&t.data[e][o.v]>=r}}])},,,,,,,,,,,,,,function(t,e,n){e=t.exports=n(0)(void 0),e.push([t.i,"body,html{background:#efefef;font-family:Microsoft Yahei,Arial;height:100%;width:100%}body{position:relative;padding:15px;font-size:16px}img{max-width:100%}header{margin:-15px -15px 0}footer{margin:0 -15px -15px}ul{list-style:none}li,ul{margin:0;padding:0}#errmsg{display:block;opacity:0;height:0;overflow:hidden;width:300px;position:fixed;top:0;left:50%;margin:0 0 0 -150px;text-align:center;transition:opacity 1s;z-index:-1;word-break:break-all}#errmsg.active{opacity:1;height:auto;z-index:999}li[wrap=score]{padding:4px 4px 4px 0}li[wrap=score] label{padding:3px;font-weight:400}li[wrap=score]>.number{display:inline-block;margin-top:6px;border:1px solid #ccc}li[wrap=score]>.number>div{display:inline-block;width:48px;padding:4px;margin:4px;text-align:center;border-bottom:1px dotted #ddd}li[wrap=score]>.number>.in{background:#3b9}ul.img-tiles li{position:relative;display:inline-block;overflow:hidden;width:80px;height:80px;margin:0;padding:0;float:left}ul.img-tiles li.img-thumbnail img{display:inline-block;position:absolute;max-width:none}ul.img-tiles li.img-thumbnail button{position:absolute;top:0;right:0}ul.img-tiles li.img-picker button{position:auto;width:100%;height:100%}ul.img-tiles li.img-picker button span{font-size:36px}div[wrap].wrap-splitline{padding-bottom:.5em;border-bottom:1px solid #fff}div[wrap].wrap-inline>*{display:inline-block;vertical-align:middle;margin:0 1em 0 0}div[wrap].wrap-inline>label{width:6em;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}div[wrap=matter]>span{cursor:pointer;text-decoration:underline}#frmPopup{position:absolute;top:0;left:0;right:0;bottom:0;border:none;width:100%;z-index:999;box-sizing:border-box}",""])},,,,,,,,,,function(t,e,n){var r=n(45);"string"==typeof r&&(r=[[t.i,r,""]]);n(1)(r,{});r.locals&&(t.exports=r.locals)},,,,,,,,,,,,function(t,e,n){t.exports=n(31)}]);
=======
/* eslint-disable no-proto */



var base64 = __webpack_require__(3)
var ieee754 = __webpack_require__(5)
var isArray = __webpack_require__(6)

exports.Buffer = Buffer
exports.SlowBuffer = SlowBuffer
exports.INSPECT_MAX_BYTES = 50

/**
 * If `Buffer.TYPED_ARRAY_SUPPORT`:
 *   === true    Use Uint8Array implementation (fastest)
 *   === false   Use Object implementation (most compatible, even IE6)
 *
 * Browsers that support typed arrays are IE 10+, Firefox 4+, Chrome 7+, Safari 5.1+,
 * Opera 11.6+, iOS 4.2+.
 *
 * Due to various browser bugs, sometimes the Object implementation will be used even
 * when the browser supports typed arrays.
 *
 * Note:
 *
 *   - Firefox 4-29 lacks support for adding new properties to `Uint8Array` instances,
 *     See: https://bugzilla.mozilla.org/show_bug.cgi?id=695438.
 *
 *   - Chrome 9-10 is missing the `TypedArray.prototype.subarray` function.
 *
 *   - IE10 has a broken `TypedArray.prototype.subarray` function which returns arrays of
 *     incorrect length in some situations.

 * We detect these buggy browsers and set `Buffer.TYPED_ARRAY_SUPPORT` to `false` so they
 * get the Object implementation, which is slower but behaves correctly.
 */
Buffer.TYPED_ARRAY_SUPPORT = global.TYPED_ARRAY_SUPPORT !== undefined
  ? global.TYPED_ARRAY_SUPPORT
  : typedArraySupport()

/*
 * Export kMaxLength after typed array support is determined.
 */
exports.kMaxLength = kMaxLength()

function typedArraySupport () {
  try {
    var arr = new Uint8Array(1)
    arr.__proto__ = {__proto__: Uint8Array.prototype, foo: function () { return 42 }}
    return arr.foo() === 42 && // typed array instances can be augmented
        typeof arr.subarray === 'function' && // chrome 9-10 lack `subarray`
        arr.subarray(1, 1).byteLength === 0 // ie10 has broken `subarray`
  } catch (e) {
    return false
  }
}

function kMaxLength () {
  return Buffer.TYPED_ARRAY_SUPPORT
    ? 0x7fffffff
    : 0x3fffffff
}

function createBuffer (that, length) {
  if (kMaxLength() < length) {
    throw new RangeError('Invalid typed array length')
  }
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    // Return an augmented `Uint8Array` instance, for best performance
    that = new Uint8Array(length)
    that.__proto__ = Buffer.prototype
  } else {
    // Fallback: Return an object instance of the Buffer class
    if (that === null) {
      that = new Buffer(length)
    }
    that.length = length
  }

  return that
}

/**
 * The Buffer constructor returns instances of `Uint8Array` that have their
 * prototype changed to `Buffer.prototype`. Furthermore, `Buffer` is a subclass of
 * `Uint8Array`, so the returned instances will have all the node `Buffer` methods
 * and the `Uint8Array` methods. Square bracket notation works as expected -- it
 * returns a single octet.
 *
 * The `Uint8Array` prototype remains unmodified.
 */

function Buffer (arg, encodingOrOffset, length) {
  if (!Buffer.TYPED_ARRAY_SUPPORT && !(this instanceof Buffer)) {
    return new Buffer(arg, encodingOrOffset, length)
  }

  // Common case.
  if (typeof arg === 'number') {
    if (typeof encodingOrOffset === 'string') {
      throw new Error(
        'If encoding is specified then the first argument must be a string'
      )
    }
    return allocUnsafe(this, arg)
  }
  return from(this, arg, encodingOrOffset, length)
}

Buffer.poolSize = 8192 // not used by this implementation

// TODO: Legacy, not needed anymore. Remove in next major version.
Buffer._augment = function (arr) {
  arr.__proto__ = Buffer.prototype
  return arr
}

function from (that, value, encodingOrOffset, length) {
  if (typeof value === 'number') {
    throw new TypeError('"value" argument must not be a number')
  }

  if (typeof ArrayBuffer !== 'undefined' && value instanceof ArrayBuffer) {
    return fromArrayBuffer(that, value, encodingOrOffset, length)
  }

  if (typeof value === 'string') {
    return fromString(that, value, encodingOrOffset)
  }

  return fromObject(that, value)
}

/**
 * Functionally equivalent to Buffer(arg, encoding) but throws a TypeError
 * if value is a number.
 * Buffer.from(str[, encoding])
 * Buffer.from(array)
 * Buffer.from(buffer)
 * Buffer.from(arrayBuffer[, byteOffset[, length]])
 **/
Buffer.from = function (value, encodingOrOffset, length) {
  return from(null, value, encodingOrOffset, length)
}

if (Buffer.TYPED_ARRAY_SUPPORT) {
  Buffer.prototype.__proto__ = Uint8Array.prototype
  Buffer.__proto__ = Uint8Array
  if (typeof Symbol !== 'undefined' && Symbol.species &&
      Buffer[Symbol.species] === Buffer) {
    // Fix subarray() in ES2016. See: https://github.com/feross/buffer/pull/97
    Object.defineProperty(Buffer, Symbol.species, {
      value: null,
      configurable: true
    })
  }
}

function assertSize (size) {
  if (typeof size !== 'number') {
    throw new TypeError('"size" argument must be a number')
  } else if (size < 0) {
    throw new RangeError('"size" argument must not be negative')
  }
}

function alloc (that, size, fill, encoding) {
  assertSize(size)
  if (size <= 0) {
    return createBuffer(that, size)
  }
  if (fill !== undefined) {
    // Only pay attention to encoding if it's a string. This
    // prevents accidentally sending in a number that would
    // be interpretted as a start offset.
    return typeof encoding === 'string'
      ? createBuffer(that, size).fill(fill, encoding)
      : createBuffer(that, size).fill(fill)
  }
  return createBuffer(that, size)
}

/**
 * Creates a new filled Buffer instance.
 * alloc(size[, fill[, encoding]])
 **/
Buffer.alloc = function (size, fill, encoding) {
  return alloc(null, size, fill, encoding)
}

function allocUnsafe (that, size) {
  assertSize(size)
  that = createBuffer(that, size < 0 ? 0 : checked(size) | 0)
  if (!Buffer.TYPED_ARRAY_SUPPORT) {
    for (var i = 0; i < size; ++i) {
      that[i] = 0
    }
  }
  return that
}

/**
 * Equivalent to Buffer(num), by default creates a non-zero-filled Buffer instance.
 * */
Buffer.allocUnsafe = function (size) {
  return allocUnsafe(null, size)
}
/**
 * Equivalent to SlowBuffer(num), by default creates a non-zero-filled Buffer instance.
 */
Buffer.allocUnsafeSlow = function (size) {
  return allocUnsafe(null, size)
}

function fromString (that, string, encoding) {
  if (typeof encoding !== 'string' || encoding === '') {
    encoding = 'utf8'
  }

  if (!Buffer.isEncoding(encoding)) {
    throw new TypeError('"encoding" must be a valid string encoding')
  }

  var length = byteLength(string, encoding) | 0
  that = createBuffer(that, length)

  var actual = that.write(string, encoding)

  if (actual !== length) {
    // Writing a hex string, for example, that contains invalid characters will
    // cause everything after the first invalid character to be ignored. (e.g.
    // 'abxxcd' will be treated as 'ab')
    that = that.slice(0, actual)
  }

  return that
}

function fromArrayLike (that, array) {
  var length = array.length < 0 ? 0 : checked(array.length) | 0
  that = createBuffer(that, length)
  for (var i = 0; i < length; i += 1) {
    that[i] = array[i] & 255
  }
  return that
}

function fromArrayBuffer (that, array, byteOffset, length) {
  array.byteLength // this throws if `array` is not a valid ArrayBuffer

  if (byteOffset < 0 || array.byteLength < byteOffset) {
    throw new RangeError('\'offset\' is out of bounds')
  }

  if (array.byteLength < byteOffset + (length || 0)) {
    throw new RangeError('\'length\' is out of bounds')
  }

  if (byteOffset === undefined && length === undefined) {
    array = new Uint8Array(array)
  } else if (length === undefined) {
    array = new Uint8Array(array, byteOffset)
  } else {
    array = new Uint8Array(array, byteOffset, length)
  }

  if (Buffer.TYPED_ARRAY_SUPPORT) {
    // Return an augmented `Uint8Array` instance, for best performance
    that = array
    that.__proto__ = Buffer.prototype
  } else {
    // Fallback: Return an object instance of the Buffer class
    that = fromArrayLike(that, array)
  }
  return that
}

function fromObject (that, obj) {
  if (Buffer.isBuffer(obj)) {
    var len = checked(obj.length) | 0
    that = createBuffer(that, len)

    if (that.length === 0) {
      return that
    }

    obj.copy(that, 0, 0, len)
    return that
  }

  if (obj) {
    if ((typeof ArrayBuffer !== 'undefined' &&
        obj.buffer instanceof ArrayBuffer) || 'length' in obj) {
      if (typeof obj.length !== 'number' || isnan(obj.length)) {
        return createBuffer(that, 0)
      }
      return fromArrayLike(that, obj)
    }

    if (obj.type === 'Buffer' && isArray(obj.data)) {
      return fromArrayLike(that, obj.data)
    }
  }

  throw new TypeError('First argument must be a string, Buffer, ArrayBuffer, Array, or array-like object.')
}

function checked (length) {
  // Note: cannot use `length < kMaxLength()` here because that fails when
  // length is NaN (which is otherwise coerced to zero.)
  if (length >= kMaxLength()) {
    throw new RangeError('Attempt to allocate Buffer larger than maximum ' +
                         'size: 0x' + kMaxLength().toString(16) + ' bytes')
  }
  return length | 0
}

function SlowBuffer (length) {
  if (+length != length) { // eslint-disable-line eqeqeq
    length = 0
  }
  return Buffer.alloc(+length)
}

Buffer.isBuffer = function isBuffer (b) {
  return !!(b != null && b._isBuffer)
}

Buffer.compare = function compare (a, b) {
  if (!Buffer.isBuffer(a) || !Buffer.isBuffer(b)) {
    throw new TypeError('Arguments must be Buffers')
  }

  if (a === b) return 0

  var x = a.length
  var y = b.length

  for (var i = 0, len = Math.min(x, y); i < len; ++i) {
    if (a[i] !== b[i]) {
      x = a[i]
      y = b[i]
      break
    }
  }

  if (x < y) return -1
  if (y < x) return 1
  return 0
}

Buffer.isEncoding = function isEncoding (encoding) {
  switch (String(encoding).toLowerCase()) {
    case 'hex':
    case 'utf8':
    case 'utf-8':
    case 'ascii':
    case 'latin1':
    case 'binary':
    case 'base64':
    case 'ucs2':
    case 'ucs-2':
    case 'utf16le':
    case 'utf-16le':
      return true
    default:
      return false
  }
}

Buffer.concat = function concat (list, length) {
  if (!isArray(list)) {
    throw new TypeError('"list" argument must be an Array of Buffers')
  }

  if (list.length === 0) {
    return Buffer.alloc(0)
  }

  var i
  if (length === undefined) {
    length = 0
    for (i = 0; i < list.length; ++i) {
      length += list[i].length
    }
  }

  var buffer = Buffer.allocUnsafe(length)
  var pos = 0
  for (i = 0; i < list.length; ++i) {
    var buf = list[i]
    if (!Buffer.isBuffer(buf)) {
      throw new TypeError('"list" argument must be an Array of Buffers')
    }
    buf.copy(buffer, pos)
    pos += buf.length
  }
  return buffer
}

function byteLength (string, encoding) {
  if (Buffer.isBuffer(string)) {
    return string.length
  }
  if (typeof ArrayBuffer !== 'undefined' && typeof ArrayBuffer.isView === 'function' &&
      (ArrayBuffer.isView(string) || string instanceof ArrayBuffer)) {
    return string.byteLength
  }
  if (typeof string !== 'string') {
    string = '' + string
  }

  var len = string.length
  if (len === 0) return 0

  // Use a for loop to avoid recursion
  var loweredCase = false
  for (;;) {
    switch (encoding) {
      case 'ascii':
      case 'latin1':
      case 'binary':
        return len
      case 'utf8':
      case 'utf-8':
      case undefined:
        return utf8ToBytes(string).length
      case 'ucs2':
      case 'ucs-2':
      case 'utf16le':
      case 'utf-16le':
        return len * 2
      case 'hex':
        return len >>> 1
      case 'base64':
        return base64ToBytes(string).length
      default:
        if (loweredCase) return utf8ToBytes(string).length // assume utf8
        encoding = ('' + encoding).toLowerCase()
        loweredCase = true
    }
  }
}
Buffer.byteLength = byteLength

function slowToString (encoding, start, end) {
  var loweredCase = false

  // No need to verify that "this.length <= MAX_UINT32" since it's a read-only
  // property of a typed array.

  // This behaves neither like String nor Uint8Array in that we set start/end
  // to their upper/lower bounds if the value passed is out of range.
  // undefined is handled specially as per ECMA-262 6th Edition,
  // Section 13.3.3.7 Runtime Semantics: KeyedBindingInitialization.
  if (start === undefined || start < 0) {
    start = 0
  }
  // Return early if start > this.length. Done here to prevent potential uint32
  // coercion fail below.
  if (start > this.length) {
    return ''
  }

  if (end === undefined || end > this.length) {
    end = this.length
  }

  if (end <= 0) {
    return ''
  }

  // Force coersion to uint32. This will also coerce falsey/NaN values to 0.
  end >>>= 0
  start >>>= 0

  if (end <= start) {
    return ''
  }

  if (!encoding) encoding = 'utf8'

  while (true) {
    switch (encoding) {
      case 'hex':
        return hexSlice(this, start, end)

      case 'utf8':
      case 'utf-8':
        return utf8Slice(this, start, end)

      case 'ascii':
        return asciiSlice(this, start, end)

      case 'latin1':
      case 'binary':
        return latin1Slice(this, start, end)

      case 'base64':
        return base64Slice(this, start, end)

      case 'ucs2':
      case 'ucs-2':
      case 'utf16le':
      case 'utf-16le':
        return utf16leSlice(this, start, end)

      default:
        if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
        encoding = (encoding + '').toLowerCase()
        loweredCase = true
    }
  }
}

// The property is used by `Buffer.isBuffer` and `is-buffer` (in Safari 5-7) to detect
// Buffer instances.
Buffer.prototype._isBuffer = true

function swap (b, n, m) {
  var i = b[n]
  b[n] = b[m]
  b[m] = i
}

Buffer.prototype.swap16 = function swap16 () {
  var len = this.length
  if (len % 2 !== 0) {
    throw new RangeError('Buffer size must be a multiple of 16-bits')
  }
  for (var i = 0; i < len; i += 2) {
    swap(this, i, i + 1)
  }
  return this
}

Buffer.prototype.swap32 = function swap32 () {
  var len = this.length
  if (len % 4 !== 0) {
    throw new RangeError('Buffer size must be a multiple of 32-bits')
  }
  for (var i = 0; i < len; i += 4) {
    swap(this, i, i + 3)
    swap(this, i + 1, i + 2)
  }
  return this
}

Buffer.prototype.swap64 = function swap64 () {
  var len = this.length
  if (len % 8 !== 0) {
    throw new RangeError('Buffer size must be a multiple of 64-bits')
  }
  for (var i = 0; i < len; i += 8) {
    swap(this, i, i + 7)
    swap(this, i + 1, i + 6)
    swap(this, i + 2, i + 5)
    swap(this, i + 3, i + 4)
  }
  return this
}

Buffer.prototype.toString = function toString () {
  var length = this.length | 0
  if (length === 0) return ''
  if (arguments.length === 0) return utf8Slice(this, 0, length)
  return slowToString.apply(this, arguments)
}

Buffer.prototype.equals = function equals (b) {
  if (!Buffer.isBuffer(b)) throw new TypeError('Argument must be a Buffer')
  if (this === b) return true
  return Buffer.compare(this, b) === 0
}

Buffer.prototype.inspect = function inspect () {
  var str = ''
  var max = exports.INSPECT_MAX_BYTES
  if (this.length > 0) {
    str = this.toString('hex', 0, max).match(/.{2}/g).join(' ')
    if (this.length > max) str += ' ... '
  }
  return '<Buffer ' + str + '>'
}

Buffer.prototype.compare = function compare (target, start, end, thisStart, thisEnd) {
  if (!Buffer.isBuffer(target)) {
    throw new TypeError('Argument must be a Buffer')
  }

  if (start === undefined) {
    start = 0
  }
  if (end === undefined) {
    end = target ? target.length : 0
  }
  if (thisStart === undefined) {
    thisStart = 0
  }
  if (thisEnd === undefined) {
    thisEnd = this.length
  }

  if (start < 0 || end > target.length || thisStart < 0 || thisEnd > this.length) {
    throw new RangeError('out of range index')
  }

  if (thisStart >= thisEnd && start >= end) {
    return 0
  }
  if (thisStart >= thisEnd) {
    return -1
  }
  if (start >= end) {
    return 1
  }

  start >>>= 0
  end >>>= 0
  thisStart >>>= 0
  thisEnd >>>= 0

  if (this === target) return 0

  var x = thisEnd - thisStart
  var y = end - start
  var len = Math.min(x, y)

  var thisCopy = this.slice(thisStart, thisEnd)
  var targetCopy = target.slice(start, end)

  for (var i = 0; i < len; ++i) {
    if (thisCopy[i] !== targetCopy[i]) {
      x = thisCopy[i]
      y = targetCopy[i]
      break
    }
  }

  if (x < y) return -1
  if (y < x) return 1
  return 0
}

// Finds either the first index of `val` in `buffer` at offset >= `byteOffset`,
// OR the last index of `val` in `buffer` at offset <= `byteOffset`.
//
// Arguments:
// - buffer - a Buffer to search
// - val - a string, Buffer, or number
// - byteOffset - an index into `buffer`; will be clamped to an int32
// - encoding - an optional encoding, relevant is val is a string
// - dir - true for indexOf, false for lastIndexOf
function bidirectionalIndexOf (buffer, val, byteOffset, encoding, dir) {
  // Empty buffer means no match
  if (buffer.length === 0) return -1

  // Normalize byteOffset
  if (typeof byteOffset === 'string') {
    encoding = byteOffset
    byteOffset = 0
  } else if (byteOffset > 0x7fffffff) {
    byteOffset = 0x7fffffff
  } else if (byteOffset < -0x80000000) {
    byteOffset = -0x80000000
  }
  byteOffset = +byteOffset  // Coerce to Number.
  if (isNaN(byteOffset)) {
    // byteOffset: it it's undefined, null, NaN, "foo", etc, search whole buffer
    byteOffset = dir ? 0 : (buffer.length - 1)
  }

  // Normalize byteOffset: negative offsets start from the end of the buffer
  if (byteOffset < 0) byteOffset = buffer.length + byteOffset
  if (byteOffset >= buffer.length) {
    if (dir) return -1
    else byteOffset = buffer.length - 1
  } else if (byteOffset < 0) {
    if (dir) byteOffset = 0
    else return -1
  }

  // Normalize val
  if (typeof val === 'string') {
    val = Buffer.from(val, encoding)
  }

  // Finally, search either indexOf (if dir is true) or lastIndexOf
  if (Buffer.isBuffer(val)) {
    // Special case: looking for empty string/buffer always fails
    if (val.length === 0) {
      return -1
    }
    return arrayIndexOf(buffer, val, byteOffset, encoding, dir)
  } else if (typeof val === 'number') {
    val = val & 0xFF // Search for a byte value [0-255]
    if (Buffer.TYPED_ARRAY_SUPPORT &&
        typeof Uint8Array.prototype.indexOf === 'function') {
      if (dir) {
        return Uint8Array.prototype.indexOf.call(buffer, val, byteOffset)
      } else {
        return Uint8Array.prototype.lastIndexOf.call(buffer, val, byteOffset)
      }
    }
    return arrayIndexOf(buffer, [ val ], byteOffset, encoding, dir)
  }

  throw new TypeError('val must be string, number or Buffer')
}

function arrayIndexOf (arr, val, byteOffset, encoding, dir) {
  var indexSize = 1
  var arrLength = arr.length
  var valLength = val.length

  if (encoding !== undefined) {
    encoding = String(encoding).toLowerCase()
    if (encoding === 'ucs2' || encoding === 'ucs-2' ||
        encoding === 'utf16le' || encoding === 'utf-16le') {
      if (arr.length < 2 || val.length < 2) {
        return -1
      }
      indexSize = 2
      arrLength /= 2
      valLength /= 2
      byteOffset /= 2
    }
  }

  function read (buf, i) {
    if (indexSize === 1) {
      return buf[i]
    } else {
      return buf.readUInt16BE(i * indexSize)
    }
  }

  var i
  if (dir) {
    var foundIndex = -1
    for (i = byteOffset; i < arrLength; i++) {
      if (read(arr, i) === read(val, foundIndex === -1 ? 0 : i - foundIndex)) {
        if (foundIndex === -1) foundIndex = i
        if (i - foundIndex + 1 === valLength) return foundIndex * indexSize
      } else {
        if (foundIndex !== -1) i -= i - foundIndex
        foundIndex = -1
      }
    }
  } else {
    if (byteOffset + valLength > arrLength) byteOffset = arrLength - valLength
    for (i = byteOffset; i >= 0; i--) {
      var found = true
      for (var j = 0; j < valLength; j++) {
        if (read(arr, i + j) !== read(val, j)) {
          found = false
          break
        }
      }
      if (found) return i
    }
  }

  return -1
}

Buffer.prototype.includes = function includes (val, byteOffset, encoding) {
  return this.indexOf(val, byteOffset, encoding) !== -1
}

Buffer.prototype.indexOf = function indexOf (val, byteOffset, encoding) {
  return bidirectionalIndexOf(this, val, byteOffset, encoding, true)
}

Buffer.prototype.lastIndexOf = function lastIndexOf (val, byteOffset, encoding) {
  return bidirectionalIndexOf(this, val, byteOffset, encoding, false)
}

function hexWrite (buf, string, offset, length) {
  offset = Number(offset) || 0
  var remaining = buf.length - offset
  if (!length) {
    length = remaining
  } else {
    length = Number(length)
    if (length > remaining) {
      length = remaining
    }
  }

  // must be an even number of digits
  var strLen = string.length
  if (strLen % 2 !== 0) throw new TypeError('Invalid hex string')

  if (length > strLen / 2) {
    length = strLen / 2
  }
  for (var i = 0; i < length; ++i) {
    var parsed = parseInt(string.substr(i * 2, 2), 16)
    if (isNaN(parsed)) return i
    buf[offset + i] = parsed
  }
  return i
}

function utf8Write (buf, string, offset, length) {
  return blitBuffer(utf8ToBytes(string, buf.length - offset), buf, offset, length)
}

function asciiWrite (buf, string, offset, length) {
  return blitBuffer(asciiToBytes(string), buf, offset, length)
}

function latin1Write (buf, string, offset, length) {
  return asciiWrite(buf, string, offset, length)
}

function base64Write (buf, string, offset, length) {
  return blitBuffer(base64ToBytes(string), buf, offset, length)
}

function ucs2Write (buf, string, offset, length) {
  return blitBuffer(utf16leToBytes(string, buf.length - offset), buf, offset, length)
}

Buffer.prototype.write = function write (string, offset, length, encoding) {
  // Buffer#write(string)
  if (offset === undefined) {
    encoding = 'utf8'
    length = this.length
    offset = 0
  // Buffer#write(string, encoding)
  } else if (length === undefined && typeof offset === 'string') {
    encoding = offset
    length = this.length
    offset = 0
  // Buffer#write(string, offset[, length][, encoding])
  } else if (isFinite(offset)) {
    offset = offset | 0
    if (isFinite(length)) {
      length = length | 0
      if (encoding === undefined) encoding = 'utf8'
    } else {
      encoding = length
      length = undefined
    }
  // legacy write(string, encoding, offset, length) - remove in v0.13
  } else {
    throw new Error(
      'Buffer.write(string, encoding, offset[, length]) is no longer supported'
    )
  }

  var remaining = this.length - offset
  if (length === undefined || length > remaining) length = remaining

  if ((string.length > 0 && (length < 0 || offset < 0)) || offset > this.length) {
    throw new RangeError('Attempt to write outside buffer bounds')
  }

  if (!encoding) encoding = 'utf8'

  var loweredCase = false
  for (;;) {
    switch (encoding) {
      case 'hex':
        return hexWrite(this, string, offset, length)

      case 'utf8':
      case 'utf-8':
        return utf8Write(this, string, offset, length)

      case 'ascii':
        return asciiWrite(this, string, offset, length)

      case 'latin1':
      case 'binary':
        return latin1Write(this, string, offset, length)

      case 'base64':
        // Warning: maxLength not taken into account in base64Write
        return base64Write(this, string, offset, length)

      case 'ucs2':
      case 'ucs-2':
      case 'utf16le':
      case 'utf-16le':
        return ucs2Write(this, string, offset, length)

      default:
        if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
        encoding = ('' + encoding).toLowerCase()
        loweredCase = true
    }
  }
}

Buffer.prototype.toJSON = function toJSON () {
  return {
    type: 'Buffer',
    data: Array.prototype.slice.call(this._arr || this, 0)
  }
}

function base64Slice (buf, start, end) {
  if (start === 0 && end === buf.length) {
    return base64.fromByteArray(buf)
  } else {
    return base64.fromByteArray(buf.slice(start, end))
  }
}

function utf8Slice (buf, start, end) {
  end = Math.min(buf.length, end)
  var res = []

  var i = start
  while (i < end) {
    var firstByte = buf[i]
    var codePoint = null
    var bytesPerSequence = (firstByte > 0xEF) ? 4
      : (firstByte > 0xDF) ? 3
      : (firstByte > 0xBF) ? 2
      : 1

    if (i + bytesPerSequence <= end) {
      var secondByte, thirdByte, fourthByte, tempCodePoint

      switch (bytesPerSequence) {
        case 1:
          if (firstByte < 0x80) {
            codePoint = firstByte
          }
          break
        case 2:
          secondByte = buf[i + 1]
          if ((secondByte & 0xC0) === 0x80) {
            tempCodePoint = (firstByte & 0x1F) << 0x6 | (secondByte & 0x3F)
            if (tempCodePoint > 0x7F) {
              codePoint = tempCodePoint
            }
          }
          break
        case 3:
          secondByte = buf[i + 1]
          thirdByte = buf[i + 2]
          if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80) {
            tempCodePoint = (firstByte & 0xF) << 0xC | (secondByte & 0x3F) << 0x6 | (thirdByte & 0x3F)
            if (tempCodePoint > 0x7FF && (tempCodePoint < 0xD800 || tempCodePoint > 0xDFFF)) {
              codePoint = tempCodePoint
            }
          }
          break
        case 4:
          secondByte = buf[i + 1]
          thirdByte = buf[i + 2]
          fourthByte = buf[i + 3]
          if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80 && (fourthByte & 0xC0) === 0x80) {
            tempCodePoint = (firstByte & 0xF) << 0x12 | (secondByte & 0x3F) << 0xC | (thirdByte & 0x3F) << 0x6 | (fourthByte & 0x3F)
            if (tempCodePoint > 0xFFFF && tempCodePoint < 0x110000) {
              codePoint = tempCodePoint
            }
          }
      }
    }

    if (codePoint === null) {
      // we did not generate a valid codePoint so insert a
      // replacement char (U+FFFD) and advance only 1 byte
      codePoint = 0xFFFD
      bytesPerSequence = 1
    } else if (codePoint > 0xFFFF) {
      // encode to utf16 (surrogate pair dance)
      codePoint -= 0x10000
      res.push(codePoint >>> 10 & 0x3FF | 0xD800)
      codePoint = 0xDC00 | codePoint & 0x3FF
    }

    res.push(codePoint)
    i += bytesPerSequence
  }

  return decodeCodePointsArray(res)
}

// Based on http://stackoverflow.com/a/22747272/680742, the browser with
// the lowest limit is Chrome, with 0x10000 args.
// We go 1 magnitude less, for safety
var MAX_ARGUMENTS_LENGTH = 0x1000

function decodeCodePointsArray (codePoints) {
  var len = codePoints.length
  if (len <= MAX_ARGUMENTS_LENGTH) {
    return String.fromCharCode.apply(String, codePoints) // avoid extra slice()
  }

  // Decode in chunks to avoid "call stack size exceeded".
  var res = ''
  var i = 0
  while (i < len) {
    res += String.fromCharCode.apply(
      String,
      codePoints.slice(i, i += MAX_ARGUMENTS_LENGTH)
    )
  }
  return res
}

function asciiSlice (buf, start, end) {
  var ret = ''
  end = Math.min(buf.length, end)

  for (var i = start; i < end; ++i) {
    ret += String.fromCharCode(buf[i] & 0x7F)
  }
  return ret
}

function latin1Slice (buf, start, end) {
  var ret = ''
  end = Math.min(buf.length, end)

  for (var i = start; i < end; ++i) {
    ret += String.fromCharCode(buf[i])
  }
  return ret
}

function hexSlice (buf, start, end) {
  var len = buf.length

  if (!start || start < 0) start = 0
  if (!end || end < 0 || end > len) end = len

  var out = ''
  for (var i = start; i < end; ++i) {
    out += toHex(buf[i])
  }
  return out
}

function utf16leSlice (buf, start, end) {
  var bytes = buf.slice(start, end)
  var res = ''
  for (var i = 0; i < bytes.length; i += 2) {
    res += String.fromCharCode(bytes[i] + bytes[i + 1] * 256)
  }
  return res
}

Buffer.prototype.slice = function slice (start, end) {
  var len = this.length
  start = ~~start
  end = end === undefined ? len : ~~end

  if (start < 0) {
    start += len
    if (start < 0) start = 0
  } else if (start > len) {
    start = len
  }

  if (end < 0) {
    end += len
    if (end < 0) end = 0
  } else if (end > len) {
    end = len
  }

  if (end < start) end = start

  var newBuf
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    newBuf = this.subarray(start, end)
    newBuf.__proto__ = Buffer.prototype
  } else {
    var sliceLen = end - start
    newBuf = new Buffer(sliceLen, undefined)
    for (var i = 0; i < sliceLen; ++i) {
      newBuf[i] = this[i + start]
    }
  }

  return newBuf
}

/*
 * Need to make sure that buffer isn't trying to write out of bounds.
 */
function checkOffset (offset, ext, length) {
  if ((offset % 1) !== 0 || offset < 0) throw new RangeError('offset is not uint')
  if (offset + ext > length) throw new RangeError('Trying to access beyond buffer length')
}

Buffer.prototype.readUIntLE = function readUIntLE (offset, byteLength, noAssert) {
  offset = offset | 0
  byteLength = byteLength | 0
  if (!noAssert) checkOffset(offset, byteLength, this.length)

  var val = this[offset]
  var mul = 1
  var i = 0
  while (++i < byteLength && (mul *= 0x100)) {
    val += this[offset + i] * mul
  }

  return val
}

Buffer.prototype.readUIntBE = function readUIntBE (offset, byteLength, noAssert) {
  offset = offset | 0
  byteLength = byteLength | 0
  if (!noAssert) {
    checkOffset(offset, byteLength, this.length)
  }

  var val = this[offset + --byteLength]
  var mul = 1
  while (byteLength > 0 && (mul *= 0x100)) {
    val += this[offset + --byteLength] * mul
  }

  return val
}

Buffer.prototype.readUInt8 = function readUInt8 (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 1, this.length)
  return this[offset]
}

Buffer.prototype.readUInt16LE = function readUInt16LE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 2, this.length)
  return this[offset] | (this[offset + 1] << 8)
}

Buffer.prototype.readUInt16BE = function readUInt16BE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 2, this.length)
  return (this[offset] << 8) | this[offset + 1]
}

Buffer.prototype.readUInt32LE = function readUInt32LE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 4, this.length)

  return ((this[offset]) |
      (this[offset + 1] << 8) |
      (this[offset + 2] << 16)) +
      (this[offset + 3] * 0x1000000)
}

Buffer.prototype.readUInt32BE = function readUInt32BE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 4, this.length)

  return (this[offset] * 0x1000000) +
    ((this[offset + 1] << 16) |
    (this[offset + 2] << 8) |
    this[offset + 3])
}

Buffer.prototype.readIntLE = function readIntLE (offset, byteLength, noAssert) {
  offset = offset | 0
  byteLength = byteLength | 0
  if (!noAssert) checkOffset(offset, byteLength, this.length)

  var val = this[offset]
  var mul = 1
  var i = 0
  while (++i < byteLength && (mul *= 0x100)) {
    val += this[offset + i] * mul
  }
  mul *= 0x80

  if (val >= mul) val -= Math.pow(2, 8 * byteLength)

  return val
}

Buffer.prototype.readIntBE = function readIntBE (offset, byteLength, noAssert) {
  offset = offset | 0
  byteLength = byteLength | 0
  if (!noAssert) checkOffset(offset, byteLength, this.length)

  var i = byteLength
  var mul = 1
  var val = this[offset + --i]
  while (i > 0 && (mul *= 0x100)) {
    val += this[offset + --i] * mul
  }
  mul *= 0x80

  if (val >= mul) val -= Math.pow(2, 8 * byteLength)

  return val
}

Buffer.prototype.readInt8 = function readInt8 (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 1, this.length)
  if (!(this[offset] & 0x80)) return (this[offset])
  return ((0xff - this[offset] + 1) * -1)
}

Buffer.prototype.readInt16LE = function readInt16LE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 2, this.length)
  var val = this[offset] | (this[offset + 1] << 8)
  return (val & 0x8000) ? val | 0xFFFF0000 : val
}

Buffer.prototype.readInt16BE = function readInt16BE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 2, this.length)
  var val = this[offset + 1] | (this[offset] << 8)
  return (val & 0x8000) ? val | 0xFFFF0000 : val
}

Buffer.prototype.readInt32LE = function readInt32LE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 4, this.length)

  return (this[offset]) |
    (this[offset + 1] << 8) |
    (this[offset + 2] << 16) |
    (this[offset + 3] << 24)
}

Buffer.prototype.readInt32BE = function readInt32BE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 4, this.length)

  return (this[offset] << 24) |
    (this[offset + 1] << 16) |
    (this[offset + 2] << 8) |
    (this[offset + 3])
}

Buffer.prototype.readFloatLE = function readFloatLE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 4, this.length)
  return ieee754.read(this, offset, true, 23, 4)
}

Buffer.prototype.readFloatBE = function readFloatBE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 4, this.length)
  return ieee754.read(this, offset, false, 23, 4)
}

Buffer.prototype.readDoubleLE = function readDoubleLE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 8, this.length)
  return ieee754.read(this, offset, true, 52, 8)
}

Buffer.prototype.readDoubleBE = function readDoubleBE (offset, noAssert) {
  if (!noAssert) checkOffset(offset, 8, this.length)
  return ieee754.read(this, offset, false, 52, 8)
}

function checkInt (buf, value, offset, ext, max, min) {
  if (!Buffer.isBuffer(buf)) throw new TypeError('"buffer" argument must be a Buffer instance')
  if (value > max || value < min) throw new RangeError('"value" argument is out of bounds')
  if (offset + ext > buf.length) throw new RangeError('Index out of range')
}

Buffer.prototype.writeUIntLE = function writeUIntLE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset | 0
  byteLength = byteLength | 0
  if (!noAssert) {
    var maxBytes = Math.pow(2, 8 * byteLength) - 1
    checkInt(this, value, offset, byteLength, maxBytes, 0)
  }

  var mul = 1
  var i = 0
  this[offset] = value & 0xFF
  while (++i < byteLength && (mul *= 0x100)) {
    this[offset + i] = (value / mul) & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeUIntBE = function writeUIntBE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset | 0
  byteLength = byteLength | 0
  if (!noAssert) {
    var maxBytes = Math.pow(2, 8 * byteLength) - 1
    checkInt(this, value, offset, byteLength, maxBytes, 0)
  }

  var i = byteLength - 1
  var mul = 1
  this[offset + i] = value & 0xFF
  while (--i >= 0 && (mul *= 0x100)) {
    this[offset + i] = (value / mul) & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeUInt8 = function writeUInt8 (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 1, 0xff, 0)
  if (!Buffer.TYPED_ARRAY_SUPPORT) value = Math.floor(value)
  this[offset] = (value & 0xff)
  return offset + 1
}

function objectWriteUInt16 (buf, value, offset, littleEndian) {
  if (value < 0) value = 0xffff + value + 1
  for (var i = 0, j = Math.min(buf.length - offset, 2); i < j; ++i) {
    buf[offset + i] = (value & (0xff << (8 * (littleEndian ? i : 1 - i)))) >>>
      (littleEndian ? i : 1 - i) * 8
  }
}

Buffer.prototype.writeUInt16LE = function writeUInt16LE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0)
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset] = (value & 0xff)
    this[offset + 1] = (value >>> 8)
  } else {
    objectWriteUInt16(this, value, offset, true)
  }
  return offset + 2
}

Buffer.prototype.writeUInt16BE = function writeUInt16BE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0)
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset] = (value >>> 8)
    this[offset + 1] = (value & 0xff)
  } else {
    objectWriteUInt16(this, value, offset, false)
  }
  return offset + 2
}

function objectWriteUInt32 (buf, value, offset, littleEndian) {
  if (value < 0) value = 0xffffffff + value + 1
  for (var i = 0, j = Math.min(buf.length - offset, 4); i < j; ++i) {
    buf[offset + i] = (value >>> (littleEndian ? i : 3 - i) * 8) & 0xff
  }
}

Buffer.prototype.writeUInt32LE = function writeUInt32LE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0)
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset + 3] = (value >>> 24)
    this[offset + 2] = (value >>> 16)
    this[offset + 1] = (value >>> 8)
    this[offset] = (value & 0xff)
  } else {
    objectWriteUInt32(this, value, offset, true)
  }
  return offset + 4
}

Buffer.prototype.writeUInt32BE = function writeUInt32BE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0)
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset] = (value >>> 24)
    this[offset + 1] = (value >>> 16)
    this[offset + 2] = (value >>> 8)
    this[offset + 3] = (value & 0xff)
  } else {
    objectWriteUInt32(this, value, offset, false)
  }
  return offset + 4
}

Buffer.prototype.writeIntLE = function writeIntLE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) {
    var limit = Math.pow(2, 8 * byteLength - 1)

    checkInt(this, value, offset, byteLength, limit - 1, -limit)
  }

  var i = 0
  var mul = 1
  var sub = 0
  this[offset] = value & 0xFF
  while (++i < byteLength && (mul *= 0x100)) {
    if (value < 0 && sub === 0 && this[offset + i - 1] !== 0) {
      sub = 1
    }
    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeIntBE = function writeIntBE (value, offset, byteLength, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) {
    var limit = Math.pow(2, 8 * byteLength - 1)

    checkInt(this, value, offset, byteLength, limit - 1, -limit)
  }

  var i = byteLength - 1
  var mul = 1
  var sub = 0
  this[offset + i] = value & 0xFF
  while (--i >= 0 && (mul *= 0x100)) {
    if (value < 0 && sub === 0 && this[offset + i + 1] !== 0) {
      sub = 1
    }
    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF
  }

  return offset + byteLength
}

Buffer.prototype.writeInt8 = function writeInt8 (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 1, 0x7f, -0x80)
  if (!Buffer.TYPED_ARRAY_SUPPORT) value = Math.floor(value)
  if (value < 0) value = 0xff + value + 1
  this[offset] = (value & 0xff)
  return offset + 1
}

Buffer.prototype.writeInt16LE = function writeInt16LE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000)
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset] = (value & 0xff)
    this[offset + 1] = (value >>> 8)
  } else {
    objectWriteUInt16(this, value, offset, true)
  }
  return offset + 2
}

Buffer.prototype.writeInt16BE = function writeInt16BE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000)
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset] = (value >>> 8)
    this[offset + 1] = (value & 0xff)
  } else {
    objectWriteUInt16(this, value, offset, false)
  }
  return offset + 2
}

Buffer.prototype.writeInt32LE = function writeInt32LE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000)
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset] = (value & 0xff)
    this[offset + 1] = (value >>> 8)
    this[offset + 2] = (value >>> 16)
    this[offset + 3] = (value >>> 24)
  } else {
    objectWriteUInt32(this, value, offset, true)
  }
  return offset + 4
}

Buffer.prototype.writeInt32BE = function writeInt32BE (value, offset, noAssert) {
  value = +value
  offset = offset | 0
  if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000)
  if (value < 0) value = 0xffffffff + value + 1
  if (Buffer.TYPED_ARRAY_SUPPORT) {
    this[offset] = (value >>> 24)
    this[offset + 1] = (value >>> 16)
    this[offset + 2] = (value >>> 8)
    this[offset + 3] = (value & 0xff)
  } else {
    objectWriteUInt32(this, value, offset, false)
  }
  return offset + 4
}

function checkIEEE754 (buf, value, offset, ext, max, min) {
  if (offset + ext > buf.length) throw new RangeError('Index out of range')
  if (offset < 0) throw new RangeError('Index out of range')
}

function writeFloat (buf, value, offset, littleEndian, noAssert) {
  if (!noAssert) {
    checkIEEE754(buf, value, offset, 4, 3.4028234663852886e+38, -3.4028234663852886e+38)
  }
  ieee754.write(buf, value, offset, littleEndian, 23, 4)
  return offset + 4
}

Buffer.prototype.writeFloatLE = function writeFloatLE (value, offset, noAssert) {
  return writeFloat(this, value, offset, true, noAssert)
}

Buffer.prototype.writeFloatBE = function writeFloatBE (value, offset, noAssert) {
  return writeFloat(this, value, offset, false, noAssert)
}

function writeDouble (buf, value, offset, littleEndian, noAssert) {
  if (!noAssert) {
    checkIEEE754(buf, value, offset, 8, 1.7976931348623157E+308, -1.7976931348623157E+308)
  }
  ieee754.write(buf, value, offset, littleEndian, 52, 8)
  return offset + 8
}

Buffer.prototype.writeDoubleLE = function writeDoubleLE (value, offset, noAssert) {
  return writeDouble(this, value, offset, true, noAssert)
}

Buffer.prototype.writeDoubleBE = function writeDoubleBE (value, offset, noAssert) {
  return writeDouble(this, value, offset, false, noAssert)
}

// copy(targetBuffer, targetStart=0, sourceStart=0, sourceEnd=buffer.length)
Buffer.prototype.copy = function copy (target, targetStart, start, end) {
  if (!start) start = 0
  if (!end && end !== 0) end = this.length
  if (targetStart >= target.length) targetStart = target.length
  if (!targetStart) targetStart = 0
  if (end > 0 && end < start) end = start

  // Copy 0 bytes; we're done
  if (end === start) return 0
  if (target.length === 0 || this.length === 0) return 0

  // Fatal error conditions
  if (targetStart < 0) {
    throw new RangeError('targetStart out of bounds')
  }
  if (start < 0 || start >= this.length) throw new RangeError('sourceStart out of bounds')
  if (end < 0) throw new RangeError('sourceEnd out of bounds')

  // Are we oob?
  if (end > this.length) end = this.length
  if (target.length - targetStart < end - start) {
    end = target.length - targetStart + start
  }

  var len = end - start
  var i

  if (this === target && start < targetStart && targetStart < end) {
    // descending copy from end
    for (i = len - 1; i >= 0; --i) {
      target[i + targetStart] = this[i + start]
    }
  } else if (len < 1000 || !Buffer.TYPED_ARRAY_SUPPORT) {
    // ascending copy from start
    for (i = 0; i < len; ++i) {
      target[i + targetStart] = this[i + start]
    }
  } else {
    Uint8Array.prototype.set.call(
      target,
      this.subarray(start, start + len),
      targetStart
    )
  }

  return len
}

// Usage:
//    buffer.fill(number[, offset[, end]])
//    buffer.fill(buffer[, offset[, end]])
//    buffer.fill(string[, offset[, end]][, encoding])
Buffer.prototype.fill = function fill (val, start, end, encoding) {
  // Handle string cases:
  if (typeof val === 'string') {
    if (typeof start === 'string') {
      encoding = start
      start = 0
      end = this.length
    } else if (typeof end === 'string') {
      encoding = end
      end = this.length
    }
    if (val.length === 1) {
      var code = val.charCodeAt(0)
      if (code < 256) {
        val = code
      }
    }
    if (encoding !== undefined && typeof encoding !== 'string') {
      throw new TypeError('encoding must be a string')
    }
    if (typeof encoding === 'string' && !Buffer.isEncoding(encoding)) {
      throw new TypeError('Unknown encoding: ' + encoding)
    }
  } else if (typeof val === 'number') {
    val = val & 255
  }

  // Invalid ranges are not set to a default, so can range check early.
  if (start < 0 || this.length < start || this.length < end) {
    throw new RangeError('Out of range index')
  }

  if (end <= start) {
    return this
  }

  start = start >>> 0
  end = end === undefined ? this.length : end >>> 0

  if (!val) val = 0

  var i
  if (typeof val === 'number') {
    for (i = start; i < end; ++i) {
      this[i] = val
    }
  } else {
    var bytes = Buffer.isBuffer(val)
      ? val
      : utf8ToBytes(new Buffer(val, encoding).toString())
    var len = bytes.length
    for (i = 0; i < end - start; ++i) {
      this[i + start] = bytes[i % len]
    }
  }

  return this
}

// HELPER FUNCTIONS
// ================

var INVALID_BASE64_RE = /[^+\/0-9A-Za-z-_]/g

function base64clean (str) {
  // Node strips out invalid characters like \n and \t from the string, base64-js does not
  str = stringtrim(str).replace(INVALID_BASE64_RE, '')
  // Node converts strings with length < 2 to ''
  if (str.length < 2) return ''
  // Node allows for non-padded base64 strings (missing trailing ===), base64-js does not
  while (str.length % 4 !== 0) {
    str = str + '='
  }
  return str
}

function stringtrim (str) {
  if (str.trim) return str.trim()
  return str.replace(/^\s+|\s+$/g, '')
}

function toHex (n) {
  if (n < 16) return '0' + n.toString(16)
  return n.toString(16)
}

function utf8ToBytes (string, units) {
  units = units || Infinity
  var codePoint
  var length = string.length
  var leadSurrogate = null
  var bytes = []

  for (var i = 0; i < length; ++i) {
    codePoint = string.charCodeAt(i)

    // is surrogate component
    if (codePoint > 0xD7FF && codePoint < 0xE000) {
      // last char was a lead
      if (!leadSurrogate) {
        // no lead yet
        if (codePoint > 0xDBFF) {
          // unexpected trail
          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
          continue
        } else if (i + 1 === length) {
          // unpaired lead
          if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
          continue
        }

        // valid lead
        leadSurrogate = codePoint

        continue
      }

      // 2 leads in a row
      if (codePoint < 0xDC00) {
        if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
        leadSurrogate = codePoint
        continue
      }

      // valid surrogate pair
      codePoint = (leadSurrogate - 0xD800 << 10 | codePoint - 0xDC00) + 0x10000
    } else if (leadSurrogate) {
      // valid bmp char, but last char was a lead
      if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
    }

    leadSurrogate = null

    // encode utf8
    if (codePoint < 0x80) {
      if ((units -= 1) < 0) break
      bytes.push(codePoint)
    } else if (codePoint < 0x800) {
      if ((units -= 2) < 0) break
      bytes.push(
        codePoint >> 0x6 | 0xC0,
        codePoint & 0x3F | 0x80
      )
    } else if (codePoint < 0x10000) {
      if ((units -= 3) < 0) break
      bytes.push(
        codePoint >> 0xC | 0xE0,
        codePoint >> 0x6 & 0x3F | 0x80,
        codePoint & 0x3F | 0x80
      )
    } else if (codePoint < 0x110000) {
      if ((units -= 4) < 0) break
      bytes.push(
        codePoint >> 0x12 | 0xF0,
        codePoint >> 0xC & 0x3F | 0x80,
        codePoint >> 0x6 & 0x3F | 0x80,
        codePoint & 0x3F | 0x80
      )
    } else {
      throw new Error('Invalid code point')
    }
  }

  return bytes
}

function asciiToBytes (str) {
  var byteArray = []
  for (var i = 0; i < str.length; ++i) {
    // Node's code seems to be doing this and not & 0x7F..
    byteArray.push(str.charCodeAt(i) & 0xFF)
  }
  return byteArray
}

function utf16leToBytes (str, units) {
  var c, hi, lo
  var byteArray = []
  for (var i = 0; i < str.length; ++i) {
    if ((units -= 2) < 0) break

    c = str.charCodeAt(i)
    hi = c >> 8
    lo = c % 256
    byteArray.push(lo)
    byteArray.push(hi)
  }

  return byteArray
}

function base64ToBytes (str) {
  return base64.toByteArray(base64clean(str))
}

function blitBuffer (src, dst, offset, length) {
  for (var i = 0; i < length; ++i) {
    if ((i + offset >= dst.length) || (i >= src.length)) break
    dst[i + offset] = src[i]
  }
  return i
}

function isnan (val) {
  return val !== val // eslint-disable-line no-self-compare
}

/* WEBPACK VAR INJECTION */}.call(exports, __webpack_require__(8)))

/***/ }),
/* 5 */
/***/ (function(module, exports) {

exports.read = function (buffer, offset, isLE, mLen, nBytes) {
  var e, m
  var eLen = nBytes * 8 - mLen - 1
  var eMax = (1 << eLen) - 1
  var eBias = eMax >> 1
  var nBits = -7
  var i = isLE ? (nBytes - 1) : 0
  var d = isLE ? -1 : 1
  var s = buffer[offset + i]

  i += d

  e = s & ((1 << (-nBits)) - 1)
  s >>= (-nBits)
  nBits += eLen
  for (; nBits > 0; e = e * 256 + buffer[offset + i], i += d, nBits -= 8) {}

  m = e & ((1 << (-nBits)) - 1)
  e >>= (-nBits)
  nBits += mLen
  for (; nBits > 0; m = m * 256 + buffer[offset + i], i += d, nBits -= 8) {}

  if (e === 0) {
    e = 1 - eBias
  } else if (e === eMax) {
    return m ? NaN : ((s ? -1 : 1) * Infinity)
  } else {
    m = m + Math.pow(2, mLen)
    e = e - eBias
  }
  return (s ? -1 : 1) * m * Math.pow(2, e - mLen)
}

exports.write = function (buffer, value, offset, isLE, mLen, nBytes) {
  var e, m, c
  var eLen = nBytes * 8 - mLen - 1
  var eMax = (1 << eLen) - 1
  var eBias = eMax >> 1
  var rt = (mLen === 23 ? Math.pow(2, -24) - Math.pow(2, -77) : 0)
  var i = isLE ? 0 : (nBytes - 1)
  var d = isLE ? 1 : -1
  var s = value < 0 || (value === 0 && 1 / value < 0) ? 1 : 0

  value = Math.abs(value)

  if (isNaN(value) || value === Infinity) {
    m = isNaN(value) ? 1 : 0
    e = eMax
  } else {
    e = Math.floor(Math.log(value) / Math.LN2)
    if (value * (c = Math.pow(2, -e)) < 1) {
      e--
      c *= 2
    }
    if (e + eBias >= 1) {
      value += rt / c
    } else {
      value += rt * Math.pow(2, 1 - eBias)
    }
    if (value * c >= 2) {
      e++
      c /= 2
    }

    if (e + eBias >= eMax) {
      m = 0
      e = eMax
    } else if (e + eBias >= 1) {
      m = (value * c - 1) * Math.pow(2, mLen)
      e = e + eBias
    } else {
      m = value * Math.pow(2, eBias - 1) * Math.pow(2, mLen)
      e = 0
    }
  }

  for (; mLen >= 8; buffer[offset + i] = m & 0xff, i += d, m /= 256, mLen -= 8) {}

  e = (e << mLen) | m
  eLen += mLen
  for (; eLen > 0; buffer[offset + i] = e & 0xff, i += d, e /= 256, eLen -= 8) {}

  buffer[offset + i - d] |= s * 128
}


/***/ }),
/* 6 */
/***/ (function(module, exports) {

var toString = {}.toString;

module.exports = Array.isArray || function (arr) {
  return toString.call(arr) == '[object Array]';
};


/***/ }),
/* 7 */
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
/* 8 */
/***/ (function(module, exports) {

var g;

// This works in non-strict mode
g = (function() {
	return this;
})();

try {
	// This works if eval is allowed (see CSP)
	g = g || Function("return this")() || (1,eval)("this");
} catch(e) {
	// This works if the window reference is available
	if(typeof window === "object")
		g = window;
}

// g can still be undefined, but nothing to do about it...
// We return undefined, instead of nothing here, so it's
// easier to handle this case. if(!global) { ...}

module.exports = g;


/***/ }),
/* 9 */
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
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(13);
if(typeof content === 'string') content = [[module.i, content, '']];
// add the styles to the DOM
var update = __webpack_require__(1)(content, {});
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
/* 11 */
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
             cancel: function() {}
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
             cancel: function() {}
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
         img && img.indexOf('http') === -1 && (img = 'http://' + location.host + img);
         if (/MicroMessenger/i.test(navigator.userAgent)) {
             var script;
             script = document.createElement('script');
             script.src = 'http://res.wx.qq.com/open/js/jweixin-1.0.0.js';
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
                                     setWxShare(title, link, desc, img, _this.options);
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
/* 12 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(10);

__webpack_require__(2);
__webpack_require__(9);

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
/* 13 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, ".modal {\r\n    display: block;\r\n    overflow: hidden;\r\n    position: fixed;\r\n    top: 0;\r\n    right: 0;\r\n    bottom: 0;\r\n    left: 0;\r\n    outline: 0;\r\n    opacity: 1;\r\n    overflow-x: hidden;\r\n    overflow-y: auto;\r\n    opacity: 1;\r\n}\r\n\r\n.modal-backdrop {\r\n    position: fixed;\r\n    top: 0;\r\n    right: 0;\r\n    bottom: 0;\r\n    left: 0;\r\n    background-color: #000;\r\n    opacity: .5;\r\n}\r\n\r\n.modal-dialog {\r\n    position: relative;\r\n    z-index: 1055;\r\n    margin: 0;\r\n    position: relative;\r\n    width: auto;\r\n    margin: 10px;\r\n}\r\n\r\n.modal-content {\r\n    position: relative;\r\n    background-color: #fff;\r\n    -webkit-background-clip: padding-box;\r\n    background-clip: padding-box;\r\n    border: 1px solid #999;\r\n    border: 1px solid rgba(0, 0, 0, .2);\r\n    border-radius: 6px;\r\n    outline: 0;\r\n    -webkit-box-shadow: 0 3px 9px rgba(0, 0, 0, .5);\r\n    box-shadow: 0 3px 9px rgba(0, 0, 0, .5);\r\n}\r\n\r\n.modal-header {\r\n    padding: 15px;\r\n    border-bottom: 1px solid #e5e5e5;\r\n}\r\n\r\n.modal-header .close {\r\n    margin-top: -2px;\r\n}\r\n\r\n.modal-title {\r\n    margin: 0;\r\n    line-height: 1.42857143;\r\n}\r\n\r\n.modal-body {\r\n    position: relative;\r\n    padding: 15px;\r\n}\r\n\r\n.modal-footer {\r\n    padding: 15px;\r\n    text-align: right;\r\n    border-top: 1px solid #e5e5e5;\r\n}\r\n\r\nbutton.close {\r\n    -webkit-appearance: none;\r\n    padding: 0;\r\n    cursor: pointer;\r\n    background: 0 0;\r\n    border: 0;\r\n}\r\n\r\n.close {\r\n    float: right;\r\n    font-size: 21px;\r\n    font-weight: 700;\r\n    line-height: 1;\r\n    color: #000;\r\n    text-shadow: 0 1px 0 #fff;\r\n    filter: alpha(opacity=20);\r\n    opacity: .2;\r\n}\r\n\r\n@media (min-width:768px) {\r\n    .modal-dialog {\r\n        width: 600px;\r\n        margin: 30px auto;\r\n    }\r\n    .modal-content {\r\n        -webkit-box-shadow: 0 5px 15px rgba(0, 0, 0, .5);\r\n        box-shadow: 0 5px 15px rgba(0, 0, 0, .5);\r\n    }\r\n}\r\n", ""]);

// exports


/***/ }),
/* 14 */
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
/* 15 */
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
/* 16 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "/*dialog*/\r\n.dialog.mask{position:fixed;background:rgba(0,0,0,0.3);top:0;left:0;bottom:0;right:0;overflow:auto;z-index:998}\r\n.dialog.dlg{position:absolute;background:#fff;left:0;right:0;bottom:0;margin:15px}\r\n.dialog .dlg-header{padding:15px 15px 0 15px}\r\n.dialog .dlg-body{padding:15px 15px 0 15px}\r\n.dialog .dlg-footer{text-align:right;padding:15px}\r\n.dialog .dlg-footer button{border-radius:0}\r\n\r\n/*filter*/\r\ndiv[wrap=filter] .detail{background:#ccc}\r\ndiv[wrap=filter] .detail .options .label{display:inline-block;margin:.5em;padding-top:.3em;font-size:100%}\r\ndiv[wrap=filter] .detail .actions .btn{border-radius:0}\r\n\r\n/*discuss switch*/\r\n\r\n/*switch*/\r\n.tms-switch{position:fixed;right:15px;width:48px;height:48px;background:rgba(192,192,192,0.5);border-radius:4px;color:#666;font-size:24px;line-height:48px;text-align:center;cursor:pointer;}\r\n.tms-switch:before{font-size:0.7em;}\r\n.tms-switch:nth-of-type(2){bottom:8px;}\r\n.tms-switch:nth-of-type(3){bottom:64px;}\r\n.tms-switch:nth-of-type(4){bottom:120px;}\r\n.tms-switch:nth-of-type(5){bottom:176px;}\r\n.tms-switch:nth-of-type(6){bottom:236px;}\r\n.tms-switch-favor:before{content:'\\6536\\85CF';}\r\n.tms-switch-favor.favored{background:rgba(132,255,192,0.5);}\r\n.tms-switch-coinpay:before{content:'\\6253\\8D4F';}\r\n.tms-switch-siteuser:before{content:'\\6211';}\r\n.tms-discuss-switch{position:fixed;bottom:48px;right:15px;width:48px;height:48px;background:rgba(192,192,192,0.5);border-radius:4px;color:#666;font-size:24px;line-height:48px;text-align:center;}\r\n@media screen and (max-width:719px){\r\n\tbody{margin-bottom:128px;}\r\n\t.tms-switch{bottom:8px;}\r\n\t.tms-switch:nth-of-type(2){right:16px;bottom:8px;}\r\n\t.tms-switch:nth-of-type(3){right:72px;bottom:8px;}\r\n\t.tms-switch:nth-of-type(4){right:128px;bottom:8px;}\r\n\t.tms-switch:nth-of-type(5){right:184px;bottom:8px;}\r\n\t.tms-switch:nth-of-type(6){right:244px;bottom:8px;}\r\n}\r\n#frmPlugin{position:absolute;top:0;bottom:0;left:0;right:0;width:100%;height:100%;border:none;z-index:999;box-sizing:border-box;padding-bottom:48px;background:#fff;}\r\n#frmPlugin iframe{width:100%;height:100%;}\r\n#frmPlugin:after{content:'\\5173\\95ED';position:absolute;width:100px;text-align:center;left:50%;margin-left:-50px;bottom:4px;padding:5px 6px 3px;border:1px solid #ccc;border-radius:4px;}\r\n\r\n/*input list view*/\r\ndiv[wrap]>.description{word-wrap: break-word;}", ""]);

// exports


/***/ }),
/* 17 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(16);
if(typeof content === 'string') content = [[module.i, content, '']];
// add the styles to the DOM
var update = __webpack_require__(1)(content, {});
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
/* 18 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


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


/***/ }),
/* 19 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

var ngMod = angular.module('http.ui.xxt', []);
ngMod.service('http2', ['$rootScope', '$http', '$timeout', '$q', '$sce', '$compile', function($rootScope, $http, $timeout, $q, $sce, $compile) {
    function createAlert(msg, type, keep) {
        var alertDomEl;
        /* backdrop */
        alertDomEl = angular.element('<div></div>');
        alertDomEl.attr({
            'class': 'tms-notice alert alert-' + (type ? type : 'info'),
            'ng-style': '{\'z-index\':1040}'
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
                return;
            }
            if (rsp.err_code != 0) {
                if (options.autoNotice) {
                    createAlert(rsp.err_msg, 'warning');
                }
                if (options.autoBreak) return;
            }
            _defer.resolve(rsp);
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
                return;
            }
            if (rsp.err_code != 0) {
                if (options.autoNotice) {
                    createAlert(rsp.err_msg, 'warning');
                }
                if (options.autoBreak) return;
            }
            _defer.resolve(rsp);
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
/* 20 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(11);
if (/MicroMessenger/i.test(navigator.userAgent) && window.signPackage && window.wx) {
    window.wx.ready(function() {
        window.wx.showOptionMenu();
    });
} else if (/YiXin/i.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        YixinJSBridge.call('showOptionMenu');
    }, false);
}

__webpack_require__(17);

__webpack_require__(19);
__webpack_require__(2);
__webpack_require__(15);
__webpack_require__(12);
__webpack_require__(14);

__webpack_require__(18);

var ngApp = angular.module('app', ['ngSanitize', 'ui.bootstrap', 'http.ui.xxt', 'page.ui.xxt', 'snsshare.ui.xxt', 'directive.enroll', 'siteuser.ui.xxt', 'favor.ui.xxt']);
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
ngApp.config(['$controllerProvider', '$uibTooltipProvider', 'lsProvider', function($cp, $uibTooltipProvider, lsProvider) {
    ngApp.provider = {
        controller: $cp.register
    };
    $uibTooltipProvider.setTriggers({ 'show': 'hide' });
    lsProvider.params(['site', 'app', 'rid', 'page', 'ek', 'preview', 'newRecord', 'ignoretime']);
}]);
ngApp.controller('ctrlAppTip', ['$scope', '$interval', function($scope, $interval) {
    var timer;
    $scope.autoCloseTime = 6;
    $scope.domId = '';
    $scope.closeTip = function() {
        var domTip = document.querySelector($scope.domId);
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent("hide", false, false);
        domTip.dispatchEvent(evt);
    };
    timer = $interval(function() {
        $scope.autoCloseTime--;
        if ($scope.autoCloseTime === 0) {
            $interval.cancel(timer);
            $scope.closeTip();
        }
    }, 1000);
}]);
ngApp.controller('ctrlMain', ['$scope', '$http', '$timeout', 'ls', 'tmsDynaPage', 'tmsSnsShare', 'tmsSiteUser', 'tmsFavor', function($scope, $http, $timeout, LS, tmsDynaPage, tmsSnsShare, tmsSiteUser, tmsFavor) {
    var tasksOfOnReady = [];
    $scope.errmsg = '';
    $scope.closePreviewTip = function() {
        $scope.preview = 'N';
    };
    var openAskFollow = function() {
        $http.get(LS.j('askFollow', 'site')).error(function(content) {
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
    };
    var PG = (function() {
        return {
            exec: function(task) {
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
        };
    })();
    $scope.back = function() {
        history.back();
    };
    $scope.closeWindow = function() {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            window.wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            window.YixinJSBridge.call('closeWebView');
        }
    };
    $scope.addRecord = function(event, page) {
        if (page) {
            $scope.gotoPage(event, page, null, null, false, 'Y');
        } else {
            for (var i in $scope.app.pages) {
                var oPage = $scope.app.pages[i];
                if (oPage.type === 'I') {
                    $scope.gotoPage(event, oPage.name, null, null, false, 'Y');
                    break;
                }
            }
        }
    };
    $scope.gotoPage = function(event, page, ek, rid, fansOnly, newRecord) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
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
        if (/remark|repos/.test(page)) {
            location = url;
        } else {
            location.replace(url);
        }
    };
    $scope.openMatter = function(id, type, replace, newWindow) {
        var url = '/rest/site/fe/matter?site=' + LS.p.site + '&id=' + id + '&type=' + type;
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
            PG.exec(task);
        } else {
            tasksOfOnReady.push(task);
        }
    };
    $http.get(LS.j('get', 'site', 'app', 'rid', 'page', 'ek', 'newRecord')).success(function(rsp) {
        if (rsp.err_code !== 0) {
            $scope.errmsg = rsp.err_msg;
            return;
        }
        var params = rsp.data,
            oSite = params.site,
            oApp = params.app,
            oMission = params.mission,
            oPage = params.page,
            oUser = params.user,
            schemasById = {},
            shareid, sharelink, shareby, summary;

        oApp.dataSchemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });
        oApp._schemasById = schemasById;
        $scope.params = params;
        $scope.site = oSite;
        $scope.mission = oMission;
        $scope.app = oApp;
        $scope.user = oUser;
        if (oApp.multi_rounds === 'Y') {
            $scope.activeRound = params.activeRound;
        }
        /* 设置活动的当前链接 */
        shareid = oUser.uid + '_' + (new Date() * 1);
        sharelink = 'http://' + location.host + LS.j('', 'site', 'app', 'rid', 'newRecord');
        sharelink += "&shareby=" + shareid;
        if (oPage && oPage.share_page && oPage.share_page === 'Y') {
            sharelink += '&page=' + oPage.name;
            params.record && (sharelink += '&ek=' + params.record.enroll_key);
            if (!(/iphone|ipad/i.test(navigator.userAgent))) {
                /*ios下操作无效，且导致微信jssdk失败*/
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, oApp.title, sharelink);
                }
            }
        }
        /* 设置分享 */
        if (/MicroMessenger|Yixin/i.test(navigator.userAgent)) {
            summary = oApp.summary;
            if (oPage && oPage.share_summary && oPage.share_summary.length && params.record) {
                summary = params.record.data[oPage.share_summary];
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
                    $http.get(url);
                    window.shareCounter++;
                    if (oApp.can_autoenroll === 'Y' && oPage.autoenroll_onshare === 'Y') {
                        $http.get(LS.j('emptyGet', 'site', 'app') + '&once=Y');
                    }
                    window.onshare && window.onshare(window.shareCounter);
                },
                jsApiList: ['hideOptionMenu', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'chooseImage', 'uploadImage', 'getLocation']
            });
            tmsSnsShare.set(oApp.title, sharelink, summary, oApp.pic);
        }

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
        tmsFavor.showSwitch($scope.user, oApp);
        if (oApp.can_siteuser === 'Y') {
            tmsSiteUser.showSwitch(oApp.siteid, true);
        }
        $timeout(function() {
            $scope.$broadcast('xxt.app.enroll.ready', params);
        });
        //
        var eleLoading;
        if (eleLoading = document.querySelector('.loading')) {
            eleLoading.parentNode.removeChild(eleLoading);
        }
        //
        $http.post('/rest/site/fe/matter/logAccess?site=' + oApp.siteid + '&id=' + oApp.id + '&type=enroll&title=' + oApp.title + '&shareby=', {
            search: location.search.replace('?', ''),
            referer: document.referrer
        });
    }).error(function(content, httpCode) {
        if (httpCode === 401) {
            var el = document.createElement('iframe');
            el.setAttribute('id', 'frmPopup');
            el.onload = function() {
                this.height = document.querySelector('body').clientHeight;
            };
            document.body.appendChild(el);
            if (content.indexOf('http') === 0) {
                window.onAuthSuccess = function() {
                    el.style.display = 'none';
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
            $scope.errmsg = content;
        }
    });
}]);
module.exports = ngApp;


/***/ }),
/* 21 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

window.xxt === undefined && (window.xxt = {});
window.xxt.geo = {
    options: {},
    getAddress: function($http, deferred, siteId) {
        var promise;
        promise = deferred.promise;
        if (window.wx) {
            window.wx.getLocation({
                success: function(res) {
                    var url = '/rest/site/fe/matter/enroll/locationGet';
                    url += '?site=' + siteId;
                    url += '&lat=' + res.latitude;
                    url += '&lng=' + res.longitude;
                    $http.get(url).success(function(rsp) {
                        if (rsp.err_code === 0) {
                            deferred.resolve({
                                errmsg: 'ok',
                                lat: res.latitude,
                                lng: res.longitude,
                                address: rsp.data.address
                            });
                        } else {
                            deferred.resolve({
                                errmsg: rsp.err_msg
                            });
                        }
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
                            $http.get(url).success(function(rsp) {
                                if (rsp.err_code === 0) {
                                    deferred.resolve({
                                        errmsg: 'ok',
                                        lat: position.coords.latitude,
                                        lng: position.coords.longitude,
                                        address: rsp.data.address
                                    });
                                } else {
                                    deferred.resolve({
                                        errmsg: rsp.err_msg
                                    });
                                }
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
/* 22 */
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
/* 23 */,
/* 24 */,
/* 25 */,
/* 26 */,
/* 27 */,
/* 28 */,
/* 29 */,
/* 30 */,
/* 31 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";

__webpack_require__(55);

__webpack_require__(22);
__webpack_require__(21);

var ngApp = __webpack_require__(20);
ngApp.config(['$compileProvider', function($compileProvider) {
    $compileProvider.aHrefSanitizationWhitelist(/^\s*(https?|ftp|mailto|tel|file|sms|wxLocalResource):/);
}]);
ngApp.factory('Input', ['$http', '$q', '$timeout', 'ls', function($http, $q, $timeout, LS) {
    function isEmpty(schema, value) {
        if (value === undefined) {
            return true;
        }
        switch (schema.type) {
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
    }

    var Input, _ins;
    Input = function() {};
    Input.prototype.check = function(data, app, page) {
        var dataSchemas, item, schema, value;
        if (page.data_schemas && page.data_schemas.length) {
            dataSchemas = JSON.parse(page.data_schemas);
            for (var i = dataSchemas.length - 1; i >= 0; i--) {
                item = dataSchemas[i];
                schema = item.schema;
                if (schema.id.indexOf('member.') === 0) {
                    var memberSchema = schema.id.substr(7);
                    if (memberSchema.indexOf('.') === -1) {
                        value = data.member[memberSchema];
                    } else {
                        memberSchema = memberSchema.split('.');
                        value = data.member.extattr[memberSchema[1]];
                    }
                } else {
                    value = data[schema.id];
                }
                if (item.config.required === 'Y') {
                    if (value === undefined || isEmpty(schema, value)) {
                        return '请填写必填题目［' + schema.title + '］';
                    }
                }
                if (value) {
                    if (schema.type === 'mobile') {
                        if (!/^(\+86|0086)?\s*1[3|4|5|7|8]\d{9}$/.test(value)) {
                            return '题目［' + schema.title + '］只能填写手机号（11位数字）';
                        }
                    }
                    if (schema.type === 'name') {
                        if (value.length < 2) {
                            return '题目［' + schema.title + '］请输入正确的姓名（不少于2个字符）';
                        }
                    }
                    if (schema.type === 'email') {
                        if (!/^\w+@\w+/.test(value)) {
                            return '题目［' + schema.title + '］请输入正确的邮箱';
                        }
                    }
                    //最终删掉 schema.number
                    if (schema.type === 'shorttext' && schema.number && schema.number === 'Y') {
                        value = data[schema.id];
                        if (!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
                            return '题目［' + schema.title + '］请输入数值';
                        }
                    }
                    if (schema.format) {
                        if (schema.format === 'number') {
                            if (!/^-{0,1}[0-9]+(.[0-9]+){0,1}$/.test(value)) {
                                return '题目［' + schema.title + '］请输入数值';
                            }
                        } else if (schema.format === 'name') {
                            if (value.length < 2) {
                                return '题目［' + schema.title + '］请输入正确的姓名（不少于2个字符）';
                            }
                        } else if (schema.format === 'mobile') {
                            if (!/^(\+86|0086)?\s*1[3|4|5|7|8]\d{9}$/.test(value)) {
                                return '题目［' + schema.title + '］请输入正确的手机号（11位数字）';
                            }
                        } else if (schema.format === 'email') {
                            //1. 开头字母数字下划线 至少一个 ^\w+
                            //2. 一个@
                            //3.字母数字下划线 至少一个 \w+
                            //4. 一个'.' 注意. 在增则中有意义需要转译  \.
                            //   /^\w+@\w+/
                            if (!/^\w+@\w+/.test(value)) {
                                return '题目［' + schema.title + '］请输入正确的邮箱';
                            }
                        }
                    }
                }
                if (/image|file/.test(schema.type)) {
                    if (schema.count && schema.count != 0) {
                        if (data[schema.id] && data[schema.id].length > schema.count) {
                            return '题目［' + schema.title + '］超出上传数量（' + schema.count + '）限制';
                        }
                    }
                }
            }
        }
        return true;
    };
    Input.prototype.submit = function(ek, data, oSupplement) {
        var defer, url, d, d2, posted;
        defer = $q.defer();
        posted = angular.copy(data);
        if (Object.keys && Object.keys(posted.member).length === 0) {
            delete posted.member;
        }
        url = LS.j('record/submit', 'site', 'app');
        ek && ek.length && (url += '&ek=' + ek);
        for (var i in posted) {
            d = posted[i];
            if (angular.isArray(d) && d.length && d[0].imgSrc !== undefined && d[0].serverId !== undefined) {
                for (var j in d) {
                    d2 = d[j];
                    delete d2.imgSrc;
                }
            }
        }
        $http.post(url, { data: posted, supplement: oSupplement }).success(function(rsp) {
            if (typeof rsp === 'string' || rsp.err_code != 0) {
                defer.reject(rsp);
            } else {
                defer.resolve(rsp);
            }
        }).error(function(content, httpCode) {
            if (httpCode === 401) {
                var el = document.createElement('iframe');
                el.setAttribute('id', 'frmPopup');
                el.onload = function() {
                    this.height = document.querySelector('body').clientHeight;
                };
                document.body.appendChild(el);
                if (content.indexOf('http') === 0) {
                    window.onAuthSuccess = function() {
                        el.style.display = 'none';
                    };
                    el.setAttribute('src', content);
                    el.style.display = 'block';
                } else {
                    if (el.contentDocument && el.contentDocument.body) {
                        el.contentDocument.body.innerHTML = content;
                        el.style.display = 'block';
                    }
                }
                defer.notify(httpCode);
            } else {
                defer.reject(content);
            }
        });
        return defer.promise;
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
        // if (window.wx !== undefined && modifiedImgFields.length) {
        //     nextWxImage = function() {
        //         var imgField, img;
        //         imgField = data[modifiedImgFields[i]];
        //         img = imgField[j];
        //         window.xxt.image.wxUpload($q.defer(), img).then(function(data) {
        //             if (j < imgField.length - 1) {
        //                 /* next img*/
        //                 j++;
        //                 nextWxImage();
        //             } else if (i < modifiedImgFields.length - 1) {
        //                 /* next field*/
        //                 j = 0;
        //                 i++;
        //                 nextWxImage();
        //             } else {
        //                 defer.resolve('ok');
        //             }
        //         });
        //     };
        //     nextWxImage();
        // } else {
        defer.resolve('ok');
        //}
        return defer.promise;
    };
    return {
        restrict: 'A',
        controller: ['$scope', '$timeout', function($scope, $timeout) {
            // $scope.beforeSubmit(function() {
            //     return onSubmit($scope.data);
            // });
            $scope.chooseImage = function(imgFieldName, count, from) {
                if (imgFieldName !== null) {
                    modifiedImgFields.indexOf(imgFieldName) === -1 && modifiedImgFields.push(imgFieldName);
                    $scope.data[imgFieldName] === undefined && ($scope.data[imgFieldName] = []);
                    if (count !== null && $scope.data[imgFieldName].length === count && count != 0) {
                        $scope.$parent.errmsg = '最多允许上传' + count + '张图片';
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
                        $scope.$broadcast('xxt.enroll.image.choose.done', imgFieldName);
                    });
                });
            };
            $scope.removeImage = function(imgField, index) {
                imgField.splice(index, 1);
            };
        }]
    }
}]);
ngApp.directive('tmsFileInput', ['$q', 'ls', 'tmsDynaPage', function($q, LS, tmsDynaPage) {
    var r, onSubmit;
    tmsDynaPage.loadScript(['/static/js/resumable.js']).then(function() {
        r = new Resumable({
            target: LS.j('record/uploadFile', 'site', 'app'),
            testChunks: false,
            chunkSize: 512 * 1024
        });
    });
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
                        $scope.$apply(function() {
                            $scope.data[fileFieldName] === undefined && ($scope.data[fileFieldName] = []);
                            $scope.data[fileFieldName].push({
                                uniqueIdentifier: r.files[r.files.length - 1].uniqueIdentifier,
                                name: f.name,
                                size: f.size,
                                type: f.type,
                                url: ''
                            });
                            $scope.$broadcast('xxt.enroll.file.choose.done', fileFieldName);
                        });
                    }
                    ele = null;
                }, true);
                ele.click();
            };
        }]
    }
}]);
ngApp.controller('ctrlInput', ['$scope', '$http', '$q', '$uibModal', '$timeout', 'Input', 'ls', 'http2', function($scope, $http, $q, $uibModal, $timeout, Input, LS, http2) {
    function setMember(user, member) {
        var member2, eles;
        if (user && member && member.schema_id && user.members) {
            if (member2 = user.members[member.schema_id]) {
                if (angular.isString(member2.extattr)) {
                    if (member2.extattr.length) {
                        member2.extattr = JSON.parse(member2.extattr);
                    } else {
                        member2.extattr = {};
                    }
                }
                eles = document.querySelectorAll("[ng-model^='data.member']");
                angular.forEach(eles, function(ele) {
                    var attr;
                    attr = ele.getAttribute('ng-model');
                    attr = attr.replace('data.member.', '');
                    attr = attr.split('.');
                    if (attr.length == 2) {
                        !member.extattr && (member.extattr = {});
                        member.extattr[attr[1]] = member2.extattr[attr[1]];
                    } else {
                        member[attr[0]] = member2[attr[0]];
                    }
                });
            }
        }
    }

    function doTask(seq, nextAction) {
        var task = tasksOfBeforeSubmit[seq];
        task().then(function(rsp) {
            seq++;
            seq < tasksOfBeforeSubmit.length ? doTask(seq, nextAction) : doSubmit(nextAction);
        });
    }

    function doSubmit(nextAction) {
        var ek, submitData;
        ek = $scope.record ? $scope.record.enroll_key : undefined;
        facInput.submit(ek, $scope.data, $scope.supplement).then(function(rsp) {
            var url;
            submitState.finish();
            if (nextAction === 'closeWindow') {
                $scope.closeWindow();
            } else if (nextAction === '_autoForward') {
                // 根据指定的进入规则自动跳转到对应页面
                url = LS.j('', 'site', 'app');
                location.replace(url);
            } else if (nextAction && nextAction.length) {
                url = LS.j('', 'site', 'app');
                url += '&page=' + nextAction;
                url += '&ek=' + rsp.data;
                location.replace(url);
            } else {
                if (ek === undefined) {
                    $scope.record = {
                        enroll_key: rsp.data
                    }
                }
                $scope.$broadcast('xxt.app.enroll.submit.done', rsp.data);
            }
        }, function(rsp) {
            if (typeof rsp === 'string') {
                $scope.$parent.errmsg = rsp;
            } else {
                $scope.$parent.errmsg = rsp.err_msg;
                submitState.finish();
            }
        }, function(rsp) {
            if (typeof rsp === 'string') {
                $scope.$parent.errmsg = rsp;
            } else {
                $scope.$parent.errmsg = rsp.err_msg;
                submitState.finish();
            }
        });
    }

    window.onbeforeunload = function() {
        // 保存未提交数据
        submitState.modified && submitState.cache();
    };

    var facInput, tasksOfBeforeSubmit, submitState;
    tasksOfBeforeSubmit = [];
    facInput = Input.ins();
    $scope.data = {
        member: {},
    };
    $scope.supplement = {};
    $scope.submitState = submitState = {
        modified: false,
        state: 'waiting',
        start: function(event) {
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
            this.state = 'running';
        },
        finish: function() {
            var cacheKey;
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
            if (window.localStorage) {
                cacheKey = this._cacheKey();
                window.localStorage.removeItem(cacheKey);
            }
        },
        isRunning: function() {
            return this.state === 'running';
        },
        _cacheKey: function() {
            var app = $scope.app;
            return '/site/' + app.siteid + '/app/' + app.id + '/record/' + ($scope.record ? $scope.record.enroll_key : '') + '/unsubmit';
        },
        cache: function() {
            if (window.localStorage) {
                var key, val;
                key = this._cacheKey();
                val = angular.copy($scope.data);
                val._cacheAt = (new Date() * 1);
                val = JSON.stringify(val);
                window.localStorage.setItem(key, val);
            }
        },
        fromCache: function(keep) {
            if (window.localStorage) {
                var key, val;
                key = this._cacheKey();
                val = window.localStorage.getItem(key);
                if (!keep) window.localStorage.removeItem(key);
                if (val) {
                    val = JSON.parse(val);
                    if (val._cacheAt && (val._cacheAt + 1800000) < (new Date() * 1)) {
                        val = false;
                    }
                    delete val._cacheAt;
                }
            }
            return val;
        }
    };
    $scope.beforeSubmit = function(fn) {
        if (tasksOfBeforeSubmit.indexOf(fn) === -1) {
            tasksOfBeforeSubmit.push(fn);
        }
    };
    var hasSetMember = false;
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        var schemasById,
            dataOfRecord, p, value;

        $scope.schemasById = schemasById = params.app._schemasById;
        /* 用户已经登记过，恢复之前的数据 */
        if (params.record) {
            dataOfRecord = params.record.data;
            for (p in dataOfRecord) {
                if (p === 'member') {
                    if (angular.isString(dataOfRecord.member)) {
                        dataOfRecord.member = JSON.parse(dataOfRecord.member);
                    }
                    $scope.data.member = angular.extend($scope.data.member, dataOfRecord.member);
                } else if (undefined !== schemasById[p]) {
                    var schema = schemasById[p];
                    if (schema.type === 'score') { // is object
                        $scope.data[p] = dataOfRecord[p];
                    } else if (dataOfRecord[p].length) { // is string
                        if (schema.type === 'image') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = [];
                            for (var i in value) {
                                $scope.data[p].push({
                                    imgSrc: value[i]
                                });
                            }
                        } else if (schema.type === 'file') {
                            value = dataOfRecord[p];
                            $scope.data[p] = value;
                        } else if (schema.type === 'multiple') {
                            value = dataOfRecord[p].split(',');
                            $scope.data[p] = {};
                            for (var i in value) $scope.data[p][value[i]] = true;
                        } else {
                            $scope.data[p] = dataOfRecord[p];
                        }
                    }
                }
            }
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
        // 登录提示
        if (!params.user.unionid) {
            var domTip = document.querySelector('#appLoginTip');
            var evt = document.createEvent("HTMLEvents");
            evt.initEvent("show", false, false);
            domTip.dispatchEvent(evt);
        }
    });
    $scope.$watch('data.member.schema_id', function(schemaId) {
        if (false === hasSetMember && schemaId && $scope.user) {
            setMember($scope.user, $scope.data.member);
            hasSetMember = true;
        }
    });
    $scope.submit = function(event, nextAction) {
        var checkResult;
        if (!submitState.isRunning()) {
            submitState.start(event);
            if (true === (checkResult = facInput.check($scope.data, $scope.app, $scope.page))) {
                tasksOfBeforeSubmit.length ? doTask(0, nextAction) : doSubmit(nextAction);
            } else {
                submitState.finish();
                $scope.$parent.errmsg = checkResult;
            }
        }
    };
    $scope.getMyLocation = function(prop) {
        window.xxt.geo.getAddress($http, $q.defer(), LS.p.site).then(function(data) {
            if (data.errmsg === 'ok') {
                $scope.data[prop] = data.address;
            } else {
                $scope.$parent.errmsg = data.errmsg;
            }
        });
    };
    $scope.dataBySchema = function(schemaId) {
        var app = $scope.app;
        $uibModal.open({
            templateUrl: 'dataBySchema.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.data = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close($scope2.data); };
                http2.get('/rest/site/fe/matter/enroll/repos/dataBySchema?site=' + app.siteid + '&app=' + app.id + '&schema=' + schemaId).then(function(result) {
                    $scope2.records = result.data.records;
                });
            }],
            windowClass: 'auto-height',
            backdrop: 'static',
        }).result.then(function(result) {
            $scope.data[schemaId] = result.selected.value;
        });
    };
    $scope.score = function(schemaId, opIndex, number) {
        var schema = $scope.schemasById[schemaId],
            op = schema.ops[opIndex];

        if ($scope.data[schemaId] === undefined) {
            $scope.data[schemaId] = {};
            schema.ops.forEach(function(op) {
                $scope.data[schema.id][op.v] = 0;
            });
        }

        $scope.data[schemaId][op.v] = number;
    };
    $scope.lessScore = function(schemaId, opIndex, number) {
        if (!$scope.schemasById) return false;

        var schema = $scope.schemasById[schemaId],
            op = schema.ops[opIndex];

        if ($scope.data[schemaId] === undefined) {
            return false;
        }

        return $scope.data[schemaId][op.v] >= number;
    };
}]);


/***/ }),
/* 32 */,
/* 33 */,
/* 34 */,
/* 35 */,
/* 36 */,
/* 37 */,
/* 38 */,
/* 39 */,
/* 40 */,
/* 41 */,
/* 42 */,
/* 43 */,
/* 44 */,
/* 45 */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(0)(undefined);
// imports


// module
exports.push([module.i, "html,body{background:#efefef;font-family:Microsoft Yahei,Arial;height:100%;width:100%;}\r\nbody{position:relative;padding:15px;font-size:16px;}\r\nimg{max-width:100%}\r\nheader{margin:-15px -15px 0 -15px;}\r\nfooter{margin:0 -15px -15px -15px;}\r\nul{margin:0;padding:0;list-style:none}\r\nli{margin:0;padding:0}\r\n#errmsg{display:block;opacity:0;height:0;overflow:hidden;width:300px;position:fixed;top:0;left:50%;margin:0 0 0 -150px;text-align:center;transition:opacity 1s;z-index:-1;word-break:break-all}\r\n#errmsg.active{opacity:1;height:auto;z-index:999}\r\n\r\n/* score schema */\r\nli[wrap=score]{padding:4px 4px 4px 0;}\r\nli[wrap=score] label{padding:3px;font-weight:400;}\r\nli[wrap=score]>.number{display:inline-block;margin-top:6px;border:1px solid #CCC;}\r\nli[wrap=score]>.number>div{display:inline-block;width:48px;padding:4px 4px;margin:4px 4px;text-align:center;border-bottom:1px dotted #ddd}\r\nli[wrap=score]>.number>.in{background:#33bb99;}\r\n\r\n/* img tiles */\r\nul.img-tiles li{position:relative;display:inline-block;overflow:hidden;width:80px;height:80px;margin:0px;padding:0px;float:left}\r\nul.img-tiles li.img-thumbnail img{display:inline-block;position:absolute;max-width:none}\r\nul.img-tiles li.img-thumbnail button{position:absolute;top:0;right:0}\r\nul.img-tiles li.img-picker button{position:auto;width:100%;height:100%}\r\nul.img-tiles li.img-picker button span{font-size:36px}\r\n\r\n/* default form style*/\r\ndiv[wrap].wrap-splitline{padding-bottom:0.5em;border-bottom:1px solid #fff;}\r\ndiv[wrap].wrap-inline>*{display:inline-block;vertical-align:middle;margin:0 1em 0 0;}\r\ndiv[wrap].wrap-inline>label{width:6em;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}\r\ndiv[wrap=matter]>span{cursor:pointer;text-decoration:underline;}\r\n\r\n/* auth */\r\n#frmPopup{position:absolute;top:0;left:0;right:0;bottom:0;border:none;width:100%;z-index:999;box-sizing:border-box;}", ""]);

// exports


/***/ }),
/* 46 */,
/* 47 */,
/* 48 */,
/* 49 */,
/* 50 */,
/* 51 */,
/* 52 */,
/* 53 */,
/* 54 */,
/* 55 */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(45);
if(typeof content === 'string') content = [[module.i, content, '']];
// add the styles to the DOM
var update = __webpack_require__(1)(content, {});
if(content.locals) module.exports = content.locals;
// Hot Module Replacement
if(false) {
	// When the styles change, update the <style> tags
	if(!content.locals) {
		module.hot.accept("!!../../../../../../node_modules/css-loader/index.js!./input.css", function() {
			var newContent = require("!!../../../../../../node_modules/css-loader/index.js!./input.css");
			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
			update(newContent);
		});
	}
	// When the module is disposed, remove the <style> tags
	module.hot.dispose(function() { update(); });
}
