(function(){
    app.provider.controller('ctrlInteract',['$scope', '$http', '$compile', function($scope, $http, $compile) {
        $('.main').css('width', '1220px');
        var players = $scope.Wall.scenario_config.player_sum,
            imgs = $scope.Wall.matters_img,
            boxs = $scope.Wall.result_img.length,
            eachPartPerson = players / boxs,
            total = 0,
            time;
        $scope.winners = [];
        function createQrcode(imgs, marginRight) {
            for(var i=0; i<imgs.length; i++) {
                var img = $("<img />");
                img.attr('src', imgs[i].qrcodesrc);
                if(imgs.length > 1 && i < imgs.length -1) {
                    img.css({'margin-right': marginRight + 'px'});
                }
                $('.qrcodes').append(img);
            }
        }
        function createBlock(num, marginRight) {
            var div, width, template, $template;
            template = '<div class="case">';
            template += '<div class="case_main"><div class="cover"></div><div class="shine"></div></div>';
            template += '</div>';
            template += '<div class="case_button" ng-click="open($event)">开启宝箱</div>';

            if(num==1) {
                div = $("<div></div>"),
                width = $('.main').width(),
                template += '<div class="case_img" ng-click="scale(' + 0 + ')"></div>';
                $template = $compile(template)($scope);
                div.css('width', width).attr('class','box').append($("<ul></ul>")).append($template);
                $('.main').append(div);
            }else {
                for(var i=0; i<num; i++) {
                    div = $("<div></div>");
                    width = ($('.main').width() - (num-1)*marginRight) / num,
                    template += '<div class="case_img" ng-click="scale(' + i + ')"></div>';
                    $template = $compile(template)($scope);
                    div.css('width', width).attr('class','box').append($("<ul></ul>")).append($template);
                    if(i < num - 1){
                        div.css({'margin-right': marginRight + 'px'});
                    }
                    $('.main').append(div);
                }
            }
        }
        function count(ul, people, eachLineNum, marginRight, marginBottom) {
            for(var i=0; i<people; i++) {
                var li = $("<li></li>"),
                    img = $("<img />"),
                    width = Math.floor((ul.width() - marginRight*eachLineNum) / eachLineNum);

                li.css({
                    'width': width,
                    'height': width,
                    'margin-right': marginRight,
                    'margin-bottom': marginBottom
                });
                img.css({
                    'width': width,
                    'height': width,
                    'border': "3px solid #FFF",
                    'borderRadius': '10%'
                }).attr('src',"/static/img/xxq_interact/interactPao.png");

                li.append(img);

                if(people==players && (i==32||i==42)) {
                    li.css({'margin-right':width*4 + marginRight*5});
                }
                ul.append(li);
            }
            $('.case').css({'top':'55%','left':'44%'});
        }
        function layout(blocks, eachPartPerson) {
            var uls = $('.box').find('ul'),
                case_imgs = $('.case_img');
            for(var i=0; i<uls.length; i++) {
                switch(blocks) {
                    case 1:
                        count($(uls[i]), eachPartPerson, 14, 10, 10);
                        break;
                    case 2:
                        count($(uls[i]), eachPartPerson, 8, 10, 10);
                        break;
                    case 3:
                        count($(uls[i]), eachPartPerson, 5, 10, 10);
                        break;
                    case 4:
                        count($(uls[i]), eachPartPerson, 4, 10, 10);
                        $('.case').css({'top':'55%','left':'30%'});
                        $('.case_button').css('left','37%');
                        break;
                }

            }
            if($scope.Wall.result_img) {
                angular.forEach($scope.Wall.result_img, function(img, index) {
                    $(case_imgs[index]).css({
                        display:'none',
                        background:'url(' + img.imgsrc + ') no-repeat',
                        width: '20px',
                        height: '20px',
                        position: 'absolute',
                        margin: 'auto',
                        top: '110px',
                        left: '25px',
                        right: '0',
                        bottom: '0',
                        borderRadius: '5%'
                    });
                });
            }
        }
        function play(imgs, btns) {
            var timer2;
            timer2 = setInterval(function() {
                if(total >= $scope.winners.length) {
                    clearInterval(timer2);
                } else {
                    $(imgs[total]).attr('src', $scope.winners[total].headimgurl);
                    $(imgs[total]).css('borderColor','#FFFA52');

                    if(total!==0) {
                        if(total % eachPartPerson ===0) {
                            $(btns[(total / eachPartPerson) - 1]).css('opacity','1')
                        }
                    }

                    total++;
                }
            }, 2000);
        }
        function start() {
            var url = '/rest/site/op/matter/wall/listPlayer?site=' + $scope.siteId + '&app=' + $scope.wallId + '&startTime=' + time + '&startId=',
                imgs = document.querySelectorAll(".box>ul>li>img"),
                btns = document.querySelectorAll(".case_button");
            setTimeout(function(){
                $scope.winners.length == '0' ? url : url += $scope.winners[$scope.winners.length-1].id;
                $http.get(url).success(function(rsp) {
                    angular.forEach(rsp.data, function(data) {
                        if($scope.winners.length > 0) {
                            var isExisted = false;
                            angular.forEach($scope.winners, function(winner) {
                                if(winner.userid == data.userid) {
                                    isExisted = true;
                                }
                            });
                            if(!isExisted) {
                                $scope.winners.push(data);
                                play(imgs, btns);
                            }
                        }else {
                            $scope.winners.push(data);
                            play(imgs, btns);
                        }
                    });
                });
                if($scope.winners.length < $scope.Wall.scenario_config.player_sum) {
                    url = '/rest/site/op/matter/wall/listPlayer?site=' + $scope.siteId + '&app=' + $scope.wallId + '&startTime=' + time + '&startId=';
                    setTimeout(arguments.callee, 3000);
                }
            },3000);
        }
        $scope.scale = function(index) {
            $('.mask').css('display','block');
            $('.mask').find('img').attr('src',$scope.Wall.result_img[index].imgsrc);
        }
        $scope.open = function() {
            var prev = $(event.target).prev(),
                next = $(event.target).next();
            setTimeout(function(){
                prev.find('.cover').css({'animation':'moveCover 2s linear','animation-fill-mode':'forwards'});
            },0);
            setTimeout(function() {
                prev.find('.shine').css({'animation': 'moveShine 2s linear','animation-fill-mode': 'forwards'});
            }, 2000);
            setTimeout(function() {
                next.css({'display':'block','animation': 'moveImg 2s linear','animation-fill-mode': 'forwards'});
            }, 3000);
        }
        $scope.close = function() {
            $('.mask').find('img').attr('src','');
            $('.mask').css('display','none');
        }
        $scope.$watch('Wall.timestamp', function(nv) {
            if(!nv) {
                alert('请点击左上角设定活动开始时间');
                return false;
            }else{
                time = nv;
                start();
            }
        })
        angular.element(document).ready(function() {
            createQrcode(imgs, 130);
            createBlock(boxs, 20);
            layout(boxs, eachPartPerson);
        });
    }]);
})()
