angular.module('channel.fe.pl', ['ui.tms']).
controller('setChannelCtrl', ['$scope', 'http2', function($scope, http2) {
    $scope.$on('channel.xxt.combox.done', function(event, aSelected) {
        var aNewChannels = [],
            relations = {};
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.$parent.editing.channels) {
                if (aSelected[i].id === $scope.$parent.editing.channels[j].id) {
                    existing = true;
                    break;
                }
            }!existing && aNewChannels.push(aSelected[i]);
        }
        relations.channels = aNewChannels;
        relations.matter = {
            id: $scope.$parent.editing.id,
            type: $scope.matterType
        };
        http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId, relations, function() {
            $scope.$parent.editing.channels = $scope.$parent.editing.channels.concat(aNewChannels);
        });
    });
    $scope.$on('channel.xxt.combox.del', function(event, removed) {
        var matter = {
            id: $scope.$parent.editing.id,
            type: $scope.matterType
        };
        http2.post('/rest/pl/fe/matter/channel/removeMatter?site=' + $scope.siteId + '&id=' + removed.id, matter, function(rsp) {
            var i = $scope.$parent.editing.channels.indexOf(removed);
            $scope.$parent.editing.channels.splice(i, 1);
        });
    });
    $scope.$watch('matterType', function(nv) {
        if (nv && nv.length) {
            $scope.matterType = nv;
            http2.get('/rest/pl/fe/matter/channel/list?site=' + $scope.siteId + '&acceptType=' + nv + '&cascade=N', function(rsp) {
                $scope.channels = rsp.data;
            });
        }
    });
}]);