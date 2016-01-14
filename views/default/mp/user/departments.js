xxtApp.controller('deptCtrl', ['$scope', 'http2', function($scope, http2) {
    var buildDepts = function(pid, depts, treeNode) {
        for (var i in depts) {
            var newNode = {
                data: depts[i],
                children: []
            };
            treeNode.children.push(newNode);
        }
    };
    $scope.depts = {
        children: []
    };
    $scope.page = {
        at: 1,
        size: 30
    };
    $scope.doSearch = function() {
        $scope.depts.children = [];
        var url = '/rest/mp/user/department/list';
        url += '?authid=' + $scope.selectedAuthapi.authid;
        http2.get(url, function(rsp) {
            buildDepts(0, rsp.data, $scope.depts);
        });
    };
    $scope.searchMembers = function() {
        var url;
        url = '/rest/mp/user/member/list?authid=' + $scope.selectedAuthapi.authid;
        url += '&dept=' + $scope.editing.id;
        url += '&page=' + $scope.page.at + '&size=' + $scope.page.size
        url += '&contain=total';
        http2.get(url, function(rsp) {
            $scope.members = rsp.data.members;
            $scope.page.total = rsp.data.total;
        });
    };
    $scope.addChild = function(node) {
        var url = '/rest/mp/user/department/add';
        url += '?authid=' + $scope.selectedAuthapi.authid;
        node.data && (url += '&pid=' + node.data.id);
        http2.get(url, function(rsp) {
            node.children.push({
                data: rsp.data,
                children: []
            });
        });
    };
    $scope.removeChild = function(child) {
        function walk(target) {
            var children = target.children,
                i;
            if (children) {
                i = children.length;
                while (i--) {
                    if (children[i] === child)
                        return children.splice(i, 1);
                    else
                        walk(children[i]);
                }
            }
        }
        http2.get('/rest/mp/user/department/remove?id=' + child.data.id, function(rsp) {
            walk($scope.depts);
        });
    };
    $scope.toggleChild = function(child) {
        if (!child.loaded) {
            child.loaded = true;
            var url = '/rest/mp/user/department/list';
            url += '?authid=' + $scope.selectedAuthapi.authid + '&pid=' + child.data.id;
            http2.get(url, function(rsp) {
                buildDepts(child.data.id, rsp.data, child);
            });
        }
        child.expanded = !child.expanded;
    };
    $scope.open = function(dept) {
        if ($scope.editing !== dept) {
            $scope.editing = dept;
            $scope.searchMembers();
        }
    };
    $scope.updateDept = function(prop) {
        var nv = {};
        nv[prop] = $scope.editing[prop];
        http2.post('/rest/mp/user/department/update?id=' + $scope.editing.id, nv);
    };
    $scope.viewUser = function(event, fan) {
        location.href = '/rest/mp/user?openid=' + fan.openid;
    };
    http2.get('/rest/mp/authapi/get?valid=Y', function(rsp) {
        $scope.authapis = rsp.data;
        $scope.selectedAuthapi = $scope.authapis[0];
        $scope.doSearch();
    });
}]);