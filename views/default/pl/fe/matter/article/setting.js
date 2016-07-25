define(['frame'], function(ngApp) {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$uibModal', 'http2', 'mattersgallery', 'mediagallery', 'noticebox', function($scope, $uibModal, http2, mattersgallery, mediagallery, noticebox) {
		var tinymceEditor, modifiedData = {};
		var r = new Resumable({
			target: '/rest/pl/fe/matter/article/attachment/upload?site=' + $scope.siteId + '&articleid=' + $scope.id,
			testChunks: false,
		});
		r.assignBrowse(document.getElementById('addAttachment'));
		r.on('fileAdded', function(file, event) {
			$scope.$root.progmsg = '开始上传文件';
			$scope.$root.$apply('progmsg');
			r.upload();
		});
		r.on('progress', function(file, event) {
			$scope.$root.progmsg = '正在上传文件：' + Math.floor(r.progress() * 100) + '%';
			$scope.$root.$apply('progmsg');
		});
		r.on('complete', function() {
			var f, lastModified, posted;
			f = r.files.pop().file;
			lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
			posted = {
				name: f.name,
				size: f.size,
				type: f.type,
				lastModified: lastModified,
				uniqueIdentifier: f.uniqueIdentifier,
			};
			http2.post('/rest/pl/fe/matter/article/attachment/add?site=' + $scope.siteId + '&id=' + $scope.id, posted, function success(rsp) {
				$scope.editing.attachments.push(rsp.data);
				$scope.$root.progmsg = null;
			});
		});
		$scope.modified = false;
		$scope.innerlinkTypes = [{
			value: 'article',
			title: '单图文',
			url: '/rest/pl/fe/matter'
		}, {
			value: 'news',
			title: '多图文',
			url: '/rest/pl/fe/matter'
		}, {
			value: 'channel',
			title: '频道',
			url: '/rest/pl/fe/matter'
		}, {
			value: 'enroll',
			scenario: 'registration',
			title: '报名',
			url: '/rest/pl/fe/matter'
		}, {
			value: 'enroll',
			scenario: 'voting',
			title: '投票',
			url: '/rest/pl/fe/matter'
		}, {
			value: 'signin',
			title: '签到',
			url: '/rest/pl/fe/matter'
		}];
		$scope.back = function() {
			history.back();
		};
		window.onbeforeunload = function(e) {
			var message;
			if ($scope.modified) {
				message = '修改还没有保存，是否要离开当前页面？',
					e = e || window.event;
				if (e) {
					e.returnValue = message;
				}
				return message;
			}
		};
		$scope.assignMission = function() {
			mattersgallery.open($scope.siteId, function(matters, type) {
				var app;
				if (matters.length === 1) {
					app = {
						id: $scope.id,
						type: 'article'
					};
					http2.post('/rest/pl/fe/matter/mission/matter/add?site=' + $scope.siteId + '&id=' + matters[0].mission_id, app, function(rsp) {
						$scope.editing.mission = rsp.data;
						$scope.editing.mission_id = rsp.data.id;
						$scope.update('mission_id');
						$scope.submit();
					});
				}
			}, {
				matterTypes: [{
					value: 'mission',
					title: '项目',
					url: '/rest/pl/fe/matter'
				}],
				hasParent: false,
				singleMatter: true
			});
		};
		$scope.submit = function() {
			http2.post('/rest/pl/fe/matter/article/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
				modifiedData = {};
				$scope.modified = false;
				noticebox.success('完成保存');
			});
		};
		$scope.remove = function() {
			if (window.confirm('确定删除？')) {
				http2.get('/rest/pl/fe/matter/article/remove?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
					if ($scope.editing.mission) {
						location = "/rest/pl/fe/matter/mission?site=" + $scope.siteId + "&id=" + $scope.editing.mission.id;
					} else {
						location = '/rest/pl/fe/site/console?site=' + $scope.siteId;
					}
				});
			}
		};
		$scope.update = function(name) {
			$scope.modified = true;
			modifiedData[name] = name === 'body' ? encodeURIComponent($scope.editing[name]) : $scope.editing[name];
			$scope.submit();
		};
		$scope.setPic = function() {
			var options = {
				callback: function(url) {
					$scope.editing.pic = url + '?_=' + (new Date()) * 1;
					$scope.update('pic');
				}
			};
			mediagallery.open($scope.siteId, options);
		};
		$scope.removePic = function() {
			$scope.editing.pic = '';
			$scope.update('pic');
		};
		$scope.$on('tinymce.multipleimage.open', function(event, callback) {
			var options = {
				callback: callback,
				multiple: true,
				setshowname: true
			};
			mediagallery.open($scope.siteId, options);
		});
		$scope.embedMatter = function() {
			var options = {
				matterTypes: $scope.innerlinkTypes,
				singleMatter: true
			};
			if ($scope.editing.mission) {
				options.mission = $scope.editing.mission;
			}
			mattersgallery.open($scope.siteId, function(matters, type) {
				var editor = tinymce.get('body1'),
					dom = editor.dom,
					selection = editor.selection,
					sibling, domMatter, fn, style;

				style = "cursor:pointer";
				if (selection && selection.getNode()) {
					/*选中了页面上已有的元素*/
					sibling = selection.getNode();
					if (sibling !== editor.getBody()) {
						while (sibling.parentNode !== editor.getBody()) {
							sibling = sibling.parentNode;
						}
						angular.forEach(matters, function(matter) {
							fn = "openMatter($event,'" + matter.id + "','" + type + "')";
							domMatter = dom.create('p', {
								'wrap': 'matter'
							}, dom.createHTML('span', {
								"ng-click": fn,
								"style": style
							}, dom.encode(matter.title)));
							dom.insertAfter(domMatter, sibling);
							selection.setCursorLocation(domMatter, 0);
						});
					} else {
						/*没有选中页面上的元素*/
						angular.forEach(matters, function(matter) {
							fn = "openMatter($event,'" + matter.id + "','" + type + "')";
							domMatter = dom.add(editor.getBody(), 'p', {
								'wrap': 'matter'
							}, dom.createHTML('span', {
								"ng-click": fn,
								"style": style
							}, dom.encode(matter.title)));
							selection.setCursorLocation(domMatter, 0);
						});
					}
					editor.focus();
				}
			}, options);
		};
		var insertVideo = function(url) {
			var editor, dom, html;
			if (url.length > 0) {
				editor = tinymce.get('body1');
				dom = editor.dom;
				html = dom.createHTML('p', {},
					dom.createHTML(
						'video', {
							style: 'width:100%',
							controls: "controls",
						},
						dom.createHTML(
							'source', {
								src: url,
								type: "video/mp4",
							})
					)
				);
				editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
			}
		};
		$scope.embedVideo = function() {
			$uibModal.open({
				templateUrl: 'insertMedia.html',
				controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
					$scope.data = {
						url: ''
					};
					$scope.cancel = function() {
						$mi.dismiss()
					};
					$scope.ok = function() {
						$mi.close($scope.data)
					};
				}],
				backdrop: 'static',
			}).result.then(function(data) {
				insertVideo(data.url);
			});
		};
		var insertAudio = function(url) {
			var editor, dom, html;
			if (url.length > 0) {
				editor = tinymce.get('body1');
				dom = editor.dom;
				html = dom.createHTML('p', {}, dom.createHTML('audio', {
					src: url,
					controls: "controls",
				}));
				editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
			}
		};
		$scope.embedAudio = function() {
			if ($scope.mpaccount._env.SAE) {
				$uibModal.open({
					templateUrl: 'insertMedia.html',
					controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
						$scope.data = {
							url: ''
						};
						$scope.cancel = function() {
							$mi.dismiss()
						};
						$scope.ok = function() {
							$mi.close($scope.data)
						};
					}],
					backdrop: 'static',
				}).result.then(function(data) {
					insertAudio(data.url);
				});
			} else {
				$scope.$broadcast('mediagallery.open', {
					mediaType: '音频',
					callback: insertAudio
				});
			}
		};
		$scope.$on('tag.xxt.combox.done', function(event, aSelected) {
			var aNewTags = [];
			angular.forEach(aSelected, function(selected) {
				var existing = false;
				angular.forEach($scope.editing.tags, function(tag) {
					if (selected.title === tag.title) {
						existing = true;
					}
				});
				!existing && aNewTags.push(selected);
			});
			http2.post('/rest/pl/fe/matter/article/tag/add?site=' + $scope.siteId + '&id=' + $scope.id, aNewTags, function(rsp) {
				$scope.editing.tags = $scope.editing.tags.concat(aNewTags);
			});
		});
		$scope.$on('tag.xxt.combox.add', function(event, newTag) {
			var oNewTag = {
				title: newTag
			};
			http2.post('/rest/pl/fe/matter/article/tag/add?site=' + $scope.siteId + '&id=' + $scope.id, [oNewTag], function(rsp) {
				$scope.editing.tags.push(oNewTag);
			});
		});
		$scope.$on('tag.xxt.combox.del', function(event, removed) {
			http2.post('/rest/pl/fe/matter/article/tag/remove?site=' + $scope.siteId + '&id=' + $scope.id, [removed], function(rsp) {
				$scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
			});
		});
		$scope.$on('tag2.xxt.combox.done', function(event, aSelected) {
			var aNewTags = [];
			angular.forEach(aSelected, function(selected) {
				var existing = false;
				angular.forEach($scope.editing.tags2, function(tag) {
					if (selected.title === tag.title) {
						existing = true;
					}
				});
				!existing && aNewTags.push(selected);
			});
			http2.post('/rest/pl/fe/matter/article/tag/add2?site=' + $scope.siteId + '&id=' + $scope.id, aNewTags, function(rsp) {
				$scope.editing.tags2 = $scope.editing.tags2.concat(aNewTags);
			});
		});
		$scope.$on('tag2.xxt.combox.add', function(event, newTag) {
			var oNewTag = {
				title: newTag
			};
			http2.post('/rest/pl/fe/matter/article/tag/add2?site=' + $scope.siteId + '&id=' + $scope.id, [oNewTag], function(rsp) {
				$scope.editing.tags2.push(oNewTag);
			});
		});
		$scope.$on('tag2.xxt.combox.del', function(event, removed) {
			http2.post('/rest/pl/fe/matter/article/tag/remove2?site=' + $scope.siteId + '&id=' + $scope.id, [removed], function(rsp) {
				$scope.editing.tags2.splice($scope.editing.tags2.indexOf(removed), 1);
			});
		});
		$scope.delAttachment = function(index, att) {
			$scope.$root.progmsg = '删除文件';
			http2.get('/rest/pl/fe/matter/article/attachment/del?site=' + $scope.siteId + '&id=' + att.id, function success(rsp) {
				$scope.editing.attachments.splice(index, 1);
				$scope.$root.progmsg = null;
			});
		};
		$scope.downloadUrl = function(att) {
			return '/rest/site/fe/matter/article/attachmentGet?site=' + $scope.siteId + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
		};
		http2.get('/rest/pl/fe/matter/tag/list?site=' + $scope.siteId + '&resType=article&subType=0', function(rsp) {
			$scope.tags = rsp.data;
		});
		http2.get('/rest/pl/fe/matter/tag/list?site=' + $scope.siteId + '&resType=article&subType=1', function(rsp) {
			$scope.tags2 = rsp.data;
		});
		$scope.$watch('editing', function(editing) {
			if (editing && tinymceEditor) {
				tinymceEditor.setContent(editing.body);
			}
		});
		$scope.$on('tinymce.instance.init', function(event, editor) {
			tinymceEditor = editor;
			if ($scope.editing) {
				editor.setContent($scope.editing.body);
			}
		});
		$scope.$on('tinymce.content.change', function(event, changed) {
			var content;
			content = tinymceEditor.getContent();
			if (content !== $scope.editing.body) {
				$scope.editing.body = content;
				modifiedData['body'] = encodeURIComponent(content);
				$scope.modified = true;
			}
		});
	}]);
});