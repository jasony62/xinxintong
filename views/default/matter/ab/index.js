if (/MicroMessenger/.test(navigator.userAgent)) {
    document.addEventListener('WeixinJSBridgeReady', function() {
        WeixinJSBridge.call('hideOptionMenu');
    }, false);
} else if (/YiXin/.test(navigator.userAgent)) {
    document.addEventListener('YixinJSBridgeReady', function() {
        YixinJSBridge.call('hideOptionMenu');
    }, false);
}
angular.module('xxt', ['infinite-scroll']).
filter("tel", function(){
    return function(tels){
        var i,aTels = tels.split(','),rst=[];
        for (i in aTels)
            rst.push("<a href='tel:"+aTels[i]+"'>"+aTels[i]+"</a>");
        return rst.join(',');
    }
}).
filter("depts", function(){
    return function(aDepts){
        var i,rst=[];
        for (i in aDepts)
            rst.push(aDepts[i].name);
        return rst.join(',');
    }
}).
controller('abCtrl',['$scope','$http',function($scope,$http){
    var $dlg = document.querySelector('#dlg'),
    $dlg2 = document.querySelector('#dlg2');
    var dlg = function() {
        var st,ch,cw;
        st = (document.body&&document.body.scrollTop)?document.body.scrollTop:document.documentElement.scrollTop;
        ch = document.documentElement.clientHeight;
        cw = document.documentElement.clientWidth;
        $dlg.style.display = 'block';
        $dlg.style.top = (st + ch / 2 - $dlg.clientHeight / 2) + 'px';
        $dlg.style.left = (cw / 2 - $dlg.clientWidth / 2) + 'px';
    };
    document.querySelector('#dlg button').addEventListener('click',function(e){
        $dlg.style.display='none';
    });
    var hasChildDept = function(pid) {
        var childDept;
        for (var i in $scope.depts) {
            childDept = $scope.depts[i];
            if (childDept.pid == pid) return true;
        }
        return false;
    };
    var setShowDepts = function(pid) {
        var childDept,showDepts = [];
        for (var i in $scope.depts) {
            childDept = $scope.depts[i];
            if (childDept.pid == pid) {
                childDept.hasChild = hasChildDept(childDept.id);
                showDepts.push(childDept);
            }
        }
        $scope.showDepts = showDepts;
    };
    $scope.abbr = '';
    $scope.pickedDept = null;
    $scope.upperDepts = [];
    $scope.page = {at:1,size:20};
    $scope.canReset = false;
    $scope.loading = false;
    $scope.doSearch = function() {
        $scope.loading = true;
        var url = '/rest/mi/matter/addressbook?mpid='+$scope.mpid;
        url += '&page='+$scope.page.at+'&size='+$scope.page.size;
        $scope.abbr && $scope.abbr.length && (url += '&abbr='+$scope.abbr);
        $scope.pickedDept && (url += '&deptid='+$scope.pickedDept.id);
        $http.get(url,{headers:{'Accept':'application/json'}}).success(function(rsp) {
            $scope.persons = $scope.persons.concat(rsp.data[0]);
            $scope.page.total = rsp.data[1]; 
            $scope.loading = false;
        });
    };
    $scope.begin = function() {
        if ($scope.abbr.length > 0) $scope.canReset = true;
        $scope.persons = [];
        $scope.page.at = 1;
        $scope.doSearch();
    };
    $scope.more = function() {
        if ($scope.page.total == $scope.persons.length) return;
        $scope.page.at += 1
        $scope.doSearch();
    };
    $scope.reset = function() {
        $scope.canReset = false;
        $scope.persons = [];
        $scope.abbr = '';
        $scope.page.at = 1;
        $scope.doSearch();
    };
    $scope.open = function(person) {
        $scope.opened = angular.copy(person);
        $scope.opened.tels = $scope.opened.tels.split(',');
        dlg(); 
    };
    var dlg2Touch = {
        lastPageY: undefined,
        start: function(event){
            var touch = event.changedTouches[0];
            dlg2Touch.lastPageY = touch.pageY;
        },
        move:function(event){
            event.preventDefault();
            var touch = event.changedTouches[0];
            $dlg2.querySelector('ul').scrollTop += (dlg2Touch.lastPageY - touch.pageY);
            dlg2Touch.lastPageY = touch.pageY;
        }
    };
    $scope.openDeptPicker = function() {
        document.body.style.overflow = 'hidden';
        $dlg2.style.display = 'block';
        document.body.addEventListener('touchstart', dlg2Touch.start, false);
        document.body.addEventListener('touchmove', dlg2Touch.move, false);
        $scope.pickedDept = null;
        $scope.upperDepts = [];
        setShowDepts(0);
    };
    $scope.closeDeptPicker = function() {
        $dlg2.style.display='none';
        document.body.style.overflow = 'auto';
        document.body.removeEventListener('touchstart', dlg2Touch.start, false);
        document.body.removeEventListener('touchmove', dlg2Touch.move, false);
    };
    $scope.pickDept = function(dept) {
        $scope.pickedDept = dept;
        $scope.begin();
        $scope.closeDeptPicker();
    };
    $scope.childDept = function(dept) {
        $scope.upperDepts.push(dept);
        setShowDepts(dept.id);
    };
    $scope.backDept = function() {
        $scope.upperDepts.length && setShowDepts($scope.upperDepts.pop().pid);
    };
    $scope.$watch('mpid', function(nv){
        nv && nv.length>0 && $scope.begin();
    });
    $scope.$watch('abbr', function(nv){
        $scope.canReset = false;
    });
    $scope.$watch('jsonDepts', function(nv){
        if (nv && nv.length) {
            $scope.depts = JSON.parse(decodeURIComponent(nv));
        }
    });
}]).
controller('ProfileCtrl',['$modalInstance','person',function($modalInstance,person){
    $scope.person = person;
}]);
