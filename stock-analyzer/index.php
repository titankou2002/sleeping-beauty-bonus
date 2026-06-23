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

/* Search dropdown */
.search-dropdown { position: absolute; top: 40px; left: 0; right: 0; background: var(--surface); border: 1px solid var(--border2); border-radius: 8px; max-height: 280px; overflow-y: auto; z-index: 200; display: none; box-shadow: 0 8px 24px rgba(0,0,0,0.5); }
.search-dropdown.show { display: block; }
.search-item { padding: 10px 12px; cursor: pointer; border-bottom: 1px solid var(--border); font-size: 13px; display: flex; align-items: center; gap: 8px; }
.search-item:last-child { border-bottom: none; }
.search-item:hover, .search-item:active { background: rgba(194,157,102,0.1); }
.search-item .si-code { font-weight: 800; color: var(--gold); min-width: 48px; }
.search-item .si-name { color: var(--text); }

/* KD prominent display */
.kd-display { display: flex; gap: 32px; justify-content: center; align-items: center; padding: 16px 0; }
.kd-item { text-align: center; }
.kd-label { font-size: 12px; color: var(--text3); font-weight: 700; letter-spacing: 1px; margin-bottom: 6px; }
.kd-value { font-size: 42px; font-weight: 900; line-height: 1; }
.kd-bar { width: 100px; height: 6px; background: var(--bg2); border-radius: 3px; margin: 8px auto 0; overflow: hidden; }
.kd-bar-fill { height: 100%; border-radius: 3px; transition: width 0.4s; }
.kd-zone { font-size: 12px; color: var(--text2); text-align: center; margin-top: 12px; padding: 4px 16px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 16px; display: inline-block; }

@media (min-width: 600px) {
  .app { border-left: 1px solid var(--border); border-right: 1px solid var(--border); }
}
</style>
</head>
<body>
<div class="app">
  <div class="topbar">
    <h1>股票戰情室</h1>
    <div class="search-box" style="position:relative">
      <input type="text" id="search-input" placeholder="代號或公司名" oninput="onSearchInput()" onkeydown="if(event.key==='Enter'){searchStock();return;}">
      <button onclick="searchStock()">查詢</button>
      <div class="search-dropdown" id="search-dropdown"></div>
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
      <span class="tab-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><span>總覽</span>
    </div>
    <div class="tab-item" id="tab-watchlist" onclick="showTab('watchlist')">
      <span class="tab-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span><span>監控</span>
    </div>
    <div class="tab-item" id="tab-alerts" onclick="showTab('alerts')">
      <span class="tab-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span><span>提醒</span>
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
  return fetch(url).then(function(r) {
    return r.text().then(function(t) {
      try { return JSON.parse(t); }
      catch(e) { return { success: false, msg: 'API 回傳格式錯誤' }; }
    });
  });
}

function apiPost(action, body) {
  return fetch(API + '?action=' + action, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  }).then(function(r) {
    return r.text().then(function(t) {
      try { return JSON.parse(t); }
      catch(e) { return { success: false, msg: 'API 回傳格式錯誤' }; }
    });
  });
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
    var sigIcon = topSignal.type === 'bullish' ? svgIcon('zap','var(--green)') : svgIcon('zap','var(--red)');
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

var searchTimer = null;
function onSearchInput() {
  var val = document.getElementById('search-input').value.trim();
  if (val.length < 1) { hideDropdown(); return; }
  clearTimeout(searchTimer);
  searchTimer = setTimeout(function() {
    apiGet('search', { q: val }).then(function(res) {
      if (!res.success || !res.data || !res.data.length) { hideDropdown(); return; }
      showSearchDropdown(res.data);
    }).catch(function() { hideDropdown(); });
  }, 300);
}

function showSearchDropdown(items) {
  var dd = document.getElementById('search-dropdown');
  var h = '';
  items.forEach(function(item) {
    h += '<div class="search-item" onclick="selectSearchResult(\'' + item.code + '\')">';
    h += '<span class="si-code">' + item.code + '</span>';
    h += '<span class="si-name">' + item.name + '</span>';
    h += '</div>';
  });
  dd.innerHTML = h;
  dd.classList.add('show');
}

