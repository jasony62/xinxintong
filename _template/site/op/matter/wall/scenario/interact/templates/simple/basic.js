(function(){
    app.provider.controller('ctrlInteract',['$scope', '$http', function($scope, $http) {
        var num = $scope.Wall.scenario_config.player_sum / 4, startTime,
            time = $scope.time,
            url = window.location.href + "&time=" + time,
            boxs = document.querySelectorAll(".box"),
            uls = document.querySelectorAll(".box > ul"),
            bgImgs = document.querySelectorAll(".bgImg");
        $scope.players = [];
        //留住第一次打开页面的时间，并在URL上隐藏；
        function getQueryString(name) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var r = window.location.search.substr(1).match(reg);
            if (r != null) return unescape(r[2]);
            return null;
        }
        if (getQueryString("time") == null) {
            startTime = time;
            history.pushState({}, "", url);
        } else {
            startTime = getQueryString("time");
        }
        //布置最初的页面
        for(var i=0; i<uls.length; i++) {
            $(uls[i]).css({width: '290px',padding:'0 30px'});
            for(var j=0; j<num; j++) {
                var li = $("<li></li>"), img = $("<img />"),
                    liWidth = ($(uls[0]).width() - 40) / 3;
                li.css({width: liWidth, position:"relative"});
                if(j % 3 == 1){li.css('margin', '0 20px')};
                if(j > 2) {li.css('marginTop', '20px')};
                img.css({width: liWidth, height: liWidth, border: "3px solid #FFF", borderRadius: "10%"});
                img.attr('src',"/static/img/interactPao.png");
                li.append(img);
                $(uls[i]).append(li);
            }
        }
        if($scope.Wall.result_img) {
            angular.forEach(bgImgs, function(bgImg, index) {
                angular.element(bgImg).css({
                    display:'none',
                    background:'url(' + $scope.Wall.result_img[index].imgsrc + ') no-repeat',
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
            })
        }
        $scope.open = function(event) {
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
        $scope.play = function() {
            var timer2, count = 0, idx,
                imgs = document.querySelectorAll(".box > ul > li > img");
            timer2 = setInterval(function() {
                if(count >= $scope.players.length) {
                    clearInterval(timer2);
                } else {
                    if(count==num||count==num*2||count==num*3) {
                        var Num = count / num;
                        $(boxs[Num]).addClass("boxBg");
                    }
                    $(imgs[count]).attr('src', $scope.players[count].headimgurl);
                    $(imgs[count]).css('borderColor','#FFFA52');

                    if(count==num||count==num*2||count==num*3) {
                        idx = (count - num) / num;
                        $(boxs[idx]).find('.button').css('opacity','1');
                    }else if(count==num*4-1){
                        idx = (count + 1 - num) / num;
                        $(boxs[idx]).find('.button').css('opacity','1');
                    }
                    count++;
                }
            }, 1000);
        };
        $scope.start = function() {
            var url = '/rest/site/op/matter/wall/listPlayer?site=' + $scope.siteId + '&app=' + $scope.wallId + '&startTime=' + startTime + '&startId=';
            setTimeout(function(){
                $scope.players.length == '0' ? url : url += $scope.players[$scope.players.length-1].id;
                $http.get(url).success(function(rsp) {
                    angular.forEach(rsp.data, function(data) {
                        if($scope.players.length > 0) {
                            var isExisted = false;
                            angular.forEach($scope.players, function(player) {
                                if(player.userid == data.userid) {
                                    isExisted = true;
                                }
                            });
                            if(!isExisted) {
                                $scope.players.push(data);
                                $scope.play();
                            }
                        }else {
                            $scope.players.push(data);
                            $scope.play();
                        }
                    });
                });
                if($scope.players.length < $scope.Wall.scenario_config.player_sum) {
                    url = '/rest/site/op/matter/wall/listPlayer?site=' + $scope.siteId + '&app=' + $scope.wallId + '&startTime=' + startTime + '&startId=';
                    setTimeout(arguments.callee, 3000);
                }
            },3000);
        }
        angular.element(document).ready(function() {
            $scope.start();
        });
    }]);
})()
