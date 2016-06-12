define([], function() {
	'use strict';
	var base = {
			title: '',
			type: '',
		},
		prefab = {
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
			},
			'phase': {
				title: '项目阶段',
				id: 'phase'
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
		newSchema: function(type, app) {
			var id = 'c' + (new Date()).getTime(),
				schema = angular.copy(base);
			schema.type = type;
			if (prefab[type]) {
				schema.id = prefab[type].id;
				schema.title = prefab[type].title;
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
				schema.id = id;
				schema.title = '新登记项';
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
				}
			}
			return schema;
		}
	}
});