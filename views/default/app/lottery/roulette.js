lotApp.controller('rouCtrl',['$scope','$interval',function($scope,$interval){
    var deg = Math.PI / 180,
    timer = null,
    stop = false,
    running = false,
    speed = 300,
    stepall, stepfast = 7,
    pointerIndex,
    stepslow, stepping,
    selected,
    slotAngle,
    state;
    var drawBg = function() {
        var tmp_1 = 270 - (slotAngle / 2),
        tmp_2 = tmp_1 + slotAngle,
        item_x, item_y,
        height = $('#rouletteWrap').height(),
        width = $('#rouletteWrap').width(),
        c_x = width / 2,
        c_y = height / 2,
        radius = height / 2,
        dom_bg = $('#bg')[0];
        dom_bg.height = height;
        dom_bg.width = width;
        ctx = dom_bg.getContext('2d');
        // clean
        $('.award_slot').remove();
        // draw
        for (var i = 0; i < $scope.setting.plate.size; i++) {
            ctx.beginPath();
            ctx.fillStyle = $scope.options.fillStyle[i % $scope.options.fillStyle.length];
            ctx.moveTo(c_x, c_y);
            ctx.arc(c_x, c_y, radius, deg * tmp_1, deg * tmp_2, false);
            ctx.fill();
            item_x = c_x + Math.cos(deg * (tmp_1 + (slotAngle / 2))) * radius * 0.75;
            item_y = c_y + Math.sin(deg * (tmp_1 + (slotAngle / 2))) * radius * 0.75;
            var aid = $scope.setting.plate['a'+i];
            var atitle = $scope.awards[aid].title,pic=$scope.awards[aid].pic;
            var slot = document.createElement('div');
            slot.classList.add('award_slot');
            slot.style.top = (item_y - 30) + 'px';
            slot.style.left =  (item_x - 30) + 'px';
            var award_bg = document.createElement('div');
            award_bg.classList.add('award_bg');
            if (pic) award_bg.style.background = "url('" + pic + "')";
            var award_title = document.createElement('div');
            award_title.classList.add('award_title');
            award_title.innerHTML = atitle;
            award_bg.appendChild(award_title);
            slot.appendChild(award_bg);
            $('#rouletteWrap').append(slot);
            tmp_1 += slotAngle;
            tmp_2 += slotAngle;
        }
    };
    var drawPointer = function() {
        var dom_plate = document.getElementById('plate'),
        ctx = dom_plate.getContext('2d'),
        startAngle = slotAngle * (pointerIndex - 2),
        half,
        dot_x,dot_y,dot_r,
        gradient;

        dom_plate.height = $('#pointer').height();
        dom_plate.width = $('#pointer').width();
        half = dom_plate.width / 2,
        dot_r = half/4,
        // disc
        ctx.beginPath();
        ctx.fillStyle = '#FFD700';
        ctx.arc(half, half, half, 0, 2 * Math.PI);
        ctx.fill();
        // dot
        dot_x = half + Math.cos(deg * startAngle) * half * 0.75;
        dot_y = half + Math.sin(deg * startAngle) * half * 0.75;
        ctx.beginPath();
        ctx.fillStyle = '#FF0000';
        ctx.arc(dot_x, dot_y, dot_r, 0, 2 * Math.PI);
        ctx.fill();
        // text
        ctx.font="bold 24px Microsoft Yahei,Arial";
        gradient=ctx.createLinearGradient(0,0,dom_plate.width,0);
        gradient.addColorStop("1.0","red");
        ctx.strokeStyle=gradient;
        ctx.textAlign="center";     
        ctx.textBaseline="middle"; 
        ctx.strokeText("抽奖",dom_plate.width / 2, dom_plate.height / 2); 
    };
    var rotate = function() {
        if (state === 1) {
            if (stepping > 7) {
                if ($scope.setting.autostop === 'Y' && stepping >= $scope.setting.maxstep) {
                    $scope.act(true);
                } else {
                    clearTimer();
                    speed = 100;
                    timer = $interval(rotate, speed);
                }
            }
        }
        if (state === 2 && stepping > stepslow && stepping < stepall) {
            clearTimer();
            speed = 300;
            timer = $interval(rotate, speed);
        }
        if (pointerIndex == $scope.setting.plate.size) {
            pointerIndex = 0;
        }
        if (state === 2 && stepping == stepall) {
            clearTimer();
            onStopRotate();
        }
        drawPointer();
        pointerIndex++;
        stepping++;
    };
    var createStop = function() {
        selected = $scope.setting.selected;
        var circle = Math.floor(Math.random() * 3) + 1;
        var c_step = circle * $scope.setting.plate.size;
        var actualIndex = stepping % $scope.setting.plate.size;
        stepall = selected - actualIndex + c_step;
        stepslow = stepall - 7;
        stepping = 0;
    };
    var onStopRotate = function() {
        var greeting;
        $scope.$parent.setting.chance = $scope.$parent.leftChance;
        $scope.leftChance = null;
        if ($scope.$parent.newAward) {
            if ($scope.$parent.newAward.type != 0)
                $scope.$parent.setting.myAwards.splice(0,0,$scope.newAward);
            greeting = $scope.$parent.newAward.award_greeting;
            $scope.$parent.newAward = null;
            if (greeting && greeting.length && greeting.trim().length)
                $scope.showGreeting(greeting);
        }
    };
    var startClick = function() {
        $scope.errmsg = '';
        $scope.nonfansalert = '';
        $scope.nochancealert = '';
        $scope.showGreeting('');
        $scope.play(function(){
            if (running) return;
            running = true;
            stop = false;
            timer = $interval(rotate, speed);
        });
    };
    var endClick = function() {
        if (stop) return;
        clearTimer();
        running = false;
        stop = true;
        createStop();
        timer = $interval(rotate, speed);
    };
    var clearTimer = function() {
        $interval.cancel(timer);
        timer = null;
    };
    var setup = function () {
        clearTimer();
        state = 0;
        pointerIndex = 0;
        running = false;
        stop = true;
        stepping = 0;
        slotAngle = 360 / $scope.setting.plate.size;
        drawBg();
        drawPointer();
    };
    $scope.act = function(forced) {
        switch (state) {
            case 0:
                state = 1;
                startClick();
                break;
            case 1:
                if (forced || $scope.setting.autostop === 'N') {
                    state = 2;
                    endClick();
                }
                break;
            case 2:
                setup();
                $scope.act();
                break;
            default:
                return;
        }
    };
    $scope.$watch('params', function(){
        setup();
    });
}]);
