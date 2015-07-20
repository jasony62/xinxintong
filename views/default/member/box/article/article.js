xxtApp = angular.module('xxtApp', ['ui.tms']);
xxtApp.config(['$locationProvider', function ($locationProvider) {
    $locationProvider.html5Mode(true);
}]);
xxtApp.controller('myArticleCtrl', ['$rootScope','$scope', '$location', 'http2', function ($rootScope,$scope, $location, http2) {
    var articleId, mpid;
    articleId = $location.search().id;
    mpid = $location.search().mpid;
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
    $scope.chooseImage = function(from) {
        if (window.wx !== undefined) {
            wx.chooseImage({
                success: function(res) {
                    $scope.editing.pic = res.localIds[0];
                    $scope.$apply('editing.pic');
                    $scope.update('pic');
                }
            });
        } else if (window.YixinJSBridge){
            if (from === undefined) {
                openPickImageFrom();
                return;
            }
            $('#pickImageFrom').hide();
            YixinJSBridge.invoke(
                'pickImage', {
                    type:from,
                    quality:100
                }, function(result){
                    if (result.data && result.data.length) {
                        $scope.editing.pic = 'data:'+result.mime+';base64,'+result.data;
                        $scope.$apply('editing.pic');
                        $scope.update('pic');
                    }
                }
            );
        } else {
            var eleInp = document.createElement('input');
            eleInp.setAttribute('type', 'file');
            eleInp.addEventListener('change', function(evt){
                var cnt,f,type; 
                cnt = evt.target.files.length;
                f = evt.target.files[0];
                type = {".jp":"image/jpeg",".pn":"image/png",".gi":"image/gif"}[f.name.match(/\.(\w){2}/g)[0]||".jp"];
                f.type2 = f.type||type;
                var reader = new FileReader();
                reader.onload = (function(theFile) {
                    return function(e) {
                        $scope.editing.pic = e.target.result.replace(/^.+(,)/, "data:"+theFile.type2+";base64,");
                        $scope.$apply('editng.pic');
                        $scope.update('pic');
                    };
                })(f);
                reader.readAsDataURL(f);
            }, false);
            eleInp.click();
        }
    };
    $scope.preview = function () {
        location.href = '/rest/mi/matter?mpid=' + mpid + '&type=article&id=' + articleId + '&preview=Y';
    };
    $scope.removePic = function () {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.update = function (prop) {
        var url, nv = {};
        nv[prop] = $scope.editing[prop];
        url = '/rest/member/box/article/update';
        url += '?mpid=' + mpid;
        url += '&id=' + articleId;
        http2.post(url, nv, function success(rsp) {
            $scope.infomsg = '保存成功';
        });
    };
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            console.log('ready', params);
            var url = '/rest/member/box/article/get';
            url += '?mpid=' + mpid;
            url += '&id=' + articleId;
            http2.get(url, function success(rsp) {
                $scope.editing = rsp.data;
            });
        }
    });
}]);
xxtApp.directive('headingPic', function(){
	return {
		restrict: 'A',
        link: function (scope, elem, attrs) {
			var w,h;
			w = $(elem).width();
			h = w / 9 * 5;
            $(elem).css('max-height', h);
        }
	};
});
