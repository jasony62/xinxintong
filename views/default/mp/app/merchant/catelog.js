(function () {
    xxtApp.register.controller('catelogCtrl', ['$scope', '$modal', 'http2', function ($scope, $modal, http2) {
        $scope.$parent.subView = 'catelog';
        $scope.get = function () {
            http2.get('/rest/mp/app/merchant/catelog/get?shopId=' + $scope.shopId, function (rsp) {
                $scope.catelogs = rsp.data;
            });
        };
        $scope.open = function (catelog) {
            if (catelog.properties === undefined) {
                http2.get('/rest/mp/app/merchant/catelog/cascaded?id=' + catelog.id, function (rsp) {
                    catelog.properties = rsp.data.properties;
                    $scope.editing = catelog;
                });
            } else
                $scope.editing = catelog;
        };
        $scope.create = function () {
            http2.get('/rest/mp/app/merchant/catelog/create?shopId=' + $scope.shopId, function (rsp) {
                $scope.catelogs.push(rsp.data);
            });
        };
        $scope.addProp = function () {
            http2.get('/rest/mp/app/merchant/catelog/propCreate?id=' + $scope.editing.id, function (rsp) {
                var len = $scope.editing.properties.push(rsp.data);
                $scope.editProp(rsp.data, len - 1);
            });
        };
        $scope.editProp = function (prop, index) {
            $modal.open({
                templateUrl: 'propEditor.html',
                backdrop: 'static',
                controller: ['$modalInstance', '$scope', function ($modalInstance, $scope) {
                    $scope.prop = angular.copy(prop);
                    $scope.close = function () {
                        $modalInstance.dismiss();
                    };
                    $scope.remove = function () {
                        $modalInstance.close({ name: 'remove', data: $scope.prop });
                    };
                    $scope.ok = function () {
                        $modalInstance.close({ name: 'update', data: $scope.prop });
                    };
                }]
            }).result.then(function (action) {
                if (action.name === 'update')
                    http2.post('/rest/mp/app/merchant/catelog/propUpdate', action.data, function (rsp) {
                        prop.name = rsp.data.name;
                    });
                else if (action.name === 'remove')
                    http2.get('/rest/mp/app/merchant/catelog/propRemove?id=' + prop.id, function (rsp) {
                        $scope.editing.properties.splice(index, 1);
                    });
            });
        };
        $scope.remove = function () {

        };
        $scope.update = function (name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/mp/app/merchant/catelog/update?id=' + $scope.editing.id, nv, function (rsp) {
            });
        };
        $scope.get();
    }]);
})();