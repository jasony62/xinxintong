xxtApp.controller('lotteryCtrl',['$scope','http2','$location',function($scope,http2,$location) {
    $scope.awardTypes = [
    {n:'未中奖',v:'0'},
    {n:'应用积分',v:'1'},
    {n:'奖励重玩',v:'2'},
    {n:'完成任务',v:'3'},
    {n:'实体奖品',v:'99'},
    ];
    $scope.lid = $location.search().lid; 
    http2.get('/rest/mp/app/lottery/get?lid='+$scope.lid, function(rsp){
        $scope.lottery = rsp.data;
        if ($scope.lottery.awards === undefined) {
            $scope.lottery.awards = [];
        }
    });
}])
.controller('SettingCtrl',['$scope','http2',function($scope,http2) {
    $scope.years = [2014,2015,2016];
    $scope.months = [];
    $scope.days = [];
    $scope.hours = [];
    $scope.minutes = [];
    for (var i=1;i<=12;i++)
        $scope.months.push(i);
    for (var i=1;i<=31;i++)
        $scope.days.push(i);
    for (var i=0;i<=23;i++)
        $scope.hours.push(i);
    for (var i=0;i<=59;i++)
        $scope.minutes.push(i);
    $scope.updateTime = function(name) {
        var time = $scope[name].getTime(); 
        var p = {};
        p[name] = time/1000;
        http2.post('/rest/mp/app/lottery/update?lid='+$scope.lid, p);
    };
    $scope.update = function(name) {
        var p = {};
        p[name] = $scope.lottery[name];
        http2.post('/rest/mp/app/lottery/update?lid='+$scope.lid, p);
    };
    $scope.setPic = function(){
        $scope.$broadcast('picgallery.open', function(url){
            var t=(new Date()).getTime(),url=url+'?_='+t,nv={pic:url};
            http2.post('/rest/mp/app/lottery/update?lid='+$scope.lid, nv, function() {
                $scope.lottery.pic = url;
            });
        }, false);
    }; 
    $scope.removePic = function(){
        var nv = {pic:''};
        http2.post('/rest/mp/app/lottery/update?lid='+$scope.lid, nv, function() {
            $scope.lottery.pic = '';
        });
    };
    $scope.gotoCode = function() {
        if ($scope.lottery.page_id != 0)
            location.href = '/rest/code?pid='+$scope.lottery.page_id;
        else {
            http2.get('/rest/code/create', function(rsp){
                var nv = {'page_id': rsp.data.id};
                http2.post('/rest/mp/app/lottery/update?lid='+$scope.lid, nv, function() {
                    $scope.lottery.page_id = rsp.data.id;
                    location.href = '/rest/code?pid='+rsp.data.id;
                });
            });
        }
    };
    $scope.$watch('lottery',function(lottery){
        if (!lottery) return;
        $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid='+lottery.mpid;
        var date;
        if (lottery.start_at == 0) {
            date = new Date();
            date.setTime(date.getTime());
        } else
        date = new Date(lottery.start_at*1000);
        $scope.start_at = (function(date){
            return {
                year:date.getFullYear(),
                month:date.getMonth()+1,
                mday:date.getDate(),
                hour:date.getHours(),
                minute:date.getMinutes(),
                getTime: function() {
                    var d = new Date(this.year, this.month-1, this.mday, this.hour, this.minute, 0, 0);
                    return d.getTime();
                }
            };
        })(date);
        if (lottery.end_at == 0) {
            date = new Date();
            date.setTime(date.getTime()+86400000);
        } else
        date = new Date(lottery.end_at*1000);
        $scope.end_at = (function(date){
            return {
                year:date.getFullYear(),
                month:date.getMonth()+1,
                mday:date.getDate(),
                hour:date.getHours(),
                minute:date.getMinutes(),
                getTime: function() {
                    var d = new Date(this.year, this.month-1, this.mday, this.hour, this.minute, 0, 0);
                    return d.getTime();
                }
            };
        })(date);
    });
}])
.controller('AwardCtrl',['$scope','http2','$rootScope',function($scope,http2,$rootScope) {
    $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid='+$scope.lottery.mpid;
    $scope.addAward = function() {
        http2.get('/rest/mp/app/lottery/addAward?lid='+$scope.lid+'&mpid='+$scope.lottery.mpid, function(rsp){
            $scope.lottery.awards.push(rsp.data);
        });
    };
    $scope.removeAward = function(award) {
        http2.get('/rest/mp/app/lottery/delAward?aid='+award.aid, function(rsp){
            var i = $scope.lottery.awards.indexOf(award);
            $scope.lottery.awards.splice(i, 1);
        });
    };
    $scope.setPic = function(award){
        $scope.$broadcast('picgallery.open', function(url){
            award.pic = url;
            $scope.update(award, 'pic');
        }, false);
    }; 
    $scope.removePic = function(award){
        award.pic = '';
        $scope.update(award, 'pic');
    }; 
    $scope.update = function(award,name) {
        var p = {};
        p[name] = award[name];
        http2.post('/rest/mp/app/lottery/setAward?aid='+award.aid, p);
    };
}])
.controller('PlateCtrl',['$scope','http2',function($scope,http2) {
    http2.get('/rest/mp/app/lottery/plate?lid='+$scope.lid, function(rsp){
        $scope.plate = rsp.data;
    });
    $scope.update = function(slot) {
        var p = {};
        p[slot] = $scope.plate[slot];
        http2.post('/rest/mp/app/lottery/setPlate?lid='+$scope.lid, p);
    };
}])
.controller('resultCtrl',['$rootScope','$scope','http2',function($rootScope,$scope,http2) {
    var doSearch = function(page) {
        !page && (page = $scope.page.current); 
        var url = '/rest/mp/app/lottery/result';
        url += '?lid='+$scope.lid+'&page='+page+'&size='+$scope.page.size;
        url += '&startAt='+$scope.startAt.getTime()/1000;
        url += '&endAt='+$scope.endAt.getTime()/1000;
        if ($scope.byAward && $scope.byAward.length >0)
            url += '&award='+$scope.byAward;
        if ($scope.associatedAct)
            url += '&assocAct='+$scope.associatedAct.aid;
        http2.get(url, function(rsp){
            $scope.result = rsp.data[0];
            rsp.data[1] && ($scope.page.total = rsp.data[1]);
            rsp.data[2] && ($scope.assocDef = rsp.data[2]);
        });
    };
    var doStat = function(){
        http2.get('/rest/mp/app/lottery/stat?lid='+$scope.lid, function(rsp){
            $scope.stat = rsp.data;
        });
    };
    $scope.byAward = '';
    $scope.page = {
        current: 1,
        size: 30
    };
    var current = new Date();
    $scope.startAt = {
        year:current.getFullYear(),
        month:current.getMonth()+1,
        mday:current.getDate(),
        getTime:function() {
            var d = new Date(this.year, this.month-1, this.mday, 0, 0, 0, 0);
            return d.getTime();
        }
    };
    $scope.endAt = {
        year:current.getFullYear(),
        month:current.getMonth()+1,
        mday:current.getDate(),
        getTime:function() {
            var d = new Date(this.year, this.month-1, this.mday, 23, 59, 59, 0);
            return d.getTime();
        }
    };
    $scope.$on('xxt.tms-datepicker.change', function(n){
        doSearch(1);
    });
    $scope.doSearch = function(page) {
        page ? doSearch(page) : doSearch();
    };
    $scope.viewUser = function(fan){
        location.href = '/rest/mp/user?openid='+fan.openid;
    };
    $scope.refresh = function() {
        doStat();
        doSearch();
    };
    $scope.removeRoll = function(r) {
        var vcode;
        vcode = prompt('是否要删除当前用户的所有抽奖记录？，若是，请输入活动名称。');
        if (vcode === $scope.lottery.title) {
            var url = '/rest/mp/app/lottery/removeRoll?lid='+$scope.lid;
            if (r.openid && r.openid.length > 0)
                url += '&openid='+r.openid;
            else
                url += '&mid='+r.mid;
            http2.get(url, function(rsp){
                $scope.refresh();
            });
        }
    };
    $scope.clean = function() {
        var vcode;
        vcode = prompt('是否要重新设置奖项数量，并删除所有抽奖记录？，若是，请输入活动名称。');
        if (vcode === $scope.lottery.title) {
            http2.get('/rest/mp/app/lottery/clean?lid='+$scope.lid, function(rsp){
                $scope.refresh();
            });
        }
    };
    $scope.addChance = function() {
        var vcode;
        vcode = prompt('是否要给未中奖用户增加1次抽奖机会？，若是，请输入活动名称。');
        if (vcode === $scope.lottery.title) {
            http2.get('/rest/mp/app/lottery/addChance?lid='+$scope.lid, function(rsp){
                $rootScope.infomsg = rsp.data;
            });
        }
    };
    http2.get('/rest/mp/app/enroll/get?page=1&size=9999', function(rsp){
        $scope.activities = rsp.data[0];
    });
    $scope.refresh();
}]);
