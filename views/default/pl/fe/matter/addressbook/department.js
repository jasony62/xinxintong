define(['frame'], function (ngApp) {
    ngApp.provider.controller('ctrlDepartment', ['$scope', '$location','http2', '$uibModal', function ($scope, $location, http2, $uibModal) {
        /*var ls = $location.search();
        $scope.id = ls.id;
        $scope.siteId = ls.site;
        $scope.modified = false;*/

        $scope.addChild = function (node) {
            var url = '/rest/pl/fe/matter/addressbook/addDept?abid=' + $scope.id + '&site=' + $scope.siteId;
            node.data && (url += '&pid=' + node.data.id);
            http2.get(url, function (rsp) {
                if (node.loaded) {
                    node.children.push({
                        data: rsp.data,
                        children: []
                    });
                } else
                    $scope.toggleChild(node);
            });
        };
        $scope.removeChild = function (child) {
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

            http2.get('/rest/pl/fe/matter/addressbook/delDept?abid=' + $scope.id + '&site=' + $scope.siteId + '&id=' + child.data.id , function (rsp) {
                walk($scope.tree);
            });
        };
        $scope.toggleChild = function (child) {
            var url;
            if (!child.loaded) {
                url = '/rest/pl/fe/matter/addressbook/dept?abid=' + $scope.id + '&site=' + $scope.siteId;
                child.data && (url += '&pid=' + child.data.id);
                child.loaded = true;
                http2.get(url, function (rsp) {
                    var depts = rsp.data;
                    buildOrg(child.data, depts, child);
                });
            }
            child.expanded = !child.expanded;
        };
        var buildOrg = function (pdept, depts, treeNode) {
            var i, dept;
            for (i = 0; dept = depts[i]; i++) {
                dept.pdept = pdept;
                var newNode = {
                    data: dept,
                    children: []
                };
                treeNode.children.push(newNode);
            }
        };
        $scope.open = function (node) {
            $scope.editingDept = node.data;
            $scope.editingNode = node;
        };
        $scope.updateDept = function (prop) {
            var nv = {};
            nv[prop] = $scope.editingDept[prop];
            http2.post('/rest/pl/fe/matter/addressbook/updateDept?abid=' + $scope.id + '&site=' + $scope.siteId + '&id=' + $scope.editingDept.id, nv);
        };
        $scope.setDeptParent = function () {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/addressbook/deptSelector.html?_=1',
                controller: 'deptSelectorCtrl',
                backdrop: 'static',
                windowClass: 'auto-height',
                size: 'lg',
                resolve: {
                    abid: function () {
                        return $scope.abid;
                    },
                    onlyOne: function () {
                        return true;
                    }
                }
            }).result.then(function (selected) {
                    http2.get('/rest/pl/fe/matter/addressbook/setDeptParent?id=' + $scope.editingDept.id + '&site=' + $scope.siteId + '&pid=' + selected.id, function (rsp) {
                        $scope.editingDept.pdept = selected;
                        (function walk(target) {
                            var children = target.children,
                                i;
                            if (children) {
                                i = children.length;
                                while (i--) {
                                    if (children[i] === $scope.editingNode)
                                        return children.splice(i, 1);
                                    else
                                        walk(children[i]);
                                }
                            }
                        })($scope.tree);
                        (function walk(target) {
                            var children = target.children,
                                i;
                            if (children) {
                                i = children.length;
                                while (i--) {
                                    if (children[i].data.id === selected.id) {
                                        children[i].loaded && children[i].children.push($scope.editingNode);
                                        return;
                                    } else
                                        walk(children[i]);
                                }
                            }
                        })($scope.tree);
                    });
                });
        };
        $scope.cleanDeptParent = function () {
            http2.get('/rest/pl/fe/matter/addressbook/setDeptParent?id=' + $scope.editingDept.id + '&pid=0'+ '&site=' + $scope.siteId, function (rsp) {
                $scope.editingDept.pdept = null;
                (function walk(target) {
                    var children = target.children,
                        i;
                    if (children) {
                        i = children.length;
                        while (i--) {
                            if (children[i] === $scope.editingNode)
                                return children.splice(i, 1);
                            else
                                walk(children[i]);
                        }
                    }
                })($scope.tree);
                $scope.tree.children.push($scope.editingNode);
            });
        };
        ngApp.provider.controller('deptSelectorCtrl', ['$scope', 'http2', '$uibModalInstance', 'abid', 'onlyOne', '$location', function($scope, http2, $mi, abid, onlyOne,$location) {
            var ls = $location.search();
            $scope.id = ls.id;
            $scope.siteId = ls.site;
            $scope.modified = false;

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
                    http2.get('/rest/pl/fe/matter/addressbook/dept?abid=' + $scope.id + '&pid=' + child.data.id + '&site=' + $scope.siteId, function(rsp) {
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
            http2.get('/rest/pl/fe/matter/addressbook/dept?abid=' + $scope.id + '&site=' + $scope.siteId, function(rsp) {
                var depts = rsp.data;
                buildDepts(0, depts, $scope.depts, []);
            });
        }]);
        $scope.tree = {
            children: []
        };
        $scope.$watch('abid', function (id) {
            http2.get('/rest/pl/fe/matter/addressbook/dept?abid=' + $scope.id + '&site=' + $scope.siteId, function (rsp) {
                var depts = rsp.data;
                buildOrg(null, depts, $scope.tree);
            });
        });
    }]);
});
