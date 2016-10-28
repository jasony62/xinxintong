ngApp = angular.module('app', ['ngSanitize']);
ngApp.config(['$controllerProvider', function($cp) {
    ngApp.register = {
        controller: $cp.register
    };
}]);
LS = (function() {
    function locationSearch() {
        var ls, search;
        ls = location.search;
        search = {};
        angular.forEach(['page'], function(q) {
            var match, pattern;
            pattern = new RegExp(q + '=([^&]*)');
            match = ls.match(pattern);
            search[q] = match ? match[1] : '';
        });
        return search;
    };
    /*join search*/
    function j(method) {
        var j, l, url = '/rest/site/fe/matter/signin/template',
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
        url = '/rest/site/fe/matter/signin/template/record/get';
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
        url = '/rest/site/fe/matter/signin/template/record/list';
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
    var facRecord, schemas;
    facRecord = Record.ins(LS.p.scenario, LS.p.template);
    facRecord.get($scope.CustomConfig);
    $scope.Record = facRecord;
    schemas = $scope.page.data_schemas;
    schemas = schemas.record.schemas;
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
ngApp.controller('ctrl', ['$scope', '$http', '$timeout', '$q', function($scope, $http, $timeout, $q) {
    window.renew = function(page, config) {
        $scope.$apply(function() {
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
        });
    };
    $scope.errmsg = '';
    $scope.data = {
        member: {}
    };
    $scope.CustomConfig = {};
    $scope.gotoPage = function(event, page, ek, rid, fansOnly, newRecord) {};
    $scope.addRecord = function(event) {};
    $scope.editRecord = function(event, page) {};
    $scope.openMatter = function(id, type) {};
}]);