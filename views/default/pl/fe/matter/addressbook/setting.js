define(['frame'], function(ngApp) {
    ngApp.provider.controller('ctrlSetting', ['$scope',  '$location','$q', 'http2', 'mattersgallery', 'noticebox', 'mediagallery',function ($scope, $location,$q, http2, mattersgallery, noticebox,mediagallery) {
        /*var ls = $location.search();
        $scope.id = ls.id;
        $scope.siteId = ls.site;*/


        $scope.update = function(name) {
            var nv = {};
            nv[name] = $scope.editing[name];
            http2.post('/rest/pl/fe/matter/addressbook/update?abid=' + $scope.id + '&site='+ $scope.siteId, nv);
        };
        $scope.setPic = function() {
            var options = {
                callback: function(url) {
                    $scope.editing.pic = url + '?_=' + (new Date()) * 1
                    $scope.update('pic');
                }
            };
            //$scope.$broadcast('mediagallery.open', options);
            mediagallery.open($scope.siteId, options);
        };
        $scope.removePic = function() {
            $scope.editing.pic = '';
            $scope.update('pic');
        };
        $scope.$watch('abid', function(id) {
            http2.get('/rest/pl/fe/matter/addressbook/get?abid=' + $scope.id + '&site='+ $scope.siteId, function(rsp) {
                $scope.editing = rsp.data;
                $scope.entryUrl = "http://" + location.host + "/rest/site/fe/matter/addressbook?siteid=" + $scope.editing.siteid + "&id=" + $scope.editing.id;
            });
        });
        /*http2.get('/rest/mp/mpaccount/get', function(rsp) {
            $scope.mpaccount = rsp.data;
        });*/
    }]);
});