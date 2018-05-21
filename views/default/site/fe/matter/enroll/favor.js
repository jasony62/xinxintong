'use strict';
require('./favor.css');

require('./_asset/ui.repos.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');

window.moduleAngularModules = ['repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll'];

var ngApp = require('./main.js');
ngApp.oUtilSchema = require('../_module/schema.util.js');
ngApp.factory('TopicRepos', ['http2', '$q', '$sce', 'tmsLocation', function(http2, $q, $sce, LS) {
    var TopicRepos, _ins;
    TopicRepos = function(oApp, oTopic) {
        var oShareableSchemas;
        oShareableSchemas = {};
        oApp.dataSchemas.forEach(function(oSchema) {
            if (oSchema.shareable && oSchema.shareable === 'Y') {
                oShareableSchemas[oSchema.id] = oSchema;
            }
        });
        this.oApp = oApp;
        this.oTopic = oTopic;
        this.shareableSchemas = oShareableSchemas;
        this.oPage = {
            at: 1,
            size: 12,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        };
        this.repos = [];
    };
    TopicRepos.prototype.list = function(pageAt) {
        var url, oDeferred, _this;
        oDeferred = $q.defer();
        _this = this;
        if (pageAt) {
            this.oPage.at = pageAt;
        } else {
            this.oPage.at++;
        }
        if (this.oPage.at == 1) {
            this.repos.splice(0, this.repos.length);
            this.oPage.total = 0;
        }
        url = LS.j('repos/recordByTopic', 'site', 'app') + '&topic=' + this.oTopic.id + this.oPage.j();

        http2.post(url, {}).then(function(oResult) {
            _this.oPage.total = oResult.data.total;
            if (oResult.data.records) {
                oResult.data.records.forEach(function(oRecord) {
                    _this.repos.push(oRecord);
                });
            }
            oDeferred.resolve(oResult);
        });

        return oDeferred.promise;
    };
    return {
        ins: function(oApp, oTopic) {
            _ins = _ins ? _ins : new TopicRepos(oApp, oTopic);
            return _ins;
        }
    };
}]);
ngApp.controller('ctrlFavor', ['$scope', '$uibModal', 'http2', 'tmsLocation', function($scope, $uibModal, http2, LS) {
    if (location.hash && /repos|tag|topic/.test(location.hash)) {
        $scope.subView = location.hash.substr(1) + '.html';
    } else {
        $scope.subView = 'repos.html';
    }
    $scope.addTopic = function() {
        $uibModal.open({
            templateUrl: 'editTopic.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _oCreated;
                $scope2.topic = _oCreated = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close(_oCreated); };
            }],
            backdrop: 'static',
            windowClass: 'auto-height',
        }).result.then(function(oCreated) {
            http2.post(LS.j('topic/add', 'site', 'app'), oCreated).then(function(rsp) {
                $scope.$broadcast('xxt.matter.enroll.favor.topic.add', rsp.data);
            });
        });
    };
    $scope.addTag = function() {
        $scope.$broadcast('xxt.matter.enroll.favor.tag.add');
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        /* 设置页面分享信息 */
        $scope.setSnsShare(); // 应该禁止分享
        /*设置页面导航*/
        var oAppNavs = {};
        if (oApp.can_repos === 'Y') {
            oAppNavs.repos = {};
        }
        if (oApp.can_rank === 'Y') {
            oAppNavs.rank = {};
        }
        if (oApp.scenarioConfig && oApp.scenarioConfig.can_action === 'Y') {
            /* 设置活动事件提醒 */
            http2.get(LS.j('notice/count', 'site', 'app')).then(function(rsp) {
                $scope.noticeCount = rsp.data;
            });
            oAppNavs.action = {};
        }
        if (Object.keys(oAppNavs).length) {
            $scope.appNavs = oAppNavs;
        }
    });
}]);
/**
 * 记录
 */
