define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlRecord', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
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
            var url;
            page && ($scope.page.at = page);
            url = '/rest/pl/fe/matter/enroll/record/list';
            url += '?site=' + $scope.siteId; // todo
            url += '&app=' + $scope.app.id;
            if ($scope.app.can_signin === 'Y') {
                url += '&signinStartAt=' + $scope.signinStartAt;
                url += '&signinEndAt=' + $scope.signinEndAt;
            }
            url += '&tags=' + $scope.page.tags.join(',');
            url += $scope.page.joinParams();
            http2.get(url, function(rsp) {
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
        $scope.page = {
            at: 1,
            size: 30,
            keyword: '',
            tags: [],
            searchBy: 'nickname',
            orderBy: 'time',
            joinParams: function() {
                var p;
                p = '&page=' + this.at + '&size=' + this.size;
                if (this.keyword !== '') {
                    p += '&kw=' + this.keyword;
                    p += '&by=' + this.searchBy;
                }
                p += '&orderby=' + this.orderBy;
                p += '&rid=' + (this.byRound ? this.byRound : 'ALL');
                return p;
            }
        };
        $scope.searchBys = [{
            n: '昵称',
            v: 'nickname'
        }];
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
        $scope.signinStartAt = startAt.getTime() / 1000;
        $scope.signinEndAt = endAt.getTime() / 1000;
        $scope.selected = {};
        $scope.selectAll;
        $scope.$on('xxt.tms-datepicker.change', function(evt, data) {
            $scope[data.state] = data.value;
            $scope.doSearch(1);
        });
        $scope.$on('search-tag.xxt.combox.done', function(event, aSelected) {
            $scope.page.tags = $scope.page.tags.concat(aSelected);
            $scope.doSearch();
        });
        $scope.$on('search-tag.xxt.combox.del', function(event, removed) {
            var i = $scope.page.tags.indexOf(removed);
            $scope.page.tags.splice(i, 1);
            $scope.doSearch();
        });
        $scope.$on('batch-tag.xxt.combox.done', function(event, aSelected) {
            var i, record, records, eks, posted;
            records = [];
            eks = [];
            for (i in $scope.selected) {
                if ($scope.selected) {
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
        $scope.viewUser = function(fan) {
            //location.href = '/rest/mp/user?openid=' + fan.openid;
        };
        $scope.keywordKeyup = function(evt) {
            evt.which === 13 && $scope.doSearch();
        };
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
        $scope.json2Obj = function(json) {
            if (json && json.length) {
                obj = JSON.parse(json);
                return obj;
            } else {
                return {};
            }
        };
        $scope.editRecord = function(record) {
            $uibModal.open({
                templateUrl: 'recordEditor.html',
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
                    //tags = updated[1];
                    var data = rsp.data.data;
                    if ($scope.mapOfSchemaByType['image'] && $scope.mapOfSchemaByType['image'].length) {
                        angular.forEach($scope.mapOfSchemaByType['image'], function(schemaId) {
                            var imgs = data[schemaId] ? data[schemaId].split(',') : [];
                            data[schemaId] = imgs;
                        });
                    }
                    angular.extend(record, p);
                    //$scope.app.tags = tags;
                });
            });
        };
        $scope.addRecord = function() {
            $uibModal.open({
                templateUrl: 'recordEditor.html',
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
                    //$scope.app.tags = tags;
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
            http2.get('/rest/pl/fe/matter/enroll/record/verifyAll?site=' + $scope.siteId + '&app=' + $scope.id, function(rsp) {
                angular.forEach($scope.records, function(record) {
                    record.verified = 'Y';
                    $scope.$root.infomsg = '完成操作';
                });
            });
        };
        $scope.$watch('selectAll', function(nv) {
            if (nv !== undefined) {
                for (var i = $scope.records.length - 1; i >= 0; i--) {
                    $scope.selected[i] = nv;
                }
            }
        });
        $scope.$watch('app', function(app) {
            if (!app) return;
            var mapOfSchemaByType = {};
            angular.forEach(app.data_schemas, function(schema) {
                mapOfSchemaByType[schema.type] === undefined && (mapOfSchemaByType[schema.type] = []);
                mapOfSchemaByType[schema.type].push(schema.id);
            });
            $scope.mapOfSchemaByType = mapOfSchemaByType;
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
    ngApp.provider.controller('ctrlEditor', ['$scope', '$uibModalInstance', '$sce', 'app', 'record', function($scope, $uibModalInstance, $sce, app, record) {
        var p, col, files;
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
        $scope.app = app;
        $scope.record = record;
        $scope.record.aTags = (!record.tags || record.tags.length === 0) ? [] : record.tags.split(',');
        $scope.aTags = app.tags;
        $scope.json2Obj = function(json) {
            if (json && json.length) {
                obj = JSON.parse(json);
                return obj;
            } else {
                return {};
            }
        };
        $scope.signin = function() {
            $scope.record.signin_at = Math.round((new Date()).getTime() / 1000);
        };
        $scope.ok = function() {
            var record = $scope.record,
                p = {
                    //tags: record.aTags.join(','),
                    data: {}
                };
            record.tags = p.tags;
            //if (record.id) {
            //p.signin_at = record.signin_at;
            p.verified = record.verified;
            //}

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