define(['angular', 'domReady!'], function(angular) {
    'use strict';
    var ngApp = angular.module('app', []),
        injector, codeAssembler;
    angular._lazyLoadModule = function(moduleName) {
        var lazyModule = angular.module(moduleName);
        /* 应用的injector，和config中的injector不是同一个，是instanceInject，返回的是通过provider.$get创建的实例 */
        /* 递归加载依赖的模块 */
        angular.forEach(lazyModule.requires, function(r) {
            angular._lazyLoadModule(r);
        });
        /* 用provider的injector运行模块的controller，directive等等 */
        angular.forEach(lazyModule._invokeQueue, function(invokeArgs) {
            try {
                var provider = ngApp.providers.$injector.get(invokeArgs[0]);
                provider[invokeArgs[1]].apply(provider, invokeArgs[2]);
            } catch (e) {
                console.error('load module invokeQueue failed:' + e.message, invokeArgs);
            }
        });
        /* 用provider的injector运行模块的config */
        angular.forEach(lazyModule._configBlocks, function(invokeArgs) {
            try {
                ngApp.providers.$injector.invoke.apply(ngApp.providers.$injector, invokeArgs[2]);
            } catch (e) {
                console.error('load module configBlocks failed:' + e.message, invokeArgs);
            }
        });
        /* 用应用的injector运行模块的run */
        angular.forEach(lazyModule._runBlocks, function(fn) {
            $injector.invoke(fn);
        });
    };
    ngApp.config(['$injector', '$controllerProvider', function($injector, $controllerProvider) {
        /*＊
         ＊ config中的injector和应用的injector不是同一个，是providerInjector，获得的是provider，而不是通过provider创建的实例
         ＊ 这个injector通过angular无法获得，所以在执行config的时候把它保存下来
        */
        ngApp.providers = {
            $injector: $injector,
            $controllerProvider: $controllerProvider
        };
    }]);
    ngApp.directive('dynamicHtml', function($compile) {
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
    });
    injector = angular.bootstrap(null, ["app"]);

    injector.invoke(['$q', function($q) {
        codeAssembler = {
            loadCss: function(css) {
                var style, head;
                style = document.createElement('style');
                style.innerHTML = css;
                head = document.querySelector('head');
                head.appendChild(style);
            },
            loadExtCss: function(url) {
                var link, head;
                link = document.createElement('link');
                link.href = url;
                link.rel = 'stylesheet';
                head = document.querySelector('head');
                head.appendChild(link);
            },
            loadJs: function(ngApp, js) {
                (function(ngApp) {
                    eval(js);
                })(ngApp);
            },
            loadExtJs: function(ngApp, code) {
                var deferred = $q.defer(),
                    jslength = code.ext_js.length,
                    loadScript;
                loadScript = function(js) {
                    var script;
                    script = document.createElement('script');
                    script.src = js.url;
                    script.onload = function() {
                        jslength--;
                        if (jslength === 0) {
                            if (code.js && code.js.length) {
                                codeAssembler.loadJs(ngApp, code.js);
                            }
                            deferred.resolve();
                        }
                    };
                    document.body.appendChild(script);
                };
                angular.forEach(code.ext_js, loadScript);
                return deferred.promise;
            },
            bootstrap: function(js) {
                require([js], function() {
                    injector.invoke(['$rootScope', '$compile',
                        function bootstrapApply(scope, compile) {
                            scope.$apply(function() {
                                compile(document)(scope);
                            });
                        }
                    ]);
                });
            },
            loadCode: function(ngApp, code) {
                var deferred = $q.defer();
                if (code.ext_css && code.ext_css.length) {
                    angular.forEach(code.ext_css, function(css) {
                        codeAssembler.loadExtCss(css.url);
                    });
                }
                if (code.css && code.css.length) {
                    codeAssembler.loadCss(code.css);
                }
                if (code.ext_js && code.ext_js.length) {
                    codeAssembler.loadExtJs(ngApp, code).then(function() {
                        deferred.resolve();
                    });
                } else {
                    if (code.js && code.js.length) {
                        codeAssembler.loadJs(ngApp, code.js);
                    }
                    deferred.resolve();
                }
                return deferred.promise;
            },
            openPlugin: function(content) {
                var frag, wrap, frm, deferred = $q.defer();
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
                        deferred.resolve();
                    };
                    frm.setAttribute('src', content);
                } else {
                    if (frm.contentDocument && frm.contentDocument.body) {
                        frm.contentDocument.body.innerHTML = content;
                    }
                }
                return deferred.promise;
            },
            cookieLogin: function(siteId) {
                var ck, cn, cs, ce, login;
                ck = document.cookie;
                cn = '_site_' + siteId + '_fe_login';
                if (ck.length > 0) {
                    cs = ck.indexOf(cn + "=");
                    if (cs !== -1) {
                        cs = cs + cn.length + 1;
                        ce = ck.indexOf(";", cs);
                        if (ce === -1) ce = ck.length;
                        login = ck.substring(cs, ce);
                        return JSON.parse(decodeURIComponent(login));
                    }
                }
                return false;
            }
        };
    }]);

    return codeAssembler;
});