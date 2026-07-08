/**
 * Frontend JavaScript for FMR Booking Form.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Ensure the config object exists (fail gracefully if shortcode didn't load properly)
        if (typeof fmrBookingConfig === 'undefined') {
            return;
        }

        const $container = $('#fmr-booking-container');
        if (!$container.length) return;

        let currentStep = 1;
        let bookingData = {
            client_id: fmrBookingConfig.clientId,
            service_id: fmrBookingConfig.serviceId,
            date: null,
            start_time: null,
            booking_mode: 'in_person',
            intake_answers: {}
        };

        // --- Navigation ---
        $('.fmr-next-step').on('click', function() {
            // Basic validation before moving to next step
            if (currentStep === 1 && !bookingData.start_time) {
                alert('Please select a date and time slot first.');
                return;
            }
            goToStep(currentStep + 1);
        });

        $('.fmr-prev-step').on('click', function() {
            goToStep(currentStep - 1);
        });

        function goToStep(step) {
            $('.fmr-form-step').removeClass('active').hide();
            $(`.fmr-form-step[data-step="${step}"]`).addClass('active').fadeIn();
            
            $('.fmr-step').removeClass('active');
            $(`.fmr-step[data-step="${step}"]`).addClass('active');
            
            currentStep = step;
        }

        // Initialize display
        goToStep(1);

        // --- Date Selection & Slot Fetching ---
        $('#fmr-booking-date').on('change', function() {
            const selectedDate = $(this).val();
            if (!selectedDate) return;

            bookingData.date = selectedDate;
            bookingData.start_time = null; // Reset slot on date change
            
            fetchAvailableSlots(selectedDate);
        });

        function fetchAvailableSlots(date) {
            const $slotsContainer = $('#fmr-slots-container');
            $slotsContainer.html(`<p>${fmrBookingConfig.i18n.loading}</p>`);

            $.ajax({
                // Dynamically build the URL using the localized object
                url: `${fmrBookingConfig.restUrl}/services/${bookingData.service_id}/slots`,
                method: 'GET',
                data: { date: date },
                success: function(response) {
                    renderSlots(response.data);
                },
                error: function() {
                    $slotsContainer.html(`<p style="color:red;">${fmrBookingConfig.i18n.error}</p>`);
                }
            });
        }

        function renderSlots(slots) {
            const $slotsContainer = $('#fmr-slots-container');
            $slotsContainer.empty();

            if (!slots || slots.length === 0) {
                $slotsContainer.html('<p>No available slots for this date.</p>');
                return;
            }

            const $grid = $('<div class="fmr-slots-grid"></div>');
            
            slots.forEach(slot => {
                // Format the time for display (e.g., "09:00 AM")
                const timeString = new Date(slot.start.replace(/-/g, '/')).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                const $btn = $('<button type="button" class="fmr-slot-btn"></button>')
                    .text(timeString)
                    .data('time', slot.start)
                    .on('click', function() {
                        $('.fmr-slot-btn').removeClass('selected');
                        $(this).addClass('selected');
                        bookingData.start_time = $(this).data('time');
                    });
                
                $grid.append($btn);
            });

            $slotsContainer.append($grid);
        }

        // --- Form Submission ---
        $('#fmr-booking-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serializeArray();
            formData.forEach(item => {
                // Map form fields to our bookingData object
                if (['customer_name', 'customer_email', 'customer_phone', 'notes'].includes(item.name)) {
                    bookingData[item.name] = item.value;
                } else if (item.name === 'booking_mode') {
                    bookingData.booking_mode = item.value;
                }
            });

            const $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Processing...');

            $.ajax({
                // Hit the secured, standardized appointments endpoint
                url: `${fmrBookingConfig.restUrl}/appointments`,
                method: 'POST',
                beforeSend: function(xhr) {
                    // Inject the cryptographic nonce to pass WP security
                    xhr.setRequestHeader('X-WP-Nonce', fmrBookingConfig.nonce);
                },
                data: JSON.stringify(bookingData),
                contentType: 'application/json',
                success: function(response) {
                    // response.data.id now contains the secure UUID
                    goToStep(4); // Show success screen
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).text('Confirm Booking');
                    const errorMsg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error.message : fmrBookingConfig.i18n.error;
                    alert('Booking failed: ' + errorMsg);
                }
            });
        });
    });

})(jQuery);
