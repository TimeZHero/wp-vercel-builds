(function($) {
    $(document).ready(function() {
        refreshIntervalId = setInterval(function() {
            $.ajax({
                type: 'GET',
                url: '/wp-json/builds/status',
                success: function(response) {
                    if (response.badge !== $('#vercel_badge_status').attr('src')) {
                        $('#vercel_badge_status').attr('src', response.badge);
                    }

                    if (response.status !== 'deployment.created') {
                        clearInterval(refreshIntervalId);
                    }
                }
            });
        }, 5000);
    });
})(jQuery);
