define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlSetting', ['$scope',  '$location','$q', 'http2', 'mattersgallery', 'noticebox', function ($scope, $location,$q, http2, mattersgallery, noticebox) {
        /*var ls = $location.search();
        $scope.id = ls.id;
        $scope.siteId = ls.site;*/

        $scope.back = function() {
            location.href = '/rest/pl/fe/matter/addressbook';
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
        $scope.$watch('abid', function(id) {
            http2.get('/rest/pl/fe/matter/addressbook/get?abid=' + $scope.id + '&site='+ $scope.siteId, function(rsp) {
                $scope.editing = rsp.data;
                $scope.entryUrl = "http://" + location.host + "/rest/pl/fe/matter/addressbook?mpid=" + $scope.editing.mpid + "&id=" + $scope.editing.id;
            });
        });
        /*http2.get('/rest/mp/mpaccount/get', function(rsp) {
            $scope.mpaccount = rsp.data;
        });*/
    }]);
});