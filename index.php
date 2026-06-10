<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>睡美人戰情室</title>
  <link rel="icon" href="favicon.png" type="image/png">
  <style>
:root {
  --bg: #000000;
  --surface: #0a0a0a;
  --surface2: rgba(255,255,255,0.06);
  --border: rgba(255,255,255,0.12);
  --text: #ffffff;
  --text2: rgba(255,255,255,0.5);
  --gold: #c29d66;
  --gold-soft: rgba(194,157,102,0.12);
  --gold-glow: rgba(194,157,102,0.25);
  --green: #22c55e;
  --red: #ef4444;
  --orange: #f97316;
  --blue: #3b82f6;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans TC", "Microsoft JhengHei", sans-serif;
  min-height: 100vh;
}
.app { display: flex; flex-direction: column; min-height: 100vh; }
.topbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(0,0,0,0.85);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(194,157,102,0.3);
}
.topbar-inner {
  max-width: 1200px; margin: 0 auto;
  padding: 0 20px;
  height: 56px;
  display: flex; align-items: center; gap: 24px;
}
.top-logo { height: 28px; width: auto; flex-shrink: 0; }
.logo {
  font-size: 16px; font-weight: 900; letter-spacing: 2px;
  color: var(--gold);
  text-shadow: 0 0 12px var(--gold-glow);
  white-space: nowrap; flex-shrink: 0;
}
.tab-bar { display: flex; gap: 2px; }
.tab-btn {
  height: 36px; padding: 0 20px; border-radius: 8px;
  font-size: 13px; font-weight: 700; letter-spacing: 0.5px;
  cursor: pointer; border: 1px solid transparent;
  background: transparent; color: var(--text2);
  transition: all 0.2s;
}
.tab-btn:hover { color: var(--text); }
.tab-btn.active {
  background: var(--gold-soft);
  border-color: rgba(194,157,102,0.4);
  color: var(--gold);
}
.ctrl-bar {
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--border);
  padding: 10px 20px;
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.ctrl-bar.hidden { display: none; }
select, .btn {
  height: 34px; border-radius: 8px;
  font-size: 13px; font-weight: 700; outline: none; cursor: pointer;
}
select {
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.15);
  color: var(--text); padding: 0 28px 0 10px; min-width: 80px;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 8px center;
}
.btn {
  padding: 0 16px; border: none; transition: all 0.2s;
}
.btn:hover { filter: brightness(1.15); }
.btn:active { transform: scale(0.97); }
.btn-primary { background: var(--gold); color: #000; }
.btn-accent  { background: rgba(194,157,102,0.2); border: 1px solid rgba(194,157,102,0.4); color: var(--gold); }
.btn-ghost   { background: transparent; border: 1px solid var(--border); color: var(--text2); }
.loading {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.8); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  gap: 12px; z-index: 999; font-size: 15px; font-weight: 700; color: var(--gold);
}
.loading.hidden { display: none; }
.spinner {
  width: 22px; height: 22px;
  border: 2px solid rgba(194,157,102,0.2); border-top-color: var(--gold);
  border-radius: 50%; animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.main {
  max-width: 1200px; margin: 0 auto;
  padding: 24px 20px 80px; width: 100%;
}
.welcome { text-align: center; padding: 80px 20px; }
.welcome h2 { font-size: 22px; margin-bottom: 12px; color: var(--gold); }
.welcome p { color: var(--text2); line-height: 1.6; }
.kpi-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px; margin-bottom: 24px;
}
.kpi-card {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 16px; padding: 18px; text-align: center;
}
.kpi-card .label { font-size: 10px; color: var(--text2); font-weight: 800; letter-spacing: 1px; margin-bottom: 6px; text-transform: uppercase; }
.kpi-card .value { font-size: 24px; font-weight: 900; }
.kpi-card .sub   { font-size: 11px; color: var(--text2); margin-top: 4px; }
.kpi-gold   .value { color: var(--gold); }
.kpi-green  .value { color: var(--green); }
.kpi-blue   .value { color: var(--blue); }
.kpi-purple .value { color: #a855f7; }
.person-section {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 16px; overflow: hidden; margin-bottom: 16px;
  transition: border-color 0.2s;
}
.person-section:hover { border-color: rgba(194,157,102,0.3); }
.person-section-header {
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  padding: 14px 20px;
  border-bottom: 1px solid var(--border);
  background: rgba(194,157,102,0.05);
}
.person-name { font-size: 16px; font-weight: 900; letter-spacing: 1px; flex: 1; }
.person-perf { font-size: 13px; color: var(--text2); }
.person-perf strong { color: var(--gold); font-size: 18px; font-weight: 900; }
.table-wrap { overflow-x: auto; }
.detail-table {
  width: 100%; border-collapse: collapse; font-size: 13px;
}
.detail-table th {
  text-align: left; padding: 10px 12px;
  color: var(--gold); font-weight: 800; font-size: 11px;
  letter-spacing: 0.5px; text-transform: uppercase;
  border-bottom: 1px solid var(--border);
  white-space: nowrap; background: rgba(0,0,0,0.3);
}
.detail-table td {
  padding: 10px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  white-space: nowrap;
}
.detail-table tr:last-child td { border-bottom: none; }
.detail-table tr:hover td { background: rgba(194,157,102,0.04); }
.text-right  { text-align: right; }
.text-center { text-align: center; }
.text-gold   { color: var(--gold); }
.text-green  { color: var(--green); }
.text-red    { color: var(--red); }
.text-purple { color: #a78bfa; }
.product-list { display: flex; flex-direction: column; gap: 10px; }
.product-card {
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 16px 20px;
  display: grid;
  grid-template-columns: 80px 1fr auto;
  gap: 0 16px; align-items: start;
  transition: border-color 0.2s;
}
.product-card:hover { border-color: rgba(194,157,102,0.3); }
.prod-grade { text-align: center; padding: 4px 0; }
.grade-badge {
  display: inline-block; font-size: 16px; font-weight: 900;
  padding: 6px 12px; border-radius: 10px; letter-spacing: 1px;
}
.grade-XXX { background: rgba(239,68,68,0.12);  color: #ef4444; border: 1px solid rgba(239,68,68,0.25); }
.grade-S   { background: rgba(194,157,102,0.12); color: var(--gold); border: 1px solid rgba(194,157,102,0.25); }
.grade-A   { background: rgba(34,197,94,0.1);   color: var(--green); border: 1px solid rgba(34,197,94,0.2); }
.grade-B   { background: rgba(59,130,246,0.1);  color: var(--blue); border: 1px solid rgba(59,130,246,0.2); }
.prod-info { }
.prod-title { font-size: 14px; font-weight: 900; margin-bottom: 4px; }
.prod-sku   { font-size: 11px; color: var(--gold); font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; }
.prod-stats { display: flex; flex-wrap: wrap; gap: 14px; }
.prod-stat .ps-label { font-size: 10px; color: var(--text2); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.prod-stat .ps-value { font-size: 14px; font-weight: 800; }
.prod-buyers { margin-top: 10px; }
.buyer-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 4px; }
.buyer-chip {
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 20px; padding: 2px 10px;
  font-size: 11px; font-weight: 700; color: var(--text2);
}
.prod-right { text-align: right; min-width: 110px; }
.prod-cost { font-size: 18px; font-weight: 900; color: var(--gold); }
.prod-frozen { font-size: 11px; font-weight: 700; margin-top: 6px; }
.frozen-hot  { color: var(--green); }
.frozen-warm { color: var(--gold); }
.frozen-cold { color: var(--orange); }
.frozen-dead { color: var(--red); }
.ed-input {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.15);
  color: #fff; border-radius: 6px; padding: 4px 6px;
  font-size: 13px; font-weight: 700; text-align: right;
  width: 72px; outline: none;
}
.ed-input:focus { border-color: var(--gold); }
.ed-input-sm { width: 52px; text-align: center; }
.ed-input-wide { width: 100px; text-align: left; }
input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--gold); }
.dash-section { background: var(--surface2); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 16px; }
.dash-title { font-size: 13px; font-weight: 900; color: var(--gold); margin-bottom: 16px; letter-spacing: 1px; }
.dash-hint { text-align: center; color: var(--text2); font-size: 13px; margin-top: 24px; padding: 20px; }

