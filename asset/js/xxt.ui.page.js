'use strict';
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
        document.documentElement.scrollTop = 0;
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
