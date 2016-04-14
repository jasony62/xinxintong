(function() {
    ngApp.provider.controller('ctrlSchema', ['$scope', 'http2', '$timeout', function($scope, http2, $timeout) {
        var id = 'c' + (new Date()).getTime(),
            base = {
                title: '',
                type: '',
                comment: '',
                required: 'N'
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
            newDef;
        newSchema = function(type) {
            var schema = Object.create(base);
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
        $scope.modify = function(name) {
            $scope.$parent.modified = true;
        };
        $scope.save = function() {
            $scope.update('data_schemas');
            $scope.submit();
        };
        $scope.$watch('app.data_schemas', function(schemas) {
            if (schemas) {
                $scope.schemas = schemas;
            }
        });
    }]);
})();