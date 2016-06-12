xxtApp.controller('deptSelectorCtrl', ['$scope', 'http2', '$uibModalInstance', 'abid', 'onlyOne', function($scope, http2, $mi, abid, onlyOne) {
    var checkedDept = onlyOne ? null : [];
    $scope.checkDepts = function(dept) {
        if (onlyOne) {
            checkedDept && checkedDept !== dept && (checkedDept.checked = 'N');
            checkedDept = dept;
            checkedDept.checked = 'Y';
        } else {
            if (dept.checked === 'Y')
                checkedDept.push(dept);
            else
                checkedDept.splice(checkedDept.indexOf(dept), 1);
        }
    };
    var buildDepts = function(pid, depts, treeNode) {
        for (var i in depts) {
            var newNode = {
                data: depts[i],
                children: [],
            };
            treeNode.children.push(newNode);
        }
    };
    $scope.toggleChild = function(child) {
        if (!child.loaded) {
            child.loaded = true;
            http2.get('/rest/mp/app/addressbook/dept?abid=' + abid + '&pid=' + child.data.id, function(rsp) {
                var depts = rsp.data;
                buildDepts(child.data.id, depts, child);
            });
        }
        child.expanded = !child.expanded;
    };
    $scope.ok = function() {
        $mi.close(checkedDept);
    };
    $scope.close = function() {
        $mi.dismiss('cancel');
    };
    $scope.depts = {
        children: []
    };
    http2.get('/rest/mp/app/addressbook/dept?abid=' + abid, function(rsp) {
        var depts = rsp.data;
        buildDepts(0, depts, $scope.depts, []);
    });
}]);