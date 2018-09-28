define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlUser', ['$scope', 'http2', 'srvPlanApp', '$uibModal', function($scope, http2, srvPlanApp, $uibModal) {
        var _oApp, _assocMschemas, _oPage;
        $scope.page = _oPage = {
            at: 1,
            size: 30,
            j: function() {
                return '&page=' + this.at + '&size=' + this.size;
            }
        }
        $scope.addUser = function() {
            $uibModal.open({
                templateUrl: 'addUser.html',
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    var _oSelected, _oPage, _oRows;
                    $scope2.selected = _oSelected = {};
                    $scope2.page = _oPage = {
                        at: 1,
                        size: 30
                    };
                    $scope2.mschemas = _assocMschemas;
                    $scope2.chooseMschema = function() {
                        $scope2.doSearch(1);
                        $scope2.msAttrs = _oSelected.mschema.attrs;
                    };
                    $scope2.doSearch = function(pageAt) {
                        var url;
                        pageAt && (_oPage.at = pageAt);
                        url = '/rest/pl/fe/site/member/list?site=' + _oApp.siteid + '&schema=' + _oSelected.mschema.id;
                        url += '&page=' + _oPage.at + '&size=' + _oPage.size
                        url += '&contain=total';
                        http2.get(url).then(function(rsp) {
                            var i, member, members = rsp.data.members;
                            for (i in members) {
                                member = members[i];
                                if (member.extattr) {
                                    try {
                                        member.extattr = JSON.parse(member.extattr);
                                    } catch (e) {
                                        member.extattr = {};
                                    }
                                }
                            }
                            $scope2.members = members;
                            _oPage.total = rsp.data.total;
                        });
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        var oChosen, chosens;
                        chosens = []
                        for (var p in _oRows.selected) {
                            if (_oRows.selected[p] === true) {
                                oChosen = $scope2.members[p];
                                chosens.push({ id: oChosen.id, userid: oChosen.userid });
                            }
                        }
                        $mi.close(chosens);
                    };
                    // 选中的记录
                    $scope2.rows = _oRows = {
                        allSelected: 'N',
                        selected: {},
                        count: 0,
                        change: function(index) {
                            this.selected[index] ? this.count++ : this.count--;
                        },
                        reset: function() {
                            this.allSelected = 'N';
                            this.selected = {};
                            this.count = 0;
                        }
                    };
                    $scope2.$watch('rows.allSelected', function(checked) {
                        var index = 0;
                        if (checked === 'Y') {
                            while (index < $scope2.members.length) {
                                _oRows.selected[index++] = true;
                            }
                            _oRows.count = $scope2.members.length;
                        } else if (checked === 'N') {
                            _oRows.reset();
                        }
                    });
                    if (_assocMschemas.length) {
                        _oSelected.mschema = _assocMschemas[0];
                        $scope2.chooseMschema();
                    }
                }],
                size: 'lg',
                backdrop: 'static'
            }).result.then(function(members) {
                if (members && members.length) {
                    http2.post('/rest/pl/fe/matter/plan/user/add?app=' + _oApp.id, { members: members }).then(function(rsp) {
                        if (rsp.data && rsp.data.length) {
                            rsp.data.forEach(function(oUser) {
                                $scope.users.splice(0, 0, oUser);
                            });
                        }
                    });
                }
            });
        };
        $scope.editUser = function(oUser) {
            $uibModal.open({
                templateUrl: 'editUser.html',
                controller: ['$scope', '$uibModalInstance', 'http2', function($scope2, $mi, http2) {
                    var _oUpdated;
                    _oUpdated = {};
                    $scope2.user = angular.copy(oUser);
                    $scope2.app = _oApp;
                    $scope2.update = function(prop) {
                        _oUpdated[prop] = $scope2.user[prop];
                    };
                    $scope2.$on('xxt.tms-datepicker.change', function(event, data) {
                        _oUpdated[data.state] = data.value;
                    });
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close(_oUpdated);
                    };
                    http2.get('/rest/pl/fe/matter/plan/schema/task/list?plan=' + _oApp.id).then(function(rsp) {
                        if (rsp.data.tasks.length) {
                            $scope2.firstTask = rsp.data.tasks[0];
                            console.log($scope2.firstTask);
                        }
                    });
                }],
                backdrop: 'static'
            }).result.then(function(oUpdated) {
                if (Object.keys(oUpdated).length) {
                    http2.post('/rest/pl/fe/matter/plan/user/update?user=' + oUser.id, oUpdated).then(function(rsp) {
                        angular.extend(oUser, rsp.data);
                    });
                }
            });
        };
        $scope.doSearch = function() {
            http2.get('/rest/pl/fe/matter/plan/user/list?app=' + _oApp.id + _oPage.j()).then(function(rsp) {
                $scope.users = rsp.data.users;
                $scope.page.total = rsp.data.total;
            });
        };
        srvPlanApp.get().then(function(oApp) {
            _oApp = oApp;
            $scope.doSearch();
            http2.get('/rest/pl/fe/matter/plan/assocMschema?app=' + _oApp.id).then(function(rsp) {
                _assocMschemas = rsp.data;
            });
        });
    }])
});