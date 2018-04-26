'use strict';
var ngMod = angular.module('editor.ui.xxt', ['ui.bootstrap']);
ngMod.directive('tmsEditor', ['$q', 'http2', function($q, http2) {
    /**
     * 根据触屏事件，设置选中的内容
     */
    function _setSelectionByTouch(oTarget, oTouchTracks) {
        var oSelection, oRange, cmd, args;
        oSelection = _iframeDoc.getSelection();
        oRange = document.createRange();
        oRange.selectNodeContents(oTarget);
        oSelection.removeAllRanges();
        oSelection.addRange(oRange);

        return oRange;
    }

    var _iframeDoc, _divContent, _bDesignMode;
    /**
     * 外部服务接口
     */
    window.tmsEditor = (function() {
        return {
            // 初始化编辑器内容
            initHTML: function(id) {},
            finish: function() {
                _divContent.blur();
                return _divContent.innerHTML;
            }
        }
    })();
    return {
        restrict: 'EA',
        scope: { id: '@', content: '=', cmds: '=' },
        replace: true,
        template: function(element, attrs) {
            var t;
            t = '<div>';
            t += '<div class="form-group"><iframe src="javascript:void(0);" style="display:block;width:100%;border:1px solid #ddd;"></iframe></div>';
            t += '<div class="btn-group">';
            //t += '<button class="btn btn-default btn-sm" command="remove">X</button>';
            if (/Android|iPhone|iPad/i.test(navigator.userAgent)) {
                t += '<button class="btn btn-default btn-sm" action="ToggleDesign">设置样式</button>';
            }
            t += '<button class="btn btn-default btn-sm" command="bold"><span style="font-weight:blod;">B</span></button>';
            t += '<button class="btn btn-default btn-sm" command="italic"><i>I</i></button>';
            t += '<button class="btn btn-default btn-sm" command="underline"><span style="text-decoration:underline;">U</span></button>';
            t += '<button class="btn btn-default btn-sm" command="BackColor"><i class="glyphicon glyphicon-text-background"></i></button>';
            t += '<button class="btn btn-default btn-sm" action="InsertImage"><i class="glyphicon glyphicon-picture"></i></button>';
            t += '</div>';
            t += '</div>';
            return t;
        },
        link: function($scope, elem, attrs) {
            var iframeHTML, iframeNode;
            /* 初始化 */
            iframeHTML = '<!DOCTYPE html><html><head>';
            iframeHTML += '<meta charset="utf-8"></head>';
            iframeHTML += '<style>';
            iframeHTML += '.tms-editor-content img{max-width:100%;}';
            iframeHTML += '</style>';
            iframeHTML += '<body onload="window.parent.tmsEditor.initHTML(\'' + $scope.id + '\');">';
            iframeHTML += '<div class="tms-editor-content" contentEditable="true">' + $scope.content + '</div>';
            iframeHTML += '</body></html>';
            iframeNode = document.querySelector('#' + $scope.id + ' iframe');
            if (iframeNode.contentDocument) {
                _iframeDoc = iframeNode.contentDocument
            } else if (iframeNode.contentWindow) {
                _iframeDoc = iframeNode.contentWindow.iframeDocument;
            }
            _iframeDoc.open();
            _iframeDoc.write(iframeHTML);
            _iframeDoc.close();
            _divContent = _iframeDoc.querySelector('body>div');
            var oTouchTracks = {};
            _iframeDoc.oncontextmenu = function(e) {
                if (_bDesignMode) {
                    e.preventDefault();
                }
            };
            _iframeDoc.ontouchstart = function(event) {
                var oTouch;
                if (_bDesignMode) {
                    if (event.targetTouches.length === 1) {
                        oTouch = event.targetTouches[0];
                        oTouchTracks.start = { x: oTouch.pageX, y: oTouch.pageY };
                        event.preventDefault();
                        _divContent.contentEditable = false;
                    }
                }
            };
            _iframeDoc.ontouchmove = function(event) {
                var oTouch;
                if (_bDesignMode) {

                    if (event.targetTouches.length === 1) {
                        oTouch = event.targetTouches[0];
                        oTouchTracks.end = { x: oTouch.pageX, y: oTouch.pageY };
                        event.preventDefault();
                    }
                }
            };
            _iframeDoc.ontouchend = function(event) {
                if (_bDesignMode) {
                    if (oTouchTracks.start && oTouchTracks.end) {　　　　
                        _setSelectionByTouch(event.target, oTouchTracks);
                        _divContent.contentEditable = true;
                        event.preventDefault();
                    }
                }
            };
            /* 设置基本样式 */
            document.querySelectorAll('#' + $scope.id + ' button[command]').forEach(function(eleBtn) {
                eleBtn.addEventListener('click', function() {
                    var cmd, args;
                    cmd = this.getAttribute('command').toLowerCase();
                    switch (cmd) {
                        case 'backcolor':
                            args = 'yellow';
                            break;
                    }
                    _iframeDoc.execCommand(cmd, false, args);
                });
            });
            /* 开启关闭设置样式 */
            var eleBtnToggleDesign;
            if (eleBtnToggleDesign = document.querySelector('#' + $scope.id + ' button[action=ToggleDesign]')) {
                eleBtnToggleDesign.addEventListener('click', function() {
                    _bDesignMode = !_bDesignMode;
                    _iframeDoc.getSelection().removeAllRanges();
                });
            }
            /* 插入图片操作 */
            if (window.xxt && window.xxt.image) {
                var eleBtnInsertImage;
                if (eleBtnInsertImage = document.querySelector('#' + $scope.id + ' button[action=InsertImage]')) {
                    eleBtnInsertImage.addEventListener('click', function() {
                        window.xxt.image.choose($q.defer()).then(function(imgs) {
                            imgs.forEach(function(oImg) {
                                http2.post('/rest/site/fe/matter/upload/image?site=platform', oImg).then(function(rsp) {
                                    console.log(rsp);
                                    _iframeDoc.execCommand('InsertImage', false, rsp.data.url);
                                });
                            });
                        });
                    });
                }
            }
        }
    }
}]);