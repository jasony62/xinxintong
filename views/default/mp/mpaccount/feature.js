xxtApp.controller('ctrlFeature', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
    $scope.update = function(name, callback) {
        var p = {};
        p[name] = $scope.features[name];
        http2.post('/rest/mp/feature/update', p, function() {
            callback && callback();
        });
    };
    $scope.setPic = function() {
        var options = {
            callback: function(url) {
                $scope.features.heading_pic = url + '?_=' + (new Date()) * 1;;
                $scope.update('heading_pic');
            }
        };
        $scope.$broadcast('mediagallery.open', options);
    };
    $scope.removePic = function() {
        $scope.features.heading_pic = '';
        $scope.update('heading_pic');
    };
    $scope.editPage = function(event, prop) {
        event.preventDefault();
        event.stopPropagation();
        var pageid = $scope.features[prop];
        if (pageid === '0') {
            http2.get('/rest/mp/feature/pageCreate', function(rsp) {
                $scope.features[prop] = new String(rsp.data);
                location.href = '/rest/code?pid=' + rsp.data;
            })
        } else {
            location.href = '/rest/code?pid=' + pageid;
        }
    };
    $scope.resetPage = function(event, prop) {
        event.preventDefault();
        event.stopPropagation();
        if (window.confirm('重置操作将覆盖已经做出的修改，确定重置？')) {
            var pageid = $scope.features[prop];
            if (pageid === '0') {
                http2.get('/rest/mp/feature/pageCreate', function(rsp) {
                    $scope.features[prop] = new String(rsp.data.id);
                    location.href = '/rest/code?pid=' + rsp.data.id;
                })
            } else {
                http2.get('/rest/mp/feature/pageReset?codeId=' + pageid, function(rsp) {
                    location.href = '/rest/code?pid=' + pageid;
                })
            }
        }
    };
    $scope.previewFollow = function(event) {
        event.preventDefault();
        event.stopPropagation();
        $uibModal.open({
            templateUrl: 'preview.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.page = {
                    src: '/rest/mp/feature/askFollow?mpid=' + $scope.features.mpid
                };
                $scope2.close = function() {
                    $mi.dismiss();
                };
            }]
        });
    };
    http2.get('/rest/mp/feature/get', function(rsp) {
        $scope.features = rsp.data;
    });
}]);