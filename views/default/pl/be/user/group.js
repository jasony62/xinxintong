define(['main'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlGroup', ['$scope', 'http2', function($scope, http2) {
        $scope.add = function() {
            http2.post('/rest/pl/be/user/group/add', {
                'name': $scope.groupName
            }, function(rsp) {
                $scope.groups.push(rsp.data);
                $scope.groupName = '';
            });
        };
        $scope.update = function(group, pname) {
            var data = {};
            data[pname] = group[pname];
            http2.post('/rest/pl/be/user/group/update?gid=' + group.group_id, data).then(function(rsp) {});
        };
        $scope.remove = function(group) {
            http2.get('/rest/pl/be/user/group/remove?gid=' + group.group_id).then(function(rsp) {
                var i = $scope.groups.indexOf(group);
                $scope.groups.splice(i, 1);
            });
        };
        http2.get('/rest/pl/be/user/group/list').then(function(rsp) {
            var i, group;
            $scope.groups = rsp.data;
            for (i in $scope.groups) {
                group = $scope.groups[i];
                if (group.asdefault == 1) {
                    $scope.defaultGroup = group;
                    break;
                }
            }
        });
    }]);
});