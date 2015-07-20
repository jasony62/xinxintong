xxtApp.controller('CardCtrl',['$scope','$http','$location',function($scope,$http,$location){
    var openKcfinder = function(type, callback, multiple) {
        var kcfCallBack = function(url) {
            window.KCFinder = null;
            $.dlg.close();
            callback && callback(url);
        };
        if (multiple) {
            window.KCFinder = {callBackMultiple: kcfCallBack};
        } else {
            window.KCFinder = {callBack: kcfCallBack};
        }
        $dlgBody = $('<iframe>').attr({
            'src': '/kcfinder/browse.php?lang=zh-cn&type=' + type + '&mpid=' + $scope.card.mpid,
            'frameborder': 0,
            'width': '100%',
            'height': '100%',
            'marginwidth': 0,
            'marginheight': 0,
            'scrolling': 'no'
        });
        $.dlg.open({
            title: '选择图片',
            dialogWidth: '800px',
            fullHeight: true,
            bodyCss: {'padding':'0', 'overflow':'hidden'},
            body: $dlgBody,
        });
    };
    //
    $http.get('/rest/mp/member/card').
    success(function(rsp){
        $scope.card = rsp.data;
    });
    $scope.submit = function() {
        $http.post('/rest/mp/member/card', $scope.card)
        .success(function(rsp){
            alert(rsp.data);
        });
    };
    $scope.setBoardPic = function(){
        openKcfinder('图片', function(url){
            $scope.card.board_pic = url;
            $scope.$apply('card');
        });
    }; 
    $scope.removeBoardPic = function(){
        $scope.card.board_pic = '';
    }; 
    $scope.setBadgePic = function(){
        openKcfinder('图片', function(url){
            $scope.card.badge_pic = url;
            $scope.$apply('card');
        });
    }; 
    $scope.removeBadgePic = function(){
        $scope.card.badge_pic = '';
    }; 
}]);
