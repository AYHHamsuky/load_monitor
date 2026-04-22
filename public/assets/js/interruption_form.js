const delaySelect = document.getElementById('delay_reason');
const otherInput  = document.getElementById('otherReason');
const otherLabel  = document.getElementById('otherLabel');

delaySelect.addEventListener('change', () => {
    if (delaySelect.value === 'others') {
        otherInput.style.display = 'block';
        otherLabel.style.display = 'block';
        otherInput.required = true;
    } else {
        otherInput.style.display = 'none';
        otherLabel.style.display = 'none';
        otherInput.required = false;
        otherInput.value = '';
    }
});

document.getElementById('interruptForm').addEventListener('submit', e => {
    if (!confirm('Confirm submission of this interruption record?')) {
        e.preventDefault();
    }
});
