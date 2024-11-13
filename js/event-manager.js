jQuery(document).ready(function ($) {
    function updateCountdown() {
        $('.countdown').each(function () {
            const date = $(this).data('date');
            const eventDate = new Date(date);
            const currentDate = new Date();

            const timeDiff = eventDate - currentDate;
            if (timeDiff > 0) {
                const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
                $(this).text(days + " days remaining");
            } else {
                $(this).text("Event has passed");
            }
        });
    }

    // Update every minute
    setInterval(updateCountdown, 60000);
    updateCountdown(); // Initial call
});