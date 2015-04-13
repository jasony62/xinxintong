var AddonParams;
(function(){
    xxtApp.register.controller('ExternalOrgCtrl',['$scope','http2','$modalInstance',function($scope,http2,$modalInstance) {
        var checkedMembers = [];
        var buildNodes = function(pid, nodes, treeNode) {
            for (var i in nodes) {
                var newNode = {
                    data: nodes[i],
                    children: [],
                };
                treeNode.children.push(newNode);
            }
        };
        $scope.toggleChild = function(child) {
            if (!child.loaded) {
                child.loaded = true;
                http2.get('/rest/cus/crccre/org/nodes?pid='+child.data.guid, function(rsp){
                    buildNodes(child.data.id, rsp.data, child);
                });
            }
            child.expanded = !child.expanded;
        };
        $scope.checkMembers = function(member) {
            if (member.checked && member.checked === 'Y')
                checkedMembers.push(member);
            else 
                checkedMembers.splice(checkedMembers.indexOf(member), 1);
        };
        $scope.ok = function () {
            var selected = [];
            for (var i in checkedMembers)
                selected.push({authedid:checkedMembers[i].useraccount,extattr:checkedMembers[i]});
            $modalInstance.close({authapp:'crccre', members:selected});
        };
        $scope.close = function () {
            $modalInstance.dismiss('cancel');
        };
        $scope.nodes = {children:[]};
        http2.get('/rest/cus/crccre/org/nodes', function(rsp){
            buildNodes(0, rsp.data, $scope.nodes, []);
        });
    }]);
    AddonParams = {
        templateUrl:'/rest/cus/crccre/org',
        controller:'ExternalOrgCtrl',
        backdrop:'static',
        windowClass:'auto-height',
        size:'lg'
    };
})();
