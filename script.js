// script.js
document.addEventListener('DOMContentLoaded', () => {
    // Example: Add form validation for signup
    const signupForm = document.querySelector('form[action="signup.php"]');
    if (signupForm) {
        signupForm.addEventListener('submit', (e) => {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
            }
        });
    }
});