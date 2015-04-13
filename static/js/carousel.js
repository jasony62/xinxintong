var els = document.querySelectorAll('div.xxt-carousel');
for (var i=0,l=els.length,el; i<l && (el=els[i]); i++) {
    var carousel,el,i,page,data,slides=[],dots=[];
    data = el.querySelectorAll('.data dd');
    var elSlider = document.createElement('div');
    elSlider.classList.add('slider');
    el.appendChild(elSlider);
    var elNav = document.createElement('ul');
    var childWidth = 22 * data.length;
    elNav.width = childWidth + 'px';
    elNav.style.marginLeft = '-' + (childWidth/2+12) + 'px';
    elNav.classList.add('nav');
    el.appendChild(elNav);
    for (i=0; i<data.length; i++) {
        slides.push(data[i].innerHTML);
        var elNavDot = document.createElement('li');
        dots.push(elNavDot);
        elNav.appendChild(elNavDot);
    }
    dots[0].className = 'selected';
    carousel = new SwipeView(elSlider, {
        numberOfPages: slides.length
    });
    for (i=0; i<3; i++) {
        page = i==0 ? slides.length-1 : i-1;
        el = document.createElement('div');
        el.innerHTML = slides[page];
        carousel.masterPages[i].appendChild(el)
    }
    carousel.onFlip(function () {
        var el,upcoming,i;
        for (i=0; i<3; i++) {
            upcoming = carousel.masterPages[i].dataset.upcomingPageIndex;
            if (upcoming != carousel.masterPages[i].dataset.pageIndex) {
                el = carousel.masterPages[i].querySelector('div');
                el.innerHTML = slides[upcoming];
            }
        }
        document.querySelector('div.xxt-carousel .nav .selected').className = '';
        dots[carousel.pageIndex].className = 'selected';
    });
}
