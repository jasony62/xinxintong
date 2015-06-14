(function(){
    xxtApp.register.controller('CrccreMemberSelectorCtrl',['$rootScope','$scope','http2',function($rootScope,$scope,http2) {
        var checkedDepts = [], checkedMembers = [];
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
        $scope.checkNode = function(node) {
            var checkedNodes, evt, userSet;
            if (node.titletype == 5) {
                checkedNodes = checkedMembers;
                userSet = {authed_identity:node.useraccount, name:node.title};
                evt = 'member.member.selector';
            } else {
                checkedNodes = checkedDepts;
                userSet = {id:node.guid, name:node.title};
                evt = 'dept.member.selector';
            }
            if (node.checked && node.checked === 'Y') {
                checkedNodes.push(node);
                $scope.$emit('add.'+evt, userSet);
            } else { 
                checkedNodes.splice(checkedDepts.indexOf(node), 1);
                $scope.$emit('remove.'+evt, userSet);
            }
        };
        $scope.nodes = {children:[]};
        http2.get('/rest/cus/crccre/org/nodes', function(rsp){
            buildNodes(0, rsp.data, $scope.nodes, []);
        })
    }]);
})();
