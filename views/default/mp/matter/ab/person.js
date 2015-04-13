xxtApp.controller('personCtrl',['$scope','http2','$timeout','$modal',function($scope,http2,$timeout,$modal){
    var getPerson = function() { 
        http2.get('/rest/mp/matter/addressbook/person?id='+$scope.personId, function(rsp) {
            $scope.person = rsp.data;
            if ($scope.person.tels && $scope.person.tels.length>0) {
                var tels = $scope.person.tels.split(',');
                $scope.person.tels = [];
                for (var i in tels) {
                    $scope.person.tels.push({i:i,v:tels[i]});
                }
            } else {
                $scope.person.tels = [];
            }
            $scope.persisted = angular.copy($scope.person);
        });
    };
    var updateTels = function() {
        var tels=[],p={};
        for (var i in $scope.person.tels) {
            tels.push($scope.person.tels[i].v);
        }
        tels = tels.join();
        p.tels = tels;
        http2.post('/rest/mp/matter/addressbook/personUpdate?id='+$scope.personId, p, function(rsp){
            $scope.persisted = angular.copy($scope.person);
        });
    };
    $scope.back = function() {
        location.href = '/page/mp/matter/ab?id='+$scope.person.ab_id;
    };
    $scope.update = function(name) {
        if (!angular.equals($scope.person, $scope.persisted)) {
            var p = {};
            p[name] = $scope.person[name];
            http2.post('/rest/mp/matter/addressbook/personUpdate?id='+$scope.personId,p,function(rsp) {
                $scope.persisted = angular.copy($scope.person);
            });
        }
    };
    $scope.remove = function() {
        http2.get('/rest/mp/matter/addressbook/personDelete?id='+$scope.personId, function(rsp) {
            location.href = '/page/mp/matter/addressbook'; 
        });
    };
    $scope.addTel = function() {
        var newTel = {i:$scope.person.tels.length,v:''};
        $scope.person.tels.push(newTel);
        $timeout(function(){$scope.$broadcast('xxt.editable.add', newTel);});
    };
    $scope.$on('xxt.editable.changed', function(e, newTel){
        updateTels();
    });
    $scope.$on('xxt.editable.remove', function(e, tel){
        var i = $scope.person.tels.indexOf(tel);
        $scope.person.tels.splice(i,1);
        updateTels();
    });
    $scope.addDept = function() {
        $modal.open({
            templateUrl:'/views/default/mp/matter/ab/deptSelector.html?_=1',
            controller:'deptSelectorCtrl',
            windowClass:'auto-height',
            backdrop:'static',
            size:'lg',
            resolve:{
                abid:function(){return $scope.person.ab_id;},
                onlyOne:function(){return false;}
            }
        }).result.then(function(selected){
            var deptids = [];
            for (var i in selected)
                deptids.push(selected[i].id);
            http2.post('/rest/mp/matter/addressbook/updPersonDept?abid='+$scope.person.ab_id+'&id='+$scope.personId, deptids, function(rsp) {
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
    $scope.delDept = function(dept) {
        http2.get('/rest/mp/matter/addressbook/delPersonDept?id='+$scope.personId+'&deptid='+dept.dept_id, function(rsp) {
            var i = $scope.person.depts.indexOf(dept);
            $scope.person.depts.splice(i, 1);
        });
    };
    $scope.$watch('personId',function(nv){
        getPerson();
    });
}]);
