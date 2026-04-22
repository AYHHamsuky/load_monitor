/**
 * assets/js/dashboard.js
 * Global dashboard utilities — clock only.
 * All 11kV load entry logic is handled inline in the dashboard view.
 */
function updateDateTime() {
    const el = document.getElementById('datetime');
    if (el) {
        el.innerText = new Date().toLocaleString();
    }
}
setInterval(updateDateTime, 1000);
updateDateTime();
