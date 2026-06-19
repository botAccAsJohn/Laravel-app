document.addEventListener('DOMContentLoaded', function() {
    let presenceRetries = 0;

    function initPresence() {
        if (window.Echo) {
            console.log('Echo presence join: store.browsing');
            window.Echo.join('store.browsing');
        } else if (presenceRetries < 5) {
            presenceRetries++;
            setTimeout(initPresence, 1000);
        }
    }
    initPresence();
});
