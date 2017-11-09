define([], function() {
    'use strict';

    function protoOps(type) {
        if (type === 'score') {
            return [{
                l: '打分项1',
                v: 'v1',
            }, {
                l: '打分项2',
                v: 'v2',
            }];
        } else if (/single|multiple/.test(type)) {
            return [{
                l: '选项1',
                v: 'v1'
            }, {
                l: '选项2',
                v: 'v2'
            }];
        } else {
            return [];
        }
    }
    var base = {
            title: '',
            type: '',
            unique: 'N',
            _ver: 1
        },
        prefab = {
            'phase': {
                title: '项目阶段'
            }
        };
    return {
        buttons: {
            submit: {
                n: 'submit',
                l: '提交信息',
                scope: ['I']
            },
            save: {
                n: 'save',
                l: '保存信息',
                scope: ['I'],
                next: ['I']
            },
            addRecord: {
                n: 'addRecord',
                l: '新增登记',
                scope: ['V', 'L'],
                next: ['I']
            },
            editRecord: {
                n: 'editRecord',
                l: '修改登记',
                scope: ['V'],
                next: ['I']
            },
            removeRecord: {
                n: 'removeRecord',
                l: '删除登记',
                scope: ['V']
            },
            sendInvite: {
                n: 'sendInvite',
                l: '发出邀请'
            },
            acceptInvite: {
                n: 'acceptInvite',
                l: '接受邀请'
            },
            gotoPage: {
                n: 'gotoPage',
                l: '页面导航',
                scope: ['I', 'V', 'L']
            },
            closeWindow: {
                n: 'closeWindow',
                l: '关闭页面',
                scope: ['I', 'V', 'L']
            }
        },
        newSchema: function(type, app, proto) {
            var schema = angular.copy(base);

            schema.id = (proto && proto.id) ? proto.id : 's' + (new Date * 1);
            schema.required = type === 'html' ? 'N' : 'Y';
            schema.type = type;
            if (prefab[type]) {
                var countOfType = 0;
                app.dataSchemas.forEach(function(schema) {
                    if (schema.type === type) {
                        countOfType++;
                    }
                });
                schema.title = (proto && proto.title) ? proto.title : (prefab[type].title + (++countOfType));
                if (type === 'phase') {
                    schema.ops = [];
                    if (app.mission && app.mission.phases) {
                        app.mission.phases.forEach(function(phase) {
                            schema.ops.push({
                                l: phase.title,
                                v: phase.phase_id
                            });
                        });
                    }
                }
            } else {
                schema.title = (proto && proto.title) ? proto.title : ('登记项' + (app.dataSchemas.length + 1));
                if (type === 'single' || type === 'multiple') {
                    schema.ops = protoOps(type);
                } else if (type === 'image' || type === 'file') {
                    schema.count = 1;
                } else if (type === 'score') {
                    schema.range = [1, 5];
                    schema.ops = protoOps(type);
                } else if (type === 'html') {
                    schema.content = '请点击下面“编辑”按钮，编辑本说明文字';
                }
            }
            if (/longtext|file|image/.test(type)) {
                schema.remarkable = 'Y';
            }
            if (proto && proto.format !== undefined) {
                schema.format = proto.format;
            } else if (/shorttext/.test(type)) {
                schema.format = '';
            }

            return schema;
        },
        changeType: function(schema, newType) {
            if (/phase/.test(newType) || schema.type === newType) {
                return false;
            }
            if ('member' === newType && !/^member\./.test(schema.id)) {
                return false;
            }
            if ('member' === schema.type) {
                if (!/shorttext/.test(newType)) {
                    return false;
                }
                delete schema.schema_id;
            }
            if (/single|multiple|score/.test(schema.type) && !/single|multiple|score/.test(newType)) {
                delete schema.ops;
            }
            if (schema.type === 'score' && newType !== 'score') {
                delete schema.range;
            }
            if (/image|file/.test(schema.type) && !/image|file/.test(newType)) {
                delete schema.count;
            }
            if (!/single|multiple|score/.test(schema.type) && /single|multiple|score/.test(newType)) {
                schema.ops = protoOps(newType);
            }
            if (schema.type !== 'score' && newType === 'score') {
                schema.range = [1, 5];
            }
            if (!/image|file/.test(schema.type) && /image|file/.test(newType)) {
                schema.count = 1;
            }
            if (/email|mobile|name/.test(schema.type) && /shorttext/.test(newType)) {
                schema.format = schema.type;
            } else if (/shorttext|longtext/.test(newType)) {
                schema.format = '';
            }
            if ('html' === newType) {
                schema.required = 'N';
            }
            schema.type = newType;

            return true;
        },
        /**
         * @schemaOptionsId 后指定的选项后面添加选项
         */
        addOption: function(schema, schemaOptionId) {
            var maxSeq = 0,
                newOp = {
                    l: ''
                },
                optionIndex = -1;

            if (schema.ops === undefined) {
                schema.ops = [];
            }
            schema.ops.forEach(function(op, index) {
                var opSeq = parseInt(op.v.substr(1));
                opSeq > maxSeq && (maxSeq = opSeq);
                if (op.v === schemaOptionId) {
                    optionIndex = index;
                }
            });
            newOp.v = 'v' + (++maxSeq);
            if (schemaOptionId === undefined) {
                schema.ops.push(newOp);
            } else {
                schema.ops.splice(optionIndex + 1, 0, newOp);
            }

            return newOp;
        },
        _upgrade: function(oSchema, oApp) {
            if (oSchema._ver === undefined) {
                oSchema.unique = 'N';
                oSchema._ver = 1;
            }
            if (!oSchema.fromApp) {
                delete oSchema.requireCheck;
            }
        }
    }
});