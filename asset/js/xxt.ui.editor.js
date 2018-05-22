'use strict';

require('./xxt.ui.paste.js');
require('./xxt.ui.url.js');

var ngMod = angular.module('editor.ui.xxt', ['ui.bootstrap', 'url.ui.xxt', 'paste.ui.xxt']);
ngMod.controller('tmsEditorController', ['$scope', 'tmsUrl', function($scope, tmsUrl) {
    /* 插入链接 */
    $scope.insertLink = function() {
        tmsUrl.fetch().then(function(oResult) {
            var oUrl;
            if (oUrl = oResult.summary) {
                $scope.iframeDoc.execCommand('insertHTML', false, '<a href="' + oUrl.url + '" target="_blank">' + (oUrl.title || '链接') + '</a>');
            }
        });
    };
    /* 开启关闭设置样式 */
    $scope.toggleDesignMode = function() {
        $scope.designMode = !$scope.designMode;
        $scope.iframeDoc.getSelection().removeAllRanges();
    };
}]);
ngMod.directive('tmsEditor', ['$q', 'http2', 'tmsPaste', function($q, http2, tmsPaste) {
    function _calcTextWidth(text) {
        var divMock, height, width;
        divMock = document.createElement('DIV');
        divMock.style.position = 'absolute';
        divMock.style.visibility = 'hidden';
        divMock.style.height = 'auto';
        divMock.style.width = 'auto';
        divMock.style.whiteSpace = 'nowrap';
        divMock.innerHTML = text;
        _iframeDoc.querySelector('body').appendChild(divMock);
        height = divMock.clientHeight;
        width = divMock.clientWidth;
        _iframeDoc.querySelector('body').removeChild(divMock);

        return { height: height, width: width, charWidth: parseInt(width / text.length) };
    }
    /**
     * 根据触屏事件，设置选中的内容
     */
    function _setSelectionByTouch(oTarget, oTouchTracks) {
        var oParam, oSelection, oRange, oStartNode;
        oParam = {
            start: {
                touch: oTouchTracks.start
            },
            end: {
                touch: oTouchTracks.end
            }
        };
        if (oTarget.childNodes.length) {
            for (var i = 0, ii = oTarget.childNodes.length; i < ii; i++) {
                if (oTarget.childNodes[i].nodeType === Node.TEXT_NODE) {
                    oStartNode = oTarget.childNodes[i];
                    break;
                }
            }
        }
        if (oStartNode) {
            oParam.start.element = {
                top: oStartNode.parentElement.offsetTop,
                left: oStartNode.parentElement.offsetLeft,
                width: oStartNode.parentElement.offsetWidth,
                height: oStartNode.parentElement.offsetHeight,
            }
            oParam.text = _calcTextWidth(oStartNode.nodeValue);
            oParam.startCharAt = parseInt((oTouchTracks.start.x - oStartNode.parentElement.offsetLeft) / oParam.text.charWidth);
            oParam.endCharAt = parseInt((oTouchTracks.end.x - oStartNode.parentElement.offsetLeft) / oParam.text.charWidth);
            oRange = document.createRange();
            oRange.setStart(oStartNode, oParam.startCharAt);
            oRange.setEnd(oStartNode, oParam.endCharAt);
        } else {
            oRange = document.createRange();
            oRange.selectNodeContents(oTarget);
        }
        oSelection = _iframeDoc.getSelection();
        oSelection.removeAllRanges();
        oSelection.addRange(oRange);

        return oRange;
    }

    var _iframeDoc, _divContent;
    /**
     * 外部服务接口
     */
    window.tmsEditor = (function() {
        return {
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
        controller: 'tmsEditorController',
        template: require('../html/ui-editor.html'),
        link: function($scope, elem, attrs) {
            var iframeHTML, iframeNode;
            /* 初始化 */
            iframeHTML = '<!DOCTYPE html><html><head>';
            iframeHTML += '<meta charset="utf-8"></head>';
            iframeHTML += '<style>';
            iframeHTML += 'html,body,body>div{width:100%;height:100%;}body{font-size:16px;margin:0}.tms-editor-content:empty::before{color:lightgrey;content:attr(placeholder);}.tms-editor-content img{max-width:100%;}';
            iframeHTML += '</style>';
            iframeHTML += '<body>';
            iframeHTML += '<div class="tms-editor-content " contentEditable="true" placeholder="添加内容...">' + $scope.content + '</div>';
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
            $scope.iframeDoc = _iframeDoc;
            _divContent = _iframeDoc.querySelector('body>div');
            /* 页面加载完成后进行初始化 */
            _iframeDoc.querySelector('body').onload = function() {
                _divContent.contentEditable = true;
            };
            $scope.mobileAgent = /Android|iPhone|iPad/i.test(navigator.userAgent);
            $scope.designMode = !$scope.mobileAgent;
            /* 触屏事件处理 */
            var oTouchTracks = {};
            _iframeDoc.oncontextmenu = function(e) {
                if ($scope.designMode) {
                    e.preventDefault();
                }
            };
            _iframeDoc.ontouchstart = function(event) {
                var oTouch;
                if ($scope.designMode) {
                    if (event.targetTouches.length === 1) {
                        oTouch = event.targetTouches[0];
                        oTouchTracks.start = { x: oTouch.pageX, y: oTouch.pageY };
                        event.preventDefault();
                        _iframeDoc.getSelection().removeAllRanges();
                        _divContent.contentEditable = false;
                    }
                }
            };
            _iframeDoc.ontouchmove = function(event) {
                var oTouch;
                if ($scope.designMode) {

                    if (event.targetTouches.length === 1) {
                        oTouch = event.targetTouches[0];
                        oTouchTracks.end = { x: oTouch.pageX, y: oTouch.pageY };
                        event.preventDefault();
                    }
                }
            };
            _iframeDoc.ontouchend = function(event) {
                if ($scope.designMode) {
                    if (oTouchTracks.start && oTouchTracks.end) {
                        /* 是否进行了有效的移动 */
                        if (Math.abs(oTouchTracks.start.x - oTouchTracks.end.x) >= 16) {
                            _setSelectionByTouch(event.target, oTouchTracks);
                        }　
                        _divContent.contentEditable = true;
                        event.preventDefault();
                    }
                }
            };
            /* 粘贴时处理格式 */
            _divContent.addEventListener('paste', function(e) {
                var text;
                e.preventDefault();
                text = e.clipboardData.getData('text/plain');
                tmsPaste.onpaste(text, { doc: _iframeDoc });
            });
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
            /* 插入图片操作 */
            if (window.xxt && window.xxt.image) {
                var eleBtnInsertImage;
                if (eleBtnInsertImage = document.querySelector('#' + $scope.id + ' button[action=InsertImage]')) {
                    eleBtnInsertImage.addEventListener('click', function() {
                        window.xxt.image.choose($q.defer()).then(function(imgs) {
                            imgs.forEach(function(oImg) {
                                http2.post('/rest/site/fe/matter/upload/image?site=platform', oImg).then(function(rsp) {
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