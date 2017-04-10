ngApp.provider.controller('ctrlBasic', ['$scope', '$http', 'PageUrl', 'srvEnrollApp', 'srvEnrollRecord', 'srvOpEnrollRound', 'srvRecordConverter', function($scope, $http, PageUrl, srvEnrollApp, srvEnrollRecord, srvOpEnrollRound, srvRecordConverter) {
    var oRecord, oBeforeRecord, PU, params = location.search.match('site=(.*)')[1];
    PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);

    function submit(ek, posted) {
        $http.post(PU.j('record/update', 'site', 'app', 'accessToken') + '&ek=' + ek, posted).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            $scope.back();
        });
    };
    $scope.editRecord = function(record) {
        $scope.subView = 'editing';
        srvEnrollApp.opGet().then(function(data) {
            var app = data.app;
            if (record.data) {
                app.data_schemas.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvRecordConverter.forEdit(col, record.data);
                    }
                });
                app._schemasFromEnrollApp.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvRecordConverter.forEdit(col, record.data);
                    }
                });
                app._schemasFromGroupApp.forEach(function(col) {
                    if (record.data[col.id]) {
                        srvRecordConverter.forEdit(col, record.data);
                    }
                });
            }
            $scope.app = app;
            $scope.enrollDataSchemas = app._schemasByEnrollApp;
            $scope.groupDataSchemas = app._schemasByGroupApp;
            $scope.record = oRecord = record;
            oBeforeRecord = angular.copy(oRecord);
            oRecord.aTags = (!oRecord.tags || oRecord.tags.length === 0) ? [] : oRecord.tags.split(',');
            $scope.aTags = app.tags;
        });
        $scope.doSearchRound();
    }
    $scope.update = function() {
        var updated = {
            tags: oRecord.aTags.join(','),
        };

        oRecord.tags = updated.tags;
        updated.comment = oRecord.comment;
        updated.verified = oRecord.verified;
        if (!angular.equals(oRecord.data, oBeforeRecord.data)) {
            updated.data = oRecord.data;
        }
        updated.rid = oRecord.rid;
        submit(oRecord.enroll_key, updated);
    };
    $scope.back = function() {
        location.href = '/rest/site/op/matter/enroll?site=' + params;
    };
    $scope.chooseImage = function(fieldName) {
        var data = $scope.record.data;
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
        srvEnrollRecord.syncByEnroll($scope.record);
    };
    $scope.syncByGroup = function() {
        srvEnrollRecord.syncByGroup($scope.record);
    };
    $scope.doSearchRound = function() {
        srvOpEnrollRound.list().then(function(result) {
            $scope.activeRound = result.active;
            $scope.rounds = result.rounds;
            $scope.pageOfRound = result.page;
        });
    };
}]);
