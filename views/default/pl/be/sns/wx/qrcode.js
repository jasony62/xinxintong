'use strict';
define(['main'], function(ngApp) {
    ngApp.provider.controller('ctrlQrcode', ['$scope', 'http2', 'matterTypes', function($scope, http2, matterTypes) {
        $scope.matterTypes = matterTypes;
        $scope.create = function() {
            http2.get('/rest/pl/be/sns/wx/qrcode/create?site=platform').then(function(rsp) {
                $scope.calls.splice(0, 0, rsp.data);
                $scope.edit($scope.calls[0]);
            });
        };
        $scope.update = function(name) {
            var p = {};
            p[name] = $scope.editing[name];
            http2.post('/rest/pl/be/sns/wx/qrcode/update?site=platform&id=' + $scope.editing.id, p);
        };
        $scope.edit = function(call) {
            if (call && call.matter === undefined && call.matter_id && call.matter_type) {
                http2.get('/rest/pl/be/sns/wx/qrcode/matter?site=platform&id=' + call.matter_id + '&type=' + call.matter_type).then(function(rsp) {
                    var matter = rsp.data;
                    $scope.editing.matter = matter;
                });
            };
            $scope.editing = call;
        };
        $scope.setReply = function() {
            mattersgallery.open($scope.wx.plid, function(aSelected, matterType) {
                if (aSelected.length === 1) {
                    var matter = aSelected[0],
                        p = {
                            matter_id: matter.id,
                            matter_type: matterType
                        };
                    http2.post('/rest/pl/be/sns/wx/qrcode/update?site=platform&id=' + $scope.editing.id, p).then(function(rsp) {
                        $scope.editing.matter = aSelected[0];
                    });
                }
            }, {
                matterTypes: $scope.matterTypes,
                hasParent: false,
                singleMatter: true
            });
        };
        http2.get('/rest/pl/be/sns/wx/qrcode/list?site=platform').then(function(rsp) {
            $scope.calls = rsp.data;
            if ($scope.calls.length > 0) {
                $scope.edit($scope.calls[0]);
            } else {
                $scope.edit(null);
            }
        });
    }]);
});