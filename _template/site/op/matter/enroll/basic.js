ngApp.provider.controller('ctrlBasic', ['$scope', '$http', 'PageUrl', function($scope, $http, PageUrl) {
    function submit(ek, posted) {
        $http.post(PU.j('record/update', 'site', 'app', 'accessToken') + '&ek=' + ek, posted).success(function(rsp) {
            if (rsp.err_code !== 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
            angular.extend($scope.editing, rsp.data);
        });
    };

    var PU, _history = [];

    PU = PageUrl.ins('/rest/site/op/matter/enroll', ['site', 'app', 'accessToken']);
    $scope.subView = 'list';
    $scope.editing = null;
    $scope.editRecord = function(record) {
        $scope.subView = 'record';
        _history.push(record);
        $scope.editing = angular.copy(record);
    };
    $scope.back = function() {
        var origin;
        $scope.subView = 'list';
        origin = _history.pop();
        angular.extend(origin, $scope.editing);
        $scope.editing = null;
    };
    $scope.verify = function(pass) {
        var ek = $scope.editing.enroll_key,
            posted = {};

        posted.verified = pass;
        submit(ek, posted);
    };
    $scope.update = function() {
        var ek = $scope.editing.enroll_key,
            posted = {};
        posted = {
            comment:$scope.editing.comment,
            data:$scope.editing.data,
            tags:$scope.editing.tags
        }
        submit(ek, posted);
    };
}]);
