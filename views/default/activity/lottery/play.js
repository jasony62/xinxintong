lotApp=angular.module('lottery',[]).
controller('lotCtrl',['$scope','$http','$timeout',function($scope,$http,$timeout) {
    window.shareCounter = 0;
    var logShare = function(shareto) {
        var url = "/rest/mi/matter/logShare"; 
        url += "?shareid="+window.shareid;
        url += "&mpid="+$scope.params.mpid; 
        url += "&id="+$scope.params.lottery.lid; 
        url += "&type=lottery"; 
        url += "&shareby="+$scope.params.shareby;
        url += "&shareto="+shareto; 
        $http.get(url);
        window.shareCounter++;
        window.onshare && window.onshare(window.shareCounter);
    };
    if (/MicroMessenger/.test(navigator.userAgent)) {
        signPackage.jsApiList = ['onMenuShareTimeline','onMenuShareAppMessage'];
        wx.config(signPackage);
        wx.ready(function(){
            wx.onMenuShareTimeline({
                title: shareData.title,
                link: shareData.link,
                imgUrl: shareData.img_url,
                success: function() {
                    logShare('T');
                }
            });
            wx.onMenuShareAppMessage({
                title: shareData.title,
                desc: shareData.desc,
                link: shareData.link,
                imgUrl: shareData.img_url,
                success: function() {
                    logShare('F');
                }
            });
        });
    } else if (/YiXin/.test(navigator.userAgent)) {
        document.addEventListener('YixinJSBridgeReady', function() {
            YixinJSBridge.on('menu:share:appmessage', function(){
                logShare('F');
                YixinJSBridge.invoke('sendAppMessage', shareData, function() {
                });
            });
            YixinJSBridge.on('menu:share:timeline', function(){
                logShare('T');
                YixinJSBridge.invoke('shareTimeline', shareData, function() {
                });
            });
        }, false);
    }
    var t = (new Date()).getTime();
    $scope.errmsg = '';
    $scope.nonfansalert = '';
    $scope.nochancealert = '';
    $scope.greetingmsg = '';
    $scope.options = {fillStyle:['#E60000','#FFB30F']};
    $scope.$watch('jsonParams', function(nv){
        if (nv && nv.length) {
            var i,l,award,params;
            params = JSON.parse(decodeURIComponent(nv.replace(/\+/g,'%20')));
            $scope.setting = params.lottery;
            $scope.awards = {};
            for(i=0,l=$scope.setting.awards.length,award; i<l; i++) {
                award = $scope.setting.awards[i];
                $scope.awards[award.aid] = award;
            }
            window.shareid = params.visitor.vid+t;
            var sharelink = 'http://'+location.hostname+"/rest/activity/lottery";
            sharelink += "?mpid="+params.mpid;
            sharelink += "&lid="+params.lottery.lid;
            sharelink += "&shareby="+window.shareid;
            window.shareData = {
                'img_url':params.lottery.pic,
                'link':sharelink,
                'title':params.lottery.title,
                'desc':params.lottery.summary
            };
            if (params.lottery.show_winners==='Y') {
                $http.get('/rest/activity/lottery/winners?lid='+params.lottery.lid+'&_='+t, {
                    headers: {'Accept':'application/json'}                     
                }).
                success(function(rsp){
                    $scope.winners = rsp.data;
                    $timeout(function(){
                        if ($scope.winners.length > 0) {
                            $scope.myScroll = new IScroll('#winners', {
                                scrollX: true,
                                scrollY: false,
                                disableMouse: true,
                                disablePointer: true,
                                disableTouch: true,
                                momentum: false
                            });
                            $scope.indexOfWinners = 1;
                            $timeout($scope.rotateWinners, 3000);
                        }
                    }, 1000);
                });
            }
            $scope.params = params;
        }
    });
    $scope.play = function(cbSuccess, cbError) {
        $http.get('/rest/activity/lottery/play?mpid='+$scope.params.mpid+'&lid='+$scope.params.lottery.lid+'&_='+t, {headers:{'Accept':'application/json'}}).success(function(rsp){
            if (angular.isString(rsp)) {
                $scope.errmsg = rsp;
                state = 0;
                return;
            }
            if (rsp.err_code == 302) {
                $('#nonfansalert').html(rsp.err_msg);
                $scope.nonfansalert =  rsp.err_msg;
                state = 0;
                return;
            }
            if (rsp.err_code == 301) {
                $('#nochancealert').html(rsp.err_msg);
                $scope.nochancealert =  rsp.err_msg;
                state = 0;
                return;
            }
            if (rsp.err_code != 0) {
                $scope.errmsg = rsp.err_msg;
                state = 0;
                return;
            }
            $scope.setting.selected = rsp.data[0];
            var aid = $scope.setting.plate['a'+rsp.data[0]];
            for (var i in $scope.setting.awards) {
                var a = $scope.setting.awards[i];
                if (a.aid === aid) {
                    $scope.newAward = {
                        aid: aid,
                        draw_at: (new Date).getTime()/1000,
                        award_title: a.title,
                        award_greeting: a.greeting,
                        type: a.type
                    };
                    break;
                }
            }
            $scope.leftChance = rsp.data[1];
            cbSuccess && cbSuccess(rsp);
        }).
        error(function(rsp){
            state = 0;
            var $el = $('#frmAuth');
            window.onAuthSuccess = function() {
                //todo 应该重新调用后台接口获得和当前用户相关的数据，现在假定这是一个新注册用户
                $scope.setting.myAwards = [];
                $scope.setting.chance = $scope.setting.max_chance;
                $scope.$apply('setting.chance');
                $el.style.display = "none";
            };
            $el.setAttribute('src',rsp);
            $el.style.display = "block";
        });
    };
    $scope.validAward = function(award) {
        return award.type != 0 && award.type != 3; 
    };
    $scope.rotateWinners = function() {
        var el;
        $scope.indexOfWinners++;
        if ($scope.indexOfWinners > $scope.winners.length) { 
            $scope.indexOfWinners = 1;
            $scope.myScroll.scrollTo(0, 0);
        } else {
            el = document.querySelector('#scroller li:nth-child('+$scope.indexOfWinners+')');
            $scope.myScroll.scrollToElement(el, 1000);
        }
        $timeout($scope.rotateWinners, 3000);
    };
    $scope.showGreeting = function(greeting) {
        if ($scope.setting.show_greeting === 'Y') {
            $('#greetingmsg').html(greeting);
            $scope.greetingmsg =  greeting;
        }
    };
    $scope.debugReset = function() {
        var c,expdate = new Date(); 
        expdate.setTime(expdate.getTime() - (86400 * 1000 * 1)); 
        c = 'xxt_' + $scope.params.lottery.lid + '_precondition'+ "=; expires=" + expdate.toGMTString() + "; path=/"; 
        document.cookie = c;
        alert('clean');
    };
}]);