.bar-chart { display: flex; gap: 0; height: 200px; }
.bar-y-axis { display: flex; flex-direction: column; justify-content: space-between; padding-right: 8px; min-width: 60px; text-align: right; }
.bar-y-label { font-size: 10px; color: var(--text2); font-weight: 700; }
.bar-area { flex: 1; display: flex; align-items: flex-end; gap: 6px; height: 100%; }
.bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; }
.bar-stack { width: 100%; max-width: 40px; border-radius: 6px 6px 0 0; overflow: hidden; display: flex; flex-direction: column; justify-content: flex-end; min-height: 4px; background: rgba(255,255,255,0.05); }
.bar-seg { width: 100%; transition: height 0.3s; cursor: pointer; }
.bar-seg:first-child { border-radius: 6px 6px 0 0; }
.bar-label { font-size: 10px; color: var(--text2); font-weight: 700; margin-top: 6px; }

.person-grid { display: flex; flex-direction: column; gap: 10px; }
.person-card { display: grid; grid-template-columns: 100px 1fr 120px; align-items: center; gap: 12px; }
.pc-name { font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.pc-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.pc-bar-wrap { height: 20px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden; }
.pc-bar { height: 100%; border-radius: 10px; transition: width 0.5s; }
.pc-nums { display: flex; align-items: center; justify-content: space-between; }
.pc-amt { font-size: 14px; font-weight: 800; color: var(--gold); }
.pc-pct { font-size: 12px; font-weight: 700; color: var(--text2); }

@media (max-width: 768px) {
  .person-card { grid-template-columns: 80px 1fr 90px; gap: 8px; }
  .pc-name { font-size: 12px; }
  .pc-amt { font-size: 12px; }
  .bar-chart { height: 150px; }
}
.modal-overlay {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.8); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
}
.modal-overlay.hidden { display: none; }
.modal-content {
  background: #0a0a0a; border: 1px solid rgba(194,157,102,0.2);
  border-radius: 20px; width: 95%; max-width: 900px;
  max-height: 85vh; display: flex; flex-direction: column;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 24px; border-bottom: 1px solid var(--border);
}
.modal-header h3 { font-size: 16px; color: var(--gold); font-weight: 900; }
.btn-close { background: none; border: none; color: var(--text2); font-size: 26px; cursor: pointer; }
.modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
.hidden { display: none !important; }
@media (max-width: 768px) {
  .topbar-inner { gap: 12px; }
  .logo { font-size: 14px; }
  .kpi-row { grid-template-columns: repeat(2, 1fr); }
  .product-card { grid-template-columns: 64px 1fr; }
  .prod-right { display: none; }
  .detail-table { font-size: 12px; }
  .detail-table th, .detail-table td { padding: 8px 8px; }
}
  </style>
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div class="topbar-inner">
        <img src="logo.svg" alt="eliTile" class="top-logo">
        <h1 class="logo">睡美人戰情室</h1>
        <div class="tab-bar">
          <button class="tab-btn active" id="tab-bonus" onclick="switchTab('bonus')">獎金試算</button>
          <button class="tab-btn" id="tab-products" onclick="switchTab('products')">產品總覽</button>
        </div>
      </div>
    </header>

    <div class="ctrl-bar" id="ctrl-bonus">
      <select id="filter-year">
        <option value="2026">2026 年</option>
        <option value="2025">2025 年</option>
      </select>
      <select id="filter-month">
        <option value="0">1 月</option><option value="1">2 月</option>
        <option value="2">3 月</option><option value="3">4 月</option>
        <option value="4">5 月</option><option value="5">6 月</option>
        <option value="6">7 月</option><option value="7">8 月</option>
        <option value="8">9 月</option><option value="9">10 月</option>
        <option value="10">11 月</option><option value="11">12 月</option>
      </select>
      <select id="filter-sales">
        <option value="">全部業務</option>
      </select>
      <button class="btn btn-primary" onclick="loadData()">查詢</button>
      <button class="btn btn-ghost" onclick="recalcAll()">重新計算</button>
      <button class="btn btn-accent hidden" id="btn-save" onclick="saveEdits()">💾 儲存修改</button>
    </div>

    <div class="ctrl-bar hidden" id="ctrl-products">
      <select id="filter-grade">
        <option value="">全部等級</option>
        <option value="XXX">XXX</option>
        <option value="S">S</option>
        <option value="A">A</option>
        <option value="B">B</option>
      </select>
      <select id="sort-by" onchange="renderProducts()">
        <option value="totalPings">依銷售坪數</option>
        <option value="inventoryCost">依庫存成本</option>
        <option value="daysSinceLastSale">依停滯天數</option>
      </select>
      <button class="btn btn-primary" onclick="loadProducts()">載入產品</button>
    </div>

    <div id="loading" class="loading hidden">
      <div class="spinner"></div>
      <span>處理中...</span>
    </div>

    <main class="main" id="main-content"></main>
  </div>

  <div id="modal-detail" class="modal-overlay hidden" onclick="closeDetail()">
    <div class="modal-content" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h3 id="modal-title">明細</h3>
        <button class="btn-close" onclick="closeDetail()">×</button>
      </div>
      <div id="modal-body" class="modal-body"></div>
    </div>
  </div>

