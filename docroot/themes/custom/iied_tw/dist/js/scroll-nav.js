function easeInOutQuad(t) {
    return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
}

function smoothScroll(element, endX, duration) {
    let startX = element.scrollLeft,
        change = endX - startX,
        currentTime = 0,
        increment = 20;

    const animateScroll = function() {
        currentTime += increment;
        let val = easeInOutQuad(currentTime / duration);
        element.scrollLeft = startX + (change * val);
        if(currentTime < duration) {
            setTimeout(animateScroll, increment);
        }
    };
    animateScroll();
}

document.addEventListener('alpine:init', () => {
    Alpine.data('scroll', () => ({
        scrollByPage(element, direction) {
            const containerWidth = element.offsetWidth;
            const currentScroll = element.scrollLeft;
            const targetScroll = direction === 'left' ? currentScroll - containerWidth : currentScroll + containerWidth;
            smoothScroll(element, targetScroll, 1000); // Scroll duration: 600ms
        }
    }));
});