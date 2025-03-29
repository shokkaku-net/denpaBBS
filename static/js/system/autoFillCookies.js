    // Function to get a cookie by name
    function getCookie(name) {
        let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        if (match) {
            return decodeURIComponent(match[2]);
        } else {
            return null;
        }
    }

    // Autofill form fields with cookie values
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('name').value = getCookie('name') || '';
        document.getElementById('email').value = getCookie('email') || '';
        document.getElementById('password').value = getCookie('password') || '';

        // Fill the additional password field in the managePost form
        const managePasswordField = document.querySelector('#managePost input[name="password"]');
        if (managePasswordField) {
            managePasswordField.value = getCookie('password') || '';
        }
    });