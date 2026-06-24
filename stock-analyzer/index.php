<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>股票戰情室</title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="icon" type="image/svg+xml" href="icon.svg">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
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

/* Bottom sheet */
.bottom-sheet { position:fixed; inset:0; z-index:500; display:none; }
.bottom-sheet.open { display:flex; flex-direction:column; justify-content:flex-end; }
.bs-backdrop { position:absolute; inset:0; background:rgba(0,0,0,0.5); }
.bs-panel { position:relative; background:var(--surface); border-radius:16px 16px 0 0; max-height:70vh; overflow-y:auto; padding:20px 16px 32px; animation:slideUp 0.3s ease; }
@keyframes slideUp { from { transform:translateY(100%); } to { transform:translateY(0); } }
.bs-handle { width:36px; height:4px; background:var(--text3); border-radius:2px; margin:0 auto 16px; }
.bs-title { font-size:16px; font-weight:800; color:var(--gold); margin-bottom:12px; }
.bs-section { margin-bottom:12px; }
.bs-section-title { font-size:12px; font-weight:700; color:var(--text2); margin-bottom:4px; }
.bs-text { font-size:13px; color:var(--text); line-height:1.7; }
.bs-tag { display:inline-block; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:700; margin-right:4px; }
.bs-tag.bullish { background:rgba(34,197,94,0.15); color:var(--green); }
.bs-tag.bearish { background:rgba(239,68,68,0.15); color:var(--red); }
.bs-tag.neutral { background:rgba(255,255,255,0.08); color:var(--text2); }

/* Help button */
.help-btn { background:none; border:1px solid var(--border2); color:var(--text3); width:18px; height:18px; border-radius:50%; font-size:10px; cursor:pointer; line-height:16px; text-align:center; margin-left:4px; flex-shrink:0; }
.help-btn:hover { color:var(--gold); border-color:var(--gold); }

