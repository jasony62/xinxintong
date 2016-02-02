xxtApp.controller('tmplmsgCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.create = function() {
        http2.get('/rest/mp/matter/tmplmsg/create', function(rsp) {
            location.href = '/rest/mp/matter/tmplmsg?id=' + rsp.data.id;
        });
    };
    $scope.edit = function(tmplmsg) {
        location.href = '/rest/mp/matter/tmplmsg?id=' + tmplmsg.id;
    };
    $scope.remove = function(event, tmplmsg) {
        event.preventDefault();
        event.stopPropagation();
        http2.get('/rest/mp/matter/tmplmsg/remove?id=' + tmplmsg.id, function(rsp) {
            var i = $scope.tmplmsgs.indexOf(tmplmsg);
            $scope.tmplmsgs.splice(i, 1);
        });
    };
    $scope.doSearch = function() {
        var url = '/rest/mp/matter/tmplmsg/list';
        http2.get(url, function(rsp) {
            $scope.tmplmsgs = rsp.data;
        });
    };
    $scope.doSearch();
}]);