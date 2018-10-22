define(["angular", "xxt-page", "enroll-directive", "angular-sanitize"], function(angular, codeAssembler) {
    'use strict';

    var ngApp = angular.module('enroll', ['ngSanitize', 'directive.enroll']);
    ngApp.provider('ls', function() {
        var _baseUrl = '/rest/site/fe/matter/template/enroll/preview',
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
        lsProvider.params(['start', 'site', 'tid', 'vid', 'page', 'ek', 'newRecord', 'openAt']);
    }]);
    ngApp.controller('ctrl', ['$scope', '$http', '$timeout', 'ls', function($scope, $http, $timeout, LS) {
        function gotoPage() {
            var url = LS.j('get', 'site', 'tid', 'vid', 'page');
            $http.get(url).success(function(rsp) {
                if (rsp.err_code !== 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                var params = rsp.data,
                    site = params.site,
                    app = params.app,
                    mission = params.mission;
                app.data_schemas = JSON.parse(app.data_schemas);
                $scope.schemasById = {};
                app.data_schemas && app.data_schemas.forEach(function(schema) {
                    $scope.schemasById[schema.id] = schema;
                });
                $scope.params = params;
                $scope.site = site;
                $scope.mission = mission;
                $scope.app = app;
                $scope.user = params.user;
                $scope.activeRound = params.activeRound;
                if (app.use_site_header === 'Y' && site && site.header_page) {
                    codeAssembler.loadCode(ngApp, site.header_page);
                }
                if (app.use_mission_header === 'Y' && mission && mission.header_page) {
                    codeAssembler.loadCode(ngApp, mission.header_page);
                }
                if (app.use_mission_footer === 'Y' && mission && mission.footer_page) {
                    codeAssembler.loadCode(ngApp, mission.footer_page);
                }
                if (app.use_site_footer === 'Y' && site && site.footer_page) {
                    codeAssembler.loadCode(ngApp, site.footer_page);
                }
                codeAssembler.loadCode(ngApp, params.page).then(function() {
                    $scope.page = params.page;
                });
                $timeout(function() {
                    $scope.$broadcast('xxt.app.enroll.ready', params);
                });
                window.loading.finish();
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
        };
        window.gotoPage = function(page) {
            var phase;
            phase = $scope.$root.$$phase;
            if (phase === '$digest' || phase === '$apply') {
                gotoPage(page);
            } else {
                $scope.$apply(function() {
                    gotoPage(page);
                });
            }
        };
        $scope.errmsg = '';
        $scope.closePreviewTip = function() {
            $scope.preview = 'N';
        };
        var openAskFollow = function() {};
        $scope.closeWindow = function() {};
        $scope.addRecord = function(event, page) {
            page ? $scope.gotoPage(event, page, null, null, false, 'Y') : alert('没有指定登记编辑页');
        };
        $scope.gotoPage = function(event, page, ek, rid, fansOnly, newRecord) {
            event.preventDefault();
            event.stopPropagation();
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
            location.replace(url);
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
        gotoPage();
    }]);
    ngApp.service('srvStorage', ['ls', function(LS) {
        var cache;

        cache = window.localStorage.getItem('enroll-preview');
        if (cache) {
            cache = JSON.parse(cache);
        } else {
            cache = {
                'records': {}
            };
            window.localStorage.setItem('enroll-preview', JSON.stringify(cache));
        }

        this.getRecord = function(ek) {
            return cache.records[ek];
        };
        this.addRecord = function(data) {
            var ek = 'ek' + (new Date() * 1);

            cache.records[ek] = {
                enroll_key: ek,
                enroll_at: Math.round((new Date() * 1) / 1000),
                data: data
            };

            window.localStorage.setItem('enroll-preview', JSON.stringify(cache));

            return ek;
        };
        this.clean = function() {
            window.localStorage.removeItem('enroll-preview');
        };
    }]);
    ngApp.service('Record', ['srvStorage', function(srvStorage) {
        this.current = {};
        this.get = function(ek) {
            this.current = srvStorage.getRecord(ek) || {
                enroll_at: Math.floor(new Date() / 1000)
            };
        };
    }]);
    ngApp.controller('ctrlRecord', ['$scope', 'Record', 'ls', '$sce', function($scope, Record, LS, $sce) {
        var schemas = $scope.app.data_schemas,
            schemasById = {};

        schemas.forEach(function(schema) {
            schemasById[schema.id] = schema;
        });

        Record.get(LS.p['ek']);
        $scope.Record = Record;

        $scope.value2Label = function(schemaId) {
            var val = '',
                s, aVal, aLab = [];

            if (schemas && Record.current.data) {
                if (val = Record.current.data[schemaId]) {
                    s = schemasById[schemaId];
                    if (s && s.ops && s.ops.length) {
                        aVal = val.split(',');
                        s.ops.forEach(function(op, i) {
                            aVal.indexOf(s.ops[i].v) !== -1 && aLab.push(s.ops[i].l);
                        });
                        if (aLab.length) val = aLab.join(',');
                    }
                }
            }
            return val;
        };
        $scope.score2Html = function(schemaId) {
            var label = '',
                schema = schemasById[schemaId],
                val;

            if (schema && Record.current.data) {
                if (val = Record.current.data[schemaId]) {
                    if (schema.ops && schema.ops.length) {
                        schema.ops.forEach(function(op, index) {
                            label += '<div>' + op.l + ': ' + val[op.v] + '</div>';
                        });
                    }
                }
            }

            return $sce.trustAsHtml(label);
        };
    }]);
    ngApp.controller('ctrlRecords', ['$scope', '$http', 'ls', function($scope, $http, LS) {}]);
    ngApp.controller('ctrlRounds', ['$scope', '$http', 'ls', function($scope, $http, LS) {}]);
    ngApp.controller('ctrlOwnerOptions', ['$scope', '$http', 'ls', function($scope, $http, LS) {}]);
    ngApp.controller('ctrlStatistic', ['$scope', '$http', 'ls', function($scope, $http, LS) {}]);
    ngApp.controller('ctrlPreview', ['$scope', 'ls', 'srvStorage', function($scope, LS, srvStorage) {
        $scope.data = {};
        if (LS.p['start'] === 'Y') {
            srvStorage.clean();
        }
        if (LS.p['ek']) {
            (function() {
                var ek = LS.p['ek'],
                    record = srvStorage.getRecord(ek);
                angular.extend($scope.data, record.data);
            })();
        }
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
        $scope.submit = function(event, nextAction) {
            var url, ek, data, i;

            // 处理数据
            data = angular.copy($scope.data);
            angular.forEach($scope.app.data_schemas, function(schema) {
                switch (schema.type) {
                    case 'multiple':
                        var val = data[schema.id],
                            p, val2 = [];
                        if (angular.isObject(val)) {
                            for (var p in val) {
                                val2.push(p);
                            }
                            data[schema.id] = val2.join(',');
                        }
                        break;
                }
            });

            ek = srvStorage.addRecord($scope.data);

            if (nextAction !== undefined && nextAction.length) {
                url = LS.j('', 'site', 'tid', 'vid');
                url += '&page=' + nextAction;
                url += '&ek=' + ek;
                location.replace(url);
            }
        };
        $scope.editRecord = function(event, nextAction) {
            var url;

            if (nextAction !== undefined && nextAction.length) {
                url = LS.j('', 'site', 'tid', 'vid');
                url += '&page=' + nextAction;
                url += '&ek=' + LS.p['ek'];
                location.replace(url);
            }
        };
    }]);

    angular._lazyLoadModule('enroll');

    return ngApp;
});