<script>
const API_BASE = 'api.php';

var currentMonth = new Date().getMonth();
var currentYear = new Date().getFullYear();
var DASHBOARD_COLORS = ['#c29d66','#22c55e','#3b82f6','#ef4444','#a855f7','#f97316','#ec4899','#14b8a6'];

document.getElementById('filter-month').value = currentMonth;
document.getElementById('filter-year').value = currentYear;
loadDashboard();
loadSalesList();

function showLoading(show) {
  document.getElementById('loading').classList.toggle('hidden', !show);
}

function toast(msg) {
  var t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#000;border:2px solid var(--gold);color:#fff;padding:12px 24px;border-radius:8px;font-weight:700;z-index:1000;box-shadow:0 0 15px rgba(194,157,102,0.3);';
  document.body.appendChild(t);
  setTimeout(function(){ t.remove(); }, 2500);
}

function apiGet(action, params, onSuccess, onFail) {
  var url = API_BASE + '?action=' + action;
  if (params) {
    for (var k in params) url += '&' + k + '=' + encodeURIComponent(params[k]);
  }
  fetch(url)
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (onSuccess) onSuccess(res);
    })
    .catch(function(err) {
      if (onFail) onFail(err);
      else toast('連線失敗: ' + err);
    });
}

function apiPost(action, params, onSuccess, onFail) {
  var url = API_BASE + '?action=' + action;
  var formData = new URLSearchParams();
  if (params) {
    for (var k in params) formData.append(k, params[k]);
  }
  fetch(url, { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (onSuccess) onSuccess(res);
    })
    .catch(function(err) {
      if (onFail) onFail(err);
      else toast('連線失敗: ' + err);
    });
}

