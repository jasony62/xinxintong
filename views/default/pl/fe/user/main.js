/**
 * Created by lishuai on 2017/3/23.
 */
define(['frame'], function(ngApp) {
    'use strict';

    ngApp.provider.controller('ctrlMain', ['$scope', 'http2', '$uibModal', 'noticebox', function($scope, http2, $uibModal, noticebox) {
        var baseURL = '/rest/pl/fe/site/user/';
        //获取同步信息
        $scope.registerYes = [];
        $scope.registerNo = [];
        //有时间改用promise
        http2.get('/rest/pl/fe/site/member/schema/list?site=' + $scope.siteId).then(function(rsp) {
            $scope.memberSchemas = rsp.data;
            http2.get(baseURL + 'profile/get?site=' + $scope.siteId + '&userid=' + $scope.userId).then(function(rsp) {
                var members, mapMembers = {};
                $scope.user = rsp.data.user;
                if (rsp.data.members) {
                    members = rsp.data.members;
                    angular.forEach(members, function(member) {
                        if (JSON.stringify(member.extattr) !== "{}") {
                            for (var i in member.extattr) {
                                member.extattr = JSON.parse(decodeURIComponent(member.extattr[i].replace(/\+/g, '%20')));
                            }
                        }
                        mapMembers[member.schema_id] = member;
                    });
                    angular.forEach($scope.memberSchemas, function(memberSchema) {
                        memberSchema.member = mapMembers[memberSchema.id];
                    });
                    angular.forEach($scope.memberSchemas, function(memberSchema) {
                        memberSchema.member ? $scope.registerYes.push(memberSchema) : $scope.registerNo.push(memberSchema);
                    });
                }
            });
        });
        $scope.canFieldShow = function(memberSchema, name) {
            return memberSchema['attr_' + name].charAt(0) === '0';
        };
        //增加-ok
        $scope.addMember = function(schema, i) {
            $uibModal.open({
                templateUrl: 'memberEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
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
                http2.post(baseURL + 'profile/memberAdd?site=' + $scope.siteId + '&userid=' + $scope.userId + '&schema=' + schema.id, member).then(function(rsp) {
                    member = rsp.data;
                    member.extattr = JSON.parse(decodeURIComponent(member.extattr.replace(/\+/g, '%20')));
                    schema.member = member;
                    $scope.registerNo.splice(i, 1);
                    $scope.registerYes.splice(0, 0, schema);
                    noticebox.success('完成');
                    //!$scope.members && ($scope.members = []);
                    //$scope.members.push(member);
                });
            });
        };
        //修改-ok
        $scope.editMember = function(memberSchema) {
            $uibModal.open({
                templateUrl: 'memberEditor.html',
                backdrop: 'static',
                controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
                    $scope.schema = memberSchema;
                    $scope.member = angular.copy($scope.schema.member);
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
                    http2.post(baseURL + 'profile/memberUpd?site=' + $scope.siteId + '&id=' + memberSchema.member.id, newData).then(function(rsp) {
                        angular.extend(memberSchema.member, newData);
                        noticebox.success('完成');
                    });
                } else if (rst.action === 'remove') {
                    http2.get(baseURL + 'profile/memberDel?site=' + $scope.siteId + '&id=' + memberSchema.member.id).then(function() {
                        $scope.members.splice($scope.members.indexOf(member), 1);
                    });
                }
            });
        };
        $scope.sync = function(openId, type) {
            //普通用户的标识，对当前公众号唯一 openid
            var url = baseURL + 'fans/refreshOne?site=' + $scope.siteId + '&openid=' + openId;
            http2.get(url).then(function(rsp) {
                type === 'wx' ? $scope.wx = rsp.data : type === 'qy' ? $scope.qy = rsp.data : $scope.yx = rsp.data;
                noticebox.success('完成同步');
            })
        }
    }])
});