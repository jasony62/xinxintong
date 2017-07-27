define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEditor', ['$scope', '$location', 'srvEnrollApp', 'srvEnrollRecord', 'srvRecordConverter', 'srvEnrollRound', function($scope, $location, srvEnrollApp, srvEnrollRecord, srvRecordConverter, srvEnlRnd) {
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

        var oRecord, oBeforeRecord, oQuizScore, oBeforeQuizScore, oApp;

        $scope.save = function() {
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
                });
            }
            oBeforeRecord = angular.copy(oRecord);
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
            var data = oRecord.data;
            srvEnrollRecord.chooseImage(fieldName).then(function(img) {
                !data[fieldName] && (data[fieldName] = []);
                data[fieldName].push(img);
            });
        };
        $scope.removeImage = function(field, index) {
            field.splice(index, 1);
        };
        $scope.chooseFile = function(fieldName) {
            var data = oRecord.data;
            srvEnrollRecord.chooseFile(fieldName).then(function(file) {
                !data[fieldName] && (data[fieldName] = []);
                data[fieldName].push(file);
            });
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