function loadSalesList() {
  var year = parseInt(document.getElementById('filter-year').value);
  var month = parseInt(document.getElementById('filter-month').value);
  apiGet('summary', { year: year, month: month }, function(res) {
    if (res.success && res.data && res.data.people) {
      var names = res.data.people.map(function(p) { return p.salesName; });
      var sel = document.getElementById('filter-sales');
      sel.innerHTML = '<option value="">全部業務</option>';
      names.forEach(function(n) {
        sel.innerHTML += '<option value="' + n.replace(/'/g, "\\'") + '">' + n + '</option>';
      });
    }
  });
}

function loadData() {
  var year = parseInt(document.getElementById('filter-year').value);
  var month = parseInt(document.getElementById('filter-month').value);
  var sales = document.getElementById('filter-sales').value;

  showLoading(true);
  apiGet('summary', { year: year, month: month }, function(res) {
    if (res.success && !res.data.people.length) {
      toast('該月份尚無資料，正在自動從銷貨拉取...');
      apiPost('sync', { year: year, month: month }, function(syncRes) {
        apiGet('summary', { year: year, month: month }, function(res2) {
          showLoading(false);
          if (res2.success) renderSummary(res2.data, year, month, sales);
          else document.getElementById('main-content').innerHTML = '<div class="welcome"><p style="color:var(--red)">❌ ' + res2.msg + '</p></div>';
        });
      }, function(err) {
        showLoading(false);
        toast('自動拉取失敗: ' + err);
      });
    } else {
      showLoading(false);
      if (res.success) renderSummary(res.data, year, month, sales);
      else document.getElementById('main-content').innerHTML = '<div class="welcome"><p style="color:var(--red)">❌ ' + res.msg + '</p></div>';
    }
  }, function(err) {
    showLoading(false);
    toast('連線失敗: ' + err);
  });
}

var _changedRows = {};

function shortCust(name) {
  return String(name || '').trim().slice(0, 2);
}

function onEditChange(rowIdx) {
  var prefix = 'ed_' + rowIdx;
  var qty = parseFloat(document.getElementById(prefix + '_qty').value) || 0;
  var price = parseFloat(document.getElementById(prefix + '_price').value) || 0;
  var mult = parseFloat(document.getElementById(prefix + '_mult').value) || 1;
  var clearance = document.getElementById(prefix + '_clear').checked ? '是' : '';

  var amt = qty * price;
  document.getElementById(prefix + '_amt').textContent = fmtNum(amt);
  document.getElementById(prefix + '_bonus').textContent = fmtNum(Math.round(amt * mult));

  _changedRows[rowIdx] = {
    qty: qty, unitPrice: price, multiplier: mult,
    clearance: clearance, note: document.getElementById(prefix + '_note').value
  };
  document.getElementById('btn-save').classList.remove('hidden');
}

function renderSummary(data, year, month, salesFilter) {
  _changedRows = {};
  document.getElementById('btn-save').classList.add('hidden');
  var container = document.getElementById('main-content');
  var people = data.people;
  var grand = data.grand;

  if (salesFilter) {
    people = people.filter(function(p) { return p.salesName === salesFilter; });
  }

  if (people.length === 0) {
    container.innerHTML = '<div class="welcome"><h2>暫無資料</h2><p>該月份尚無睡美人銷貨紀錄。</p></div>';
    return;
  }

  var totalPerf = people.reduce(function(s, p) { return s + p.totalBonus; }, 0);

  var kpiHtml = '<div class="kpi-row">' +
    '<div class="kpi-card kpi-blue"><div class="label">總筆數</div><div class="value">' + grand.count + '</div><div class="sub">筆交易</div></div>' +
    '<div class="kpi-card kpi-gold"><div class="label">原始金額</div><div class="value">' + fmt(grand.totalAmt) + '</div><div class="sub">元</div></div>' +
    '<div class="kpi-card kpi-green"><div class="label">毛利率</div><div class="value">' + grand.margin + '%</div><div class="sub">平均</div></div>' +
    '<div class="kpi-card kpi-purple"><div class="label">業績金額合計</div><div class="value">' + fmt(totalPerf) + '</div><div class="sub">元（含倍數）</div></div>' +
    '</div>';

  var tableHtml = '';
  people.forEach(function(p) {
    var mColor = p.margin > 15 ? 'text-green' : (p.margin > 0 ? 'text-gold' : 'text-red');

    tableHtml += '<div class="person-section">' +
      '<div class="person-section-header">' +
        '<span class="person-name">' + p.salesName + '</span>' +
        '<span class="person-perf">業績 <strong id="perf_' + p.salesName + '">' + fmt(p.totalBonus) + '</strong> 元</span>' +
        '<span class="' + mColor + '" style="font-size:13px;font-weight:700;">毛利率 ' + p.margin + '%</span>' +
      '</div>' +
      '<div class="table-wrap"><table class="detail-table"><thead><tr>' +
        '<th>客戶</th><th>編號</th><th>系列</th>' +
        '<th class="text-right">片數</th><th class="text-right">單價</th>' +
        '<th class="text-right">金額</th><th class="text-center">等級</th>' +
        '<th class="text-center">倍數</th><th class="text-right">業績金額</th>' +
        '<th class="text-center">全出清</th><th>備註</th>' +
      '</tr></thead><tbody>';

    p.details.forEach(function(r) {
      var rowIdx = r._rowIdx;
      var prefix = 'ed_' + rowIdx;
      var qty = parseFloat(r['片數']) || 0;
      var price = parseFloat(r['單價']) || 0;
      var mult = parseFloat(r['倍數']) || 1;
      var amt = qty * price;
      var perf = Math.round(amt * mult);
      var cleared = (r['全出清'] === '是');

      tableHtml += '<tr>' +
        '<td>' + shortCust(r['客戶']) + '</td>' +
        '<td class="text-purple" style="font-size:11px;">' + (r['編號'] || '') + '</td>' +
        '<td style="font-size:11px;">' + (r['系列'] || '') + '</td>' +
        '<td class="text-right"><input type="number" step="1" class="ed-input" id="' + prefix + '_qty" value="' + qty + '" onchange="onEditChange(' + rowIdx + ')"></td>' +
        '<td class="text-right"><input type="number" step="0.01" class="ed-input" id="' + prefix + '_price" value="' + price + '" onchange="onEditChange(' + rowIdx + ')"></td>' +
        '<td class="text-right" id="' + prefix + '_amt">' + fmtNum(amt) + '</td>' +
        '<td class="text-center">' + (r['等級'] || '') + '</td>' +
        '<td class="text-center"><input type="number" step="0.1" class="ed-input ed-input-sm" id="' + prefix + '_mult" value="' + mult + '" onchange="onEditChange(' + rowIdx + ')"></td>' +
        '<td class="text-right text-gold" id="' + prefix + '_bonus" style="font-weight:800;">' + fmtNum(perf) + '</td>' +
        '<td class="text-center"><input type="checkbox" id="' + prefix + '_clear" onchange="onEditChange(' + rowIdx + ')"' + (cleared ? ' checked' : '') + '></td>' +
        '<td><input type="text" class="ed-input ed-input-wide" id="' + prefix + '_note" value="' + (r['備註'] || '').replace(/"/g, '&quot;') + '" onchange="onEditChange(' + rowIdx + ')"></td>' +
      '</tr>';
    });

    tableHtml += '</tbody></table></div></div>';
  });

  container.innerHTML = kpiHtml + tableHtml;
}

function saveEdits() {
  var keys = Object.keys(_changedRows);
  if (!keys.length) { toast('沒有修改'); return; }
  showLoading(true);
  var done = 0, fails = 0;
  keys.forEach(function(rowIdx) {
    var d = _changedRows[rowIdx];
    var formData = new URLSearchParams();
    formData.append('rowIdx', rowIdx);
    formData.append('qty', d.qty);
    formData.append('unitPrice', d.unitPrice);
    formData.append('multiplier', d.multiplier);
    formData.append('clearance', d.clearance);
    formData.append('note', d.note);
    fetch(API_BASE + '?action=update-row', { method: 'POST', body: formData })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        done++;
        if (!res.success) fails++;
        if (done === keys.length) {
          showLoading(false);
          if (fails) toast('儲存完成，' + fails + ' 筆失敗');
          else { toast('已儲存 ' + keys.length + ' 筆修改'); _changedRows = {}; document.getElementById('btn-save').classList.add('hidden'); loadData(); }
        }
      });
  });
}

