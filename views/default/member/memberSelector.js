(function() {
    xxtApp.register.controller('InnerMemberSelectorCtrl', ['$scope', 'http2', function($scope, http2) {
        var checkedDepts = [],
            checkedTags = [],
            checkedMembers = [];
        $scope.isDeptChecked = function(dept) {
            return checkedDepts.indexOf(dept) !== -1;
        };
        $scope.checkDepts = function(dept) {
            if (dept.checked && dept.checked === 'Y') {
                checkedDepts.push(dept);
                $scope.$emit('add.dept.member.selector', dept);
            } else {
                checkedDepts.splice(checkedDepts.indexOf(dept), 1);
                $scope.$emit('remove.dept.member.selector', dept);
            }
        };
        $scope.checkAllUser = function() {
            checkedMembers = [];
            $scope.members.forEach(function(m) {
                m.checked = 'Y';
                $scope.checkMembers(m);
            });
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
                http2.get('/rest/mp/user/department/list?authid=' + $scope.authid + '&pid=' + child.data.id, function(rsp) {
                    var depts = rsp.data;
                    buildDepts(child.data.id, depts, child);
                });
            }
            child.expanded = !child.expanded;
        };
        $scope.isTagChecked = function(tag) {
            return checkedDepts.indexOf(tag) !== -1;
        };
        $scope.checkTags = function(tag) {
            if (tag.checked && tag.checked === 'Y') {
                checkedTags.push(tag);
                $scope.$emit('add.tag.member.selector', tag);
            } else {
                checkedTags.splice(checkedTags.indexOf(tag), 1);
                $scope.$emit('remove.tag.member.selector', tag);
            }
            $scope.selectedTag = $scope.selectedTag === tag ? null : tag;
            $scope.isPickSingleMember === 'Y' && $scope.searchMember();
        };
        $scope.checkMembers = function(member) {
            if (member.checked && member.checked === 'Y') {
                checkedMembers.push(member);
                $scope.$emit('add.member.member.selector', member);
            } else {
                checkedMembers.splice(checkedMembers.indexOf(member), 1);
                $scope.$emit('remove.member.member.selector', member);
            }
        };
        $scope.searchTag = function() {
            http2.get('/rest/member/tags?authid=' + $scope.authid, function(rsp) {
                $scope.tags = rsp.data;
            });
        };
        $scope.searchMember = function() {
            var url, params = [];
            $scope.selectedDept && params.push('dept=' + $scope.selectedDept.id);
            $scope.selectedTag && params.push('tag=' + $scope.selectedTag.id);
            url = '/rest/member/members?authid=' + $scope.authid;
            url += '&page=' + $scope.page.at + '&size=' + $scope.page.size
            url += '&contain=total';
            params.length && (url += '&' + params.join('&'));
            http2.get(url, function(rsp) {
                $scope.members = rsp.data.members;
                rsp.data.total !== undefined && ($scope.page.total = rsp.data.total);
            });
        };
        $scope.selectDept = function(dept) {
            if ($scope.isPickSingleMember === 'Y') {
                $scope.selectedDept = $scope.selectedDept === dept ? null : dept;
                $scope.searchMember();
            } else {
                dept.checked = dept.checked === 'Y' ? 'N' : 'Y';
                $scope.checkDepts(dept);
            }
        };
        $scope.page = {
            at: 1,
            size: 30,
            keyword: ''
        };
        $scope.params = {};
        $scope.depts = {
            children: []
        };
        $scope.$watch('authid', function(authid) {
            if (authid) {
                http2.get('/rest/member/departments?authid=' + authid, function(rsp) {
                    var depts = rsp.data;
                    buildDepts(0, depts, $scope.depts, []);
                });
                $scope.searchTag();
                $scope.$emit('init.member.selector', {
                    showPickSingleMember: true
                });
            }
        });
        $scope.$watch('isPickSingleMember', function(nv) {
            if (nv && nv === 'N') {
                $scope.selectedDept = null;
                $scope.selectedTag = null;
            } else {
                $scope.searchMember();
            }
        });
    }]);
})();