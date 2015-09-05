lotApp.register.controller('rouCtrl', ['$scope', '$interval', function($scope, $interval) {
    var lot,
        deg = Math.PI / 180,
        timer = null,
        stop = false,
        running = false,
        speed = 300,
        stepall,
        stepfast = 7,
        pointerIndex,
        stepslow,
        stepping,
        slotAngle,
        state,
        newAward,
        selectedSlot,
        playAfter;
    var setup = function() {
        clearTimer();
        state = 0;
        pointerIndex = 0;
        running = false;
        stop = true;
        stepping = 0;
        slotAngle = 360 / lot.plate.size;
        drawBg();
        drawPointer();
    };
    var drawAward = function(oAward, posx, posy) {
        var title, pic, eleWrap, eleBg, eleTitle;
        title = oAward.title;
        pic = oAward.pic;
        eleWrap = document.createElement('div');
        eleWrap.classList.add('award-slot');
        eleWrap.style.top = (posy - 30) + 'px';
        eleWrap.style.left = (posx - 30) + 'px';
        eleBg = document.createElement('div');
        eleBg.classList.add('award-bg');
        if (pic) eleBg.style.background = "url('" + pic + "')";
        eleTitle = document.createElement('div');
        eleTitle.classList.add('award-title');
        eleTitle.innerHTML = title;
        eleBg.appendChild(eleTitle);
        eleWrap.appendChild(eleBg);

        return eleWrap;
    };
    var drawBg = function() {
        var $wrap = $('#rouletteWrap'),
            startAngle = 270 - (slotAngle / 2),
            endAngle = startAngle + slotAngle,
            height = $wrap.height(),
            width = $wrap.width(),
            centerx = width / 2,
            centery = height / 2,
            radius = height / 2,
            eleBg = document.querySelector('.roulette-bg'),
            options,
            stylesLength;
        eleBg.height = height;
        eleBg.width = width;
        ctx = eleBg.getContext('2d');
        options = {
            fillStyle: ['#E60000', '#FFB30F']
        };
        stylesLength = options.fillStyle.length;
        // clean
        $('.award-slot').remove();
        // draw
        var i, l, aid, awardx, awardy, eleAward;
        for (i = 0, l = lot.plate.size; i < l; i++) {
            // slot
            ctx.beginPath();
            ctx.fillStyle = options.fillStyle[i % stylesLength];
            ctx.moveTo(centerx, centery);
            ctx.arc(centerx, centery, radius, deg * startAngle, deg * endAngle, false);
            ctx.fill();
            // award
            aid = lot.plate['a' + i];
            awardx = centerx + Math.cos(deg * (startAngle + (slotAngle / 2))) * radius * 0.75;
            awardy = centery + Math.sin(deg * (startAngle + (slotAngle / 2))) * radius * 0.75;
            eleAward = drawAward($scope.$parent.awards[aid], awardx, awardy);
            $wrap.append(eleAward);
            startAngle += slotAngle;
            endAngle += slotAngle;
        }
    };
    var drawPointer = function() {
        var elePlate = document.querySelector('.roulette-plate'),
            elePointer = document.querySelector('.roulette-pointer'),
            ctx = elePlate.getContext('2d'),
            startAngle = slotAngle * (pointerIndex - 2),
            half,
            dot_x, dot_y, dot_r,
            gradient;

        elePlate.height = $(elePointer).height();
        elePlate.width = $(elePointer).width();
        half = elePlate.width / 2;
        dot_r = half / 4;
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
        ctx.font = "bold 24px Microsoft Yahei,Arial";
        gradient = ctx.createLinearGradient(0, 0, elePlate.width, 0);
        gradient.addColorStop("1.0", "red");
        ctx.strokeStyle = gradient;
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.strokeText("抽奖", elePlate.width / 2, elePlate.height / 2);
    };
    var rotate = function() {
        if (state === 1) {
            if (stepping > 7) {
                if (lot.autostop === 'Y' && stepping >= lot.maxstep) {
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
        if (pointerIndex == lot.plate.size) {
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
        var circle, step, actualIndex;
        circle = Math.floor(Math.random() * 3) + 1;
        step = circle * lot.plate.size;
        actualIndex = stepping % lot.plate.size;
        stepall = selectedSlot - actualIndex + step;
        stepslow = stepall - 7;
        stepping = 0;
    };
    var onStopRotate = function() {
        playAfter();
    };
    var startClick = function() {
        $scope.$parent.play(function(result, after) {
            if (running) return;
            running = true;
            stop = false;
            newAward = result.award;
            selectedSlot = result.slot;
            playAfter = after;
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
    $scope.act = function(forced) {
        switch (state) {
            case 0:
                state = 1;
                startClick();
                break;
            case 1:
                if (forced || lot.autostop === 'N') {
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
    $scope.$on('xxt.app.lottery.ready', function(params) {
        lot = $scope.$parent.lot;
        setup();
    });
}]);