function pullData() {
  showLoading(true);
  var year = parseInt(document.getElementById('filter-year').value);
  var month = parseInt(document.getElementById('filter-month').value);
  apiPost('sync', { year: year, month: month }, function(res) {
    showLoading(false);
    if (res.success) {
      toast('拉取完成！新增 ' + res.added + ' 筆，共 ' + res.total + ' 筆');
      loadData();
    } else {
      toast('拉取失敗: ' + res.msg);
    }
  }, function(err) {
    showLoading(false);
    toast('連線失敗: ' + err);
  });
}

function recalcAll() {
  showLoading(true);
  apiPost('recalc', {}, function(res) {
    showLoading(false);
    if (res.success) {
      toast('已重新計算 ' + res.count + ' 行');
      loadData();
    } else {
      toast('計算失敗: ' + res.msg);
    }
  }, function(err) {
    showLoading(false);
    toast('連線失敗: ' + err);
  });
}

function fmt(n) { return (n || 0).toLocaleString(); }
function fmtNum(n) { return isNaN(n) ? '0' : Math.round(n).toLocaleString(); }

var currentTab = 'bonus';
function switchTab(tab) {
  currentTab = tab;
  document.getElementById('tab-bonus').classList.toggle('active', tab === 'bonus');
  document.getElementById('tab-products').classList.toggle('active', tab === 'products');
  document.getElementById('ctrl-bonus').classList.toggle('hidden', tab !== 'bonus');
  document.getElementById('ctrl-products').classList.toggle('hidden', tab === 'bonus');
  if (tab === 'products') {
    if (!window._productsData) loadProducts();
    else renderProducts();
  }
}

