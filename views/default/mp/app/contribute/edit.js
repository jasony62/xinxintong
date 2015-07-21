xxtApp.config(['$routeProvider', function ($rp) {
    $rp.when('/rest/mp/app/contribute', {
        templateUrl: '/views/default/mp/app/contribute/setting.html',
    });
}]);
xxtApp.controller('contributeCtrl', ['$location', '$scope', 'http2', function ($location, $scope, http2) {
    var id;
    id = $location.search().id;
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
        var i, j, c;
        $scope.editing.params.subChannels === undefined && ($scope.editing.params.subChannels = []);
        for (i = 0, j = data.length; i < j; i++) {
            c = data[i];
            $scope.editing.subChannels.push({ id: c.id, title: c.title });
            $scope.editing.params.subChannels.push(c.id);
        }
        $scope.update('params');
    });
    $scope.$on('sub-channel.xxt.combox.del', function (event, ch) {
        var i;
        i = $scope.editing.subChannels.indexOf(ch);
        $scope.editing.subChannels.splice(i, 1);
        i = $scope.editing.params.subChannels.indexOf(ch.id);
        $scope.editing.params.subChannels.splice(i, 1);
        $scope.update('params');
    });
    $scope.$watch('jsonParams', function (nv) {
        if (nv && nv.length) {
            var params, entryUrl, ch, mapChannels = {};
            params = JSON.parse(decodeURIComponent(nv.replace(/\+/g, '%20')));
            console.log('ready', params);
            $scope.mpid = params.mpid;
            entryUrl = 'http://' + location.hostname + '/rest/app/contribute';
            entryUrl += '?mpid=' + params.mpid;
            $scope.entryUrl = entryUrl;
            $scope.editing = params.app;
            $scope.editing.canSetInitiator = 'Y';
            $scope.editing.canSetReviewer = 'Y';
            $scope.editing.canSetTypesetter = 'Y';
            $scope.editing.params = params.app.params ? JSON.parse($scope.editing.params) : {};
            $scope.editing.subChannels = [];
            for (i = 0, j = params.channels.length; i < j; i++) {
                ch = params.channels[i];
                mapChannels[ch.id] = ch;
            }
            if ($scope.editing.params.subChannels && $scope.editing.params.subChannels.length) {
                var i, j, cid;
                for (i = 0, j = $scope.editing.params.subChannels.length; i < j; i++) {
                    cid = $scope.editing.params.subChannels[i];
                    $scope.editing.subChannels.push(mapChannels[cid]);
                }
            }
            $scope.channels = params.channels;
            $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid=' + params.mpid;
        }
    });
}]);
