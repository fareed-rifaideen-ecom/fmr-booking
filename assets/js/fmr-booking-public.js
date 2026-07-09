document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.querySelector('input[type="date"]');
    
    if (!dateInput) return;

    dateInput.addEventListener('change', async (e) => {
        const selectedDate = e.target.value;
        if (!selectedDate) return;

        // Find or create the message container below the input
        let messageArea = dateInput.parentElement.nextElementSibling;
        if (!messageArea || messageArea.tagName.toLowerCase() === 'button') {
            messageArea = document.createElement('div');
            messageArea.style.marginTop = '15px';
            dateInput.parentElement.after(messageArea);
        }

        messageArea.innerHTML = `<p style="color:#666;">${fmrBookingConfig.i18n.loading}</p>`;

        try {
            const url = `${fmrBookingConfig.restUrl}/slots?service_id=${fmrBookingConfig.serviceId}&date=${selectedDate}`;
            
            const response = await fetch(url, {
                headers: { 'X-WP-Nonce': fmrBookingConfig.nonce }
            });

            // Handle potential 404 HTML responses if Permalinks aren't flushed
            const textResponse = await response.text();
            let data;
            try {
                data = JSON.parse(textResponse);
            } catch (parseError) {
                throw new Error("REST API is unreachable (404). Please go to Settings -> Permalinks in WordPress and click 'Save Changes' to flush routes.");
            }

            // 🚨 THE FIX: Throw the EXACT error message returned by our PHP backend
            if (!response.ok) {
                throw new Error(data.message || fmrBookingConfig.i18n.error);
            }

            // Handle empty slots gracefully
            if (data.length === 0) {
                messageArea.innerHTML = '<p>No available slots for this date.</p>';
                return;
            }

            // Render the slots dynamically
            let slotsHtml = '<div style="display:flex; flex-wrap:wrap; gap:10px;">';
            data.forEach(slot => {
                const timeString = new Date(slot.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                slotsHtml += `<button type="button" style="padding:10px 15px; border:1px solid #0073aa; background:transparent; color:#0073aa; border-radius:4px; cursor:pointer;" class="fmr-slot-btn" data-start="${slot.start}" data-end="${slot.end}">${timeString}</button>`;
            });
            slotsHtml += '</div>';

            messageArea.innerHTML = slotsHtml;

        } catch (error) {
            // 🚨 VISUALIZE THE REAL ERROR: Print the exact issue to the screen
            messageArea.innerHTML = `<p style="color:#d63638; font-weight:bold;">API Error: ${error.message}</p>`;
        }
    });
});
