define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlSetting', ['$scope', '$q', 'http2', 'mattersgallery', 'noticebox', function ($scope, $q, http2, mattersgallery, noticebox) {
        $scope.back = function() {
            location.href = '/rest/mp/app/addressbook';
        };
        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/mp/app/addressbook/update?abid=' + $scope.editing.id, nv);
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.pic = url + '?_=' + (new Date()) * 1
                    $scope.update('pic');
                }
            };
            $scope.$broadcast('mediagallery.open', options);
        };
        $scope.removePic = function() {
            $scope.editing.pic = '';
            $scope.update('pic');
        };
        /*http2.get('/rest/mp/mpaccount/get', function(rsp) {
            $scope.mpaccount = rsp.data;
        });*/
    }]);
});