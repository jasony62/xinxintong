//define(['angular'], function(angular) {
(function(){
    'use strict';

    var ngMod = angular.module('thumbnail.ui.xxt', []);
    ngMod.service('tmsThumbnail', [ '$http', function(http) {

        this.thumbnail = function(editing) {
            if( !editing.pic && !editing.thumbnail){
                var canvas, context, img, url,
                    H = 96,
                    W = 96;
                canvas = document.createElement('canvas');
                canvas.width = W;
                canvas.height = H;
                context = canvas.getContext('2d');
                context.fillStyle = '#50555B';
                //设置绘制颜色
                //设置绘制线性?
                context.fillStyle = "#50555B";
                context.strokeStyle = "#fff";
                //填充一个矩形
                context.beginPath();
                context.rect(0,0,W,H);
                context.closePath();
                context.fill();
                //绘制一个圆
                context.lineWidth = '2';
                context.beginPath();
                context.arc(W/2,H/2,(W-10)/2,0,Math.PI*2);
                context.closePath();
                context.stroke();
                ////填充一个圆
                context.fillStyle = "#fff";
                context.beginPath();
                context.arc(W/2,H/2,(W-10-8)/2,0,Math.PI*2);
                context.closePath();
                context.fill();
                //
                context.fillStyle = "#CE2157";
                context.font = "bold 40px 微软雅黑";
                context.beginPath();
                context.stroke();
                context.textAlign = "center";
                //获取字符串第一个字
                context.fillText(editing.title.slice(0,1),W/2,(H+30)/2);
                //提交数据
                editing.pic = canvas.toDataURL('img/png');
                url = '/rest/pl/fe/matter/article/update?site=' + editing.siteid + '&id=' + editing.id;
                http.post(url,{'pic':editing.pic});
            }
        };
    }]);
})()


