'use strict';

var ngMod = angular.module('picviewer.ui.xxt', []);
ngMod.factory('picviewer', ['$q', function($q) {
    /*私有方法*/
    var _method = {
        isArray: function(value) {
            return Object.prototype.toString.call(value) == '[object Array]';
        },
        all: function(selector, contextElement) {
            var nodeList,
                list = [];
            if (contextElement) {
                nodeList = contextElement.querySelectorAll(selector);
            } else {
                nodeList = document.querySelectorAll(selector);
            }
            if (nodeList && nodeList.length > 0) {
                list = Array.prototype.slice.call(nodeList);
            }
            return list;
        },
        delegate: function(ele, eventType, selector, fn) {
            var _this = this;
            if (!ele) { return; }
            ele.addEventListener(eventType, function(e) {
                var targets = _this.all(selector, ele);
                if (!targets) {
                    return;
                }
                for (var i = 0; i < targets.length; i++) {
                    var node = e.target;
                    while (node) {
                        if (node == targets[i]) {
                            fn.call(node, e);
                            break;
                        }
                        node = node.parentNode;
                        if (node == ele) {
                            break;
                        }
                    }
                }
            }, false);
        }
    }

    /*初始化*/
    var _picviewer = function() {
        this.winw = window.innerWidth || document.body.clientWidth; 
        this.winh = (window.innerHeight+1) || document.body.clientHeight;
        this.originWinw = this.winw;
        this.originWinh = this.winh;
        this.marginRight = 15;
        this.imageChageMoveX = this.marginRight + this.winw;
        this.imageChageNeedX = Math.floor(this.winw * (0.5));
        this.cssprefix = ["", "webkit", "Moz", "ms", "o"];
        this.imgLoadCache = new Object();
        this.scale = 1;
        this.maxScale = 4;
        this.maxOverScale = 6;
        this.openTime = 0.3;
        this.slipTime = 0.5;
        this.maxOverWidthPercent = 0.5;
        this.box = false;
        this.isPreview = false;
        this.container = document.createElement('div');
        this.container.setAttribute('id', 'previewImage-container');
        this.container.style.width = this.winw + 'px';
        this.container.style.height = this.winh + 'px'; 
        document.body.appendChild(this.container); 
        this.bind();  
    };

    /*绑定事件*/
    _picviewer.prototype.bind = function() {
        var _this = this;
        var container = this.container;

        var closePreview = function() {
            _this.setCloseStatus.call(_this);
        }
        var touchStartFun = function() {
            _this.touchStartFun.call(_this);
        }
        var touchMoveFun = function() {
            _this.touchMoveFun.call(_this);
        }
        var touchEndFun = function() {
            _this.touchEndFun.call(_this);
        }
        var reSizeFun = function() {
            var _this = this;
            _this.winw = window.innerWidth || document.body.clientWidth; 
            _this.winh = window.innerHeight || document.body.clientHeight;
            _this.originWinw = _this.winw; 
            _this.originWinh = _this.winh; 
            _this.container.style.width = _this.winw + 'px';
            _this.container.style.height = _this.winh + 'px'; 
            _this.imageChageMoveX = _this.marginRight + _this.winw;
            var offsetX = -_this.imageChageMoveX * _this.index; 
            try {
                _this.boxData.x = offsetX;
                _this.translateScale(_this.bIndex, 0);
            } catch (e) {}
        }.bind(this);
        var keyDownFun = function(){
            var _this = this;
            if (event.keyCode == 37) {
                this.prev &&  this.prev();
            } else if(event.keyCode == 39) {
                this.next && this.next();
            }
        }.bind(this);

        window.addEventListener("resize", reSizeFun, false);
        document.addEventListener("keydown", keyDownFun, false);
        _method.delegate(container, 'click', '.previewImage-item', closePreview);
        _method.delegate(container, 'touchstart', '.previewImage-item', touchStartFun);
        _method.delegate(container, 'touchmove', '.previewImage-item', touchMoveFun);
        _method.delegate(container, 'touchend', '.previewImage-item', touchEndFun);
        _method.delegate(container, 'touchcancel', '.previewImage-item', touchEndFun);
    };
    _picviewer.prototype.setCloseStatus = function() {
        if(this.winw > 992) {
            if(this.urls.length == 1 || this.index == this.maxLen) {
                this.closePreview();
            }else {
                this.next && this.next();
            }
        }else{
            this.closePreview();
        }
    };
    _picviewer.prototype.closePreview = function(){
        var _this = this;
        this.imgStatusCache[this.cIndex].x = this.winw;
        this.translateScale(this.cIndex,this.openTime);
        this.imgStatusRewrite();
        this.translateScale(this.index,this.slipTime);
        setTimeout(function(){
            _this.container.style.display = "none";
            document.body.style.overflow = 'auto';
        },this.slipTime*1000);
        _this.isPreview = false;
    };
    _picviewer.prototype.touchStartFun = function(imgitem){
        this.ts = this.getTouches();
        this.allowMove = true; 
        this.statusX = 0; 
        this.statusY = 0; 
    };
    _picviewer.prototype.touchMoveFun = function(imgitem){
        this.tm = this.getTouches();
        var tm = this.tm;
        var ts = this.ts;
        this.moveAction(ts,tm);
    };
    _picviewer.prototype.touchEndFun = function(imgitem){
        var container = this.container;
        this.te = this.getTouches();
        this.endAction(this.ts,this.te);
    };
    
    /*被调用的方法*/
    _picviewer.prototype.moveAction = function(ts,tm){
        if(!this.allowMove){ return false; }
        var imgStatus, maxWidth, x0_offset, y0_offset, imgPositionX, imgPositionY, allow, allowX, allowY;
        imgStatus = this.getIndexImage();
        maxWidth = this.winw*0.3/imgStatus.scale;
        x0_offset = tm.x0 - ts.x0;
        y0_offset = tm.y0 - ts.y0;
        if(Math.abs(y0_offset)>0){  
            event.preventDefault();
        }
        imgPositionX = imgStatus.x+x0_offset;
        imgPositionY = imgStatus.y+y0_offset;
        allow = this.getAllow(this.index);
        allowX = this.allowX = allow.x;
        allowY = this.allowY = allow.y0;
        if(x0_offset<=0){ 
            this.allowX = -allowX;
        }
        if(y0_offset<=0){   
            allowY = this.allowY = allow.y1;
        }
        if(tm.length==1){   
            if(imgStatus.scale>1){
                if(imgPositionY>=allow.y0){  
                    this.statusY = 1;
                    var overY = imgPositionY - allow.y0;
                    imgStatus.my = allow.y0-imgStatus.y+this.getSlowlyNum(overY,maxWidth);
                }else if(imgPositionY<=allow.y1){ 
                    this.statusY = 1;
                    var overY = imgPositionY - allow.y1;
                    imgStatus.my = allow.y1-imgStatus.y+this.getSlowlyNum(overY,maxWidth);
                }else{
                    this.statusY = 2;
                    imgStatus.my = y0_offset;
                }

            
                if(x0_offset<0&&imgStatus.x<=-allowX){ 
                    this.statusX = 1;
                    this.boxData.m = x0_offset; 
                    if(this.index==this.maxLen){ 
                        this.boxData.m = this.getSlowlyNum(x0_offset);  
                    }
                    this.translateScale(this.bIndex,0);
                    this.translateScale(this.index,0);
                }else if(x0_offset>0&&imgStatus.x>=allowX){   
                    this.statusX = 2;
                    this.boxData.m = x0_offset;
                    if(this.index==0){ 
                        this.boxData.m = this.getSlowlyNum(x0_offset); 
                    }
                    this.translateScale(this.bIndex,0);
                    this.translateScale(this.index,0);
                }else{  
                    if(x0_offset==0){
                        return
                    }
                    this.statusX = 3;
                    imgStatus.m = x0_offset;
                    if(imgPositionX>=allowX){   
                        this.statusX = 4;
                        var overX = imgPositionX - allowX;
                        imgStatus.m = allowX-imgStatus.x+this.getSlowlyNum(overX,maxWidth);
                    }
                    if(imgPositionX<=-allowX){  
                        this.statusX = 4;
                        var overX = imgPositionX + allowX;
                        imgStatus.m = -allowX-imgStatus.x+this.getSlowlyNum(overX,maxWidth);
                    }
                    this.translateScale(this.index,0);
                }
            }else{ 
                if(Math.abs(y0_offset)>5&&this.statusX != 5){  
                    var $img = this.getJqElem(this.index);
                    var imgBottom = $img.height-this.winh;
                    if(y0_offset>0&&imgPositionY>0){
                        this.statusX = 7;
                        this.allowY = 0;
                        imgStatus.my = - imgStatus.y + this.getSlowlyNum(imgPositionY,maxWidth);
                    }else if(y0_offset<0&&imgPositionY<-imgBottom){
                        this.statusX = 7;
                        if($img.height>this.winh){
                            var overY = imgPositionY + imgBottom;
                            this.allowY = -imgBottom;
                            imgStatus.my = -imgBottom - imgStatus.y + this.getSlowlyNum(overY,maxWidth);
                        }else{
                            this.allowY = 0;
                            imgStatus.my = - imgStatus.y + this.getSlowlyNum(imgPositionY,maxWidth);
                        }
                    }else{

                        this.statusX = 6;
                        imgStatus.my = y0_offset;
                    }
                    this.translateScale(this.index,0);
                }else{
                    if(this.statusX == 6){
                        return
                    }
                    this.statusX = 5;
                    if((this.index==0&&x0_offset>0)||(this.index==this.maxLen&&x0_offset<0)){
                        this.boxData.m = this.getSlowlyNum(x0_offset);
                    }else{
                        this.boxData.m = x0_offset;
                    }
                    this.translateScale(this.bIndex,0);
                }
            }
        }else{  
            var scalem = this.getScale(ts,tm)
            var scale = scalem*imgStatus.scale;
            if(scale>=this.maxScale){  
                var over = scale - this.maxScale;
                scale = this.maxScale+this.getSlowlyNum(over,this.maxOverScale);
                scalem = scale/imgStatus.scale;
            }
            imgStatus.scalem = scalem;
            this.translateScale(this.index,0);
        }
    };
    _picviewer.prototype.endAction = function(ts,te){
        var imgStatus, x0_offset, y0_offset, time, slipTime;
        imgStatus = this.getIndexImage();
        x0_offset = te.x0 - ts.x0;
        y0_offset = te.y0 - ts.y0;
        time = te.time - ts.time;
        slipTime = 0;
        this.allowMove = false; 
        if(ts.length==1){     
            if(Math.abs(x0_offset)>10){
                event.preventDefault();
            }
            switch(this.statusY){
                case 1:
                    imgStatus.y = this.allowY;
                    imgStatus.my = 0;
                    slipTime = this.slipTime;
                break
                case 2:
                    imgStatus.y = imgStatus.y+imgStatus.my;
                    imgStatus.my = 0;
                break
            }

            switch(this.statusX){
                case 1: 
                    if(this.index!=this.maxLen&&(x0_offset<=-this.imageChageNeedX||(time<200&&x0_offset<-30))){   
                        this.changeIndex(1);
                    }else{
                        this.changeIndex(0);
                        if(slipTime!=0){
                            this.translateScale(this.index,slipTime);
                        }
                    }
                break
                case 2: 
                    if(this.index!=0&&(x0_offset>=this.imageChageNeedX||(time<200&&x0_offset>30))){ 
                        this.changeIndex(-1);
                    }else{
                        this.changeIndex(0);
                        if(slipTime!=0){
                            this.translateScale(this.index,slipTime);
                        }
                    }
                break
                case 3: 
                    imgStatus.x = imgStatus.x+imgStatus.m;
                    imgStatus.m = 0;
                    this.translateScale(this.index,slipTime);
                break
                case 4:
                    imgStatus.x = this.allowX;
                    imgStatus.m = 0;
                    slipTime = this.slipTime;
                    this.translateScale(this.index,slipTime);
                break
                case 5: 
                    if(x0_offset>=this.imageChageNeedX||(time<200&&x0_offset>30)){    
                        this.changeIndex(-1);
                    }else if(x0_offset<=-this.imageChageNeedX||(time<200&&x0_offset<-30)){ 
                        this.changeIndex(1);
                    }else{
                        this.changeIndex(0);
                    }
                break
                case 6:
                    imgStatus.y = imgStatus.y+imgStatus.my;
                    imgStatus.my = 0;
                break
                case 7: 
                    imgStatus.y = this.allowY;
                    imgStatus.my = 0;
                    this.translateScale(this.index,this.slipTime);
                break
            }
        }else{  
            event.preventDefault();

            var scale = imgStatus.scale*imgStatus.scalem;
            var $img = this.getJqElem(this.index);
            imgStatus.scale = scale;
            var allow = this.getAllow(this.index);

            if(imgStatus.x>allow.x){
                slipTime = this.slipTime;
                imgStatus.x = allow.x;
            }else if(imgStatus.x<-allow.x){
                slipTime = this.slipTime;
                imgStatus.x = -allow.x;
            }

            if(imgStatus.y>allow.y0){
                slipTime = this.slipTime;
                imgStatus.y = allow.y0;
            }else if(imgStatus.y<allow.y1){
                slipTime = this.slipTime;
                imgStatus.y = allow.y1;
            }

            if($img.height*imgStatus.scale<=this.winh){
                imgStatus.y = 0;
            }

            if($img.width*imgStatus.scale<=this.winw){
                imgStatus.x = 0;
            }

            imgStatus.scalem = 1;
            if(scale>this.maxScale){     
                imgStatus.scale = this.maxScale;
                slipTime = this.slipTime;
            }else if(scale<1){
                this.imgStatusRewrite();
                slipTime = this.slipTime;
            }
            if(slipTime!=0){
                this.changeIndex(0);
                this.translateScale(this.index,slipTime);
            }
        }
    };
    _picviewer.prototype.changeIndex = function(x){
        var imgStatus, oldIndex, _this, hash, imgCache;
        imgStatus = this.getIndexImage();
        oldIndex = this.index;
        _this = this;

        if(this.index==0&&x==-1){
            this.index = this.index;
        }else if(this.index==this.maxLen&&x==1){
            this.index = this.index;
        }else{
            this.index+=x;
            this.ePage.innerHTML = (this.index + 1) + '/' + (this.maxLen + 1);
            hash = this.imgStatusCache[this.index].hash;
            imgCache = this.imgLoadCache[hash];
            if(!imgCache.isload){    
                imgCache.elem.src = this.urls[this.index];
                imgCache.elem.onload = function(){
                    imgCache.isload = true;
                }
            }
        }
        this.setActionStatus();
        this.boxData.x = -this.imageChageMoveX*this.index;
        this.boxData.m = 0;
        if(oldIndex!=this.index){
            this.imgStatusRewrite(oldIndex);
        }
        this.translateScale(this.bIndex,this.slipTime);
    };
    _picviewer.prototype.setActionStatus = function(){
        if (this.index==0) {
            this.ePrev.classList.add('hide');
            this.eNext.classList.remove('hide');
        } else if (this.index==this.maxLen) {
            this.ePrev.classList.remove('hide');
            this.eNext.classList.add('hide');
        } else {
            this.ePrev.classList.remove('hide');
            this.eNext.classList.remove('hide');
        }
    };
    _picviewer.prototype.getTouches = function(e){
        var touches = event.touches.length>0?event.touches:event.changedTouches;
        var obj = {touches:touches,length:touches.length};
            obj.x0 = touches[0].pageX
            obj.y0 = touches[0].pageY;
            obj.time = new Date().getTime();
        if(touches.length>=2){
            obj.x1 = touches[0].pageX
            obj.y1 = touches[1].pageY
        }
        return obj;
    };
    _picviewer.prototype.getIndexImage = function(index){
        var index = index==undefined?this.index:index;
        return  this.imgStatusCache[this.index];
    };
    _picviewer.prototype.getAllow = function(index){
        var $img, imgStatus, allowX, allowY0, allowY1;
        $img = this.getJqElem(index);
        imgStatus = this.getIndexImage(index);
        allowX = Math.floor(($img.width*imgStatus.scale-this.winw)/(2*imgStatus.scale));
        if($img.height*imgStatus.scale<=this.winh){
            allowY0 = 0;
            allowY1 = 0;
        }else if($img.height<=this.winh){
            allowY0 = Math.floor(($img.height*imgStatus.scale-this.winh)/(2*imgStatus.scale));
            allowY1 = -allowY0;
        }else{
            allowY0 = Math.floor($img.height*(imgStatus.scale-1)/(2*imgStatus.scale));
            allowY1 = -Math.floor(($img.height*(imgStatus.scale+1)-2*this.winh)/(2*imgStatus.scale));
        }
        return {
            x:allowX,
            y0:allowY0,
            y1:allowY1,
        };
    };
    _picviewer.prototype.getSlowlyNum = function(x,maxOver){
        var maxOver = maxOver||this.winw*this.maxOverWidthPercent;
        if(x<0){
            x = -x;
            return -(1-(x/(maxOver+x)))*x;
        }else{
            return (1-(x/(maxOver+x)))*x;
        }
    };
    _picviewer.prototype.getScale = function(ts,tm){
        var fingerRangeS, fingerRangeM, range;
        fingerRangeS = Math.sqrt(Math.pow((ts.x1 - ts.x0),2)+Math.pow((ts.y1-ts.y0),2)); 
        fingerRangeM = Math.sqrt(Math.pow((tm.x1 - tm.x0),2)+Math.pow((tm.y1-tm.y0),2));
        range = fingerRangeM/fingerRangeS;
        return range;
    };
    _picviewer.prototype.imgStatusRewrite = function(idx){
        var index=idx===undefined?this.index:idx;
        var imgStatus=this.imgStatusCache[index], currentScale=imgStatus.scale,
            currentX=imgStatus.x, currentY=imgStatus.y;
        imgStatus.x = 0;
        imgStatus.y = 0;
        imgStatus.m = 0;
        imgStatus.my = 0;
        imgStatus.scale = 1;
        imgStatus.scalem = 1;
        if(index!=this.index){
            if(this.winw > 992) {
                imgStatus.scale = currentScale;
                imgStatus.x = currentX;
                imgStatus.y = currentY;
            }
            this.translateScale(index,this.slipTime);
        }
    };
    _picviewer.prototype.translateScale = function (index,duration){
        var imgStatus, $elem, scale, offsetX, offsetY, tran_origin, tran_3d, transition;
        imgStatus = this.imgStatusCache[index];
        $elem = this.getJqElem(index);
        scale = imgStatus.scale*imgStatus.scalem;
        offsetX = imgStatus.x+imgStatus.m;
        offsetY = imgStatus.y+imgStatus.my;
        tran_origin = '0px 0px 0px';
        tran_3d='scale3d('+scale+','+scale+','+'1)' + ' translate3d(' + offsetX + 'px,' + offsetY + 'px,0px)';
        transition = 'transform '+duration+'s ease-out';
        if(this.winw > 992) {
            this.addCssPrefix($elem,'transform-origin',tran_origin);
            tran_3d='translate3d(' + offsetX + 'px,' + offsetY + 'px,0px)' + ' scale3d('+scale+','+scale+','+'1)';
        }
        this.addCssPrefix($elem,'transition',transition);
        this.addCssPrefix($elem,'transform',tran_3d);
    };
    _picviewer.prototype.getJqElem = function(index){
        var $elem, index, hash;
        index = index == undefined?this.index:index;
        if(index<=this.maxLen){
            hash = this.imgStatusCache[index].hash;
            $elem = this.imgLoadCache[hash].elem;
        }else{
            $elem = this.imgStatusCache[index].elem;
        }
        return $elem;
    };
    _picviewer.prototype.addCssPrefix = function(elem,prop,value){
        for(var i in this.cssprefix){
            var cssprefix = this.cssprefix[i];
            if(cssprefix===""){
                prop = prop.toLowerCase();
            }else{
                prop = prop.substr(0,1).toUpperCase()+prop.substr(1,prop.length).toLowerCase()
            }
            if(document.body.style[prop]!==undefined){
                elem.style[prop] = value;
                return false;
            }
        }
    };
    _picviewer.prototype.prev = function(){
        if (this.index > 0) {
            this.changeIndex(-1);
        }
    };
    _picviewer.prototype.next = function(){
        if (this.index < this.maxLen) {
            this.changeIndex(1);
        }
    };
    /*开始和渲染*/
    _picviewer.prototype.render = function(){
        var _this = this;
        document.body.style.overflow = 'hidden';
        if(this.box===false){ 
            this.box = document.createElement('div');
            this.box.setAttribute('class', 'previewImage-box'); 
        }else{
            this.box.innerHTML = ''; 
        }
        this.text = document.createElement('div');   
        this.text.setAttribute('class', 'previewImage-text');
        this.text.innerHTML = "<span class='page'>"+(this.index+1)+"/"+(this.maxLen+1)+"</span><span class='prev'><i class='glyphicon glyphicon-menu-left'></i></span><span class='next'><i class='glyphicon glyphicon-menu-right'></i></span><span class='exit'><i class='glyphicon glyphicon-remove'></i></span>";
        this.containerData = this.imgStatusCache[this.cIndex] = {elem:this.container,x:this.winw,y:0,m:0,my:0,scale:1,scalem:1}; 
        this.boxData = this.imgStatusCache[this.bIndex] = {elem:this.box,x:0,y:0,m:0,my:0,scale:1,scalem:1};   
        this.urls.forEach(function(v,i){    
            var div, hash, img, imgCache;
            div = document.createElement('div');
            hash = window.md5?md5(v+i):v+i;
            imgCache = _this.imgLoadCache[hash];
            if(imgCache&&imgCache.isload){   
                img = imgCache.elem;
            }else{  
                img = new Image();
                img.setAttribute('class', 'previewImage-image');
                _this.imgLoadCache[hash] = {isload:false,elem:img};
                if(i == _this.index){  
                    img.src = v;
                    img.onload = function(){
                        _this.imgLoadCache[hash].isload = true;
                    }
                }
            }
            _this.imgStatusCache[i] = {hash:hash,x:0,m:0,y:0,my:0,scale:_this.scale,scalem:1};
            div.setAttribute('class', 'previewImage-item');
            div.appendChild(img);
            _this.box.appendChild(div);
        })

        this.container.appendChild(this.box);  
        this.container.appendChild(this.text);   
        this.ePage = document.querySelector('.previewImage-text span.page');
        this.ePrev = document.querySelector('.previewImage-text span.prev');
        this.eNext = document.querySelector('.previewImage-text span.next');
        this.eCloser = document.querySelector('.previewImage-text span.exit');

        var offsetX = -this.imageChageMoveX*this.index;  
        this.boxData.x = offsetX;  
        this.containerData.x = 0;  
        this.container.style.display = "block";
        setTimeout(function(){
            _this.setActionStatus();
            if(_this.winw  > 992) {
                _this.urls.forEach(function(v, i) {
                    var that = _this, elImg, currentScale, toX, toY, overrideWidth, overrideHeight;
                    elImg = that.selectorEleAll[i];
                    currentScale = Math.min(that.winw / elImg.naturalWidth, that.winh / elImg.naturalHeight);
                    toX = toY = 0;
                    function imgHeight() {
                        return elImg.naturalHeight * currentScale;
                    }

                    function imgWidth() {
                        return elImg.naturalWidth * currentScale;
                    }

                    toX > 0 && (toX = 0);
                    overrideWidth = Math.round(that.winw - imgWidth());
                    if (toX < overrideWidth) {
                        toX = overrideWidth < 0 ? overrideWidth : overrideWidth / 2;
                    }
                    toY > 0 && (toY = 0);
                    overrideHeight = Math.round(that.winh - imgHeight());
                    if (toY < overrideHeight) {
                        toY = overrideHeight < 0 ? overrideHeight : overrideHeight / 2;
                    }
                    that.imgStatusCache[i].x = toX;
                    that.imgStatusCache[i].y = toY;

                    that.imgStatusCache[i].scale = currentScale;
                    that.translateScale(i, 0);
                });
            }
            _this.translateScale(_this.bIndex,0);
            _this.translateScale(_this.cIndex,_this.openTime);

            _this.isPreview = true;
            _this.ePrev.addEventListener('click', function(e) {
                e.preventDefault();
                _this.prev();
                return false;
            }, false);
            _this.eNext.addEventListener('click', function(e) {
                e.preventDefault();
                _this.next();
                return false;
            }, false);
            _this.eCloser.addEventListener('click', function(e) {
                e.preventDefault();
                _this.closePreview();
                return false;
            }, false);
        },50);
    };
    _picviewer.prototype.start = function(obj){
        this.container.innerHTML = '';
        if (!obj.urls || !_method.isArray(obj.urls) || obj.urls.length == 0) { 
            alert("urls must be a Array and the minimum length more than zero");
            return false;
        }
        if (!obj.current) {
            this.index = 0;
            console.warn("current is empty,it will be the first value of urls!");
        } else {
            var index = obj.urls.indexOf(obj.current);
            if (index < 0) {
                index = 0;
                console.warn("current isnot on urls,it will be the first value of urls!");
            }
            this.index = index; 
        }
        this.selectorEleAll = obj.elems;
        this.urls = obj.urls; 
        this.maxLen = obj.urls.length - 1;
        this.cIndex = this.maxLen + 1; 
        this.bIndex = this.maxLen + 2; 
        this.imgStatusCache = new Object(); 
        this.render(); 
    };
    _picviewer.prototype.init = function(selectorAll){
        var urls = [], _this = this;
        selectorAll.forEach(function(selector, index) {
            urls.push(selector.src);
            selector.addEventListener('click', function() {
                var obj = {
                    elems: selectorAll,
                    urls: urls,
                    current: this.src
                }
                _this.start(obj);
            });
        });
    }
    var picviewer = new _picviewer();

    return picviewer;
}]);