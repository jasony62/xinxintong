(function() {
    app.provider.controller('ctrlSchema', ['$scope', 'http2', '$modal', '$timeout', 'Mp', function($scope, http2, $modal, $timeout, Mp) {
        $scope.$watch('app.data_schemas', function(schemas) {
            if (schemas) {
                $scope.schemas = schemas;
            }
        });
        $scope.add = function() {
            $modal.open({
                templateUrl: 'embedInputLib.html',
                backdrop: 'static',
                controller: ['$scope', '$modalInstance', function($scope, $mi) {
                    var key;
                    key = 'c' + (new Date()).getTime();
                    (new Mp()).getAuthapis().then(function(data) {
                        $scope.authapis = data;
                    });
                    $scope.def = {
                        key: 'name',
                        type: '0',
                        name: '姓名',
                        required: '0',
                        component: 'R',
                        align: 'V',
                        count: 1,
                        setUpper: 'N'
                    };
                    $scope.addOption = function() {
                        if ($scope.def.ops === undefined)
                            $scope.def.ops = [];
                        var newOp = {
                            text: ''
                        };
                        $scope.def.ops.push(newOp);
                        $timeout(function() {
                            $scope.$broadcast('xxt.editable.add', newOp);
                        });
                    };
                    $scope.addExtAttr = function() {
                        $scope.def.attrs === undefined && ($scope.def.attrs = []);
                        var newAttr = {
                            name: '',
                            value: ''
                        };
                        $scope.def.attrs.push(newAttr);
                    };
                    $scope.$on('xxt.editable.remove', function(e, op) {
                        var i = $scope.def.ops.indexOf(op);
                        $scope.def.ops.splice(i, 1);
                    });
                    $scope.changeType = function() {
                        var map = {
                            '0': {
                                name: '姓名',
                                key: 'name'
                            },
                            '1': {
                                name: '手机',
                                key: 'mobile'
                            },
                            '2': {
                                name: '邮箱',
                                key: 'email'
                            }
                        };
                        if (map[$scope.def.type]) {
                            $scope.def.name = map[$scope.def.type].name;
                            $scope.def.key = map[$scope.def.type].key;
                        } else if ($scope.def.type === 'auth') {
                            $scope.def.name = '';
                            $scope.def.key = '';
                            $scope.def.auth = {};
                        } else {
                            $scope.def.name = '';
                            $scope.def.key = key;
                        }
                    };
                    $scope.selectedAuth = {
                        api: null,
                        attrs: null,
                        attr: null
                    };
                    $scope.shiftAuthapi = function() {
                        var auth = $scope.selectedAuth.api,
                            authAttrs = [];
                        $scope.def.auth.authid = auth.authid;
                        auth.attr_name[0] === '0' && (authAttrs.push({
                            id: 'name',
                            label: '姓名'
                        }));
                        auth.attr_mobile[0] === '0' && (authAttrs.push({
                            id: 'mobile',
                            label: '手机'
                        }));
                        auth.attr_email[0] === '0' && (authAttrs.push({
                            id: 'email',
                            label: '邮箱'
                        }));
                        if (auth.extattr && auth.extattr.length) {
                            var i, l, ea;
                            for (i = 0, l = auth.extattr.length; i < l; i++) {
                                ea = auth.extattr[i];
                                authAttrs.push({
                                    id: 'extattr.' + ea.id,
                                    label: ea.label
                                });
                            }
                        }
                        $scope.selectedAuth.attrs = authAttrs;
                    };
                    $scope.shiftAuthAttr = function() {
                        var attr = $scope.selectedAuth.attr;
                        $scope.def.name = attr.label;
                        $scope.def.key = 'member.' + attr.id;
                    };
                    $scope.ok = function() {
                        if ($scope.def.name.length === 0) {
                            alert('必须指定登记项的名称');
                            return;
                        }
                        $mi.close($scope.def);
                    };
                    $scope.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope.$watch('def.setUpper', function(nv) {
                        if (nv === 'Y') {
                            $scope.def.upper = $scope.def.ops ? $scope.def.ops.length : 0;
                        }
                    });
                }],
            }).result.then(function(def) {
                var schema;
                schema = {
                    id: def.key,
                    title: def.name,
                    type: def.type,
                    required: def.required
                };
                $scope.schemas.push(schema);
            });
        };
        $scope.save = function() {
            $scope.update('data_schemas');
            $scope.submit();
        };
    }]);
})();