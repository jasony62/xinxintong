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
        $scope.$watch('abid', function(nv) {
            http2.get('/rest/mp/app/addressbook/get?abid=' + nv, function(rsp) {
                $scope.editing = rsp.data;
                $scope.entryUrl = "http://" + location.host + "/rest/app/addressbook?mpid=" + $scope.editing.mpid + "&id=" + $scope.editing.id;
            });
        });
        http2.get('/rest/mp/mpaccount/get', function(rsp) {
            $scope.mpaccount = rsp.data;
        });
    }]);
});