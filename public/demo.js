// Rafale de requêtes vers /worker : la gerbe d'étincelles, le marteau et les braises de la forge.
// Fichier séparé du gabarit pour une CSP sans 'unsafe-inline'.
(function () {
  var fig = document.getElementById('pulse');
  if (!fig || !window.fetch) return;
  var btn = document.getElementById('fp-fire');
  var bars = document.getElementById('fp-bars');
  var plot = document.getElementById('fp-plot');
  var legend = document.getElementById('fp-legend');
  var stats = document.getElementById('fp-stats');
  var count = document.getElementById('fp-count');
  var served = document.getElementById('fp-served');
  var recycle = document.getElementById('fp-recycle');
  var line = document.getElementById('fp-line');
  var up = document.getElementById('fp-up');
  var empty = document.getElementById('fp-empty');
  var forge = document.getElementById('forge');
  var sparks = document.getElementById('fp-sparks');
  var litLabel = document.getElementById('fp-lit');
  var coals = forge ? forge.querySelectorAll('.fp-coal') : [];
  var size = parseInt(fig.dataset.burst, 10) || 40;
  var budget = parseInt(fig.dataset.budget, 10) || 10;
  var pageWorker = fig.dataset.pageWorker;
  var label = btn.textContent;
  var fr = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 });
  var calm = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var running = false;
  var colors = {}, order = [];

  function set(k, v) { stats.querySelector('[data-k="' + k + '"]').textContent = v; }
  var classic = fig.dataset.mode !== 'worker';
  // Sans worker, chaque requête est un script différent : une seule teinte froide, pas six couleurs qui ne désignent personne.
  function color(id) { return classic ? 'var(--f-bleui)' : 'var(--w' + (colors[id] % 6) + ')'; }

  // Le marteau frappe et une étincelle part par réponse, dans la couleur du worker qui a répondu.
  function strike(list) {
    if (!forge || calm) return;
    forge.classList.remove('is-striking');
    void forge.offsetWidth;
    forge.classList.add('is-striking');
    window.setTimeout(function () {
      list.forEach(function (r) {
        for (var k = 0; k < 3; k++) {
          var s = document.createElement('i');
          s.className = 'fp-spark';
          s.style.setProperty('--a', (-170 + Math.random() * 160).toFixed(1) + 'deg');
          s.style.setProperty('--d', (40 + Math.random() * 90).toFixed(0) + 'px');
          s.style.setProperty('--t', (0.45 + Math.random() * 0.4).toFixed(2) + 's');
          if (!classic) s.style.setProperty('--wc', color(r.worker));
          s.addEventListener('animationend', function () { this.remove(); });
          sparks.appendChild(s);
        }
      });
    }, 130);
  }

  // Les braises suivent le compteur du worker de la page : une de plus à chaque doublement.
  function heat(n) {
    var lit = 0;
    Array.prototype.forEach.call(coals, function (c) {
      var on = n >= parseInt(c.getAttribute('data-at'), 10);
      c.classList.toggle('is-lit', on);
      if (on) lit++;
    });
    if (forge && coals.length) forge.style.setProperty('--heat', (lit / coals.length).toFixed(3));
    if (litLabel) litLabel.textContent = lit;
  }

  function render(results) {
    results.forEach(function (r) { if (!(r.worker in colors)) { colors[r.worker] = order.length; order.push(r.worker); } });
    var ms = results.map(function (r) { return r.server_ms; }).sort(function (a, b) { return a - b; });
    var p95 = ms.length ? ms[Math.min(ms.length - 1, Math.ceil(ms.length * 0.95) - 1)] : 0;
    var top = Math.max(budget / 4, p95 * 1.5);
    var lineAt = budget / top;
    line.hidden = lineAt > 1;
    line.style.bottom = (Math.min(lineAt, 1) * 100) + '%';
    up.hidden = lineAt <= 1;
    empty.hidden = true;
    bars.textContent = '';
    results.forEach(function (r, i) {
      var b = document.createElement('span');
      b.style.setProperty('--h', Math.max(Math.min(r.server_ms / top, 1), 0.04).toFixed(3));
      b.style.setProperty('--wc', color(r.worker));
      if (r.server_ms > budget) b.className = 'is-over';
      if (r.request === 1) {
        var boot = document.createElement('b');
        boot.className = 'fp-boot';
        b.appendChild(boot);
      }
      b.title = 'Requête ' + r.request + ' du worker ' + r.worker + ' : ' + fr.format(r.server_ms) + ' ms' + (r.request === 1 ? ', four rallumé' : '');
      bars.appendChild(b);
    });
    var median = ms.length ? ms[Math.floor((ms.length - 1) / 2)] : 0;
    var under = results.filter(function (r) { return r.server_ms <= budget; }).length;
    var seen = order.filter(function (id) { return results.some(function (r) { return r.worker === id; }); });
    var boots = results.filter(function (r) { return r.request === 1; }).length;
    set('median', fr.format(median) + ' ms');
    set('under', under + ' / ' + results.length);
    set('workers', seen.length);
    set('boots', boots === 0 ? '0 ✓' : boots);
    stats.querySelector('[data-k="boots"]').classList.toggle('is-ok', boots === 0);
    plot.setAttribute('aria-label', results.length + ' requêtes, médiane ' + fr.format(median) + ' ms, ' + under + ' sous le budget de ' + budget + ' ms, ' + seen.length + ' workers, ' + boots + ' redémarrages de Symfony');
    legend.textContent = '';
    if (seen.length <= 6) {
      seen.forEach(function (id) {
        var n = results.filter(function (r) { return r.worker === id; }).length;
        var s = document.createElement('span');
        s.style.setProperty('--wc', color(id));
        s.textContent = id + ' : ' + n + ' req.';
        legend.appendChild(s);
      });
    } else {
      var e = document.createElement('em');
      e.textContent = seen.length + ' scripts PHP différents : sans worker, chaque requête rallume le four.';
      legend.appendChild(e);
    }
    var mine = results.filter(function (r) { return r.worker === pageWorker && r.mode === 'worker'; });
    if (mine.length) {
      var last = mine[mine.length - 1];
      if (count.textContent !== last.request.toLocaleString('fr-FR')) {
        count.textContent = last.request.toLocaleString('fr-FR');
        count.classList.remove('is-stamped');
        void count.offsetWidth;
        count.classList.add('is-stamped');
      }
      served.textContent = last.request.toLocaleString('fr-FR');
      if (recycle && last.recycle_in !== null) recycle.textContent = last.recycle_in;
      heat(last.request);
    }
  }

  function one() {
    return fetch('/worker', { cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .catch(function () { return null; });
  }

  function fire() {
    if (running) return;
    running = true;
    btn.disabled = true;
    btn.textContent = 'Rafale en cours…';
    colors = {}; order = [];
    if (fig.dataset.mode === 'worker') { colors[pageWorker] = 0; order.push(pageWorker); }
    var results = [], sent = 0;
    function batch() {
      if (sent >= size) {
        running = false;
        btn.disabled = false;
        btn.textContent = label;
        return;
      }
      var n = Math.min(4, size - sent), calls = [];
      for (var i = 0; i < n; i++) calls.push(one());
      sent += n;
      Promise.all(calls).then(function (list) {
        var got = list.filter(Boolean);
        got.forEach(function (r) { results.push(r); });
        strike(got);
        render(results);
        batch();
      });
    }
    batch();
  }

  btn.addEventListener('click', fire);
})();
