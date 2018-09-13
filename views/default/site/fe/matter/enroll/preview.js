'use strict';
require('./directive.css');
require('./preview.css');

require('../../../../../../asset/js/xxt.ui.http.js');
require('../../../../../../asset/js/xxt.ui.page.js');
require('./directive.js');

var ngApp = angular.module('app', ['ngSanitize', 'http.ui.xxt', 'page.ui.xxt', 'directive.enroll']);
ngApp.config(['$locationProvider', function($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlMain', ['$scope', 'http2', '$timeout', 'tmsLocation', 'tmsDynaPage', function($scope, http2, $timeout, LS, tmsDynaPage) {
    function gotoPage() {
        var url = LS.j('get', 'site', 'app', 'ek', 'openAt', 'page');
        http2.get(url, { autoBreak: false }).then(function(rsp) {
            var params = rsp.data,
                site = params.site,
                app = params.app,
                mission = params.mission;
            $scope.schemasById = {};
            app.dataSchemass && app.dataSchemas.forEach(function(schema) {
                $scope.schemasById[schema.id] = schema;
            });
            $scope.params = params;
            $scope.site = site;
            $scope.mission = mission;
            $scope.app = app;
            $scope.user = params.user;
            $scope.activeRound = app.appRound;
            if (app.use_site_header === 'Y' && site && site.header_page) {
                tmsDynaPage.loadCode(ngApp, site.header_page);
            }
            if (app.use_mission_header === 'Y' && mission && mission.header_page) {
                tmsDynaPage.loadCode(ngApp, mission.header_page);
            }
            if (app.use_mission_footer === 'Y' && mission && mission.footer_page) {
                tmsDynaPage.loadCode(ngApp, mission.footer_page);
            }
            if (app.use_site_footer === 'Y' && site && site.footer_page) {
                tmsDynaPage.loadCode(ngApp, site.footer_page);
            }
            tmsDynaPage.loadCode(ngApp, params.page).then(function() {
                $scope.page = params.page;
            });
            $timeout(function() {
                $scope.$broadcast('xxt.app.enroll.ready', params);
            });
            //
            var eleLoading;
            if (eleLoading = document.querySelector('.loading')) {
                eleLoading.parentNode.removeChild(eleLoading);
            }
        }, function() {
            var eleLoading;
            if (eleLoading = document.querySelector('.loading')) {
                eleLoading.parentNode.removeChild(eleLoading);
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
    $scope.closeWindow = function() {};
    $scope.addRecord = function(event, page) {
        page ? $scope.gotoPage(event, page, null, null, 'Y') : alert('没有指定登记编辑页');
    };
    $scope.gotoPage = function(event, page, ek, rid, newRecord) {
        event.preventDefault();
        event.stopPropagation();
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
    gotoPage();
}]);
ngApp.service('srvStorage', ['tmsLocation', function(LS) {
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
ngApp.controller('ctrlRecord', ['$scope', 'Record', 'tmsLocation', '$sce', function($scope, Record, LS, $sce) {
    var schemasById = $scope.app._schemasById;

    Record.get(LS.s()['ek']);
    $scope.Record = Record;

    $scope.value2Label = function(schemaId) {
        var val = '',
            s, aVal, aLab = [];

        if (schemasById && Record.current.data) {
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
ngApp.controller('ctrlRecords', [function() {}]);
ngApp.controller('ctrlRounds', [function() {}]);
ngApp.controller('ctrlOwnerOptions', [function() {}]);
ngApp.controller('ctrlStatistic', [function() {}]);
ngApp.controller('ctrlPreview', ['$scope', 'tmsLocation', 'srvStorage', function($scope, LS, srvStorage) {
    $scope.data = {};
    if (LS.s().start === 'Y') {
        srvStorage.clean();
    }
    if (LS.s().ek) {
        (function() {
            var ek = LS.s().ek,
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
        var schema, op;
        if (!$scope.schemasById) return false;

        if (!(schema = $scope.schemasById[schemaId])) {
            return false;
        }
        if ($scope.data[schemaId] === undefined) {
            return false;
        }
        op = schema.ops[opIndex];

        return $scope.data[schemaId][op.v] >= number;
    };
    $scope.submit = function(event, nextAction) {
        var url, ek, data, i;

        // 处理数据
        data = angular.copy($scope.data);
        $scope.app.dataSchemas(function(schema) {
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
            url = LS.j('', 'site', 'app');
            url += '&page=' + nextAction;
            url += '&ek=' + ek;
            location.replace(url);
        }
    };
    $scope.editRecord = function(event, nextAction) {
        var url;

        if (nextAction !== undefined && nextAction.length) {
            url = LS.j('', 'site', 'app');
            url += '&page=' + nextAction;
            url += '&ek=' + LS.s()['ek'];
            location.replace(url);
        }
    };
}]);