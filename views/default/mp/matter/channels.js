xxtApp.controller('channelCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.doSearch = function() {
        var url = '/rest/mp/matter/channel/list?cascade=N',
            params = {};
        $scope.fromParent && $scope.fromParent === 'Y' && (params.src = 'p');
        http2.post(url, params, function(rsp) {
            $scope.channels = rsp.data;
        });
    };
    $scope.create = function() {
        var obj = {
            title: '新频道',
            volume: 5
        };
        http2.post('/rest/mp/matter/channel/create', obj, function(rsp) {
            location.href = '/rest/mp/matter/channel?id=' + rsp.data.id;
        });
    };
    $scope.edit = function(channel) {
        location.href = '/rest/mp/matter/channel?id=' + channel.id;
    };
    $scope.deleteOne = function(event, channel, index) {
        event.preventDefault();
        event.stopPropagation();
        if (window.confirm('确认删除？'))
            http2.get('/rest/mp/matter/channel/delete?id=' + channel.id, function(rsp) {
                $scope.channels.splice(index, 1);
            });
    };
    $scope.doSearch();
}]);