/* Pattern tags in detail */
.pattern-list { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
.pattern-tag { padding:6px 10px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; border:1px solid var(--border); }
.pattern-tag.bullish { background:rgba(34,197,94,0.1); border-color:rgba(34,197,94,0.3); color:var(--green); }
.pattern-tag.bearish { background:rgba(239,68,68,0.1); border-color:rgba(239,68,68,0.3); color:var(--red); }
.pattern-tag.neutral { background:rgba(255,255,255,0.05); color:var(--text2); }

/* Filter chips on dashboard */
.filter-bar { display:flex; gap:6px; padding:8px 0; overflow-x:auto; -webkit-overflow-scrolling:touch; }
.filter-bar::-webkit-scrollbar { display:none; }
.filter-chip { white-space:nowrap; padding:6px 12px; border-radius:16px; font-size:12px; font-weight:700; cursor:pointer; border:1px solid var(--border2); color:var(--text2); background:var(--surface); flex-shrink:0; }
.filter-chip.active { border-color:var(--gold); color:var(--gold); background:rgba(194,157,102,0.1); }

/* Scanner tab */
.scanner-filters { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
.scanner-chip { padding:6px 10px; border-radius:8px; font-size:11px; font-weight:700; cursor:pointer; border:1px solid var(--border); color:var(--text2); background:var(--surface); }
.scanner-chip.active { border-color:var(--gold); color:var(--gold); background:rgba(194,157,102,0.1); }
.scanner-chip.bullish.active { border-color:var(--green); color:var(--green); background:rgba(34,197,94,0.1); }
.scanner-chip.bearish.active { border-color:var(--red); color:var(--red); background:rgba(239,68,68,0.1); }
.scan-progress { padding:20px; text-align:center; }
.scan-progress-bar { height:4px; background:var(--bg2); border-radius:2px; overflow:hidden; margin:12px 0; }
.scan-progress-fill { height:100%; background:var(--gold); border-radius:2px; transition:width 0.3s; }
.scan-btn { width:100%; padding:12px; border-radius:var(--radius); border:none; background:linear-gradient(135deg, var(--gold), #d4b483); color:#000; font-weight:800; font-size:14px; cursor:pointer; margin-bottom:12px; }

@media (min-width: 600px) {
  .app { border-left: 1px solid var(--border); border-right: 1px solid var(--border); }
}
</style>
</head>
<body>
<div class="app">
  <div class="topbar">
    <h1><svg width="22" height="22" viewBox="0 0 64 64" style="vertical-align:-3px;margin-right:4px"><defs><linearGradient id="tg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#d4b483"/><stop offset="100%" stop-color="#a07840"/></linearGradient></defs><rect width="64" height="64" rx="14" fill="#0a0a0a"/><path d="M12 52 L12 12 Q12 8 16 8 L48 8 Q52 8 52 12 L52 38 Q52 42 48 42 L20 42 L12 52Z" fill="none" stroke="url(#tg)" stroke-width="2.5" stroke-linejoin="round"/><rect x="20" y="20" width="4" height="14" rx="1" fill="#22c55e"/><line x1="22" y1="17" x2="22" y2="37" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round"/><rect x="28" y="16" width="4" height="12" rx="1" fill="#ef4444"/><line x1="30" y1="13" x2="30" y2="31" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round"/><rect x="36" y="18" width="4" height="10" rx="1" fill="#ef4444"/><line x1="38" y1="14" x2="38" y2="32" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round"/><rect x="44" y="14" width="4" height="14" rx="1" fill="#ef4444"/><line x1="46" y1="11" x2="46" y2="31" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round"/></svg>股票戰情室</h1>
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

  <!-- Tab: Scanner -->
  <div class="main" id="page-scanner" style="display:none">
    <div class="section-title">技術選股</div>
    <button class="scan-btn" onclick="startMarketScan()">掃描全市場</button>
    <div id="scan-progress-area"></div>
    <div class="section-title" style="font-size:12px;color:var(--text2);padding-top:4px">篩選條件 (可多選)</div>
    <div class="scanner-filters" id="scanner-filters"></div>
    <div id="scanner-results"></div>
  </div>

  <!-- Bottom Sheet -->
  <div class="bottom-sheet" id="bottom-sheet">
    <div class="bs-backdrop" onclick="closeSheet()"></div>
    <div class="bs-panel">
      <div class="bs-handle"></div>
      <div class="bs-title" id="bs-title"></div>
      <div class="bs-body" id="bs-body"></div>
    </div>
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
    <div class="tab-item" id="tab-scanner" onclick="showTab('scanner')">
      <span class="tab-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span><span>選股</span>
    </div>
  </div>
</div>

<script>
var API = 'api.php';
var currentTab = 'dashboard';
var allResults = [];
var currentDashFilter = 'all';
var scannerFilters = [];
var scanResults = [];
var scanPolling = null;

var INDICATOR_HELP = {
  ma5:{name:'五日均線 (MA5)',desc:'最近5天收盤價的平均，代表一週內買進的人的平均成本。',high:'股價在MA5之上：短線偏多，最近買的人多數賺錢，有人願意追高。',low:'股價在MA5之下：短線偏空，最近買的人開始虧錢，可能引發停損賣壓。',tip:'最靈敏的均線，適合短線操作者觀察。跌破MA5是第一個警訊。'},
  ma20:{name:'二十日均線 (MA20) / 月線',desc:'最近一個月的平均成本，是法人和主力最常看的均線。',high:'站上月線：中短線趨勢偏多，回檔到月線附近常是買點。',low:'跌破月線：中短線轉弱，反彈到月線附近常遇壓力。',tip:'月線是多空分水嶺，很多投資人以此判斷進出場。'},
  ma60:{name:'六十日均線 (MA60) / 季線',desc:'最近三個月的平均成本，代表中期趨勢方向。',high:'站上季線：中期趨勢向上，適合偏多操作。',low:'跌破季線：中期趨勢轉弱，要降低持股或觀望。',tip:'「季線是生命線」，跌破季線後通常需要較長時間才能收復。'},
  rsi:{name:'相對強弱指標 (RSI)',desc:'衡量股價漲跌力道的強弱，數值在0-100之間。簡單說就是最近漲的力氣大還是跌的力氣大。',high:'RSI > 70 (超買)：最近漲太多太快，獲利了結賣壓可能出現，不適合追高。',low:'RSI < 30 (超賣)：最近跌太多太快，可能出現反彈機會，但要確認不是持續破底。',tip:'RSI要搭配趨勢看。在強勢上漲中RSI可以長期維持在50以上，不要看到70就急著賣。'},
  kd:{name:'KD隨機指標 (9日)',desc:'用最近9天的最高價、最低價和收盤價，計算出目前股價在區間中的相對位置。K是快線，D是慢線。',high:'KD > 80 (超買區)：股價已在近期高檔，K線從上往下穿D線叫「死亡交叉」，是賣出信號。',low:'KD < 20 (超賣區)：股價已在近期低檔，K線從下往上穿D線叫「黃金交叉」，是買入信號。',tip:'KD在超買區不代表一定會跌，強勢股可以「鈍化」在高檔很久。重點看K和D的交叉方向。'},
  macd:{name:'MACD 趨勢指標',desc:'用兩條不同速度的均線的差距來判斷趨勢。柱狀體代表多空力道的變化速度。',high:'MACD柱狀體 > 0：多方力道強，數值越大代表上漲動能越強。柱體由大轉小代表漲勢放緩。',low:'MACD柱狀體 < 0：空方力道強，數值越負代表下跌動能越強。柱體由負轉正是重要買點。',tip:'MACD是趨勢指標，反應比較慢但比較可靠。DIF線上穿MACD線叫「黃金交叉」。'},
  volume:{name:'量比',desc:'今天的成交量除以最近20天平均量。大於1代表今天交易比平常熱絡。',high:'量比 > 1.3 (放量)：交易明顯增加，可能有大資金在動作。搭配漲跌方向判斷是買盤還是賣盤。',low:'量比 < 0.7 (縮量)：交易冷清，觀望氣氛濃。上漲縮量代表追高意願低。',tip:'「量為價先」是重要觀念。突破前要先看到量能放大，沒有量的突破容易是假突破。'}
};

var PATTERN_HELP = {
  doji:{name:'十字線',meaning:'開盤和收盤價幾乎一樣，代表多空雙方力量平衡。如果在一波漲勢或跌勢後出現，可能是變盤的前兆。',action:'觀望等確認，隔天收陽線可能續漲，收陰線可能反轉。'},
  long_upper_shadow:{name:'長上影線',meaning:'股價盤中被拉上去，但收盤又被壓回來。表示上面有人在倒貨，賣壓沉重。',action:'短線注意上方壓力，不要急著追高。'},
  long_lower_shadow:{name:'長下影線',meaning:'股價盤中被打下去，但收盤又被拉回來。表示下面有人在接貨，支撐力道強。',action:'下方有買盤支撐，如果在低檔出現可以留意反彈機會。'},
  hammer:{name:'錘子線',meaning:'在一波下跌後出現，長下影線短上影線。像錘子在「打底」，表示賣壓已衰竭，買盤開始進場。',action:'重要的底部反轉信號！隔天如果跳空開高或收陽線，可以考慮進場。'},
  hanging_man:{name:'吊燈線',meaning:'在一波上漲後出現，外型跟錘子一樣但位置在高檔。像一個人吊在高處，暗示上漲可能到頂。',action:'高檔警訊，不要再追高。隔天如果跳空開低或收陰線，應該減碼。'},
  large_bullish:{name:'大陽線',meaning:'開低走高，收盤遠高於開盤。買方全面進攻，多頭氣勢非常強。',action:'短線強勢，但如果在連漲多天後出現，可能是最後一波噴出。'},
  large_bearish:{name:'大陰線',meaning:'開高走低，收盤遠低於開盤。賣方全面攻擊，空頭氣勢非常強。',action:'短線弱勢，如果帶大量要特別小心，可能是主力出貨。'},
  spinning_top:{name:'紡錘線',meaning:'實體小、上下影線都長。多空雙方激烈拉鋸但誰也贏不了，市場猶豫不決。',action:'等待方向選擇，不急著進場。'},
  bullish_engulfing:{name:'看多吞噬',meaning:'陽線的實體完全包住前一天陰線的實體，代表多方一口氣吃掉空方的地盤。',action:'強力的反轉買入信號，尤其在下跌趨勢底部出現時效果最好。'},
  bearish_engulfing:{name:'看空吞噬',meaning:'陰線的實體完全包住前一天陽線的實體，代表空方一口氣吃掉多方的地盤。',action:'強力的反轉賣出信號，尤其在上漲趨勢頂部出現時效果最好。'},
  morning_star:{name:'晨星',meaning:'三根K棒組成：大陰線→小K棒→大陽線。像黑暗後出現的晨星，代表黎明將至。',action:'經典的底部反轉型態，看到可以準備進場做多。'},
  evening_star:{name:'夜星',meaning:'三根K棒組成：大陽線→小K棒→大陰線。像白天結束出現的暮星，代表黑暗即將降臨。',action:'經典的頭部反轉型態，看到應該考慮獲利了結。'},
  dark_cloud_cover:{name:'烏雲罩頂',meaning:'高開後大跌，收盤跌入前天陽線的中點以下。就像烏雲遮住太陽，多頭的天空要變天了。',action:'頭部反轉信號，如果在高檔出現應該減碼觀望。'},
  piercing_line:{name:'曙光乍現',meaning:'低開後大漲，收盤漲入前天陰線的中點以上。黑暗中出現曙光，多方開始反攻。',action:'底部反轉信號，如果在低檔出現可以留意買點。'},
  bullish_harami:{name:'看多母子線',meaning:'一根小陽線被前天的大陰線包住，像母親抱著嬰兒。空方力道衰減，多方可能接手。',action:'下跌趨勢可能暫停，等隔天確認方向。'},
  bearish_harami:{name:'看空母子線',meaning:'一根小陰線被前天的大陽線包住。多方力道衰減，空方可能接手。',action:'上漲趨勢可能暫停，等隔天確認方向。'},
  three_white_soldiers:{name:'三白兵',meaning:'連續三根陽線，每根都創新高，像三個士兵並肩前進。多方士氣高昂。',action:'強勢多方型態，趨勢向上。但如果在高檔出現要注意是否過熱。'},
  three_black_crows:{name:'三烏鴉',meaning:'連續三根陰線，每根都創新低，像三隻烏鴉帶來壞消息。空方氣勢洶洶。',action:'強勢空方型態，趨勢向下。應該減碼或停損。'},
  gap_up:{name:'向上跳空',meaning:'今天最低價高於昨天最高價，中間留下空白。代表買盤急迫，開盤就搶。',action:'跳空缺口常成為支撐，回測缺口不破可以加碼。'},
  gap_down:{name:'向下跳空',meaning:'今天最高價低於昨天最低價，中間留下空白。代表賣盤恐慌，開盤就殺。',action:'跳空缺口常成為壓力，反彈到缺口附近可能再度下跌。'},
  island_reversal_bullish:{name:'島型反轉(多)',meaning:'先跳空下跌再跳空上漲，中間的K棒像一座孤島。非常罕見但非常強的反轉信號。',action:'極為強烈的買入信號，底部成形機率很高。'},
  island_reversal_bearish:{name:'島型反轉(空)',meaning:'先跳空上漲再跳空下跌，中間的K棒像一座孤島。非常罕見但非常強的反轉信號。',action:'極為強烈的賣出信號，頭部成形機率很高。'},
  breakout_high:{name:'突破前高',meaning:'收盤價超過近期最高點。代表多方攻破了之前的壓力，可能展開新一波漲勢。',action:'如果搭配放量突破，追進的勝率較高。但要設停損在突破點下方。'},
  break_support:{name:'跌破支撐',meaning:'收盤價跌破近期最低點。之前的支撐線失守，可能進一步下跌。',action:'應該停損出場，不要猜底。等止跌確認再進場。'},
  cross_above_ma20:{name:'突破月線',meaning:'股價從下方穿越20日均線。中短線趨勢可能從空轉多。',action:'站上月線後如果能守住3天以上，上漲機率增加。'},
  cross_below_ma20:{name:'跌破月線',meaning:'股價從上方跌破20日均線。中短線趨勢可能從多轉空。',action:'跌破月線後如果反彈無法收復，通常還會再跌一段。'},
  volume_price_divergence_bear:{name:'量價背離(空)',meaning:'股價在漲但成交量卻在萎縮。代表願意追高的人越來越少，上漲缺乏力道。',action:'漲勢可能即將結束，不要追高，等回檔再說。'},
  volume_shrink_stop:{name:'量縮止跌',meaning:'股價在跌但成交量也在萎縮。代表想賣的人越來越少，賣壓接近尾聲。',action:'下跌可能接近尾聲，開始觀察是否出現止跌信號。'},
  consecutive_highs:{name:'連續創高',meaning:'連續多天都創出新高，多方強勢攻擊不停歇。',action:'強勢趨勢中持股續抱，但越高越要注意量能是否跟上。'},
  consecutive_lows:{name:'連續創低',meaning:'連續多天都創出新低，空方不斷下殺。',action:'不要接刀，等止跌信號出現再進場。'}
};

var ALL_PATTERN_FILTERS = [
  {code:'breakout_high',name:'突破前高',type:'bullish'},
  {code:'break_support',name:'跌破支撐',type:'bearish'},
  {code:'hammer',name:'錘子線',type:'bullish'},
  {code:'hanging_man',name:'吊燈線',type:'bearish'},
  {code:'morning_star',name:'晨星',type:'bullish'},
  {code:'evening_star',name:'夜星',type:'bearish'},
  {code:'bullish_engulfing',name:'看多吞噬',type:'bullish'},
  {code:'bearish_engulfing',name:'看空吞噬',type:'bearish'},
  {code:'three_white_soldiers',name:'三白兵',type:'bullish'},
  {code:'three_black_crows',name:'三烏鴉',type:'bearish'},
  {code:'gap_up',name:'向上跳空',type:'bullish'},
  {code:'gap_down',name:'向下跳空',type:'bearish'},
  {code:'doji',name:'十字線',type:'neutral'},
  {code:'long_upper_shadow',name:'長上影線',type:'bearish'},
  {code:'long_lower_shadow',name:'長下影線',type:'bullish'},
  {code:'cross_above_ma20',name:'突破月線',type:'bullish'},
  {code:'cross_below_ma20',name:'跌破月線',type:'bearish'},
  {code:'volume_price_divergence_bear',name:'量價背離',type:'bearish'},
  {code:'volume_shrink_stop',name:'量縮止跌',type:'bullish'},
  {code:'consecutive_highs',name:'連續創高',type:'bullish'},
  {code:'consecutive_lows',name:'連續創低',type:'bearish'}
];

function showLoading(show) { document.getElementById('loading').classList.toggle('hidden', !show); }

function showTab(tab) {
  currentTab = tab;
  ['dashboard','watchlist','alerts','detail','scanner'].forEach(function(t) {
    var el = document.getElementById('page-' + t);
    if (el) el.style.display = t === tab ? 'block' : 'none';
  });
  document.querySelectorAll('.tab-item').forEach(function(el) {
    el.classList.toggle('active', el.id === 'tab-' + tab);
  });
  if (tab === 'dashboard' && allResults.length === 0) scanAll();
  if (tab === 'watchlist') loadWatchlist();
  if (tab === 'alerts') loadAlerts();
  if (tab === 'scanner') renderScannerFilters();
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

function filterDashboard(code) {
    currentDashFilter = code;
    renderDashboard();
}

function renderDashboard() {
  var html = '';
  if (allResults.length === 0) {
    html = '<div class="empty"><h3>尚無資料</h3><p>請先在「監控」頁面加入股票</p></div>';
    document.getElementById('dashboard-content').innerHTML = html;
    return;
  }

  // Quick filter chips
  var filterCodes = [
      {code:'all', name:'全部'},
      {code:'breakout_high', name:'突破前高'},
      {code:'morning_star', name:'晨星'},
      {code:'hammer', name:'錘子線'},
      {code:'bullish_engulfing', name:'看多吞噬'},
      {code:'three_white_soldiers', name:'三白兵'},
      {code:'gap_up', name:'向上跳空'},
      {code:'break_support', name:'跌破支撐'},
      {code:'evening_star', name:'夜星'},
      {code:'bearish_engulfing', name:'看空吞噬'},
  ];
  html += '<div class="filter-bar">';
  filterCodes.forEach(function(f) {
      var isActive = currentDashFilter === f.code;
      html += '<div class="filter-chip' + (isActive ? ' active' : '') + '" onclick="filterDashboard(\'' + f.code + '\')">' + f.name + '</div>';
  });
  html += '</div>';

  var filtered = allResults;
  if (currentDashFilter !== 'all') {
      filtered = allResults.filter(function(r) {
          return r.patterns && r.patterns.indexOf(currentDashFilter) !== -1;
      });
  }
  if (filtered.length === 0 && currentDashFilter !== 'all') {
      html += '<div class="empty"><p>監控清單中沒有符合「' + filterCodes.find(function(f){return f.code===currentDashFilter;}).name + '」的股票</p></div>';
      document.getElementById('dashboard-content').innerHTML = html;
      return;
  }

  // Group by recommendation
  var groups = { risk: [], wait: [], watch: [], good: [] };
  filtered.forEach(function(r) {
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
    showTab('detail');
    renderDetail(res.data);
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
  h += '<div class="chart-title">' + svgIcon('target','var(--gold)',14) + ' KD 指標 (9日) <button class="help-btn" onclick="event.stopPropagation();showIndicatorHelp(\'kd\')" style="margin-left:8px">?</button></div>';
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
  h += indCardHelp('MA5', '五日均線', fmtPrice(tech.ma5), tech.ma5 && d.price > tech.ma5 ? 'var(--green)' : 'var(--red)', 'ma5');
  h += indCardHelp('MA20', '月線', fmtPrice(tech.ma20), tech.ma20 && d.price > tech.ma20 ? 'var(--green)' : 'var(--red)', 'ma20');
  h += indCardHelp('MA60', '季線', fmtPrice(tech.ma60), tech.ma60 && d.price > tech.ma60 ? 'var(--green)' : 'var(--red)', 'ma60');
  h += indCardHelp('RSI(14)', '相對強弱', (tech.rsi14||0).toFixed(1), tech.rsi14 > 70 ? 'var(--red)' : tech.rsi14 < 30 ? 'var(--green)' : 'var(--text)', 'rsi');
  h += indCardHelp('MACD', '趨勢指標', (tech.macdHist||0).toFixed(2), tech.macdHist > 0 ? 'var(--red)' : 'var(--green)', 'macd');
  h += indCardHelp('量比', '成交活躍度', (tech.volumeRatio||1).toFixed(2) + 'x', tech.volumeRatio > 1.3 ? 'var(--orange)' : 'var(--text)', 'volume');
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

  // Patterns
  if (d.patterns && d.patterns.length) {
      h += '<div class="chart-box">';
      h += '<div class="chart-title">' + svgIcon('zap','var(--gold)',14) + ' K線型態辨識</div>';
      h += '<div class="pattern-list">';
      d.patterns.forEach(function(p) {
          h += '<div class="pattern-tag ' + p.type + '" onclick="showPatternHelp(\'' + p.code + '\')">';
          h += p.name + ' <span style="font-size:10px;opacity:0.7">' + p.date.substr(5) + '</span>';
          h += '</div>';
      });
      h += '</div>';
      h += '<div style="font-size:11px;color:var(--text3);margin-top:4px">點擊型態查看白話解說</div>';
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

  // Draw charts after layout is complete (double rAF ensures reflow)
  requestAnimationFrame(function() {
    requestAnimationFrame(function() {
      if (d.quotes && d.quotes.length > 5) {
        drawKlineChart(document.getElementById('kline-canvas'), d.quotes, tech.maSeries || {});
      }
      if (tech.kdSeries) {
        drawKDChart(document.getElementById('kd-chart-canvas'), tech.kdSeries);
      }
      if (hasPeChart) {
        drawPERiverChart(document.getElementById('pe-river-canvas'), d.quotes, d.peHistory, val);
      }
    });
  });
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

function indCardHelp(label, subLabel, value, color, helpKey) {
    var style = color ? ' style="color:' + color + '"' : '';
    return '<div class="ind-card"><div class="ind-label" style="display:flex;align-items:center;justify-content:space-between">' + label + '<button class="help-btn" onclick="event.stopPropagation();showIndicatorHelp(\'' + helpKey + '\')">?</button></div><div style="font-size:9px;color:var(--text3);margin-bottom:4px">' + subLabel + '</div><div class="ind-value"' + style + '>' + value + '</div></div>';
}

// Bottom sheet functions
function openSheet(title, bodyHtml) {
  document.getElementById('bs-title').textContent = title;
  document.getElementById('bs-body').innerHTML = bodyHtml;
  document.getElementById('bottom-sheet').classList.add('open');
}
function closeSheet() {
  document.getElementById('bottom-sheet').classList.remove('open');
}
function showIndicatorHelp(key) {
  var h = INDICATOR_HELP[key];
  if (!h) return;
  var html = '<div class="bs-section"><div class="bs-section-title">這是什麼？</div><div class="bs-text">' + h.desc + '</div></div>';
  html += '<div class="bs-section"><div class="bs-section-title" style="color:var(--red)">數值偏高代表...</div><div class="bs-text">' + h.high + '</div></div>';
  html += '<div class="bs-section"><div class="bs-section-title" style="color:var(--green)">數值偏低代表...</div><div class="bs-text">' + h.low + '</div></div>';
  html += '<div class="bs-section"><div class="bs-section-title" style="color:var(--gold)">實戰小提醒</div><div class="bs-text">' + h.tip + '</div></div>';
  openSheet(h.name, html);
}
function showPatternHelp(code) {
  var p = PATTERN_HELP[code];
  if (!p) return;
  var html = '<div class="bs-section"><div class="bs-section-title">白話解釋</div><div class="bs-text">' + p.meaning + '</div></div>';
  html += '<div class="bs-section"><div class="bs-section-title" style="color:var(--gold)">該怎麼做？</div><div class="bs-text">' + p.action + '</div></div>';
  openSheet(p.name, html);
}

// Scanner tab functions
function renderScannerFilters() {
    var h = '';
    ALL_PATTERN_FILTERS.forEach(function(f) {
        var active = scannerFilters.indexOf(f.code) !== -1;
        h += '<div class="scanner-chip ' + f.type + (active ? ' active' : '') + '" onclick="toggleScannerFilter(\'' + f.code + '\')">' + f.name + '</div>';
    });
    document.getElementById('scanner-filters').innerHTML = h;
}

function toggleScannerFilter(code) {
    var idx = scannerFilters.indexOf(code);
    if (idx === -1) scannerFilters.push(code);
    else scannerFilters.splice(idx, 1);
    renderScannerFilters();
    if (scanResults.length > 0) renderScannerResults();
}

function startMarketScan() {
    var params = {};
    if (scannerFilters.length > 0) params.filters = scannerFilters.join(',');

    document.getElementById('scan-progress-area').innerHTML = '<div class="scan-progress"><div class="spinner" style="margin:0 auto 8px"></div><div style="color:var(--text2)">正在掃描全市場...</div><div class="scan-progress-bar"><div class="scan-progress-fill" id="scan-fill" style="width:0%"></div></div><div style="font-size:11px;color:var(--text3)" id="scan-status">準備中...</div></div>';
    document.getElementById('scanner-results').innerHTML = '';

    apiGet('scan-market', params).then(function(res) {
        if (res.scanning) {
            pollScanProgress();
        } else if (res.success && res.data) {
            scanResults = res.data;
            document.getElementById('scan-progress-area').innerHTML = '';
            renderScannerResults();
        } else {
            document.getElementById('scan-progress-area').innerHTML = '<div class="empty"><p>' + (res.msg || '掃描失敗') + '</p></div>';
        }
    }).catch(function() {
        document.getElementById('scan-progress-area').innerHTML = '<div class="empty"><p>連線失敗</p></div>';
    });
}

function pollScanProgress() {
    if (scanPolling) clearInterval(scanPolling);
    scanPolling = setInterval(function() {
        apiGet('scan-progress').then(function(res) {
            if (!res.running && res.running !== undefined) {
                clearInterval(scanPolling);
                scanPolling = null;
                startMarketScan();
                return;
            }
            var pct = res.total > 0 ? Math.round((res.done || 0) / res.total * 100) : 0;
            var fill = document.getElementById('scan-fill');
            var status = document.getElementById('scan-status');
            if (fill) fill.style.width = pct + '%';
            if (status) status.textContent = '已掃描 ' + (res.done || 0) + ' / ' + (res.total || '?') + ' 檔 (' + pct + '%)';
        });
    }, 3000);
}

function renderScannerResults() {
    var data = scanResults;
    // Apply client-side filter
    if (scannerFilters.length > 0) {
        data = data.filter(function(r) {
            if (!r.patterns) return false;
            for (var i = 0; i < scannerFilters.length; i++) {
                if (r.patterns.indexOf(scannerFilters[i]) !== -1) return true;
            }
            return false;
        });
    }

    // Sort by score descending
    data.sort(function(a, b) { return (b.score || 0) - (a.score || 0); });

    var h = '<div class="section-title">掃描結果 <span class="count">' + data.length + ' 檔</span></div>';
    if (data.length === 0) {
        h += '<div class="empty"><p>沒有符合條件的股票</p></div>';
    } else {
        data.forEach(function(r) {
            h += '<div class="stock-card" onclick="showDetail(\'' + r.stockId + '\',\'tw\')" style="cursor:pointer">';
            h += '<div class="card-header"><div><div class="card-name">' + (r.name || r.stockId) + '</div>';
            h += '<div class="card-sub">' + r.stockId + '.TW</div></div>';
            h += '<div style="font-size:16px;font-weight:900;color:' + ((r.changePct||0) >= 0 ? 'var(--red)' : 'var(--green)') + '">$' + fmtPrice(r.price) + '</div></div>';
            if (r.patternDetails && r.patternDetails.length) {
                h += '<div class="pattern-list" style="margin-top:8px">';
                r.patternDetails.forEach(function(p) {
                    h += '<div class="pattern-tag ' + p.type + '" onclick="event.stopPropagation();showPatternHelp(\'' + p.code + '\')" style="font-size:11px;padding:4px 8px">' + p.name + '</div>';
                });
                h += '</div>';
            }
            h += '</div>';
        });
    }
    document.getElementById('scanner-results').innerHTML = h;
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
