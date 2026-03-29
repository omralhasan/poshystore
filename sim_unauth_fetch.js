const fetch = require('node-fetch');

(async () => {
    // Making a POST request without cookies translates to an unauthenticated request.
    const FormData = require('form-data');
    const form = new FormData();
    form.append('action', 'update_order_status');
    form.append('order_id', '27');
    form.append('status', 'shipped');
    
    try {
        const response = await fetch('http://localhost/pages/admin/admin_panel.php', {
            method: 'POST',
            body: form
        });
        const text = await response.text();
        console.log("Status:", response.status);
        console.log("Response text start:", text.substring(0, 50));
        JSON.parse(text);
    } catch (e) {
        console.error("JSON Parse Error:", e.message);
    }
})();
