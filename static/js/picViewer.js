(function(window){
    window.PicViewer = function(selector, options) {

        options || (options = {});
        options.log || (options.log = function(msg){});

        var hammertime;
        var vendorPrefixes = ["", "-webkit-", "-moz-", "-o-", "-ms-", "-khtml-"];
        var elImg;
        var viewHeight, viewWidth;
        var minScale, maxScale, currentScale, lastScale, scaleX, scaleY;
        var lastX,lastY,toX,toY;

        var imgHeight = function() {
            return elImg.height * currentScale;
        };
        var imgWidth = function() {
            return elImg.width * currentScale;
        };
        var transform = function() {
            var style, vendor, cssScale, cssTranslate, overrideWidth, overrideHeight;

            if (currentScale > maxScale) {
                return;
            }
            if (currentScale < minScale) currentScale = minScale;

            toX > 0 && (toX = 0);
            overrideWidth = Math.round(viewWidth - imgWidth());
            if (toX < overrideWidth) {
                toX = overrideWidth < 0 ? overrideWidth : overrideWidth / 2;
            }
            toY > 0 && (toY = 0);
            overrideHeight = Math.round(viewHeight - imgHeight());
            if (toY < overrideHeight) {
                toY = overrideHeight < 0 ? overrideHeight : overrideHeight / 2;
            }

            style = elImg.style, vendor;
            cssScale = "scale("+ currentScale +")";
            cssTranslate = 'translate(' + toX +"px, "+ toY +"px)"; //如果xy的值太小有可能使设置无效

            for (var i=0,l=vendorPrefixes.length; i<l; i++) {
                vendor = vendorPrefixes[i];
                style[vendor + "transform"] = cssTranslate + ' ' + cssScale;
            };
            options.log(cssTranslate + ' ' + cssScale);
        };
        var setOrigin = function(x, y) {
            var style = elImg.style, vendor;
            for (var i=0,l=vendorPrefixes.length;i<l;i++) {
                vendor = vendorPrefixes[i];
                style[vendor + "transform-origin"] = x + ' ' + y; 
            };
            style["MozTransformOrigin"] = x + ' ' + y;
        };
        this.fresh = function() {
            elImg = document.querySelector(selector);
            viewHeight = elImg.parentNode.clientHeight, viewWidth = elImg.parentNode.clientWidth;
            minScale = Math.min(viewWidth / elImg.width, viewHeight / elImg.height);
            maxScale = Math.max(viewWidth * 3 / elImg.width, viewHeight * 3 / elImg.height);
            lastScale = currentScale = minScale;
            scaleX = scaleY = 0;
            lastX = lastY = toX = toY = 0;

            if (hammertime === undefined) {
                hammertime = Hammer(elImg, {
                    preventDefault:true,
                    swipe:false,
                    dragMaxTouches:1
                }).
                on('tap', function(event){
                    event.gesture.stopDetect();
                    event.preventDefault();
                    options.close && options.close();
                }).
                on('transformstart', function(event){
                    event.preventDefault();
                    options.log('transformstart touches:' + event.gesture.touches.length);
                    options.log('transformstart center.pageX:' + event.gesture.center.pageX);
                    options.log('transformstart center.pageY:' + event.gesture.center.pageY);
                    options.log('transformstart center.clientX:' + event.gesture.center.clientX);
                    options.log('transformstart center.clientY:' + event.gesture.center.clientY);
                    scaleX = event.gesture.center.pageX;
                    scaleY = event.gesture.center.pageY;
                }).
                on('transform', function(event){
                    event.preventDefault();
                    var gesture, deltaScale;
                    gesture = event.gesture;
                    deltaScale = gesture.scale;
                    options.log('transform T:' + gesture.eventType +' scale:' + gesture.scale+',factor:' + currentScale);
                    if (lastScale * deltaScale > maxScale) {
                        if (lastScale === maxScale) {
                            return;
                        }
                        deltaScale = maxScale / lastScale;
                    } else if (lastScale * deltaScale < minScale) {
                        if (lastSacleScale === minScale) {
                            return;
                        }
                    }
                    currentScale = lastScale * deltaScale;
                    toX = scaleX - (scaleX - lastX)  * deltaScale;
                    toY = scaleY - (scaleY - lastY)  * deltaScale;
                    transform();
                }).
                on('transformend', function(event){
                    event.gesture.stopDetect();
                    event.preventDefault();
                    lastScale = currentScale;
                    lastX = toX;
                    lastY = toY;
                    options.log('transformend');
                }).
                on('dragstart', function(event){
                    options.log('dragstart touches:' + event.gesture.touches.length);
                    event.preventDefault();
                    lastX = toX;
                    lastY = toY;
                }).
                on('drag', function(event){
                    var gesture = event.gesture;
                    event.preventDefault();
                    options.log('drag T:' + gesture.eventType +' X:' + gesture.deltaX + ',Y:' + gesture.deltaY);
                    toX = lastX + (event.gesture.deltaX / currentScale);
                    toY = lastY + (event.gesture.deltaY / currentScale);
                    transform();
                }).
                on('dragend', function(event){
                    event.preventDefault();
                    var gesture = event.gesture;
                    options.log('dragend T:' + gesture.eventType +' X:' + gesture.deltaX + ',Y:' + gesture.deltaY);
                });
            }

            options.log('viewWidth:' + viewWidth);
            options.log('viewHeight:' + viewHeight);
            options.log('imgWidth:' + elImg.width);
            options.log('imgHeight:' + elImg.height);
            options.log('maxScale:' + maxScale);
            options.log('minScale:' + minScale);

            setOrigin(0, 0);
            transform();
        };
        return this;
    };
})(window);
