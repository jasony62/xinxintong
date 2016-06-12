xxtApp.controller('newsCtrl', ['$location', '$scope', '$uibModal', 'http2', 'News', function($location, $scope, $uibModal, http2, News) {
    $scope.mpid = $location.search().mpid;
    $scope.entry = $location.search().entry;
    $scope.id = $location.search().id;
    $scope.back = function(event) {
        event.preventDefault();
        history.back();
    };
    $scope.removeMatter = function() {};
    $scope.update = function(name) {};
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
                    return $scope.mpid;
                }
            },
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function(selectedMps) {
            if (selectedMps && selectedMps.length) {
                var data = {
                    id: $scope.id,
                    type: 'news',
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
    $scope.forward = function() {
        $uibModal.open({
            templateUrl: '/static/template/userpicker.html?_=2',
            controller: 'ReviewUserPickerCtrl',
            backdrop: 'static',
            size: 'lg',
            windowClass: 'auto-height'
        }).result.then(function(data) {
            $scope.News.forward($scope.editing, data, 'R').then(function() {
                location.href = '/rest/app/contribute/typeset?mpid=' + $scope.mpid + '&entry=' + $scope.entry;
            });
        });
    };
    $scope.News = new News('typeset', $scope.mpid, $scope.entry);
    $scope.News.get($scope.id).then(function(data) {
        $scope.editing = data;
    });
}]);