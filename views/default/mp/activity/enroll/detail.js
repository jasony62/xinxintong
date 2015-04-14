xxtApp.controller('enrollCtrl',['$scope','http2',function($scope,http2) {
    $scope.roundState = ['新建','启用','停止'];
    $scope.update = function(name){
        if (!angular.equals($scope.activity, $scope.persisted)) {
            var p = {};
            p[name] = $scope.activity[name];
            http2.post('/rest/mp/activity/enroll/update?aid='+$scope.aid, p, function(rsp){
                $scope.persisted = angular.copy($scope.activity);
            });
        }
    }; 
    $scope.$watch('aid', function(nv) {
        if (nv && nv.length) 
            http2.get('/rest/mp/activity/enroll?aid='+nv, function(rsp){
                $scope.activity = rsp.data;
                $scope.activity.pages.form.title = '登记信息页';
                $scope.activity.pages.result.title = '查看结果页';
                $scope.activity.canSetReceiver = 'Y';
                $scope.persisted = angular.copy($scope.activity);
                $scope.picGalleryUrl = '/kcfinder/browse.php?lang=zh-cn&type=图片&mpid='+$scope.activity.mpid;
            });
    });
}])
.controller('settingCtrl',['$scope','http2','matterTypes','$modal',function($scope,http2,matterTypes,$modal) {
    $scope.matterTypes = matterTypes;
    $scope.setPic = function(){
        $scope.$broadcast('picgallery.open', function(url){
            var t=(new Date()).getTime(),url=url+'?_='+t,nv={pic:url};
            http2.post('/rest/mp/activity/enroll/update?aid='+$scope.aid, nv, function() {
                $scope.activity.pic = url;
            });
        }, false);
    }; 
    $scope.removePic = function(){
        var nv = {pic:''};
        http2.post('/rest/mp/activity/enroll/update?aid='+$scope.aid, nv, function() {
            $scope.activity.pic = '';
        });
    };
    $scope.setSuccessReply = function(){
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType){
            if (aSelected.length === 1) {
                var p = {mt: matterType, mid: aSelected[0].id};
                http2.post('/rest/mp/activity/enroll/setSuccessReply?aid='+$scope.activity.aid, p, function(rsp) {
                    $scope.activity.successMatter = aSelected[0];
                });
            }
        });
    };
    $scope.setFailureReply = function(){
        $scope.$broadcast('mattersgallery.open', function(aSelected, matterType){
            if (aSelected.length === 1) {
                var p = {mt: matterType, mid: aSelected[0].id};
                http2.post('/rest/mp/activity/enroll/setFailureReply?aid='+$scope.activity.aid, p, function(rsp) {
                    $scope.activity.failureMatter = aSelected[0];
                });
            }
        });
    };
    $scope.removeSuccessReply = function(){
        var p = {mt:'', mid: ''};
        http2.post('/rest/mp/activity/enroll/setSuccessReply?aid='+$scope.activity.aid, p, function(rsp) {
            $scope.activity.successMatter = null;
        });
    };
    $scope.removeFailureReply = function(){
        var p = {mt:'', mid:''};
        http2.post('/rest/mp/activity/enroll/setFailureReply?aid='+$scope.activity.aid, p, function(rsp) {
            $scope.activity.failureMatter = null;
        });
    };
    $scope.addRound = function(){
        $modal.open({
            templateUrl:'roundEditor.html',
            backdrop:'static',
            resolve:{
                roundState: function() {return $scope.roundState;}
            },
            controller:['$scope','$modalInstance','roundState', function($scope,$modalInstance,roundState){
                $scope.round = {state:0};
                $scope.roundState = roundState;
                $scope.close = function() {$modalInstance.dismiss();};
                $scope.ok = function() {$modalInstance.close($scope.round);};
                $scope.start = function() {
                    $scope.round.state = 1;
                    $modalInstance.close($scope.round);
                };
            }]
        }).result.then(function(newRound){
            http2.post('/rest/mp/activity/enroll/addRound?aid='+$scope.activity.aid, newRound, function(rsp){
                if ($scope.activity.rounds.length > 0 && rsp.data.state == 1)
                    $scope.activity.rounds[1].state = 2;
                $scope.activity.rounds.splice(0, 0 ,rsp.data);
            });
        });
    };
    $scope.openRound = function(round) {
        $modal.open({
            templateUrl:'roundEditor.html',
            backdrop:'static',
            resolve:{
                roundState: function() {return $scope.roundState;}
            },
            controller:['$scope','$modalInstance','roundState', function($scope,$modalInstance,roundState){
                $scope.round = angular.copy(round);
                $scope.roundState = roundState;
                $scope.close = function() {$modalInstance.dismiss();};
                $scope.ok = function() {$modalInstance.close({action:'update',data:$scope.round});};
                $scope.remove = function() {$modalInstance.close({action:'remove'});};
                $scope.start = function() {
                    $scope.round.state = 1;
                    $modalInstance.close({action:'update',data:$scope.round});
                };
            }]
        }).result.then(function(rst){
            if (rst.action === 'update') {
                var url = '/rest/mp/activity/enroll/updateRound';
                url += '?aid='+$scope.activity.aid;
                url += '&rid='+round.rid;
                http2.post(url, rst.data, function(rsp){
                    if ($scope.activity.rounds.length > 1 && rst.data.state == 1)
                        $scope.activity.rounds[1].state = 2;
                    angular.extend(round, rst.data);
                });
            } else if (rst.action === 'remove') {
                var url = '/rest/mp/activity/enroll/removeRound';
                url += '?aid='+$scope.activity.aid;
                url += '&rid='+round.rid;
                http2.get(url, function(rsp){
                    var i = $scope.activity.rounds.indexOf(round);
                    $scope.activity.rounds.splice(i,1);
                });
            }
        });
    };
}])
.controller('pageCtrl',['$scope','http2','$modal','$timeout',function($scope,http2,$modal,$timeout) {
    var addWrap = function(page, name, attrs, html){
        var dom,body,wrap,newWrap,selection,activeEditor;
        activeEditor = tinymce.get(page.name);
        dom = activeEditor.dom;
        body = activeEditor.getBody();
        selection = activeEditor.selection
        wrap = selection.getNode();
        if (wrap === body) {
            newWrap = dom.add(body, name, attrs, html);
        } else {
            while (wrap.parentNode !== body)
                wrap = wrap.parentNode;
            newWrap = dom.create(name,attrs,html);
            dom.insertAfter(newWrap, wrap);
        }
        selection.setCursorLocation(newWrap,0);
        activeEditor.focus();
    };
    var extractSchema = function(html) {
        var extractModelId = function(model) {
            var id;
            if (id = model.match(/ng-model=\"data\.(.+?)\"/)) {
                id = id.pop().replace('ng-model="data.','').replace('"','');
                return id;
            }
            return false;
        };
        var extractRadioModelOp = function(model) {
            var v,l;
            if (v = schema.match(/value=\"(.+?)\"/))
                v = v.pop().replace('value=','').replace(/\"/g,'');
            if (l = schema.match(/data-label=\"(.+?)\"/)) 
                l = l.pop().replace('data-label=','').replace(/\"/g,'');
            return {v:v,l:l};
        };
        var extractCheckboxModelOp = function(model) {
            var v,l;
            if (v = schema.match(/ng-model=\"(.+?)\"/))
                v = v.pop().replace('ng-model=','').replace(/\"/g,'').split('.').pop();
            if (l = schema.match(/data-label=\"(.+?)\"/)) 
                l = l.pop().replace('data-label=','').replace(/\"/g,'');
            return {v:v,l:l};
        };
        var defs = {},i,schemas,schema,type,title,modelId;
        schemas = html.match(/<(div|li).+?wrap=(.+?)>.+?<\/(div|li)>/gi);
        for (i in schemas) {
            schema = schemas[i];
            type = schema.match(/wrap=\".+?\"/).pop().replace('wrap=', '').replace(/\"/g, '');
            switch (type) {
                case 'input':
                case 'radio':
                case 'checkbox':
                    title = schema.match(/\btitle=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                    if (schema.match(/(<textarea|type=\"text\")/)) {
                        if (modelId = extractModelId(schema))
                            defs[modelId] = {id:modelId,title:title,type:type};
                    } else if (schema.match(/type=\"radio\"/)) {
                        if (modelId = extractModelId(schema)) {
                            if (defs[modelId] === undefined)
                                defs[modelId] = {id:modelId,title:title,type:type,op:[]};
                            defs[modelId].op.push(extractRadioModelOp(schema));
                        }
                    } else if (schema.match(/type=\"checkbox\"/)) {
                        if (modelId = extractModelId(schema)) {
                            modelId = modelId.split('.')[0];
                            if (defs[modelId] === undefined)
                                defs[modelId] = {id:modelId,title:title,type:type,op:[]};
                            defs[modelId].op.push(extractCheckboxModelOp(schema));
                        }
                    }
                    break;
                case 'img':
                    title = schema.match(/title=\".*?\"/).pop().replace('title=', '').replace(/\"/g, '');
                    if (title.length === 0) title='（没有指定字段标题）';
                    if (modelId = schema.match(/ng-repeat=\"img in data\.(.+?)\"/)) {
                        modelId = modelId.pop().replace(/ng-repeat=\"img in data\./,'').replace(/\"/g,'');
                        defs[modelId] = {id:modelId,title:title,type:type};
                    }
                    break;
            }
        }
        return defs;
    };
    var CusdataCtrl = function($scope,$modalInstance) {
        $scope.def = {type:'0',name:'',showname:'1',align:'V'};
        $scope.addOption = function() {
            if ($scope.def.ops === undefined)
                $scope.def.ops = [];
            var newOp = {text:''};
            $scope.def.ops.push(newOp);
            $timeout(function(){$scope.$broadcast('xxt.editable.add', newOp);});
        };
        $scope.$on('xxt.editable.remove', function(e, op){
            var i = $scope.def.ops.indexOf(op);
            $scope.def.ops.splice(i,1);
        });
        $scope.ok = function () {
            $modalInstance.close($scope.def);
        };
        $scope.cancel = function () {
            $modalInstance.dismiss();
        };
    };
    var embedRecord = function(page, def) {
        if (def.schema === undefined) return;
        var i,s;
        for (i in def.schema) {
            s = def.schema[i];
            if (!s.checked) continue;
            switch (s.type) {
                case 'input':
                    addWrap(page, 'div', {wrap:'text',class:'form-group'}, '<label>'+s.title+'</label><p class="form-control-static">{{Record.current.data.'+s.id+'}}</p>');
                    break;
                case 'radio':
                case 'checkbox':
                    addWrap(page, 'div', {wrap:'text',class:'form-group'}, '<label>'+s.title+'</label><p class="form-control-static">{{Record.current.data.'+s.id+'}}</p>');
                    break;
                case 'img':
                    addWrap(page, 'div', {wrap:'text',class:'form-group'}, '<label>'+s.title+'</label><ul><li ng-repeat="img in Record.current.data.'+s.id+'.split(\',\')"><img ng-src="{{img}}"></li></ul>');
                    break;
            }
        }
        if (def.addEnrollAt) {
            html = "<label>登记时间</label><p>{{(Record.current.enroll_at*1000)|date:'yyyy-MM-dd HH:mm'}}</p>";
            addWrap(page, 'div', {wrap:'text',class:'form-group'}, html);
        }
        if (def.addNickname) {
            html = "<label>昵称</label><p>{{Record.current.enroller.nickname}}</p>";
            addWrap(page, 'div', {wrap:'text',class:'form-group'}, html);
        }
    };
    var embedList = function(page, def) {
        var dataApi,onclick,html;
        dataApi = def.dataScope === 'A' ? "Record.nextPage()" : "Record.nextPage('user')";
        onclick = def.onclick.length ? " ng-click=\"gotoPage($event,'"+def.onclick+"',r.enroll_key)\"" : '';
        html = '<ul class="list-group" infinite-scroll="'+dataApi+'" infinite-scroll-disabled="Record.busy" infinite-scroll-distance="1">';
        html += '<li class="list-group-item" ng-repeat="r in Record.list"'+onclick+'>';
        if (def.addEnrollAt) {
            html += "<div><label>登记时间</label><div>{{(r.enroll_at*1000)|date:'yyyy-MM-dd HH:mm'}}</div></div>";
        }
        if (def.addNickname) {
            html += "<div><label>昵称</label><div>{{r.nickname}}</div></div>";
        }
        if (def.schema) {
            var i,s;
            for (i in def.schema) {
                s = def.schema[i];
                if (!s.checked) continue;
                switch (s.type) {
                    case 'input':
                        html += '<div class="form-group"><label>'+s.title+'</label><p class="form-control-static">{{r.data.'+s.id+'}}</p></div>';
                        break;
                    case 'radio':
                    case 'checkbox':
                        html += '<div class="form-group"><label>'+s.title+'</label><p class="form-control-static">{{r.data.'+s.id+'}}</p></div>';
                        break;
                    case 'img':
                        html += '<div class="form-group"><label>'+s.title+'</label><ul><li ng-repeat="img in r.data.'+s.id+'.split(\',\')"><img ng-src="{{img}}"></li></ul></div>';
                        break;
                }
            }
        }
        if (def.canLike === 'Y') {
            html += '<div title="总赞数">{{r.score}}</div>';
            html += "<div ng-if='!r.myscore'><a href='javascript:void(0)' ng-click='Record.like($event,r)'>赞</a></div>";
            html += "<div ng-if='r.myscore==1'>已赞</div>";
        }
        html += "</li></ul>";
        addWrap(page, 'div', {wrap:'list',class:'form-group'}, html);
        page.html = tinymce.get(page.name).getContent();
        $scope.updPage(page, 'html');
    }; 
    var embedRounds = function(page, def) {
        var onclick,html;
        onclick = def.onclick.length ? " ng-click=\"gotoPage($event,'"+def.onclick+"',null,r.rid)\"" : '';
        html = "<ul class='list-group' tms-init='Round.nextPage()'><li class='list-group-item' ng-repeat='r in Round.list'"+onclick+"><div>{{r.title}}</div></li></ul>";
        addWrap(page, 'div', {wrap:'list',class:'form-group'}, html);
    };
    var embedRemarks = function(page, def) {
        var ctrl,dataApi,onclick,html,js;
        html = "<ul class='list-group'><li class='list-group-item' ng-repeat='r in Record.current.remarks'><div>{{r.remark}}</div><div>{{r.nickname}}</div><div>{{(r.create_at*1000)|date:'yyyy-MM-dd HH:mm'}}</div></li></ul>";
        addWrap(page, 'div', {wrap:'list',class:'form-group'}, html);
    };
    $scope.innerlinkTypes = [
        {value:'article',title:'单图文'},
        {value:'news',title:'多图文'},
        {value:'channel',title:'频道'}
    ];
    $scope.embedInput = function(page) {
        $modal.open({
            templateUrl: 'embedInputLib.html',
            controller: CusdataCtrl,
            backdrop:'static',
        }).result.then(function(def){
            var cus='',key,inpAttrs;
            key = 'c'+(new Date()).getTime();
            inpAttrs = {wrap:'input',class:'form-group'};
            if (def.showname == 1)
                addWrap(page, 'div',{wrap:'text',class:'form-group'},def.name);
            switch (def.type){
                case '0':
                    addWrap(page, 'div', inpAttrs, '<input type="text" ng-model="data.name" title="姓名" placeholder="姓名" class="form-control input-lg">');
                    break;
                case '1':
                    addWrap(page, 'div', inpAttrs,'<input type="text" ng-model="data.mobile" title="手机" placeholder="手机" class="form-control input-lg">');
                    break;
                case '2':
                    addWrap(page, 'div', inpAttrs,'<input type="text" ng-model="data.email" title="邮箱" placeholder="邮箱" class="form-control input-lg">');
                    break;
                case '3':
                    addWrap(page, 'div', inpAttrs,'<input type="text" ng-model="data.'+key+'" title="'+def.name+'" placeholder="'+def.name+'" class="form-control input-lg">');
                    break;
                case '4':
                    addWrap(page, 'div', inpAttrs,'<textarea ng-model="data.'+key+'" title="'+def.name+'" placeholder="'+def.name+'" class="form-control input-lg" rows="3">'+def.name+'</textarea>');
                    break;
                case '5':
                    if (def.ops && def.ops.length > 0) {
                        var html='',cls='radio';
                        if (def.align==='H') cls += '-inline'
                        for (var i in def.ops) {
                            html += '<li class="'+cls+'" wrap="radio"><label';
                            if (def.align==='H') html += ' class="radio-inline"';
                            html += '><input type="radio" name="'+key+'" value="'+i+'" ng-model="data.'+key+'" title="'+def.name+'" data-label="'+def.ops[i].text+'"><span>'+def.ops[i].text+'</span></label></li>';
                        }
                        addWrap(page, 'ul', {class:'form-group'}, html);
                    }
                    break;
                case '6':
                    if (def.ops && def.ops.length > 0) {
                        var html = '',cls='checkbox';
                        if (def.align==='H') cls += '-inline'
                        for (var i in def.ops) {
                            html += '<li class="'+cls+'" wrap="checkbox"><label';
                            if (def.align==='H') html += ' class="checkbox-inline"';
                            html += '><input type="checkbox" name="'+key+'" ng-model="data.'+key+'.'+i+'" title="'+def.name+'" data-label="'+def.ops[i].text+'"><span>'+def.ops[i].text+'</span></label></li>';
                        }
                        addWrap(page, 'ul',{class:'form-group'}, html);
                    }
                    break;
                case '7':
                    var html = '';
                    html += '<li wrap="img" ng-repeat="img in data.'+key+'" class="img-thumbnail" title="'+def.name+'">';
                    html += '<img flex-img>';
                    html += '<button class="btn btn-default btn-xs" ng-click="removeImage(data.'+key+',$index)"><span class="glyphicon glyphicon-remove"></span></button>';
                    html += '</li>';
                    html += '<li class="img-picker">';
                    html += '<button class="btn btn-default" ng-click="chooseImage(\''+key+'\')"><span class="glyphicon glyphicon-picture"></span><br>上传图片</button>';
                    html += '</li>';
                    addWrap(page, 'ul',{class:'form-group img-tiles clearfix', name:key}, html);
                    break;
            }
        });
    };
    $scope.embedButton = function(page) {
        $modal.open({
            templateUrl: 'embedButtonLib.html',
            controller: ['$scope','$modalInstance','pages',function($scope,$mi,pages){
                $scope.buttons = [
                    ['submit','提交信息'],
                    ['addRecord','新增登记'],
                    ['editRecord','修改登记'],
                    ['likeRecord','点赞'],
                    ['remarkRecord','评论'],
                    ['gotoPage','页面导航'],
                    ['closeWindow','关闭页面']
                ];
                $scope.pages = pages;
                $scope.def = {type:'0',label:'',next:''};
                $scope.ok = function() {$mi.close($scope.def);};
                $scope.cancel = function() {$mi.dismiss();};
            }],
            backdrop:'static',
            resolve:{
                pages: function() {return $scope.activity.pages;}
            }
        }).result.then(function(def){
            var attrs = {wrap:'button',class:'form-group'}
            ,tmplBtn = function(id, action, label) {
                return '<button id="'+id+'" class="btn btn-primary btn-block btn-lg" ng-click="'+action+'"><span>'+label+'</span></button>';
            }
            ,args = def.next ? "($event,'"+def.next+"')" : "($event)"
            ,button = def.type[0];
            switch (button){
                case 'submit':
                    addWrap(page, 'div', attrs, tmplBtn('btnSubmit', "submit"+args,  def.label));
                    break;
                case 'addRecord':
                    addWrap(page, 'div', attrs, tmplBtn('btnNewRecord', "addRecord"+args, def.label));
                    break;
                case 'editRecord':
                    addWrap(page, 'div', attrs, tmplBtn('btnNewRecord', "gotoPage($event,'form')", def.label));
                    break;
                case 'likeRecord':
                    addWrap(page, 'div', attrs, tmplBtn('btnLikeRecord', "Record.like($event)", def.label));
                    break;
                case 'remarkRecord':
                    var html = '<input type="text" class="form-control" placeholder="评论" ng-model="newRemark">';
                    html += '<span class="input-group-btn">';
                    html += '<button class="btn btn-success" type="button" ng-click="Record.remark($event,newRemark)">发送</button>';
                    html += '</span>';
                    addWrap(page, 'div', {class:'form-group input-group input-group-lg'}, html);
                    break;
                case 'gotoPage':
                    addWrap(page, 'div', attrs, tmplBtn('btnGotoPage_'+def.next, "gotoPage"+args, def.label));
                    break;
                case 'closeWindow':
                    addWrap(page, 'div', attrs, tmplBtn('btnCloseWindow', 'closeWindow($event)', def.label));
                    break;
            }
        });
    };
    $scope.embedShow = function(page) {
        $modal.open({
            templateUrl:'embedShowLib.html',
            backdrop:'static',
            resolve:{
                pages: function() {return $scope.activity.pages;},
                schema: function() {
                    var i, page, s, s2;
                    s = extractSchema($scope.activity.pages.form.html);
                    for (i in $scope.activity.pages) {
                        page = $scope.activity.pages[i];
                        if (page.type && page.type === 'I') {
                            s2 = extractSchema(page.html);
                            s = angular.extend(s, s2);
                        }
                    }
                    return s;
                }
            },
            controller:['$scope','$modalInstance','pages','schema',function($scope,$mi,pages,schema){
                $scope.pages = pages;
                $scope.def = {type:'record',dataScope:'U',canLike:'N',onclick:'',addEnrollAt:0,addNickname:0};
                $scope.def.schema = schema;
                $scope.ok = function() {$mi.close($scope.def);};
                $scope.cancel = function() {$mi.dismiss();};
            }]
        })
        .result.then(function(def){
            switch (def.type){
                case 'record':
                    embedRecord(page, def);
                    break;
                case 'list':
                    embedList(page, def);
                    break;
                case 'rounds':
                    embedRounds(page, def);
                    break;
                case 'remarks':
                    embedRemarks(page, def);
                    break;
            }
        });
    };
    $scope.$on('tinymce.innerlink_dlg.open', function(event, callback){
        $scope.$broadcast('mattersgallery.open', callback);
    });
    $scope.$on('tinymce.multipleimage.open', function(event, callback){
        $scope.$broadcast('picgallery.open', callback, true, true);
    });
    $scope.extraPages = function() {
        var result = {};
        angular.forEach($scope.activity.pages, function(value, key) {
            if (key !== 'form' && key !== 'result')
                result[key] = value;
        });
        return result;
    };
    $scope.addPage = function() {
        http2.get('/rest/mp/activity/enroll/addPage?aid='+$scope.aid, function(rsp){
            var page = rsp.data;
            $scope.activity.pages[page.name] = page;
            $timeout(function(){
                $('a[href="#tab_'+page.name+'"]').tab('show');
            });
        });
    };
    $scope.updPage = function(page, name){
        if (!angular.equals($scope.activity, $scope.persisted)) {
            var url,p = {};
            p[name] = page[name];
            url = '/rest/mp/activity/enroll/updPage';
            url += '?aid='+$scope.aid;
            url += '&pid='+page.id;
            url += '&pname='+page.name;
            url += '&cid='+page.code_id;
            http2.post(url, p, function(rsp){
                $scope.persisted = angular.copy($scope.activity);
            });
        }
    };
    $scope.delPage = function(page) {
        var url = '/rest/mp/activity/enroll/delPage';
        url += '?aid='+$scope.aid;
        url += '&pid='+page.id;
        http2.get(url, function(rsp){
            tinymce.remove('#'+page.name);
            delete $scope.activity.pages[page.name];
            $timeout(function(){
                $('a[href="#tab_form"]').tab('show');
            });
        });
    };
    $scope.gotoCode = function(codeid) {
        window.open('/rest/code?pid='+codeid);
    };
}])
.controller('rollCtrl',['$scope','http2','$modal',function($scope,http2,$modal) {
    var t = (new Date()).getTime();
    $scope.doSearch = function(page) {
        page && ($scope.page.at = page); 
        var url = '/rest/mp/activity/enroll/records?aid='+$scope.aid+'&contain=total'+$scope.page.joinParams();
        http2.get(url, function(rsp){
            if (rsp.data) {
                $scope.roll = rsp.data[0] ? rsp.data[0] : [];
                rsp.data[1] && ($scope.page.total = rsp.data[1]);
                rsp.data[2] && ($scope.cols = rsp.data[2]);
            } else
                $scope.roll = [];
        });
    };
    $scope.page = {
        at:1,
        size:30,
        keyword:'',
        searchBy:'nickname',
        joinParams: function() {
            var p;
            p = '&page='+this.at+'&size='+this.size;
            if (this.keyword !== '') {
                p += '&kw=' + this.keyword;
                p += '&by=' + this.searchBy;
            }
            p += '&rid=' + (this.byRound ? this.byRound : 'ALL');
            return p;
        }
    };
    $scope.searchBys = [
        {n:'昵称',v:'nickname'},
        {n:'手机号',v:'mobile'},
    ];
    $scope.viewUser = function(fan){
        location.href = '/rest/mp/user?fid='+fan.fid;
        // todo 如果是认证用户???
    };
    $scope.keywordKeyup = function(evt) {
        if (evt.which === 13)
            $scope.doSearch();
    };
    $scope.editRoll = function(rollItem) {
        var ins = $modal.open({
            templateUrl: 'editor.html',
            controller: 'editorCtrl',
            resolve: {
                rollItem: function() {
                    rollItem.aid = $scope.aid;
                    return rollItem;
                },
                tags: function() {
                    return $scope.activity.tags;
                },
                cols: function() {
                    return $scope.cols;
                }
            }
        });
        ins.result.then(function(updated) {
            var p = updated[0], tags =  updated[1].join(',');
            if ($scope.activity.tags.length !== tags.length) {
                $scope.activity.tags = tags;
                $scope.update('tags');
            }
            http2.post('/rest/mp/activity/enroll/updateRoll?aid='+$scope.aid+'&ek='+rollItem.enroll_key, p);
        });
    };
    $scope.addRoll = function() {
        var ins = $modal.open({
            templateUrl: 'editor.html',
            controller: 'editorCtrl',
            resolve: {
                rollItem: function() {
                    return {aid:$scope.aid,tags:''};
                },
                tags: function() {
                    return $scope.activity.tags;
                },
                cols: function() {
                    return $scope.cols;
                }
            }
        });
        ins.result.then(function(updated) {
            var p = updated[0], tags =  updated[1].join(',');
            if ($scope.activity.tags.length !== tags.length) {
                $scope.activity.tags = tags;
                $scope.update('tags');
            }
            http2.post('/rest/mp/activity/enroll/addRoll?aid='+$scope.aid, p, function(rsp){
                $scope.roll.splice(0, 0, rsp.data);
            });
        });
    };
    $scope.importRoll = function() {
        http2.get('/rest/member/auth/userselector', function(rsp){
            var url = rsp.data;
            $.getScript(url, function(){
                $modal.open(AddonParams).result.then(function(selected) {
                    if (selected.members && selected.members.length) {
                        var members=[];
                        for (var i in selected.members)
                            members.push(selected.members[i].data.mid);
                        http2.post('/rest/mp/activity/importRoll?aid='+$scope.aid, members, function(rsp){
                            for (var i in rsp.data)
                                $scope.roll.splice(0, 0, rsp.data[i]);
                        });
                    }
                })
            });
        });
    };
    $scope.importRoll2 = function() {
        $modal.open({
            templateUrl: 'importActivityRoll.html',
            controller: 'importActivityRollCtrl',
            backdrop:'static',
            size:'lg'
        }).result.then(function(param) {
            http2.post('/rest/mp/activity/enroll/importRoll2?aid='+$scope.aid, param, function(rsp){
                $scope.doSearch(1);
            });
        });
    };
    $scope.removeRoll = function(roll) {
        var vcode;
        vcode = prompt('是否要删除登记信息？，若是，请输入活动名称。');
        if (vcode === $scope.activity.title) {
            http2.get('/rest/mp/activity/enroll/removeRoll?aid='+$scope.aid+'&key='+roll.enroll_key, function(rsp){
                var i = $scope.roll.indexOf(roll); 
                $scope.roll.splice(i,1);
                $scope.page.total = $scope.page.total - 1;
            });
        }
    };
    $scope.cleanAll = function() {
        var vcode;
        vcode = prompt('是否要删除所有登记信息？，若是，请输入活动名称。');
        if (vcode === $scope.activity.title) {
            http2.get('/rest/mp/activity/enroll/clean?aid='+$scope.aid, function(rsp){
                $scope.doSearch(1);
            });
        }
    };
    $scope.doSearch();
}])
.controller('importActivityRollCtrl',['$scope','http2','$modalInstance',function($scope,http2,$modalInstance) {
    $scope.param = {
        checkedActs:[],
        checkedWalls:[],
        wallUserState:'active',
        alg:'inter'
    };
    $scope.changeAct = function(act){
        var i = $scope.param.checkedActs.indexOf(act.aid);
        if (i===-1)
            $scope.param.checkedActs.push(act.aid);
        else
            $scope.param.checkedActs.splice(i, 1);
    };
    $scope.changeWall = function(wall){
        var i = $scope.param.checkedWalls.indexOf(wall.wid);
        if (i===-1)
            $scope.param.checkedWalls.push(wall.wid);
        else
            $scope.param.checkedWalls.splice(i, 1);
    };
    $scope.cancel = function() {
        $modalInstance.dismiss();
    };
    $scope.ok = function() {
        $modalInstance.close($scope.param);
    };
    http2.get('/rest/mp/activity/enroll?page=1&size=999', function(rsp){
        $scope.activities = rsp.data[0];
    });
    http2.get('/rest/mp/activity/wall', function(rsp){
        $scope.walls = rsp.data;
    });
}])
.controller('editorCtrl',['$scope','$modalInstance','rollItem','tags','cols', function($scope, $modalInstance, rollItem, tags, cols) {
    $scope.item = rollItem;
    $scope.item.aTags = (!rollItem.tags||rollItem.tags.length=== 0)?[]:rollItem.tags.split(',');
    $scope.aTags = (!tags||tags.length === 0)?[]:tags.split(',');
    $scope.cols = cols;
    $scope.signin = function() {
        $scope.item.signin_at = Math.round((new Date()).getTime()/1000);
    };
    $scope.ok = function () {
        var p, col;
        p = {tags:$scope.item.aTags.join(','),data:{}};
        $scope.item.tags = p.tags;
        if ($scope.item.id)
            p.signin_at = $scope.item.signin_at;
        for (var c in $scope.cols) {
            col = $scope.cols[c];
            p.data[col.id] = $scope.item.data[col.id];
        }
        $modalInstance.close([p, $scope.aTags]);
    };
    $scope.cancel = function () {
        $modalInstance.dismiss('cancel');
    };
    $scope.$on('tag.xxt.combox.done', function(event, aSelected){
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.item.aTags) {
                if (aSelected[i] === $scope.item.aTags[j]) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewTags.push(aSelected[i]);
        }
        $scope.item.aTags = $scope.item.aTags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function(event, newTag){
        $scope.item.aTags.push(newTag);
        if ($scope.aTags.indexOf(newTag) === -1) {
            $scope.aTags.push(newTag);
        }
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed){
        $scope.item.aTags.splice($scope.item.aTags.indexOf(removed), 1);
    });
}])
.controller('StatCtrl',['$scope','http2',function($scope,http2) {
    http2.get('/rest/mp/activity/enroll/stat?aid='+$scope.aid, function(rsp){
        $scope.stat = rsp.data;
    });
}])
.controller('lotteryCtrl',['$scope','http2',function($scope,http2) {
    var getWinners = function() {
        var url = '/rest/mp/activity/enroll/lotteryWinners?aid='+$scope.aid;
        if ($scope.editing)
            url += '&rid='+$scope.editing.round_id;
        http2.get(url, function(rsp){
            $scope.winners = rsp.data;
        });
    };
    $scope.aTargets = null;
    $scope.addRound = function() {
        http2.post('/rest/mp/activity/enroll/addLotteryRound?aid='+$scope.aid, null, function(rsp){
            $scope.rounds.push(rsp.data);
        });
    };
    $scope.open = function(round) {
        $scope.editing = round;
        $scope.aTargets = $scope.editing.targets.length === 0 ? [] : eval($scope.editing.targets);
        getWinners();
    };
    $scope.updateLotteryRound = function(name) {
        var nv = {};
        nv[name] = $scope.editing[name];
        http2.post('/rest/mp/activity/enroll/updateLotteryRound?aid='+$scope.aid+'&rid='+$scope.editing.round_id, nv,function(rsp){
        });
    };
    $scope.removeLotteryRound = function() {
        http2.post('/rest/mp/activity/enroll/removeLotteryRound?aid='+$scope.aid+'&rid='+$scope.editing.round_id, null, function(rsp){
            var i = $scope.rounds.indexOf($scope.editing);
            $scope.rounds.splice(i, 1);
        });
    };
    $scope.addTarget = function() {
        var target = {tags:[]};
        $scope.aTargets.push(target);
    };
    $scope.removeTarget = function(i) {
        $scope.aTargets.splice(i, 1);
    };
    $scope.saveTargets = function() {
        var arr = [];
        for (var i in $scope.aTargets) 
            arr.push({tags:$scope.aTargets[i].tags});
        $scope.editing.targets = JSON.stringify(arr);
        $scope.updateLotteryRound('targets');
    };
    $scope.$on('tag.xxt.combox.done', function(event, aSelected, state){
        var aNewTags = [];
        for (var i in aSelected) {
            var existing = false;
            for (var j in $scope.aTargets[state].tags) {
                if (aSelected[i] === $scope.aTargets[state].tags[j]) {
                    existing = true;
                    break;
                }
            }
            !existing && aNewTags.push(aSelected[i]);
        }
        $scope.aTargets[state].tags = $scope.aTargets[state].tags.concat(aNewTags);
    });
    $scope.$on('tag.xxt.combox.add', function(event, newTag, state){
        $scope.aTargets[state].tags.push(newTag);
        if ($scope.aTags.indexOf(newTag) === -1) {
            $scope.aTags.push(newTag);
            $scope.activity.tags = $scope.aTags.join(',');
            $scope.update('tags'); 
        }
    });
    $scope.$on('tag.xxt.combox.del', function(event, removed, state){
        $scope.aTargets[state].tags.splice($scope.aTargets[state].tags.indexOf(removed), 1);
    });
    $scope.aTags = $scope.activity.tags.length === 0 ? [] : $scope.activity.tags.split(',');
    $scope.lotteryUrl = "http://"+location.host+"/rest/activity/enroll/lottery2?aid="+$scope.aid;
    http2.get('/rest/mp/activity/enroll/lotteryRounds?aid='+$scope.aid, function(rsp) {
        $scope.rounds = rsp.data;
    });
    getWinners();
}]);