function hideDropdown() {
  document.getElementById('search-dropdown').classList.remove('show');
}

function selectSearchResult(code) {
  document.getElementById('search-input').value = code;
  hideDropdown();
  showDetail(code, 'tw');
}

function searchStock() {
  var input = document.getElementById('search-input').value.trim();
  if (!input) return;
  hideDropdown();
  if (/^\d{4,6}$/.test(input)) { showDetail(input, 'tw'); return; }
  var upper = input.toUpperCase();
  if (/^[A-Z]{1,5}$/.test(upper)) { showDetail(upper, 'us'); return; }
  showLoading(true);
  apiGet('search', { q: input }).then(function(res) {
    showLoading(false);
    if (res.success && res.data && res.data.length) {
      showDetail(res.data[0].code, 'tw');
    } else {
      alert('查無「' + input + '」相關股票');
    }
  }).catch(function() { showLoading(false); alert('搜尋失敗'); });
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

  // K-line chart
  if (d.quotes && d.quotes.length > 5) {
    h += '<div class="chart-box">';
    h += '<div class="chart-title">' + svgIcon('bar-chart','var(--gold)',14) + ' K線圖 <span style="font-size:11px;color:var(--text2);font-weight:400">MA5 / MA20 / MA60</span></div>';
    h += '<canvas id="kline-canvas" style="width:100%;height:260px"></canvas>';
    h += '</div>';
  }

  // KD prominent display
  var kVal = tech.k || 0;
  var dVal = tech.d || 0;
  var kColor = kVal > 80 ? 'var(--red)' : kVal < 20 ? 'var(--green)' : 'var(--gold)';
  var dColor = dVal > 80 ? 'var(--red)' : dVal < 20 ? 'var(--green)' : 'var(--blue)';
  var kdZone = '';
  if (kVal > 80 && dVal > 80) kdZone = '超買區 - 注意回檔壓力';
  else if (kVal < 20 && dVal < 20) kdZone = '超賣區 - 留意反彈機會';
  else if (kVal > dVal) kdZone = 'K > D 短線偏多';
  else kdZone = 'K < D 短線偏空';

  h += '<div class="chart-box">';
  h += '<div class="chart-title">' + svgIcon('target','var(--gold)',14) + ' KD 指標 (9日)</div>';
  h += '<div class="kd-display">';
  h += '<div class="kd-item"><div class="kd-label">K</div>';
  h += '<div class="kd-value" style="color:' + kColor + '">' + kVal.toFixed(1) + '</div>';
  h += '<div class="kd-bar"><div class="kd-bar-fill" style="width:' + kVal + '%;background:' + kColor + '"></div></div></div>';
  h += '<div class="kd-item"><div class="kd-label">D</div>';
  h += '<div class="kd-value" style="color:' + dColor + '">' + dVal.toFixed(1) + '</div>';
  h += '<div class="kd-bar"><div class="kd-bar-fill" style="width:' + dVal + '%;background:' + dColor + '"></div></div></div>';
  h += '</div>';
  h += '<div style="text-align:center"><span class="kd-zone">' + kdZone + '</span></div>';
  if (tech.kdSeries) {
    h += '<canvas id="kd-chart-canvas" style="width:100%;height:120px;margin-top:12px"></canvas>';
  }
  h += '</div>';

  // Indicators grid (without KD, replaced by MACD)
  h += '<div class="ind-grid">';
  h += indCard('MA5', fmtPrice(tech.ma5), tech.ma5 && d.price > tech.ma5 ? 'var(--green)' : 'var(--red)');
  h += indCard('MA20', fmtPrice(tech.ma20), tech.ma20 && d.price > tech.ma20 ? 'var(--green)' : 'var(--red)');
  h += indCard('MA60 (季線)', fmtPrice(tech.ma60), tech.ma60 && d.price > tech.ma60 ? 'var(--green)' : 'var(--red)');
  h += indCard('RSI(14)', (tech.rsi14||0).toFixed(1), tech.rsi14 > 70 ? 'var(--red)' : tech.rsi14 < 30 ? 'var(--green)' : 'var(--text)');
  h += indCard('MACD', (tech.macdHist||0).toFixed(2), tech.macdHist > 0 ? 'var(--red)' : 'var(--green)');
  h += indCard('量比', (tech.volumeRatio||1).toFixed(2) + 'x', tech.volumeRatio > 1.3 ? 'var(--orange)' : 'var(--text)');
  h += '</div>';

  // Signals
  if (tech.signals && tech.signals.length) {
    h += '<div class="section-title">' + svgIcon('zap','var(--gold)',14) + ' 信號</div><div class="signal-list">';
    tech.signals.forEach(function(s) {
      h += '<div class="signal-item"><div class="signal-dot ' + s.type + '"></div>';
      h += '<div class="signal-text"><div class="signal-name">' + s.name + '</div>';
      h += '<div class="signal-desc">' + s.desc + '</div></div></div>';
    });
    h += '</div>';
  }

  // PE River Chart
  var hasPeChart = d.peHistory && d.peHistory.length > 5 && d.quotes && d.quotes.length > 5 && val.zone && val.zone !== 'unknown' && val.perP25;
  if (hasPeChart) {
    h += '<div class="chart-box">';
    h += '<div class="chart-title">' + svgIcon('trending-up','var(--gold)',14) + ' PE 估值河流 <span style="font-size:11px;color:var(--text2);font-weight:400">' + val.label + ' · PER ' + (val.per||0).toFixed(1) + '</span></div>';
    h += '<canvas id="pe-river-canvas" style="width:100%;height:220px"></canvas>';
    h += '<div style="display:flex;gap:12px;justify-content:center;margin-top:8px;font-size:10px">';
    h += '<span style="color:rgba(34,197,94,0.8)">■ 便宜 (P25)</span>';
    h += '<span style="color:rgba(194,157,102,0.8)">■ 合理 (P50)</span>';
    h += '<span style="color:rgba(249,115,22,0.8)">■ 偏貴 (P75)</span>';
    h += '</div>';
    h += '</div>';
  }

  // Valuation bar (simpler fallback if no PE river data)
  if (val.zone && val.zone !== 'unknown' && !hasPeChart) {
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
    h += '<div style="margin-top:8px;font-size:12px;color:var(--text2)">最新 PER ' + (val.per||0).toFixed(1) + ' · ' + val.label + '</div>';
    h += '</div>';
  }

  // Chip flow
  if (chip.trend && chip.trend !== 'unknown') {
    h += '<div class="chart-box">';
    h += '<div class="chart-title">' + svgIcon('shield','var(--gold)',14) + ' 籌碼流 <span style="font-size:11px;color:var(--text2)">法人 / 融資</span></div>';
    var maxChip = Math.max(Math.abs(chip.foreignNet||0), Math.abs(chip.trustNet||0), Math.abs(chip.dealerNet||0), 1);
    h += renderChipBar('外資', chip.foreignNet || 0, maxChip);
    h += renderChipBar('投信', chip.trustNet || 0, maxChip);
    h += renderChipBar('自營', chip.dealerNet || 0, maxChip);
    if (chip.marginChange !== undefined) h += renderChipBar('融資', chip.marginChange, maxChip);
    h += '<div style="margin-top:8px;font-size:11px;color:var(--text2)">近 ' + (chip.days||0) + ' 日法人/融資流向; 最新法人淨額 ' + fmtNum(chip.totalNet || 0) + '。</div>';
    if (chip.warning) h += '<div style="margin-top:4px;font-size:11px;color:var(--orange);display:flex;align-items:center;gap:4px">' + svgIcon('alert-triangle','var(--orange)',14) + ' ' + chip.warning + '</div>';
    h += '</div>';
  }

  // Position sizing
  if (pos.suggestedShares) {
    h += '<div class="chart-box">';
    h += '<div class="chart-title">' + svgIcon('target','var(--gold)',14) + ' 建議部位</div>';
    h += '<div class="ind-grid" style="margin-bottom:0">';
    h += indCard('建議張數', pos.suggestedLots + ' 張', 'var(--gold)');
    h += indCard('投入金額', '$' + fmtNum(pos.investAmount), '');
    h += indCard('停損價', '$' + fmtPrice(sl.stopLoss), 'var(--red)');
    h += indCard('風險金額', '$' + fmtNum(pos.riskAmount), 'var(--orange)');
    h += '</div></div>';
  }

  document.getElementById('detail-content').innerHTML = h;

  // Draw charts after DOM is ready
  setTimeout(function() {
    if (d.quotes && d.quotes.length > 5) {
      drawKlineChart(document.getElementById('kline-canvas'), d.quotes, tech.maSeries || {});
    }
    if (tech.kdSeries) {
      drawKDChart(document.getElementById('kd-chart-canvas'), tech.kdSeries);
    }
    if (hasPeChart) {
      drawPERiverChart(document.getElementById('pe-river-canvas'), d.quotes, d.peHistory, val);
    }
  }, 50);
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
      var icon = a.type === 'bullish' ? svgIcon('circle','var(--green)',14) : a.type === 'bearish' ? svgIcon('circle','var(--red)',14) : svgIcon('circle','var(--orange)',14);
      h += '<div class="signal-item"><div style="font-size:16px">' + icon + '</div>';
      h += '<div class="signal-text"><div class="signal-name">' + a.stockId + ' - ' + a.message + '</div>';
      h += '<div class="signal-desc">' + a.time + '</div></div></div>';
    });
    document.getElementById('alerts-content').innerHTML = h;
  });
}

