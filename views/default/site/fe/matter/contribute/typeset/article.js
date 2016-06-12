xxtApp.controller('myArticleCtrl', ['$location', '$scope', '$uibModal', 'http2', 'Article', function ($location, $scope, $uibModal, http2, Article) {
    $scope.back = function (event) {
        event.preventDefault();
        history.back();
    };
    window.onbeforeunload = function (e) {
        var message;
        if ($scope.bodyModified) {
            message = '已经修改的正文还没有保存',
            e = e || window.event;
            if (e) {
                e.returnValue = message;
            }
            return message;
        }
    };
    $scope.onBodyChange = function () {
        $scope.bodyModified = true;
    };
    $scope.update = function (name) {
        $scope.Article.update($scope.editing, name);
        name === 'body' && ($scope.bodyModified = false);
    };
    $scope.setTargetMp = function (mp) {
        var mps = JSON.parse($scope.editing.target_mps);
        $scope.editing.target_mps2[mp.id] === 'Y' ? mps.push(mp.id) : mps.splice(mps.indexOf(mp.id), 1);
        $scope.editing.target_mps = JSON.stringify(mps);
        $scope.Article.update($scope.editing, 'target_mps');
    };
    $scope.forward = function () {
        $uibModal.open({
            templateUrl: '/static/template/userpicker.html?_=2',
            controller: 'ReviewUserPickerCtrl',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function (data) {
            $scope.Article.forward($scope.editing, data, 'R').then(function () {
                location.href = '/rest/app/contribute/typeset?mpid=' + $scope.mpid + '&entry=' + $scope.editing.entry;
            });
        });
    };
    $scope.$on('channel.xxt.combox.done', function (event, aSelected) {
        var aNewChannels = [], relations = {};
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.editing.channels) {
                if (aSelected[i].id === $scope.editing.channels[j].id) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewChannels.push(aSelected[i]);
        }
        relations.channels = aNewChannels;
        relations.matter = { id: $scope.editing.id, type: 'article' };
        http2.post('/rest/app/contribute/typeset/channelAddMatter?mpid=' + $scope.mpid, relations, function () {
            $scope.editing.channels = $scope.editing.channels.concat(aNewChannels);
        });
    });
    $scope.$on('channel.xxt.combox.del', function (event, removed) {
        var matter = { id: $scope.editing.id, type: 'article' };
        http2.post('/rest/app/contribute/typeset/channelDelMatter?mpid=' + $scope.mpid + '&id=' + removed.id, matter, function (rsp) {
            var i = $scope.editing.channels.indexOf(removed);
            $scope.editing.channels.splice(i, 1);
        });
    });
    $scope.mpid = $location.search().mpid;
    $scope.id = $location.search().id;
    $scope.Article = new Article('typeset', $scope.mpid, '');
    $scope.Article.get($scope.id).then(function (data) {
        $scope.editing = data;
        $scope.Article.mpaccounts().then(function (data) {
            $scope.mpaccounts = data;
            $scope.editing.target_mps2 = {};
            angular.forEach($scope.mpaccounts, function (mpa) {
                $scope.editing.target_mps2[mpa.id] = 'N';
            });
            if ($scope.editing.target_mps.indexOf('[') === 0) {
                var mps = JSON.parse($scope.editing.target_mps);
                angular.forEach(mps, function (mpid) {
                    $scope.editing.target_mps2[mpid] = 'Y';
                });
            } else {
                $scope.editing.target_mps = '[]';
            }
        });
    });
    $scope.Article.channels().then(function (data) {
        $scope.channels = data;
    });
}]);
