// Password strength checker
const passwordInput = document.getElementById('password');
if (passwordInput) {
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        strengthBar.className = 'password-strength-bar';
        
        if (strength === 0 || password.length === 0) {
            strengthBar.style.width = '0';
            strengthText.textContent = '';
        } else if (strength <= 2) {
            strengthBar.classList.add('strength-weak');
            strengthText.textContent = 'Weak password';
            strengthText.style.color = '#f44336';
        } else if (strength === 3) {
            strengthBar.classList.add('strength-medium');
            strengthText.textContent = 'Medium password';
            strengthText.style.color = '#ff9800';
        } else {
            strengthBar.classList.add('strength-strong');
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#4caf50';
        }
    });
}

// Form validation for registration
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            return false;
        }
    });
}