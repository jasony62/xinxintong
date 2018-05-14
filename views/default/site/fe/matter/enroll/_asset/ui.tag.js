'use strict';

var ngMod = angular.module('tag.ui.enroll', []);
ngMod.factory('enlTag', ['$q', '$uibModal', 'http2', 'tmsLocation', function($q, $uibModal, http2, LS) {
    var _oInstance = {};
    _oInstance.assignTag = function(oRecord) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: require('./assign-tag.html'),
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                var _aCheckedTagIds;
                _aCheckedTagIds = [];
                $scope.newTag = {};
                $scope.checkTag = function(oTag) {
                    oTag.checked ? _aCheckedTagIds.push(oTag.tag_id) : _aCheckedTagIds.splice(_aCheckedTagIds.indexOf(oTag.tag_id), 1);
                };
                $scope.addTag = function() {
                    http2.post(LS.j('tag/submit', 'site', 'app'), $scope.newTag).then(function(rsp) {
                        var oNewTag;
                        $scope.newTag = {};
                        oNewTag = rsp.data;
                        $scope.tags.splice(0, 0, rsp.data);
                        oNewTag.checked = true;
                        $scope.checkTag(oNewTag);
                    });
                };
                $scope.cancel = function() { $mi.dismiss(); };
                $scope.ok = function() { $mi.close(_aCheckedTagIds); };
                http2.get(LS.j('tag/byRecord', 'site') + '&record=' + oRecord.id).then(function(rsp) {
                    rsp.data.user.forEach(function(oTag) {
                        _aCheckedTagIds.push(oTag.tag_id);
                    });
                    http2.get(LS.j('tag/list', 'site', 'app') + '&public=Y').then(function(rsp) {
                        rsp.data.forEach(function(oTag) {
                            oTag.checked = _aCheckedTagIds.indexOf(oTag.tag_id) !== -1;
                        });
                        $scope.tags = rsp.data;
                    });
                });
            }],
            backdrop: 'static',
            windowClass: 'modal-opt-topic auto-height',
        }).result.then(function(aCheckedTagIds) {
            http2.post(LS.j('tag/assign', 'site'), { record: oRecord.id, tag: aCheckedTagIds }).then(function(rsp) {
                oDeferred.resolve(rsp);
            });
        });
        return oDeferred.promise;
    };

    return _oInstance;
}]);