ngApp.controller('ctrlRepos', ['$scope', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'tmsDynaPage', 'noticebox', 'enlTag', 'enlTopic', function($scope, $sce, $q, $uibModal, http2, LS, $timeout, tmsDynaPage, noticebox, enlTag, enlTopic) {
    /* 是否可以对记录进行表态 */
    function fnCanAgreeRecord(oRecord, oUser) {
        if (oUser.is_leader) {
            if (oUser.is_leader === 'S') {
                return true;
            }
            if (oUser.is_leader === 'Y') {
                if (oUser.group_id === oRecord.group_id) {
                    return true;
                } else if (oUser.is_editor && oUser.is_editor === 'Y') {
                    return true;
                }
            }
        }
        return false;
    }
    var _oApp, _oPage, _oFilter, _oCriteria, _oShareableSchemas, _coworkRequireLikeNum, _oMocker;
    _coworkRequireLikeNum = 0; // 记录获得多少个赞，才能开启协作填写
    $scope.page = _oPage = { at: 1, size: 12, total: 0 };
    $scope.filter = _oFilter = {}; // 过滤条件
    $scope.criteria = _oCriteria = { rid: 'all', creator: false, favored: true, agreed: 'all', orderby: 'lastest' }; // 数据查询条件
    $scope.schemas = _oShareableSchemas = {}; // 支持分享的题目
    $scope.repos = []; // 分享的记录
    $scope.reposLoading = false;
    $scope.recordList = function(pageAt) {
        var url, deferred;
        deferred = $q.defer();
        if (pageAt) {
            _oPage.at = pageAt;
        } else {
            _oPage.at++;
        }
        if (_oPage.at == 1) {
            $scope.repos = [];
            _oPage.total = 0;
        }
        url = LS.j('repos/recordList', 'site', 'app');
        url += '&page=' + _oPage.at + '&size=' + _oPage.size;
        $scope.reposLoading = true;
        http2.post(url, _oCriteria).then(function(result) {
            _oPage.total = result.data.total;
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    if (_coworkRequireLikeNum > oRecord.like_num) {
                        oRecord._coworkRequireLikeNum = (_coworkRequireLikeNum > oRecord.like_num ? _coworkRequireLikeNum - oRecord.like_num : 0);
                    }
                    oRecord._canAgree = fnCanAgreeRecord(oRecord, $scope.user);
                    $scope.repos.push(oRecord);
                });
            }
            tmsDynaPage.loadScript(['/static/js/hammer.min.js', '/asset/js/xxt.ui.picviewer.js']);
            $scope.reposLoading = false;
            deferred.resolve(result);
        });

        return deferred.promise;
    }
    $scope.likeRecord = function(oRecord) {
        var url;
        url = LS.j('record/like', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.like_log = rsp.data.like_log;
            oRecord.like_num = rsp.data.like_num;
        });
    };
    $scope.remarkRecord = function(oRecord) {
        var url;
        url = LS.j('', 'site', 'app');
        url += '&ek=' + oRecord.enroll_key;
        url += '&page=cowork#remarks';
        location.href = url;
    };
    $scope.setAgreed = function(oRecord, value) {
        var url;
        if (oRecord.agreed !== value) {
            url = LS.j('record/agree', 'site');
            url += '&ek=' + oRecord.enroll_key;
            url += '&value=' + value;
            http2.get(url).then(function(rsp) {
                oRecord.agreed = value;
            });
        }
    };
    $scope.favorRecord = function(oRecord) {
        var url;
        if (!oRecord.favored) {
            url = LS.j('favor/add', 'site');
            url += '&ek=' + oRecord.enroll_key;
            http2.get(url).then(function(rsp) {
                oRecord.favored = true;
            });
        } else {
            noticebox.confirm('取消收藏，确定？').then(function() {
                url = LS.j('favor/remove', 'site');
                url += '&ek=' + oRecord.enroll_key;
                http2.get(url).then(function(rsp) {
                    delete oRecord.favored;
                    $scope.repos.splice($scope.repos.indexOf(oRecord), 1);
                    _oPage.total--;
                });
            });
        }
    };
    $scope.shareRecord = function(oRecord) {
        location.href = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
    };
    $scope.editRecord = function(event, oRecord) {
        if (oRecord.userid !== $scope.user.uid) {
            noticebox.warn('不允许编辑其他用户提交的记录');
            return;
        }
        var page;
        for (var i in $scope.app.pages) {
            var oPage = $scope.app.pages[i];
            if (oPage.type === 'I') {
                page = oPage.name;
                break;
            }
        }
        $scope.gotoPage(event, page, oRecord.enroll_key);
    };
    $scope.shiftAgreed = function(agreed) {
        _oCriteria.agreed = agreed;
        $scope.recordList(1);
    };
    /**
     * 选取专题
     */
    $scope.assignTopic = function(oRecord) {
        enlTopic.assignTopic(oRecord);
    };
    /**
     * 选取标签
     */
    $scope.assignTag = function(oRecord) {
        enlTag.assignTag(oRecord).then(function(rsp) {
            if (rsp.data.user && rsp.data.user.length) {
                oRecord.userTags = rsp.data.user;
            } else {
                delete oRecord.userTags;
            }
        });
    };
    $scope.spyRecordsScroll = true; // 监控滚动事件
    $scope.recordsScrollToBottom = function() {
        if ($scope.repos.length < $scope.page.total) {
            $scope.recordList().then(function() {
                $timeout(function() {
                    if ($scope.repos.length < $scope.page.total) {
                        $scope.spyRecordsScroll = true;
                    }
                });
            });
        }
    };
    $scope.$watch('app', function(oApp) {
        if (!oApp) return;
        _oApp = oApp;
        /* 活动任务 */
        if (_oApp.actionRule) {
            /* 开启协作填写需要的点赞数 */
            if (_oApp.actionRule.record && _oApp.actionRule.record.cowork && _oApp.actionRule.record.cowork.pre) {
                if (_oApp.actionRule.record.cowork.pre.record && _oApp.actionRule.record.cowork.pre.record.likeNum !== undefined) {
                    _coworkRequireLikeNum = parseInt(_oApp.actionRule.record.cowork.pre.record.likeNum);
                }
            }
        }
        _oApp.dataSchemas.forEach(function(schema) {
            if (schema.shareable && schema.shareable === 'Y') {
                _oShareableSchemas[schema.id] = schema;
            }
        });
        $scope.recordList(1);
    });
}]);
/**
 * 专题
 */
