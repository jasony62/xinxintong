angular.module('channel.fe.pl', ['ui.tms']).
controller('ctrlSetChannel', ['$scope', 'http2', function($scope, http2) {
    $scope.$on('channel.xxt.combox.done', function(event, aSelected) {
        var i, j, existing, aNewChannels = [],
            relations = {},
            matter = $scope.$parent[$scope.matterObj];
        for (i in aSelected) {
            existing = false;
            for (j in matter.channels) {
                if (aSelected[i].id === matter.channels[j].id) {
                    existing = true;
                    break;
                }
            }!existing && aNewChannels.push(aSelected[i]);
        }
        relations.channels = aNewChannels;
        relations.matter = {
            id: matter.id,
            type: $scope.matterType
        };
        http2.post('/rest/pl/fe/matter/channel/addMatter?site=' + $scope.siteId, relations, function() {
            matter.channels = matter.channels.concat(aNewChannels);
        });
    });
    $scope.$on('channel.xxt.combox.del', function(event, removed) {
        var matter = $scope.$parent[$scope.matterObj],
            param = {
                id: matter.id,
                type: $scope.matterType
            };
        http2.post('/rest/pl/fe/matter/channel/removeMatter?site=' + $scope.siteId + '&id=' + removed.id, param, function(rsp) {
            matter.channels.splice(matter.channels.indexOf(removed), 1);
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