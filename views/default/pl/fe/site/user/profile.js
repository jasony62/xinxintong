(function() {
    ngApp.provider.controller('ctrlProfile', ['$location', '$scope', 'http2', '$modal', function($location, $scope, http2, $modal) {
        var baseURL = '/rest/pl/fe/site/user/profile/',
            userid = $location.search().userid;
        http2.get(baseURL + 'get?site=' + $scope.siteId + '&userid=' + userid, function(rsp) {
            $scope.user = rsp.data.user;
            $scope.members = rsp.data.members;
        });
        $scope.addMember = function(schema) {
            $modal.open({
                templateUrl: 'memberEditor.html',
                backdrop: 'static',
                controller: ['$modalInstance', '$scope', function($mi, $scope) {
                    $scope.schema = schema;
                    $scope.member = {
                        extattr: {}
                    };
                    $scope.canShow = function(name) {
                        return schema['attr_' + name].charAt(0) === '0';
                    };
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close($scope.member);
                    };
                }]
            }).result.then(function(member) {
                http2.post(baseURL + 'memberAdd?site=' + $scope.siteId + '&userid=' + userid + '&schema=' + schema.id, member, function(rsp) {
                    member = rsp.data;
                    member.extattr = JSON.parse(decodeURIComponent(member.extattr.replace(/\+/g, '%20')));
                    member.schema = schema;
                    !$scope.members && ($scope.members = []);
                    $scope.members.push(member);
                });
            });
        };
        $scope.editMember = function(member) {
            $modal.open({
                templateUrl: 'memberEditor.html',
                backdrop: 'static',
                controller: ['$modalInstance', '$scope', function($mi, $scope) {
                    $scope.schema = member.schema;
                    $scope.member = angular.copy(member);
                    $scope.canShow = function(name) {
                        return $scope.schema && $scope.schema['attr_' + name].charAt(0) === '0';
                    };
                    $scope.close = function() {
                        $mi.dismiss();
                    };
                    $scope.ok = function() {
                        $mi.close({
                            action: 'update',
                            data: $scope.member
                        });
                    };
                    $scope.remove = function() {
                        $mi.close({
                            action: 'remove'
                        });
                    };
                }]
            }).result.then(function(rst) {
                if (rst.action === 'update') {
                    var newData, i, ea;
                    newData = {
                        verified: rst.data.verified,
                        name: rst.data.name,
                        mobile: rst.data.mobile,
                        email: rst.data.email,
                        email_verified: rst.data.email_verified,
                        extattr: rst.data.extattr
                    };
                    http2.post(baseURL + 'memberUpd?id=' + member.id, newData, function(rsp) {
                        angular.extend(member, newData);
                    });
                } else if (rst.action === 'remove') {
                    http2.get(baseURL + 'memberDel?id=' + member.id, function() {
                        $scope.members.splice($scope.members.indexOf(member), 1);
                    });
                }
            });
        };
    }]);
})();