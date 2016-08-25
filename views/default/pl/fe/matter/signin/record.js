define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlRecord', ['$scope', function($scope) {
        var mapOfRounds = {}; // 轮次id对应轮次对象
        // 当前处理的数据集
        $scope.recordSet = 'signin';
        $scope.chooseRecordSet = function(name) {
            $scope.recordSet = name;
        };
        $scope.json2Obj = function(json) {
            if (json && json.length) {
                obj = JSON.parse(json);
                return obj;
            } else {
                return {};
            }
        };
        // 当前签到记录是否迟到？
        $scope.isSigninLate = function(record, roundId) {
            var round = mapOfRounds[roundId],
                signinAt;

            if (record && record.signin_log && round && round.late_at > 0) {
                signinAt = parseInt(record.signin_log[roundId]);
                if (signinAt) {
                    // 忽略秒的影响
                    return signinAt > parseInt(round.late_at) + 59;
                }
            }
            return false;
        };
        $scope.$watch('app.rounds', function(rounds) {
            if (rounds && rounds.length) {
                angular.forEach(rounds, function(round) {
                    mapOfRounds[round.rid] = round;
                });
            }
        });
    }]);
    ngApp.provider.controller('ctrlSigninRecords', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
        function searchSigninRecords(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/matter/signin/record/list';
            url += '?site=' + $scope.siteId; // todo
            url += '&app=' + $scope.app.id;
            url += $scope.page.joinParams();
            http2.post(url, $scope.criteria, function(rsp) {
                if (rsp.data) {
                    $scope.records = rsp.data.records ? rsp.data.records : [];
                    rsp.data.total && ($scope.page.total = rsp.data.total);
                } else {
                    $scope.records = [];
                }
                angular.forEach($scope.records, function(record) {
                    record.data.member && (record.data.member = JSON.parse(record.data.member));
                    if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                        angular.forEach($scope.mapOfSchemaByType['image'], function(schemaId) {
                            var imgs = record.data[schemaId] ? record.data[schemaId].split(',') : [];
                            record.data[schemaId] = imgs;
                        });
                    }
                });
            });
        };

        $scope.notifyMatterTypes = [{
            value: 'text',
            title: '文本',
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
        $scope.doSearch = function(page) {
            searchSigninRecords(page);
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
            searchBy: 'nickname',
            orderBy: 'time',
            byRound: '',
            joinParams: function() {
                var p;
                p = '&page=' + this.at + '&size=' + this.size;
                p += '&orderby=' + this.orderBy;
                p += '&rid=' + (this.byRound ? this.byRound : 'ALL');
                return p;
            }
        };
        $scope.orderBys = [{
            n: '签到时间',
            v: 'time'
        }];
        var current, startAt, endAt;
        current = new Date();
        startAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 0, 0, 0, 0);
                return d.getTime();
            }
        };
        endAt = {
            year: current.getFullYear(),
            month: current.getMonth() + 1,
            mday: current.getDate(),
            getTime: function() {
                var d = new Date(this.year, this.month - 1, this.mday, 23, 59, 59, 0);
                return d.getTime();
            }
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
        $scope.memberAttr = function(val, key) {
            var keys;
            if (val.member) {
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
        $scope.value2Label2 = function(val, key) {
            var schemas = $scope.app.enrollApp.data_schemas,
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
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/signin/component/recordFilter.html?_=1',
                controller: 'ctrlSigninFilter',
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
                templateUrl: '/views/default/pl/fe/matter/signin/component/recordEditor.html?_=3',
                controller: 'ctrlEditor',
                backdrop: 'static',
                windowClass: 'auto-height middle-width',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    enrollDataSchemas: function() {
                        return $scope.enrollDataSchemas;
                    },
                    record: function() {
                        record.aid = $scope.id;
                        return angular.copy(record);
                    },
                }
            }).result.then(function(updated) {
                var p, tags;
                p = updated[0];
                http2.post('/rest/pl/fe/matter/signin/record/update?site=' + $scope.siteId + '&app=' + $scope.id + '&ek=' + record.enroll_key, p, function(rsp) {
                    var data = rsp.data.data;
                    if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                        angular.forEach($scope.mapOfSchemaByType['image'], function(schemaId) {
                            var imgs = data[schemaId] ? data[schemaId].split(',') : [];
                            data[schemaId] = imgs;
                        });
                    }
                    angular.extend(record, rsp.data);
                });
            });
        };
        $scope.addRecord = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/signin/component/recordEditor.html?_=3',
                controller: 'ctrlEditor',
                windowClass: 'auto-height middle-width',
                resolve: {
                    app: function() {
                        return $scope.app;
                    },
                    enrollDataSchemas: function() {
                        return $scope.enrollDataSchemas;
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
                http2.post('/rest/pl/fe/matter/signin/record/add?site=' + $scope.siteId + '&app=' + $scope.id, p, function(rsp) {
                    var record = rsp.data;
                    if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                        angular.forEach($scope.mapOfSchemaByType['image'], function(schemaId) {
                            var imgs = record.data[schemaId] ? record.data[schemaId].split(',') : [];
                            record.data[schemaId] = imgs;
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
                controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                    $scope.cancel = function() {
                        $mi.dismiss();
                    }
                }],
            }).result.then(function(selected) {
                if (selected.members && selected.members.length) {
                    var members = [];
                    for (var i in selected.members)
                        members.push(selected.members[i].data.mid);
                    http2.post('/rest/pl/fe/matter/signin/record/importUser?aid=' + $scope.id, members, function(rsp) {
                        for (var i in rsp.data)
                            $scope.records.splice(0, 0, rsp.data[i]);
                    });
                }
            });
        };
        $scope.removeRecord = function(record) {
            if (window.confirm('确认删除？')) {
                http2.get('/rest/pl/fe/matter/signin/record/remove?site=' + $scope.siteId + '&app=' + $scope.id + '&key=' + record.enroll_key, function(rsp) {
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
                http2.get('/rest/pl/fe/matter/signin/record/empty?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                    $scope.doSearch(1);
                });
            }
        };
        $scope.export = function() {
            var url, params = {
                criteria: $scope.criteria
            };

            url = '/rest/pl/fe/matter/signin/record/export';
            url += '?site=' + $scope.siteId + '&app=' + $scope.id;
            $scope.page.byRound && (url += '&round=' + $scope.page.byRound);

            http2.post(url, params, function(rsp) {
                var blob;

                blob = new Blob([rsp.data], {
                    type: "text/plain;charset=utf-8"
                });

                saveAs(blob, $scope.app.title + '.csv');
            });
        };
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
        $scope.tmsTableWrapReady = 'N';
        $scope.enrollDataSchemas = [];
        $scope.$watch('app', function(app) {
            if (!app) return;
            //
            var mapOfSchemaByType = {},
                mapOfSchemaById = {};

            angular.forEach(app.data_schemas, function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema.id);
                mapOfSchemaById[schema.id] = schema;
            });
            //
            $scope.mapOfSchemaByType = mapOfSchemaByType;
            // 关联的报名登记项
            if (app.enrollApp && app.enrollApp.data_schemas) {
                $scope.enrollDataSchemas = [];
                angular.forEach(app.enrollApp.data_schemas, function(item) {
                    if (mapOfSchemaById[item.id] === undefined) {
                        $scope.enrollDataSchemas.push(item);
                    }
                });
            }
            $scope.tmsTableWrapReady = 'Y';
            $scope.doSearch();
        });
    }]);
    ngApp.provider.controller('ctrlEnrollRecords', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
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
        }];
        $scope.doSearch = function(page) {
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/matter/signin/record/listByEnroll';
            url += '?site=' + $scope.siteId; // todo
            url += '&app=' + $scope.id;
            url += $scope.page.joinParams();
            http2.post(url, $scope.criteria, function(rsp) {
                if (rsp.data) {
                    $scope.records = rsp.data.records ? rsp.data.records : [];
                    rsp.data.total && ($scope.page.total = rsp.data.total);
                } else {
                    $scope.records = [];
                }
                angular.forEach($scope.records, function(record) {
                    if (record.data) {
                        if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                            angular.forEach($scope.mapOfSchemaByType['image'], function(schemaId) {
                                var imgs = record.data[schemaId] ? record.data[schemaId].split(',') : [];
                                record.data[schemaId] = imgs;
                            });
                        }
                    }
                });
            });
        };
        // 选中的记录
        $scope.$on('search-tag.xxt.combox.done', function(event, aSelected) {
            $scope.criteria.tags = $scope.criteria.tags.concat(aSelected);
            $scope.doSearch();
        });
        $scope.$on('search-tag.xxt.combox.del', function(event, removed) {
            var i = $scope.criteria.tags.indexOf(removed);
            $scope.criteria.tags.splice(i, 1);
            $scope.doSearch();
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
            var schemas = $scope.app.enrollApp.data_schemas,
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
        $scope.filter = function() {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/enroll/component/recordFilter.html?_=3',
                controller: 'ctrlFilter',
                windowClass: 'auto-height',
                backdrop: 'static',
                resolve: {
                    app: function() {
                        return $scope.app.enrollApp;
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
        $scope.export = function() {
            var url, params = {
                criteria: $scope.criteria
            };

            url = '/rest/pl/fe/matter/signin/record/exportByEnroll';
            url += '?site=' + $scope.siteId; // todo
            url += '&app=' + $scope.id;
            $scope.page.byRound && (url += '&round=' + $scope.page.byRound);

            http2.post(url, params, function(rsp) {
                var blob;

                blob = new Blob([rsp.data], {
                    type: "text/plain;charset=utf-8"
                });

                saveAs(blob, $scope.app.title + '.csv');
            });
        };
        $scope.tmsTableWrapReady = 'N';
        $scope.$watch('app', function(app) {
            if (!app) return;
            var mapOfSchemaByType = {};

            angular.forEach(app.enrollApp.data_schemas, function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema.id);
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
    ngApp.provider.controller('ctrlSigninFilter', ['$scope', '$uibModalInstance', 'app', 'criteria', function($scope, $mi, app, lastCriteria) {
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
    ngApp.provider.controller('ctrlEditor', ['$scope', 'http2', '$uibModalInstance', '$sce', 'app', 'enrollDataSchemas', 'record', function($scope, http2, $mi, $sce, app, enrollDataSchemas, record) {
        function _convertRecord(col, data) {
            var files;
            if (col.type === 'file') {
                files = JSON.parse(data[col.id]);
                angular.forEach(files, function(file) {
                    file.url = $sce.trustAsResourceUrl(file.url);
                });
                data[col.id] = files;
            } else if (col.type === 'multiple') {
                var value = data[col.id].split(','),
                    obj = {};
                angular.forEach(value, function(p) {
                    obj[p] = true;
                });
                data[col.id] = obj;
            } else if (col.type === 'image') {
                var value = data[col.id],
                    obj = [];
                angular.forEach(value, function(p) {
                    obj.push({
                        imgSrc: p
                    });
                });
                data[col.id] = obj;
            }
            return data;
        };
        if (record.data) {
            angular.forEach(app.data_schemas, function(col) {
                if (record.data[col.id]) {
                    _convertRecord(col, record.data);
                }
            });
            angular.forEach(enrollDataSchemas, function(col) {
                if (record.data[col.id]) {
                    _convertRecord(col, record.data);
                }
            });
        }
        $scope.app = app;
        $scope.enrollDataSchemas = enrollDataSchemas;
        $scope.record = record;
        $scope.record.aTags = (!record.tags || record.tags.length === 0) ? [] : record.tags.split(',');
        $scope.aTags = app.tags;
        $scope.ok = function() {
            var record = $scope.record,
                p = {};

            p.data = record.data;
            p.verified = record.verified;
            p.tags = record.tags = record.aTags.join(',');
            p.comment = record.comment;
            p.signin_log = record.signin_log;

            $mi.close([p, $scope.aTags]);
        };
        $scope.cancel = function() {
            $mi.dismiss('cancel');
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
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            if (data.state === 'signinAt') {
                !record.signin_log && (record.signin_log = {});
                record.signin_log[data.obj.rid] = data.value;
            }
        });
        $scope.syncByEnroll = function() {
            var url;

            url = '/rest/pl/fe/matter/signin/record/matchEnroll';
            url += '?site=' + app.siteid;
            url += '&app=' + app.id;

            http2.post(url, $scope.record.data, function(rsp) {
                var matched;
                if (rsp.data && rsp.data.length === 1) {
                    matched = rsp.data[0];
                    angular.forEach(enrollDataSchemas, function(col) {
                        if (matched[col.id]) {
                            _convertRecord(col, matched);
                        }
                    });
                    angular.extend(record.data, matched);
                } else {
                    alert('没有找到匹配的记录，请检查数据是否一致');
                }
            });
        };
    }]);
});