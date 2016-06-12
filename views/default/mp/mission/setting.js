(function() {
    xxtApp.register.controller('settingCtrl', ['$scope', 'http2', 'matterTypes', function($scope, http2, matterTypes) {
        $scope.matterTypes = matterTypes;
        var modifiedData = {};
        $scope.modified = false;
        window.onbeforeunload = function(e) {
            var message;
            if ($scope.modified) {
                message = '修改还没有保存，是否要离开当前页面？',
                    e = e || window.event;
                if (e) {
                    e.returnValue = message;
                }
                return message;
            }
        };
        $scope.submit = function() {
            http2.post('/rest/mp/mission/update?id=' + $scope.id, modifiedData, function(rsp) {
                $scope.modified = false;
                modifiedData = {};
            });
        };
        $scope.update = function(name) {
            modifiedData[name] = $scope.editing[name];
            $scope.modified = true;
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.pic = url + '?_=' + (new Date()) * 1;
                    $scope.update('pic');
                }
            };
            $scope.$broadcast('mediagallery.open', options);
        };
        $scope.removePic = function() {
            var nv = {
                pic: ''
            };
            http2.post('/rest/mp/mission/update?id=' + $scope.id, nv, function() {
                $scope.editing.pic = '';
            });
        };
    }]);
})();