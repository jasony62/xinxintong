define([], function() {
	'use strict';
	var base = {
			title: '',
			type: '',
			unique: 'N',
			_ver: 1
		},
		prefab = {
			'name': {
				id: 'name',
				title: '姓名'
			},
			'mobile': {
				id: 'mobile',
				title: '手机'
			},
			'email': {
				id: 'email',
				title: '邮箱'
			},
			'phase': {
				id: 'phase',
				title: '项目阶段'
			}
		};
	return {
		buttons: {
			submit: {
				n: 'submit',
				l: '提交信息'
			},
			addRecord: {
				n: 'addRecord',
				l: '新增登记'
			},
			editRecord: {
				n: 'editRecord',
				l: '修改登记'
			},
			removeRecord: {
				n: 'removeRecord',
				l: '删除登记'
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
				l: '页面导航'
			},
			closeWindow: {
				n: 'closeWindow',
				l: '关闭页面'
			}
		},
		newSchema: function(type, app, proto) {
			var schema = angular.copy(base);
			schema.type = type;
			if (prefab[type]) {
				schema.id = (proto && proto.id) ? proto.id : prefab[type].id;
				schema.title = (proto && proto.title) ? proto.title : prefab[type].title;
				if (type === 'phase') {
					schema.ops = [];
					if (app.mission && app.mission.phases) {
						angular.forEach(app.mission.phases, function(phase) {
							schema.ops.push({
								l: phase.title,
								v: phase.phase_id
							});
						});
					}
				}
			} else {
				schema.id = (proto && proto.id) ? proto.id : 'c' + (new Date() * 1);
				schema.title = (proto && proto.title) ? proto.title : '新登记项';
				if (type === 'single' || type === 'multiple') {
					schema.ops = [{
						l: '选项1',
						v: 'v1'
					}, {
						l: '选项2',
						v: 'v2'
					}];
				} else if (type === 'image' || type === 'file') {
					schema.count = 1;
				} else if (type === 'score') {
					schema.range = [1, 5];
					schema.ops = [{
						l: '打分项1',
						v: 'v1',
					}, {
						l: '打分项2',
						v: 'v2',
					}];
				}
			}

			return schema;
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
		_upgrade: function(schema) {
			if (schema._ver === undefined) {
				schema.unique = 'N';
				schema._ver = 1;
			}
		}
	}
});