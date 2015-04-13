angular.module('activity.xxt',['tmseditor','ui.tms'])
.config(['$locationProvider',function($locationProvider){
    $locationProvider.html5Mode(true);
}])
.controller('ActCtrl',['$scope','$http','$location',function($scope,$http,$location) {
    $scope.aid = $location.search().aid; 
    $http.get('/rest/wbox/activity?aid='+$scope.aid)
    .success(function(rsp){
        $scope.activity = rsp.data;
        $scope.persisted = angular.copy($scope.activity);
    });
}])
.controller('SettingCtrl',['$scope','$http','$modal','$timeout',function($scope,$http,$modal,$timeout) {
    var innerInp = {name:'姓名',mobile:'手机号',email:'邮箱'};
    var embedInnerInp = function(name) {
        tmsEditor.addInput(tmsEditor.getDoc('enroll_ele'), {model:"$parent.data."+name,title:innerInp[name],placeholder:innerInp[name]});
    };
    var embedBtnEnroll = function(){
        tmsEditor.addButton(tmsEditor.getDoc('enroll_ele'), {id:'btnEnroll',action:'enroll()',title:'提交信息'});
    };
    var CusdataCtrl = function($scope,$modalInstance) {
        $scope.def = {type:'0'};
        $scope.addOption = function() {
            if ($scope.def.ops === undefined)
                $scope.def.ops = [];
            var newOp = {text:''};
            $scope.def.ops.push(newOp);
            $timeout(function(){$scope.$broadcast('xxt.editable.add', newOp);});
        };
        $scope.$on('xxt.editable.remove', function(e, op){
            var i = $scope.def.ops.indexOf(op);
            $scope.def.ops.splice(i,1);
        });
        $scope.ok = function () {
            $modalInstance.close($scope.def);
        };
        $scope.cancel = function () {
            $modalInstance.dismiss('cancel');
        };
    };
    var embedCusData = function() {
        var ins = $modal.open({
            templateUrl: 'cusdata.html',
            controller: CusdataCtrl,
            backdrop:'static',
            resolve: {
            }
        });
        ins.result.then(function(def) {
            var cus='',key;
            key = 'c'+(new Date()).getTime();
            switch (def.type){
                case '0':
                    tmsEditor.addInput(tmsEditor.getDoc('enroll_ele'), {model:"$parent.data."+key, title:def.name, placeholder:def.name});
                    break;
                case '1':
                    tmsEditor.addTextarea(tmsEditor.getDoc('enroll_ele'), {model:"$parent.data."+key, title:def.name, placeholder:def.name, rows:3});
                    break;
                case '2':
                    if (def.ops && def.ops.length > 0) {
                        for (var i=0;i<def.ops.length;i++) {
                            tmsEditor.addRadio(tmsEditor.getDoc('enroll_ele'), {name:key, value:(i+1), model:"$parent.data."+key, title:def.name, label:def.ops[i].text});
                        }
                    }
                    break;
                case '3':
                    if (def.ops && def.ops.length > 0) {
                        for (var i=0;i<def.ops.length;i++) {
                            tmsEditor.addCheckbox(tmsEditor.getDoc('enroll_ele'), {name:key, model:"$parent.data."+key+'.'+(i+1), title:def.name, label:def.ops[i].text});
                        }
                    }
                    break;
            }
        }, function () {
        });
    };
    $scope.enrollCmds = [
        {title:'自定义数据',action:embedCusData},
        {title:'邮箱输入框',action:function(){embedInnerInp('email');}},
        {title:'手机号输入框',action:function(){embedInnerInp('mobile');}},
        {title:'姓名输入框',action:function(){embedInnerInp('name');}},
        {title:'提交信息按钮',action:embedBtnEnroll},
    ];
    $scope.update = function(name){
        if (!angular.equals($scope.activity, $scope.persisted)) {
            var p = {};
            p[name] = $scope.activity[name];
            $http.post('/rest/wbox/activity/update?aid='+$scope.aid, p)
            .success(function(rsp){
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    return;
                }
                $scope.persisted = angular.copy($scope.activity);
            });
        }
    };
    $scope.sendMe = function() {
        $http.post('/rest/wbox/activity/sendme?aid='+$scope.aid)
        .success(function(rsp){
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                return;
            }
        });
    };
}])
.controller('RollCtrl',['$scope','$http','$modal',function($scope,$http,$modal) {
    var doSearch = function(page) {
        !page && (page = $scope.page.current); 
        var filter = '';
        if ($scope.page.keyword !== '') {
            filter = '&kw=' + $scope.page.keyword;
            filter += '&by=' + $scope.page.searchBy;
        }
        var t = (new Date()).getTime();
        $http.get('/rest/wbox/activity/roll?_='+t+'&aid='+$scope.aid+'&page='+$scope.page.current+'&size='+$scope.page.size+'&contain=total'+filter)
        .success(function(rsp){
            $scope.roll = rsp.data[0];
            rsp.data[1] && ($scope.page.total = rsp.data[1]);
            rsp.data[2] && ($scope.cols = rsp.data[2]);
        });
    };
    $scope.searchBys = [
        {n:'手机号',v:'mobile'},
    ];
    $scope.page = {current:1,size:30,keyword:'',searchBy:'mobile'};
    doSearch();
    $scope.pageChanged = function() {
        doSearch();
    };
    $scope.search = function(keyword) {
        doSearch(1);
    };
    $scope.keywordKeyup = function(evt) {
        if (evt.which === 13) {
            doSearch();
        }
    };
}])
.controller('StatCtrl',['$scope','$http',function($scope,$http) {
    $http.get('/rest/wbox/activity/stat?aid='+$scope.aid)
    .success(function(rsp){
        $scope.stat = rsp.data;
    });
}]);
