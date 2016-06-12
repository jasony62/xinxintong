xxtApp.controller('ImportAddressbookModalInstCtrl', ['$scope', '$uibModalInstance', 'abid', function($scope, $mi, abid) {
    $scope.options = {};
    $scope.options.cleanExistent = 'N';
    $scope.ok = function() {
        $('#formImport').ajaxSubmit({
            url: '/rest/mp/app/addressbook/import?abid=' + abid + '&cleanExistent=' + $scope.options.cleanExistent,
            type: 'POST',
            success: function(rsp) {
                if (typeof rsp === 'string')
                    $scope.$root.errmsg = rsp;
                else
                    $scope.$root.infomsg = rsp.err_msg;
                $mi.close();
            }
        });
    };
    $scope.cancel = function() {
        $mi.dismiss();
    };
}]);
xxtApp.controller('abCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.back = function() {
        location.href = '/rest/mp/app/addressbook';
    };
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/addressbook/update?abid=' + $scope.editing.id, nv);
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.editing.pic = url + '?_=' + (new Date()) * 1
                $scope.update('pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.$watch('abid', function(nv) {
        http2.get('/rest/mp/app/addressbook/get?abid=' + nv, function(rsp) {
            $scope.editing = rsp.data;
            $scope.entryUrl = "http://" + location.host + "/rest/app/addressbook?mpid=" + $scope.editing.mpid + "&id=" + $scope.editing.id;
        });
    });
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
    });
}]);
xxtApp.controller('rollCtrl', ['$scope', '$uibModal', 'http2', function($scope, $uibModal, http2) {
    $scope.abbr = '';
    $scope.page = {
        at: 1,
        size: 30
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/app/addressbook/person?abid=' + $scope.abid + '&page=' + $scope.page.at + '&size=' + $scope.page.size + '&abbr=' + $scope.abbr;
        http2.get(url, function(rsp) {
            $scope.page.total = rsp.data.amount;
            $scope.persons = rsp.data.objects;
        });
    };
    $scope.create = function() {
        http2.get('/rest/mp/app/addressbook/personCreate?abid=' + $scope.abid, function(rsp) {
            location.href = '/page/mp/app/addressbook/person?abid=' + $scope.abid + '&id=' + rsp.data.id;
        });
    };
    $scope.edit = function(person) {
        location.href = '/page/mp/app/addressbook/person?id=' + person.id;
    };
    $scope.keypress = function(event) {
        if (event.keyCode == 13)
            $scope.doSearch();
    }
    $scope.showImport = function() {
        $uibModal.open({
            templateUrl: 'modalImportAddressbook.html',
            controller: 'ImportAddressbookModalInstCtrl',
            resolve: {
                abid: function() {
                    return $scope.editing.id;
                }
            }
        }).result.then(function() {
            $scope.doSearch();
        });
    };
    $scope.$watch('abid', function(nv) {
        $scope.doSearch();
    });
}]);
xxtApp.controller('deptCtrl', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
    $scope.addChild = function(node) {
        var url = '/rest/mp/app/addressbook/addDept?abid=' + $scope.abid;
        node.data && (url += '&pid=' + node.data.id);
        http2.get(url, function(rsp) {
            if (node.loaded) {
                node.children.push({
                    data: rsp.data,
                    children: []
                });
            } else
                $scope.toggleChild(node);
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
        http2.get('/rest/mp/app/addressbook/delDept?abid=' + $scope.abid + '&id=' + child.data.id, function(rsp) {
            walk($scope.tree);
        });
    };
    $scope.toggleChild = function(child) {
        var url;
        if (!child.loaded) {
            url = '/rest/mp/app/addressbook/dept?abid=' + $scope.abid;
            child.data && (url += '&pid=' + child.data.id);
            child.loaded = true;
            http2.get(url, function(rsp) {
                var depts = rsp.data;
                buildOrg(child.data, depts, child);
            });
        }
        child.expanded = !child.expanded;
    };
    var buildOrg = function(pdept, depts, treeNode) {
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
    $scope.open = function(node) {
        $scope.editingDept = node.data;
        $scope.editingNode = node;
    };
    $scope.updateDept = function(prop) {
        var nv = {};
        nv[prop] = $scope.editingDept[prop];
        http2.post('/rest/mp/app/addressbook/updateDept?abid=' + $scope.abid + '&id=' + $scope.editingDept.id, nv);
    };
    $scope.setDeptParent = function() {
        $uibModal.open({
            templateUrl: '/views/default/mp/app/addressbook/deptSelector.html?_=1',
            controller: 'deptSelectorCtrl',
            backdrop: 'static',
            windowClass: 'auto-height',
            size: 'lg',
            resolve: {
                abid: function() {
                    return $scope.abid;
                },
                onlyOne: function() {
                    return true;
                }
            }
        }).result.then(function(selected) {
            http2.get('/rest/mp/app/addressbook/setDeptParent?id=' + $scope.editingDept.id + '&pid=' + selected.id, function(rsp) {
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
    $scope.cleanDeptParent = function() {
        http2.get('/rest/mp/app/addressbook/setDeptParent?id=' + $scope.editingDept.id + '&pid=0', function(rsp) {
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
    $scope.tree = {
        children: []
    };
    $scope.$watch('abid', function(nv) {
        http2.get('/rest/mp/app/addressbook/dept?abid=' + $scope.abid, function(rsp) {
            var depts = rsp.data;
            buildOrg(null, depts, $scope.tree);
        });
    });
}]);