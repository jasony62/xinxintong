(function() {
    xxtApp.register.controller('settingCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.createArticle = function() {
            http2.get('/rest/mp/matter/article/createByMission?id=' + $scope.id, function(rsp) {
                location.href = '/rest/mp/matter/article?id=' + rsp.data.id;
            });
        };
        $scope.createEnroll = function() {
            http2.get('/rest/mp/app/enroll/createByMission?id=' + $scope.id, function(rsp) {
                location.href = '/rest/mp/app/enroll/detail?aid=' + rsp.data.id;
            });
        };
        $scope.open = function(matter) {
            if (matter.type === 'article') {
                location.href = '/rest/mp/matter/article?id=' + matter.id;
            } else if (matter.matter_type === 'enroll') {
                location.href = '/rest/mp/app/enroll/detail?aid=' + matter.id;
            }
        };
        $scope.fetch = function() {
            http2.get('/rest/mp/mission/mattersList?id=' + $scope.id, function(rsp) {
                angular.forEach(rsp.data, function(matter) {
                    matter._operator = matter.modifier_name || matter.creater_name;
                    matter._operateAt = matter.modifiy_at || matter.create_at;
                });
                $scope.matters = rsp.data;
            });
        };
        $scope.fetch();
    }]);
})();