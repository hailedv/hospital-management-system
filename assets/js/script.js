// Hospital Management System - Main JavaScript

// Form validation functions
function validateLoginForm() {
    const userType = document.getElementById('user_type').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (!userType) {
        alert('Please select a user type');
        return false;
    }
    
    if (!username.trim()) {
        alert('Please enter your username');
        return false;
    }
    
    if (!password.trim()) {
        alert('Please enter your password');
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters long');
        return false;
    }
    
    return true;
}

// Patient registration validation
function validatePatientRegistration() {
    const requiredFields = ['full_name', 'email', 'phone', 'address', 'username', 'password'];
    
    for (let field of requiredFields) {
        const element = document.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            alert(`Please fill in the ${field.replace('_', ' ')} field`);
            return false;
        }
    }
    
    // Validate email format
    const email = document.querySelector('[name="email"]').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }
    
    // Validate phone format
    const phone = document.querySelector('[name="phone"]').value;
    const phoneRegex = /^[0-9+\-\s()]+$/;
    if (!phoneRegex.test(phone)) {
        alert('Please enter a valid phone number');
        return false;
    }
    
    // Validate password confirmation
    const password = document.querySelector('[name="password"]').value;
    const confirmPassword = document.querySelector('[name="confirm_password"]');
    if (confirmPassword && password !== confirmPassword.value) {
        alert('Passwords do not match');
        return false;
    }
    
    return true;
}

// Medicine form validation
function validateMedicineForm() {
    const requiredFields = ['name', 'category', 'unit_price', 'stock_quantity'];
    
    for (let field of requiredFields) {
        const element = document.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            alert(`Please fill in the ${field.replace('_', ' ')} field`);
            return false;
        }
    }
    
    // Validate numeric fields
    const price = document.querySelector('[name="unit_price"]').value;
    const stock = document.querySelector('[name="stock_quantity"]').value;
    
    if (isNaN(price) || parseFloat(price) <= 0) {
        alert('Please enter a valid unit price');
        return false;
    }
    
    if (isNaN(stock) || parseInt(stock) < 0) {
        alert('Please enter a valid stock quantity');
        return false;
    }
    
    return true;
}

// Appointment form validation
function validateAppointmentForm() {
    const requiredFields = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time'];
    
    for (let field of requiredFields) {
        const element = document.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            alert(`Please select/fill in the ${field.replace('_', ' ')} field`);
            return false;
        }
    }
    
    // Validate appointment date (should be future date)
    const appointmentDate = new Date(document.querySelector('[name="appointment_date"]').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (appointmentDate < today) {
        alert('Appointment date cannot be in the past');
        return false;
    }
    
    return true;
}

// Billing form validation
function validateBillingForm() {
    const requiredFields = ['patient_id', 'consultation_fee'];
    
    for (let field of requiredFields) {
        const element = document.querySelector(`[name="${field}"]`);
        if (!element || !element.value.trim()) {
            alert(`Please fill in the ${field.replace('_', ' ')} field`);
            return false;
        }
    }
    
    // Validate numeric fields
    const numericFields = ['consultation_fee', 'medicine_cost', 'lab_charges', 'other_charges', 'discount'];
    
    for (let field of numericFields) {
        const element = document.querySelector(`[name="${field}"]`);
        if (element && element.value && (isNaN(element.value) || parseFloat(element.value) < 0)) {
            alert(`Please enter a valid ${field.replace('_', ' ')}`);
            return false;
        }
    }
    
    return true;
}

// Vitals form validation
function validateVitalsForm() {
    const numericFields = ['temperature', 'blood_pressure_systolic', 'blood_pressure_diastolic', 
                          'heart_rate', 'respiratory_rate', 'oxygen_saturation', 'weight', 'height'];
    
    for (let field of numericFields) {
        const element = document.querySelector(`[name="${field}"]`);
        if (element && element.value && (isNaN(element.value) || parseFloat(element.value) <= 0)) {
            alert(`Please enter a valid ${field.replace('_', ' ')}`);
            return false;
        }
    }
    
    // Validate blood pressure ranges
    const systolic = document.querySelector('[name="blood_pressure_systolic"]');
    const diastolic = document.querySelector('[name="blood_pressure_diastolic"]');
    
    if (systolic && diastolic && systolic.value && diastolic.value) {
        if (parseInt(systolic.value) <= parseInt(diastolic.value)) {
            alert('Systolic pressure should be higher than diastolic pressure');
            return false;
        }
    }
    
    return true;
}

// Auto-calculate total amount in billing
function calculateTotal() {
    const consultationFee = parseFloat(document.querySelector('[name="consultation_fee"]').value) || 0;
    const medicineCost = parseFloat(document.querySelector('[name="medicine_cost"]').value) || 0;
    const labCharges = parseFloat(document.querySelector('[name="lab_charges"]').value) || 0;
    const otherCharges = parseFloat(document.querySelector('[name="other_charges"]').value) || 0;
    const discount = parseFloat(document.querySelector('[name="discount"]').value) || 0;
    
    const totalAmount = consultationFee + medicineCost + labCharges + otherCharges;
    const finalAmount = totalAmount - discount;
    
    document.querySelector('[name="total_amount"]').value = totalAmount.toFixed(2);
    document.querySelector('[name="final_amount"]').value = finalAmount.toFixed(2);
}

// Search functionality
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    input.addEventListener('keyup', function() {
        const filter = input.value.toLowerCase();
        
        for (let i = 1; i < rows.length; i++) { // Skip header row
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let j = 0; j < cells.length; j++) {
                if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    });
}

// Confirm delete action
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`);
}

// Show loading spinner
function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'loading-spinner';
    loader.innerHTML = '<div class="spinner">Loading...</div>';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        color: white;
        font-size: 18px;
    `;
    document.body.appendChild(loader);
}

// Hide loading spinner
function hideLoading() {
    const loader = document.getElementById('loading-spinner');
    if (loader) {
        loader.remove();
    }
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'ETB'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Print functionality
function printPage() {
    window.print();
}

// Export to CSV
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        
        for (let j = 0; j < cols.length; j++) {
            csvRow.push('"' + cols[j].textContent.replace(/"/g, '""') + '"');
        }
        
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Initialize page functions
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for billing calculations
    const billingInputs = document.querySelectorAll('[name="consultation_fee"], [name="medicine_cost"], [name="lab_charges"], [name="other_charges"], [name="discount"]');
    billingInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    // Add search functionality to tables
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        const tableId = input.getAttribute('data-table');
        if (tableId) {
            searchTable(input.id, tableId);
        }
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Utility functions
const Utils = {
    // Validate email format
    isValidEmail: function(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    // Validate phone format
    isValidPhone: function(phone) {
        const regex = /^[0-9+\-\s()]+$/;
        return regex.test(phone);
    },
    
    // Generate random ID
    generateId: function(prefix, length = 4) {
        const numbers = Math.floor(Math.random() * Math.pow(10, length));
        return prefix + numbers.toString().padStart(length, '0');
    },
    
    // Debounce function for search
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};