// SVG Icons
function svgIcon(name, color, size) {
  size = size || 16;
  color = color || 'currentColor';
  var icons = {
    'zap': '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" fill="none" stroke="'+color+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    'alert-triangle': '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" fill="none" stroke="'+color+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="9" x2="12" y2="13" stroke="'+color+'" stroke-width="2"/><line x1="12" y1="17" x2="12.01" y2="17" stroke="'+color+'" stroke-width="2"/>',
    'circle': '<circle cx="12" cy="12" r="10" fill="'+color+'" stroke="none"/>',
    'trending-up': '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18" fill="none" stroke="'+color+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="17 6 23 6 23 12" fill="none" stroke="'+color+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    'trending-down': '<polyline points="23 18 13.5 8.5 8.5 13.5 1 6" fill="none" stroke="'+color+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="17 18 23 18 23 12" fill="none" stroke="'+color+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    'bar-chart': '<line x1="18" y1="20" x2="18" y2="10" stroke="'+color+'" stroke-width="2" stroke-linecap="round"/><line x1="12" y1="20" x2="12" y2="4" stroke="'+color+'" stroke-width="2" stroke-linecap="round"/><line x1="6" y1="20" x2="6" y2="14" stroke="'+color+'" stroke-width="2" stroke-linecap="round"/>',
    'target': '<circle cx="12" cy="12" r="10" fill="none" stroke="'+color+'" stroke-width="2"/><circle cx="12" cy="12" r="6" fill="none" stroke="'+color+'" stroke-width="2"/><circle cx="12" cy="12" r="2" fill="none" stroke="'+color+'" stroke-width="2"/>',
    'shield': '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="none" stroke="'+color+'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
  };
  return '<svg width="'+size+'" height="'+size+'" viewBox="0 0 24 24" style="display:inline-block;vertical-align:middle">' + (icons[name] || '') + '</svg>';
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

// Chart: K-line candlestick
function drawKlineChart(canvas, quotes, maSeries) {
  if (!canvas || !quotes || quotes.length < 2) return;
  var ctx = canvas.getContext('2d');
  var dpr = window.devicePixelRatio || 1;
  var w = canvas.offsetWidth;
  var h = canvas.offsetHeight;
  canvas.width = w * dpr;
  canvas.height = h * dpr;
  ctx.scale(dpr, dpr);

  var n = quotes.length;
  var pad = { top: 10, right: 8, bottom: 22, left: 48 };
  var chartH = (h - pad.top - pad.bottom) * 0.72;
  var volH = (h - pad.top - pad.bottom) * 0.2;
  var volTop = pad.top + chartH + (h - pad.top - pad.bottom) * 0.08;

  var minP = Infinity, maxP = -Infinity, maxVol = 0;
  for (var i = 0; i < n; i++) {
    if (quotes[i].low < minP) minP = quotes[i].low;
    if (quotes[i].high > maxP) maxP = quotes[i].high;
    if (quotes[i].volume > maxVol) maxVol = quotes[i].volume;
  }
  var priceRange = maxP - minP;
  if (priceRange <= 0) priceRange = 1;
  var cw = w - pad.left - pad.right;
  var gap = cw / n;
  var candleW = Math.max(1, gap * 0.6);

  function toX(i) { return pad.left + i * gap + gap / 2; }
  function toPY(p) { return pad.top + (1 - (p - minP) / priceRange) * chartH; }

  // Grid
  ctx.strokeStyle = 'rgba(255,255,255,0.05)';
  ctx.lineWidth = 0.5;
  for (var g = 0; g <= 4; g++) {
    var gy = pad.top + (chartH * g / 4);
    ctx.beginPath(); ctx.moveTo(pad.left, gy); ctx.lineTo(w - pad.right, gy); ctx.stroke();
  }

  // Volume bars + candlesticks
  for (var i = 0; i < n; i++) {
    var q = quotes[i];
    var x = toX(i);
    var isUp = q.close >= q.open;
    var color = isUp ? '#ef4444' : '#22c55e';

    // Volume
    var vH = maxVol > 0 ? (q.volume / maxVol) * volH : 0;
    ctx.fillStyle = isUp ? 'rgba(239,68,68,0.25)' : 'rgba(34,197,94,0.25)';
    ctx.fillRect(x - candleW / 2, volTop + volH - vH, candleW, vH);

    // Wick
    ctx.beginPath();
    ctx.strokeStyle = color;
    ctx.lineWidth = 1;
    ctx.moveTo(x, toPY(q.high));
    ctx.lineTo(x, toPY(q.low));
    ctx.stroke();

    // Body
    var yO = toPY(q.open), yC = toPY(q.close);
    var bTop = Math.min(yO, yC);
    var bH = Math.max(Math.abs(yO - yC), 1);
    ctx.fillStyle = color;
    ctx.fillRect(x - candleW / 2, bTop, candleW, bH);
  }

  // MA lines
  var maColors = { ma5: '#f97316', ma20: '#3b82f6', ma60: '#a855f7' };
  var maKeys = ['ma5', 'ma20', 'ma60'];
  for (var m = 0; m < maKeys.length; m++) {
    var key = maKeys[m];
    var series = (maSeries || {})[key];
    if (!series) continue;
    var offset = n - series.length;
    ctx.beginPath();
    ctx.strokeStyle = maColors[key];
    ctx.lineWidth = 1.5;
    var started = false;
    for (var i = 0; i < series.length; i++) {
      if (series[i] === null || series[i] === undefined) continue;
      var sx = toX(i + offset);
      var sy = toPY(series[i]);
      if (!started) { ctx.moveTo(sx, sy); started = true; }
      else ctx.lineTo(sx, sy);
    }
    ctx.stroke();
  }

  // Y-axis labels
  ctx.font = '10px -apple-system, sans-serif';
  ctx.fillStyle = 'rgba(255,255,255,0.3)';
  ctx.textAlign = 'right';
  for (var g = 0; g <= 4; g++) {
    var pv = minP + (priceRange * (4 - g) / 4);
    ctx.fillText(fmtPrice(pv), pad.left - 4, pad.top + (chartH * g / 4) + 3);
  }

  // X-axis dates
  ctx.textAlign = 'center';
  var dateStep = Math.max(1, Math.floor(n / 5));
  for (var i = 0; i < n; i += dateStep) {
    ctx.fillText(quotes[i].date.substr(5), toX(i), h - 4);
  }

  // MA legend
  ctx.textAlign = 'left';
  ctx.font = 'bold 9px -apple-system, sans-serif';
  ctx.fillStyle = '#f97316'; ctx.fillText('MA5', pad.left + 4, pad.top + 10);
  ctx.fillStyle = '#3b82f6'; ctx.fillText('MA20', pad.left + 32, pad.top + 10);
  ctx.fillStyle = '#a855f7'; ctx.fillText('MA60', pad.left + 66, pad.top + 10);
}

// Chart: KD line chart
function drawKDChart(canvas, kdSeries) {
  if (!canvas || !kdSeries) return;
  var ctx = canvas.getContext('2d');
  var dpr = window.devicePixelRatio || 1;
  var w = canvas.offsetWidth;
  var h = canvas.offsetHeight;
  canvas.width = w * dpr;
  canvas.height = h * dpr;
  ctx.scale(dpr, dpr);

  var kData = kdSeries.k || [];
  var dData = kdSeries.d || [];
  var n = kData.length;
  if (n < 2) return;

  var pad = { top: 6, right: 8, bottom: 16, left: 28 };
  var cw = w - pad.left - pad.right;
  var ch = h - pad.top - pad.bottom;

  function toX(i) { return pad.left + (i / (n - 1)) * cw; }
  function toY(v) { return pad.top + (1 - (v || 0) / 100) * ch; }

  // Zone backgrounds
  ctx.fillStyle = 'rgba(239,68,68,0.06)';
  ctx.fillRect(pad.left, toY(100), cw, toY(80) - toY(100));
  ctx.fillStyle = 'rgba(34,197,94,0.06)';
  ctx.fillRect(pad.left, toY(20), cw, toY(0) - toY(20));

  // Reference lines
  var refs = [20, 50, 80];
  for (var r = 0; r < refs.length; r++) {
    ctx.beginPath();
    ctx.strokeStyle = 'rgba(255,255,255,0.08)';
    ctx.lineWidth = 0.5;
    ctx.setLineDash([3, 3]);
    ctx.moveTo(pad.left, toY(refs[r]));
    ctx.lineTo(w - pad.right, toY(refs[r]));
    ctx.stroke();
    ctx.setLineDash([]);
  }

  // K line
  ctx.beginPath();
  ctx.strokeStyle = '#f97316';
  ctx.lineWidth = 2;
  var started = false;
  for (var i = 0; i < n; i++) {
    if (kData[i] === null || kData[i] === undefined) continue;
    if (!started) { ctx.moveTo(toX(i), toY(kData[i])); started = true; }
    else ctx.lineTo(toX(i), toY(kData[i]));
  }
  ctx.stroke();

  // D line
  ctx.beginPath();
  ctx.strokeStyle = '#a855f7';
  ctx.lineWidth = 1.5;
  started = false;
  for (var i = 0; i < n; i++) {
    if (dData[i] === null || dData[i] === undefined) continue;
    if (!started) { ctx.moveTo(toX(i), toY(dData[i])); started = true; }
    else ctx.lineTo(toX(i), toY(dData[i]));
  }
  ctx.stroke();

  // Y labels
  ctx.font = '9px -apple-system, sans-serif';
  ctx.textAlign = 'right';
  ctx.fillStyle = 'rgba(255,255,255,0.25)';
  var yLabels = [0, 20, 50, 80, 100];
  for (var r = 0; r < yLabels.length; r++) {
    ctx.fillText(yLabels[r], pad.left - 3, toY(yLabels[r]) + 3);
  }

  // Legend
  ctx.textAlign = 'left';
  ctx.font = 'bold 9px -apple-system, sans-serif';
  ctx.fillStyle = '#f97316'; ctx.fillText('K', w - pad.right - 30, pad.top + 10);
  ctx.fillStyle = '#a855f7'; ctx.fillText('D', w - pad.right - 14, pad.top + 10);
}

// Chart: PE River
function drawPERiverChart(canvas, quotes, peHistory, val) {
  if (!canvas || !quotes || !peHistory || !val) return;
  var ctx = canvas.getContext('2d');
  var dpr = window.devicePixelRatio || 1;
  var w = canvas.offsetWidth;
  var h = canvas.offsetHeight;
  canvas.width = w * dpr;
  canvas.height = h * dpr;
  ctx.scale(dpr, dpr);

  // Build PE date map
  var peMap = {};
  for (var i = 0; i < peHistory.length; i++) {
    if (peHistory[i].per > 0) peMap[peHistory[i].date] = peHistory[i];
  }

  // Build data points with PE-derived bands
  var points = [];
  var lastEps = null;
  for (var i = 0; i < quotes.length; i++) {
    var q = quotes[i];
    var pe = peMap[q.date];
    if (pe && pe.per > 0) lastEps = q.close / pe.per;
    if (lastEps && lastEps > 0 && val.perP25 > 0) {
      points.push({
        date: q.date,
        price: q.close,
        bMin: lastEps * (val.perMin || val.perP25 * 0.7),
        bP25: lastEps * val.perP25,
        bP50: lastEps * val.perP50,
        bP75: lastEps * val.perP75,
        bMax: lastEps * (val.perMax || val.perP75 * 1.3)
      });
    }
  }
  if (points.length < 3) return;

  // Price range
  var allP = [];
  for (var i = 0; i < points.length; i++) {
    allP.push(points[i].price, points[i].bMin, points[i].bMax);
  }
  var minP = Math.min.apply(null, allP) * 0.97;
  var maxP = Math.max.apply(null, allP) * 1.03;
  var range = maxP - minP;
  if (range <= 0) range = 1;

  var pad = { top: 8, right: 8, bottom: 22, left: 48 };
  var cw = w - pad.left - pad.right;
  var ch = h - pad.top - pad.bottom;

  function toX(i) { return pad.left + (i / (points.length - 1)) * cw; }
  function toY(p) { return pad.top + (1 - (p - minP) / range) * ch; }

  // Draw filled bands
  function drawBandFill(keyBot, keyTop, color) {
    ctx.beginPath();
    for (var i = 0; i < points.length; i++) {
      var x = toX(i), y = toY(points[i][keyTop]);
      if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
    }
    for (var i = points.length - 1; i >= 0; i--) {
      ctx.lineTo(toX(i), toY(points[i][keyBot]));
    }
    ctx.closePath();
    ctx.fillStyle = color;
    ctx.fill();
  }

  drawBandFill('bMin', 'bP25', 'rgba(34,197,94,0.15)');
  drawBandFill('bP25', 'bP50', 'rgba(194,157,102,0.12)');
  drawBandFill('bP50', 'bP75', 'rgba(249,115,22,0.10)');
  drawBandFill('bP75', 'bMax', 'rgba(239,68,68,0.08)');

  // Band border lines
  function drawBandLine(key, color) {
    ctx.beginPath();
    ctx.strokeStyle = color;
    ctx.lineWidth = 0.8;
    for (var i = 0; i < points.length; i++) {
      var x = toX(i), y = toY(points[i][key]);
      if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
    }
    ctx.stroke();
  }
  drawBandLine('bP25', 'rgba(34,197,94,0.4)');
  drawBandLine('bP50', 'rgba(194,157,102,0.4)');
  drawBandLine('bP75', 'rgba(249,115,22,0.4)');

  // Price line
  ctx.beginPath();
  ctx.strokeStyle = '#fff';
  ctx.lineWidth = 2;
  for (var i = 0; i < points.length; i++) {
    var x = toX(i), y = toY(points[i].price);
    if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
  }
  ctx.stroke();

  // Current price dot
  var last = points[points.length - 1];
  ctx.beginPath();
  ctx.arc(toX(points.length - 1), toY(last.price), 4, 0, Math.PI * 2);
  ctx.fillStyle = '#c29d66';
  ctx.fill();
  ctx.beginPath();
  ctx.arc(toX(points.length - 1), toY(last.price), 6, 0, Math.PI * 2);
  ctx.strokeStyle = 'rgba(194,157,102,0.4)';
  ctx.lineWidth = 1;
  ctx.stroke();

  // Y-axis labels
  ctx.font = '10px -apple-system, sans-serif';
  ctx.fillStyle = 'rgba(255,255,255,0.3)';
  ctx.textAlign = 'right';
  for (var g = 0; g <= 4; g++) {
    var pv = minP + (range * (4 - g) / 4);
    ctx.fillText('$' + Math.round(pv), pad.left - 4, pad.top + (ch * g / 4) + 3);
  }

  // X-axis dates
  ctx.textAlign = 'center';
  var dateStep = Math.max(1, Math.floor(points.length / 5));
  for (var i = 0; i < points.length; i += dateStep) {
    ctx.fillText(points[i].date.substr(5), toX(i), h - 4);
  }
}

// Close search dropdown on outside click
document.addEventListener('click', function(e) {
  if (!e.target.closest('.search-box')) hideDropdown();
});

// Init
scanAll();
</script>
</body>
</html>
