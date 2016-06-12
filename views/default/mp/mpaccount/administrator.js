xxtApp.controller('adminCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
    $scope.admins = [];
    $scope.isAdmin = true;
    $scope.add = function() {
        var url = '/rest/mp/mpaccount/addAdmin';
        $scope.authedid && $scope.authedid.length > 0 && (url += '?authedid=' + $scope.authedid);
        http2.get(url, function(rsp) {
            if (rsp.data.externalOrg) {
                $.getScript(rsp.data.externalOrg, function() {
                    $uibModal.open(AddonParams).result.then(function(selected) {
                        var url2 = '/rest/mp/mpaccount/addAdmin',
                            url3, member;
                        for (var i in selected.members) {
                            member = selected.members[i];
                            url3 = '?authedid=' + member.authedid + '&autoreg=Y&authapp=' + selected.authapp;
                            http2.get(url2 + url3, function(rsp) {
                                $scope.admins.push(rsp.data);
                            });
                        }
                    });
                });
            } else {
                $scope.admins.push(rsp.data);
                $scope.authedid = '';
                $scope.select(rsp.data);
            }
        });
    };
    $scope.remove = function() {
        http2.get('/rest/mp/mpaccount/removeAdmin?uid=' + $scope.selectedAdmin.uid, function(rsp) {
            var index = $scope.admins.indexOf($scope.selectedAdmin);
            $scope.admins.splice(index, 1);
            $scope.selectedAdmin = false;
        });
    };
    $scope.select = function(admin) {
        $scope.selectedAdmin = admin;
    };
    $scope.$watch('jsonParams', function(nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
            $scope.admins = params.administrators;
        }
    });
}]);