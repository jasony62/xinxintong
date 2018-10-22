define([], function() {
    'use strict';
    return {
        bootstrap: function(_oRawPathes) {
            function _fnConfigRequire(oPathAndTimes) {
                var oPaths = {
                    "domReady": '/static/js/domReady'
                };
                for (var p in _oRawPathes.js) {
                    oPaths[p] = _oRawPathes.js[p];
                }
                require.config({
                    waitSeconds: 0,
                    paths: oPaths,
                    urlArgs: function(id, url) {
                        return oPathAndTimes.js[id] ? ('?bust=' + oPathAndTimes.js[id].time) : '';
                    }
                });
                for (var n in _oRawPathes.html) {
                    if (oPathAndTimes.html && oPathAndTimes.html[n]) {
                        oPathAndTimes.html[n].path = _oRawPathes.html[n] + '';
                    }
                }
                if (oPathAndTimes.html) {
                    oPathAndTimes.html.url = function(templateName) {
                        if (this[templateName]) {
                            return this[templateName].path + '.html?_=' + this[templateName].time;
                        }
                        return '';
                    }
                }
                define('frame/templates', oPathAndTimes.html ? oPathAndTimes.html : {});
                define('frame/RouteParam', function() {
                    return function(name) {
                        if (oPathAndTimes && oPathAndTimes.html && oPathAndTimes.html[name]) {
                            this.templateUrl = oPathAndTimes.html[name].path + '.html?_=' + oPathAndTimes.html[name].time;
                        }
                        this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
                        this.reloadOnSearch = false;
                        this.resolve = {
                            load: function($q) {
                                var defer = $q.defer();
                                require([name + 'Ctrl'], function() {
                                    defer.resolve();
                                });
                                return defer.promise;
                            }
                        };
                    }
                });
                require(['frame']);
            }
            /* 获得要加载文件的修改时间 */
            angular.injector(['ng']).invoke(function($http) {
                $http.post('/rest/script/time', _oRawPathes, { 'headers': { 'accept': 'application/json' } }).success(function(rsp) {
                    _fnConfigRequire(rsp.data);
                });
            });
        }
    }
});