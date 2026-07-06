/**
 * Frontend JavaScript for FMR Booking Form.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $container = $('#fmr-booking-container');
        if (!$container.length) return;

        let currentStep = 1;
        let bookingData = {
            client_id: $container.data('client-id'),
            service_id: null,
            date: null,
            start_time: null
        };

        // Navigation
        $('.fmr-next-step').on('click', function() {
            goToStep(currentStep + 1);
        });

        $('.fmr-prev-step').on('click', function() {
            goToStep(currentStep - 1);
        });

        function goToStep(step) {
            $('.fmr-form-step').removeClass('active');
            $(`.fmr-form-step[data-step="${step}"]`).addClass('active');
            
            $('.fmr-step').removeClass('active');
            $(`.fmr-step[data-step="${step}"]`).addClass('active');
            
            currentStep = step;
        }

        // Handle Form Submission
        $('#fmr-booking-form').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serializeArray();
            formData.forEach(item => {
                bookingData[item.name] = item.value;
            });

            $.ajax({
                url: '/wp-json/fmr-booking/v1/book',
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                data: bookingData,
                success: function(response) {
                    goToStep(4);
                },
                error: function(error) {
                    alert('Booking failed: ' + (error.responseJSON.message || 'Unknown error'));
                }
            });
        });

        // Mock Loading Services (In real scenario, this would be an AJAX call)
        function loadServices() {
            const $list = $('.fmr-services-list');
            // This is just a placeholder. In a real app, you'd fetch services via REST API.
            $list.html('<p>Loading services...</p>');
        }

        loadServices();
    });

})(jQuery);
