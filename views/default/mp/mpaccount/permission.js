xxtApp.controller('permCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
    $scope.users = [];
    $scope.addUser = function() {
        var url = '/rest/mp/permission/addUser';
        $scope.authedid && $scope.authedid.length > 0 && (url += '?authedid=' + $scope.authedid);
        http2.get(url, function(rsp) {
            if (rsp.data.externalOrg) {
                $.getScript(rsp.data.externalOrg, function() {
                    $uibModal.open(AddonParams).result.then(function(selected) {
                        var url2 = '/rest/mp/permission/addUser',
                            url3, member;
                        for (var i in selected.members) {
                            member = selected.members[i];
                            url3 = '?authedid=' + member.authedid + '&autoreg=Y&authapp=' + selected.authapp;
                            http2.get(url2 + url3, function(rsp) {
                                $scope.users.push(rsp.data);
                            });
                        }
                    });
                });
            } else {
                $scope.users.push(rsp.data);
                $scope.authedid = '';
                $scope.selected(rsp.data);
            }
        });
    };
    $scope.removeUser = function() {
        http2.get('/rest/mp/permission/removeUser?uid=' + $scope.selectedUser.uid, function(rsp) {
            var index = $scope.users.indexOf($scope.selectedUser);
            $scope.users.splice(index, 1);
            $scope.rights = {};
            $scope.selectedUser = false;
        });
    };
    $scope.selected = function(event, user) {
        event.preventDefault();
        event.stopPropagation();
        $scope.selectedUser && (delete $scope.selectedUser.selected);
        $scope.selectedUser = user;
        $scope.selectedUser.selected = true;
        http2.get('/rest/mp/permission/getRight?uid=' + user.uid, function(rsp) {
            $scope.rights = rsp.data;
        });
    };
    $scope.batchCheck = function(perm, bCheck) {
        var data = {};
        for (var r in $scope.rights[perm]) {
            $scope.rights[perm][r] = bCheck;
            if ($('input[ng-model="rights.' + perm + '.' + r + '"]').length === 1)
                data[r] = bCheck;
        }
        http2.post('/rest/mp/permission/updatePerm?uid=' + $scope.selectedUser.uid + '&perm=' + perm, data, function(rsp) {});
    };
    $scope.update = function(perm, crud) {
        var nv = {};
        nv[crud] = $scope.rights[perm][crud];
        http2.post('/rest/mp/permission/updatePerm?uid=' + $scope.selectedUser.uid + '&perm=' + perm, nv, function(rsp) {});
    };
    http2.get('/rest/mp/permission/user', function(rsp) {
        $scope.users = rsp.data;
    });
}]);