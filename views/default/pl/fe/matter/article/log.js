define(['frame'], function(ngApp) {
    'use strict';
    ngApp.provider.controller('ctrlLog', ['$scope', 'http2', 'srvLog', 'facListFilter', '$uibModal', '$compile', function($scope, http2, srvLog, facListFilter, $uibModal, $compile) {
        var spread, favor, download, oSiteid, oId;
        $scope.spread = spread = {
            page: {},
            criteria: {
               start: '',
               end: '',
               byUser: ''
            },
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'spread', this.criteria).then(function(spreaders) {
                    _this.spreaders = spreaders.logs;
                    _this.page.total = spreaders.total;
                });
            },
            cancle: function() {
                var _this = this;
                this.criteria.byUser = '';
                this.list();
            },
            detail: function(user, type) {
                var _this = this;
                $uibModal.open({
                    templateUrl: 'detail.html',
                    controller: ['$scope', '$uibModalInstance', 'http2', function($scope, $mi, http2) {
                        var _oCriteria = {
                            byOp: type,
                            byUserId: user.userid,
                            start: spread.criteria.start,
                            end: spread.criteria.end,
                            shareby: user.matter_shareby
                        }
                        $scope.page = {
                            at: 1,
                            size: 15,
                            j: function() {
                                return '&page=' + this.at + '&size=' + this.size;
                            }
                        };
                        $scope.doSearch = function() {
                            var url;
                            url = '/rest/pl/fe/matter/article/log/userMatterAction?appId=' + oId + $scope.page.j();
                            http2.post(url, _oCriteria, function(rsp) {
                                $scope.logs = rsp.data.logs;
                                $scope.page.total = rsp.data.total;
                            });
                        };
                        $scope.cancle = function() {
                            $mi.dismiss();
                        };
                        $scope.doSearch();
                    }],
                    backdrop: 'static'
                })
            },
            open: function(event, uid) {
                var _this = this, _oPage;
                $(event.currentTarget).addClass('hidden').next().removeClass('hidden');
                $scope.oPage = _oPage = {
                    at: 1,
                    size: 10,
                    j: function() {
                        return '&page=' + this.at + '&size=' + this.size;
                    }
                };
                this.criteria.shareby = uid;
                srvLog.list($scope.editing, _oPage, 'spread', this.criteria).then(function(users) {
                    var template, $template, persons=[];
                    persons = users.logs;
                    $scope.oPage.total = users.total;
                    for(var i=persons.length-1; i>=0; i--) {
                        template ='<tr class="bg1">';
                        template +='<td>'+(i+1)+'</td>';
                        template +='<td>'+persons[i].nickname+'</td>';
                        template +='<td ng-click=\'spread.detail(editing,'+JSON.stringify(persons[i])+',"read")\'><a href="#">'+persons[i].readNum+'</a></td>';
                        template +='<td ng-click=\'spread.detail(editing,'+JSON.stringify(persons[i])+', "share.timeline")\'><a href="#">'+persons[i].shareTNum+'</a></td>';
                        template +='<td ng-click=\'spread.detail(editing,'+JSON.stringify(persons[i])+', "share.friend")\'><a href="#">'+persons[i].shareFNum+'</a></td>';
                        template +='<td>'+persons[i].attractReadNum+'</td>';
                        template +='<td>'+persons[i].attractReaderNum+'</td>';
                        template +='<td>';
                        template +='</td>';
                        template +='</tr>';
                        $template = $compile(template)($scope);
                        $(event.target).parents('tr').after($template);
                    }
                });
            },
            close: function(event) {
                var str = $(event.target).parents('tr').attr('class');
                $('tbody').find('tr').each(function() {
                    if($(this).attr('class') !== str) {
                        $(this).remove();
                    }
                });
                $(event.currentTarget).addClass('hidden').prev().removeClass('hidden');
            }
        };
        $scope.export = function(user) {
            var url;
            url = '/rest/pl/fe/matter/article/log/exportOperateStat?site='+oSiteid+'&appId='+oId;
            url += '&start='+spread.criteria.start+'&end='+spread.criteria.end;
            if(user) {url += '&shareby='+user.userid;}
            window.open(url);
        };
        $scope.favor = favor = {
            page: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'favor').then(function(favorers) {
                    _this.favorers = favorers.data;
                    _this.page.total = favorers.total;
                });
            }
        };
        $scope.download = download = {
            page: {},
            criteria: {},
            list: function() {
                var _this = this;
                srvLog.list($scope.editing, this.page, 'download', this.criteria).then(function(logs) {
                    _this.logs = logs.logs;
                    _this.page.total = logs.total;
                });
            }
        }
        $scope.$on('xxt.tms-datepicker.change', function(event, data) {
            spread.criteria[data.state] = data.value;
            if(spread.criteria.start&&spread.criteria.end) {
                spread.list();
            }
        });
        $scope.$watch('editing', function(nv) {
            if(!nv) return;
            oSiteid = nv.siteid;
            oId = nv.id;
            spread.list();
            favor.list();
            download.list();
        });
    }]);
});
