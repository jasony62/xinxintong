formApp=angular.module('formApp', ['infinite-scroll']);
formApp.directive('tmsInit', ['$rootScope','$timeout',function($rootScope,$timeout){
    return {
        restrict: 'A',
        link: function(scope, elem, attrs) {
            return $timeout(function(){
                if ($rootScope.$$phase) {
                    return scope.$eval(attrs.tmsInit);
                } else {
                    return scope.$apply(attrs.tmsInit);
                }
            },0);
        }
    };
}]);
formApp.factory('Round', function($http) {
    var Round = function(mpid, aid, current) {
        this.mpid = mpid;
        this.aid = aid;
        this.current = current;
        this.list = [];
    };
    Round.prototype.nextPage = function() {
        var _this = this;
        var url = '/rest/activity/enroll/rounds';
        url += '?mpid='+_this.mpid;
        url += '&aid='+_this.aid;
        $http.get(url).success(function(rsp){
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.list = rsp.data;
        });
    };
    return Round;
});
formApp.factory('Record', function($http) {
    var Record = function(mpid, aid, rid, current){
        this.mpid = mpid;
        this.aid = aid;
        this.rid = rid;
        this.current = current;
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.orderBy = 'time';
        this.owner = 'all';
    };
    var listGet = function(ins, owner) {
        if (ins.busy) return;
        ins.busy = true;
        var url;
        url = '/rest/activity/enroll/';
        url += ins.owner === 'user' ? 'myRecords' : 'records';
        url += '?mpid='+ins.mpid;
        url += '&aid='+ins.aid;
        url += '&rid='+ins.rid;
        url += '&orderby='+ins.orderBy;
        url += '&page='+ins.page;
        url += '&size=10';
        $http.get(url).success(function(rsp){
            if (rsp.data[0] && rsp.data[0].length) {
                for (var i=0; i<rsp.data[0].length; i++)
                    ins.list.push(rsp.data[0][i]);
                ins.page++;
            }
            ins.busy = false;
        });
    };
    Record.prototype.changeOrderBy = function(orderBy) {
        this.orderBy = orderBy;
        this.reset();
    };
    Record.prototype.reset = function() {
        this.list = [];
        this.busy = false;
        this.page = 1;
        this.nextPage();
    };
    Record.prototype.nextPage = function(owner) {
        if (owner && this.owner !== owner) {
            this.owner = owner;    
            this.reset();
        } else 
            listGet(this);
    };
    Record.prototype.like = function(event, record) {
        event.preventDefault();
        event.stopPropagation();
        if (!record && !this.current) {
            alert('没有指定要点赞的登记记录');
            return;
        }
        var url = '/rest/activity/enroll/recordScore';
        url += '?mpid='+this.mpid;
        url += '&ek=';
        record === undefined && (record = this.current);
        url += record.enroll_key;
        $http.get(url).success(function(rsp){
            record.myscore = rsp.data[0];
            record.score = rsp.data[1];
        });
    };
    Record.prototype.remark = function(event, newRemark) {
        event.preventDefault();
        event.stopPropagation();
        if (!newRemark || newRemark.length === 0) {
            alert('评论内容不允许为空');
            return;
        }
        var _this = this;
        if (this.current.enroll_key === undefined) {
            alert('没有指定要评论的登记记录');
            return;
        }
        var url = '/rest/activity/enroll/recordRemark';
        url += '?mpid='+this.mpid;
        url += '&ek='+this.current.enroll_key;
        $http.post(url, {remark:newRemark}).success(function(rsp){
            if (rsp.err_code != 0) {
                alert(rsp.data);
                return;
            }
            _this.current.remarks.push(rsp.data);
        });
    };
    return Record;
});
formApp.factory('Statistic', function(){
    var Stat = function(mpid, aid, data) {
        this.mpid = mpid;
        this.aid = aid;
        this.data = null;        
    };
    return Stat;
});
formApp.factory('User', function(){
    var Stat = function(mpid, aid, data) {
        this.mpid = mpid;
        this.aid = aid;
        this.data = null;        
    };
    return Stat;
});
formApp.controller('formCtrl', ['$scope','$http','$timeout','$q','Round','Record','Statistic','User',function($scope,$http,$timeout,$q,Round,Record,Statistic,User){
    window.shareCounter = 0;
    var logShare = function(shareto) {
        var url = "/rest/mi/matter/logShare"; 
        url += "?shareid="+window.shareid;
        url += "&mpid="+$scope.params.mpid; 
        url += "&id="+$scope.params.activity.aid; 
        url += "&type=activity";
        url += "&shareby="+$scope.params.shareby;
        url += "&shareto="+shareto; 
        $http.get(url);
        window.shareCounter++;
        window.onshare && window.onshare(window.shareCounter);
    };
    if (/MicroMessenger/i.test(navigator.userAgent)) {
        signPackage.jsApiList = ['hideOptionMenu','showOptionMenu','closeWindow','chooseImage','uploadImage','onMenuShareTimeline','onMenuShareAppMessage'];
        signPackage.debug = false;
        wx.config(signPackage);
        wx.ready(function(){
            wx.showOptionMenu();
            wx.onMenuShareTimeline({
                title: $scope.shareData.title,
                link: $scope.shareData.link,
                imgUrl: $scope.shareData.img_url,
                success: function () {
                    logShare('T');
                }
            });
            wx.onMenuShareAppMessage({
                title: $scope.shareData.title,
                desc: $scope.shareData.desc,
                link: $scope.shareData.link,
                imgUrl: $scope.shareData.img_url,
                success: function () {
                    logShare('F');
                }
            });
        });
    } else if (/YiXin/i.test(navigator.userAgent)) {
        document.addEventListener('YixinJSBridgeReady', function() {
            YixinJSBridge.call('showOptionMenu');
            YixinJSBridge.on('menu:share:appmessage', function(){
                logShare('F');
                YixinJSBridge.invoke('sendAppMessage', $scope.shareData, function() {});
            });
            YixinJSBridge.on('menu:share:timeline', function(){
                logShare('T');
                YixinJSBridge.invoke('shareTimeline', $scope.shareData, function() {});
            });
        }, false);
    }
    document.body.addEventListener('click', function(event){
        var target = event.target;
        if (target.tagName === 'A' && target.classList.contains('innerlink')) {
            event.preventDefault();
            var id=target.getAttribute('href'),type=target.getAttribute('type');
            id = id.split('/').pop();
            url = '/rest/mi/matter?mpid='+$scope.param.mpid+'type='+type+'&id='+id;
            location.href = url;
        }
    }, false);
    var openPickImageFrom = function() {
        var st = (document.body && document.body.scrollTop) ? document.body.scrollTop : document.documentElement.scrollTop;
        var ch = document.documentElement.clientHeight;
        var cw = document.documentElement.clientWidth;
        var $dlg = $('#pickImageFrom');
        $dlg.css({
            'display':'block',
            'top': (st + (ch - $dlg.height() - 30) / 2) + 'px',
            'left': ((cw -$dlg.width() - 30) / 2) + 'px'
        });
    };
    var required = function(value, len, alerttext) {
        if (value == null || value == "" || value.length < len) {
            $scope.errmsg = alerttext; return false;
        } else {return true;}
    };
    var validatePhone = function(value, alerttext) {
        if (false === /^1[3|4|5|7|8][0-9]\d{4,8}$/.test(value)) {
            $scope.errmsg = alerttext;return false;
        } else {return true;}
    };
    var validate = function() {
        if ($('[ng-model="data.name"]').length === 1) {
            if (false === required($scope.data.name, 2, '请提供您的姓名！')) {
                document.querySelector('[ng-model="data.name"]').focus();
                return false;
            }
        }
        if ($('[ng-model="data.mobile"]').length === 1) {
            if (false === validatePhone($scope.data.mobile, '请提供正确的手机号（11位数字）！')) {
                document.querySelector('[ng-model="data.mobile"]').focus();
                return false;
            }
        }
        $scope.errmsg = '';
        return true;
    };
    var modifiedImgFields = [];
    $scope.ready = false;
    $scope.errmsg = '';
    $scope.data = {};
    $scope.closeWindow = function() {
        if (/MicroMessenger/i.test(navigator.userAgent)) {
            wx.closeWindow();
        } else if (/YiXin/i.test(navigator.userAgent)) {
            YixinJSBridge.call('closeWebView');
        }
    };
    $scope.onUnauthorized = function(callback) {
        var $el = $('#frmAuth');
        window.onAuthSuccess = function() {
            $scope.requireAuth = 'N';
            $el.hide();
            callback && callback();
        };
        $el.attr('src',$scope.authurl).show();
    };
    $scope.chooseImage = function(imgFieldName, from) {
        if (-1 === modifiedImgFields.indexOf(imgFieldName)) modifiedImgFields.push(imgFieldName);
        if ($scope.data[imgFieldName] === undefined) $scope.data[imgFieldName] = [];

        var imgs = $scope.data[imgFieldName];
        if (window.wx !== undefined) {
            wx.chooseImage({
                success: function(res) {
                    var i, img;
                    for (i in res.localIds) {
                        img = {imgSrc:res.localIds[i]};
                        $scope.data[imgFieldName].push(img);
                        $scope.$apply('data.'+imgFieldName);
                        $('ul[name="'+imgFieldName+'"] li:nth-last-child(2) img').attr('src', img.imgSrc);
                    }
                }
            });
        } else if (window.YixinJSBridge){
            if (from === undefined) {
                $scope.cachedImgFieldName = imgFieldName;
                openPickImageFrom();
                return;
            }
            imgFieldName = $scope.cachedImgFieldName;
            $scope.cachedImgFieldName = null;
            $('#pickImageFrom').hide();
            YixinJSBridge.invoke(
                'pickImage', {
                    type:from,
                    quality:100
                }, function(result){
                    if (result.data && result.data.length) {
                        var img = {imgSrc:'data:'+result.mime+';base64,'+result.data};
                        $scope.data[imgFieldName].push(img);
                        $scope.$apply('data.'+imgFieldName);
                    }
                }
            );
        } else {
            var eleInp = document.createElement('input');
            eleInp.setAttribute('type', 'file');
            eleInp.addEventListener('change', function(evt){
                var i,cnt,f; 
                cnt = evt.target.files.length;
                for (i=0; i<cnt; i++) {
                    f = evt.target.files[i];
                    type = {".jp":"image/jpeg",".pn":"image/png",".gi":"image/gif"}[f.name.match(/\.(\w){2}/g)[0]||".jp"];
                    f.type2 = f.type||type;
                    var reader = new FileReader();
                    reader.onload = (function(theFile) {
                        return function(e) {
                            var img={};
                            img.imgSrc = e.target.result.replace(/^.+(,)/, "data:"+theFile.type2+";base64,");
                            $scope.data[imgFieldName].push(img);
                            $scope.$apply('data.'+imgFieldName);
                        };
                    })(f);
                    reader.readAsDataURL(f);
                }
            }, false);
            eleInp.click();
        }
    };
    $scope.removeImage = function(imgField, index) {
        imgField.splice(index, 1);
    };
    $scope.submit = function(event, nextAction) {
        if (!validate()) return;
        document.querySelector('#btnSubmit').setAttribute('disabled', true);

        var uploadWxImage = function(img) {
            var deferred, promise;
            deferred = $q.defer();
            promise = deferred.promise;
            if (0 === img.imgSrc.indexOf('weixin://') || 0 === img.imgSrc.indexOf('wxLocalResource://')) {
                wx.uploadImage({
                    localId: img.imgSrc,
                    isShowProgressTips: 1,
                    success: function(res) {
                        img.serverId = res.serverId;
                        deferred.resolve(img);
                    }
                });
            } else 
                deferred.resolve(img);

            return promise;
        };
        var submitWhole = function() {
            var url = '/rest/activity/enroll/submit?mpid='+$scope.params.mpid+'&aid='+$scope.params.activity.aid;
            if (!$scope.isNew && $scope.params.enrollKey && $scope.params.enrollKey.length)
                url += '&ek='+$scope.params.enrollKey;
            $http.post(url, $scope.data)
            .success(function(rsp){
                if (typeof(rsp) === 'string') {
                    $scope.errmsg = rsp;
                    document.querySelector('#btnSubmit').removeAttribute('disabled');
                    return;
                }
                if (rsp.err_code != 0) {
                    $scope.errmsg = rsp.err_msg;
                    document.querySelector('#btnSubmit').removeAttribute('disabled');
                    return;
                }
                if (nextAction === 'closeWindow') {
                    $scope.closeWindow();
                    return;
                }
                if (nextAction !== undefined && nextAction.length) {
                    var url = '/rest/activity/enroll';
                    url += '?mpid='+$scope.params.mpid;
                    url += '&aid='+$scope.params.activity.aid;
                    url += '&ek='+rsp.data;
                    url += '&page='+nextAction;
                    location.href = url;
                }
            });
        }
        if (window.wx !== undefined && modifiedImgFields.length) {
            try {
                var i=0,j=0,imgField,img;
                var nextWxImage = function() {
                    imgField = $scope.data[modifiedImgFields[i]];
                    img = imgField[j];
                    uploadWxImage(img).then(function(data){
                        if (j < imgField.length - 1)
                            j++;
                        else if (i < modifiedImgFields.length - 1) {
                            j = 0;
                            i++;
                        } else {
                            submitWhole();
                            return true;
                        }
                        nextWxImage();
                    });
                };
                nextWxImage();
            } catch (e) {
                alert(e.message);
            }
        } else
            submitWhole();
    };
    $scope.gotoPage = function(event, page, ek, rid) {
        event.preventDefault();
        event.stopPropagation();
        var url = '/rest/activity/enroll';
        url += '?mpid='+$scope.mpid;
        url += '&aid='+$scope.aid;
        if (page !== 'form' || ek !== undefined) {
            if (ek === undefined)
                $scope.Record.current.enroll_key !== undefined && (url += '&ek='+$scope.Record.current.enroll_key);
            else if (ek !== undefined && ek.length)
                url += '&ek='+ek;
        }
        if (rid !== undefined)
            url += '&rid='+rid;
        url += '&page='+page;
        location.href = url;
    };
    $scope.addRecord = function(event) {
        $scope.gotoPage(event, 'form');
    };
    $scope.$watch('jsonParams', function(nv){
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g,'%20')));
            window.shareid = params.user.vid+(new Date()).getTime();
            var sharelink = 'http://'+location.hostname+"/rest/activity/enroll";
            sharelink += "?mpid="+params.mpid;
            sharelink += "&aid="+params.activity.aid;
            sharelink += "&shareby="+window.shareid;
            $scope.shareData = {
                'img_url':params.activity.pic,
                'link':sharelink,
                'title':params.activity.title,
                'desc':params.activity.summary
            };
            $scope.User = new Record($scope.mpid, $scope.aid, params.user);
            $scope.Record = new Record($scope.mpid, $scope.aid, $scope.rid, params.record);
            $scope.Round = new Round($scope.mpid, $scope.aid);
            $scope.Statistic = new Statistic($scope.mpid, $scope.aid, params.statdata);
            $scope.params = params;
            $timeout(function(){
                if ($scope.params.subView === 'form') {
                    var p,type,data;
                    for (p in $scope.Record.current) {
                        if ($('[name='+p+']').hasClass('img-tiles')) {
                            if ($scope.Record.current[p] && $scope.Record.current[p].length) {
                                data = $scope.Record.current[p].split(',');
                                $scope.data[p] = [];
                                for (var i in data)
                                    $scope.data[p].push({imgSrc:data[i]});
                            }
                        } else {
                            type = $('[name='+p+']').attr('type');
                            if (type==='checkbox') {
                                if ($scope.Record.current[p] && $scope.Record.current[p].length) {
                                    data = $scope.Record.current[p].split(',');
                                    $scope.data[p] = {};
                                    for (var i in data)
                                        $scope.data[p][data[i]] = true;
                                }
                            } else
                                $scope.data[p] = $scope.Record.current[p];
                        }
                    }
                }
            });
            $scope.ready = true;
            console.log('page ready');
        }
    });
}])
.directive('runningButton', function(){
    return {
        restrict:'EA',
        template:"<button ng-class=\"isRunning?'btn-default':'btn-primary'\" ng-disabled='isRunning' ng-transclude></button>",
        scope:{isRunning:'='},
        replace:true,
        transclude:true
    }
})
.directive('flexImg', function(){
    return {
        restrict:'A',
        replace:true,
        template:"<img src='{{img.imgSrc}}'>",
        link:function(scope, elem, attrs){
            $(elem).on('load', function(){
                var w = $(this).width(),h = $(this).height(),s;
                if (w > h) {
                    sw = w/h*72;
                    $(this).css({'height':'100%','width':sw+'px','top':'0','left':'50%','margin-left':(-1*sw/2)+'px'});
                } else {
                    sh = h/w*72;
                    $(this).css({'width':'100%','height':sh+'px','left':'0','top':'50%','margin-top':(-1*sh/2)+'px'});
                }
            })
        }
    }
});
