ngApp.provider.controller('ctrlBasic', ['$scope', '$http', function($scope, $http) {
    /*function submit(ek, posted) {
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
    PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);*/


    /*$scope.editRecord = function(record) {
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
    function _chooseImage(imgFieldName) {
        var defer = $q.defer();
        if (imgFieldName !== null) {
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
                            defer.resolve(img);
                        };
                    })(f);
                    reader.readAsDataURL(f);
                }
            }, false);
            ele.click();
        }
        return defer.promise;
    }
    $scope.chooseImage = function(fieldName) {
        var data = $scope.editing.data;
        _chooseImage(fieldName).then(function(img) {
            !data[fieldName] && (data[fieldName] = []);
            data[fieldName].push(img);
        });
    };
    $scope.removeImage = function(field, index) {
        field.splice(index, 1);
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
    });*/
}]);
