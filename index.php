<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>睡美人戰情室</title>
  <link rel="icon" href="favicon.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700;800;900&family=Noto+Sans+TC:wght@600;700;800;900&display=swap" rel="stylesheet">
  <style>
:root {
  --bg: #0a0a0a;
  --surface: #0d0d0d;
  --surface2: rgba(255,255,255,0.04);
  --border: rgba(194,157,102,0.15);
  --border-light: rgba(255,255,255,0.06);
  --text: #ffffff;
  --text2: rgba(255,255,255,0.45);
  --gold: #c29d66;
  --gold-soft: rgba(194,157,102,0.1);
  --gold-glow: rgba(194,157,102,0.2);
  --gold-gradient: linear-gradient(135deg, #c29d66 0%, #d4b483 100%);
  --purple: #a78bfa;
  --red: #ef4444;
  --orange: #f97316;
  --blue: #3b82f6;
  --green: #22c55e;
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --radius-xl: 18px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: "Inter", -apple-system, BlinkMacSystemFont, "Noto Sans TC", "Microsoft JhengHei", sans-serif;
  font-feature-settings: "tnum" 1;
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}
.app { display: flex; flex-direction: column; min-height: 100dvh; }
.topbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(10,10,10,0.88);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(194,157,102,0.2);
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
  text-shadow: 0 0 20px rgba(194,157,102,0.3);
  white-space: nowrap; flex-shrink: 0;
}
.tab-bar { display: flex; gap: 2px; }
.tab-btn {
  height: 36px; padding: 0 20px; border-radius: var(--radius-md);
  font-size: 13px; font-weight: 700; letter-spacing: 0.5px;
  cursor: pointer; border: 1px solid transparent;
  background: transparent; color: var(--text2);
  transition: all 0.2s;
}
.tab-btn:hover { color: var(--text); background: rgba(255,255,255,0.03); }
.tab-btn.active {
  background: var(--gold-soft);
  border-color: rgba(194,157,102,0.4);
  color: var(--gold);
}
.ctrl-bar {
  background: rgba(10,10,10,0.7);
  backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--border);
  padding: 10px 20px;
  display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.ctrl-bar.hidden { display: none; }
select, .btn {
  height: 34px; border-radius: var(--radius-md);
  font-size: 13px; font-weight: 700; outline: none; cursor: pointer;
  font-family: inherit;
}
select {
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--border-light);
  color: var(--text); padding: 0 28px 0 10px; min-width: 80px;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23c29d66' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 8px center;
  transition: border-color 0.2s;
}
select:focus { border-color: rgba(194,157,102,0.5); }
.sort-dir-btn {
  height: 30px; width: 32px; border-radius: var(--radius-sm);
  font-size: 14px; font-weight: 700; cursor: pointer;
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--border-light); color: var(--text);
  transition: all 0.2s; line-height: 1;
}
.sort-dir-btn:hover { background: rgba(255,255,255,0.1); border-color: var(--gold); }
.prod-sub-tabs { display: inline-flex; gap: 2px; margin-left: 4px; }
.sub-tab {
  height: 30px; padding: 0 14px; border-radius: var(--radius-sm);
  font-size: 12px; font-weight: 700; letter-spacing: 0.5px;
  cursor: pointer; border: 1px solid transparent;
  background: transparent; color: var(--text2);
  transition: all 0.2s;
}
.sub-tab:hover { color: var(--text); background: rgba(255,255,255,0.03); }
.sub-tab.active {
  background: rgba(167,139,250,0.12);
  border-color: rgba(167,139,250,0.35);
  color: var(--purple);
}
.btn {
  padding: 0 16px; border: none; transition: all 0.2s;
}
.btn:hover { filter: brightness(1.2); }
.btn:active { transform: scale(0.96); }
.btn-primary { background: var(--gold-gradient); color: #000; }
.btn-accent  { background: rgba(194,157,102,0.12); border: 1px solid rgba(194,157,102,0.3); color: var(--gold); }
.btn-ghost   { background: transparent; border: 1px solid var(--border-light); color: var(--text2); }
.loading {
  position: fixed; inset: 0;
  background: rgba(10,10,10,0.85); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
  gap: 14px; z-index: 999; font-size: 15px; font-weight: 700; color: var(--gold);
}
.loading.hidden { display: none; }
.spinner {
  width: 24px; height: 24px;
  border: 2px solid rgba(194,157,102,0.15); border-top-color: var(--gold);
  border-radius: 50%; animation: spin 0.8s linear infinite;
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
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 10px; margin-bottom: 24px;
}
.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 20px 16px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.kpi-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0;
  height: 2px;
  background: var(--gold-gradient);
  opacity: 0.4;
}
.kpi-card .label {
  font-size: 10px; color: var(--text2); font-weight: 700;
  letter-spacing: 1.5px; margin-bottom: 8px; text-transform: uppercase;
}
.kpi-card .value { font-size: 26px; font-weight: 900; letter-spacing: -0.5px; }
.kpi-card .sub { font-size: 11px; color: var(--text2); margin-top: 6px; }
.kpi-gold   .value { color: var(--gold); }
.kpi-green  .value { color: var(--green); }
.kpi-blue   .value { color: var(--blue); }
.kpi-red    .value { color: var(--red); }
.person-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  overflow: hidden; margin-bottom: 16px;
  transition: border-color 0.2s;
}
.person-section:hover { border-color: rgba(194,157,102,0.25); }
.person-section-header {
  display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
  padding: 14px 20px;
  border-bottom: 1px solid var(--border);
}
.person-name { font-size: 16px; font-weight: 900; letter-spacing: 1px; flex: 1; }
.person-perf { font-size: 13px; color: var(--text2); }
.person-perf strong { color: var(--gold); font-size: 18px; font-weight: 900; }
.table-wrap { overflow-x: auto; }
.detail-table {
  width: 100%; border-collapse: collapse; font-size: 13px;
}
.detail-table th {
  text-align: left; padding: 10px 14px;
  color: var(--gold); font-weight: 700; font-size: 11px;
  letter-spacing: 0.8px;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
.detail-table td {
  padding: 10px 14px;
  border-bottom: 1px solid var(--border-light);
  white-space: nowrap;
}
.detail-table tr:last-child td { border-bottom: none; }
.detail-table tr:hover td { background: rgba(194,157,102,0.03); }
.text-right  { text-align: right; }
.text-center { text-align: center; }
.text-gold   { color: var(--gold); }
.text-green  { color: var(--green); }
.text-red    { color: var(--red); }
.text-purple { color: var(--purple); }
.product-list { display: flex; flex-direction: column; gap: 8px; }
.product-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  display: grid;
  grid-template-columns: 72px 1fr auto;
  gap: 0 16px; align-items: start;
  transition: all 0.25s;
}
.product-card:hover {
  border-color: rgba(194,157,102,0.35);
  box-shadow: 0 0 0 1px rgba(194,157,102,0.08);
}
.prod-grade { text-align: center; padding: 4px 0; }
.grade-badge {
  display: inline-block; font-size: 15px; font-weight: 900;
  padding: 6px 14px; border-radius: var(--radius-md);
  letter-spacing: 1px;
}
.grade-XXX { background: rgba(239,68,68,0.1);  color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
.grade-S   { background: rgba(194,157,102,0.1); color: var(--gold); border: 1px solid rgba(194,157,102,0.2); }
.grade-A   { background: rgba(34,197,94,0.08);  color: var(--green); border: 1px solid rgba(34,197,94,0.15); }
.grade-B   { background: rgba(59,130,246,0.08); color: var(--blue); border: 1px solid rgba(59,130,246,0.15); }
.prod-title { font-size: 14px; font-weight: 900; margin-bottom: 3px; }
.prod-sku   { font-size: 11px; color: var(--gold); font-weight: 700; letter-spacing: 1px; margin-bottom: 8px; font-feature-settings: "tnum" 0; }
.prod-stats { display: flex; flex-wrap: wrap; gap: 16px; }
.prod-stat .ps-label { font-size: 10px; color: var(--text2); font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; }
.prod-stat .ps-value { font-size: 14px; font-weight: 800; }
.prod-buyers { margin-top: 10px; }
.buyer-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
.buyer-chip {
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border-light);
  border-radius: 20px; padding: 3px 10px;
  font-size: 11px; font-weight: 600; color: var(--text2);
  white-space: nowrap;
}
.prod-right { text-align: right; min-width: 110px; }
.prod-cost { font-size: 18px; font-weight: 900; color: var(--gold); }
.prod-frozen { font-size: 11px; font-weight: 700; margin-top: 6px; }
.frozen-hot  { color: var(--green); }
.frozen-warm { color: var(--gold); }
.frozen-cold { color: var(--orange); }
.frozen-dead { color: var(--red); }
.ed-input {
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border-light);
  color: #fff; border-radius: var(--radius-sm);
  padding: 4px 6px;
  font-size: 13px; font-weight: 700; text-align: right;
  width: 72px; outline: none; font-family: inherit;
  font-feature-settings: "tnum" 1;
  transition: border-color 0.2s;
}
.ed-input:focus { border-color: rgba(194,157,102,0.5); }
.ed-input-sm { width: 52px; text-align: center; }
.ed-input-wide { width: 100px; text-align: left; }
input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--gold); }
.dash-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 22px;
  margin-bottom: 16px;
  position: relative;
}
.dash-section::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0;
  height: 1.5px;
  background: var(--gold-gradient);
  opacity: 0.15;
  border-radius: var(--radius-xl) var(--radius-xl) 0 0;
}
.dash-title {
  font-size: 12px; font-weight: 800; color: var(--gold);
  margin-bottom: 18px; letter-spacing: 1.5px;
  text-transform: uppercase;
}
.dash-hint { text-align: center; color: var(--text2); font-size: 13px; margin-top: 24px; padding: 20px; }

