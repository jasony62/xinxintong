var xxtMembers = angular.module('member.xxt', ['ui.bootstrap']);
xxtMembers.service('userSetAsParam', [function() {
    this.convert = function(userSet) {
        if (userSet.userScope === '') return [];
        var params = [],
            i, dept, tagIds = [],
            tagNames = [];
        switch (userSet.userScope) {
            case 'a':
                var newUs = {
                    identity: -1,
                    idsrc: 'G',
                    label: '所有关注用户'
                };
                params.push(newUs);
                break;
            case 'g':
                var group;
                for (i = 0; i < userSet.fansGroup.length; i++) {
                    group = userSet.fansGroup[i];
                    var newUs = {
                        identity: group.id,
                        idsrc: 'G',
                        label: group.name
                    };
                    params.push(newUs);
                }
                break;
            default:
                if (userSet.tags && userSet.tags.length) {
                    for (i = 0; i < userSet.tags.length; i++) {
                        tagIds.push(userSet.tags[i].id);
                        tagNames.push(userSet.tags[i].name);
                    }
                }
                if (userSet.depts && userSet.depts.length) {
                    for (i in userSet.depts) {
                        dept = userSet.depts[i];
                        var newUs = {
                            identity: dept.id + (tagIds.length > 0 ? ',' + tagIds.join(',') : ''),
                            idsrc: tagIds.length > 0 ? 'DT' : 'D',
                            label: dept.name + (tagNames.length > 0 ? ',' + tagNames.join(',') : '')
                        };
                        params.push(newUs);
                    }
                } else if (tagIds.length) {
                    var newUs = {
                        identity: tagIds.join(','),
                        idsrc: 'T',
                        label: tagNames.join(',')
                    };
                    params.push(newUs);
                } else if (userSet.members && userSet.members.length) {
                    var newUs, member;
                    for (var i in userSet.members) {
                        member = userSet.members[i];
                        newUs = {
                            identity: member.id,
                            idsrc: 'M',
                            label: member.name || member.nickname || member.email || member.mobile
                        };
                        params.push(newUs);
                    }
                }
        }
        return params;
    };
}]);
xxtMembers.controller('MemberAclUserPickerController', ['$scope', '$uibModalInstance', 'userSetAsParam', 'params', function($scope, $mi, userSetAsParam, params) {
    $scope.siteId = params.siteId;
    $scope.userConfig = {
        userScope: ['M']
    };
    $scope.userSet = {};
    $scope.cancel = function() {
        $mi.dismiss();
    };
    $scope.ok = function() {
        var data = {};
        data.userScope = $scope.userSet.userScope;
        data.userSet = userSetAsParam.convert($scope.userSet);
        $mi.close(data);
    };
}]);
xxtMembers.controller('MemberAclController', ['$rootScope', '$scope', 'http2', '$timeout', '$uibModal', function($rootScope, $scope, http2, $timeout, $uibModal) {
    var setObjMemberSchemas = function() {
        var aMemberSchemas, i;
        $scope.objMemberSchemas = angular.copy($scope.memberschemas);
        aMemberSchemas = $scope.obj[$scope.propMemberSchemas] ? $scope.obj[$scope.propMemberSchemas].trim() : '';
        aMemberSchemas = aMemberSchemas.length === 0 ? [] : aMemberSchemas.split(',');
        for (i in $scope.objMemberSchemas) {
            $scope.objMemberSchemas[i].checked = aMemberSchemas.indexOf($scope.objMemberSchemas[i].id) !== -1 ? 'Y' : 'N';
        }
    };
    $scope.setAccessControl = function() {
        $scope.updateAccessControl();
        if ($scope.memberschemas.length === 1) {
            $scope.obj[$scope.propMemberSchemas] = $scope.obj[$scope.propAccess] === 'Y' ? $scope.memberschemas[0].id : '';
            $scope.objMemberSchemas[0].checked = $scope.obj[$scope.propAccess] === 'Y' ? 'Y' : 'N';
            $scope.updateMemberSchemas();
        }
    };
    $scope.setMemberschema = function(api) {
        var eapis, p = {};
        eapis = $scope.obj[$scope.propMemberSchemas] ? $scope.obj[$scope.propMemberSchemas].trim() : '';
        eapis = eapis.length === 0 ? [] : eapis.split(',');
        api.checked === 'Y' ? eapis.push(api.id) : eapis.splice(eapis.indexOf(api.id), 1);
        p.memberschemas = eapis.join();
        $scope.obj[$scope.propMemberSchemas] = p.memberschemas;
        $scope.updateMemberSchemas();
        if (eapis.length === 0) {
            if ($scope.obj[$scope.propAccess] !== 'N') {
                $scope.obj[$scope.propAccess] = 'N';
                $scope.updateAccessControl();
            }
        } else {
            if ($scope.obj[$scope.propAccess] !== 'Y') {
                $scope.obj[$scope.propAccess] = 'Y';
                $scope.updateAccessControl();
            }
        }
    };
    $scope.addAcl = function() {
        var newAcl = {
            identity: '',
            idsrc: ''
        };
        $scope.obj[$scope.propAcl].push(newAcl);
        $timeout(function() {
            $('ul.acls li:last-child input').focus();
        }, 10);
    };
    $scope.openAclSelector = function() {
        $uibModal.open({
            templateUrl: '/views/default/pl/fe/_module/userpicker.html?_=' + (new Date()).getTime(),
            resolve: {
                params: function() {
                    return {
                        siteId: $scope.siteId
                    };
                },
            },
            controller: 'MemberAclUserPickerController',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function(data) {
            var i, newAcl, addAcl;
            addAcl = function(rsp) {
                $scope.obj[$scope.propAcl].push(rsp.data);
            };
            for (i in data.userSet) {
                newAcl = data.userSet[i];
                http2.post($scope.changeAclUrl, newAcl, addAcl);
            }
        });
    };
    $scope.clickAcl = function(acl, state, event) {
        if (acl.idsrc.length === 0) {
            state.editing = true;
            var i = $scope.obj[$scope.propAcl].indexOf(acl) + 1;
            $timeout(function() {
                $('ul.acls li:nth-child(' + i + ') input').focus();
            }, 10);
        }
    };
    $scope.changeAcl = function(newAcl, state) {
        http2.post($scope.changeAclUrl, newAcl, function(rsp) {
            if (newAcl.id === undefined)
                newAcl.id = rsp.data.id;
            if (newAcl.idsrc === '') newAcl.label = newAcl.identity;
            state.editing = false;
        });
    };
    $scope.removeAcl = function(acl, event) {
        event.preventDefault();
        event.stopPropagation();
        var i = $scope.obj[$scope.propAcl].indexOf(acl);
        if (acl.id === undefined)
            $scope.obj[$scope.propAcl].splice(i, 1);
        else {
            http2.get($scope.removeAclUrl + '&acl=' + acl.id, function(rsp) {
                $scope.obj[$scope.propAcl].splice(i, 1);
            });
        }
    };
    $scope.$watch('obj', function(obj) {
        if (obj && $scope.memberschemas) {
            setObjMemberSchemas();
        }
    });
    http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.siteId + '&valid=Y', function(rsp) {
        $scope.memberschemas = rsp.data;
        if ($scope.obj) {
            setObjMemberSchemas();
        }
    });
}]);
xxtMembers.directive('memberacl', function() {
    return {
        restrict: 'EA',
        scope: {
            title: '@',
            label: '@',
            siteId: '@',
            obj: '=',
            propAcl: '@',
            labelOfList: '@',
            propAccess: '@',
            propMemberSchemas: '@',
            changeAclUrl: '@',
            removeAclUrl: '@',
            updateAccessControl: '&',
            updateMemberSchemas: '&',
            labelSpan: '@',
            controlSpan: '@',
            disabled: '@',
            hideAccessControl: '@',
            hideMemberSchemas: '@'
        },
        controller: 'MemberAclController',
        templateUrl: '/views/default/pl/fe/_module/memberacl.html?_=' + (new Date()).getTime(),
    }
});
xxtMembers.controller('UserPickerController', ['http2', '$scope', function(http2, $scope) {
    var getPickedMemberSchema = function() {
        var i, id = $scope.userSet.userScope.split('_').pop();
        for (i in $scope.memberschemas) {
            if (id === $scope.memberschemas[i].id)
                return $scope.memberschemas[i];
        }
    };
    $scope.showPickSingleMember = false;
    $scope.isPickSingleMember = 'N';
    $scope.isPickMember = function() {
        return /ms_\d+/.test($scope.userSet.userScope);
    };
    $scope.canGroup = function() {
        return !$scope.userConfig || $scope.userConfig.userScope.indexOf('G') !== -1;
    };
    $scope.canMember = function() {
        return !$scope.userConfig || $scope.userConfig.userScope.indexOf('M') !== -1;
    };
    $scope.pickMp = function(mp) {
        !$scope.userSet.childmps && ($scope.userSet.childmps = []);
        if (mp.checked === 'Y')
            $scope.userSet.childmps.push(mp);
        else
            $scope.userSet.childmps.splice($scope.userSet.childmps.indexOf(mp), 1);
    };
    $scope.pickGroup = function(g) {
        !$scope.userSet.fansGroup && ($scope.userSet.fansGroup = []);
        if (g.checked === 'Y')
            $scope.userSet.fansGroup.push(g);
        else
            $scope.userSet.fansGroup.splice($scope.userSet.fansGroup.indexOf(g), 1);
    };
    $scope.$watch('userSet.userScope', function(nv) {
        if (nv && nv.length) {
            if (nv === 'mp') {
                http2.get('/rest/mp/mpaccount/childmps', function(rsp) {
                    $scope.childmps = rsp.data;
                });
            } else if (nv === 'g' && $scope.groups === undefined) {
                http2.get('/rest/mp/user/fans/group', function(rsp) {
                    $scope.groups = rsp.data;
                });
            } else if (/ms_\d+/.test(nv)) {
                $scope.memberschema = getPickedMemberSchema();
                http2.get($scope.memberschema.url + '/memberSelector?site=' + $scope.siteId + '&id=' + $scope.memberschema.id, function(rsp) {
                    $.getScript(rsp.data.js, function() {
                        $scope.memberViewUrl = rsp.data.view;
                        $scope.$apply('memberViewUrl');
                    });
                });
            }
        }
    });
    $scope.$on('init.member.selector', function(event, config) {
        if (config && config.showPickSingleMember !== undefined)
            $scope.showPickSingleMember = config.showPickSingleMember;
    });
    $scope.$on('add.dept.member.selector', function(event, dept) {
        !$scope.userSet.depts && ($scope.userSet.depts = []);
        $scope.userSet.depts.push(dept);
    });
    $scope.$on('remove.dept.member.selector', function(event, dept) {
        !$scope.userSet.depts && ($scope.userSet.depts = []);
        $scope.userSet.depts.splice($scope.userSet.depts.indexOf(dept), 1);
    });
    $scope.$on('add.tag.member.selector', function(event, tag) {
        !$scope.userSet.tags && ($scope.userSet.tags = []);
        $scope.userSet.tags.push(tag);
    });
    $scope.$on('remove.tag.member.selector', function(event, tag) {
        !$scope.userSet.tags && ($scope.userSet.tags = []);
        $scope.userSet.tags.splice($scope.userSet.tags.indexOf(tag), 1);
    });
    $scope.$on('add.member.member.selector', function(event, member) {
        !$scope.userSet.members && ($scope.userSet.members = []);
        $scope.userSet.members.push(member);
    });
    $scope.$on('remove.member.member.selector', function(event, member) {
        !$scope.userSet.members && ($scope.userSet.members = []);
        $scope.userSet.members.splice($scope.userSet.members.indexOf(member), 1);
    });
    if ($scope.canMember()) {
        http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.siteId, function(rsp) {
            $scope.memberschemas = rsp.data;
        });
    }
}]);
xxtMembers.directive('userpicker2', ['http2', function(http2) {
    return {
        restrict: 'EA',
        scope: {
            siteId: '@',
            userSet: '=',
            userConfig: '='
        },
        controller: 'UserPickerController',
        templateUrl: function() {
            return '/rest/pl/fe/site/member/schema/picker?site=';
        },
    };
}]);