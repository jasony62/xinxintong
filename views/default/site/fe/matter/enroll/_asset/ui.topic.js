'use strict';

var ngMod = angular.module('topic.ui.enroll', []);
ngMod.factory('enlTopic', ['$q', '$uibModal', 'http2', 'tmsLocation', function($q, $uibModal, http2, LS) {
    var _oInstance = {};
    _oInstance.assignTopic = function(oRecord, topics) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: require('./assign-topic.html'),
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _aCheckedTopicIds;
                _aCheckedTopicIds = [];
                $scope2.checkTopic = function(oTopic) {
                    oTopic.checked ? _aCheckedTopicIds.push(oTopic.id) : _aCheckedTopicIds.splice(_aCheckedTopicIds.indexOf(oTopic.id), 1);
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close(_aCheckedTopicIds); };
                http2.get(LS.j('topic/byRecord', 'site') + '&record=' + oRecord.id).then(function(rsp) {
                    rsp.data.forEach(function(oTopic) {
                        _aCheckedTopicIds.push(oTopic.topic_id);
                    });
                    var oDeferredTopics = $q.defer();
                    oDeferredTopics.promise.then(function(topics) {
                        topics.forEach(function(oTopic) {
                            oTopic.checked = _aCheckedTopicIds.indexOf(oTopic.id) !== -1;
                        });
                        $scope2.topics = topics;
                    });
                    if (topics) {
                        oDeferredTopics.resolve(topics);
                    } else {
                        http2.get(LS.j('topic/list', 'site', 'app')).then(function(rsp) {
                            oDeferredTopics.resolve(rsp.data.topics);
                        });
                    }
                });
            }],
            backdrop: 'static',
            windowClass: 'modal-opt-topic auto-height',
        }).result.then(function(aCheckedTopicIds) {
            http2.post(LS.j('topic/assign', 'site') + '&record=' + oRecord.id, { topic: aCheckedTopicIds }).then(function(rsp) {
                oDeferred.resolve(rsp);
            });
        });
        return oDeferred.promise;
    };

    return _oInstance;
}]);