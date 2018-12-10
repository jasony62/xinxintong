'use strict';
/**
 * 复制的时候保存在本地存储中，黏贴的时候取出
 * 支持跨活动进行复制
 */
var ngMod = angular.module('assoc.ui.enroll', []);
ngMod.service('enlAssoc', ['$q', '$uibModal', 'noticebox', 'http2', 'tmsLocation', function($q, $uibModal, noticebox, http2, LS) {
    function fnGetEntitySketch(oEntity) {
        var defer, url;
        defer = $q.defer();
        if (oEntity.type === 'record') {
            url = LS.j('record/sketch', 'site') + '&record=' + oEntity.id;
        } else if (oEntity.type === 'topic') {
            url = LS.j('topic/sketch', 'site') + '&topic=' + oEntity.id
        }
        if (url) {
            http2.get(url).then(function(rsp) {
                defer.resolve(rsp.data)
            });
        } else {
            defer.reject();
        }
        return defer.promise;
    }

    var _self, _cacheKey;
    _self = this;
    _cacheKey = '/xxt/site/app/enroll/assoc';
    this.isSupport = function() {
        return !!window.sessionStorage;
    };
    this.hasCache = function() {
        return !!window.sessionStorage.getItem(_cacheKey);
    };
    this.copy = function(oApp, oEntity) {
        var oDeferred, oCache;
        oDeferred = $q.defer();
        if (window.sessionStorage) {
            oCache = {
                app: {
                    id: oApp.id,
                    title: oApp.title
                },
                entity: {
                    id: oEntity.id,
                    type: oEntity.type
                }
            };
            oCache.entity = oEntity;
            window.sessionStorage.setItem(_cacheKey, JSON.stringify(oCache));
            noticebox.info('完成复制');
            oDeferred.resolve();
        }
        return oDeferred.promise;
    };
    this.paste = function(oUser, oRecord, oEntity) {
        var oDeferred, oCache;
        oDeferred = $q.defer();
        if (window.sessionStorage) {
            if (oCache = window.sessionStorage.getItem(_cacheKey)) {
                oCache = JSON.parse(oCache);
                $uibModal.open({
                    template: require('./assoc-link.html'),
                    controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                        var _oAssoc;
                        $scope.user = oUser;
                        $scope.cache = oCache;
                        $scope.assoc = _oAssoc = { public: 'N' };
                        $scope.cancel = function() { $mi.dismiss(); };
                        $scope.ok = function() {
                            var oPosted = {};
                            oPosted.assoc = _oAssoc;
                            oPosted.entityA = { id: oEntity.id, type: oEntity.type };
                            oPosted.entityB = oCache.entity;
                            http2.post(LS.j('assoc/link', 'site') + '&ek=' + oRecord.enroll_key, oPosted).then(function(rsp) {
                                if (!_oAssoc.retainCopied) {
                                    window.sessionStorage.removeItem(_cacheKey);
                                }
                                $mi.close(rsp.data);
                            });
                        };
                        fnGetEntitySketch(oCache.entity).then(function(oSketch) {
                            _oAssoc.text = oSketch.title;
                        });
                    }],
                    backdrop: 'static',
                    windowClass: 'auto-height',
                }).result.then(function(oNewAssoc) {
                    oDeferred.resolve(oNewAssoc);
                });
            } else {
                noticebox.warn('没有粘贴的内容。可在共享页或讨论页【复制】内容，然后通过【粘贴】建立数据间的关联。');
                oDeferred.reject();
            }
        }
        return oDeferred.promise;
    };
    this.update = function(oUser, oAssoc) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: require('./assoc-update.html'),
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                var oCache, oUpdated;
                oUpdated = {};
                $scope.user = oUser;
                $scope.assoc = oCache = { text: oAssoc.assoc_text, reason: oAssoc.assoc_reason, public: oAssoc.public };
                $scope.countUpdated = 0;
                $scope.update = function(prop) {
                    if (!oUpdated[prop]) $scope.countUpdated++;
                    oUpdated[prop] = oCache[prop];
                };
                $scope.ok = function() {
                    if (oCache.updatePublic) oUpdated.updatePublic = true;
                    http2.post(LS.j('assoc/update', 'site') + '&assoc=' + oAssoc.id, oUpdated).then(function(rsp) {
                        oAssoc.assoc_text = oCache.text;
                        oAssoc.assoc_reason = oCache.reason;
                        oAssoc.public = oCache.public;
                        $mi.close();
                    });
                };
                $scope.cancel = function() { $mi.dismiss(); };
            }],
            backdrop: 'static',
            windowClass: 'auto-height',
        }).result.then(function() {
            oDeferred.resolve();
        });
        return oDeferred.promise;
    };
    /* 关联应用内素材 */
    this.assocMatter = function(oUser, oRecord, oEntity) {
        var oDeferred;
        oDeferred = $q.defer();
        $uibModal.open({
            template: require('./assoc-matter.html'),
            controller: ['$scope', '$uibModalInstance', function($scope, $mi) {
                var _oResult, _oPage, _oAssoc;
                $scope.result = _oResult = { type: 'article' };
                $scope.page = _oPage = {};
                $scope.assoc = _oAssoc = { public: 'Y' };
                $scope.doSearch = function() {
                    var url;
                    url = '/rest/pl/fe/matter/article/list';
                    http2.post(url, { byTitle: _oResult.title }, { page: _oPage }).then(function(rsp) {
                        $scope.matters = rsp.data.docs;
                        if ($scope.matters.length)
                            _oResult.matter = $scope.matters[0];
                    });
                };
                $scope.ok = function() {
                    var oPosted, oMatter;
                    if (oMatter = _oResult.matter) {
                        _oAssoc.text = oMatter.title;
                        oPosted = {};
                        oPosted.assoc = _oAssoc;
                        oPosted.entityA = { id: oEntity.id, type: oEntity.type };
                        oPosted.entityB = { id: oMatter.id, type: oMatter.type };
                        http2.post(LS.j('assoc/link', 'site') + '&ek=' + oRecord.enroll_key, oPosted).then(function(rsp) {
                            $mi.close(rsp.data);
                        });
                    }
                };
                $scope.cancel = function() { $mi.dismiss(); };
            }],
            backdrop: 'static',
            windowClass: 'auto-height',
        }).result.then(function(oAssoc) {
            oDeferred.resolve(oAssoc);
        });
        return oDeferred.promise;
    };
}]);