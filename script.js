// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
    } else {
        input.type = 'password';
        button.textContent = 'Show';
    }
}

// Add toggle buttons to all password fields
document.addEventListener('DOMContentLoaded', function() {
    // Add toggle buttons to password fields
    const passwordFields = document.querySelectorAll('input[type="password"]');
    passwordFields.forEach(field => {
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'toggle-password';
        toggleButton.textContent = 'Show';
        toggleButton.onclick = function() {
            if (field.type === 'password') {
                field.type = 'text';
                this.textContent = 'Hide';
            } else {
                field.type = 'password';
                this.textContent = 'Show';
            }
        };
        
        // Wrap the input and button in a container
        const container = document.createElement('div');
        container.className = 'password-container';
        field.parentNode.insertBefore(container, field);
        container.appendChild(field);
        container.appendChild(toggleButton);
    });
    
    // Add animations to elements
    const animatedElements = document.querySelectorAll('.card, .form-section, .table-section');
    animatedElements.forEach((element, index) => {
        element.style.animationDelay = `${index * 0.1}s`;
        element.classList.add('fade-in');
    });
    
    // Add confirmation for delete actions
    const deleteButtons = document.querySelectorAll('a.btn-danger');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
});

// Toast notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    // Add styles if not already added
    if (!document.querySelector('#toast-styles')) {
        const styles = document.createElement('style');
        styles.id = 'toast-styles';
        styles.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                z-index: 1000;
                animation: slideInRight 0.3s, fadeOut 0.5s 2.5s forwards;
            }
            .toast.success { background-color: #4caf50; }
            .toast.error { background-color: #f44336; }
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; visibility: hidden; }
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(toast);
    
    // Remove toast after animation
    setTimeout(() => {
        toast.remove();
    }, 3000);
}