window._productsData = null;

function loadProducts() {
  showLoading(true);
  apiGet('products', {}, function(res) {
    showLoading(false);
    if (!res.success) {
      document.getElementById('main-content').innerHTML = '<div class="welcome"><p style="color:var(--red)">❌ ' + res.msg + '</p></div>';
      return;
    }
    window._productsData = res.data;
    renderProducts();
  }, function(err) {
    showLoading(false);
    toast('載入失敗: ' + err);
  });
}

function renderProducts() {
  if (!window._productsData) return;
  var gradeFilter = document.getElementById('filter-grade').value;
  var sortBy = document.getElementById('sort-by').value;

  var list = window._productsData.filter(function(p) {
    return !gradeFilter || p.grade === gradeFilter;
  });

  list = list.slice().sort(function(a, b) {
    if (sortBy === 'inventoryCost') return b.inventoryCost - a.inventoryCost;
    if (sortBy === 'daysSinceLastSale') {
      var da = a.daysSinceLastSale === null ? 99999 : a.daysSinceLastSale;
      var db = b.daysSinceLastSale === null ? 99999 : b.daysSinceLastSale;
      return db - da;
    }
    return b.totalPings - a.totalPings;
  });

  var totalCost = list.reduce(function(s, p) { return s + p.inventoryCost; }, 0);
  var totalPings = list.reduce(function(s, p) { return s + p.totalPings; }, 0);
  var neverSold = list.filter(function(p) { return p.daysSinceLastSale === null; }).length;
  var frozen = list.filter(function(p) { return p.daysSinceLastSale !== null && p.daysSinceLastSale > 180; }).length;

  var html = '<div class="kpi-row">' +
    '<div class="kpi-card kpi-blue"><div class="label">產品數</div><div class="value">' + list.length + '</div><div class="sub">睡美人 SKU</div></div>' +
    '<div class="kpi-card kpi-gold"><div class="label">庫存佔用成本</div><div class="value">' + fmt(totalCost) + '</div><div class="sub">元</div></div>' +
    '<div class="kpi-card kpi-green"><div class="label">歷史銷售</div><div class="value">' + Math.round(totalPings) + '</div><div class="sub">坪</div></div>' +
    '<div class="kpi-card" style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;text-align:center"><div class="label" style="font-size:11px;color:var(--text2);font-weight:700;margin-bottom:6px">停滯 180天+</div><div class="value" style="font-size:26px;font-weight:900;color:var(--red)">' + (frozen + neverSold) + '</div><div class="sub" style="font-size:11px;color:var(--text2);margin-top:4px">支</div></div>' +
    '</div>';

  html += '<div class="product-list">';
  list.forEach(function(p) {
    var frozenClass, frozenText;
    if (p.daysSinceLastSale === null) {
      frozenClass = 'frozen-dead'; frozenText = '⚠️ 從未銷售';
    } else if (p.daysSinceLastSale > 365) {
      frozenClass = 'frozen-dead'; frozenText = '🧊 ' + p.daysSinceLastSale + ' 天未售';
    } else if (p.daysSinceLastSale > 180) {
      frozenClass = 'frozen-cold'; frozenText = '❄️ ' + p.daysSinceLastSale + ' 天未售';
    } else if (p.daysSinceLastSale > 90) {
      frozenClass = 'frozen-warm'; frozenText = '🔸 ' + p.daysSinceLastSale + ' 天未售';
    } else {
      frozenClass = 'frozen-hot'; frozenText = '✅ ' + p.daysSinceLastSale + ' 天前';
    }

    var buyerHtml = '';
    if (p.buyers && p.buyers.length > 0) {
      buyerHtml = '<div class="prod-buyers"><div class="ps-label">歷史買家</div><div class="buyer-chips">' +
        p.buyers.map(function(b) {
          return '<span class="buyer-chip">' + b.name + ' ' + b.pings + '坪</span>';
        }).join('') + '</div></div>';
    }

    html += '<div class="product-card">' +
      '<div class="prod-grade"><div class="grade-badge grade-' + p.grade + '">' + p.grade + '</div>' +
        '<div style="font-size:11px;color:var(--text2);margin-top:6px">' + p.perPing + '片/坪</div>' +
      '</div>' +
      '<div class="prod-info">' +
        '<div class="prod-title">' + (p.series || '未分類') + '</div>' +
        '<div class="prod-sku">' + p.sku + '</div>' +
        '<div class="prod-stats">' +
          '<div class="prod-stat"><div class="ps-label">歷史銷售</div><div class="ps-value">' + p.totalPings + ' 坪</div></div>' +
          '<div class="prod-stat"><div class="ps-label">庫存</div><div class="ps-value">' + p.stockPing + ' 坪</div></div>' +
          '<div class="prod-stat"><div class="ps-label">成本/片</div><div class="ps-value text-gold">$' + p.costPerPiece + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">最後銷售</div><div class="ps-value">' + p.lastSaleStr + '</div></div>' +
        '</div>' +
        buyerHtml +
      '</div>' +
      '<div class="prod-right">' +
        '<div class="label" style="font-size:11px;color:var(--text2);font-weight:700;margin-bottom:4px">庫存佔用成本</div>' +
        '<div class="prod-cost">' + fmt(p.inventoryCost) + '</div>' +
        '<div class="prod-frozen ' + frozenClass + '">' + frozenText + '</div>' +
      '</div>' +
    '</div>';
  });
  html += '</div>';

  document.getElementById('main-content').innerHTML = html;
}

