define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEditor', ['$scope', '$q', 'noticebox', '$location', 'srvEnrollApp', 'srvEnrollRecord', 'srvRecordConverter', 'srvEnrollRound', function($scope, $q, noticebox, $location, srvEnrollApp, srvEnrollRecord, srvRecordConverter, srvEnlRnd) {
        function _afterGetApp(app) {
            if (oRecord.data) {
                srvRecordConverter.forTable(oRecord, app._schemasById);
                app.dataSchemas.forEach(function(schema) {
                    if (oRecord.data[schema.id]) {
                        srvRecordConverter.forEdit(schema, oRecord.data);
                    }
                });
                app._schemasFromEnrollApp.forEach(function(schema) {
                    if (oRecord.data[schema.id]) {
                        srvRecordConverter.forEdit(schema, oRecord.data);
                    }
                });
                app._schemasFromGroupApp.forEach(function(schema) {
                    if (oRecord.data[schema.id]) {
                        srvRecordConverter.forEdit(schema, oRecord.data);
                    }
                });
                oBeforeRecord = angular.copy(oRecord);
            } else {
                oRecord.data = {};
            }
            $scope.app = oApp = app;
            $scope.enrollDataSchemas = app._schemasByEnrollApp;
            $scope.groupDataSchemas = app._schemasByGroupApp;
            $scope.aTags = app.tags;
            if(oRecord.data_tag) {
                for(var schemaId in oRecord.data_tag) {
                    var dataTags = oRecord.data_tag[schemaId],
                        converted = [];
                    dataTags.forEach(function(tagId) {
                        $scope.app._tagsById[tagId] && converted.push(app._tagsById[tagId]);
                    });
                    oRecord.data_tag[schemaId] = converted;
                }
            }
            if (oApp.scenario === 'quiz') {
                oQuizScore = {};
                _quizScore(oRecord);
                $scope.quizScore = oQuizScore;
            }
            /* 点评数据 */
            var remarkableSchemas = [];
            app.dataSchemas.forEach(function(schema) {
                if (schema.remarkable === 'Y') {
                    schema._open = false;
                    oRecord.verbose && oRecord.verbose[schema.id] && (schema.summary = oRecord.verbose[schema.id]);
                    remarkableSchemas.push(schema);
                }
            });
            $scope.remarkableSchemas = remarkableSchemas;
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

        function doSave (){
            //oRecord 原始数据
            //updated 上传数据包
            var updated = {
                //数组 转 字符串
                tags: oRecord.aTags.join(','),
            };

            oRecord.tags = updated.tags;
            updated.comment = oRecord.comment; //oRecord 信息
            updated.verified = oRecord.verified;
            updated.rid = oRecord.rid;
            updated.userid = oRecord.userid;

            if (oRecord.enroll_key) {
                if (!angular.equals(oRecord.data, oBeforeRecord.data)) {
                    updated.data = oRecord.data;
                }
                if (!angular.equals(oRecord.supplement, oBeforeRecord.supplement)) {
                    updated.supplement = oRecord.supplement;
                }
                if (!angular.equals(oQuizScore, oBeforeQuizScore)) {
                    updated.quizScore = oQuizScore;
                }
                srvEnrollRecord.update(oRecord, updated).then(function(newRecord) {
                    if (oApp.scenario === 'quiz') {
                        _quizScore(newRecord);
                    }
                    noticebox.success('完成保存');
                });
            } else {
                updated.data = oRecord.data;
                updated.supplement = oRecord.supplement;
                updated.quizScore = oQuizScore;
                srvEnrollRecord.add(updated).then(function(newRecord) {
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
            var r, onSubmit;
            r = new Resumable({
                target: '/rest/site/fe/matter/enroll/record/uploadFile?site=' + site + '&app=' + id,
                testChunks: false,
                chunkSize: 512 * 1024
            });
            onSubmit = function($scope) {
                var defer;
                defer = $q.defer();
                if (!r.files || r.files.length === 0)
                    defer.resolve('empty');
                r.on('progress', function() {
                    var phase, p;
                    p = r.progress();
                    var phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.progressOfUploadFile = Math.ceil(p * 100);
                    } else {
                        $scope.$apply(function() {
                            $scope.progressOfUploadFile = Math.ceil(p * 100);
                        });
                    }
                });
                r.on('complete', function() {
                    var phase = $scope.$root.$$phase;
                    if (phase === '$digest' || phase === '$apply') {
                        $scope.progressOfUploadFile = '完成';
                    } else {
                        $scope.$apply(function() {
                            $scope.progressOfUploadFile = '完成';
                        });
                    }
                    r.cancel();
                    defer.resolve('ok');
                });
                r.upload();
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
                    r.addFile(f);
                    $scope.$apply(function() {
                        data[fileFieldName] === undefined && (data[fileFieldName] = []);
                        data[fileFieldName].push({
                            uniqueIdentifier: r.files[r.files.length - 1].uniqueIdentifier,
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
        }
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
        $scope.agree = function(oRecord, oSchema) {
            srvEnrollRecord.agree(oRecord.enroll_key, oSchema.id, oRecord.verbose[oSchema.id].agreed).then(function() {});
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
        $scope.switchSchemaRemarks = function(schema) {
            $scope.openedRemarksSchema = schema;
            srvEnrollRecord.listRemark(ek, schema.id).then(function(result) {
                schemaRemarks[schema.id] = result.remarks;
            });
        };
        $scope.addRemark = function(schema) {
            srvEnrollRecord.addRemark(ek, schema ? schema.id : null, $scope.newRemark).then(function(remark) {
                if (schema) {
                    !schemaRemarks[schema.id] && (schemaRemarks[schema.id] = []);
                    schemaRemarks[schema.id].push(remark);
                    if (oRecord.verbose[schema.id] === undefined) {
                        oRecord.verbose[schema.id] = {};
                    }
                    oRecord.verbose[schema.id].remark_num = schemaRemarks[schema.id].length;
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
