(function () {
    xxtApp.register.controller('settingCtrl', ['$scope', 'http2', 'matterTypes', '$modal', function ($scope, http2, matterTypes, $modal) {
        $scope.$parent.subView = 'setting';
        $scope.pages4OutAcl = [];
        $scope.pages4Unauth = [];
        $scope.pages4Nonfan = [];
        $scope.$watch('editing.pages', function (nv) {
            var newPage;
            if (!nv) return;
            $scope.pages4OutAcl = $scope.editing.access_control === 'Y' ? [{ name: '$authapi_outacl', title: '提示白名单' }] : [];
            $scope.pages4Unauth = $scope.editing.access_control === 'Y' ? [{ name: '$authapi_auth', title: '提示认证' }] : [];
            $scope.pages4Nonfan = [{ name: '$mp_follow', title: '提示关注' }];
            for (var p in nv) {
                newPage = {
                    name: p,
                    title: nv[p].title
                };
                $scope.pages4OutAcl.push(newPage);
                $scope.pages4Unauth.push(newPage);
                $scope.pages4Nonfan.push(newPage);
            }
        }, true);
        $scope.matterTypes = matterTypes;
        $scope.updateEntryRule = function () {
            var p = { entry_rule: encodeURIComponent(JSON.stringify($scope.editing.entry_rule)) };
            http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, p, function (rsp) {
                $scope.persisted = angular.copy($scope.editing);
            });
        };
        $scope.setPic = function () {
            var options = {
                callback: function (url) {
                    $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update('pic');
                }
            };
            $scope.$broadcast('mediagallery.open', options);
        };
        $scope.removePic = function () {
            var nv = { pic: '' };
            http2.post('/rest/mp/app/enroll/update?aid=' + $scope.aid, nv, function () {
                $scope.editing.pic = '';
            });
        };
        $scope.$on('xxt.tms-datepicker.change', function (event, data) {
            $scope.editing[data.state] = data.value;
            $scope.update(data.state);
        });
        $scope.setSuccessReply = function () {
            $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
                if (aSelected.length === 1) {
                    var p = { mt: matterType, mid: aSelected[0].id };
                    http2.post('/rest/mp/app/enroll/setSuccessReply?aid=' + $scope.aid, p, function (rsp) {
                        $scope.editing.successMatter = aSelected[0];
                    });
                }
            });
        };
        $scope.setFailureReply = function () {
            $scope.$broadcast('mattersgallery.open', function (aSelected, matterType) {
                if (aSelected.length === 1) {
                    var p = { mt: matterType, mid: aSelected[0].id };
                    http2.post('/rest/mp/app/enroll/setFailureReply?aid=' + $scope.aid, p, function (rsp) {
                        $scope.editing.failureMatter = aSelected[0];
                    });
                }
            });
        };
        $scope.removeSuccessReply = function () {
            var p = { mt: '', mid: '' };
            http2.post('/rest/mp/app/enroll/setSuccessReply?aid=' + $scope.aid, p, function (rsp) {
                $scope.editing.successMatter = null;
            });
        };
        $scope.removeFailureReply = function () {
            var p = { mt: '', mid: '' };
            http2.post('/rest/mp/app/enroll/setFailureReply?aid=' + $scope.aid, p, function (rsp) {
                $scope.editing.failureMatter = null;
            });
        };
    }]);
})();