ngApp.controller('ctrlTopic', ['$scope', '$uibModal', 'http2', 'tmsLocation', 'noticebox', function($scope, $uibModal, http2, LS, noticebox) {
    var _topics;
    $scope.editTopic = function(oTopic) {
        $uibModal.open({
            templateUrl: 'editTopic.html',
            controller: ['$scope', '$uibModalInstance', 'TopicRepos', function($scope2, $mi, TopicRepos) {
                var _oCached, _oTopicRepos, _oUpdated;
                _oCached = angular.copy(oTopic);
                _oUpdated = {};
                $scope2.topic = _oCached;
                $scope2.countUpdated = 0;
                _oTopicRepos = TopicRepos.ins($scope.app, oTopic);
                $scope2.page = _oTopicRepos.page;
                $scope2.repos = _oTopicRepos.repos;
                $scope2.schemas = _oTopicRepos.shareableSchemas;
                _oTopicRepos.list(1).then(function() {});
                $scope2.quitRec = function(oRecord, index) {
                    http2.post(LS.j('topic/removeRec', 'site') + '&topic=' + oTopic.id, { record: oRecord.id }).then(function(rsp) {
                        $scope2.repos.splice(index, 1);
                    });
                };
                $scope2.moveRec = function(oRecord, step, index) {
                    http2.post(LS.j('topic/updateSeq', 'site') + '&topic=' + oTopic.id, { record: oRecord.id, step: step }).then(function(rsp) {
                        $scope2.repos.splice(index, 1);
                        $scope2.repos.splice(index + step, 0, oRecord);
                    });
                };
                $scope2.update = function(prop) {
                    if (!_oUpdated[prop]) $scope2.countUpdated++;
                    _oUpdated[prop] = _oCached[prop];
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() { $mi.close(_oUpdated); };
            }],
            backdrop: 'static',
            windowClass: 'modal-edit-topic auto-height',
        }).result.then(function(oUpdated) {
            if (oUpdated && Object.keys(oUpdated).length) {
                http2.post(LS.j('topic/update', 'site') + '&topic=' + oTopic.id, oUpdated).then(function(rsp) {
                    angular.extend(oTopic, oUpdated);
                });
            }
        });
    };
    $scope.removeTopic = function(oTopic, index) {
        noticebox.confirm('删除专题【' + oTopic.title + '】，确定？').then(function() {
            http2.get(LS.j('topic/remove', 'site') + '&topic=' + oTopic.id).then(function(rsp) {
                _topics.splice(index, 1);
            });
        });
    };
    $scope.shareTopic = function(oTopic) {
        location.href = LS.j('', 'site', 'app') + '&topic=' + oTopic.id + '&page=share';
    };
    $scope.gotoTopic = function(oTopic) {
        location.href = LS.j('', 'site', 'app') + '&topic=' + oTopic.id + '&page=topic';
    };
    $scope.$on('xxt.matter.enroll.favor.topic.add', function(event, oNewTopic) {
        _topics.splice(0, 0, oNewTopic);
    });
    http2.get(LS.j('topic/list', 'site', 'app')).then(function(rsp) {
        $scope.topics = _topics = rsp.data.topics;
    });
}]);
/**
 * 标签
 */
ngApp.controller('ctrlTag', ['$scope', 'http2', 'tmsLocation', function($scope, http2, LS) {
    var _tags;
    $scope.$on('xxt.matter.enroll.favor.tag.add', function(event) {
        $scope.addTag();
    });
    $scope.addTag = function() {
        $scope.newTag = {};
    };
    $scope.update = function(oTag, prop) {
        var oUpdated;
        oUpdated = {};
        oUpdated[prop] = oTag[prop];
        http2.post(LS.j('tag/update', 'site', 'app') + '&tag=' + oTag.tag_id, oUpdated).then(function(rsp) {});
    };
    $scope.submitNewTag = function() {
        http2.post(LS.j('tag/submit', 'site', 'app'), $scope.newTag).then(function(rsp) {
            delete $scope.newTag;
            _tags.splice(0, 0, rsp.data);
        });
    };
    $scope.cancelNewTag = function() {
        delete $scope.newTag;
    };
    if ($scope.app && $scope.user) {
        var oActionRule;
        if ($scope.user.is_leader && /S|Y/.test($scope.user.is_leader)) {
            $scope.canSetPublic = true;
        }
        if (false === $scope.canSetPublic) {
            if (oActionRule = $scope.app.actionRule) {
                if (oActionRule.tag && oActionRule.tag.public && oActionRule.tag.public.pre && oActionRule.tag.public.pre.editor) {
                    if ($scope.user.is_editor && $scope.user.is_editor === 'Y') {
                        $scope.canSetPublic = true;
                    }
                }
            }
        }
    }
    http2.get(LS.j('tag/list', 'site', 'app')).then(function(rsp) {
        $scope.tags = _tags = rsp.data;
    });
}]);