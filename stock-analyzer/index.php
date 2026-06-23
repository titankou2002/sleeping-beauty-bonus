<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>股票戰情室</title>
<style>
:root {
  --bg: #0a0a0a; --bg2: #111; --surface: #161616;
  --border: rgba(255,255,255,0.08); --border2: rgba(255,255,255,0.12);
  --text: #fff; --text2: rgba(255,255,255,0.5); --text3: rgba(255,255,255,0.3);
  --gold: #c29d66; --red: #ef4444; --green: #22c55e; --blue: #3b82f6;
  --orange: #f97316; --purple: #a855f7;
  --radius: 12px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: var(--bg); color: var(--text); font-family: -apple-system, "SF Pro", "Noto Sans TC", sans-serif; font-size: 14px; min-height: 100vh; }
a { color: var(--gold); text-decoration: none; }

.app { max-width: 480px; margin: 0 auto; min-height: 100vh; display: flex; flex-direction: column; }

/* Top bar */
.topbar { padding: 12px 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: rgba(10,10,10,0.92); backdrop-filter: blur(16px); z-index: 100; }
.topbar h1 { font-size: 16px; font-weight: 800; color: var(--gold); flex: 1; }
.search-box { display: flex; gap: 6px; }
.search-box input { height: 34px; padding: 0 10px; border-radius: 8px; border: 1px solid var(--border2); background: var(--surface); color: var(--text); font-size: 13px; width: 120px; outline: none; font-family: inherit; }
.search-box input:focus { border-color: var(--gold); }
.search-box button { height: 34px; padding: 0 14px; border-radius: 8px; border: none; background: linear-gradient(135deg, var(--gold), #d4b483); color: #000; font-weight: 700; font-size: 13px; cursor: pointer; }

/* Bottom tabs */
.bottom-tabs { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 480px; display: flex; background: rgba(16,16,16,0.95); backdrop-filter: blur(16px); border-top: 1px solid var(--border); z-index: 100; padding: 6px 0 env(safe-area-inset-bottom, 8px); }
.tab-item { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 6px 0; cursor: pointer; color: var(--text3); font-size: 10px; font-weight: 600; transition: color 0.2s; }
.tab-item.active { color: var(--gold); }
.tab-item .tab-icon { font-size: 20px; }

/* Main content */
.main { flex: 1; padding: 12px 12px 80px; }

/* Stock card */
.stock-card { background: var(--surface); border: 1px solid var(--border2); border-radius: var(--radius); padding: 16px; margin-bottom: 10px; cursor: pointer; transition: border-color 0.2s; }
.stock-card:hover { border-color: rgba(194,157,102,0.3); }
.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.card-name { font-size: 15px; font-weight: 800; }
.card-sub { font-size: 11px; color: var(--text2); margin-top: 2px; }
.card-action { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.action-badge { font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 4px; }
.action-badge .arrow { font-size: 16px; }
.dots { display: flex; gap: 3px; }
.dot { width: 10px; height: 10px; border-radius: 50%; }
.dot-red { background: var(--red); }
.dot-yellow { background: var(--orange); }
.dot-green { background: var(--green); }
.card-price { font-size: 20px; font-weight: 900; color: var(--red); margin-bottom: 4px; }
.card-price.up { color: var(--red); }
.card-price.down { color: var(--green); }
.card-change { font-size: 12px; color: var(--text2); margin-bottom: 10px; }
.card-reason { font-size: 12px; color: var(--text2); line-height: 1.5; margin-bottom: 8px; }
.card-stoploss { font-size: 11px; color: var(--text3); }
.card-signal { margin-top: 8px; padding: 8px 10px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; font-size: 12px; }
.card-signal .sig-icon { margin-right: 4px; }
.card-signal.bullish { border-color: rgba(34,197,94,0.3); color: var(--green); }
.card-signal.bearish { border-color: rgba(239,68,68,0.3); color: var(--red); }

/* Section headers */
.section-title { font-size: 14px; font-weight: 800; color: var(--gold); padding: 16px 4px 10px; display: flex; align-items: center; gap: 8px; }
.section-title .count { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 2px 8px; font-size: 11px; color: var(--text2); }

/* Detail page */
.detail-page { display: none; }
.detail-page.active { display: block; }
.detail-back { display: flex; align-items: center; gap: 6px; padding: 12px 0; font-size: 14px; color: var(--gold); cursor: pointer; font-weight: 700; }
.detail-header { padding: 8px 0 16px; }
.detail-header .dh-name { font-size: 20px; font-weight: 900; }
.detail-header .dh-sub { font-size: 12px; color: var(--text2); margin-top: 4px; }
.detail-header .dh-price { font-size: 32px; font-weight: 900; margin-top: 8px; }
.detail-header .dh-change { font-size: 14px; margin-top: 4px; }

/* Indicator cards */
.ind-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 16px; }
.ind-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 12px; }
.ind-label { font-size: 10px; color: var(--text3); font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 6px; }
.ind-value { font-size: 18px; font-weight: 800; }

/* Signal list */
.signal-list { margin-bottom: 16px; }
.signal-item { display: flex; align-items: flex-start; gap: 8px; padding: 10px 12px; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 6px; }
.signal-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
.signal-dot.bullish { background: var(--green); }
.signal-dot.bearish { background: var(--red); }
.signal-text { flex: 1; }
.signal-name { font-size: 13px; font-weight: 700; }
.signal-desc { font-size: 11px; color: var(--text2); margin-top: 2px; }

/* Chart placeholder */
.chart-box { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; margin-bottom: 16px; min-height: 200px; position: relative; }
.chart-title { font-size: 13px; font-weight: 800; color: var(--gold); margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
.chart-canvas { width: 100%; height: 180px; }

/* Valuation bar */
.val-bar { display: flex; height: 8px; border-radius: 4px; overflow: hidden; margin: 8px 0; }
.val-seg { height: 100%; }
.val-cheap { background: var(--green); }
.val-fair { background: var(--orange); }
.val-expensive { background: var(--red); }
.val-labels { display: flex; justify-content: space-between; font-size: 10px; color: var(--text3); margin-bottom: 4px; }
.val-marker { font-size: 13px; font-weight: 800; color: var(--gold); }

/* Chip flow */
.chip-bar { display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
.chip-label { font-size: 11px; color: var(--text2); min-width: 32px; }
.chip-track { flex: 1; height: 16px; background: var(--bg2); border-radius: 4px; overflow: hidden; position: relative; }
.chip-fill { height: 100%; border-radius: 4px; transition: width 0.4s; }
.chip-fill.pos { background: var(--red); }
.chip-fill.neg { background: var(--green); }
.chip-value { font-size: 11px; font-weight: 700; min-width: 70px; text-align: right; }

/* Loading */
.loading { position: fixed; inset: 0; background: rgba(10,10,10,0.85); display: flex; align-items: center; justify-content: center; z-index: 999; }
.loading.hidden { display: none; }
.spinner { width: 24px; height: 24px; border: 2px solid rgba(194,157,102,0.2); border-top-color: var(--gold); border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 10px; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty state */
.empty { text-align: center; padding: 60px 20px; color: var(--text2); }
.empty h3 { color: var(--gold); margin-bottom: 8px; }

/* Watchlist form */
.wl-form { display: flex; gap: 6px; padding: 12px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 12px; flex-wrap: wrap; }
.wl-form input, .wl-form select { height: 32px; padding: 0 8px; border-radius: 6px; border: 1px solid var(--border2); background: var(--bg2); color: var(--text); font-size: 12px; outline: none; }
.wl-form input { flex: 1; min-width: 80px; }
.wl-form select { min-width: 60px; }
.wl-form button { height: 32px; padding: 0 12px; border-radius: 6px; border: none; background: var(--gold); color: #000; font-weight: 700; font-size: 12px; cursor: pointer; }

.remove-btn { background: none; border: none; color: var(--red); font-size: 18px; cursor: pointer; padding: 4px; line-height: 1; }

@media (min-width: 600px) {
  .app { border-left: 1px solid var(--border); border-right: 1px solid var(--border); }
}
</style>
</head>
<body>
<div class="app">
  <div class="topbar">
    <h1>股票戰情室</h1>
    <div class="search-box">
      <input type="text" id="search-input" placeholder="輸入股票代號" onkeydown="if(event.key==='Enter')searchStock()">
      <button onclick="searchStock()">查詢</button>
    </div>
  </div>

  <div id="loading" class="loading hidden">
    <div class="spinner"></div>
    <span style="color:var(--gold);font-weight:700">分析中...</span>
  </div>

  <!-- Tab: Dashboard -->
  <div class="main" id="page-dashboard">
    <div id="dashboard-content">
      <div class="empty"><h3>載入中...</h3><p>正在掃描監控清單</p></div>
    </div>
  </div>

  <!-- Tab: Watchlist -->
  <div class="main" id="page-watchlist" style="display:none">
    <div class="section-title">監控清單</div>
    <div class="wl-form">
      <input type="text" id="wl-stock" placeholder="代號 (如 2330)">
      <select id="wl-market"><option value="tw">台股</option><option value="us">美股</option></select>
      <input type="number" id="wl-entry" placeholder="成本價" style="width:80px">
      <button onclick="addWatchlist()">+ 加入</button>
    </div>
    <div id="watchlist-content"></div>
  </div>

  <!-- Tab: Detail -->
  <div class="main detail-page" id="page-detail" style="display:none">
    <div class="detail-back" onclick="showTab('dashboard')">← 返回</div>
    <div id="detail-content"></div>
  </div>

  <!-- Tab: Alerts -->
  <div class="main" id="page-alerts" style="display:none">
    <div class="section-title">提醒紀錄</div>
    <div id="alerts-content"></div>
  </div>

  <!-- Bottom tabs -->
  <div class="bottom-tabs">
    <div class="tab-item active" id="tab-dashboard" onclick="showTab('dashboard')">
      <span class="tab-icon">📊</span><span>總覽</span>
    </div>
    <div class="tab-item" id="tab-watchlist" onclick="showTab('watchlist')">
      <span class="tab-icon">⭐</span><span>監控</span>
    </div>
    <div class="tab-item" id="tab-alerts" onclick="showTab('alerts')">
      <span class="tab-icon">🔔</span><span>提醒</span>
    </div>
  </div>
</div>

<script>
var API = 'api.php';
var currentTab = 'dashboard';
var allResults = [];

function showLoading(show) { document.getElementById('loading').classList.toggle('hidden', !show); }

function showTab(tab) {
  currentTab = tab;
  ['dashboard','watchlist','alerts','detail'].forEach(function(t) {
    var el = document.getElementById('page-' + t);
    if (el) el.style.display = t === tab ? '' : 'none';
  });
  document.querySelectorAll('.tab-item').forEach(function(el) {
    el.classList.toggle('active', el.id === 'tab-' + tab);
  });
  if (tab === 'dashboard' && allResults.length === 0) scanAll();
  if (tab === 'watchlist') loadWatchlist();
  if (tab === 'alerts') loadAlerts();
}

function apiGet(action, params) {
  var url = API + '?action=' + action;
  if (params) for (var k in params) url += '&' + k + '=' + encodeURIComponent(params[k]);
  return fetch(url).then(function(r) { return r.json(); });
}

function apiPost(action, body) {
  return fetch(API + '?action=' + action, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  }).then(function(r) { return r.json(); });
}

function scanAll() {
  showLoading(true);
  apiGet('scan-all').then(function(res) {
    showLoading(false);
    if (!res.success) { document.getElementById('dashboard-content').innerHTML = '<div class="empty"><h3>錯誤</h3><p>' + (res.msg || '') + '</p></div>'; return; }
    allResults = res.data || [];
    renderDashboard();
  }).catch(function(err) {
    showLoading(false);
    document.getElementById('dashboard-content').innerHTML = '<div class="empty"><h3>連線失敗</h3><p>' + err + '</p></div>';
  });
}

function renderDashboard() {
  var html = '';
  if (allResults.length === 0) {
    html = '<div class="empty"><h3>尚無資料</h3><p>請先在「監控」頁面加入股票</p></div>';
    document.getElementById('dashboard-content').innerHTML = html;
    return;
  }

  // Group by recommendation
  var groups = { risk: [], wait: [], watch: [], good: [] };
  allResults.forEach(function(r) {
    var score = r.recommendation ? r.recommendation.score : 50;
    if (score <= 30) groups.risk.push(r);
    else if (score <= 45) groups.wait.push(r);
    else if (score <= 60) groups.watch.push(r);
    else groups.good.push(r);
  });

  if (groups.risk.length) {
    html += '<div class="section-title">風險升高 <span class="count">' + groups.risk.length + '</span></div>';
    groups.risk.forEach(function(r) { html += renderStockCard(r); });
  }
  if (groups.wait.length) {
    html += '<div class="section-title">等回檔 <span class="count">' + groups.wait.length + '</span></div>';
    groups.wait.forEach(function(r) { html += renderStockCard(r); });
  }
  if (groups.watch.length) {
    html += '<div class="section-title">觀望 <span class="count">' + groups.watch.length + '</span></div>';
    groups.watch.forEach(function(r) { html += renderStockCard(r); });
  }
  if (groups.good.length) {
    html += '<div class="section-title">偏多 <span class="count">' + groups.good.length + '</span></div>';
    groups.good.forEach(function(r) { html += renderStockCard(r); });
  }

  document.getElementById('dashboard-content').innerHTML = html;
}

function renderStockCard(r) {
  var rec = r.recommendation || {};
  var risk = r.risk || {};
  var tech = r.technical || {};
  var changePct = r.changePct || 0;
  var isUp = changePct >= 0;

  var dots = renderDots(risk.dots || [2,1,2]);
  var topSignal = (tech.signals && tech.signals[0]) ? tech.signals[0] : null;

  var h = '<div class="stock-card" onclick="showDetail(\'' + r.stockId + '\',\'' + (r.market || 'tw') + '\')">';
  h += '<div class="card-header">';
  h += '<div><div class="card-name">' + (r.name || r.stockId) + '</div>';
  h += '<div class="card-sub">' + (r.market === 'us' ? '美股' : '台股') + ' · ' + r.stockId + (r.market === 'tw' ? '.TW' : '') + '</div></div>';
  h += '<div class="card-action">';
  h += '<div class="action-badge"><span class="arrow">' + (rec.actionIcon || '→') + '</span> ' + (rec.action || '觀望') + '</div>';
  h += '<div class="dots">' + dots + '</div>';
  h += '</div></div>';

  h += '<div class="card-price ' + (isUp ? 'up' : 'down') + '">$' + fmtPrice(r.price) + ' / ' + fmtPct(changePct) + '</div>';
  h += '<div class="card-change">今日 $' + (r.change >= 0 ? '+' : '') + fmtPrice(r.change || 0) + ' / ' + (changePct >= 0 ? '+' : '') + changePct + '%</div>';

  if (rec.summary) h += '<div class="card-reason">' + rec.summary + '</div>';

  if (topSignal) {
    var sigClass = topSignal.type === 'bullish' ? 'bullish' : 'bearish';
    var sigIcon = topSignal.type === 'bullish' ? '⚡' : '⚡';
    h += '<div class="card-signal ' + sigClass + '"><span class="sig-icon">' + sigIcon + '</span> <b>' + topSignal.name + '</b>：' + topSignal.desc + '</div>';
  }

  if (r.stopLoss) h += '<div class="card-stoploss">停損 $' + fmtPrice(r.stopLoss.stopLoss) + '</div>';
  h += '</div>';
  return h;
}

function renderDots(d) {
  var h = '';
  for (var i = 0; i < (d[0]||0); i++) h += '<div class="dot dot-red"></div>';
  for (var i = 0; i < (d[1]||0); i++) h += '<div class="dot dot-yellow"></div>';
  for (var i = 0; i < (d[2]||0); i++) h += '<div class="dot dot-green"></div>';
  return h;
}

function showDetail(stockId, market) {
  showLoading(true);
  apiGet('analyze', { stock: stockId, market: market }).then(function(res) {
    showLoading(false);
    if (!res.success) { alert(res.msg || '查詢失敗'); return; }
    renderDetail(res.data);
    showTab('detail');
  }).catch(function(err) { showLoading(false); alert('連線失敗'); });
}

function searchStock() {
  var input = document.getElementById('search-input').value.trim().toUpperCase();
  if (!input) return;
  var market = /^[A-Z]{1,5}$/.test(input) ? 'us' : 'tw';
  showDetail(input, market);
}

function renderDetail(d) {
  var rec = d.recommendation || {};
  var tech = d.technical || {};
  var risk = d.risk || {};
  var val = d.valuation || {};
  var chip = d.chip || {};
  var pos = d.position || {};
  var sl = d.stopLoss || {};
  var changePct = d.changePct || 0;
  var isUp = changePct >= 0;

  var h = '<div class="detail-header">';
  h += '<div class="dh-name">' + (d.name || d.stockId) + '</div>';
  h += '<div class="dh-sub">' + (d.market === 'us' ? '美股' : '台股') + ' · ' + d.stockId + ' · AI</div>';
  h += '<div class="dh-price" style="color:' + (isUp ? 'var(--red)' : 'var(--green)') + '">$' + fmtPrice(d.price) + ' / ' + fmtPct(changePct) + '</div>';
  h += '<div class="dh-change" style="color:var(--text2)">今日 $' + (d.change >= 0 ? '+' : '') + fmtPrice(d.change || 0) + ' / ' + (changePct >= 0 ? '+' : '') + changePct + '%</div>';
  h += '</div>';

  // Risk & recommendation
  h += '<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius)">';
  h += '<div style="font-size:24px">' + (rec.actionIcon || '→') + '</div>';
  h += '<div style="flex:1"><div style="font-weight:800;font-size:15px">' + (rec.action || '觀望') + '</div>';
  h += '<div style="font-size:11px;color:var(--text2)">' + (rec.summary || '') + '</div></div>';
  h += '<div class="dots">' + renderDots(risk.dots || [2,1,2]) + '</div>';
  h += '</div>';

  // Indicators grid
  h += '<div class="ind-grid">';
  h += indCard('MA5', fmtPrice(tech.ma5), tech.ma5 && d.price > tech.ma5 ? 'var(--green)' : 'var(--red)');
  h += indCard('MA20', fmtPrice(tech.ma20), tech.ma20 && d.price > tech.ma20 ? 'var(--green)' : 'var(--red)');
  h += indCard('MA60 (季線)', fmtPrice(tech.ma60), tech.ma60 && d.price > tech.ma60 ? 'var(--green)' : 'var(--red)');
  h += indCard('RSI(14)', (tech.rsi14||0).toFixed(1), tech.rsi14 > 70 ? 'var(--red)' : tech.rsi14 < 30 ? 'var(--green)' : 'var(--text)');
  h += indCard('K / D', (tech.k||0).toFixed(1) + ' / ' + (tech.d||0).toFixed(1), '');
  h += indCard('量比', (tech.volumeRatio||1).toFixed(2) + 'x', tech.volumeRatio > 1.3 ? 'var(--orange)' : 'var(--text)');
  h += '</div>';

  // Signals
  if (tech.signals && tech.signals.length) {
    h += '<div class="section-title">信號</div><div class="signal-list">';
    tech.signals.forEach(function(s) {
      h += '<div class="signal-item"><div class="signal-dot ' + s.type + '"></div>';
      h += '<div class="signal-text"><div class="signal-name">' + s.name + '</div>';
      h += '<div class="signal-desc">' + s.desc + '</div></div></div>';
    });
    h += '</div>';
  }

  // Valuation
  if (val.zone && val.zone !== 'unknown') {
    h += '<div class="chart-box">';
    h += '<div class="chart-title">股價光譜 <span style="font-size:11px;color:var(--text2)">PER 分位估值帶</span></div>';
    h += '<div class="val-labels"><span>便宜</span><span>合理</span><span>偏貴</span><span>昂貴</span></div>';
    h += '<div class="val-bar">';
    h += '<div class="val-seg val-cheap" style="width:25%"></div>';
    h += '<div class="val-seg" style="width:25%;background:var(--gold)"></div>';
    h += '<div class="val-seg val-fair" style="width:25%"></div>';
    h += '<div class="val-seg val-expensive" style="width:25%"></div>';
    h += '</div>';
    var pctPos = Math.max(5, Math.min(95, val.percentile || 50));
    h += '<div style="position:relative;height:20px;margin-bottom:8px">';
    h += '<div class="val-marker" style="position:absolute;left:' + pctPos + '%;transform:translateX(-50%)">$' + fmtPrice(d.price) + '</div>';
    h += '</div>';
    h += '<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text3)">';
    h += '<span>$' + fmtPrice(val.perMin * (d.price / (val.per || 1))) + '</span>';
    h += '<span>$' + fmtPrice(val.perP50 * (d.price / (val.per || 1))) + '</span>';
    h += '<span>$' + fmtPrice(val.perMax * (d.price / (val.per || 1))) + '</span>';
    h += '</div>';
    h += '<div style="margin-top:8px;font-size:12px;color:var(--text2)">現價位於近一年 PER 分位估值帶; 最新 PER ' + (val.per||0).toFixed(1) + '。</div>';
    h += '</div>';
  }

  // Chip flow
  if (chip.trend && chip.trend !== 'unknown') {
    h += '<div class="chart-box">';
    h += '<div class="chart-title">籌碼流 <span style="font-size:11px;color:var(--text2)">法人 / 融資</span></div>';
    var maxChip = Math.max(Math.abs(chip.foreignNet||0), Math.abs(chip.trustNet||0), Math.abs(chip.dealerNet||0), 1);
    h += renderChipBar('外資', chip.foreignNet || 0, maxChip);
    h += renderChipBar('投信', chip.trustNet || 0, maxChip);
    h += renderChipBar('自營', chip.dealerNet || 0, maxChip);
    if (chip.marginChange !== undefined) h += renderChipBar('融資', chip.marginChange, maxChip);
    h += '<div style="margin-top:8px;font-size:11px;color:var(--text2)">近 ' + (chip.days||0) + ' 日法人/融資流向; 最新法人淨額 ' + fmtNum(chip.totalNet || 0) + '。</div>';
    if (chip.warning) h += '<div style="margin-top:4px;font-size:11px;color:var(--orange)">⚠️ ' + chip.warning + '</div>';
    h += '</div>';
  }

  // Position sizing
  if (pos.suggestedShares) {
    h += '<div class="chart-box">';
    h += '<div class="chart-title">建議部位</div>';
    h += '<div class="ind-grid" style="margin-bottom:0">';
    h += indCard('建議張數', pos.suggestedLots + ' 張', 'var(--gold)');
    h += indCard('投入金額', '$' + fmtNum(pos.investAmount), '');
    h += indCard('停損價', '$' + fmtPrice(sl.stopLoss), 'var(--red)');
    h += indCard('風險金額', '$' + fmtNum(pos.riskAmount), 'var(--orange)');
    h += '</div></div>';
  }

  document.getElementById('detail-content').innerHTML = h;
}

function renderChipBar(label, value, max) {
  var pct = max > 0 ? Math.min(Math.abs(value) / max * 100, 100) : 0;
  var cls = value >= 0 ? 'pos' : 'neg';
  var color = value >= 0 ? 'var(--red)' : 'var(--green)';
  return '<div class="chip-bar"><span class="chip-label">' + label + '</span>' +
    '<div class="chip-track"><div class="chip-fill ' + cls + '" style="width:' + pct + '%"></div></div>' +
    '<span class="chip-value" style="color:' + color + '">' + fmtNum(value) + '</span></div>';
}

function indCard(label, value, color) {
  var style = color ? ' style="color:' + color + '"' : '';
  return '<div class="ind-card"><div class="ind-label">' + label + '</div><div class="ind-value"' + style + '>' + value + '</div></div>';
}

// Watchlist
function loadWatchlist() {
  apiGet('watchlist').then(function(res) {
    var list = (res.data || []);
    if (!list.length) {
      document.getElementById('watchlist-content').innerHTML = '<div class="empty"><p>尚未加入任何股票</p></div>';
      return;
    }
    var h = '';
    list.forEach(function(item) {
      h += '<div class="stock-card" style="display:flex;align-items:center;gap:12px;cursor:default">';
      h += '<div style="flex:1"><div class="card-name">' + item.stockId + '</div>';
      h += '<div class="card-sub">' + (item.market === 'us' ? '美股' : '台股') + (item.entryPrice ? ' · 成本 $' + item.entryPrice : '') + '</div></div>';
      h += '<button class="remove-btn" onclick="removeWatchlist(\'' + item.stockId + '\')">✕</button>';
      h += '</div>';
    });
    document.getElementById('watchlist-content').innerHTML = h;
  });
}

function addWatchlist() {
  var stock = document.getElementById('wl-stock').value.trim().toUpperCase();
  var market = document.getElementById('wl-market').value;
  var entry = document.getElementById('wl-entry').value || 0;
  if (!stock) return;
  apiGet('watchlist-add', { stock: stock, market: market, entryPrice: entry }).then(function() {
    document.getElementById('wl-stock').value = '';
    document.getElementById('wl-entry').value = '';
    loadWatchlist();
  });
}

function removeWatchlist(stock) {
  apiGet('watchlist-remove', { stock: stock }).then(function() { loadWatchlist(); });
}

// Alerts
function loadAlerts() {
  apiGet('alert-history').then(function(res) {
    var list = (res.data || []).reverse();
    if (!list.length) {
      document.getElementById('alerts-content').innerHTML = '<div class="empty"><p>尚無提醒紀錄</p></div>';
      return;
    }
    var h = '';
    list.forEach(function(a) {
      var icon = a.type === 'bullish' ? '🟢' : a.type === 'bearish' ? '🔴' : '🟡';
      h += '<div class="signal-item"><div style="font-size:16px">' + icon + '</div>';
      h += '<div class="signal-text"><div class="signal-name">' + a.stockId + ' - ' + a.message + '</div>';
      h += '<div class="signal-desc">' + a.time + '</div></div></div>';
    });
    document.getElementById('alerts-content').innerHTML = h;
  });
}

// Helpers
function fmtPrice(n) { n = parseFloat(n) || 0; return n >= 100 ? n.toLocaleString('en-US', {minimumFractionDigits:0,maximumFractionDigits:0}) : n.toFixed(2); }
function fmtPct(n) { return (n >= 0 ? '+' : '') + n.toFixed(2) + '%'; }
function fmtNum(n) {
  n = parseInt(n) || 0;
  if (Math.abs(n) >= 100000000) return (n / 100000000).toFixed(1) + '億';
  if (Math.abs(n) >= 10000) return (n / 10000).toFixed(1) + '萬';
  return n.toLocaleString();
}

// Init
scanAll();
</script>
</body>
</html>
