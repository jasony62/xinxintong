angular.module('tmseditor',['ui.bootstrap'])
.controller('tmsEditorController',['$scope',function($scope) {
}])
.directive('tmseditor',function(){
    var baseURL = function(){
        // Get base where the tmseditor script is located
        var baseURL,scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            var src = scripts[i].src;
            // Script types supported:
            if (/tmseditor\.js/.test(src)) {
                baseURL = src.substring(0, src.lastIndexOf('/'));
                break;
            }
        }
        return baseURL;
    }
    /**
    * 外部服务接口
    */
    window.tmsEditor = (function(){
        var onEditWrap = function(doc, el) {
            var activedWrap;
            if (activedWrap = doc.querySelector('[wrap].active'))
                activedWrap.classList.remove('active');
            el.classList.add('active');
        };
        var setSelection = function(doc, el) {
            var sel,rng;
            sel = doc.getSelection();
            rng = doc.createRange();
            rng.selectNodeContents(el);
            rng.collapse(false);
            sel.removeAllRanges();
            sel.addRange(rng);
            el.focus();
        };
        var activeWrap = function(doc, wrap) {
            var type;
            type = wrap.getAttribute('wrap');
            switch (type){
                case 'text':
                    setSelection(doc,wrap);
                    break;
                case 'button':
                    setSelection(doc,wrap.firstChild.firstChild);
                    break;
            }
            onEditWrap(doc,wrap);
        };
        var addWrapRow = function(doc, wrap) {
            var activedWrap;
            if (activedWrap = doc.querySelector('[wrap].active')) {
                if (activedWrap === activedWrap.parentNode.lastChild)
                    activedWrap.parentNode.appendChild(wrap);
                else
                    activedWrap.parentNode.insertBefore(wrap,activedWrap.nextSibling);
            } else {
                doc.body.appendChild(wrap);
            }
            activeWrap(doc,wrap);
            scrollToTop(doc,wrap);
        };
        var scrollToTop = function(doc, wrap) {
            var win = doc.defaultView,cs,y,offsety;
            y = wrap.offsetTop;
            cs = doc.defaultView.getComputedStyle(doc.body, null); 
            offsety = cs.getPropertyValue('margin-top').replace('px','')*1;
            offsety += doc.body.clientTop;
            offsety += cs.getPropertyValue('padding-top').replace('px','')*1;
            win.scrollTo(0,y-offsety);
        };
        return {
            // 初始化编辑器内容
            initHTML:function(id) {
                var doc = this.getDoc(id);
                [].forEach.call(doc.querySelectorAll('[wrap]'), function(el){
                    switch (el.getAttribute('wrap')){
                        case 'text':
                            el.contentEditable = 'true';
                            break;
                        case 'button':
                            var span = el.firstChild.firstChild;
                            span.contentEditable = 'true';
                            span.onblur = function(){
                                if (this.innerHTML === '<br>' || this.innerHTML.length === 0)
                                    this.innerHTML = '请输入名称';
                            };
                            break;
                        case 'radio':
                        case 'checkbox':
                            var span = el.firstChild.lastChild;
                            span.contentEditable = 'true';
                            el.firstChild.firstChild.setAttribute('disabled', 'true');
                            break;
                    }
                    el.onclick = function(){onEditWrap(doc,el);};
                });
            },
            setHTML:function(id,html) {
                //todo
                var node = this.getDoc(id).body;
                node.innerHTML = html;
            },
            getHTML:function(id) {
                var node = this.getDoc(id).body;
                /**
                * 清理数据
                 */
                node = node.cloneNode(true);
                [].forEach.call(node.querySelectorAll('[contentEditable]'),function(el){
                    el.removeAttribute('contentEditable');
                });
                [].forEach.call(node.querySelectorAll('[wrap].active'),function(el){
                    el.classList.remove('active');
                });
                [].forEach.call(node.querySelectorAll('input[disabled]'),function(el){
                    el.removeAttribute('disabled');
                });
                return node.innerHTML;
            },
            addHTML:function(id,content) {
                var node = this.getDoc(id).body;
                node.innerHTML += content;
            },
            getDoc:function(id) {
                var f = document.getElementById(id);
                f = f.getElementsByTagName('iframe')[0];
                if(f.contentDocument)
                    d = f.contentDocument
                else if(f.contentWindow)
                    d = f.contentWindow.iframeDocument;
                return d;
            }, 
            removeActive:function(doc) {
                var activedWrap;
                if (activedWrap = doc.querySelector('[wrap].active'))
                    activedWrap.parentNode.removeChild(activedWrap);
            },
            addText:function(doc) {
                var p = doc.createElement('DIV');
                p.setAttribute('wrap', 'text');
                p.setAttribute('contentEditable', 'true');
                p.classList.add('form-group');
                p.onclick = function(){onEditWrap(doc,p);};
                addWrapRow(doc,p);
            },
            addButton:function(doc,prop){
                var p,btn,span;
                p = doc.createElement('DIV');
                p.setAttribute('wrap', 'button');
                p.classList.add('form-group');
                p.onclick = function(){onEditWrap(doc,p);};
                btn = doc.createElement('BUTTON');
                btn.setAttribute('id', prop.id);
                btn.setAttribute('ng-click', prop.action);
                btn.classList.add('btn');
                btn.classList.add('btn-primary');
                btn.classList.add('btn-block');
                span = doc.createElement('SPAN');
                span.innerHTML = prop.title;
                span.setAttribute('contentEditable', 'true');
                btn.appendChild(span);
                p.appendChild(btn);
                addWrapRow(doc,p);
            },
            addInput:function(doc,prop){
                var p,inp;
                p = doc.createElement('DIV');
                p.setAttribute('wrap','input');
                p.classList.add('form-group');
                p.onclick = function(){onEditWrap(doc,p);};
                inp = doc.createElement('INPUT');
                inp.setAttribute('type', 'text');
                inp.setAttribute('title', prop.title);
                inp.setAttribute('ng-model', prop.model);
                inp.setAttribute('placeholder', prop.placeholder);
                inp.classList.add('form-control');
                p.appendChild(inp);
                addWrapRow(doc,p);
            },
            addRadio:function(doc,prop){
                var p,label,inp,span;
                p = doc.createElement('DIV');
                p.setAttribute('wrap','radio');
                p.classList.add('radio');
                p.onclick = function(){onEditWrap(doc,p);};
                label = doc.createElement('LABEL');
                inp = doc.createElement('INPUT');
                inp.setAttribute('type', 'radio');
                inp.setAttribute('name', prop.name);
                inp.setAttribute('value', prop.value);
                inp.setAttribute('title', prop.title);
                inp.setAttribute('ng-model', prop.model);
                inp.setAttribute('disabled', 'true');
                span = doc.createElement('SPAN');
                span.setAttribute('contentEditable', 'true');
                span.innerHTML = prop.label;
                label.appendChild(inp);
                label.appendChild(span);
                p.appendChild(label);
                addWrapRow(doc,p);
            },
            addCheckbox:function(doc,prop){
                var p,label,inp,span;
                p = doc.createElement('DIV');
                p.setAttribute('wrap','checkbox');
                p.classList.add('checkbox');
                p.onclick = function(){onEditWrap(doc,p);};
                label = doc.createElement('LABEL');
                inp = doc.createElement('INPUT');
                inp.setAttribute('type', 'checkbox');
                inp.setAttribute('name', prop.name);
                inp.setAttribute('title', prop.title);
                inp.setAttribute('ng-model', prop.model);
                inp.setAttribute('disabled', 'true');
                span = doc.createElement('SPAN');
                span.setAttribute('contentEditable', 'true');
                span.innerHTML = prop.label;
                label.appendChild(inp);
                label.appendChild(span);
                p.appendChild(label);
                addWrapRow(doc,p);
            },
            addTextarea:function(doc,prop){
                var p,inp;
                p = doc.createElement('DIV');
                p.setAttribute('wrap','input');
                p.classList.add('form-group');
                p.onclick = function(){onEditWrap(doc,p);};
                inp = doc.createElement('TEXTAREA');
                inp.setAttribute('title', prop.title);
                inp.setAttribute('ng-model', prop.model);
                inp.setAttribute('placeholder', prop.placeholder);
                inp.setAttribute('rows', prop.rows);
                inp.classList.add('form-control');
                p.appendChild(inp);
                addWrapRow(doc,p);
            }
        }
    })();
    return {
        restrict:'EA',
        scope:{id:'@',content:'=',cmds:'=',contenteditable:'=',update:'&'},
        controller:'tmsEditorController',
        replace:true,
        templateUrl:function(){
            var t = (new Date()).getTime();
            return baseURL()+'/tmseditor.tpl.html?_='+t;
        },
        link:function($scope, elem, attrs){
            var t,iframeHTML,iframeNode,iframeDoc;
            /**
            * 页面初始化
            */
            t = (new Date()).getTime();
            iframeHTML = '<!DOCTYPE html><html><head>';
            iframeHTML += '<meta charset="utf-8">';
            iframeHTML += '<link href="'+baseURL()+'/tmseditor.css?_='+t+'" rel="stylesheet">';
            iframeHTML += '</head><body onload="window.parent.tmsEditor.initHTML(\''+$scope.id+'\');">';
            iframeHTML += '<div id="debug"></div>';
            iframeHTML += $scope.content;
            iframeHTML += '</body></html>';
            iframeNode = $('#'+$scope.id).find('iframe')[0];
            if(iframeNode.contentDocument)
                iframeDoc = iframeNode.contentDocument
            else if(iframeNode.contentWindow)
                iframeDoc = iframeNode.contentWindow.iframeDocument;
            iframeDoc.open();
            iframeDoc.write(iframeHTML);
            iframeDoc.close();
            /**
            * 工具栏事件
            */
            elem.find('button[command]').click(function(){
                var cmd = $(this).attr('command').toLowerCase(),args=false;
                switch (cmd){
                    case 'addtext':
                        window.tmsEditor.addText(iframeDoc);
                        break;
                    case 'remove':
                        window.tmsEditor.removeActive(iframeDoc);
                        break;
                    case 'backcolor':
                        args = 'yellow';
                        break;
                }
                iframeDoc.execCommand(cmd,false,args);
            });
            /**
            * 失去焦点是提交修改的内容
            */
            if (iframeNode.contentWindow) {
                $(iframeNode.contentWindow).blur(function(){
                    $scope.content = tmsEditor.getHTML($scope.id);
                    $scope.$apply('content');
                    $scope.update();
                });
            } else {
                $(iframeDoc).blur(function(){
                    $scope.content = tmsEditor.getHTML($scope.id);
                    $scope.$apply('content');
                    $scope.update();
                });
            }
            /**
             * 数据异步加载问题
             * 只做一次
             */
            $scope.$watch('content', function(nv, ov){
                if (ov === undefined && nv && iframeDoc && iframeDoc.body)
                    iframeDoc.body.innerHTML = nv;
            });
        }
    }
});
