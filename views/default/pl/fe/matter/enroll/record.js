define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$uibModal', 'srvApp', 'srvRecord', 'http2', 'noticebox', function($scope, $uibModal, srvApp, srvRecord, http2, noticebox) {
        $scope.notifyMatterTypes = [{
            value: 'tmplmsg',
            title: '模板消息',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'article',
            title: '单图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'news',
            title: '多图文',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'channel',
            title: '频道',
            url: '/rest/pl/fe/matter'
        }, {
            value: 'enroll',
            title: '登记活动',
            url: '/rest/pl/fe/matter'
        }];
        $scope.doSearch = function(pageNumber) {
            $scope.rows = {
                allSelected: 'N',
                selected: {}
            };
            srvRecord.search(pageNumber);
        };
        $scope.$on('search-tag.xxt.combox.done', function(event, aSelected) {
            $scope.criteria.tags = $scope.criteria.tags.concat(aSelected);
            $scope.doSearch();
        });
        $scope.$on('search-tag.xxt.combox.del', function(event, removed) {
            var i = $scope.criteria.tags.indexOf(removed);
            $scope.criteria.tags.splice(i, 1);
            $scope.doSearch();
        });
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordFilter.html?_=3',
                controller: 'ctrlFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    criteria: function() {
                        return angular.copy($scope.criteria);
                    }
                }
            }).result.then(function(criteria) {
                angular.extend($scope.criteria, criteria);
                $scope.doSearch(1);
            });
        };
        $scope.editRecord = function(record) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordEditor.html?_=7',
                controller: 'ctrlEdit',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    record: function() {
                        if (record === undefined) {
                            return {
                                aid: $scope.id,
                                tags: '',
                                data: {}
                            };
                        } else {
                            record.aid = $scope.id;
                            return angular.copy(record);
                        }
                    },
                }
            }).result.then(function(updated) {
                if (record) {
                    srvRecord.update(record, updated[0]);
                } else {
                    srvRecord.add(updated[0]);
                }
            });
        };
        $scope.batchTag = function() {
            srvRecord.batchTag($scope.rows);
        };
        $scope.removeRecord = function(record) {
            srvRecord.remove(record);
        };
        $scope.empty = function() {
            srvRecord.empty();
        };
        $scope.verifyAll = function() {
            srvRecord.verifyAll();
        };
        $scope.batchVerify = function() {
            srvRecord.batchVerify($scope.rows);
        };
        $scope.notify = function(isBatch) {
            srvRecord.notify($scope.notifyMatterTypes, isBatch ? $scope.rows : undefined);
        };
        $scope.export = function() {
            srvRecord.export();
        };
        $scope.exportImage = function() {
            srvRecord.exportImage();
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
        $scope.importByOther = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/importByOther.html?_=1',
                controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
                    var page, data, filter;
                    $scope2.page = page = {
                        at: 1,
                        size: 10,
                        j: function() {
                            return 'page=' + this.at + '&size=' + this.size;
                        }
                    };
                    $scope2.data = data = {};
                    $scope2.filter = filter = {};
                    $scope2.ok = function() {
                        $mi.close(data);
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss('cancel');
                    };
                    $scope2.doFilter = function() {
                        page.at = 1;
                        $scope2.doSearch();
                    };
                    $scope2.doSearch = function() {
                        var url = '/rest/pl/fe/matter/enroll/list?site=' + app.siteid + '&' + page.j();
                        http2.post(url, {
                            byTitle: filter.byTitle
                        }, function(rsp) {
                            $scope2.apps = rsp.data.apps;
                            if ($scope2.apps.length) {
                                data.fromApp = $scope2.apps[0].id;
                            }
                            page.total = rsp.data.total;
                        });
                    };
                    $scope2.doSearch();
                }],
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    }
                }
            }).result.then(function(data) {
                var url = '/rest/pl/fe/matter/enroll/record/importByOther?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
                url += '&fromApp=' + data.fromApp;
                http2.post(url, {}, function(rsp) {
                    noticebox.info('导入（' + rsp.data + '）条数据');
                    $scope.doSearch(1);
                });
            });
        };
        $scope.createAppByRecords = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/createAppByRecords.html?_=4',
                controller: ['$scope', '$uibModalInstance', 'app', function($scope2, $mi, app) {
                    var canUseSchemas = {},
                        config;
                    app.data_schemas.forEach(function(schema) {
                        if (/shorttext|longtext/.test(schema.type)) {
                            canUseSchemas[schema.id] = schema;
                        }
                    });
                    $scope2.schemas = canUseSchemas;
                    $scope2.config = config = { protoSchema: { type: 'score', range: [1, 5] } };
                    $scope2.ok = function() {
                        var schemas = [];
                        for (var id in config.schemas) {
                            if (config.schemas[id]) {
                                schemas.push(canUseSchemas[id]);
                            }
                        }
                        $mi.close({ schemas: schemas, protoSchema: config.protoSchema });
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss('cancel');
                    };
                }],
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app;
                    }
                }
            }).result.then(function(config) {
                var eks = [];
                if (config.schemas.length) {
                    for (var p in $scope.rows.selected) {
                        if ($scope.rows.selected[p] === true) {
                            eks.push($scope.records[p].enroll_key);
                        }
                    }
                    if (eks.length) {
                        var url = '/rest/pl/fe/matter/enroll/createByRecords?site=' + $scope.app.siteid + '&app=' + $scope.app.id;
                        if ($scope.app.mission_id) {
                            url += '&mission=' + $scope.app.mission_id;
                        }
                        http2.post(url, {
                            proto: {
                                scenario: 'voting',
                                schema: {
                                    type: config.protoSchema.type,
                                    range: config.protoSchema.range,
                                    unique: 'N',
                                    _ver: 1
                                }
                            },
                            record: {
                                schemas: config.schemas,
                                eks: eks
                            }
                        }, function(rsp) {
                            location.href = '/rest/pl/fe/matter/enroll?site=' + rsp.data.siteid + '&id=' + rsp.data.id;
                        });
                    }
                }
            });
        };
        // 选中的记录
        $scope.rows = {
            allSelected: 'N',
            selected: {}
        };
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

        $scope.page = {}; // 分页条件
        $scope.criteria = {}; // 过滤条件
        $scope.records = []; // 登记记录
        $scope.tmsTableWrapReady = 'N';
        $scope.$watch('app', function(app) {
            if (!app) return;
            srvRecord.init(app, $scope.page, $scope.criteria, $scope.records);
            // schemas
            srvApp.mapSchemas(app);
            var recordSchemas = [];
            app.data_schemas.forEach(function(schema) {
                if (schema.type !== 'html') {
                    recordSchemas.push(schema);
                }
            });
            $scope.recordSchemas = recordSchemas;
            $scope.enrollDataSchemas = app._schemasFromEnrollApp;
            $scope.groupDataSchemas = app._schemasFromGroupApp;
            $scope.tmsTableWrapReady = 'Y';
            $scope.doSearch();
        });
    }]);
    /**
     * 设置过滤条件
     */
    ngApp.provider.controller('ctrlFilter', ['$scope', '$uibModalInstance', 'app', 'criteria', function($scope, $mi, app, lastCriteria) {
        var canFilteredSchemas = [];
        app.data_schemas.forEach(function(schema) {
            if (false === /image|file/.test(schema.type)) {
                canFilteredSchemas.push(schema);
            }
            if (/multiple/.test(schema.type)) {
                var options = {};
                if (lastCriteria.data[schema.id]) {
                    lastCriteria.data[schema.id].split(',').forEach(function(key) {
                        options[key] = true;
                    })
                }
                lastCriteria.data[schema.id] = options;
            }
        });
        $scope.schemas = canFilteredSchemas;
        $scope.criteria = lastCriteria;
        $scope.ok = function() {
            var criteria = $scope.criteria,
                optionCriteria;
            // 将单选题/多选题的结果拼成字符串
            canFilteredSchemas.forEach(function(schema) {
                var result;
                if (/multiple/.test(schema.type)) {
                    if ((optionCriteria = criteria.data[schema.id])) {
                        result = [];
                        Object.keys(optionCriteria).forEach(function(key) {
                            optionCriteria[key] && result.push(key);
                        });
                        criteria.data[schema.id] = result.join(',');
                    }
                }
            });
            $mi.close(criteria);
        };
        $scope.cancel = function() {
            $mi.dismiss('cancel');
        };
    }]);
    ngApp.provider.controller('ctrlEdit', ['$scope', '$uibModalInstance', '$sce', 'app', 'record', 'srvRecord', function($scope, $uibModalInstance, $sce, app, record, srvRecord) {
        if (record.data) {
            app.data_schemas.forEach(function(col) {
                if (record.data[col.id]) {
                    srvRecord.convertRecord4Edit(col, record.data);
                }
            });
            app._schemasFromEnrollApp.forEach(function(col) {
                if (record.data[col.id]) {
                    srvRecord.convertRecord4Edit(col, record.data);
                }
            });
            app._schemasFromGroupApp.forEach(function(col) {
                if (record.data[col.id]) {
                    srvRecord.convertRecord4Edit(col, record.data);
                }
            });
        }
        $scope.app = app;
        $scope.enrollDataSchemas = app._schemasByEnrollApp;
        $scope.groupDataSchemas = app._schemasByGroupApp;
        $scope.record = record;
        $scope.record.aTags = (!record.tags || record.tags.length === 0) ? [] : record.tags.split(',');
        $scope.aTags = app.tags;
        $scope.ok = function() {
            var record = $scope.record,
                p = {
                    tags: record.aTags.join(','),
                    data: {}
                };

            record.tags = p.tags;
            record.comment && (p.comment = record.comment);
            p.verified = record.verified;
            p.data = $scope.record.data;
            $uibModalInstance.close([p, $scope.aTags]);
        };
        $scope.cancel = function() {
            $uibModalInstance.dismiss('cancel');
        };
        $scope.scoreRangeArray = function(schema) {
            var arr = [];
            if (schema.range && schema.range.length === 2) {
                for (var i = schema.range[0]; i <= schema.range[1]; i++) {
                    arr.push('' + i);
                }
            }
            return arr;
        };
        $scope.chooseImage = function(fieldName) {
            var data = $scope.record.data;
            srvRecord.chooseImage(fieldName).then(function(img) {
                !data[fieldName] && (data[fieldName] = []);
                data[fieldName].push(img);
            });
        };
        $scope.removeImage = function(field, index) {
            field.splice(index, 1);
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
        $scope.syncByEnroll = function() {
            srvRecord.syncByEnroll($scope.record);
        };
        $scope.syncByGroup = function() {
            srvRecord.syncByGroup($scope.record);
        };
    }]);
    ngApp.provider.directive('flexImg', function() {
        return {
            restrict: 'A',
            replace: true,
            template: "<img src='{{img.imgSrc}}'>",
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
});
