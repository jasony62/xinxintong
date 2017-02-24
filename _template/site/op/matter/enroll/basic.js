ngApp.provider.controller('ctrlBasic', ['$scope', '$http', 'PageUrl', 'srvApp', 'srvRecord', 'srvRecordConverter', function($scope, $http, PageUrl, srvApp, srvRecord, srvRecordConverter) {
    var PU, params = location.search.match('site=(.*)')[1];
    PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);

    function submit(ek, posted) {
        $http.post(PU.j('record/update', 'site', 'app', 'accessToken') + '&ek=' + ek, posted).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            angular.extend($scope.record, rsp.data);
            $scope.back();
        });
    };
    $scope.editRecord = function(record) {
        $scope.subView = 'editing';
        srvApp.opGet().then(function(data) {
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
            $scope.record = record;
            $scope.record.aTags = (!record.tags || record.tags.length === 0) ? [] : record.tags.split(',');
            $scope.aTags = app.tags;
        });
    }
    $scope.update = function() {
        var record = $scope.record,
            ek = $scope.record.enroll_key,
            p = {
                tags: record.aTags.join(','),
                data: {}
            };

        record.tags = p.tags;
        record.comment && (p.comment = record.comment);
        p.verified = record.verified;
        p.data = $scope.record.data;
        submit(ek, p);
    };
    $scope.back = function() {
        location.href = '/rest/site/op/matter/enroll?site=' + params;
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
