(function() {
    ngApp.provider.controller('ctrlSchema', ['$scope', 'http2', '$timeout', '$modal', function($scope, http2, $timeout, $modal) {
        var base = {
                title: '',
                type: '',
                comment: '',
                required: 'N',
                showname: 'label'
            },
            map = {
                'name': {
                    title: '姓名',
                    id: 'name'
                },
                'mobile': {
                    title: '手机',
                    id: 'mobile'
                },
                'email': {
                    title: '邮箱',
                    id: 'email'
                }
            },
            newDef,
            newSchema = function(type) {
                var id = 'c' + (new Date()).getTime(),
                    schema = angular.copy(base);
                schema.type = type;
                if (map[type]) {
                    schema.id = map[type].id;
                    schema.title = map[type].title;
                } else {
                    schema.id = id;
                    if (type === 'single' || type === 'multiple') {
                        schema.ops = [];
                        schema.align = 'V';
                        if (type === 'single') {
                            schema.component = 'R';
                        }
                    } else if (type === 'image' || type === 'file') {
                        schema.count = 1;
                    }
                }
                return schema;
            };
        $scope.addSchema = function(type) {
            var schema = newSchema(type);
            $scope.schemas.push(schema);
            $scope.$parent.modified = true;
        };
        $scope.addMember = function() {
            var schema = newSchema('member');
            $modal.open({
                templateUrl: 'memberSchema.html',
                resolve: {
                    memberSchemas: function() {
                        return $scope.memberSchemas;
                    }
                },
                controller: ['$scope', '$modalInstance', 'memberSchemas', function($scope2, $mi, memberSchemas) {
                    $scope2.memberSchemas = memberSchemas;
                    $scope2.schema = schema;
                    $scope2.data = {};
                    $scope2.shiftSchema = function() {
                        var schema = $scope2.data.memberSchema,
                            attrs = [];
                        $scope2.schema.schema_id = schema.id;
                        schema.attr_name[0] === '0' && (attrs.push({
                            id: 'name',
                            label: '姓名'
                        }));
                        schema.attr_mobile[0] === '0' && (attrs.push({
                            id: 'mobile',
                            label: '手机'
                        }));
                        schema.attr_email[0] === '0' && (attrs.push({
                            id: 'email',
                            label: '邮箱'
                        }));
                        if (schema.extattr && schema.extattr.length) {
                            angular.forEach(schema.extattr, function(ea) {
                                attrs.push({
                                    id: 'extattr.' + ea.id,
                                    label: ea.label
                                });
                            });
                        }
                        $scope2.data.attrs = attrs;
                    };
                    $scope2.shiftAttr = function() {
                        var attr = $scope2.data.attr;
                        $scope2.schema.title = attr.label;
                        $scope2.schema.id = 'member.' + attr.id;
                    };
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.schema);
                    };
                }],
                backdrop: 'static'
            }).result.then(function(newSchema) {
                $scope.schemas.push(schema);
                $scope.$parent.modified = true;
            });
        };
        $scope.addOption = function(schema) {
            var maxSeq = 0,
                newOp = {
                    l: ''
                };
            angular.forEach(schema.ops, function(op) {
                var opSeq = parseInt(op.v.substr(1));
                opSeq > maxSeq && (maxSeq = opSeq);
            });
            newOp.v = 'v' + (++maxSeq);
            schema.ops.push(newOp);
            $timeout(function() {
                $scope.$broadcast('xxt.editable.add', newOp);
            });
            $scope.$parent.modified = true;
        };
        $scope.$on('xxt.editable.remove', function(e, op) {
            angular.forEach($scope.schemas, function(schema) {
                if (schema.ops && schema.ops.length && schema.ops.indexOf(op) !== -1) {
                    schema.ops.splice(schema.ops.indexOf(op), 1);
                }
            });
            $scope.$parent.modified = true;
        });
        $scope.configSchema = function(schema) {
            $modal.open({
                templateUrl: 'configSchema.html',
                controller: ['$scope', '$modalInstance', function($scope2, $mi) {
                    $scope2.schema = angular.copy(schema);
                    $scope2.cancel = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.schema);
                    };
                }],
                backdrop: 'static'
            }).result.then(function(newSchema) {
                schema.id = newSchema.id;
                schema.comment = newSchema.comment;
                schema.required = newSchema.required;
                $scope.$parent.modified = true;
            });
        };
        $scope.removeSchema = function(schema) {
            $scope.schemas.splice($scope.schemas.indexOf(schema), 1);
            $scope.$parent.modified = true;
        };
        $scope.modify = function(name) {
            $scope.$parent.modified = true;
        };
        $scope.save = function() {
            $scope.update('data_schemas');
            $scope.submit().then($scope.getApp);
        };
        $scope.$watch('app.data_schemas', function(schemas) {
            if (schemas) {
                $scope.schemas = schemas;
            }
        });
    }]);
})();