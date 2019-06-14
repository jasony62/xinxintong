'use strict';
require('./enroll.public.css');
require('./_asset/ui.bottom.nav.js');
require('./_asset/ui.repos.js');
require('./_asset/ui.tag.js');
require('./_asset/ui.topic.js');
require('./_asset/ui.assoc.js');

window.moduleAngularModules = ['nav.bottom.ui', 'repos.ui.enroll', 'tag.ui.enroll', 'topic.ui.enroll', 'assoc.ui.enroll', 'ngRoute'];

var ngApp = require('./main.js');
ngApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider
        .when('/rest/site/fe/matter/enroll/people/favor', { template: require('./people/favor.html'), controller: 'ctrlPeopleFavor' })
}]);
ngApp.factory('TopicRepos', ['http2', '$q', '$sce', 'tmsLocation', function(http2, $q, $sce, LS) {
    var TopicRepos, _ins;
    TopicRepos = function(oApp, oTopic) {
        var oShareableSchemas;
        oShareableSchemas = {};
        oApp.dynaDataSchemas.forEach(function(oSchema) {
            if (oSchema.shareable && oSchema.shareable === 'Y') {
                oShareableSchemas[oSchema.id] = oSchema;
            }
        });
        this.oApp = oApp;
        this.oTopic = oTopic;
        this.shareableSchemas = oShareableSchemas;
        this.oPage = {};
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
        url = LS.j('repos/recordByTopic', 'site', 'app') + '&topic=' + this.oTopic.id;

        http2.get(url, { page: this.oPage }).then(function(oResult) {
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
            return new TopicRepos(oApp, oTopic);
        }
    };
}]);
ngApp.controller('ctrlPeople', ['$scope', '$location', 'tmsLocation', 'http2', function($scope, $location, LS, http2) {
    $scope.activeNav = '';
    $scope.viewTo = function(event, subView) {
        if (subView.type === 'user') {
            location.href = '/rest/site/fe/user?site=' + $scope.oApp.siteid;
        } else {
            $scope.activeView = subView;
        }
    };
    $scope.$on('$locationChangeSuccess', function(event, currentRoute) {
        var subView = currentRoute.match(/([^\/]+?)\?/);
        $scope.subView = subView[1] === 'favor' ? 'favor' : subView[1];
    });
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        $scope.oApp = params.app;
        /* 请求导航 */
        http2.get(LS.j('navs', 'site', 'app')).then(function(rsp) {
            $scope.navs = rsp.data;
        });
    });
}]);
ngApp.controller('ctrlPeopleFavor', ['$scope', '$uibModal', 'http2', 'tmsLocation', function($scope, $uibModal, http2, LS) {
    var _oApp;
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
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        /* 设置页面分享信息 */
        $scope.setSnsShare(); // 应该禁止分享
        /*页面阅读日志*/
        $scope.logAccess();
    });
}]);
ngApp.controller('ctrlPeopleUser', ['$scope', 'http2', 'tmsLocation', function($scope, http2, LS) {}]);
/**
 * 记录
 */
ngApp.controller('ctrlRepos', ['$scope', '$sce', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'picviewer', 'noticebox', 'enlTag', 'enlTopic', function($scope, $sce, $q, $uibModal, http2, LS, $timeout, picviewer, noticebox, enlTag, enlTopic) {
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
    var _oApp, _oPage, _oFilter, _oCriteria, _oShareableSchemas, _coworkRequireLikeNum, shareby;
    shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
    _coworkRequireLikeNum = 0; // 记录获得多少个赞，才能开启协作填写
    $scope.page = _oPage = {};
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
        url = LS.j('favor/list', 'site', 'app');
        $scope.reposLoading = true;
        http2.post(url, _oCriteria, { page: _oPage }).then(function(result) {
            if (result.data.records) {
                result.data.records.forEach(function(oRecord) {
                    if (_coworkRequireLikeNum > oRecord.like_num) {
                        oRecord._coworkRequireLikeNum = (_coworkRequireLikeNum > oRecord.like_num ? _coworkRequireLikeNum - oRecord.like_num : 0);
                    }
                    oRecord._canAgree = fnCanAgreeRecord(oRecord, $scope.user);
                    $scope.repos.push(oRecord);
                });
            }
            $timeout(function() {
                var imgs;
                if (imgs = document.querySelectorAll('.data img')) {
                    picviewer.init(imgs);
                }
            });
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
    $scope.dislikeRecord = function(oRecord) {
        var url;
        url = LS.j('record/dislike', 'site');
        url += '&ek=' + oRecord.enroll_key;
        http2.get(url).then(function(rsp) {
            oRecord.dislike_log = rsp.data.dislike_log;
            oRecord.dislike_num = rsp.data.dislike_num;
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
        var url;
        url = LS.j('', 'site', 'app') + '&ek=' + oRecord.enroll_key + '&page=share';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
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
        _oApp.dynaDataSchemas.forEach(function(schema) {
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
ngApp.controller('ctrlTopic', ['$scope', '$uibModal', 'http2', 'tmsLocation', 'noticebox', 'enlAssoc', function($scope, $uibModal, http2, LS, noticebox, enlAssoc) {
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
                $scope2.page = _oTopicRepos.oPage;
                $scope2.repos = _oTopicRepos.repos;
                $scope2.schemas = _oTopicRepos.shareableSchemas;
                _oTopicRepos.list(1).then(function() {});
                $scope2.quitRec = function(oRecord, index) {
                    http2.post(LS.j('topic/removeRec', 'site') + '&topic=' + oTopic.id, { id_in_topic: oRecord.id_in_topic }).then(function(rsp) {
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
        var url, shareby;
        url = LS.j('', 'site', 'app') + '&topic=' + oTopic.id + '&page=share';
        shareby = location.search.match(/shareby=([^&]*)/) ? location.search.match(/shareby=([^&]*)/)[1] : '';
        if (shareby) {
            url += '&shareby=' + shareby;
        }
        location.href = url;
    };
    $scope.copyTopic = function(oTopic) {
        enlAssoc.copy($scope.app, { id: oTopic.id, type: 'topic' });
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