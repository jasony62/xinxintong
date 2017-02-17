ngApp.provider.controller('ctrlBasic', ['$scope', '$http', 'PageUrl', 'srvRecordConverter', function($scope, $http, PageUrl, srvRecordConverter) {
    function submit(ek, posted) {
        $http.post(PU.j('record/update', 'site', 'app', 'accessToken') + '&ek=' + ek, posted).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            angular.extend($scope.editing, rsp.data);
            $scope.back();
        });
    };
    var PU, params;
    params = location.search.match('site=(.*)')[1];
    PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);
    $scope.subView = 'list';
    $scope.editing = null;
    $scope.editRecord = function(record) {
        $scope.subView = 'record';
        if (record.data) {
            $scope.app.dataSchemas.forEach(function(col) {
                if (record.data[col.id]) {
                    srvRecordConverter.forEdit(col, record.data);
                }
            });
        }
        record.aTags = (!record.tags || record.tags.length === 0) ? [] : record.tags.split(',');
        $scope.editing = record;
    };
    $scope.back = function() {
        location.href = '/rest/site/op/matter/enroll?site=' + params;
    };
    $scope.verify = function(pass) {
        var ek = $scope.editing.enroll_key,
            posted = {};

        posted.verified = pass;
        submit(ek, posted);
    };
    $scope.update = function() {
        var editing = $scope.editing,
            ek = $scope.editing.enroll_key,
            p = {
                tags: editing.aTags.join(','),
                data: {}
            };
        editing.tags = p.tags;
        editing.comment && (p.comment = editing.comment);
        p.data = $scope.editing.data;
        submit(ek, p);
    };
    $scope.$on('tag.xxt.combox.done', function(event, aSelected) {
            var aNewTags = [];
            for (var i in aSelected) {
                var existing = false;
                for (var j in $scope.editing.aTags) {
                    if (aSelected[i] === $scope.editing.aTags[j]) {
                        existing = true;
                        break;
                    }
                }!existing && aNewTags.push(aSelected[i]);
            }
            $scope.editing.aTags = $scope.editing.aTags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function(event, newTag) {
        if (-1 === $scope.editing.aTags.indexOf(newTag)) {
            $scope.editing.aTags.push(newTag);
            if (-1 === $scope.aTags.indexOf(newTag)) {
                $scope.aTags.push(newTag);
            }
        }
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed) {
        $scope.editing.aTags.splice($scope.editing.aTags.indexOf(removed), 1);
    });
}]);