// ─── Dashboard ───
function loadDashboard() {
  var year = currentYear;
  apiGet('year-summary', { year: year }, function(res) {
    if (res.success) renderDashboard(res.data);
  });
}

function renderDashboard(d) {
  var months = d.months;
  var grandTotal = d.grandTotal;
  var peopleTotal = d.peopleTotal;
  var target = d.target;
  var rate = Math.round(grandTotal / target * 10000) / 100;
  var names = Object.keys(peopleTotal).sort(function(a, b) { return peopleTotal[b] - peopleTotal[a]; });
  var colorMap = {};
  names.forEach(function(n, i) { colorMap[n] = DASHBOARD_COLORS[i % DASHBOARD_COLORS.length]; });

  var html = '<div class="kpi-row">' +
    '<div class="kpi-card kpi-gold"><div class="label">年度業績總計</div><div class="value">' + fmt(grandTotal) + '</div><div class="sub">元（含倍數）</div></div>' +
    '<div class="kpi-card' + (rate >= 100 ? ' kpi-green' : '') + '"><div class="label">目標 300 萬</div><div class="value" style="color:' + (rate >= 100 ? '#22c55e' : '#c29d66') + '">' + rate + '%</div><div class="sub">達成率</div></div>' +
    '<div class="kpi-card kpi-blue"><div class="label">月份</div><div class="value">' + (months.length ? months[months.length-1].month : '-') + '</div><div class="sub">已過月數</div></div>' +
    '<div class="kpi-card" style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;text-align:center"><div class="label" style="font-size:10px;color:var(--text2);font-weight:800;letter-spacing:1px;margin-bottom:6px;text-transform:uppercase">目標差距</div><div class="value" style="font-size:22px;font-weight:900;color:' + (target - grandTotal > 0 ? '#ef4444' : '#22c55e') + '">' + fmt(Math.max(0, target - grandTotal)) + '</div><div class="sub" style="font-size:11px;color:var(--text2);margin-top:4px">' + (target - grandTotal > 0 ? '尚差' : '已達成') + '</div></div>' +
    '</div>';

  // 長條圖
  var maxTotal = d.maxMonthTotal || 1;
  var barChartHtml = '<div class="dash-section"><div class="dash-title">逐月業績長條圖</div><div class="bar-chart"><div class="bar-y-axis">';
  var ySteps = [0, 25, 50, 75, 100];
  for (var yi = ySteps.length - 1; yi >= 0; yi--) {
    barChartHtml += '<div class="bar-y-label">' + fmtNum(Math.round(maxTotal * ySteps[yi] / 100)) + '</div>';
  }
  barChartHtml += '</div><div class="bar-area">';
  months.forEach(function(m) {
    var pct = m.total / maxTotal * 100;
    barChartHtml += '<div class="bar-col"><div class="bar-stack" style="height:' + Math.max(pct, 2) + '%">';
    var sortedPeople = Object.keys(m.people).sort(function(a, b) { return m.people[b] - m.people[a]; });
    sortedPeople.forEach(function(n) {
      var segPct = m.total > 0 ? m.people[n] / m.total * 100 : 0;
      barChartHtml += '<div class="bar-seg" style="height:' + segPct + '%;background:' + colorMap[n] + '" title="' + n + ': ' + fmt(m.people[n]) + '"></div>';
    });
    barChartHtml += '</div><div class="bar-label">' + m.month + '月</div></div>';
  });
  barChartHtml += '</div></div></div>';

  html += barChartHtml;

  // 業務佔比
  html += '<div class="dash-section"><div class="dash-title">業務佔比</div><div class="person-grid">';
  names.forEach(function(n) {
    var amt = peopleTotal[n];
    var pct = grandTotal > 0 ? Math.round(amt / grandTotal * 10000) / 100 : 0;
    var barW = grandTotal > 0 ? Math.round(amt / grandTotal * 100) : 0;
    html += '<div class="person-card">' +
      '<div class="pc-name"><span class="pc-dot" style="background:' + colorMap[n] + '"></span>' + n + '</div>' +
      '<div class="pc-bar-wrap"><div class="pc-bar" style="width:' + barW + '%;background:' + colorMap[n] + '"></div></div>' +
      '<div class="pc-nums"><span class="pc-amt">' + fmt(amt) + '</span><span class="pc-pct">' + pct + '%</span></div>' +
    '</div>';
  });
  html += '</div></div>';

  html += '<div class="dash-hint">💡 選擇月份後按「查詢」查看明細，或切換「產品總覽」</div>';

  document.getElementById('main-content').innerHTML = html;
}
</script>
</body>
</html>
