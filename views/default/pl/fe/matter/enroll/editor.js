define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEditor', ['$scope', '$sce', '$q', 'noticebox', '$location', 'srvEnrollApp', 'srvEnrollRecord', 'srvEnrollRound', 'tmsSchema', function($scope, $sce, $q, noticebox, $location, srvEnrollApp, srvEnrollRecord, srvEnlRnd, tmsSchema) {
        function _afterGetApp(app) {
            if (oRecord.data) {
                app.dataSchemas.forEach(function(schema) {
                    if (oRecord.data[schema.id]) {
                        tmsSchema.forEdit(schema, oRecord.data);
                        if (schema.type == 'multitext') {
                            _items(schema);
                        } else if (schema.type === 'file') {
                            if (oRecord.data[schema.id].length) {
                                oRecord.data[schema.id].forEach(function(oFile) {
                                    if (oFile.url) {
                                        oFile.url = $sce.trustAsResourceUrl(oFile.url);
                                    }
                                });
                            }
                        }
                    }
                });
                app._schemasFromEnrollApp.forEach(function(schema) {
                    if (oRecord.data[schema.id]) {
                        tmsSchema.forEdit(schema, oRecord.data);
                    }
                });
                app._schemasFromGroupApp.forEach(function(schema) {
                    if (oRecord.data[schema.id]) {
                        tmsSchema.forEdit(schema, oRecord.data);
                    }
                });
                oBeforeRecord = angular.copy(oRecord);
            } else {
                oRecord.data = {};
            }
            $scope.app = oApp = app;
            $scope.enrollDataSchemas = app._schemasByEnrollApp;
            $scope.groupDataSchemas = app._schemasByGroupApp;
            if (oApp.scenario === 'quiz') {
                oQuizScore = {};
                _quizScore(oRecord);
                $scope.quizScore = oQuizScore;
            }
        }

        function _items(schema) {
            var _item = {};
            angular.forEach(oRecord.verbose[schema.id].items, function(item) {
                _item[item.id] = item;
                oRecord.verbose[schema.id]._items = _item;
            });
        }

        function _quizScore(oRecord) {
            if (oRecord.verbose) {
                for (var schemaId in oRecord.verbose) {
                    oQuizScore[schemaId] = oRecord.verbose[schemaId].score;
                }
                oBeforeQuizScore = angular.copy(oQuizScore);
            }
        }

        function doTask(seq) {
            var task = oTasksOfBeforeSubmit[seq];
            task().then(function(rsp) {
                seq++;
                seq < oTasksOfBeforeSubmit.length ? doTask(seq) : doSave();
            });
        }

        function doSave() {
            //oRecord 原始数据
            //updated 上传数据包
            var oUpdated = {
                //数组 转 字符串
                tags: oRecord.aTags.join(','),
            };
            /*多项填空题，如果值为空则删掉*/
            for (var k in oRecord.data) {
                if (k !== 'member' && oApp._schemasById[k] && oApp._schemasById[k].type == 'multitext') {
                    angular.forEach(oRecord.data[k], function(data, index) {
                        if (data.value == '') {
                            oRecord.data[k].splice(index, 1);
                        }
                    });
                }
            };

            oRecord.tags = oUpdated.tags;
            oUpdated.comment = oRecord.comment; //oRecord 信息
            oUpdated.agreed = oRecord.agreed; //oRecord 信息
            oUpdated.verified = oRecord.verified;
            oUpdated.rid = oRecord.rid;
            oUpdated.userid = oRecord.userid;

            if (oRecord.enroll_key) {
                if (!angular.equals(oRecord.data, oBeforeRecord.data)) {
                    oUpdated.data = oRecord.data;
                }
                if (!angular.equals(oRecord.supplement, oBeforeRecord.supplement)) {
                    oUpdated.supplement = oRecord.supplement;
                }
                if (!angular.equals(oRecord.score, oBeforeRecord.score)) {
                    oUpdated.score = oRecord.score;
                }
                if (!angular.equals(oQuizScore, oBeforeQuizScore)) {
                    oUpdated.quizScore = oQuizScore;
                }
                srvEnrollRecord.update(oRecord, oUpdated).then(function(newRecord) {
                    if (oApp.scenario === 'quiz') {
                        _quizScore(newRecord);
                    }
                    noticebox.success('完成保存');
                });
            } else {
                oUpdated.data = oRecord.data;
                oUpdated.supplement = oRecord.supplement;
                oUpdated.quizScore = oQuizScore;
                srvEnrollRecord.add(oUpdated).then(function(newRecord) {
                    oRecord.enroll_key = newRecord.enroll_key;
                    oRecord.enroll_at = newRecord.enroll_at;
                    $location.search({ site: oApp.siteid, id: oApp.id, ek: newRecord.enroll_key });
                    if (oApp.scenario === 'quiz') {
                        _quizScore(newRecord);
                    }
                    noticebox.success('完成保存');
                });
            }
            oBeforeRecord = angular.copy(oRecord);
        };

        var oRecord, oBeforeRecord, oQuizScore, oBeforeQuizScore, oApp, oTasksOfBeforeSubmit;
        oTasksOfBeforeSubmit = [];

        $scope.save = function() {
            oTasksOfBeforeSubmit.length ? doTask(0) : doSave();
        }
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
            var data = oRecord.data;
            srvEnrollRecord.chooseImage(fieldName).then(function(img) {
                !data[fieldName] && (data[fieldName] = []);
                data[fieldName].push(img);
            });
        };
        $scope.removeImage = function(field, index) {
            field.splice(index, 1);
        };
        $scope.beforeSubmit = function(fn) {
            if (oTasksOfBeforeSubmit.indexOf(fn) === -1) {
                oTasksOfBeforeSubmit.push(fn);
            }
        };
        $scope.chooseFile = function(fileFieldName) {
            var oResumable, onSubmit;
            oResumable = new Resumable({
                target: '/rest/site/fe/matter/enroll/record/uploadFile?site=' + site + '&app=' + id,
                testChunks: false,
                chunkSize: 512 * 1024
            });
            onSubmit = function($scope) {
                var defer;
                defer = $q.defer();
                if (!oResumable.files || oResumable.files.length === 0)
                    defer.resolve('empty');
                oResumable.on('progress', function() {
                    var phase, p;
                    p = oResumable.progress();
                    var phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        noticebox.progress('上传文件：' + Math.ceil(p * 100));
                    } else {
                        $scope.$apply(function() {
                            noticebox.progress('上传文件：' + Math.ceil(p * 100));
                        });
                    }
                });
                oResumable.on('complete', function() {
                    var phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        noticebox.close();
                    } else {
                        $scope.$apply(function() {
                            noticebox.close();
                        });
                    }
                    oResumable.cancel();
                    defer.resolve('ok');
                });
                oResumable.upload();
                return defer.promise;
            };
            $scope.beforeSubmit(function() {
                return onSubmit($scope);
            });
            var data = oRecord.data;
            var ele = document.createElement('input');
            ele.setAttribute('type', 'file');
            ele.addEventListener('change', function(evt) {
                var i, cnt, f;
                cnt = evt.target.files.length;
                for (i = 0; i < cnt; i++) {
                    f = evt.target.files[i];
                    oResumable.addFile(f);
                    $scope.$apply(function() {
                        data[fileFieldName] === undefined && (data[fileFieldName] = []);
                        data[fileFieldName].push({
                            uniqueIdentifier: oResumable.files[oResumable.files.length - 1].uniqueIdentifier,
                            name: f.name,
                            size: f.size,
                            type: f.type,
                            url: ''
                        });
                    });
                }
                ele = null;
            }, true);
            ele.click();
        };
        $scope.removeFile = function(field, index) {
            field.splice(index, 1);
        };
        $scope.addItem = function(schemaId) {
            var data = oRecord.data;
            var item = {
                id: 0,
                value: ''
            }
            data[schemaId].push(item);
        };
        $scope.removeItem = function(items, index) {
            items.splice(index, 1);
        };
        $scope.$on('tag.xxt.combox.done', function(event, aSelected) {
            var aNewTags = [];
            for (var i in aSelected) {
                var existing = false;
                for (var j in oRecord.aTags) {
                    if (aSelected[i] === oRecord.aTags[j]) {
                        existing = true;
                        break;
                    }
                }!existing && aNewTags.push(aSelected[i]);
            }
            oRecord.aTags = oRecord.aTags.concat(aNewTags);
        });
        $scope.$on('tag.xxt.combox.add', function(event, newTag) {
            if (-1 === oRecord.aTags.indexOf(newTag)) {
                oRecord.aTags.push(newTag);
                if (-1 === $scope.aTags.indexOf(newTag)) {
                    $scope.aTags.push(newTag);
                }
            }
        });
        $scope.$on('tag.xxt.combox.del', function(event, removed) {
            oRecord.aTags.splice(oRecord.aTags.indexOf(removed), 1);
        });
        $scope.syncByEnroll = function() {
            srvEnrollRecord.syncByEnroll(oRecord);
        };
        $scope.syncByGroup = function() {
            srvEnrollRecord.syncByGroup(oRecord);
        };
        $scope.doSearchRound = function() {
            srvEnlRnd.list().then(function(result) {
                $scope.activeRound = result.active;
                $scope.rounds = result.rounds;
                $scope.pageOfRound = result.page;
            });
        };
        $scope.agree = function(oRecord, oSchema, oAgreed, oItemId) {
            srvEnrollRecord.agree(oRecord.enroll_key, oSchema.id, oAgreed, oItemId).then(function() {});
        };
        $scope.agreeRemark = function(oRemark) {
            srvEnrollRecord.agreeRemark(oRemark.id, oRemark.agreed).then(function() {});
        };
        var ek = $location.search().ek,
            site = $location.search().site,
            id = $location.search().id,
            schemaRemarks;

        $scope.newRemark = {};
        $scope.schemaRemarks = schemaRemarks = {};
        $scope.openedRemarksSchema = false;
        $scope.openedItemRemarksSchema = false;
        $scope.switchSchemaRemarks = function(schema, itemId) {
            $scope.openedRemarksSchema = schema;
            $scope.openedItemRemarksSchema = itemId;
            srvEnrollRecord.listRemark(ek, schema.id, itemId).then(function(result) {
                schemaRemarks[itemId] = result.remarks;
            });
        };
        $scope.addRemark = function(schema, itemId) {
            srvEnrollRecord.addRemark(ek, schema ? schema.id : null, $scope.newRemark, itemId).then(function(remark) {
                if (itemId) {
                    !schemaRemarks[itemId] && (schemaRemarks[itemId] = []);
                    schemaRemarks[itemId].push(remark);
                    if (oRecord.verbose[schema.id] === undefined) {
                        oRecord.verbose[schema.id] = {};
                    }
                    if (schema.type == 'multitext' && oRecord.verbose[schema.id].id !== itemId) {
                        oRecord.verbose[schema.id]._items[itemId].remark_num = schemaRemarks[itemId].length;
                    } else {
                        oRecord.verbose[schema.id].remark_num = schemaRemarks[itemId].length;
                    }
                } else {
                    $scope.remarks.push(remark);
                }
                $scope.newRemark.content = '';
            });
        };
        if (ek) {
            srvEnrollRecord.get(ek).then(function(record) {
                $scope.record = oRecord = record;
                oRecord.aTags = (!oRecord.tags || oRecord.tags.length === 0) ? [] : oRecord.tags.split(',');
                $scope.doSearchRound();
                srvEnrollApp.get().then(function(app) {
                    _afterGetApp(app);
                });
            });
        } else {
            $scope.record = oRecord = {};
            oRecord.aTags = [];
            $scope.doSearchRound();
            srvEnrollApp.get().then(function(app) {
                _afterGetApp(app);
            });
        }
    }]);
});