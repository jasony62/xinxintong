define(["require", "angular", "signinService"], function(require, angular) {
    'use strict';
    var ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'schema.ui.xxt', 'service.matter', 'service.signin']);
    ngApp.constant('cstApp', {});
    ngApp.config(['$locationProvider', '$routeProvider', 'srvSigninAppProvider', 'srvOpSigninRecordProvider', function($locationProvider, $routeProvider, srvSigninAppProvider, srvOpSigninRecordProvider) {
        var RouteParam = function(name, baseURL) {
            !baseURL && (baseURL = '/views/default/site/op/matter/signin/');
            this.templateUrl = baseURL + name + '.html?_=' + (new Date() * 1);
            this.controller = 'ctrl' + name[0].toUpperCase() + name.substr(1);
        };
        $routeProvider
            .when('/rest/site/op/matter/signin/list', new RouteParam('list'))
            .when('/rest/site/op/matter/signin/record', new RouteParam('record'))
            .otherwise(new RouteParam('summary'));
        //
        $locationProvider.html5Mode(true);
        (function() {
            var ls, siteId, appId, accessId;
            ls = location.search;
            siteId = ls.match(/[\?&]site=([^&]*)/)[1];
            appId = ls.match(/[\?&]app=([^&]*)/)[1];
            accessId = ls.match(/[\?&]accessToken=([^&]*)/)[1];
            //
            srvSigninAppProvider.config(siteId, appId, accessId);
            srvOpSigninRecordProvider.config(siteId, appId, accessId);
        })();
    }]);
    ngApp.controller('ctrlApp', ['$scope', '$location', 'http2', 'srvSigninApp', function($scope, $location, http2, srvSigninApp) {
        $scope.switchParam = {};
        $scope.switchTo = function(view, oRound) {
            var url;
            $scope.switchParam.round = oRound;
            url = '/rest/site/op/matter/signin/' + view;
            $location.path(url);
        };
        srvSigninApp.opGet().then(function(data) {
            var app = data.app,
                recordSchemas = [],
                enrollDataSchemas = [],
                groupDataSchemas = [];
            app.dataSchemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    recordSchemas.push(schema);
                }
            });
            $scope.recordSchemas = recordSchemas;
            app._schemasFromEnrollApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    enrollDataSchemas.push(schema);
                }
            });
            $scope.enrollDataSchemas = enrollDataSchemas;
            app._schemasFromGroupApp.forEach(function(schema) {
                if (schema.type !== 'html') {
                    groupDataSchemas.push(schema);
                }
            });
            $scope.groupDataSchemas = groupDataSchemas;
            $scope.app = app;
            window.loading.finish();
        });
        http2.get('/rest/site/fe/user/get?site=' + $location.search().site, function(rsp) {
            $scope.user = rsp.data;
        });
    }]);
    ngApp.controller('ctrlSummary', ['$scope', '$location', 'http2', function($scope, $location, http2) {
        var url = '/rest/site/op/matter/signin/opData';
        url += '?site=' + $location.search().site;
        url += '&app=' + $location.search().app;
        url += '&accessToken=' + $location.search().accessToken;
        http2.get(url, function(rsp) {
            $scope.summary = rsp.data;
        });
    }]);
    ngApp.controller('ctrlList', ['$scope', '$location', 'http2', 'srvOpSigninRecord', function($scope, $location, http2, srvOpSigninRecord) {
        var execStatus = {};
        $scope.switchToRecord = function(event, oRecord) {
            if ($scope.user.unionid) {
                var oSearch = $location.search();
                oSearch.ek = oRecord.enroll_key;
                $location.path('/rest/site/op/matter/signin/record').search(oSearch);
            } else if (event) {
                var popoverEvt, target, fnClosePopover;
                event.preventDefault();
                event.stopPropagation();
                target = event.target;
                popoverEvt = document.createEvent("HTMLEvents");
                popoverEvt.initEvent('show', true, false);
                target.dispatchEvent(popoverEvt);
                fnClosePopover = function() {
                    popoverEvt = document.createEvent("HTMLEvents");
                    popoverEvt.initEvent('hide', true, false);
                    target.dispatchEvent(popoverEvt);
                    if (execStatus.pendingByLogin && execStatus.pendingByLogin.name === 'switchToRecord') {
                        delete execStatus.pendingByLogin;
                    }
                    document.body.removeEventListener('click', fnClosePopover);
                };
                document.body.addEventListener('click', fnClosePopover);
                execStatus.pendingByLogin = { name: 'switchToRecord', args: [null, oRecord] };
            }
        };
        $scope.switchToLogin = function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (window.sessionStorage && execStatus.pendingByLogin) {
                var method = JSON.stringify(execStatus.pendingByLogin);
                window.sessionStorage.setItem('site.op.matter.signin.pendingByLogin', method);
            }
            location.href = '/rest/site/fe/user/login?site=' + $location.search().site;
        };
        $scope.getRecords = function(pageNumber) {
            $scope.rows.reset();
            srvOpSigninRecord.search(pageNumber);
        };
        $scope.removeRecord = function(record) {
            srvOpSigninRecord.remove(record);
        };
        $scope.batchVerify = function() {
            srvOpSigninRecord.batchVerify($scope.rows);
        };
        $scope.filter = function() {
            srvOpSigninRecord.filter().then(function() {
                $scope.rows.reset();
            });
        };
        $scope.countSelected = function() {
            var count = 0;
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    count++;
                }
            }
            return count;
        };
        $scope.rows = {
            allSelected: 'N',
            selected: {},
            reset: function() {
                this.allSelected = 'N';
                this.selected = {};
            }
        };
        // 选中的记录
        $scope.$watch('rows.allSelected', function(checked) {
            var index = 0;
            if (checked === 'Y') {
                while (index < $scope.records.length) {
                    $scope.rows.selected[index++] = true;
                }
            } else if (checked === 'N') {
                $scope.rows.selected = {};
            }
        });
        $scope.page = { byRound: '' }; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        $scope.$watch('app', function(app) {
            if (!app) return;
            if ($scope.switchParam.round) {
                $scope.page.byRound = $scope.switchParam.round.rid;
                delete $scope.switchParam.round;
            }
            srvOpSigninRecord.init(app, $scope.page, $scope.criteria, $scope.records);
            $scope.tmsTableWrapReady = 'Y';
            $scope.getRecords();
        });
        $scope.$watch('user', function(oUser) {
            if (!oUser) return;
            if (window.sessionStorage) {
                var pendingByLogin;
                if (pendingByLogin = window.sessionStorage.getItem('site.op.matter.signin.pendingByLogin')) {
                    window.sessionStorage.removeItem('site.op.matter.signin.pendingByLogin');
                    if (oUser.loginExpire) {
                        pendingByLogin = JSON.parse(pendingByLogin);
                        $scope[pendingByLogin.name].apply($scope, pendingByLogin.args || []);
                    }
                }
            }
        });
    }]);
    ngApp.controller('ctrlRecord', ['$scope', '$location', 'http2', 'srvSigninRecord', 'tmsSchema', function($scope, $location, http2, srvSigninRecord, tmsSchema) {
        var oApp, oRecord, oBeforeRecord;

        function submit(ek, posted) {
            var url;
            url = '/rest/site/op/matter/signin/record/update';
            url += '?site=' + $location.search().site;
            url += '&app=' + $location.search().app;
            url += '&accessToken=' + $location.search().accessToken;
            url += '&ek=' + ek;
            http2.post(url, posted, function(rsp) {
                //angular.extend($scope.record, rsp.data);
            });
        };
        $scope.chooseImage = function(fieldName) {
            var data = $scope.record.data;
            srvSigninRecord.chooseImage(fieldName).then(function(img) {
                !data[fieldName] && (data[fieldName] = []);
                data[fieldName].push(img);
            });
        };
        $scope.removeImage = function(field, index) {
            field.splice(index, 1);
        };
        $scope.update = function() {
            var record = $scope.record,
                ek = $scope.record.enroll_key,
                p = {
                    //tags: record.aTags.join(','),
                    data: {}
                };

            //record.tags = p.tags;
            record.comment && (p.comment = record.comment);
            p.verified = record.verified;
            p.data = $scope.record.data;
            submit(ek, p);
        };
        $scope.$on('tag.xxt.combox.done', function(event, aSelected) {
            var aNewTags = [];
            for (var i in aSelected) {
                var existing = false;
                for (var j in $scope.record.aTags) {
                    if (aSelected[i] === $scope.record.aTags[j]) {
                        existing = true;
                        break;
                    }
                }!existing && aNewTags.push(aSelected[i]);
            }
            $scope.record.aTags = $scope.record.aTags.concat(aNewTags);
        });
        $scope.$on('tag.xxt.combox.add', function(event, newTag) {
            if (-1 === $scope.record.aTags.indexOf(newTag)) {
                $scope.record.aTags.push(newTag);
                if (-1 === $scope.aTags.indexOf(newTag)) {
                    $scope.aTags.push(newTag);
                }
            }
        });
        $scope.$on('tag.xxt.combox.del', function(event, removed) {
            $scope.record.aTags.splice($scope.record.aTags.indexOf(removed), 1);
        });
        $scope.$watch('app', function(app) {
            if (!app) return;
            srvSigninRecord.get($location.search().ek).then(function(data) {
                oApp = app;
                oBeforeRecord = data;
                if (oBeforeRecord.data) {
                    oApp.dataSchemas.forEach(function(schema) {
                        if (oBeforeRecord.data[schema.id]) {
                            tmsSchema.forEdit(schema, oBeforeRecord.data);
                        }
                    });
                    oApp._schemasFromEnrollApp.forEach(function(schema) {
                        if (oBeforeRecord.data[schema.id]) {
                            tmsSchema.forEdit(schema, oBeforeRecord.data);
                        }
                    });
                    oApp._schemasFromGroupApp.forEach(function(schema) {
                        if (oBeforeRecord.data[schema.id]) {
                            tmsSchema.forEdit(schema, oBeforeRecord.data);
                        }
                    });
                }
                $scope.record = oRecord = angular.copy(oBeforeRecord);
            });
        });
    }]);
    require(['domReady!'], function(document) {
        angular.bootstrap(document, ["app"]);
    });
});