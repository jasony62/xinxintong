xxtApp.controller('userCtrl', ['$location', '$scope', 'http2', '$uibModal', function($location, $scope, http2, $uibModal) {
    var openid;
    openid = $location.search().openid;
    $scope.SexMap = {
        '0': '未知',
        '1': '男',
        '2': '女',
        '3': '无效值'
    };
    $scope.trackpage = {
        at: 1,
        size: 30
    };
    $scope.matterType = 'write';
    $scope.matterpage = {
        at: 1,
        size: 30
    };
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    $scope.update = function(name) {
        var nv = {};
        nv[name] = $scope.user[name];
        http2.post('/rest/mp/user/fans/update?openid=' + $scope.user.openid, nv);
    };
    $scope.userTrack = function() {
        var url = '/rest/mp/user/fans/track?openid=' + $scope.user.openid;
        url += '&page=' + $scope.trackpage.at + '&size=' + $scope.trackpage.size;
        http2.get(url, function(rsp) {
            $scope.track = rsp.data;
        });
    };
    $scope.selectMatter = function(matter) {
        $scope.selectedMatter = matter;
    };
    $scope.fetchMatter = function(page) {
        if ($scope.matterType === 'write') {
            $scope.matters = null;
            return;
        }
        $scope.selectedMatter = null;
        var url, params = {};
        url = '/rest/mp/matter/' + $scope.matterType + '/list';
        !page && (page = $scope.matterpage.at);
        url += '?page=' + page + '&size=' + $scope.matterpage.size;
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            if (/article/.test($scope.matterType)) {
                $scope.matters = rsp.data.articles;
                $scope.matterpage.total = rsp.data.total;
            } else {
                $scope.matters = rsp.data;
            }
        });
    };
    $scope.send = function() {
        var data;
        if ($scope.matterType === 'write') {
            data = {
                text: $scope.text
            };
        } else {
            data = {
                id: $scope.selectedMatter.id,
                type: $scope.matterType,
                title: $scope.selectedMatter.title || $scope.selectedMatter.content
            };
        }
        http2.post('/rest/mp/send/custom?openid=' + $scope.user.openid, data, function(rsp) {
            $scope.userTrack();
        });
    };
    $scope.remove = function() {
        http2.get('/rest/mp/user/fans/removeOne?fid=' + $scope.user.fid, function(rsp) {
            location.href = '/page/mp/user/received';
        });
    };
    $scope.refresh = function() {
        http2.get('/rest/mp/user/fans/refreshOne?openid=' + $scope.user.openid, function(rsp) {
            $scope.user.sex = rsp.data.sex;
            $scope.user.nickname = rsp.data.nickname;
            rsp.data.subscribe_at !== undefined && ($scope.user.subscribe_at = rsp.data.subscribe_at);
            rsp.data.icon !== undefined && ($scope.user.headimgurl = rsp.data.icon);
            $scope.$root.infomsg = '完成刷新';
        });
    };
    $scope.$on('tag.xxt.combox.done', function(event, aSelected, index) {
        var aNewTags = [],
            aTagIds = [],
            member = $scope.user.members[index];
        for (var i in aSelected) {
            var existing = false;
            for (var j in member.tags) {
                if (aSelected[i].name === member.tags[j].name) {
                    existing = true;
                    break;
                }
            }!existing && aNewTags.push(aSelected[i]);
        }
        for (var i in aNewTags)
            aTagIds.push(aNewTags[i].id);
        http2.post('/rest/mp/user/member/addTags?mid=' + member.mid, aTagIds, function(rsp) {
            member.tags2 = member.tags2.concat(aNewTags);
            member.tags = rsp.data;
        });
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed, index) {
        var member = $scope.user.members[index];
        http2.get('/rest/mp/user/member/delTags?mid=' + member.mid + '&tagid=' + removed.id, function(rsp) {
            member.tags2.splice(member.tags2.indexOf(removed), 1);
            member.tags = rsp.data;
        });
    });
    $scope.selectDept = function(member) {
        $uibModal.open({
            templateUrl: 'deptSelector.html',
            controller: 'deptSelectorCtrl',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height',
            resolve: {
                member: function() {
                    return member;
                },
                depts: function() {
                    return angular.copy($scope.depts);
                }
            }
        }).result.then(function(checkedDepts) {
            member.depts = JSON.stringify(checkedDepts);
            http2.post('/rest/mp/user/member/updateDepts?mid=' + member.mid, {
                'depts': member.depts
            }, function(rsp) {
                member.depts2 = rsp.data;
            });
        });
    };
    $scope.canFieldShow = function(authapi, name) {
        return authapi['attr_' + name].charAt(0) === '0';
    };
    $scope.addMember = function(authapi) {
        $uibModal.open({
            templateUrl: 'memberEditor.html',
            backdrop: 'static',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.authapi = authapi;
                $scope.member = {
                    extattr: {}
                };
                $scope.canShow = function(name) {
                    return authapi['attr_' + name].charAt(0) === '0';
                };
                $scope.close = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.member);
                };
            }]
        }).result.then(function(member) {
            http2.post('/rest/mp/user/member/create?fid=' + $scope.user.fid + '&authid=' + authapi.authid, member, function(rsp) {
                member = rsp.data;
                member.extattr = JSON.parse(decodeURIComponent(member.extattr.replace(/\+/g, '%20')));
                member.authapi = authapi;
                !$scope.user.members && ($scope.user.members = []);
                $scope.user.members.push(member);
            });
        });
    };
    $scope.editMember = function(member) {
        $uibModal.open({
            templateUrl: 'memberEditor.html',
            backdrop: 'static',
            controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                $scope.authapi = member.authapi;
                $scope.member = angular.copy(member);
                $scope.canShow = function(name) {
                    return $scope.authapi && $scope.authapi['attr_' + name].charAt(0) === '0';
                };
                $scope.close = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close({
                        action: 'update',
                        data: $scope.member
                    });
                };
                $scope.remove = function() {
                    $mi.close({
                        action: 'remove'
                    });
                };
            }]
        }).result.then(function(rst) {
            if (rst.action === 'update') {
                var newData, i, ea;
                newData = {
                    verified: rst.data.verified,
                    name: rst.data.name,
                    mobile: rst.data.mobile,
                    email: rst.data.email,
                    email_verified: rst.data.email_verified,
                    extattr: rst.data.extattr
                };
                for (i in member.authapi.extattr) {
                    ea = member.authapi.extattr[i];
                    newData[ea.id] = rst.data[ea.id];
                }
                http2.post('/rest/mp/user/member/update?mid=' + member.mid, newData, function(rsp) {
                    angular.extend(member, newData);
                });
            } else if (rst.action === 'remove') {
                http2.get('/rest/mp/user/member/remove?mid=' + member.mid, function() {
                    $scope.user.members.splice($scope.user.members.indexOf(member), 1);
                });
            }
        });
    };
    http2.get('/rest/mp/mpaccount/get', function(rsp) {
        $scope.mpaccount = rsp.data;
        $scope.hasParent = $scope.mpaccount.parent_mpid && $scope.mpaccount.parent_mpid.length;
    });
    http2.get('/rest/mp/user/get?openid=' + openid, function(rsp) {
        var i, j;
        $scope.user = rsp.data.fan;
        $scope.groups = rsp.data.groups;
        $scope.authapis = rsp.data.authapis;
        $scope.availableAuthapis = angular.copy(rsp.data.authapis);
        //
        if ($scope.user.members) {
            for (i in $scope.user.members) {
                var member, aTagIds, aTags = [];
                member = $scope.user.members[i];
                member.extattr ? member.extattr = JSON.parse(member.extattr) : {};
                aTagIds = member.tags ? member.tags.split(',') : [];
                for (var i in aTagIds) {
                    for (var j in member.authapi.tags) {
                        if (aTagIds[i] == member.authapi.tags[j].id) {
                            aTags.push(member.authapi.tags[j])
                            break;
                        }
                    }
                }
                member.tags2 = aTags;
                for (j = 0; j < $scope.availableAuthapis.length; j++) {
                    if (member.authapi.authid === $scope.availableAuthapis[j].authid) {
                        $scope.availableAuthapis.splice(j, 1);
                        break;
                    }
                }
            }
        }
        $scope.userTrack();
    });
}]).controller('deptSelectorCtrl', ['$uibModalInstance', 'http2', '$scope', 'member', function($mi, http2, $scope, member) {
    var checkedDepts;
    if (member.depts && member.depts.length)
        checkedDepts = JSON.parse(member.depts);
    else
        checkedDepts = [];
    $scope.depts = {
        children: []
    };
    $scope.isChecked = function(dept) {
        var i, checked;
        for (i in checkedDepts) {
            checked = checkedDepts[i].indexOf(dept.id) === checkedDepts[i].length - 1;
            if (checked) return true;
        }
        return false;
    };
    var buildDepts = function(pid, depts, treeNode, path) {
        var i, dept, newNode;
        for (i in depts) {
            dept = depts[i];
            dept.path = path; // parent path.
            dept.indexAtParent = i;
            newNode = {
                data: dept,
                children: [],
            };
            treeNode.children.push(newNode);
        }
    };
    $scope.close = function() {
        $mi.dismiss('cancel');
    };
    $scope.ok = function() {
        $mi.close(checkedDepts);
    };
    $scope.toggleChild = function(child) {
        if (!child.loaded) {
            child.loaded = true;
            http2.get('/rest/mp/user/department/list?authid=' + member.authapi_id + '&pid=' + child.data.id, function(rsp) {
                var depts = rsp.data;
                buildDepts(child.data.id, depts, child);
            });
        }
        child.expanded = !child.expanded;
    };
    $scope.updateDepts = function(dept) {
        if (dept.checked && dept.checked === 'Y') {
            var path;
            path = dept.fullpath.split(',');
            checkedDepts.push(path);
        } else {
            for (var i in checkedDepts) {
                if (checkedDepts[i].indexOf(dept.id) === checkedDepts[i].length - 1) {
                    checkedDepts.splice(i, 1);
                    break;
                }
            }
        }
    };
    buildDepts(0, member.authapi.depts, $scope.depts, []);
}]).filter('joinobj', function() {
    return function(objs, prop) {
        var output = [];
        for (i in objs)
            output.push(objs[i][prop]);
        return output.join(',');
    };
});