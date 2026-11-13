// Geen inline scripts (CSP: script-src 'self'). Alles werkt ook zonder JavaScript;
// dit script maakt het alleen sneller en prettiger.
(function () {
    'use strict';

    var root = document.documentElement;
    var COOKIE = 'wv_prefs';
    var MAX = 4;
    var CONTRASTS = ['standaard', 'hoog', 'donker'];

    // ------------------------------------------------------------------ voorkeuren
    function readPrefs() {
        var m = document.cookie.match(/(?:^|;\s*)wv_prefs=([^;]*)/);
        var p = { t: 1, c: 'standaard', r: 0 };
        if (m) {
            decodeURIComponent(m[1]).split('&').forEach(function (kv) {
                var a = kv.split('=');
                if (a[0] === 't' && /^[1-4]$/.test(a[1])) { p.t = parseInt(a[1], 10); }
                if (a[0] === 'c' && CONTRASTS.indexOf(a[1]) >= 0) { p.c = a[1]; }
                if (a[0] === 'r') { p.r = a[1] === '1' ? 1 : 0; }
            });
        }
        return p;
    }

    function applyPrefs(p) {
        root.classList.remove('t2', 't3', 't4', 'ruim');
        if (p.t > 1) { root.classList.add('t' + p.t); }
        if (p.r) { root.classList.add('ruim'); }
        if (p.c === 'standaard') { root.removeAttribute('data-contrast'); } else { root.setAttribute('data-contrast', p.c); }
    }

    function savePrefs(p) {
        var secure = location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = COOKIE + '=' + encodeURIComponent('t=' + p.t + '&c=' + p.c + '&r=' + p.r)
            + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
    }

    // A− en A+ in de kop: direct toepassen, zonder de pagina te verversen (ingevulde velden blijven staan)
    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('a[data-size]');
        if (!link || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) { return; }
        e.preventDefault();
        var p = readPrefs();
        p.t = Math.max(1, Math.min(MAX, p.t + parseInt(link.getAttribute('data-size'), 10)));
        applyPrefs(p);
        savePrefs(p);
        refreshSizeLinks(p);
    });

    function refreshSizeLinks(p) {
        document.querySelectorAll('a[data-size]').forEach(function (a) {
            var next = p.t + parseInt(a.getAttribute('data-size'), 10);
            a.href = a.href.replace(/([?&])t=\d/, '$1t=' + Math.max(1, Math.min(MAX, next)));
            var atLimit = next < 1 || next > MAX;
            a.toggleAttribute('hidden', false);
            a.style.opacity = atLimit ? '0.4' : '';
        });
    }

    // Weergave-pagina: voorbeeld direct tonen terwijl u kiest
    var form = document.querySelector('[data-prefs-form]');
    if (form) {
        var sample = form.querySelector('[data-sample]');
        var update = function () {
            var t = form.querySelector('input[name="t"]:checked');
            var c = form.querySelector('input[name="c"]:checked');
            var r = form.querySelector('input[name="r"]');
            applyPrefs({ t: t ? parseInt(t.value, 10) : 1, c: c ? c.value : 'standaard', r: r && r.checked ? 1 : 0 });
        };
        form.addEventListener('change', update);
        if (sample) { sample.setAttribute('aria-live', 'off'); }
    }

    // ---------------------------------------------------------------- bevestigingsvraag
    document.addEventListener('submit', function (e) {
        var msg = (e.submitter && e.submitter.getAttribute('data-confirm')) || e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) {
            e.preventDefault();
        }
    });

    // Keuzekaarten: markeer de gekozen optie (CSS :has() doet dit ook, dit is de terugval voor oudere browsers)
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'radio') { return; }
        var group = input.closest('fieldset') || document;
        group.querySelectorAll('input[type="radio"][name="' + input.name.replace(/"/g, '\\"') + '"]').forEach(function (r) {
            var card = r.closest('.choice');
            if (card) { card.classList.toggle('selected', r.checked); }
        });
    });

    // ------------------------------------------------------------------- lees voor
    var btn = document.querySelector('[data-speak]');
    if (btn && 'speechSynthesis' in window && typeof SpeechSynthesisUtterance !== 'undefined') {
        btn.hidden = false;
        var speaking = false;

        var stop = function () {
            window.speechSynthesis.cancel();
            speaking = false;
            btn.textContent = 'Lees voor';
            btn.classList.remove('speaking');
            btn.removeAttribute('aria-pressed');
        };

        var textToRead = function () {
            var sel = String(window.getSelection ? window.getSelection() : '').trim();
            if (sel) { return sel; }
            var main = document.getElementById('inhoud');
            if (!main) { return ''; }
            var clone = main.cloneNode(true);
            clone.querySelectorAll('script, style, .sr-only, [aria-hidden="true"], textarea, input, select').forEach(function (n) { n.remove(); });
            // vul ingevulde tekstvakken niet voor: dat zijn uw eigen woorden, en soms privé; alleen bij selectie
            return clone.innerText || clone.textContent || '';
        };

        btn.addEventListener('click', function () {
            if (speaking) { stop(); return; }
            var text = textToRead().replace(/\s+/g, ' ').trim();
            if (!text) { return; }
            // in stukken, want sommige browsers kappen lange teksten af
            var parts = text.match(/[^.!?]+[.!?]*\s*/g) || [text];
            var chunks = [], cur = '';
            parts.forEach(function (s) {
                if ((cur + s).length > 220 && cur) { chunks.push(cur); cur = s; } else { cur += s; }
            });
            if (cur) { chunks.push(cur); }

            speaking = true;
            btn.textContent = 'Stop met voorlezen';
            btn.classList.add('speaking');
            btn.setAttribute('aria-pressed', 'true');
            window.speechSynthesis.cancel();
            chunks.forEach(function (c, i) {
                var u = new SpeechSynthesisUtterance(c);
                u.lang = 'nl-NL';
                u.rate = 0.9;
                if (i === chunks.length - 1) { u.onend = stop; }
                u.onerror = function () { if (speaking) { stop(); } };
                window.speechSynthesis.speak(u);
            });
        });
        window.addEventListener('pagehide', function () { window.speechSynthesis.cancel(); });
    }

    // Foutmelding bovenaan krijgt focus, zodat schermlezers hem direct voorlezen
    var err = document.getElementById('fouten');
    if (err) { err.focus(); }
})();
