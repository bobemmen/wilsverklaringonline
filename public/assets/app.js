// Geen inline scripts (CSP: script-src 'self').
(function () {
    // Bevestigingsvraag: <form data-confirm="Weet u het zeker?"> of <button data-confirm="...">
    document.addEventListener('submit', function (e) {
        var msg = (e.submitter && e.submitter.getAttribute('data-confirm')) || e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) {
            e.preventDefault();
        }
    });

    // Keuzerondjes: markeer de gekozen optie in de groep
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'radio') { return; }
        var group = input.closest('.radio-group');
        if (!group) { return; }
        group.querySelectorAll('.radio-option').forEach(function (o) { o.classList.remove('selected'); });
        var label = input.closest('.radio-option');
        if (label) { label.classList.add('selected'); }
    });
})();