.rank-list { display: flex; flex-direction: column; gap: 5px; }
.rank-row {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 14px;
  background: rgba(255,255,255,0.02);
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  transition: all 0.2s;
}
.rank-row:hover {
  background: rgba(255,255,255,0.04);
  border-color: var(--border-light);
}
.rank-num {
  font-size: 13px; font-weight: 900;
  color: rgba(194,157,102,0.5);
  min-width: 28px; font-feature-settings: "tnum" 1;
}
.rank-name {
  flex: 1; font-size: 13px; font-weight: 700;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.rank-amt { font-size: 14px; font-weight: 800; color: var(--gold); text-align: right; }

.bar-chart { display: flex; gap: 0; height: 220px; }
.bar-y-axis { display: flex; flex-direction: column; justify-content: space-between; padding-right: 10px; min-width: 60px; text-align: right; }
.bar-y-label { font-size: 10px; color: var(--text2); font-weight: 600; }
.bar-area {
  flex: 1; display: flex; align-items: flex-end; gap: 5px;
  height: 100%; position: relative;
  border-left: 1px solid var(--border-light);
  border-bottom: 1px solid var(--border-light);
  padding-left: 4px;
}
.bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: flex-end; }
.bar-stack {
  width: 100%; max-width: 32px;
  border-radius: 4px 4px 0 0;
  overflow: hidden; position: relative;
  background: rgba(255,255,255,0.03);
  min-height: 3px;
}
.bar-seg {
  position: absolute; left: 0; right: 0;
  cursor: pointer;
  transition: height 0.4s ease;
  border-radius: 2px 2px 0 0;
}
.bar-label { font-size: 10px; color: var(--text2); font-weight: 700; margin-top: 6px; font-feature-settings: "tnum" 1; }

