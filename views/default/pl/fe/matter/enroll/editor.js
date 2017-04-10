define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlEditor', ['$scope', '$location', 'srvEnrollApp', 'srvEnrollRecord', 'srvRecordConverter', 'srvEnrollRound', function($scope, $location, srvEnrollApp, srvEnrollRecord, srvRecordConverter, srvEnlRnd) {
        function afterGetApp(app) {
            if (oRecord.data) {
                app.data_schemas.forEach(function(schema) {
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
            }
            $scope.app = oApp = app;
            $scope.enrollDataSchemas = app._schemasByEnrollApp;
            $scope.groupDataSchemas = app._schemasByGroupApp;
            $scope.aTags = app.tags;
        }

        var oRecord, oBeforeRecord, oApp;

        $scope.save = function() {
            var updated = {
                tags: oRecord.aTags.join(','),
            };

            oRecord.tags = updated.tags;
            updated.comment = oRecord.comment;
            updated.verified = oRecord.verified;
            updated.rid = oRecord.rid;
            if (oRecord.enroll_key) {
                if (!angular.equals(oRecord.data, oBeforeRecord.data)) {
                    updated.data = oRecord.data;
                }
                srvEnrollRecord.update(oRecord, updated);
            } else {
                srvEnrollRecord.add(updated).then(function(newRecord) {
                    oRecord.enroll_key = newRecord.enroll_key;
                    oRecord.enroll_at = newRecord.enroll_at;
                    $location.search({ site: oApp.siteid, id: oApp.id, ek: newRecord.enroll_key });
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

        if ($location.search().ek) {
            srvEnrollRecord.get($location.search().ek).then(function(record) {
                $scope.record = oRecord = record;
                oRecord.aTags = (!oRecord.tags || oRecord.tags.length === 0) ? [] : oRecord.tags.split(',');
                $scope.doSearchRound();
                srvEnrollApp.get().then(function(app) {
                    afterGetApp(app);
                });
            });
        } else {
            $scope.record = oRecord = {};
            oRecord.aTags = [];
            $scope.doSearchRound();
            srvEnrollApp.get().then(function(app) {
                afterGetApp(app);
            });
        }
    }]);
    ngApp.provider.controller('ctrlRemark', ['$scope', '$location', '$q', '$http', 'srvEnrollApp', 'srvEnrollRecord', function($scope, $location, $q, $http, srvEnrollApp, srvEnrollRecord) {
        function summary(ek) {
            var url, defer = $q.defer();
            url = '/rest/site/fe/matter/enroll/remark/summary?site=' + $scope.app.siteid + '&ek=' + ek;
            $http.get(url).success(function(rsp) {
                defer.resolve(rsp.data)
            });
            return defer.promise;
        }

        var ek = $location.search().ek,
            remarkableSchemas = [],
            schemaRemarks;

        $scope.newRemark = {};
        $scope.schemaRemarks = schemaRemarks = {};
        $scope.switchSchema = function(schema) {
            schema._open = !schema._open;
            if (schema._open) {
                srvEnrollRecord.listRemark(ek, schema.id).then(function(result) {
                    schemaRemarks[schema.id] = result.remarks;
                });
            }
        };
        $scope.addRemark = function(schema) {
            srvEnrollRecord.addRemark(ek, schema ? schema.id : null, $scope.newRemark).then(function(remark) {
                if (schema) {
                    !schemaRemarks[schema.id] && (schemaRemarks[schema.id] = []);
                    schemaRemarks[schema.id].splice(0, 0, remark);
                } else {
                    $scope.remarks.splice(0, 0, remark);
                }
                $scope.newRemark.content = '';
            });
        };
        srvEnrollApp.get().then(function(app) {
            if (ek) {
                summary(ek).then(function(result) {
                    var summaryBySchema = {};
                    result.forEach(function(schema) {
                        summaryBySchema[schema.schema_id] = schema;
                    });
                    app.data_schemas.forEach(function(schema) {
                        summaryBySchema[schema.id] && (schema.summary = summaryBySchema[schema.id]);
                        remarkableSchemas.push(schema);
                    });
                    $scope.remarkableSchemas = remarkableSchemas;
                });
            } else {
                app.data_schemas.forEach(function(schema) {
                    remarkableSchemas.push(schema);
                });
                $scope.remarkableSchemas = remarkableSchemas;
            }
        });
    }]);
});
