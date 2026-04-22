const loadInput = document.getElementById('load_read');
const faultBox  = document.getElementById('faultBox');
const faultCode = document.getElementById('fault_code');
const remark    = document.getElementById('fault_remark');

function toggleFault() {
    if (parseFloat(loadInput.value) > 0) {
        faultBox.style.opacity = 0.5;
        faultCode.disabled = true;
        remark.disabled = true;
        faultCode.value = '';
        remark.value = '';
    } else {
        faultBox.style.opacity = 1;
        faultCode.disabled = false;
        remark.disabled = false;
    }
}

loadInput.addEventListener('input', toggleFault);

document.getElementById('loadForm').addEventListener('submit', e => {
    if (!confirm('Confirm submission of this load entry?')) {
        e.preventDefault();
    }
});
