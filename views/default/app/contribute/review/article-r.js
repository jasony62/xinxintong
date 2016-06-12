xxtApp.controller('reviewCtrl', ['$location', '$scope', '$uibModal', 'http2', 'Article', 'Entry', 'Reviewlog', function($location, $scope, $uibModal, http2, Article, Entry, Reviewlog) {
    var mpid, id;
    mpid = $location.search().mpid;
    id = $location.search().id;
    $scope.phases = {
        'I': '投稿',
        'R': '审核',
        'T': '版面'
    };
    $scope.entry = $location.search().entry;
    $scope.Article = new Article('review', mpid, $scope.entry);
    $scope.Entry = new Entry(mpid, $scope.entry);
    $scope.Reviewlog = new Reviewlog('initiate', mpid, {
        type: 'article',
        id: id
    });
    $scope.back = function(event) {
        event.preventDefault();
        location.href = '/rest/app/contribute/review?mpid=' + mpid + '&entry=' + $scope.entry;
    };
    $scope.Article.get(id).then(function(data) {
        $scope.editing = data;
        var ele = document.querySelector('#content>iframe');
        if (ele.contentDocument && ele.contentDocument.body)
            ele.contentDocument.body.innerHTML = data.body;
        $scope.Article.mpaccounts().then(function(data) {
            var target_mps2 = [];
            if ($scope.editing.target_mps.indexOf('[') === 0) {
                var mps = JSON.parse($scope.editing.target_mps);
                angular.forEach(data, function(mpa) {
                    mps.indexOf(mpa.id) !== -1 && target_mps2.push(mpa.name);
                });
                $scope.targetMps = target_mps2.join(',');
            }
        });
    }).then(function() {
        $scope.Entry.get().then(function(data) {
            var i, j, ch, mapSubChannels = {};
            $scope.editing.subChannels = [];
            $scope.entryApp = data;
            if (data.subChannels)
                for (i = 0, j = data.subChannels.length; i < j; i++) {
                    ch = data.subChannels[i];
                    mapSubChannels[ch.id] = ch;
                }
            if ($scope.editing.channels)
                for (i = 0, j = $scope.editing.channels.length; i < j; i++) {
                    ch = $scope.editing.channels[i];
                    mapSubChannels[ch.id] && $scope.editing.subChannels.push(ch);
                }
        });
    });
    $scope.return = function() {
        $uibModal.open({
            templateUrl: 'replyBox.html',
            controller: ['$scope', '$uibModalInstance', 'http2', function($scope, $mi, http2) {
                $scope.data = {
                    message: ''
                };
                $scope.close = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.data);
                };
            }],
            backdrop: 'static',
        }).result.then(function(data) {
            $scope.Article.return($scope.editing, data.message).then(function() {
                location.href = '/rest/app/contribute/review?mpid=' + mpid + '&entry=' + $scope.editing.entry;
            });
        });
    };
    $scope.pass = function() {
        $scope.Article.pass($scope.editing).then(function() {
            location.href = '/rest/app/contribute/review?mpid=' + mpid + '&entry=' + $scope.editing.entry;
        });
    };
    $scope.refuse = function() {

    };
    $scope.publish = function() {
        $uibModal.open({
            templateUrl: '/views/default/app/contribute/publish.html',
            controller: ['$scope', '$uibModalInstance', 'http2', 'mpid', function($scope, $mi, http2, mpid) {
                $scope.pickMp = function(mp) {
                    !$scope.selected && ($scope.selected = []);
                    if (mp.checked === 'Y')
                        $scope.selected.push(mp);
                    else
                        $scope.selected.splice($scope.childmps.indexOf(mp), 1);
                };
                $scope.cancel = function() {
                    $mi.dismiss();
                };
                $scope.ok = function() {
                    $mi.close($scope.selected);
                };
                http2.get('/rest/app/contribute/typeset/childmps?mpid=' + mpid, function(rsp) {
                    $scope.childmps = rsp.data;
                });
            }],
            resolve: {
                mpid: function() {
                    return mpid;
                }
            },
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function(selectedMps) {
            if (selectedMps && selectedMps.length) {
                var data = {
                    id: id,
                    type: 'article',
                };
                var i = 0,
                    mps = [];
                for (i; i < selectedMps.length; i++) {
                    mps.push(selectedMps[i].mpid);
                }
                data.mps = mps;
                http2.post('/rest/mp/send/mass2mps', data, function(rsp) {
                    $scope.$root.infomsg = '发送完成';
                });
            }
        });
    };
    $scope.preview = function() {
        location.href = '/rest/mi/matter?mode=preview&type=article&tpl=std&mpid=' + mpid + '&id=' + id;
    };
    $scope.Reviewlog.list().then(function(data) {
        $scope.logs = data;
    });
}]);