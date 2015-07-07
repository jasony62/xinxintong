(function () {
    xxtApp.register.controller('rollCtrl', ['$scope', 'http2', '$modal', function ($scope, http2, $modal) {
        $scope.$parent.subView = 'roll';
        var t = (new Date()).getTime();
        $scope.doSearch = function (page) {
            page && ($scope.page.at = page);
            var url = '/rest/mp/app/enroll/records?aid=' + $scope.aid + '&contain=total' + $scope.page.joinParams();
            http2.get(url, function (rsp) {
                if (rsp.data) {
                    $scope.roll = rsp.data[0] ? rsp.data[0] : [];
                    rsp.data[1] && ($scope.page.total = rsp.data[1]);
                    rsp.data[2] && ($scope.cols = rsp.data[2]);
                } else
                    $scope.roll = [];
            });
        };
        $scope.page = {
            at: 1,
            size: 30,
            keyword: '',
            searchBy: 'nickname',
            joinParams: function () {
                var p;
                p = '&page=' + this.at + '&size=' + this.size;
                if (this.keyword !== '') {
                    p += '&kw=' + this.keyword;
                    p += '&by=' + this.searchBy;
                }
                p += '&rid=' + (this.byRound ? this.byRound : 'ALL');
                return p;
            }
        };
        $scope.searchBys = [
            { n: '昵称', v: 'nickname' },
            { n: '手机号', v: 'mobile' },
        ];
        $scope.viewUser = function (fan) {
            location.href = '/rest/mp/user?fid=' + fan.fid;
            // todo 如果是认证用户???
        };
        $scope.keywordKeyup = function (evt) {
            if (evt.which === 13)
                $scope.doSearch();
        };
        $scope.editRoll = function (rollItem) {
            var ins = $modal.open({
                templateUrl: 'editor.html',
                controller: 'editorCtrl',
                resolve: {
                    rollItem: function () {
                        rollItem.aid = $scope.aid;
                        return rollItem;
                    },
                    tags: function () {
                        return $scope.editing.tags;
                    },
                    cols: function () {
                        return $scope.cols;
                    }
                }
            });
            ins.result.then(function (updated) {
                var p = updated[0], tags = updated[1].join(',');
                if ($scope.editing.tags.length !== tags.length) {
                    $scope.editing.tags = tags;
                    $scope.update('tags');
                }
                http2.post('/rest/mp/app/enroll/updateRoll?aid=' + $scope.aid + '&ek=' + rollItem.enroll_key, p);
            });
        };
        $scope.addRoll = function () {
            var ins = $modal.open({
                templateUrl: 'editor.html',
                controller: 'editorCtrl',
                resolve: {
                    rollItem: function () {
                        return { aid: $scope.aid, tags: '' };
                    },
                    tags: function () {
                        return $scope.editing.tags;
                    },
                    cols: function () {
                        return $scope.cols;
                    }
                }
            });
            ins.result.then(function (updated) {
                var p = updated[0], tags = updated[1].join(',');
                if ($scope.editing.tags.length !== tags.length) {
                    $scope.editing.tags = tags;
                    $scope.update('tags');
                }
                http2.post('/rest/mp/app/enroll/addRoll?aid=' + $scope.aid, p, function (rsp) {
                    $scope.roll.splice(0, 0, rsp.data);
                });
            });
        };
        $scope.importRoll = function () {
            http2.get('/rest/member/auth/userselector', function (rsp) {
                var url = rsp.data;
                $.getScript(url, function () {
                    $modal.open(AddonParams).result.then(function (selected) {
                        if (selected.members && selected.members.length) {
                            var members = [];
                            for (var i in selected.members)
                                members.push(selected.members[i].data.mid);
                            http2.post('/rest/mp/app/importRoll?aid=' + $scope.aid, members, function (rsp) {
                                for (var i in rsp.data)
                                    $scope.roll.splice(0, 0, rsp.data[i]);
                            });
                        }
                    })
                });
            });
        };
        $scope.importRoll2 = function () {
            $modal.open({
                templateUrl: 'importActivityRoll.html',
                controller: 'importActivityRollCtrl',
                backdrop: 'static',
                size: 'lg'
            }).result.then(function (param) {
                http2.post('/rest/mp/app/enroll/importRoll2?aid=' + $scope.aid, param, function (rsp) {
                    $scope.doSearch(1);
                });
            });
        };
        $scope.removeRoll = function (roll) {
            var vcode;
            vcode = prompt('是否要删除登记信息？，若是，请输入活动名称。');
            if (vcode === $scope.editing.title) {
                http2.get('/rest/mp/app/enroll/removeRoll?aid=' + $scope.aid + '&key=' + roll.enroll_key, function (rsp) {
                    var i = $scope.roll.indexOf(roll);
                    $scope.roll.splice(i, 1);
                    $scope.page.total = $scope.page.total - 1;
                });
            }
        };
        $scope.cleanAll = function () {
            var vcode;
            vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
            if (vcode === $scope.editing.title) {
                http2.get('/rest/mp/app/enroll/clean?aid=' + $scope.aid, function (rsp) {
                    $scope.doSearch(1);
                });
            }
        };
        $scope.doSearch();
    }]);
    xxtApp.register.controller('importActivityRollCtrl', ['$scope', 'http2', '$modalInstance', function ($scope, http2, $modalInstance) {
        $scope.param = {
            checkedActs: [],
            checkedWalls: [],
            wallUserState: 'active',
            alg: 'inter'
        };
        $scope.changeAct = function (act) {
            var i = $scope.param.checkedActs.indexOf(act.aid);
            if (i === -1)
                $scope.param.checkedActs.push(act.aid);
            else
                $scope.param.checkedActs.splice(i, 1);
        };
        $scope.changeWall = function (wall) {
            var i = $scope.param.checkedWalls.indexOf(wall.wid);
            if (i === -1)
                $scope.param.checkedWalls.push(wall.wid);
            else
                $scope.param.checkedWalls.splice(i, 1);
        };
        $scope.cancel = function () {
            $modalInstance.dismiss();
        };
        $scope.ok = function () {
            $modalInstance.close($scope.param);
        };
        http2.get('/rest/mp/app/enroll?page=1&size=999', function (rsp) {
            $scope.activities = rsp.data[0];
        });
        http2.get('/rest/mp/app/wall', function (rsp) {
            $scope.walls = rsp.data;
        });
    }]);
    xxtApp.register.controller('editorCtrl', ['$scope', '$modalInstance', 'rollItem', 'tags', 'cols', function ($scope, $modalInstance, rollItem, tags, cols) {
        $scope.item = rollItem;
        $scope.item.aTags = (!rollItem.tags || rollItem.tags.length === 0) ? [] : rollItem.tags.split(',');
        $scope.aTags = (!tags || tags.length === 0) ? [] : tags.split(',');
        $scope.cols = cols;
        $scope.signin = function () {
            $scope.item.signin_at = Math.round((new Date()).getTime() / 1000);
        };
        $scope.ok = function () {
            var p, col;
            p = { tags: $scope.item.aTags.join(','), data: {} };
            $scope.item.tags = p.tags;
            if ($scope.item.id)
                p.signin_at = $scope.item.signin_at;
            for (var c in $scope.cols) {
                col = $scope.cols[c];
                p.data[col.id] = $scope.item.data[col.id];
            }
            $modalInstance.close([p, $scope.aTags]);
        };
        $scope.cancel = function () {
            $modalInstance.dismiss('cancel');
        };
        $scope.$on('tag.xxt.combox.done', function (event, aSelected) {
            var aNewTags = [];
            for (var i in aSelected) {
                var existing = false;
                for (var j in $scope.item.aTags) {
                    if (aSelected[i] === $scope.item.aTags[j]) {
                        existing = true;
                        break;
                    }
                }
                !existing && aNewTags.push(aSelected[i]);
            }
            $scope.item.aTags = $scope.item.aTags.concat(aNewTags);
        });
        $scope.$on('tag.xxt.combox.add', function (event, newTag) {
            $scope.item.aTags.push(newTag);
            if ($scope.aTags.indexOf(newTag) === -1) {
                $scope.aTags.push(newTag);
            }
        });
        $scope.$on('tag.xxt.combox.del', function (event, removed) {
            $scope.item.aTags.splice($scope.item.aTags.indexOf(removed), 1);
        });
    }]);
})();
