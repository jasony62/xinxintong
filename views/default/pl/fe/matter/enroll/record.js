define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', 'http2', '$uibModal', 'mattersgallery', 'pushnotify', 'noticebox', function($scope, http2, $uibModal, mattersgallery, pushnotify, noticebox) {
        $scope.notifyMatterTypes = [{
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
        $scope.doSearch = function(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/matter/enroll/record/list';
            url += '?site=' + $scope.siteId;
            url += '&app=' + $scope.app.id;
            url += $scope.page.joinParams();
            http2.post(url, $scope.criteria, function(rsp) {
                if (rsp.data) {
                    $scope.records = rsp.data.records ? rsp.data.records : [];
                    $scope.page.total = rsp.data.total;

                    // 计算登记项的分数
                    angular.forEach($scope.records, function(record) {
                        if (record.data) {
                            if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                                angular.forEach($scope.mapOfSchemaByType['image'], function(schema) {
                                    var imgs = record.data[schema.id] ? record.data[schema.id].split(',') : [];
                                    record.data[schema.id] = imgs;
                                });
                            }
                        }
                    });
                } else {
                    $scope.records = [];
                    $scope.page.total = 0;
                }
            });
        };
        // 过滤条件
        $scope.criteria = {
            record: {
                searchBy: '',
                keyword: '',
                verified: ''
            },
            tags: [],
            data: {}
        };
        $scope.page = {
            at: 1,
            size: 30,
            orderBy: 'time',
            byRound: '',
            joinParams: function() {
                var p;
                p = '&page=' + this.at + '&size=' + this.size;
                this.byRound && (p += '&rid=' + this.byRound);
                p += '&orderby=' + this.orderBy;
                return p;
            }
        };
        $scope.orderBys = [{
            n: '登记时间',
            v: 'time'
        }, {
            n: '邀请数',
            v: 'follower'
        }, {
            n: '点赞数',
            v: 'score'
        }, {
            n: '评论数',
            v: 'remark'
        }];
        $scope.$on('search-tag.xxt.combox.done', function(event, aSelected) {
            $scope.criteria.tags = $scope.criteria.tags.concat(aSelected);
            $scope.doSearch();
        });
        $scope.$on('search-tag.xxt.combox.del', function(event, removed) {
            var i = $scope.criteria.tags.indexOf(removed);
            $scope.criteria.tags.splice(i, 1);
            $scope.doSearch();
        });
        $scope.$on('batch-tag.xxt.combox.done', function(event, aSelected) {
            var i, record, records, eks, posted;
            records = [];
            eks = [];
            for (i in $scope.rows.selected) {
                if ($scope.rows.selected) {
                    record = $scope.records[i];
                    eks.push(record.enroll_key);
                    records.push(record);
                }
            }
            if (eks.length) {
                posted = {
                    eks: eks,
                    tags: aSelected
                };
                http2.post('/rest/pl/fe/matter/enroll/record/batchTag?aid=' + $scope.id, posted, function(rsp) {
                    var i, l, m, n, newTag;
                    n = aSelected.length;
                    for (i = 0, l = records.length; i < l; i++) {
                        record = records[i];
                        if (!record.tags || record.length === 0) {
                            record.tags = aSelected.join(',');
                        } else {
                            for (m = 0; m < n; m++) {
                                newTag = aSelected[m];
                                (',' + record.tags + ',').indexOf(newTag) === -1 && (record.tags += ',' + newTag);
                            }
                        }
                    }
                });
            }
        });
        $scope.memberAttr = function(val, key) {
            var keys;
            if (val && val.member) {
                keys = key.split('.');
                if (keys.length === 2) {
                    return val.member[keys[1]];
                } else if (val.member.extattr) {
                    return val.member.extattr[keys[2]];
                } else {
                    return '';
                }
            } else {
                return '';
            }
        };
        $scope.value2Label = function(val, key) {
            var schemas = $scope.app.data_schemas,
                i, j, s, aVal, aLab = [];
            if (val === undefined) return '';
            for (i = 0, j = schemas.length; i < j; i++) {
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
        };
        $scope.json2Obj = function(json) {
            if (json && json.length) {
                obj = JSON.parse(json);
                return obj;
            } else {
                return {};
            }
        };
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
                $scope.criteria = criteria;
                $scope.doSearch(1);
            });
        };
        $scope.editRecord = function(record) {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordEditor.html?_=1',
                controller: 'ctrlEditor',
                backdrop: 'static',
                windowClass: 'auto-height',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    record: function() {
                        record.aid = $scope.id;
                        return angular.copy(record);
                    },
                }
            }).result.then(function(updated) {
                var p, tags;
                p = updated[0];
                http2.post('/rest/pl/fe/matter/enroll/record/update?site=' + $scope.siteId + '&app=' + $scope.id + '&ek=' + record.enroll_key, p, function(rsp) {
                    var data = rsp.data.data;
                    if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                        angular.forEach($scope.mapOfSchemaByType['image'], function(schema) {
                            var imgs = data[schema.id] ? data[schema.id].split(',') : [];
                            data[schema.id] = imgs;
                        });
                    }
                    angular.extend(record, rsp.data);
                });
            });
        };
        $scope.addRecord = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordEditor.html?_=1',
                controller: 'ctrlEditor',
                windowClass: 'auto-height',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    record: function() {
                        return {
                            aid: $scope.id,
                            tags: '',
                            data: {}
                        };
                    }
                }
            }).result.then(function(updated) {
                var p, tags;
                p = updated[0];
                tags = updated[1];
                http2.post('/rest/pl/fe/matter/enroll/record/add?site=' + $scope.siteId + '&app=' + $scope.id, p, function(rsp) {
                    var record = rsp.data;
                    if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                        angular.forEach($scope.mapOfSchemaByType['image'], function(schema) {
                            var imgs = record.data[schema.id] ? record.data[schema.id].split(',') : [];
                            record.data[schema.id] = imgs;
                        });
                    }
                    $scope.records.splice(0, 0, rsp.data);
                });
            });
        };
        $scope.importUser = function() {
            $uibModal.open({
                templateUrl: "userPicker.html",
                backdrop: 'static',
                windowClass: 'auto-height',
                size: 'lg',
                controller: function($scope, $uibModalInstance) {
                    $scope.cancel = function() {
                        $uibModalInstance.dismiss();
                    }
                },
            }).result.then(function(selected) {
                if (selected.members && selected.members.length) {
                    var members = [];
                    for (var i in selected.members)
                        members.push(selected.members[i].data.mid);
                    http2.post('/rest/pl/fe/matter/enroll/record/importUser?aid=' + $scope.id, members, function(rsp) {
                        for (var i in rsp.data)
                            $scope.records.splice(0, 0, rsp.data[i]);
                    });
                }
            });
        };
        $scope.removeRecord = function(record) {
            if (window.confirm('确认删除？')) {
                http2.get('/rest/pl/fe/matter/enroll/record/remove?site=' + $scope.siteId + '&app=' + $scope.id + '&key=' + record.enroll_key, function(rsp) {
                    var i = $scope.records.indexOf(record);
                    $scope.records.splice(i, 1);
                    $scope.page.total = $scope.page.total - 1;
                });
            }
        };
        $scope.empty = function() {
            var vcode;
            vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
            if (vcode === $scope.app.title) {
                http2.get('/rest/pl/fe/matter/enroll/record/empty?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                    $scope.doSearch(1);
                });
            }
        };
        $scope.verifyAll = function() {
            if (window.confirm('确定审核通过所有记录（共' + $scope.page.total + '条）？')) {
                http2.get('/rest/pl/fe/matter/enroll/record/verifyAll?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                    angular.forEach($scope.records, function(record) {
                        record.verified = 'Y';
                    });
                    noticebox.success('完成操作');
                });
            }
        };
        $scope.batchVerify = function() {
            var eks = [];
            for (var p in $scope.rows.selected) {
                if ($scope.rows.selected[p] === true) {
                    eks.push($scope.records[p].enroll_key);
                }
            }
            if (eks.length) {
                http2.post('/rest/pl/fe/matter/enroll/record/batchVerify?site=' + $scope.siteId + '&app=' + $scope.id, {
                    eks: eks
                }, function(rsp) {
                    for (var p in $scope.rows.selected) {
                        if ($scope.rows.selected[p] === true) {
                            $scope.records[p].verified = 'Y';
                        }
                    }
                    noticebox.success('完成操作');
                });
            }
        };
        $scope.notify = function() {
            pushnotify.open($scope.siteId, function(notify) {
                var url;
                if (notify.matters.length) {
                    url = '/rest/pl/fe/matter/enroll/record/notify';
                    url += '?site=' + $scope.siteId;
                    url += '&app=' + $scope.id;
                    url += '&tmplmsg=' + notify.tmplmsg.id;
                    url += $scope.page.joinParams();
                    http2.post(url, {
                        message: notify.message,
                        criteria: $scope.criteria
                    }, function(data) {
                        noticebox.success('发送成功');
                    });
                }
            }, {
                singleMatter: 'Y',
                matterTypes: $scope.notifyMatterTypes
            });
        };
        $scope.export = function() {
            var url, params = {
                criteria: $scope.criteria
            };

            url = '/rest/pl/fe/matter/enroll/record/export';
            url += '?site=' + $scope.siteId + '&app=' + $scope.id;

            http2.post(url, params, function(rsp) {
                var blob;

                blob = new Blob([rsp.data], {
                    type: "text/plain;charset=utf-8"
                });

                saveAs(blob, $scope.app.title + '.csv');
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
        $scope.tmsTableWrapReady = 'N'; // 表格定义是否已经准备完毕
        $scope.$watch('app', function(app) {
            if (!app) return;
            var mapOfSchemaByType = {};
            angular.forEach(app.data_schemas, function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema);
            });
            $scope.mapOfSchemaByType = mapOfSchemaByType;
            $scope.tmsTableWrapReady = 'Y';
            $scope.doSearch();
        });
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
    ngApp.provider.controller('ctrlEditor', ['$scope', '$uibModalInstance', '$sce', 'app', 'record', function($scope, $uibModalInstance, $sce, app, record) {
        var p, col, files;
        if (record.data) {
            for (p in app.data_schemas) {
                col = app.data_schemas[p];
                if (record.data[col.id]) {
                    if (col.type === 'file') {
                        files = JSON.parse(record.data[col.id]);
                        angular.forEach(files, function(file) {
                            file.url = $sce.trustAsResourceUrl(file.url);
                        });
                        record.data[col.id] = files;
                    } else if (col.type === 'multiple') {
                        var value = record.data[col.id].split(','),
                            obj = {};
                        angular.forEach(value, function(p) {
                            obj[p] = true;
                        });
                        record.data[col.id] = obj;
                    } else if (col.type === 'image') {
                        var value = record.data[col.id],
                            obj = [];
                        angular.forEach(value, function(p) {
                            obj.push({
                                imgSrc: p
                            });
                        });
                        record.data[col.id] = obj;
                    }
                }
            }
        }
        $scope.app = app;
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

            angular.forEach($scope.app.data_schemas, function(col) {
                p.data[col.id] = $scope.record.data[col.id];
            });
            $uibModalInstance.close([p, $scope.aTags]);
        };
        $scope.cancel = function() {
            $uibModalInstance.dismiss('cancel');
        };
        $scope.chooseImage = function(imgFieldName, count, from) {
            var data = $scope.record.data;
            if (imgFieldName !== null) {
                data[imgFieldName] === undefined && (data[imgFieldName] = []);
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
                                $scope.$apply(function() {
                                    data[imgFieldName].push(img);
                                });
                            };
                        })(f);
                        reader.readAsDataURL(f);
                    }
                }, false);
                ele.click();
            }
        };
        $scope.removeImage = function(imgField, index) {
            imgField.splice(index, 1);
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
            $scope.record.aTags.push(newTag);
            $scope.aTags.indexOf(newTag) === -1 && $scope.aTags.push(newTag);
        });
        $scope.$on('tag.xxt.combox.del', function(event, removed) {
            $scope.record.aTags.splice($scope.record.aTags.indexOf(removed), 1);
        });
    }]);
});