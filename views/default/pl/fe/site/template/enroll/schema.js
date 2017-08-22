define(['frame', 'schema', 'wrap'], function(ngApp, schemaLib, wrapLib) {
    'use strict';
    /**
     * 登记项管理
     */
    ngApp.provider.controller('ctrlSchema', ['$scope', 'cstApp', 'srvEnrollPage', 'srvEnrollApp', 'srvTempApp', 'srvTempPage', function($scope, cstApp, srvEnrollPage, srvEnrollApp, srvTempApp, srvTempPage) {
        function _appendSchema(newSchema, afterIndex) {
            if ($scope.app._schemasById[newSchema.id]) {
                alert(cstApp.alertMsg['schema.duplicated']);
                return;
            }
            if (afterIndex === undefined) {
                $scope.app.data_schemas.push(newSchema);
            } else {
                $scope.app.data_schemas.splice(afterIndex + 1, 0, newSchema);
            }
            $scope.app._schemasById[newSchema.id] = newSchema;
            srvTempApp.update('data_schemas').then(function() {
                $scope.app.pages.forEach(function(page) {
                    if (page.appendSchema(newSchema)) {
                        srvTempPage.update(page, ['data_schemas', 'html']);
                    }
                });
            });
        }

        function _removeSchema(removedSchema) {
            var pages = $scope.app.pages,
                l = pages.length;

            (function removeSchemaFromPage(index) {
                var page = pages[index];
                if (page.removeSchema(removedSchema)) {
                    srvTempPage.update(page, ['data_schemas', 'html']).then(function() {
                        if (++index < l) {
                            removeSchemaFromPage(index);
                        } else {
                            $scope.app.data_schemas.splice($scope.app.data_schemas.indexOf(removedSchema), 1);
                            srvTempApp.update('data_schemas');
                        }
                    });
                } else {
                    if (++index < l) {
                        removeSchemaFromPage(index);
                    } else {
                        $scope.app.data_schemas.splice($scope.app.data_schemas.indexOf(removedSchema), 1);
                        delete $scope.app._schemasById[removedSchema.id];
                        srvTempApp.update('data_schemas');
                    }
                }
            })(0);
        }

        $scope.newSchema = function(type) {
            var newSchema, mission;

            if (type === 'phase') {
                mission = $scope.app.mission;
                if (!mission || !mission.phases || mission.phases.length === 0) {
                    alert(cstApp.alertMsg['require.mission.phase']);
                    return;
                }
            }
            newSchema = schemaLib.newSchema(type, $scope.app);
            _appendSchema(newSchema);
        };
        $scope.copySchema = function(schema) {
            var newSchema = angular.copy(schema),
                afterIndex;

            newSchema.id = 's' + (new Date() * 1);
            afterIndex = $scope.app.data_schemas.indexOf(schema);
            _appendSchema(newSchema, afterIndex);
        };
        $scope.removeSchema = function(removedSchema) {
            if (window.confirm('确定从所有页面上删除登记项［' + removedSchema.title + '］？')) {
                _removeSchema(removedSchema);
            }
        };
    }]);
});