angular.module('channel.matter.mp', ['ui.tms']).
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
        http2.post('/rest/mp/matter/channel/addMatter', relations, function() {
            $scope.$parent.editing.channels = $scope.$parent.editing.channels.concat(aNewChannels);
        });
    });
    $scope.$on('channel.xxt.combox.del', function(event, removed) {
        var matter = {
            id: $scope.$parent.editing.id,
            type: $scope.matterType
        };
        http2.post('/rest/mp/matter/channel/removeMatter?id=' + removed.id, matter, function(rsp) {
            var i = $scope.$parent.editing.channels.indexOf(removed);
            $scope.$parent.editing.channels.splice(i, 1);
        });
    });
    $scope.$watch('channelsFromParent', function(nv) {
        if (nv && nv.length) {
            var url = '/rest/mp/matter/channel/get?acceptType=' + $scope.matterType + '&cascade=N';
            nv === 'Y' && (url += '&src=p');
            http2.get(url, function(rsp) {
                $scope.channels = rsp.data;
            });
        }
    });
    $scope.$watch('matterType', function(nv) {
        if (nv && nv.length) {
            $scope.matterType = nv;
            http2.get('/rest/mp/matter/channel/get?acceptType=' + nv + '&cascade=N', function(rsp) {
                $scope.channels = rsp.data;
            });
        }
    });
}]);