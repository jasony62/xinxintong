xxtApp.controller('contributeCtrl', ['$scope', 'http2', function ($scope, http2) {
    $scope.taskCodeEntryUrl = 'http://' + location.host + '/rest/q';
    $scope.update = function (name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/app/contribute/update?id=' + $scope.editing.id, nv);
    };
    $scope.setPic = function () {
        $scope.$broadcast('picgallery.open', function (url) {
            var t = (new Date()).getTime();
            url += '?_=' + t;
            $scope.editing.pic = url;
            $scope.update('pic');
        }, false);
    };
    $scope.removePic = function () {
        $scope.editing.pic = '';
        $scope.update('pic');
    };
    $scope.$on('sub-channel.xxt.combox.done', function (event, data) {
        $scope.editing.subChannels = $scope.editing.subChannels.concat(data); 
    });
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
            console.log('ready', params);
            $scope.editing = params.app;
            $scope.editing.canSetInitiator = 'Y';
            $scope.editing.canSetReviewer = 'Y';
            $scope.editing.canSetTypesetter = 'Y';
            $scope.editing.params = params.app.params ? JSON.parse($scope.editing.params) : {};
            $scope.editing.subChannels = []; 
            $scope.channels = params.channels;
            $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + params.mpid;
        }
    });
}]);
