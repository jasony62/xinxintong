(function() {
    xxtApp.register.controller('tmplmsgCtrl', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
        $scope.$parent.subView = 'tmplmsg';
        $scope.orderEvts = [{
            id: 'submit_order',
            label: '用户提交订单'
        }, {
            id: 'pay_order',
            label: '用户完成支付'
        }, {
            id: 'feedback_order',
            label: '客服反馈订单'
        }, {
            id: 'finish_order',
            label: '客服完成订单'
        }, {
            id: 'cancel_order',
            label: '客服取消订单'
        }, {
            id: 'cus_cancel_order',
            label: '用户取消订单'
        }];
        http2.get('/rest/mp/matter/tmplmsg/list?cascaded=Y', function(rsp) {
            $scope.tmplmsgs = rsp.data;
        });
        $scope.selectCatelog = function() {};
        $scope.choose = function(orderEvt) {
            var url;
            url = '/rest/mp/matter/tmplmsg/mappingGet';
            url += '?id=' + $scope.$parent.editing[orderEvt.id + '_tmplmsg'];
            http2.get(url, function(rsp) {
                var def, i, l, o, prop;
                def = rsp.data;
                if (def.msgid) {
                    for (i = 0, l = $scope.tmplmsgs.length; i < l; i++) {
                        o = $scope.tmplmsgs[i];
                        if (o.id === def.msgid) {
                            orderEvt.tmplmsg = o;
                            break;
                        }
                    }
                } else {
                    orderEvt.tmplmsg = null;
                }
                if (def.mapping) {
                    for (i in def.mapping) {
                        o = def.mapping[i];
                        switch (o.src) {
                            case 'product':
                                if (o.id === '__productName') {
                                    prop = {
                                        id: '__productName',
                                        name: '名称'
                                    };
                                } else {
                                    $scope.$parent.editing.properties.forEach(function(v) {
                                        if (v.id == o.id) {
                                            prop = v;
                                            return false;
                                        }
                                    });
                                }
                                break;
                            case 'order':
                                if (o.id === '__orderSn') {
                                    prop = {
                                        id: '__orderSn',
                                        name: '订单号'
                                    };
                                } else if (o.id === '__orderState') {
                                    prop = {
                                        id: '__orderState',
                                        name: '订单状态'
                                    };
                                } else {
                                    $scope.$parent.editing.orderProperties.forEach(function(v) {
                                        if (v.id == o.id) {
                                            prop = v;
                                            return false;
                                        }
                                    });
                                }
                                break;
                            case 'feedback':
                                $scope.$parent.editing.feedbackProperties.forEach(function(v) {
                                    if (v.id == o.id) {
                                        prop = v;
                                        return false;
                                    }
                                });
                                break;
                            case 'text':
                                prop = {
                                    name: o.id
                                };
                                break;
                        }
                        if (prop) {
                            def.mapping[i] = prop;
                            def.mapping[i].src = o.src;
                        }
                    }
                    orderEvt.mapping = def.mapping;
                } else {
                    orderEvt.mapping = {};
                }
                $scope.selectedOrderEvt = orderEvt;
            });
        };
        $scope.selectTmplmsg = function() {
            $uibModal.open({
                templateUrl: 'tmplmsgSelector.html',
                backdrop: 'static',
                resolve: {
                    tmplmsgs: function() {
                        return $scope.tmplmsgs;
                    }
                },
                controller: ['$uibModalInstance', '$scope', 'tmplmsgs', function($mi, $scope2, tmplmsgs) {
                    $scope2.tmplmsgs = tmplmsgs;
                    $scope2.data = {};
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function(data) {
                $scope.selectedOrderEvt.tmplmsg = data.selected;
            });
        };
        $scope.selectProperty = function(tmplmsgProp) {
            $uibModal.open({
                templateUrl: 'propertySelector.html',
                backdrop: 'static',
                resolve: {
                    catelog: function() {
                        return $scope.$parent.editing;
                    }
                },
                controller: ['$uibModalInstance', '$scope', 'catelog', function($mi, $scope2, catelog) {
                    $scope2.catelog = catelog;
                    $scope2.properties = angular.copy(catelog.properties);
                    $scope2.properties.push({
                        id: '__productName',
                        name: '名称'
                    });
                    $scope2.orderProperties = angular.copy(catelog.orderProperties);
                    $scope2.orderProperties.push({
                        id: '__orderSn',
                        name: '订单号'
                    });
                    $scope2.orderProperties.push({
                        id: '__orderState',
                        name: '订单状态'
                    });
                    $scope2.data = {
                        srcProp: 'product'
                    };
                    $scope2.changeSrcProp = function() {
                        if ($scope2.data.srcProp === 'text') {
                            $scope2.data.selected = {
                                name: ''
                            };
                        }
                    };
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function(data) {
                data.selected.src = data.srcProp;
                if (data.srcProp === 'text') {
                    data.selected.id = data.selected.name;
                }
                $scope.selectedOrderEvt.mapping[tmplmsgProp.id] = data.selected;
            });
        };
        $scope.clean = function() {
            if (window.confirm('确定清除？')) {
                var url, sov, catelog;
                catelog = $scope.$parent.editing;
                sov = $scope.selectedOrderEvt;
                url = '/rest/mp/app/merchant/tmplmsg/clean';
                url += '?catelog=' + catelog.id;
                url += '&mappingid=' + catelog[sov.id + '_tmplmsg'];
                url += '&event=' + sov.id;
                http2.get(url, function(rsp) {
                    catelog[sov.id + '_tmplmsg'] = 0;
                    sov.mapping = {};
                });
            }
        };
        $scope.save = function() {
            var sov, i, m, posted, url;
            sov = $scope.selectedOrderEvt;
            posted = {
                evt: sov.id,
                msgid: sov.tmplmsg.id,
                mapping: {}
            };
            for (i in sov.mapping) {
                m = sov.mapping[i];
                posted.mapping[i] = {
                    src: m.src,
                    id: m.id
                }
            }
            url = '/rest/mp/app/merchant/tmplmsg/setup';
            url += '?catelog=' + $scope.$parent.editing.id;
            url += '&mappingid=' + $scope.$parent.editing[sov.id + '_tmplmsg'];
            http2.post(url, posted, function(rsp) {
                alert(rsp.data);
            });
        };
    }]);
})();