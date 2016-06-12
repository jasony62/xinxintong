xxtApp.controller('personCtrl', ['$scope', 'http2', '$timeout', '$uibModal', function ($scope, http2, $timeout, $uibModal) {
    var getPersonTags = function () {
        http2.get('/rest/mp/app/addressbook/tagGet?abid=' + $scope.person.ab_id, function (rsp) {
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
        http2.get('/rest/mp/app/addressbook/person?id=' + $scope.personId, function (rsp) {
            $scope.person = rsp.data;
            if ($scope.person.tels && $scope.person.tels.length > 0) {
                var tels = $scope.person.tels.split(',');
                $scope.person.tels = [];
                for (var i in tels) {
                    $scope.person.tels.push({ i: i, v: tels[i] });
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
        http2.post('/rest/mp/app/addressbook/personUpdate?id=' + $scope.personId, p, function (rsp) {
            $scope.persisted = angular.copy($scope.person);
        });
    };
    $scope.options = {};
    $scope.back = function () {
        location.href = '/page/mp/app/addressbook/edit?id=' + $scope.person.ab_id;
    };
    $scope.update = function (name) {
        if (!angular.equals($scope.person, $scope.persisted)) {
            var p = {};
            p[name] = $scope.person[name];
            http2.post('/rest/mp/app/addressbook/personUpdate?id=' + $scope.personId, p, function (rsp) {
                $scope.persisted = angular.copy($scope.person);
            });
        }
    };
    $scope.remove = function () {
        http2.get('/rest/mp/app/addressbook/personDelete?id=' + $scope.personId, function (rsp) {
            location.href = '/rest/mp/app/addressbook';
        });
    };
    $scope.addTel = function () {
        var newTel = { i: $scope.person.tels.length, v: '' };
        $scope.person.tels.push(newTel);
        $timeout(function () { $scope.$broadcast('xxt.editable.add', newTel); });
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
            templateUrl: '/views/default/mp/app/addressbook/deptSelector.html?_=1',
            controller: 'deptSelectorCtrl',
            windowClass: 'auto-height',
            backdrop: 'static',
            size: 'lg',
            resolve: {
                abid: function () { return $scope.person.ab_id; },
                onlyOne: function () { return false; }
            }
        }).result.then(function (selected) {
            var deptids = [];
            for (var i in selected)
                deptids.push(selected[i].id);
            http2.post('/rest/mp/app/addressbook/updPersonDept?abid=' + $scope.person.ab_id + '&id=' + $scope.personId, deptids, function (rsp) {
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
        http2.get('/rest/mp/app/addressbook/delPersonDept?id=' + $scope.personId + '&deptid=' + dept.dept_id, function (rsp) {
            var i = $scope.person.depts.indexOf(dept);
            $scope.person.depts.splice(i, 1);
        });
    };
    $scope.$watch('personId', function (nv) {
        getPerson();
    });
    $scope.$on('tag.xxt.combox.add', function (event, newTag) {
        var oNewTag = { name: newTag };
        http2.post('/rest/mp/app/addressbook/personAddTag?id=' + $scope.person.id, [oNewTag], function (rsp) {
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
        http2.post('/rest/mp/app/addressbook/personAddTag?id=' + person.id, aNewTags, function (rsp) {
            $scope.person.tags2 = person.tags2.concat(aNewTags);
            $scope.person.tags = rsp.data;
        });
    });
    $scope.$on('tag.xxt.combox.del', function (event, removed) {
        var person = $scope.person;
        http2.get('/rest/mp/app/addressbook/personDelTag?id=' + person.id + '&tagid=' + removed.id, function (rsp) {
            person.tags2.splice(person.tags2.indexOf(removed), 1);
            person.tags = rsp.data;
        });
    });
}]);