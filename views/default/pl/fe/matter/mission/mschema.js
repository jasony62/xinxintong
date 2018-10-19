define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlMschema', ['$scope', '$location', '$uibModal', 'http2', 'tmsSchema', 'srvSite', 'CstNaming', 'pushnotify', 'noticebox', 'tkAccount', 'tkMember', function($scope, $location, $uibModal, http2, tmsSchema, srvSite, CstNaming, pushnotify, noticebox, tkAccount, tkMember) {
        var _oSelected;
        $scope.selected = _oSelected = {
            mschema: null
        };
        $scope.chooseMschema = function() {
            var mschema;
            if (mschema = _oSelected.mschema) {
                $scope.searchBys = [];
                mschema.attr_name[0] == 0 && $scope.searchBys.push({
                    n: '姓名',
                    v: 'name'
                });
                mschema.attr_mobile[0] == 0 && $scope.searchBys.push({
                    n: '手机号',
                    v: 'mobile'
                });
                mschema.attr_email[0] == 0 && $scope.searchBys.push({
                    n: '邮箱',
                    v: 'email'
                });
                $scope.page = {
                    size: 30,
                    keyword: '',
                    searchBy: $scope.searchBys[0].v
                };
                $scope.rows = {
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
                $location.hash(mschema.id);
                $scope.doSearch(1);
            }
        };
        $scope.createMschema = function() {
            var url, proto;
            if ($scope.mission && $scope.mission.siteid) {
                url = '/rest/pl/fe/site/member/schema/create?site=' + $scope.mission.siteid;
                proto = { valid: 'Y', matter_id: $scope.mission.id, matter_type: $scope.mission.type, title: $scope.mission.title + '-通讯录' + ($scope.mschemas.length + 1) };
                http2.post(url, proto).then(function(rsp) {
                    $scope.mschemas.push(rsp.data);
                    _oSelected.mschema = rsp.data;
                    $scope.chooseMschema();
                });
            }
        };
        $scope.doSearch = function(page) {
            page && ($scope.page.at = page);
            var url, filter = '';
            if ($scope.page.keyword !== '') {
                filter = '&kw=' + $scope.page.keyword;
                filter += '&by=' + $scope.page.searchBy;
            }
            url = '/rest/pl/fe/site/member/list?site=' + _oSelected.mschema.siteid + '&schema=' + _oSelected.mschema.id;
            url += filter
            url += '&contain=total';
            http2.get(url, { page: $scope.page }).then(function(rsp) {
                var members;
                members = rsp.data.members;
                if (members.length) {
                    if (_oSelected.mschema.extAttrs.length) {
                        members.forEach(function(oMember) {
                            oMember._extattr = tmsSchema.member.getExtattrsUIValue(_oSelected.mschema.extAttrs, oMember);
                        });
                    }
                }
                $scope.members = members;
            });
        };
        /* 创建通讯录用户 */
        $scope.createByAccount = function() {
            /* 选择一个访客用户 */
            tkAccount.pick({ id: _oSelected.mschema.siteid }, { single: true }).then(function(oSiteAccount) {
                if (oSiteAccount) {
                    /* 访客用户创建通讯录用户 */
                    tkMember.create(_oSelected.mschema, { userid: oSiteAccount.uid }).then(function(oNewMember) {
                        oNewMember._extattr = tmsSchema.member.getExtattrsUIValue(_oSelected.mschema.extAttrs, oNewMember);
                        $scope.members.splice(0, 0, oNewMember);
                    });
                }
            });
        };
        $scope.editMember = function(oMember) {
            tkMember.edit(_oSelected.mschema, oMember).then(function(oResult) {
                if (oResult.action) {
                    switch (oResult.action) {
                        case 'remove':
                            $scope.members.splice($scope.members.indexOf(oMember), 1);
                            break;
                        case 'update':
                            oMember._extattr = tmsSchema.member.getExtattrsUIValue(_oSelected.mschema.extAttrs, oMember);
                            break;
                    }
                }
            });
        };
        $scope.notify = function(isBatch) {
            var rows = isBatch ? $scope.rows : null;
            var options = {
                matterTypes: CstNaming.notifyMatter,
                sender: 'schema:' + _oSelected.mschema.id
            };
            pushnotify.open(_oSelected.mschema.siteid, function(notify) {
                var url, targetAndMsg = {};
                if (notify.matters.length) {
                    if (rows) {
                        targetAndMsg.users = [];
                        Object.keys(rows.selected).forEach(function(key) {
                            if (rows.selected[key] === true) {
                                var rec = $scope.members[key];
                                targetAndMsg.users.push({ id: rec.id, userid: rec.userid });
                            }
                        });
                    }
                    targetAndMsg.message = notify.message;

                    url = '/rest/pl/fe/site/member/notice/send?site=' + _oSelected.mschema.siteid;
                    targetAndMsg.schema = _oSelected.mschema.id;
                    targetAndMsg.tmplmsg = notify.tmplmsg.id;

                    http2.post(url, targetAndMsg).then(function(data) {
                        noticebox.success('发送完成');
                    });
                }
            }, options);
        }
        $scope.$watch('rows.allSelected', function(nv) {
            var index = 0;
            if (nv == 'Y') {
                while (index < $scope.members.length) {
                    $scope.rows.selected[index++] = true;
                }
                $scope.rows.count = $scope.members.length;
            } else if (nv == 'N') {
                $scope.rows.reset();
            }
        });
        $scope.$watch('mission', function(oMission) {
            if (!oMission) return;
            srvSite.memberSchemaList(oMission, true).then(function(aMemberSchemas) {
                var hashMschemaId = $location.hash();
                $scope.mschemas = aMemberSchemas;
                if ($scope.mschemas.length) {
                    if (hashMschemaId) {
                        for (var i = $scope.mschemas.length - 1; i >= 0; i--) {
                            if ($scope.mschemas[i].id === hashMschemaId) {
                                _oSelected.mschema = $scope.mschemas[i];
                                break;
                            }
                        }
                    } else {
                        _oSelected.mschema = $scope.mschemas[0];
                    }
                    $scope.chooseMschema();
                }
            });
        });
    }]);
});