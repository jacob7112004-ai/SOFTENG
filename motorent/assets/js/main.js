// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .5s'; setTimeout(() => el.remove(), 500); }, 4000);
});

// Motorcycle selection in booking
document.querySelectorAll('.moto-card[data-id]').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.moto-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        const inp = document.getElementById('motorcycle_id');
        if (inp) inp.value = this.dataset.id;
        updateSummary();
    });
});

// Booking price summary
function updateSummary() {
    const start = document.getElementById('start_date')?.value;
    const end   = document.getElementById('end_date')?.value;
    const rate  = parseFloat(document.querySelector('.moto-card.selected')?.dataset.rate || 0);
    const dep   = parseFloat(document.querySelector('.moto-card.selected')?.dataset.deposit || 0);
    if (!start || !end || !rate) return;
    const days = Math.max(1, (new Date(end) - new Date(start)) / 86400000);
    const rental = days * rate;
    const total  = rental + dep;
    const el = id => document.getElementById(id);
    if (el('sum-days'))   el('sum-days').textContent   = days + ' day' + (days>1?'s':'');
    if (el('sum-rate'))   el('sum-rate').textContent   = '₱' + rate.toLocaleString();
    if (el('sum-rental')) el('sum-rental').textContent = '₱' + rental.toLocaleString();
    if (el('sum-dep'))    el('sum-dep').textContent    = '₱' + dep.toLocaleString();
    if (el('sum-total'))  el('sum-total').textContent  = '₱' + total.toLocaleString();
    if (el('total_amount')) el('total_amount').value   = total;
    if (el('deposit_amount')) el('deposit_amount').value = dep;
}

document.getElementById('start_date')?.addEventListener('change', updateSummary);
document.getElementById('end_date')?.addEventListener('change', updateSummary);

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});

// Search table filter
const searchInput = document.getElementById('table-search');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