.person-grid { display: flex; flex-direction: column; gap: 8px; }
.person-card {
  display: grid;
  grid-template-columns: 110px 1fr 130px;
  align-items: center; gap: 14px;
  padding: 6px 0;
}
.pc-name { font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 10px; }
.pc-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pc-bar-wrap { height: 22px; background: rgba(255,255,255,0.04); border-radius: 11px; overflow: hidden; }
.pc-bar { height: 100%; border-radius: 11px; transition: width 0.6s ease; }
.pc-nums { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.pc-amt { font-size: 14px; font-weight: 800; color: var(--gold); font-feature-settings: "tnum" 1; }
.pc-pct { font-size: 12px; font-weight: 700; color: var(--text2); }

@media (max-width: 768px) {
  .person-card { grid-template-columns: 70px 1fr 80px; gap: 8px; }
  .pc-name { font-size: 12px; }
  .pc-amt { font-size: 12px; }
  .bar-chart { height: 150px; }
}
.modal-overlay {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(10,10,10,0.85); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center;
}
.modal-overlay.hidden { display: none; }
.modal-content {
  background: var(--surface);
  border: 1px solid rgba(194,157,102,0.2);
  border-radius: var(--radius-xl);
  width: 95%; max-width: 900px;
  max-height: 85vh; display: flex; flex-direction: column;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 18px 24px; border-bottom: 1px solid var(--border);
}
.modal-header h3 { font-size: 16px; color: var(--gold); font-weight: 900; }
.btn-close { background: none; border: none; color: var(--text2); font-size: 26px; cursor: pointer; transition: color 0.2s; }
.btn-close:hover { color: var(--text); }
.modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
.hidden { display: none !important; }
.report-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 18px;
}
.chart-card, .dash-section {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  padding: 18px 18px 16px;
}
.chart-card.full-span { grid-column: 1 / -1; }
.chart-title {
  font-size: 15px;
  font-weight: 900;
  color: var(--gold);
  margin-bottom: 14px;
  letter-spacing: 0.5px;
}
.chart-sub {
  color: var(--text2);
  font-size: 12px;
  margin-top: -6px;
  margin-bottom: 14px;
}
.bar-list { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: grid; grid-template-columns: 120px 1fr 78px; gap: 10px; align-items: center; }
.bar-label {
  font-size: 12px; font-weight: 800; color: var(--text);
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.bar-track {
  height: 12px; border-radius: 999px; overflow: hidden;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.04);
}
.bar-fill {
  height: 100%; border-radius: 999px;
  background: linear-gradient(90deg, rgba(194,157,102,0.85), #d4b483);
}
.bar-value { font-size: 12px; color: var(--gold); font-weight: 800; text-align: right; }
.donut-grid {
  display: grid;
  grid-template-columns: minmax(180px, 220px) 1fr;
  gap: 18px;
  align-items: center;
}
.donut-wrap { display: flex; align-items: center; justify-content: center; }
.donut {
  width: 180px; height: 180px; border-radius: 50%;
  position: relative;
  box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
}
.donut::after {
  content: attr(data-center);
  position: absolute; inset: 24px;
  border-radius: 50%;
  background: #090909;
  border: 1px solid rgba(255,255,255,0.05);
  display: flex; align-items: center; justify-content: center;
  text-align: center; white-space: pre-line;
  color: var(--text); font-size: 13px; font-weight: 900; line-height: 1.5;
  padding: 12px;
}
.legend-list { display: flex; flex-direction: column; gap: 8px; }
.legend-row {
  display: grid; grid-template-columns: 18px 1fr auto;
  gap: 10px; align-items: center;
  font-size: 12px;
}
.legend-dot { width: 10px; height: 10px; border-radius: 50%; }
.legend-name {
  color: var(--text); font-weight: 700;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.legend-val { color: var(--gold); font-weight: 800; }
.trend-wrap {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 8px; align-items: end;
  min-height: 220px; padding-top: 14px;
}
.trend-col { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.trend-value { font-size: 10px; color: var(--text2); writing-mode: vertical-rl; transform: rotate(180deg); }
.trend-bar {
  width: 100%; min-height: 8px; border-radius: 10px 10px 0 0;
  background: linear-gradient(180deg, #d4b483 0%, rgba(194,157,102,0.35) 100%);
  border: 1px solid rgba(194,157,102,0.18);
}
.trend-col.active .trend-bar {
  background: linear-gradient(180deg, #3b82f6 0%, rgba(59,130,246,0.35) 100%);
  border-color: rgba(59,130,246,0.35);
}
.trend-label { font-size: 11px; color: var(--text2); font-weight: 700; }
.insight-list { display: flex; flex-direction: column; gap: 10px; }
.insight-item {
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  font-size: 13px; line-height: 1.6;
}
@media (max-width: 768px) {
  .topbar-inner { gap: 12px; }
  .logo { font-size: 14px; }
  .kpi-row { grid-template-columns: repeat(2, 1fr); }
  .product-card { grid-template-columns: 60px 1fr; }
  .prod-right { display: none; }
  .detail-table { font-size: 12px; }
  .detail-table th, .detail-table td { padding: 8px 8px; }
  .report-grid { grid-template-columns: 1fr; }
  .donut-grid { grid-template-columns: 1fr; }
  .bar-row { grid-template-columns: 92px 1fr 62px; }
  .donut { width: 160px; height: 160px; }
  .trend-wrap { gap: 6px; }
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
          <button class="tab-btn" id="tab-reports" onclick="switchTab('reports')">戰略報表</button>
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
      <button class="btn btn-accent hidden" id="btn-save" onclick="saveEdits()">儲存修改</button>
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
        <option value="inventoryCost">依庫存金額</option>
        <option value="stockPing">依庫存坪數</option>
        <option value="daysSinceLastSale">依停滯天數</option>
        <option value="lastSaleDate">依上次銷售日</option>
        <option value="saleCount">依銷售頻率</option>
      </select>
      <button class="sort-dir-btn" id="sort-dir-btn" onclick="toggleSortDir()">↓</button>
      <span class="prod-sub-tabs" id="prod-sub-tabs">
        <button class="sub-tab active" id="sub-sleeper" onclick="switchProdTab('sleeper')">睡美人</button>
        <button class="sub-tab" id="sub-normal" onclick="switchProdTab('normal')">正常品</button>
        <button class="sub-tab" id="sub-discontinued" onclick="switchProdTab('discontinued')">不續辦</button>
      </span>
      <button class="btn btn-primary" onclick="loadProducts()">載入產品</button>
      <button class="btn btn-accent" onclick="rebuildCache()">🔄 同步銷售快取</button>
    </div>

    <div class="ctrl-bar hidden" id="ctrl-reports">
      <select id="report-year">
        <option value="2026">2026 年</option>
        <option value="2025">2025 年</option>
      </select>
      <select id="report-month">
        <option value="1">1 月</option><option value="2">2 月</option>
        <option value="3">3 月</option><option value="4">4 月</option>
        <option value="5">5 月</option><option value="6">6 月</option>
        <option value="7">7 月</option><option value="8">8 月</option>
        <option value="9">9 月</option><option value="10">10 月</option>
        <option value="11">11 月</option><option value="12">12 月</option>
      </select>
      <button class="btn btn-primary" onclick="loadStrategyReport()">載入報表</button>
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
document.getElementById('report-month').value = currentMonth + 1;
document.getElementById('report-year').value = currentYear;
loadDashboard();
loadSalesList();

function showLoading(show) {
  document.getElementById('loading').classList.toggle('hidden', !show);
}

function toast(msg, isError) {
  var t = document.createElement('div');
  t.textContent = msg;
var bg = isError ? '#ef4444' : '#0a0a0a';
  t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:' + bg + ';border:2px solid ' + (isError ? '#ef4444' : 'var(--gold)') + ';color:#fff;padding:14px 28px;border-radius:10px;font-weight:700;z-index:1000;box-shadow:0 0 15px rgba(194,157,102,0.3);cursor:pointer;max-width:90vw;text-align:center;line-height:1.4;';
  t.onclick = function() { t.remove(); };
  document.body.appendChild(t);
  setTimeout(function(){ t.remove(); }, isError ? 12000 : 5000);
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
function driveUrlToDirect(url) {
  var m = url.match(/\/file\/d\/([^\/]+)/);
  return m ? 'https://drive.google.com/uc?export=view&id=' + m[1] : url;
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
        '<th class="text-right">成本</th><th class="text-right">金額</th><th class="text-center">等級</th>' +
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
        '<td class="text-right text-gold">$' + (parseFloat(r['成本']) || 0) + '</td>' +
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

function fmt(n) { n = n || 0; return (n / 10000).toFixed(1) + '萬'; }
function fmtNum(n) { return isNaN(n) ? '0萬' : (n / 10000).toFixed(1) + '萬'; }

var currentTab = 'bonus';
var currentProdTab = 'sleeper';
var sortDir = -1;
window._strategyReport = null;
function toggleSortDir() {
  sortDir = sortDir === -1 ? 1 : -1;
  document.getElementById('sort-dir-btn').textContent = sortDir === -1 ? '↓' : '↑';
  renderProducts();
}
window._sleeperData = null;
window._normalData = null;
window._disconProducts = null;
function switchTab(tab) {
  currentTab = tab;
  document.getElementById('tab-bonus').classList.toggle('active', tab === 'bonus');
  document.getElementById('tab-products').classList.toggle('active', tab === 'products');
  document.getElementById('tab-reports').classList.toggle('active', tab === 'reports');
  document.getElementById('ctrl-bonus').classList.toggle('hidden', tab !== 'bonus');
  document.getElementById('ctrl-products').classList.toggle('hidden', tab !== 'products');
  document.getElementById('ctrl-reports').classList.toggle('hidden', tab !== 'reports');
  if (tab === 'products') {
    var el = document.getElementById('filter-grade');
    el.style.display = currentProdTab === 'sleeper' ? '' : 'none';
    if (!window._sleeperData) loadProducts();
    else renderProducts();
  } else if (tab === 'reports') {
    if (!window._strategyReport) loadStrategyReport();
    else renderStrategyReport(window._strategyReport);
  }
}
function switchProdTab(tab) {
  currentProdTab = tab;
  document.getElementById('sub-sleeper').classList.toggle('active', tab === 'sleeper');
  document.getElementById('sub-normal').classList.toggle('active', tab === 'normal');
  document.getElementById('sub-discontinued').classList.toggle('active', tab === 'discontinued');
  document.getElementById('filter-grade').style.display = tab === 'sleeper' ? '' : 'none';
  renderProducts();
}

function loadProducts() {
  showLoading(true);
  var total = 3, loaded = 0, failed = false;
  function checkDone() {
    loaded++;
    if (loaded === total) { showLoading(false); if (!failed) renderProducts(); }
  }
  apiGet('products', function(res) {
    if (res.success) window._sleeperData = res.data;
    else failed = true;
    checkDone();
  }, function() { failed = true; checkDone(); });
  apiGet('normal-products', function(res) {
    if (res.success) window._normalData = res.data;
    else failed = true;
    checkDone();
  }, function() { failed = true; checkDone(); });
  apiGet('discontinued-products', function(res) {
    if (res.success) window._disconProducts = res.data;
    else failed = true;
    checkDone();
  }, function() { failed = true; checkDone(); });
}

function renderProducts() {
  var data;
  var subtitle;
  if (currentProdTab === 'sleeper') { data = window._sleeperData; subtitle = '睡美人'; }
  else if (currentProdTab === 'normal') { data = window._normalData; subtitle = '正常品'; }
  else { data = window._disconProducts; subtitle = '不續辦'; }

  if (!data) { document.getElementById('main-content').innerHTML = '<div class="welcome" style="padding:40px;text-align:center;color:var(--text2)">載入中...</div>'; return; }

  var gradeFilter = document.getElementById('filter-grade').value;
  var sortBy = document.getElementById('sort-by').value;

  var list = data.filter(function(p) {
    if (currentProdTab === 'sleeper' && gradeFilter && p.grade !== gradeFilter) return false;
    return p.stockPing >= 3;
  });

  list = list.slice().sort(function(a, b) {
    var cmp = 0;
    if (sortBy === 'inventoryCost') cmp = a.inventoryCost - b.inventoryCost;
    else if (sortBy === 'stockPing') cmp = a.stockPing - b.stockPing;
    else if (sortBy === 'lastSaleDate') {
      var da = a.daysSinceLastSale === null ? -1 : a.daysSinceLastSale;
      var db = b.daysSinceLastSale === null ? -1 : b.daysSinceLastSale;
      cmp = da - db;
    } else if (sortBy === 'saleCount') {
      var ca = (a.buyers && a.buyers.length) || 0;
      var cb = (b.buyers && b.buyers.length) || 0;
      cmp = ca - cb;
    } else if (sortBy === 'daysSinceLastSale') {
      var da = a.daysSinceLastSale === null ? 99999 : a.daysSinceLastSale;
      var db = b.daysSinceLastSale === null ? 99999 : b.daysSinceLastSale;
      cmp = da - db;
    } else {
      cmp = a.totalPings - b.totalPings;
    }
    return cmp * sortDir;
  });

  var totalCost = list.reduce(function(s, p) { return s + p.inventoryCost; }, 0);
  var totalPings = list.reduce(function(s, p) { return s + p.totalPings; }, 0);
  var neverSold = list.filter(function(p) { return p.daysSinceLastSale === null; }).length;
  var frozen = list.filter(function(p) { return p.daysSinceLastSale !== null && p.daysSinceLastSale > 180; }).length;

  var html = '<div class="kpi-row">' +
    '<div class="kpi-card kpi-blue"><div class="label">產品數</div><div class="value">' + list.length + '</div><div class="sub">' + subtitle + ' SKU</div></div>' +
    '<div class="kpi-card kpi-gold"><div class="label">庫存佔用成本</div><div class="value">' + fmt(totalCost) + '</div><div class="sub">元</div></div>' +
    '<div class="kpi-card kpi-green"><div class="label">歷史銷售</div><div class="value">' + Math.round(totalPings) + '</div><div class="sub">坪</div></div>' +
    '<div class="kpi-card" style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;text-align:center"><div class="label" style="font-size:11px;color:var(--text2);font-weight:700;margin-bottom:6px">停滯 180天+</div><div class="value" style="font-size:26px;font-weight:900;color:var(--red)">' + (frozen + neverSold) + '</div><div class="sub" style="font-size:11px;color:var(--text2);margin-top:4px">支</div></div>' +
    '</div>';

  html += '<div class="product-list">';
  list.forEach(function(p) {
    var frozenClass, frozenText;
    if (p.daysSinceLastSale === null) {
      frozenClass = 'frozen-dead'; frozenText = '從未銷售';
    } else if (p.daysSinceLastSale > 365) {
      frozenClass = 'frozen-dead'; frozenText = p.daysSinceLastSale + ' 天未售';
    } else if (p.daysSinceLastSale > 180) {
      frozenClass = 'frozen-cold'; frozenText = p.daysSinceLastSale + ' 天未售';
    } else if (p.daysSinceLastSale > 90) {
      frozenClass = 'frozen-warm'; frozenText = p.daysSinceLastSale + ' 天未售';
    } else {
      frozenClass = 'frozen-hot'; frozenText = p.daysSinceLastSale + ' 天前';
    }

    var buyerHtml = '';
    if (p.buyers && p.buyers.length > 0) {
      buyerHtml = '<div class="prod-buyers"><div class="ps-label">歷史買家</div><div class="buyer-chips">' +
        p.buyers.map(function(b) {
          return '<span class="buyer-chip">' + shortCust(b.name) + ' ' + b.pings + '坪</span>';
        }).join('') + '</div></div>';
    }

    var mosText = p.mos === 888 ? '無銷售' : (p.mos || 0) + ' 月';
    var mosClass = p.mosLevel === 2 ? 'text-red' : (p.mosLevel === 1 ? 'text-gold' : 'text-green');

    var gradeHtml = p.grade ? '<div class="grade-badge grade-' + p.grade + '">' + p.grade + '</div>' : '';
    html += '<div class="product-card">' +
      '<div class="prod-grade">' + gradeHtml +
        '<div style="font-size:11px;color:var(--text2);margin-top:6px">' + p.perPing + '片/坪</div>' +
      '</div>' +
      '<div class="prod-info">' +
        '<div class="prod-title">' + (p.series || '未分類') + '</div>' +
        '<div class="prod-sku">' + p.sku + '</div>' +
        '<div class="prod-stats">' +
          '<div class="prod-stat"><div class="ps-label">歷史銷售</div><div class="ps-value">' + p.totalPings + ' 坪</div></div>' +
          '<div class="prod-stat"><div class="ps-label">目前陳列</div><div class="ps-value text-gold" style="cursor:pointer; text-decoration:underline;" onclick="showDisplayDetails(\'' + p.sku + '\')">🖼️ ' + (p.displayCount || 0) + ' 家</div></div>' +
          '<div class="prod-stat"><div class="ps-label">庫存</div><div class="ps-value">' + p.stockPing + ' 坪</div></div>' +
          '<div class="prod-stat"><div class="ps-label">成本/片</div><div class="ps-value text-gold">$' + p.costPerPiece + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">去化月數 (MOS)</div><div class="ps-value ' + mosClass + '">' + mosText + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">最後銷售</div><div class="ps-value">' + p.lastSaleStr + '</div></div>' +
        '</div>' +
        buyerHtml +
      '</div>' +
      '<div class="prod-right">' +
        '<div class="label" style="font-size:11px;color:var(--text2);font-weight:700;margin-bottom:4px">庫存佔用成本</div>' +
        '<div class="prod-cost">' + fmt(p.inventoryCost) + '</div>' +
        '<div class="prod-frozen ' + frozenClass + '">' + (p.stagnantReason || frozenText) + '</div>' +
        (p.action ? '<div class="action-badge" style="background:' + p.actionColor + '15; border:1px solid ' + p.actionColor + '30; color:' + p.actionColor + '; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:800; display:inline-block; margin-top:6px">' + p.action + '</div>' : '') +
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
  apiGet('discontinued-products', {}, function(res) {
    if (res.success) window._disconProducts = res.data;
    if (window._disconProducts && window._disconProducts.length) renderDiscontinued();
  });
  apiGet('products', {}, function(res) {
    if (res.success) window._sleeperData = res.data;
  });
  apiGet('normal-products', {}, function(res) {
    if (res.success) window._normalData = res.data;
  });
}

function renderDiscontinued() {
  var list = window._disconProducts || [];
  var totalCost = list.reduce(function(s, p) { return s + p.inventoryCost; }, 0);
  var totalPings = list.reduce(function(s, p) { return s + p.totalPings; }, 0);
  var neverSold = list.filter(function(p) { return p.daysSinceLastSale === null; }).length;
  var html = '<div class="dash-section" id="discon-section"><div class="dash-title">不續辦產品 — 銷售狀況</div>' +
    '<div class="kpi-row" style="margin-bottom:16px">' +
    '<div class="kpi-card kpi-blue"><div class="label">產品數</div><div class="value">' + list.length + '</div><div class="sub">支 SKU</div></div>' +
    '<div class="kpi-card kpi-gold"><div class="label">庫存佔用成本</div><div class="value">' + fmt(totalCost) + '</div><div class="sub">元</div></div>' +
    '<div class="kpi-card kpi-green"><div class="label">歷史銷售</div><div class="value">' + Math.round(totalPings) + '</div><div class="sub">坪</div></div>' +
    '<div class="kpi-card" style="background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;text-align:center"><div class="label" style="font-size:11px;color:var(--text2);font-weight:700;margin-bottom:6px">從未銷售</div><div class="value" style="font-size:26px;font-weight:900;color:var(--red)">' + neverSold + '</div><div class="sub" style="font-size:11px;color:var(--text2);margin-top:4px">支</div></div>' +
    '</div><div class="product-list">';
  list.forEach(function(p) {
    var frozenClass, frozenText;
    if (p.daysSinceLastSale === null) {
      frozenClass = 'frozen-dead'; frozenText = '從未銷售';
    } else if (p.daysSinceLastSale > 365) {
      frozenClass = 'frozen-dead'; frozenText = p.daysSinceLastSale + ' 天未售';
    } else if (p.daysSinceLastSale > 180) {
      frozenClass = 'frozen-cold'; frozenText = p.daysSinceLastSale + ' 天未售';
    } else if (p.daysSinceLastSale > 90) {
      frozenClass = 'frozen-warm'; frozenText = p.daysSinceLastSale + ' 天未售';
    } else {
      frozenClass = 'frozen-hot'; frozenText = p.daysSinceLastSale + ' 天前';
    }
    var buyerHtml = '';
    if (p.buyers && p.buyers.length > 0) {
      buyerHtml = '<div class="prod-buyers"><div class="ps-label">歷史買家</div><div class="buyer-chips">' +
        p.buyers.map(function(b) {
          return '<span class="buyer-chip">' + shortCust(b.name) + ' ' + b.pings + '坪</span>';
        }).join('') + '</div></div>';
    }
    html += '<div class="product-card">' +
      '<div class="prod-grade">' +
      (p.imageUrl ? '<img src="' + driveUrlToDirect(p.imageUrl) + '" alt="" style="width:60px;height:60px;object-fit:contain;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid var(--border-light)" onerror="this.style.display=\'none\'">' : '') +
      '</div>' +
      '<div class="prod-info">' +
      '<div class="prod-title">' + (p.series || '未分類') + '</div>' +
      '<div class="prod-sku">' + p.sku + '</div>' +
      '<div class="prod-stats">' +
      '<div class="prod-stat"><div class="ps-label">歷史銷售</div><div class="ps-value">' + p.totalPings + ' 坪</div></div>' +
      '<div class="prod-stat"><div class="ps-label">庫存</div><div class="ps-value">' + p.stockPing + ' 坪</div></div>' +
      '<div class="prod-stat"><div class="ps-label">成本/片</div><div class="ps-value text-gold">$' + p.costPerPiece + '</div></div>' +
      '<div class="prod-stat"><div class="ps-label">最後銷售</div><div class="ps-value">' + p.lastSaleStr + '</div></div>' +
      '</div>' + buyerHtml +
      '</div>' +
      '<div class="prod-right">' +
      '<div class="label" style="font-size:11px;color:var(--text2);font-weight:700;margin-bottom:4px">庫存佔用成本</div>' +
      '<div class="prod-cost">' + fmt(p.inventoryCost) + '</div>' +
      '<div class="prod-frozen ' + frozenClass + '">' + frozenText + '</div>' +
      '</div></div>';
  });
  html += '</div></div>';
  document.getElementById('main-content').insertAdjacentHTML('beforeend', html);
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
    '<div class="kpi-card' + (rate >= 100 ? ' kpi-green' : ' kpi-gold') + '"><div class="label">目標 300 萬</div><div class="value">' + rate + '%</div><div class="sub">達成率</div></div>' +
    '<div class="kpi-card kpi-blue"><div class="label">已過月數</div><div class="value">' + (months.length ? months[months.length-1].month : '-') + '</div><div class="sub">月份</div></div>' +
    '<div class="kpi-card ' + (target - grandTotal > 0 ? 'kpi-red' : 'kpi-green') + '"><div class="label">目標差距</div><div class="value">' + fmt(Math.max(0, target - grandTotal)) + '</div><div class="sub">' + (target - grandTotal > 0 ? '尚差' : '已達成') + '</div></div>' +
    '</div>';

  // 長條圖（由下往上）
  var maxTotal = d.maxMonthTotal || 1;
  var barChartHtml = '<div class="dash-section"><div class="dash-title">逐月業績長條圖</div><div class="bar-chart"><div class="bar-y-axis">';
  var ySteps = [0, 25, 50, 75, 100];
  for (var yi = ySteps.length - 1; yi >= 0; yi--) {
    barChartHtml += '<div class="bar-y-label">' + fmtNum(Math.round(maxTotal * ySteps[yi] / 100)) + '</div>';
  }
  barChartHtml += '</div><div class="bar-area"><div class="bar-baseline"></div>';
  months.forEach(function(m) {
    var barH = m.total > 0 ? Math.max(m.total / maxTotal * 100, 3) : 0;
    barChartHtml += '<div class="bar-col"><div class="bar-stack" style="height:' + barH + '%">';
    var sortedPeople = Object.keys(m.people).sort(function(a, b) { return m.people[b] - m.people[a]; });
    var cumPct = 0;
    sortedPeople.forEach(function(n) {
      var segPct = m.total > 0 ? m.people[n] / m.total * 100 : 0;
      var segH = segPct;
      barChartHtml += '<div class="bar-seg" style="height:' + segH + '%;bottom:' + cumPct + '%;background:' + colorMap[n] + '" title="' + n + ': ' + fmt(m.people[n]) + '"></div>';
      cumPct += segPct;
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

  // 前 10 大客戶
    if (d.topCustomers && d.topCustomers.length) {
    html += '<div class="dash-section"><div class="dash-title">前 10 大客戶（銷售金額）</div><div class="rank-list">';
    d.topCustomers.forEach(function(c, i) {
      html += '<div class="rank-row" style="cursor:pointer" onclick="showCustomerDetail(\'' + c.fullName.replace(/'/g, "\\'") + '\')"><span class="rank-num">' + (i+1) + '</span><span class="rank-name">' + c.name + ' <span style="font-size:11px;color:var(--purple)">' + c.series + '</span></span><span class="rank-amt">' + c.totalWan + '萬 <span style="font-size:11px;color:var(--gold)">' + c.pct + '%</span></span></div>';
    });
    html += '</div></div>';
    }

  // 前 10 大產品
  if (d.topProducts && d.topProducts.length) {
    html += '<div class="dash-section"><div class="dash-title">前 10 大產品（系列 + 編號 + 金額）</div><div class="rank-list">';
    d.topProducts.forEach(function(p, i) {
      html += '<div class="rank-row"><span class="rank-num">' + (i+1) + '</span><span class="rank-name">' + p.series + ' <span class="text-purple" style="font-size:11px">' + p.sku + '</span></span><span class="rank-amt">' + fmt(Math.round(p.amt)) + '</span></div>';
    });
    html += '</div></div>';
  }

  // 不續辦統計
  if (d.discontStats) {
    html += '<div class="dash-section"><div class="dash-title">不續辦產品</div>';
    html += '<div class="kpi-row" style="margin-bottom:12px">' +
      '<div class="kpi-card kpi-blue"><div class="label">不續辦品項</div><div class="value">' + (d.discontStats.discCount || 0) + '</div><div class="sub">支 SKU</div></div>' +
    '</div>';
    if (d.discontStats.missingSleeper && d.discontStats.missingSleeper.length) {
      html += '<div style="padding:12px;background:rgba(194,157,102,0.08);border-radius:10px;font-size:12px;color:var(--gold)">編號價目表標記為「睡美人」但睡美人工作表缺少的 SKU (' + d.discontStats.missingSleeper.length + ' 支)：' +
        d.discontStats.missingSleeper.slice(0, 5).map(function(s) { return s.sku + '(' + s.series + ')'; }).join('、') +
        (d.discontStats.missingSleeper.length > 5 ? '…等' + d.discontStats.missingSleeper.length + ' 支' : '') +
      '</div>';
    }
    html += '</div>';
  }

  html += '<div class="dash-hint">選擇月份後按「查詢」查看明細，或切換「產品總覽」</div>';

  document.getElementById('main-content').innerHTML = html;
}

function loadStrategyReport() {
  var year = parseInt(document.getElementById('report-year').value, 10);
  var month = parseInt(document.getElementById('report-month').value, 10);
  showLoading(true);
  apiGet('strategy-report', { year: year, month: month }, function(res) {
    showLoading(false);
    if (!res.success) {
      toast(res.msg || '載入報表失敗', true);
      return;
    }
    window._strategyReport = res.data;
    renderStrategyReport(res.data);
  }, function(err) {
    showLoading(false);
    toast('載入報表失敗: ' + err, true);
  });
}

function fmtPct(n) {
  return (Math.round((n || 0) * 10) / 10).toFixed(1) + '%';
}

function ellipsis(s, n) {
  s = String(s || '');
  return s.length > n ? s.slice(0, n) + '…' : s;
}

function buildBarRows(items, key, valueFormatter) {
  if (!items || !items.length) return '<div class="chart-sub">本月尚無資料</div>';
  var max = items[0].amount || 1;
  return '<div class="bar-list">' + items.map(function(item) {
    var width = max > 0 ? Math.max(6, Math.round((item.amount || 0) / max * 100)) : 0;
    return '<div class="bar-row">' +
      '<div class="bar-label">' + ellipsis(item[key], 12) + '</div>' +
      '<div class="bar-track"><div class="bar-fill" style="width:' + width + '%"></div></div>' +
      '<div class="bar-value">' + valueFormatter(item.amount || 0) + '</div>' +
      '</div>';
  }).join('') + '</div>';
}

function buildDonutCard(title, items, key, centerLabel) {
  if (!items || !items.length) {
    return '<div class="chart-card"><div class="chart-title">' + title + '</div><div class="chart-sub">本月尚無資料</div></div>';
  }
  var total = items.reduce(function(sum, item) { return sum + (item.amount || 0); }, 0) || 1;
  var start = 0;
  var gradients = [];
  var legends = [];
  items.forEach(function(item, idx) {
    var pct = (item.amount || 0) / total * 100;
    var end = start + pct;
    var color = DASHBOARD_COLORS[idx % DASHBOARD_COLORS.length];
    gradients.push(color + ' ' + start.toFixed(2) + '% ' + end.toFixed(2) + '%');
    legends.push(
      '<div class="legend-row">' +
      '<span class="legend-dot" style="background:' + color + '"></span>' +
      '<span class="legend-name">' + ellipsis(item[key], 16) + '</span>' +
      '<span class="legend-val">' + fmtPct(pct) + '</span>' +
      '</div>'
    );
    start = end;
  });
  return '<div class="chart-card">' +
    '<div class="chart-title">' + title + '</div>' +
    '<div class="donut-grid">' +
      '<div class="donut-wrap"><div class="donut" data-center="' + centerLabel.replace(/\n/g, '&#10;') + '" style="background:conic-gradient(' + gradients.join(',') + ')"></div></div>' +
      '<div class="legend-list">' + legends.join('') + '</div>' +
    '</div>' +
  '</div>';
}

function buildTrendChart(monthTrend, activeMonth) {
  var max = 1;
  for (var i = 1; i <= 12; i++) max = Math.max(max, monthTrend[i] || 0);
  var html = '<div class="trend-wrap">';
  for (var m = 1; m <= 12; m++) {
    var val = monthTrend[m] || 0;
    var h = Math.max(8, Math.round(val / max * 150));
    html += '<div class="trend-col' + (m === activeMonth ? ' active' : '') + '">' +
      '<div class="trend-value">' + (val > 0 ? fmt(val) : '0萬') + '</div>' +
      '<div class="trend-bar" style="height:' + h + 'px"></div>' +
      '<div class="trend-label">' + m + '月</div>' +
      '</div>';
  }
  html += '</div>';
  return html;
}

function renderStrategyReport(d) {
  var s = d.summary || {};
  var topSales = d.topSales || [];
  var topCustomers = d.topCustomers || [];
  var topProducts = d.topProducts || [];
  var topSeries = d.topSeries || [];
  var topSalesDonut = topSales.slice(0, 5);
  if (topSales.length > 5) {
    var otherAmt = topSales.slice(5).reduce(function(sum, item) { return sum + (item.amount || 0); }, 0);
    if (otherAmt > 0) topSalesDonut.push({ name: '其他', amount: otherAmt });
  }

  var html = '<div class="kpi-row">' +
    '<div class="kpi-card kpi-gold"><div class="label">本月銷售額</div><div class="value">' + fmt(s.monthTotal || 0) + '</div><div class="sub">' + d.year + ' / ' + d.month + '</div></div>' +
    '<div class="kpi-card kpi-green"><div class="label">本月銷售坪數</div><div class="value">' + (s.monthPings || 0) + '</div><div class="sub">坪</div></div>' +
    '<div class="kpi-card kpi-blue"><div class="label">交易筆數</div><div class="value">' + (s.monthTxCount || 0) + '</div><div class="sub">筆</div></div>' +
    '<div class="kpi-card kpi-red"><div class="label">前 3 業務占比</div><div class="value">' + fmtPct(s.top3SalesPct || 0) + '</div><div class="sub">集中度</div></div>' +
    '</div>';

  html += '<div class="report-grid">';
  html += '<div class="chart-card"><div class="chart-title">業務月排行</div><div class="chart-sub">看誰真的在出貨，不看感覺。</div>' + buildBarRows(topSales, 'name', fmt) + '</div>';
  html += buildDonutCard('業務占比', topSalesDonut, 'name', '本月占比\n' + fmt(s.monthTotal || 0));
  html += '<div class="chart-card"><div class="chart-title">前 10 大客戶</div><div class="chart-sub">看客戶集中風險。</div>' + buildBarRows(topCustomers, 'name', fmt) + '</div>';
  html += '<div class="chart-card"><div class="chart-title">前 10 大產品</div><div class="chart-sub">看哪個 SKU 在拉動銷售。</div>' + buildBarRows(topProducts.map(function(item) {
    return { name: item.sku, amount: item.amount };
  }), 'name', fmt) + '</div>';
  html += buildDonutCard('系列占比', topSeries, 'name', '系列結構\n' + topSeries.length + ' 類');
  html += '<div class="chart-card"><div class="chart-title">年度月趨勢</div><div class="chart-sub">判斷本月是高峰、平穩，還是掉速。</div>' + buildTrendChart(d.monthTrend || {}, d.month) + '</div>';
  html += '<div class="chart-card full-span"><div class="chart-title">管理提示</div><div class="insight-list">' +
    '<div class="insight-item">' + (d.insights && d.insights.leader ? d.insights.leader : '本月尚無業務資料。') + '</div>' +
    '<div class="insight-item">' + (d.insights && d.insights.concentration ? d.insights.concentration : '尚無集中度資料。') + '</div>' +
    '<div class="insight-item">' + (d.insights && d.insights.customer ? d.insights.customer : '尚無客戶分析資料。') + '</div>' +
    '<div class="insight-item">' + (d.insights && d.insights.product ? d.insights.product : '尚無產品分析資料。') + '</div>' +
    '</div></div>';
  html += '</div>';

  document.getElementById('main-content').innerHTML = html;
}

function showCustomerDetail(customer) {
  showLoading(true);
  var year = currentYear;
  apiGet('customer-detail', { customer: customer, year: year }, function(res) {
    showLoading(false);
    if (!res.success || !res.data.length) { toast('無此客戶資料'); return; }
    var html = '<div style="padding:8px 0 14px;font-size:13px;color:var(--text2)">共 ' + res.data.length + ' 筆交易</div>' +
      '<div class="table-wrap" style="max-height:400px;overflow-y:auto"><table class="detail-table"><thead><tr>' +
      '<th>日期</th><th>業務</th><th>編號</th><th>系列</th><th class="text-right">數量</th><th class="text-right">金額</th><th>備註</th>' +
      '</tr></thead><tbody>';
    res.data.forEach(function(r) {
      html += '<tr>' +
        '<td>' + r.date + '</td>' +
        '<td>' + r.sales + '</td>' +
        '<td class="text-purple" style="font-size:11px">' + r.sku + '</td>' +
        '<td style="font-size:11px">' + r.series + '</td>' +
        '<td class="text-right">' + r.qty + '</td>' +
        '<td class="text-right text-gold">' + fmt(r.amt) + '</td>' +
        '<td style="font-size:11px;color:var(--text2)">' + (r.note || '') + '</td></tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('modal-title').textContent = '客戶明細 — ' + customer;
    document.getElementById('modal-body').innerHTML = html;
    document.getElementById('modal-detail').classList.remove('hidden');
  }, function(err) { showLoading(false); toast('查詢失敗: ' + err); });
}

function rebuildCache() {
  showLoading(true);
  apiPost('rebuild-cache', {}, function(res) {
    showLoading(false);
    if (res.success) {
      toast('同步快取成功！歷史年度：' + res.years.join(', ') + '，已快取 ' + res.cacheRows + ' 筆資料。');
      loadProducts();
    } else {
      toast('快取同步失敗: ' + res.error, true);
    }
  }, function(err) {
    showLoading(false);
    toast('連線失敗: ' + err, true);
  });
}

function showDisplayDetails(sku) {
  var data;
  if (currentProdTab === 'sleeper') data = window._sleeperData;
  else if (currentProdTab === 'normal') data = window._normalData;
  else data = window._disconProducts;

  if (!data) return;
  var p = data.find(function(item) { return item.sku === sku; });
  if (!p || !p.displays || p.displays.length === 0) {
    toast('該品項目前無上架陳列樣板。');
    return;
  }

  var title = document.getElementById('modal-title');
  var body = document.getElementById('modal-body');
  
  title.textContent = sku + ' 目前陳列樣板明細 (' + p.displays.length + ' 家)';
  
  var html = '<div style="display:flex; flex-direction:column; gap:16px;">';
  p.displays.forEach(function(d) {
    html += '<div style="display:flex; gap:16px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:12px; padding:12px; align-items:center;">';
    if (d.photoUrl) {
      html += '<div style="width:120px; height:90px; border-radius:8px; overflow:hidden; border:1px solid rgba(255,255,255,0.1); flex-shrink:0;">' +
        '<img src="' + d.photoUrl + '" style="width:100%; height:100%; object-fit:cover; cursor:pointer;" onclick="window.open(\'' + d.photoUrl + '\')">' +
        '</div>';
    } else {
      html += '<div style="width:120px; height:90px; border-radius:8px; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; color:var(--text2); font-size:11px; flex-shrink:0;">無照片</div>';
    }
    html += '<div style="display:flex; flex-direction:column; justify-content:center; gap:6px;">' +
      '<div style="font-size:16px; font-weight:800; color:var(--gold);">' + d.cust + '</div>' +
      '<div style="font-size:12px; color:var(--text2);">上架日期：' + (d.date || '未記錄') + '</div>' +
      '</div>' +
      '</div>';
  });
  html += '</div>';
  
  body.innerHTML = html;
  document.getElementById('modal-detail').classList.remove('hidden');
}

function closeDetail() {
  document.getElementById('modal-detail').classList.add('hidden');
}

</script>

</body>
</html>
