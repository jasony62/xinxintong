xxtApp.controller('myArticleCtrl', ['$rootScope', '$scope', '$location', '$modal', 'http2', 'Article', function ($rootScope, $scope, $location, $modal, http2, Article) {
    $scope.back = function (event) {
        event.preventDefault();
        history.back();
    };
    $scope.edit = function (event, article) {
        if (article._cascade === true)
            $scope.editing = article;
        else
            $scope.Article.get(article.id).then(function (rsp) {
                article._cascade = true;
                article.channels = rsp.channels;
                $scope.editing = article;
            });
    };
    $scope.setPic = function () {
        $scope.$broadcast('picgallery.open', function (url) {
            var t = (new Date()).getTime();
            url += '?_=' + t;
            $scope.editing.pic = url;
            $scope.Article.update($scope.editing, 'pic');
        }, false);
    };
    $scope.removePic = function () {
        $scope.editing.pic = '';
        $scope.Article.update($scope.editing, 'pic');
    };
    $scope.$on('tinymce.innerlink_dlg.open', function (event, callback) {
        $scope.$broadcast('mattersgallery.open', callback);
    });
    $scope.$on('tinymce.multipleimage.open', function (event, callback) {
        $scope.$broadcast('picgallery.open', callback, true, true);
    });
    $scope.update = function (name) {
        $scope.Article.update($scope.editing, name);
    };
    $scope.remove = function () {
        $scope.Article.remove($scope.editing).then(function (data) {
            location.href = '/rest/app/contribute/initiate?mpid=' + $scope.mpid + '&entry=' + $scope.editing.entry;
        });
    };
    $scope.finish = function () {
        $scope.editing.finished = 'Y';
        $scope.update('finished');
    };
    $scope.forward = function () {
        $modal.open({
            templateUrl: '/static/template/userpicker.html?_=2',
            controller: 'ReviewUserPickerCtrl',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function (data) {
            $scope.Article.forward($scope.editing, data, 'R').then(function () {
                location.href = '/rest/app/contribute/initiate?mpid=' + $scope.mpid + '&entry=' + $scope.editing.entry;
            });
        });
    };
    $scope.mpid = $location.search().mpid;
    $scope.id = $location.search().id;
    $scope.Article = new Article('initiate', $scope.mpid, '');
    $scope.Article.get($scope.id).then(function (data) {
        $scope.editing = data;
    });
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params = JSON.parse(decodeURIComponent(nv.replace(/\+/, '%20')));
            console.log('ready', params);
            $scope.fid = params.fid;
            $scope.needReview = params.needReview;
            if ($scope.needReview === 'Y')
                $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.mpid;
            else
                $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + $scope.fid;
        }
    });

}]);
