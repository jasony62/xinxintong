'use strict';
require('./enroll.public.css');
require('./task.css');
require('./_asset/ui.round.js');

window.moduleAngularModules = ['round.ui.enroll'];

var ngApp = require('./main.js');
ngApp.controller('ctrlTask', ['$scope', '$parse', '$q', '$uibModal', 'http2', 'tmsLocation', '$timeout', 'noticebox', 'enlRound', function($scope, $parse, $q, $uibModal, http2, LS, $timeout, noticebox, enlRound) {
    function fnGetTasks(oRound) {
        _tasks.splice(0, _tasks.length);
        http2.get(LS.j('task/list', 'site', 'app') + '&rid=' + oRound.rid).then(function(rsp) {
            if (rsp.data.length) {
                rsp.data.forEach(function(oTask) {
                    switch (oTask.rule.type) {
                        case 'question':
                            _tasks.push({ msg: '有提问任务', data: oTask });
                            break;
                        case 'answer':
                            _tasks.push({ msg: '有回答任务', data: oTask });
                            break;
                        case 'vote':
                            _tasks.push({ msg: '有投票任务', data: oTask });
                            break;
                        case 'score':
                            _tasks.push({ msg: '有打分任务', data: oTask });
                            break;
                    }
                });
            }
        });
    }
    var _oApp, _tasks;
    $scope.tasks = _tasks = [];
    $scope.Label = { task: { state: { 'IP': '进行中', 'BS': '未开始', 'AE': '已结束' } } };
    $scope.shiftRound = function(oRound) {
        $scope.selectedRound = oRound;
        fnGetTasks(oRound);
    };
    $scope.question = function(oTask) {
        if (oTask.topic) {
            location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.topic.id + '&page=topic';
        }
    };
    $scope.answer = function(oTask) {
        if (oTask.topic) {
            location.href = LS.j('', 'site', 'app') + '&topic=' + oTask.topic.id + '&page=topic';
        }
    };
    $scope.vote = function(oTask) {
        $uibModal.open({
            template: require('./_asset/vote-rec-data.html'),
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.vote = function(oRecData) {
                    http2.get(LS.j('task/vote', 'site') + '&data=' + oRecData.id + '&task=' + oTask.id).then(function(rsp) {
                        oRecData.voteResult.vote_num++;
                        oRecData.voteResult.vote_at = rsp.data[0].vote_at;
                        var remainder = rsp.data[1][0] - rsp.data[1][1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                };
                $scope2.unvote = function(oRecData) {
                    http2.get(LS.j('task/unvote', 'site') + '&data=' + oRecData.id + '&task=' + oTask.id).then(function(rsp) {
                        oRecData.voteResult.vote_num--;
                        oRecData.voteResult.vote_at = 0;
                        var remainder = rsp.data[0] - rsp.data[1];
                        if (remainder > 0) {
                            noticebox.success('还需要投出【' + remainder + '】票');
                        } else {
                            noticebox.success('已完成全部投票');
                        }
                    });
                };
                http2.get(LS.j('task/votingRecData', 'site', 'app') + '&task=' + oTask.id).then(function(rsp) {
                    $scope2.votingRecDatas = rsp.data[Object.keys(rsp.data)[0]];
                });
            }],
            backdrop: 'static',
            windowClass: 'auto-height'
        });
    };
    $scope.score = function(oTask) {
        var _oScoreApp;
        _oScoreApp = $parse('rule.scoreApp')(oTask);
        if (!_oScoreApp || !_oScoreApp.id) return;
        $uibModal.open({
            template: require('./_asset/score-app.html'),
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var _oData, _oScoreRecord;
                $scope2.data = _oData = {};
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.score = function(oSchema, opIndex, number) {
                    var oOption;

                    if (!(oOption = oSchema.ops[opIndex])) return;

                    if (_oData[oSchema.id] === undefined) {
                        _oData[oSchema.id] = {};
                        oSchema.ops.forEach(function(oOp) {
                            _oData[oSchema.id][oOp.v] = 0;
                        });
                    }

                    _oData[oSchema.id][oOption.v] = number;
                };
                $scope2.lessScore = function(oSchema, opIndex, number) {
                    var oOption;

                    if (!(oOption = oSchema.ops[opIndex])) return false;
                    if (_oData[oSchema.id] === undefined) {
                        return false;
                    }
                    return _oData[oSchema.id][oOption.v] >= number;
                };
                $scope2.submit = function() {
                    var url;
                    url = LS.j('record/submit', 'site') + '&app=' + _oScoreApp.id;
                    if (_oScoreRecord)
                        url += '&ek=' + _oScoreRecord.enroll_key;
                    http2.post(url, { data: _oData }, { autoBreak: false }).then(function(rsp) {
                        http2.post(LS.j('marks/renewReferScore', 'site') + '&app=' + _oScoreApp.id, {
                            /* 如何更新页面上已有的数据？ */
                        });
                    });
                };
                http2.get(LS.j('get', 'site') + '&app=' + _oScoreApp.id).then(function(rsp) {
                    _oScoreApp = rsp.data.app;
                    $scope2.schemas = _oScoreApp.dynaDataSchemas;
                    http2.get(LS.j('record/get', 'site') + '&app=' + _oScoreApp.id).then(function(rsp) {
                        if (rsp.data.enroll_key) {
                            _oScoreRecord = rsp.data;
                            http2.merge(_oData, _oScoreRecord.data);
                        }
                    });
                });
            }],
            backdrop: 'static',
            windowClass: 'auto-height'
        });
    };
    $scope.$on('xxt.app.enroll.ready', function(event, params) {
        _oApp = params.app;
        var facRound = new enlRound(_oApp);
        facRound.list().then(function(oResult) {
            $scope.rounds = oResult.rounds;
            if ($scope.rounds.length) $scope.shiftRound($scope.rounds[0]);
        });
    });
}]);