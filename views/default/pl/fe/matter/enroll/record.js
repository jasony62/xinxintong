define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', '$uibModal', 'srvApp', 'srvRecord', function($scope, $uibModal, srvApp, srvRecord) {
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
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordEditor.html?_=5',
                controller: 'ctrlEdit',
                backdrop: 'static',
                windowClass: 'auto-height',
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
            srvRecord.empty(record);
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
        angular.forEach(app.data_schemas, function(schema) {
            if (false === /image|file/.test(schema.type)) {
                canFilteredSchemas.push(schema);
            }
        });
        $scope.schemas = canFilteredSchemas;
        $scope.criteria = lastCriteria;
        $scope.ok = function() {
            var criteria = $scope.criteria,
                optionCriteria;
            // 将单选题/多选题的结果拼成字符串
            angular.forEach(app.data_schemas, function(schema) {
                if (/multiple/.test(schema.type)) {
                    if ((optionCriteria = criteria.data[schema.id])) {
                        criteria.data[schema.id] = Object.keys(optionCriteria).join(',');
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
                    data[fieldName] === undefined && (data[fieldName] = []);
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