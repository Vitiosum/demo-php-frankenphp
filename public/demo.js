// Rafale de requêtes vers /worker et rendu du graphe (sorti du gabarit pour une CSP sans 'unsafe-inline').
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
  var size = parseInt(fig.dataset.burst, 10) || 40;
  var budget = parseInt(fig.dataset.budget, 10) || 10;
  var pageWorker = fig.dataset.pageWorker;
  var label = btn.textContent;
  var fr = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 });
  var running = false;

  function set(k, v) { stats.querySelector('[data-k="' + k + '"]').textContent = v; }

  function render(results) {
    var colors = {}, order = [];
    if (fig.dataset.mode === 'worker') { colors[pageWorker] = 0; order.push(pageWorker); }
    results.forEach(function (r) { if (!(r.worker in colors)) { colors[r.worker] = order.length; order.push(r.worker); } });
    var sorted = results.map(function (r) { return r.server_ms; }).sort(function (a, b) { return a - b; });
    var p95 = sorted.length ? sorted[Math.min(sorted.length - 1, Math.ceil(sorted.length * 0.95) - 1)] : 0;
    var top = Math.max(budget / 4, p95 * 1.5);
    var lineAt = budget / top;
    line.hidden = lineAt > 1;
    line.style.bottom = (Math.min(lineAt, 1) * 100) + '%';
    up.hidden = lineAt <= 1;
    empty.hidden = true;
    bars.textContent = '';
    results.forEach(function (r, i) {
      var b = document.createElement('span');
      var h = Math.min(r.server_ms / top, 1);
      b.style.setProperty('--h', Math.max(h, 0.03).toFixed(3));
      b.style.setProperty('--n', i);
      b.style.setProperty('--wc', 'var(--w' + (colors[r.worker] % 6) + ')');
      if (r.server_ms > budget) b.className = 'is-over';
      b.title = 'Requête ' + r.request + ' du worker ' + r.worker + ' · ' + fr.format(r.server_ms) + ' ms';
      bars.appendChild(b);
    });
    var ms = results.map(function (r) { return r.server_ms; }).sort(function (a, b) { return a - b; });
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
        var n = results.filter(function (r) { return r.worker === id; });
        var s = document.createElement('span');
        s.style.setProperty('--wc', 'var(--w' + (colors[id] % 6) + ')');
        s.textContent = id + ' · ' + n.length + ' req.';
        legend.appendChild(s);
      });
    } else {
      var s = document.createElement('em');
      s.textContent = seen.length + ' scripts PHP différents : sans worker, chaque requête repart de zéro.';
      legend.appendChild(s);
    }
    var mine = results.filter(function (r) { return r.worker === pageWorker && r.mode === 'worker'; });
    if (mine.length) {
      var last = mine[mine.length - 1];
      count.textContent = last.request.toLocaleString('fr-FR');
      served.textContent = last.request.toLocaleString('fr-FR');
      if (recycle && last.recycle_in !== null) recycle.textContent = last.recycle_in;
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
        list.forEach(function (r) { if (r) results.push(r); });
        render(results);
        batch();
      });
    }
    batch();
  }

  btn.addEventListener('click', fire);
})();
