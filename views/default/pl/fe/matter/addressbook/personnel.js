define(['frame'],function(ngApp){
    ngApp.provider.controller('ctrlPersonnel', ['$scope', 'http2', '$timeout', '$uibModal', '$location', function ($scope, http2, $timeout, $uibModal, $location) {
        var ls = $location.search();
        $scope.abid = ls.abid;

        var getPersonTags = function () {
            http2.get('/rest/pl/fe/matter/addressbook/tagGet?abid=' + $scope.id + '&site=' + $scope.siteId, function (rsp) {
                $scope.options.tags = rsp.data;
                var i, j, aTagIds, aTags = [];
                aTagIds = $scope.person.tags ? $scope.person.tags.split(',') : [];
                for (i in aTagIds) {
                    for (j in rsp.data) {
                        if (aTagIds[i] == rsp.data[j].id) {
                            aTags.push(rsp.data[j])
                            break;
                        }
                    }
                }
                $scope.person.tags2 = aTags;
            });
        };
        var getPerson = function () {
            http2.get('/rest/pl/fe/matter/addressbook/person?id=' + $scope.id + '&site=' + $scope.siteId, function (rsp) {
                $scope.person = rsp.data;
                if ($scope.person.tels && $scope.person.tels.length > 0) {
                    var tels = $scope.person.tels.split(',');
                    $scope.person.tels = [];
                    for (var i in tels) {
                        $scope.person.tels.push({i: i, v: tels[i]});
                    }
                } else {
                    $scope.person.tels = [];
                }
                $scope.persisted = angular.copy($scope.person);
                getPersonTags();
            });
        };
        var updateTels = function () {
            var tels = [], p = {};
            for (var i in $scope.person.tels) {
                tels.push($scope.person.tels[i].v);
            }
            tels = tels.join();
            p.tels = tels;
            http2.post('/rest/pl/fe/matter/addressbook/personUpdate?id=' + $scope.id+ '&site=' + $scope.siteId, p, function (rsp) {
                $scope.persisted = angular.copy($scope.person);
            });
        };
        $scope.options = {};
        $scope.back = function () {
            location.href = '/rest/pl/fe/matter/addressbook/roll?id=' + $scope.person.ab_id + '&site=' + $scope.siteId;
        };
        $scope.update = function (name) {
            if (!angular.equals($scope.person, $scope.persisted)) {
                var p = {};
                p[name] = $scope.person[name];
                http2.post('/rest/pl/fe/matter/addressbook/personUpdate?id=' + $scope.id + '&site=' + $scope.siteId, p, function (rsp) {
                    $scope.persisted = angular.copy($scope.person);
                });
            }
        };
        $scope.remove = function () {
            http2.get('/rest/pl/fe/matter/addressbook/personDelete?id=' + $scope.id + '&site='+ $scope.siteId, function () {
                /*location.href = '/rest/pl/fe/site/console?site=' + $scope.siteId;*/
                location.href = '/rest/pl/fe/matter/addressbook/roll?id=' + $scope.person.ab_id + '&site=' + $scope.siteId;
            });
        };
        $scope.addTel = function () {
            var newTel = {i: $scope.person.tels.length, v: ''};
            $scope.person.tels.push(newTel);
            $timeout(function () {
                $scope.$broadcast('xxt.editable.add', newTel);
            });
        };
        $scope.$on('xxt.editable.changed', function (e, newTel) {
            updateTels();
        });
        $scope.$on('xxt.editable.remove', function (e, tel) {
            var i = $scope.person.tels.indexOf(tel);
            $scope.person.tels.splice(i, 1);
            updateTels();
        });
        $scope.addDept = function () {
            $uibModal.open({
                templateUrl: '/views/default/pl/fe/matter/addressbook/deptSelector.html?_=1',
                controller: 'deptSelectorCtrl',
                windowClass: 'auto-height',
                backdrop: 'static',
                size: 'lg',
                resolve: {
                    abid: function () {
                        return $scope.abid;
                    },
                    onlyOne: function () {
                        return false;
                    }
                }
            }).result.then(function (selected) {
                    var deptids = [];
                    for (var i in selected)
                        deptids.push(selected[i].id);
                    http2.post('/rest/pl/fe/matter/addressbook/updPersonDept?abid=' + $scope.abid + '&id=' + $scope.id+ '&site=' + $scope.siteId, deptids, function (rsp) {
                        for (var j in rsp.data) {
                            for (var i in selected) {
                                if (rsp.data[j].dept_id = selected[i].id) {
                                    rsp.data[j].name = selected[i].name;
                                    break;
                                }
                            }
                            $scope.person.depts.push(rsp.data[j]);
                        }
                    });
                });
        };
        $scope.delDept = function (dept) {
            http2.get('/rest/pl/fe/matter/addressbook/delPersonDept?id=' + $scope.personId + '&deptid=' + dept.dept_id+ '&site=' + $scope.siteId, function (rsp) {
                var i = $scope.person.depts.indexOf(dept);
                $scope.person.depts.splice(i, 1);
            });
        };
        ngApp.provider.controller('deptSelectorCtrl', ['$scope', 'http2', '$uibModalInstance', 'abid', 'onlyOne', '$location', function($scope, http2, $mi, abid, onlyOne,$location) {
            var ls = $location.search();
            $scope.id = ls.id;
            $scope.abid = ls.abid;
            $scope.siteId = ls.site;


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
            http2.get('/rest/pl/fe/matter/addressbook/dept?abid=' + $scope.abid + '&site=' + $scope.siteId, function(rsp) {
                var depts = rsp.data;
                buildDepts(0, depts, $scope.depts, []);
            });
        }]);
        $scope.$watch('personId', function (id) {
            getPerson();
        });
        $scope.$on('tag.xxt.combox.add', function (event, newTag) {
            var oNewTag = {name: newTag};
            http2.post('/rest/pl/fe/matter/addressbook/personAddTag?id=' + $scope.person.id+ '&site=' + $scope.siteId, [oNewTag], function (rsp) {
                $scope.person.tags = rsp.data;
                getPersonTags();
            });
        });
        $scope.$on('tag.xxt.combox.done', function (event, aSelected) {
            var i, j, aNewTags = [], person = $scope.person, existing;
            for (i in aSelected) {
                existing = false;
                for (j in person.tags) {
                    if (aSelected[i].name === person.tags[j].name) {
                        existing = true;
                        break;
                    }
                }
                !existing && aNewTags.push(aSelected[i]);
            }
            http2.post('/rest/pl/fe/matter/addressbook/personAddTag?id=' + person.id+ '&site=' + $scope.siteId, aNewTags, function (rsp) {
                $scope.person.tags2 = person.tags2.concat(aNewTags);
                $scope.person.tags = rsp.data;
            });
        });
        $scope.$on('tag.xxt.combox.del', function (event, removed) {
            var person = $scope.person;
            http2.get('/rest/pl/fe/matter/addressbook/personDelTag?id=' + person.id + '&tagid=' + removed.id + '&site=' + $scope.siteId, function (rsp) {
                person.tags2.splice(person.tags2.indexOf(removed), 1);
                person.tags = rsp.data;
            });
        });
    }]);
});