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
        if (currentTime < duration) {
            setTimeout(animateScroll, increment);
        }
    };
    animateScroll();
}

function scrollByPage(element, direction) {
    const containerWidth = element.offsetWidth;
    const currentScroll = element.scrollLeft;
    const maxScrollLeft = element.scrollWidth - containerWidth;

    // Use 20% of the container width as offset
    const percentageOffset = 0.20;
    const offset = containerWidth * percentageOffset;

    // Define the scroll amount subtracting the percentage-based offset
    let scrollAmount = containerWidth - offset;

    if (direction === 'right') {
        // Stop scrolling at the rightmost edge
        const remainingScroll = maxScrollLeft - currentScroll;
        scrollAmount = Math.min(scrollAmount, remainingScroll);
    } else {
        // Stop scrolling at the leftmost edge
        scrollAmount = Math.min(scrollAmount, currentScroll);
    }

    const targetScroll = direction === 'left' ? currentScroll - scrollAmount : currentScroll + scrollAmount;
    smoothScroll(element, targetScroll, 600); // Duration: 600ms
}

function updateScrollState(element, data) {
    const threshold = 5;
    data.isAtStart = element.scrollLeft <= threshold;
    const scrolledToEnd = element.scrollWidth - element.scrollLeft - element.clientWidth <= threshold;
    data.isAtEnd = scrolledToEnd;

    console.log("Updating Scroll State:");
    console.log("Current Scroll Left:", element.scrollLeft);
    console.log("Maximum Scroll Left:", element.scrollWidth - element.clientWidth);
    console.log("isAtStart:", data.isAtStart);
    console.log("isAtEnd:", data.isAtEnd);
}

document.addEventListener('alpine:init', () => {
    Alpine.data('scrollMenu', () => ({
        isAtStart: true,
        isAtEnd: false,
        init() {
            this.updateScrollState(this.$refs.scrollContainer);
            this.$refs.scrollContainer.addEventListener('scroll', () => {
                this.updateScrollState(this.$refs.scrollContainer);
            });

            // Initial check if elements exist already
            requestAnimationFrame(() => {
                this.updateScrollState(this.$refs.scrollContainer);
            });
        },
        scrollByPage(element, direction) {
            scrollByPage(element, direction);
            setTimeout(() => this.updateScrollState(element), 650); // Allow enough time for the smooth scroll to finish
        },
        updateScrollState: function(element) {
            return updateScrollState(element, this);
        }
    }));
});

