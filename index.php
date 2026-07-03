<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>高雅瓷戰情室</title>
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
  padding: 12px 16px;
  display: grid;
  grid-template-columns: 56px 1fr;
  gap: 0 14px; align-items: start;
  transition: all 0.25s;
}
.prod-summary-row {
  display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px;
}
.prod-summary-main { flex: 1 1 100%; min-width: 0; }
.prod-summary-stats { display: flex; flex-wrap: wrap; gap: 16px; flex: 1 1 100%; }
.prod-detail-toggle { margin-top: 6px; }
.prod-detail-toggle > summary { color: var(--gold); }
@media (min-width: 600px) {
  .prod-summary-main { flex: 0 1 auto; }
  .prod-summary-stats { flex: 1; justify-content: flex-end; }
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
.mode-tabs { display: inline-flex; gap: 4px; }
.mode-tab {
  height: 30px; padding: 0 12px; border-radius: 999px;
  font-size: 12px; font-weight: 800; letter-spacing: 0.4px;
  border: 1px solid var(--border-light); background: rgba(255,255,255,0.03); color: var(--text2);
  cursor: pointer;
}
.mode-tab.active {
  background: var(--gold-soft);
  border-color: rgba(194,157,102,0.35);
  color: var(--gold);
}
.bar-list { display: flex; flex-direction: column; gap: 10px; }
.bar-row { display: grid; grid-template-columns: 120px 1fr 78px; gap: 10px; align-items: center; }
.bar-row.delta { grid-template-columns: 140px 1fr 92px; }
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
.bar-fill.pos { background: linear-gradient(90deg, rgba(34,197,94,0.85), #86efac); }
.bar-fill.neg { background: linear-gradient(90deg, rgba(239,68,68,0.85), #fca5a5); }
.bar-value { font-size: 12px; color: var(--gold); font-weight: 800; text-align: right; }
.compare-strip {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 18px;
}
.compare-card {
  padding: 14px 16px;
  border-radius: var(--radius-lg);
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
}
.compare-label { font-size: 11px; color: var(--text2); font-weight: 800; letter-spacing: 0.8px; margin-bottom: 6px; }
.compare-value { font-size: 20px; font-weight: 900; margin-bottom: 4px; }
.compare-sub { font-size: 12px; color: var(--text2); }
.delta-up { color: var(--green); }
.delta-down { color: var(--red); }
.delta-flat { color: var(--text2); }
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
  .product-card { grid-template-columns: 48px 1fr; }
  .detail-table { font-size: 12px; }
  .detail-table th, .detail-table td { padding: 8px 8px; }
  .report-grid { grid-template-columns: 1fr; }
  .donut-grid { grid-template-columns: 1fr; }
  .bar-row { grid-template-columns: 92px 1fr 62px; }
  .bar-row.delta { grid-template-columns: 90px 1fr 70px; }
  .donut { width: 160px; height: 160px; }
  .trend-wrap { gap: 6px; }
  .compare-strip { grid-template-columns: 1fr; }
}
  </style>
</head>
<body>
  <div class="app">
    <header class="topbar">
      <div class="topbar-inner">
        <img src="logo.svg" alt="eliTile" class="top-logo" onclick="loadDashboardHome()" style="cursor:pointer">
        <h1 class="logo" onclick="loadDashboardHome()" style="cursor:pointer">高雅瓷戰情室</h1>
        <div class="tab-bar">
          <button class="tab-btn active" id="tab-products" onclick="switchTab('products')">產品總覽</button>
          <button class="tab-btn" id="tab-reports" onclick="switchTab('reports')">銷售報表</button>
          <button class="tab-btn" id="tab-bonus" onclick="switchTab('bonus')">睡美人銷售</button>
          <button class="tab-btn" id="tab-customers" onclick="switchTab('customers')">客戶分析</button>
          <button class="tab-btn" id="tab-analysis" onclick="switchTab('analysis')">新品分析 <span style="font-size:10px;color:var(--text2);margin-left:4px;cursor:pointer;text-decoration:underline" onclick="event.stopPropagation();window.open('analysis.php','_blank')">新視窗</span></button>
          <button class="tab-btn" id="tab-reps" onclick="switchTab('reps')">業務分析 <span style="font-size:10px;color:var(--text2);margin-left:4px;cursor:pointer;text-decoration:underline" onclick="event.stopPropagation();window.open('reps.php','_blank')">新視窗</span></button>
          <button class="tab-btn" id="tab-mgr" onclick="switchTab('mgr')">主管報告 <span style="font-size:10px;color:var(--text2);margin-left:4px;cursor:pointer;text-decoration:underline" onclick="event.stopPropagation();window.open('group_meeting.php','_blank')">新視窗</span></button>
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
      <input type="text" id="filter-keyword" placeholder="搜尋編號/系列/尺寸" oninput="renderProducts()" style="height:34px;padding:0 10px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:13px;min-width:140px">
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
        <button class="sub-tab active" id="sub-normal" onclick="switchProdTab('normal')">正常品</button>
        <button class="sub-tab" id="sub-sleeper" onclick="switchProdTab('sleeper')">睡美人</button>
        <button class="sub-tab" id="sub-discontinued" onclick="switchProdTab('discontinued')">不續辦</button>
      </span>
      <button class="btn btn-primary" onclick="loadProducts()">載入產品</button>
      <button class="btn btn-accent" onclick="rebuildCache()">🔄 同步銷售快取</button>
      <button class="btn btn-ghost" id="btn-restock-advisor" style="display:none" onclick="loadRestockAdvisor()">🤖 補貨建議</button>
    </div>

    <div class="ctrl-bar hidden" id="ctrl-reports">
      <div class="mode-tabs">
        <button class="mode-tab active" id="mode-month" onclick="setReportMode('month')">月</button>
        <button class="mode-tab" id="mode-quarter" onclick="setReportMode('quarter')">季</button>
        <button class="mode-tab" id="mode-half" onclick="setReportMode('half')">半年</button>
        <button class="mode-tab" id="mode-year" onclick="setReportMode('year')">年</button>
      </div>
      <select id="report-year">
        <option value="2026">2026 年</option>
        <option value="2025">2025 年</option>
      </select>
      <select id="report-period"></select>
      <button class="btn btn-primary" onclick="loadStrategyReport()">載入報表</button>
      <button class="btn btn-accent" onclick="rebuildCache()">🔄 同步快取</button>
      <span id="cache-info-reports" style="font-size:11px;color:var(--text2);margin-left:4px"></span>
      <button class="btn btn-ghost" onclick="window.open('meeting.php', '_blank')">月會模式</button>
      <button class="btn btn-ghost" style="color:var(--gold);" onclick="window.open('group_meeting.php', '_blank')">集團比較</button>
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
var reportMode = 'month';

document.getElementById('filter-month').value = currentMonth;
document.getElementById('filter-year').value = currentYear;
document.getElementById('report-year').value = currentYear;
updateReportPeriodOptions();

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
  var s = String(url || '');
  var m = s.match(/\/file\/d\/([^\/]+)/);
  if (m) return 'https://drive.google.com/thumbnail?id=' + m[1] + '&sz=w1200';
  m = s.match(/[?&]id=([^&]+)/);
  if (m) return 'https://drive.google.com/thumbnail?id=' + m[1] + '&sz=w1200';
  return s;
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

function fmt(n) { n = n || 0; var wan = n / 10000; return (n < 50000 ? wan.toFixed(1) : Math.ceil(wan)) + '萬'; }
function fmtNum(n) { if (isNaN(n)) return '0萬'; var wan = n / 10000; return (n < 50000 ? wan.toFixed(1) : Math.ceil(wan)) + '萬'; }

var currentTab = 'products';
var currentProdTab = 'normal';
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

function loadDashboardHome() {
  showLoading(true);
  var loaded = 0, total = 3;
  function checkDone() {
    loaded++;
    if (loaded === total) { showLoading(false); renderDashboardHome(); }
  }
  apiGet('customer-analysis', {}, function(res) {
    if (res.success) { window._dashCustomers = res.data; window._dashSummary = res.summary; }
    checkDone();
  });
  apiGet('products', {}, function(res) {
    if (res.success) { window._dashProducts = res.data; }
    checkDone();
  });
  apiGet('strategy-report', { year: currentYear, month: currentMonth }, function(res) {
    if (res.success) { window._dashReport = res.data; }
    checkDone();
  });
}

function renderDashboardHome() {
  var html = '<div style="padding:20px">';
  var s = window._dashSummary || {};
  var r = window._dashReport || {};
  var reportSummary = r.summary || {};
  console.log('Dashboard data:', s, r);
  html += '<div class="kpi-row">';
  html += '<div class="kpi-card kpi-gold"><div class="label">當月銷售</div><div class="value">' + fmtNum(reportSummary.total || 0) + '</div><div class="sub">當月睡美人 ' + fmtNum(reportSummary.sleeperSales || 0) + '</div></div>';
  var yoyText = (r.comparisons && r.comparisons.yoy && r.comparisons.yoy.totalPct !== undefined) ? (r.comparisons.yoy.totalPct >= 0 ? '+' : '') + r.comparisons.yoy.totalPct.toFixed(1) + '%' : '—';
  html += '<div class="kpi-card kpi-blue"><div class="label">同期相比</div><div class="value" style="color:' + (parseFloat(yoyText) >= 0 ? 'var(--gold)' : 'var(--red)') + '">' + yoyText + '</div></div>';
  html += '<div class="kpi-card"><div class="label">年銷售</div><div class="value">' + (s.totalAmount ? fmtNum(s.totalAmount) : '—') + '</div></div>';
  html += '</div>';
  html += '<div class="kpi-row" style="margin-top:20px">';
  var healthLabels = { warning: '90天未單', dormant: '半年未單', decline: '業績衰退', growth: '成長中' };
  ['warning', 'dormant', 'decline', 'growth'].forEach(function(key) {
    var count = (s.healthCounts && s.healthCounts[key]) || 0;
    var info = HEALTH_INFO[key];
    html += '<div class="kpi-card" style="border:1px solid ' + info.color + '"><div class="label">' + healthLabels[key] + '</div><div class="value" style="color:' + info.color + '">' + count + '</div><div class="sub">家客戶</div></div>';
  });
  html += '</div>';

  html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px">';

  html += '<div style="padding:15px;background:var(--bg2);border-radius:8px"><div style="text-align:center;margin-bottom:10px;font-weight:bold;color:var(--gold)">產品類型</div>';
  var ptAmts = s.productTypeAmounts || {};
  var ptSum = (ptAmts.sleeper || 0) + (ptAmts.normal || 0) + (ptAmts.discontinued || 0);
  var ptLabels = { sleeper: '睡美人', normal: '正常品', discontinued: '不續辦' };
  var ptColors = { sleeper: 'var(--gold)', normal: '#4CAF50', discontinued: '#FF6B6B' };
  if (ptSum === 0) {
    html += '<div style="padding:8px;color:var(--border)">無數據</div>';
  } else {
    Object.keys(ptLabels).forEach(function(key) {
      var amt = ptAmts[key] || 0;
      var pct = ptSum > 0 ? ((amt / ptSum) * 100).toFixed(1) : 0;
      html += '<div style="padding:8px;border-bottom:1px solid var(--border);font-size:12px">';
      html += '<div style="display:flex;justify-content:space-between"><span style="color:' + ptColors[key] + '">' + ptLabels[key] + '</span> <span style="color:var(--gold)">' + pct + '%</span></div>';
      html += '<div style="background:var(--border);height:4px;border-radius:2px;overflow:hidden;margin-top:4px">';
      html += '<div style="background:' + ptColors[key] + ';height:100%;width:' + pct + '%"></div></div></div>';
    });
  }
  html += '</div>';

  html += '<div style="padding:15px;background:var(--bg2);border-radius:8px"><div style="text-align:center;margin-bottom:10px;font-weight:bold;color:var(--gold)">前10大系列</div>';
  var topSeries = s.topSeries || [];
  if (topSeries.length === 0) {
    html += '<div style="padding:8px;color:var(--border)">無數據</div>';
  } else {
    topSeries.forEach(function(ser, i) {
      html += '<div style="padding:8px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;font-size:12px">';
      html += '<span>' + (i+1) + '. ' + ser.name + '</span>';
      html += '<span style="color:var(--gold);font-weight:bold">' + fmtNum(ser.amount) + '</span>';
      html += '</div>';
    });
  }
  html += '</div></div>';

  html += '<div style="padding:15px;background:var(--bg2);border-radius:8px;margin-top:20px"><div style="text-align:center;margin-bottom:10px;font-weight:bold;color:var(--gold)">前10大客戶</div>';
  var topCust = s.topCustomers || [];
  if (topCust.length === 0) {
    html += '<div style="padding:8px;color:var(--border)">無數據</div>';
  } else {
    topCust.forEach(function(c, i) {
      html += '<div style="padding:8px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;font-size:12px">';
      html += '<span>' + (i+1) + '. ' + c.name + '</span>';
      html += '<span style="color:var(--gold);font-weight:bold">' + fmtNum(c.totalAmount) + '</span>';
      html += '</div>';
    });
  }
  html += '</div></div>';
  document.getElementById('main-content').innerHTML = html;
}

function switchTab(tab) {
  currentTab = tab;
  document.getElementById('tab-bonus').classList.toggle('active', tab === 'bonus');
  document.getElementById('tab-products').classList.toggle('active', tab === 'products');
  document.getElementById('tab-reports').classList.toggle('active', tab === 'reports');
  document.getElementById('tab-customers').classList.toggle('active', tab === 'customers');
  document.getElementById('tab-analysis').classList.toggle('active', tab === 'analysis');
  document.getElementById('tab-reps').classList.toggle('active', tab === 'reps');
  document.getElementById('tab-mgr').classList.toggle('active', tab === 'mgr');
  document.getElementById('ctrl-bonus').classList.toggle('hidden', tab !== 'bonus');
  document.getElementById('ctrl-products').classList.toggle('hidden', tab !== 'products');
  document.getElementById('ctrl-reports').classList.toggle('hidden', tab !== 'reports');
  if (tab === 'products') {
    var el = document.getElementById('filter-grade');
    el.style.display = currentProdTab === 'sleeper' ? '' : 'none';
    loadCacheInfo();
    if (!window._normalData) loadProducts();
    else renderProducts();
  } else if (tab === 'reports') {
    if (!window._strategyReport) loadStrategyReport();
    else renderStrategyReport(window._strategyReport);
  } else if (tab === 'bonus') {
    loadDashboard();
    loadSalesList();
  } else if (tab === 'customers') {
    loadCustomerAnalysis();
  } else if (tab === 'analysis') {
    var c = document.getElementById('main-content');
    if (!c.querySelector('iframe[data-tab=analysis]')) {
      c.innerHTML = '<iframe data-tab="analysis" src="analysis.php" style="width:100%;border:none;background:var(--bg);min-height:calc(100vh - 120px)"></iframe>';
    }
  } else if (tab === 'reps') {
    var c = document.getElementById('main-content');
    if (!c.querySelector('iframe[data-tab=reps]')) {
      c.innerHTML = '<iframe data-tab="reps" src="reps.php" style="width:100%;border:none;background:var(--bg);min-height:calc(100vh - 120px)"></iframe>';
    }
  } else if (tab === 'mgr') {
    var c = document.getElementById('main-content');
    if (!c.querySelector('iframe[data-tab=mgr]')) {
      c.innerHTML = '<iframe data-tab="mgr" src="group_meeting.php" style="width:100%;border:none;background:var(--bg);min-height:calc(100vh - 120px)"></iframe>';
    }
  }
}

function updateReportPeriodOptions() {
  var sel = document.getElementById('report-period');
  var html = '';
  var defaultValue = '1';
  if (reportMode === 'month') {
    for (var m = 1; m <= 12; m++) html += '<option value="' + m + '">' + m + ' 月</option>';
    defaultValue = String(currentMonth + 1);
    sel.style.display = '';
  } else if (reportMode === 'quarter') {
    for (var q = 1; q <= 4; q++) html += '<option value="' + q + '">Q' + q + '</option>';
    defaultValue = String(Math.floor(currentMonth / 3) + 1);
    sel.style.display = '';
  } else if (reportMode === 'half') {
    html = '<option value="1">H1</option><option value="2">H2</option>';
    defaultValue = currentMonth < 6 ? '1' : '2';
    sel.style.display = '';
  } else {
    html = '<option value="1">全年</option>';
    defaultValue = '1';
    sel.style.display = 'none';
  }
  sel.innerHTML = html;
  sel.value = defaultValue;
}

function setReportMode(mode) {
  reportMode = mode;
  ['month', 'quarter', 'half', 'year'].forEach(function(key) {
    document.getElementById('mode-' + key).classList.toggle('active', key === mode);
  });
  updateReportPeriodOptions();
}
function switchProdTab(tab) {
  currentProdTab = tab;
  document.getElementById('sub-sleeper').classList.toggle('active', tab === 'sleeper');
  document.getElementById('sub-normal').classList.toggle('active', tab === 'normal');
  document.getElementById('sub-discontinued').classList.toggle('active', tab === 'discontinued');
  document.getElementById('filter-grade').style.display = tab === 'sleeper' ? '' : 'none';
  document.getElementById('btn-restock-advisor').style.display = tab === 'normal' ? '' : 'none';
  renderProducts();
}

loadDashboardHome();

function loadProducts() {
  showLoading(true);
  var total = 3, loaded = 0, failed = false;
  function checkDone() {
    loaded++;
    if (loaded === total) { showLoading(false); if (!failed) renderProducts(); }
  }
  apiGet('products', {}, function(res) {
    if (res.success) window._sleeperData = res.data;
    else failed = true;
    checkDone();
  }, function() { failed = true; checkDone(); });
  apiGet('normal-products', {}, function(res) {
    if (res.success) window._normalData = res.data;
    else failed = true;
    checkDone();
  }, function() { failed = true; checkDone(); });
  apiGet('discontinued-products', {}, function(res) {
    if (res.success) window._disconProducts = res.data;
    else failed = true;
    checkDone();
  }, function() { failed = true; checkDone(); });
}

var restockAdviceByTab = {};
function loadRestockAdvisor(refresh) {
  var tab = currentProdTab;
  apiGet('product-advisor', { tab: tab, refresh: refresh ? 1 : 0 }, function(res) {
    if (!res.success) { alert('補貨建議載入失敗：' + (res.msg || '未知錯誤')); return; }
    var map = {};
    (res.data.advice || []).forEach(function(a) { map[a.sku] = a; });
    restockAdviceByTab[tab] = map;
    renderProducts();
  });
}

function renderProducts() {
  var restockAdviceMap = restockAdviceByTab[currentProdTab] || {};
  var data;
  var subtitle;
  if (currentProdTab === 'sleeper') { data = window._sleeperData; subtitle = '睡美人'; }
  else if (currentProdTab === 'normal') { data = window._normalData; subtitle = '正常品'; }
  else { data = window._disconProducts; subtitle = '不續辦'; }

  if (!data) { document.getElementById('main-content').innerHTML = '<div class="welcome" style="padding:40px;text-align:center;color:var(--text2)">載入中...</div>'; return; }

  var gradeFilter = document.getElementById('filter-grade').value;
  var sortBy = document.getElementById('sort-by').value;
  var keyword = (document.getElementById('filter-keyword').value || '').trim().toUpperCase();

  var list = data.filter(function(p) {
    if (currentProdTab === 'sleeper' && gradeFilter && p.grade !== gradeFilter) return false;
    if (keyword) {
      var hay = [p.sku, p.series, p.size, p.productName].join(' ').toUpperCase();
      if (hay.indexOf(keyword) === -1) return false;
    }
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
      var ca = a.saleCount || 0;
      var cb = b.saleCount || 0;
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

    var customerHtml = '';
    if (p.customers && p.customers.length > 0) {
      customerHtml = '<details class="prod-buyers"><summary class="ps-label" style="cursor:pointer">客戶銷售（' + p.customers.length + '）</summary><div style="display:grid;gap:6px;margin-top:6px">' +
        p.customers.map(function(c) {
          return '<div style="display:grid;grid-template-columns:minmax(0,1fr) 72px 72px 52px;gap:8px;font-size:12px;align-items:center">' +
            '<div style="color:var(--text);font-weight:700;min-width:0">' + shortCust(c.name) + '</div>' +
            '<div style="color:var(--text2);text-align:right">' + Math.round(c.qty || 0) + '片</div>' +
            '<div style="color:var(--gold);text-align:right">' + fmt(c.amount || 0) + '</div>' +
            '<div style="color:var(--text2);text-align:right">' + truncNum(c.sharePct || 0) + '%</div>' +
          '</div>';
        }).join('') + '</div></details>';
    }

    var mosText = p.mos === 888 ? '無銷售' : (p.mos || 0) + ' 月';
    var mosClass = p.mosLevel === 2 ? 'text-red' : (p.mosLevel === 1 ? 'text-gold' : 'text-green');

    var gradeHtml = p.grade ? '<div class="grade-badge grade-' + p.grade + '">' + p.grade + '</div>' : '';
    var thumbHtml = p.imageUrl
      ? '<img src="' + driveUrlToDirect(p.imageUrl) + '" alt="" style="width:56px;height:56px;object-fit:contain;border-radius:10px;background:rgba(255,255,255,0.03);border:1px solid var(--border-light)" onerror="this.style.display=\'none\';this.parentNode.innerHTML=\'<div style=&quot;width:56px;height:56px;border:1px dashed var(--border-light);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--text2);font-size:11px&quot;>無圖</div>\'">'
      : '<div style="width:56px;height:56px;border:1px dashed var(--border-light);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--text2);font-size:11px">無圖</div>';

    var stockPieces = Math.round((p.stockPing || 0) * (p.perPing || 36));
    var lastSaleDays = p.daysSinceLastSale === null ? '從未銷售' : ('距今 ' + p.daysSinceLastSale + ' 天');

    var detailHtml =
        '<div class="prod-stats" style="margin-top:8px">' +
          '<div class="prod-stat"><div class="ps-label">歷史銷售</div><div class="ps-value">' + p.totalPings + ' 坪</div></div>' +
          '<div class="prod-stat"><div class="ps-label">目前陳列</div><div class="ps-value text-gold" style="cursor:pointer; text-decoration:underline;" onclick="showDisplayDetails(\'' + p.sku + '\')">🖼️ ' + (p.displayCount || 0) + ' 家</div></div>' +
          '<div class="prod-stat"><div class="ps-label">平均毛利率</div><div class="ps-value text-gold">' + truncNum(p.avgMarginPct || 0) + '%</div></div>' +
          '<div class="prod-stat"><div class="ps-label">去化月數 (MOS)</div><div class="ps-value ' + mosClass + '">' + mosText + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">近6月平均月銷</div><div class="ps-value">' + (p.monthlySpeedPings || 0) + ' 坪</div></div>' +
          '<div class="prod-stat"><div class="ps-label">庫存佔用成本</div><div class="ps-value text-gold">' + fmt(p.inventoryCost) + '</div></div>' +
        '</div>' +
        '<div class="prod-frozen ' + frozenClass + '" style="margin-top:8px">' + (p.stagnantReason || frozenText) + '</div>' +
        (p.action ? '<div class="action-badge" style="background:' + p.actionColor + '15; border:1px solid ' + p.actionColor + '30; color:' + p.actionColor + '; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:800; display:inline-block; margin-top:6px">' + p.action + '</div>' : '') +
        (restockAdviceMap[p.sku] ? '<div class="action-badge" style="background:rgba(96,165,250,0.12); border:1px solid rgba(96,165,250,0.3); color:#60a5fa; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:800; display:inline-block; margin-top:6px">🤖 ' + restockAdviceMap[p.sku].advice + '</div>' : '') +
        '<div style="margin-top:8px"><button class="btn btn-ghost" style="font-size:11px;padding:4px 10px" onclick="event.stopPropagation();showLifecycle(\'' + p.sku + '\')">📈 產品生命週期</button></div>' +
        customerHtml;

    html += '<div class="product-card">' +
      '<div class="prod-grade">' + gradeHtml + thumbHtml +
      '</div>' +
      '<div class="prod-info">' +
        '<div class="prod-summary-row">' +
          '<div class="prod-summary-main">' +
            '<div class="prod-title">' + p.sku + ' · ' + (p.series || '未分類') + (p.size ? ' · ' + p.size : '') + '</div>' +
          '</div>' +
          '<div class="prod-summary-stats">' +
            '<div class="prod-stat"><div class="ps-label">庫存</div><div class="ps-value">' + stockPieces + ' 片 / ' + p.stockPing + ' 坪</div></div>' +
            '<div class="prod-stat"><div class="ps-label">最後銷售</div><div class="ps-value">' + p.lastSaleStr + '（' + lastSaleDays + '）</div></div>' +
          '</div>' +
        '</div>' +
        '<details class="prod-detail-toggle"><summary class="ps-label" style="cursor:pointer">更多資訊</summary>' + detailHtml + '</details>' +
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
  var period = parseInt(document.getElementById('report-period').value || '1', 10);
  showLoading(true);
  apiGet('strategy-report', { year: year, mode: reportMode, period: period }, function(res) {
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

function fmtDeltaPct(n) {
  var num = truncNum(n || 0);
  return (num > 0 ? '+' : '') + num + '%';
}

function deltaClass(n) {
  return n > 0 ? 'delta-up' : (n < 0 ? 'delta-down' : 'delta-flat');
}

function truncNum(n) {
  n = Number(n || 0);
  return n < 0 ? Math.ceil(n) : Math.floor(n);
}

function fmtReportWan(n) {
  n = Number(n || 0) / 10000;
  return truncNum(n) + '萬';
}

function fmtReportInt(n) {
  return String(truncNum(n || 0));
}

function fmtReportPct(n) {
  return truncNum(n || 0) + '%';
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

function buildDeltaRows(items, key) {
  if (!items || !items.length) return '<div class="chart-sub">與上期相比沒有明顯變化</div>';
  var max = 1;
  items.forEach(function(item) { max = Math.max(max, Math.abs(item.delta || 0)); });
  return '<div class="bar-list">' + items.map(function(item) {
    var width = Math.max(6, Math.round(Math.abs(item.delta || 0) / max * 100));
    var cls = item.delta >= 0 ? 'pos' : 'neg';
    var sign = item.delta >= 0 ? '+' : '';
    return '<div class="bar-row delta">' +
      '<div class="bar-label">' + ellipsis(item[key], 14) + '</div>' +
      '<div class="bar-track"><div class="bar-fill ' + cls + '" style="width:' + width + '%"></div></div>' +
      '<div class="bar-value ' + deltaClass(item.delta) + '">' + sign + fmtReportWan(Math.abs(item.delta || 0) * (item.delta < 0 ? -1 : 1)) + '</div>' +
      '</div>';
  }).join('') + '</div>';
}

function buildVisitRows(items, key, valueKey, valueFormatter, subFormatter) {
  if (!items || !items.length) return '<div class="chart-sub">本期尚無資料</div>';
  var max = 1;
  items.forEach(function(item) { max = Math.max(max, item[valueKey] || 0); });
  return '<div class="bar-list">' + items.map(function(item) {
    var width = Math.max(6, Math.round(((item[valueKey] || 0) / max) * 100));
    return '<div class="bar-row">' +
      '<div class="bar-label">' + ellipsis(item[key], 12) + '</div>' +
      '<div class="bar-track"><div class="bar-fill" style="width:' + width + '%"></div></div>' +
      '<div class="bar-value">' + valueFormatter(item[valueKey] || 0) + (subFormatter ? '<div class="chart-sub" style="margin:4px 0 0">' + subFormatter(item) + '</div>' : '') + '</div>' +
      '</div>';
  }).join('') + '</div>';
}

function buildRepRows(items) {
  if (!items || !items.length) return '<div class="chart-sub">本期尚無外勤資料</div>';
  var max = 1;
  items.forEach(function(item) { max = Math.max(max, item.salesAmount || 0); });
  return '<div class="bar-list">' + items.map(function(item) {
    var width = Math.max(6, Math.round(((item.salesAmount || 0) / max) * 100));
    return '<div class="bar-row">' +
      '<div class="bar-label">' + ellipsis(item.name, 10) + '</div>' +
      '<div class="bar-track"><div class="bar-fill" style="width:' + width + '%"></div></div>' +
      '<div class="bar-value">' + fmtReportWan(item.salesAmount || 0) +
        '<div class="chart-sub" style="margin:4px 0 0">拜訪 ' + fmtReportInt(item.visits || 0) + ' 次 / ' +
        fmtReportInt(item.customerCount || 0) + ' 客 / ' +
        fmtReportInt(item.km || 0) + ' km</div>' +
      '</div>' +
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
      '<span class="legend-val">' + fmtReportPct(pct) + '</span>' +
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
      '<div class="trend-value">' + (val > 0 ? fmtReportWan(val) : '0萬') + '</div>' +
      '<div class="trend-bar" style="height:' + h + 'px"></div>' +
      '<div class="trend-label">' + m + '月</div>' +
      '</div>';
  }
  html += '</div>';
  return html;
}

function buildTrailingTrendChart(rows) {
  rows = rows || [];
  if (!rows.length) return '<div class="chart-sub">本期尚無資料</div>';
  var max = 1;
  rows.forEach(function(row) {
    max = Math.max(max, row.current || 0, row.previous || 0);
  });
  return '<div class="trend-wrap">' + rows.map(function(row) {
    var curr = row.current || 0;
    var prev = row.previous || 0;
    var currH = Math.max(8, Math.round(curr / max * 150));
    var prevH = Math.max(8, Math.round(prev / max * 150));
    return '<div class="trend-col">' +
      '<div style="display:flex;gap:6px;align-items:flex-end;height:170px">' +
      '<div style="flex:1;text-align:center"><div class="trend-value">' + (prev > 0 ? fmtReportWan(prev) : '0萬') + '</div><div class="trend-bar" style="height:' + prevH + 'px;background:#4a4a4a"></div></div>' +
      '<div style="flex:1;text-align:center"><div class="trend-value">' + (curr > 0 ? fmtReportWan(curr) : '0萬') + '</div><div class="trend-bar" style="height:' + currH + 'px"></div></div>' +
      '</div>' +
      '<div class="trend-label">' + row.label + '</div>' +
      '</div>';
  }).join('') + '</div>';
}

function buildCompareCard(label, currentValue, previousLabel, previousValue, yoyLabel, yoyValue, primaryPct, yoyPct) {
  return '<div class="compare-card">' +
    '<div class="compare-label">' + label + '</div>' +
    '<div class="compare-value">' + currentValue + '</div>' +
    '<div class="compare-sub">' + previousLabel + ' ' + previousValue + ' ・ <span class="' + deltaClass(primaryPct) + '">' + fmtDeltaPct(primaryPct || 0) + '</span></div>' +
    '<div class="compare-sub">' + yoyLabel + ' ' + yoyValue + ' ・ <span class="' + deltaClass(yoyPct) + '">' + fmtDeltaPct(yoyPct || 0) + '</span></div>' +
    '</div>';
}

function renderStrategyReport(d) {
  var s = d.summary || {};
  var bases = d.bases || {};
  var basePrev = bases.previous || {};
  var baseYoy = bases.yoy || {};
  var c = d.comparisons || {};
  var primary = c.primary || {};
  var yoy = c.yoy || {};
  var topSales = d.topSales || [];
  var topCustomers = d.topCustomers || [];
  var topProjects = d.topProjects || [];
  var topProducts = d.topProducts || [];
  var topSeries = d.topSeries || [];
  var trailingTrend = d.trailingTrend || [];
  var growthSales = d.growthSales || [];
  var growthCustomers = d.growthCustomers || [];
  var growthProjects = d.growthProjects || [];
  var growthProducts = d.growthProducts || [];
  var fieldActivity = d.fieldActivity || {};
  var fieldBases = d.fieldBases || {};
  var fieldBasePrev = fieldBases.previous || {};
  var fieldBaseYoy = fieldBases.yoy || {};
  var fieldComparisons = d.fieldComparisons || {};
  var fieldPrimary = fieldComparisons.primary || {};
  var fieldYoy = fieldComparisons.yoy || {};
  var fieldSummary = fieldActivity.summary || {};
  var topVisitedCustomers = fieldActivity.topVisitedCustomers || [];
  var underVisitedCustomers = fieldActivity.underVisitedCustomers || [];
  var highVisitLowSalesCustomers = fieldActivity.highVisitLowSalesCustomers || [];
  var repEfficiency = fieldActivity.repEfficiency || [];
  var taskMix = fieldActivity.taskMix || [];
  var topSalesDonut = topSales.slice(0, 5);
  if (topSales.length > 5) {
    var otherAmt = topSales.slice(5).reduce(function(sum, item) { return sum + (item.amount || 0); }, 0);
    if (otherAmt > 0) topSalesDonut.push({ name: '其他', amount: otherAmt });
  }

  var html = '<div class="kpi-row">' +
    '<div class="kpi-card kpi-gold"><div class="label">本期銷售額</div><div class="value">' + fmtReportWan(s.total || 0) + '</div><div class="sub">' + d.label + '</div></div>' +
    '<div class="kpi-card"><div class="label">睡美人業績</div><div class="value">' + fmtReportWan(s.sleeperSales || 0) + '</div><div class="sub">佔比 ' + fmtReportPct(s.sleeperPct || 0) + '</div></div>' +
    '<div class="kpi-card kpi-green"><div class="label">銷售坪數 / 交易筆數</div><div class="value">' + fmtReportInt(s.pings || 0) + ' / ' + fmtReportInt(s.txCount || 0) + '</div><div class="sub">坪 / 筆</div></div>' +
    '<div class="kpi-card kpi-blue"><div class="label">去年同期</div><div class="value">' + fmtReportWan(baseYoy.total || 0) + '</div><div class="sub">YOY ' + fmtDeltaPct(yoy.totalPct || 0) + '</div></div>' +
    '</div>';

  html += '<div class="compare-strip">' +
    '<details class="chart-card full-span" open><summary style="cursor:pointer;list-style:none;display:flex;justify-content:space-between;align-items:center"><div><div class="chart-title">MOM</div><div class="chart-sub">點開看今年 1 月起，和去年同期同月的雙柱比較。</div></div><div class="' + deltaClass(primary.totalPct) + '">' + fmtDeltaPct(primary.totalPct || 0) + '</div></summary><div style="padding-top:12px">' +
      '<div class="compare-sub">' + d.previousLabel + ' ' + fmtReportWan(basePrev.total || 0) + ' ・ <span class="' + deltaClass(primary.totalPct) + '">' + fmtDeltaPct(primary.totalPct || 0) + '</span></div>' +
      '<div class="compare-sub">' + d.yoyLabel + ' ' + fmtReportWan(baseYoy.total || 0) + ' ・ <span class="' + deltaClass(yoy.totalPct) + '">' + fmtDeltaPct(yoy.totalPct || 0) + '</span></div>' +
      buildTrailingTrendChart(trailingTrend) +
    '</div></details>' +
    '</div>';

  html += '<div class="kpi-row">' +
    '<div class="kpi-card"><div class="label">客戶到訪次數</div><div class="value">' + fmtReportInt(fieldSummary.totalVisits || 0) + '</div><div class="sub">' + d.label + '</div></div>' +
    '<div class="kpi-card"><div class="label">到訪客戶數</div><div class="value">' + fmtReportInt(fieldSummary.visitedCustomers || 0) + '</div><div class="sub">已拜訪客戶</div></div>' +
    '<div class="kpi-card"><div class="label">外勤公里數</div><div class="value">' + fmtReportInt(fieldSummary.totalKm || 0) + '</div><div class="sub">km</div></div>' +
    '<div class="kpi-card"><div class="label">油資</div><div class="value">' + fmtReportWan(fieldSummary.fuelAmount || 0) + '</div><div class="sub">本期油資</div></div>' +
    '</div>';

  html += '<div class="compare-strip">' +
    buildCompareCard(fieldPrimary.label || '前期比較', fmtReportInt(fieldSummary.totalVisits || 0), d.previousLabel || '前期', fmtReportInt(fieldBasePrev.totalVisits || 0), d.yoyLabel || '去年同期', fmtReportInt(fieldBaseYoy.totalVisits || 0), fieldPrimary.totalVisitsPct, fieldYoy.totalVisitsPct) +
    buildCompareCard('拜訪客戶數', fmtReportInt(fieldSummary.visitedCustomers || 0), d.previousLabel || '前期', fmtReportInt(fieldBasePrev.visitedCustomers || 0), d.yoyLabel || '去年同期', fmtReportInt(fieldBaseYoy.visitedCustomers || 0), fieldPrimary.visitedCustomersPct, fieldYoy.visitedCustomersPct) +
    buildCompareCard('外勤公里數', fmtReportInt(fieldSummary.totalKm || 0), d.previousLabel || '前期', fmtReportInt(fieldBasePrev.totalKm || 0), d.yoyLabel || '去年同期', fmtReportInt(fieldBaseYoy.totalKm || 0), fieldPrimary.totalKmPct, fieldYoy.totalKmPct) +
    '</div>';

  html += '<div class="report-grid">';
  html += '<div class="chart-card"><div class="chart-title">業務排行</div><div class="chart-sub">看誰真的在出貨，不看感覺。</div>' + buildBarRows(topSales, 'name', fmtReportWan) + '</div>';
  html += buildDonutCard('業務占比', topSalesDonut, 'name', '本期占比\n' + fmtReportWan(s.total || 0));
  html += '<div class="chart-card"><div class="chart-title">前 10 大客戶</div><div class="chart-sub">只看客戶，不把專案混進來。</div>' + buildBarRows(topCustomers, 'name', fmtReportWan) + '</div>';
  html += '<div class="chart-card"><div class="chart-title">前 10 大專案</div><div class="chart-sub">專案另外統計，避免客戶失真。</div>' + buildBarRows(topProjects, 'name', fmtReportWan) + '</div>';
  html += '<div class="chart-card"><div class="chart-title">前 10 大產品</div><div class="chart-sub">看哪個 SKU 在拉動銷售。</div>' + buildBarRows(topProducts.map(function(item) {
    return { name: item.sku, amount: item.amount };
  }), 'name', fmtReportWan) + '</div>';
  html += buildDonutCard('系列占比', topSeries, 'name', '系列結構\n' + topSeries.length + ' 類');
  html += '<div class="chart-card"><div class="chart-title">年度月趨勢</div><div class="chart-sub">判斷這期是在高峰、平穩，還是掉速。</div>' + buildTrendChart(d.monthTrend || {}, d.months ? d.months[0] : 0) + '</div>';
  html += '<div class="chart-card"><div class="chart-title">業務成長貢獻</div><div class="chart-sub">相對 ' + (d.previousLabel || '前期') + '，誰拉上來、誰掉下去。紅色代表低於比較基準。</div>' + buildDeltaRows(growthSales, 'name') + '</div>';
  html += '<div class="chart-card"><div class="chart-title">客戶成長貢獻</div><div class="chart-sub">看成長來自哪些客戶，或少在哪些客戶。紅色代表低於 ' + (d.previousLabel || '前期') + '。</div>' + buildDeltaRows(growthCustomers, 'name') + '</div>';
  html += '<div class="chart-card"><div class="chart-title">專案成長貢獻</div><div class="chart-sub">專案獨立看，避免混淆客戶變化。紅色代表低於 ' + (d.previousLabel || '前期') + '。</div>' + buildDeltaRows(growthProjects, 'name') + '</div>';
  html += '<div class="chart-card full-span"><div class="chart-title">產品成長貢獻</div><div class="chart-sub">看哪個 SKU 真正在推升或拖累本期。</div>' + buildDeltaRows(growthProducts, 'name') + '</div>';
  html += '<div class="chart-card"><div class="chart-title">客戶到訪熱度</div><div class="chart-sub">哪些客戶被拜訪最多，頻率是否合理。</div>' + buildVisitRows(topVisitedCustomers, 'name', 'visits', fmtReportInt, function(item) { return '業績 ' + fmtReportWan(item.salesAmount || 0) + ' / 最近 ' + (item.lastVisit || '無'); }) + '</div>';
  html += '<div class="chart-card"><div class="chart-title">很少去但有業績</div><div class="chart-sub">高業績但拜訪偏少，優先補拜訪。</div>' + buildVisitRows(underVisitedCustomers, 'name', 'salesAmount', fmtReportWan, function(item) { return '拜訪 ' + fmtReportInt(item.visits || 0) + ' 次'; }) + '</div>';
  html += '<div class="chart-card"><div class="chart-title">高拜訪低產出</div><div class="chart-sub">去很多次，但本期業績偏弱。</div>' + buildVisitRows(highVisitLowSalesCustomers, 'name', 'visits', fmtReportInt, function(item) { return '業績 ' + fmtReportWan(item.salesAmount || 0); }) + '</div>';
  html += '<div class="chart-card"><div class="chart-title">業務外勤效率</div><div class="chart-sub">公里數、拜訪次數與產值一起看。</div>' + buildRepRows(repEfficiency) + '</div>';
  html += buildDonutCard('工作型態占比', taskMix, 'name', '本期任務\n' + fmtReportInt(fieldSummary.totalVisits || 0));
  html += '<div class="chart-card full-span"><div class="chart-title">管理提示</div><div class="insight-list">' +
    '<div class="insight-item">' + (d.insights && d.insights.leader ? d.insights.leader : '本期尚無業務資料。') + '</div>' +
    '<div class="insight-item">' + (d.insights && d.insights.concentration ? d.insights.concentration : '尚無集中度資料。') + '</div>' +
    '<div class="insight-item">' + (d.insights && d.insights.customer ? d.insights.customer : '尚無客戶分析資料。') + '</div>' +
    '<div class="insight-item">' + (d.insights && d.insights.product ? d.insights.product : '尚無產品分析資料。') + '</div>' +
    '<div class="insight-item">本期外勤 ' + fmtReportInt(fieldSummary.totalVisits || 0) + ' 次、' + fmtReportInt(fieldSummary.visitedCustomers || 0) + ' 客、' + fmtReportInt(fieldSummary.totalKm || 0) + ' km，平均每次拜訪帶來 ' + fmtReportWan(fieldSummary.salesPerVisit || 0) + '。</div>' +
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
      loadCacheInfo();
      loadProducts();
    } else {
      toast('快取同步失敗: ' + res.error, true);
    }
  }, function(err) {
    showLoading(false);
    toast('連線失敗: ' + err, true);
  });
}

function loadCacheInfo() {
  apiGet('cache-info', {}, function(res) {
    if (!res.success) return;
    var txt = '快取：' + res.lastUpdate + '（' + res.rows + ' 筆）';
    var els = document.querySelectorAll('[id^="cache-info"]');
    els.forEach(function(el) { el.textContent = txt; });
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

var HEALTH_INFO = {
  dormant: { label: '半年+未下單', color: '#e5484d' },
  warning: { label: '90天未下單', color: '#f5a623' },
  decline: { label: '業績衰退', color: '#e5484d' },
  growth:  { label: '成長中', color: '#3ecf8e' },
  no_sales:{ label: '無銷售記錄', color: 'var(--text2)' },
  normal:  { label: '正常', color: 'var(--text2)' }
};

function applyCustomerDaysFilter() {
  var v = parseInt(document.getElementById('customer-days-input').value, 10);
  window._customerDaysFilter = isNaN(v) || v <= 0 ? 0 : v;
  renderCustomerAnalysis(window._customerData);
}

function loadCustomerAnalysis() {
  var container = document.getElementById('main-content');
  if (window._customerData) { renderCustomerAnalysis(window._customerData); return; }
  container.innerHTML = '<div class="welcome" style="padding:40px;text-align:center;color:var(--text2)">載入中...</div>';
  apiGet('customer-analysis', {}, function(res) {
    if (!res.success) { container.innerHTML = '<div class="welcome"><p style="color:var(--red)">❌ ' + res.msg + '</p></div>'; return; }
    window._customerData = res.data;
    window._customerSummary = res.summary || null;
    renderCustomerAnalysis(res.data);
  });
}

var CAT_COLOR = {
  '客訴': '#e5484d', '送樣': '#60a5fa', '帳款': '#f5a623', '版面': '#a78bfa',
  '送貨退貨': '#3ecf8e', '案件': '#f97316', '客情': '#c084fc', '其他': 'var(--text2)'
};

function renderCustomerDashboard(summary) {
  if (!summary) return '';
  var yoyText, yoyColor;
  if (summary.yoyPct === null) { yoyText = '—'; yoyColor = 'var(--text2)'; }
  else if (summary.yoyPct > 0) { yoyText = '▲ +' + summary.yoyPct + '%'; yoyColor = 'var(--red)'; }
  else if (summary.yoyPct < 0) { yoyText = '▼ ' + summary.yoyPct + '%'; yoyColor = 'var(--green)'; }
  else { yoyText = '0%'; yoyColor = 'var(--text2)'; }

  var html = '<div style="font-size:15px;font-weight:800;color:var(--gold);margin:4px 0 8px">📑 客戶月報總覽</div>';

  html += '<div class="kpi-row">' +
    '<div class="kpi-card"><div class="label">客戶總數</div><div class="value">' + summary.customerCount + '</div><div class="sub">家</div></div>' +
    '<div class="kpi-card"><div class="label">今年累積業績</div><div class="value">' + fmt(summary.thisYearAmount) + '</div><div class="sub">去年同期 ' + fmt(summary.lastYearAmount) + '</div></div>' +
    '<div class="kpi-card"><div class="label">整體 YOY</div><div class="value" style="color:' + yoyColor + '">' + yoyText + '</div></div>' +
    '<div class="kpi-card"><div class="label">現正陳列版面數</div><div class="value">' + summary.totalActiveDisplays + '</div><div class="sub">個 SKU 上架中</div></div>' +
  '</div>';

  // 健康度分布
  var healthOrder = ['growth', 'normal', 'warning', 'decline', 'dormant'];
  var maxH = Math.max.apply(null, healthOrder.map(function(k) { return summary.healthCounts[k] || 0; })) || 1;
  html += '<div class="kpi-card" style="margin-top:10px"><div class="label">客戶健康度分布</div><div style="display:flex;flex-direction:column;gap:5px;margin-top:8px">';
  healthOrder.forEach(function(k) {
    var info = HEALTH_INFO[k];
    var n = summary.healthCounts[k] || 0;
    var pct = Math.round(n / maxH * 100);
    html += '<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2)">' +
      '<span style="width:80px;flex-shrink:0">' + info.label + '</span>' +
      '<div style="flex:1;background:var(--bg2);border-radius:3px;height:10px;overflow:hidden"><div style="width:' + pct + '%;height:100%;background:' + info.color + '"></div></div>' +
      '<span style="width:36px;text-align:right;flex-shrink:0;color:var(--text1)">' + n + '</span>' +
    '</div>';
  });
  html += '</div></div>';

  // Top 10 客戶
  if (summary.topCustomers && summary.topCustomers.length) {
    var maxAmt = Math.max.apply(null, summary.topCustomers.map(function(c) { return c.totalAmount; })) || 1;
    html += '<div class="kpi-card" style="margin-top:10px"><div class="label">業績 Top 10 客戶（依今年累積總額）</div><div style="display:flex;flex-direction:column;gap:5px;margin-top:8px">';
    summary.topCustomers.forEach(function(c) {
      var pct = Math.round(c.totalAmount / maxAmt * 100);
      var color = HEALTH_INFO[c.health] ? HEALTH_INFO[c.health].color : 'var(--gold)';
      html += '<div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text2)">' +
        '<span style="width:90px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text1)">' + shortCust(c.name) + '</span>' +
        '<div style="flex:1;background:var(--bg2);border-radius:3px;height:10px;overflow:hidden"><div style="width:' + pct + '%;height:100%;background:' + color + '"></div></div>' +
        '<span style="width:50px;text-align:right;flex-shrink:0;color:var(--text1)">' + fmt(c.totalAmount) + '</span>' +
      '</div>';
    });
    html += '</div></div>';
  }

  html += '<div style="font-size:15px;font-weight:800;color:var(--gold);margin:16px 0 8px">📋 客戶明細</div>';
  return html;
}

function renderCustomerAnalysis(data) {
  _custCardSeq = 0;
  var filter = window._customerHealthFilter || '';
  var salesFilter = window._customerSalesFilter || '';
  var base = data;

  // 先做業務篩選
  var filtered = base;
  if (salesFilter) {
    filtered = filtered.filter(function(c) { return (c.salesRep || '未分配') === salesFilter; });
  }

  var counts = {};
  filtered.forEach(function(c) { counts[c.health] = (counts[c.health] || 0) + 1; });

  var list = filter ? filtered.filter(function(c) { return c.health === filter; }) : filtered;

  var search = (window._customerSearch || '').trim();
  if (search) {
    list = list.filter(function(c) { return String(c.name).indexOf(search) !== -1; });
  }

  var sortKey = window._customerSort || 'amount_desc';
  var sorters = {
    amount_desc: function(a, b) { return b.thisYearAmount - a.thisYearAmount; },
    amount_asc: function(a, b) { return a.thisYearAmount - b.thisYearAmount; },
    yoy_desc: function(a, b) { return (b.yoyPct === null ? -999 : b.yoyPct) - (a.yoyPct === null ? -999 : a.yoyPct); },
    yoy_asc: function(a, b) { return (a.yoyPct === null ? 999 : a.yoyPct) - (b.yoyPct === null ? 999 : b.yoyPct); },
    days_desc: function(a, b) { return (b.daysSinceLastOrder === null ? -1 : b.daysSinceLastOrder) - (a.daysSinceLastOrder === null ? -1 : a.daysSinceLastOrder); },
    days_asc: function(a, b) { return (a.daysSinceLastOrder === null ? 999 : a.daysSinceLastOrder) - (b.daysSinceLastOrder === null ? 999 : b.daysSinceLastOrder); },
    visits_desc: function(a, b) { return b.visits - a.visits; },
    visits_asc: function(a, b) { return a.visits - b.visits; },
    margin_asc: function(a, b) { return (a.avgMarginPct === null ? 999 : a.avgMarginPct) - (b.avgMarginPct === null ? 999 : b.avgMarginPct); }
  };
  list = list.slice().sort(sorters[sortKey] || sorters.amount_desc);

  var groupByArea = !!window._customerGroupByArea;

  var html = '';
  html += renderCustomerDashboard(window._customerSummary);

  html += '<div class="kpi-row" style="align-items:center">' +
    '<div class="kpi-card" style="flex:1 1 200px">' +
      '<div class="label">搜尋客戶</div>' +
      '<input id="customer-search-input" type="text" value="' + (search || '').replace(/"/g, '&quot;') + '" placeholder="輸入客戶名稱..." style="width:100%;background:var(--bg2);border:1px solid var(--border);color:var(--text1);border-radius:6px;padding:6px 8px;font-size:13px;margin-top:6px" oninput="window._customerSearch=this.value;renderCustomerAnalysis(window._customerData)">' +
    '</div>' +
    '<div class="kpi-card" style="flex:0 0 auto">' +
      '<div class="label">排序</div>' +
      '<select id="customer-sort-select" style="width:140px;background:var(--bg2);border:1px solid var(--border);color:var(--text1);border-radius:6px;padding:6px 8px;font-size:13px;margin-top:6px" onchange="window._customerSort=this.value;renderCustomerAnalysis(window._customerData)">' +
        '<option value="amount_desc"' + (sortKey === 'amount_desc' ? ' selected' : '') + '>今年業績 高→低</option>' +
        '<option value="amount_asc"' + (sortKey === 'amount_asc' ? ' selected' : '') + '>今年業績 低→高</option>' +
        '<option value="yoy_desc"' + (sortKey === 'yoy_desc' ? ' selected' : '') + '>YOY 成長→衰退</option>' +
        '<option value="yoy_asc"' + (sortKey === 'yoy_asc' ? ' selected' : '') + '>YOY 衰退→成長</option>' +
        '<option value="days_desc"' + (sortKey === 'days_desc' ? ' selected' : '') + '>未下單天數 多→少</option>' +
        '<option value="days_asc"' + (sortKey === 'days_asc' ? ' selected' : '') + '>未下單天數 少→多</option>' +
        '<option value="visits_desc"' + (sortKey === 'visits_desc' ? ' selected' : '') + '>到訪次數 多→少</option>' +
        '<option value="visits_asc"' + (sortKey === 'visits_asc' ? ' selected' : '') + '>到訪次數 少→多</option>' +
        '<option value="margin_asc"' + (sortKey === 'margin_asc' ? ' selected' : '') + '>毛利率 低→高</option>' +
      '</select>' +
    '</div>' +
    '<div class="kpi-card" style="flex:0 0 auto">' +
      '<div class="label">顯示方式</div>' +
      '<div style="margin-top:6px"><button class="btn" style="padding:6px 12px;font-size:12px' + (groupByArea ? ';border-color:var(--gold);color:var(--gold)' : '') + '" onclick="window._customerGroupByArea=' + (groupByArea ? 'false' : 'true') + ';renderCustomerAnalysis(window._customerData)">依區域分組</button></div>' +
    '</div>' +
  '</div>';

  html += '<div class="kpi-row">';
  ['warning', 'dormant', 'decline', 'growth'].forEach(function(key) {
    var info = HEALTH_INFO[key];
    var active = filter === key;
    html += '<div class="kpi-card" style="cursor:pointer;border:1px solid ' + (active ? info.color : 'var(--border)') + '" onclick="window._customerHealthFilter=' + (active ? "''" : "'" + key + "'") + ';renderCustomerAnalysis(window._customerData)">' +
      '<div class="label">' + info.label + '</div><div class="value" style="color:' + info.color + '">' + (counts[key] || 0) + '</div><div class="sub">家客戶</div></div>';
  });
  html += '</div>';

  if (groupByArea) {
    var groups = {};
    var order = [];
    list.forEach(function(c) {
      var area = c.area || '未分配';
      if (!groups[area]) { groups[area] = []; order.push(area); }
      groups[area].push(c);
    });
    order.sort(function(a, b) {
      if (a === '未分配') return 1;
      if (b === '未分配') return -1;
      return a.localeCompare(b);
    });
    order.forEach(function(area, idx) {
      var expandId = 'area-expand-' + idx;
      var isExpanded = window._expandedAreas && window._expandedAreas[area];
      html += '<div style="margin:14px 0 0;cursor:pointer;user-select:none" onclick="window._expandedAreas=window._expandedAreas||{};window._expandedAreas[\'' + area + '\']=!window._expandedAreas[\'' + area + '\'];renderCustomerAnalysis(window._customerData)">' +
        '<div style="font-size:14px;font-weight:800;color:var(--gold);padding:6px 0">' + (isExpanded ? '▼' : '▶') + ' 📍 ' + area + '（' + groups[area].length + '）</div>' +
      '</div>' +
      (isExpanded ? '<div class="product-list">' + groups[area].map(function(c) { return renderCustomerCard(c); }).join('') + '</div>' : '');
    });
  } else {
    html += '<div class="product-list">';
    list.forEach(function(c) { html += renderCustomerCard(c); });
    html += '</div>';
  }

  document.getElementById('main-content').innerHTML = html;
  var input = document.getElementById('customer-search-input');
  if (input) {
    var pos = input.selectionStart;
    input.focus();
    input.setSelectionRange(pos, pos);
  }
}

var _custCardSeq = 0;
function renderCustomerCard(c) {
  var idx = _custCardSeq++;
  var info = HEALTH_INFO[c.health];
  var yoyText, yoyColor;
  if (c.yoyPct === null) {
    yoyText = '—'; yoyColor = 'var(--text2)';
  } else if (c.yoyPct > 0) {
    yoyText = '▲ +' + c.yoyPct + '%'; yoyColor = 'var(--red)';
  } else if (c.yoyPct < 0) {
    yoyText = '▼ ' + c.yoyPct + '%'; yoyColor = 'var(--green)';
  } else {
    yoyText = '0%'; yoyColor = 'var(--text2)';
  }
  var nameEsc = String(c.name).replace(/'/g, "\\'");
  var gradeColors = {'特':'#ff2a85','A':'var(--green)','B':'var(--blue)','C':'var(--text2)'};
  var gradeHtml = c.grade ? '<span style="background:' + (gradeColors[c.grade]||'var(--text2)') + '20;color:' + (gradeColors[c.grade]||'var(--text2)') + ';border:1px solid ' + (gradeColors[c.grade]||'var(--text2)') + '40;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:800;margin-left:6px">' + c.grade + '</span>' : '';
  var targetHtml = '';
  if (c.target) {
    var achPct = Math.round(c.thisYearAmount / c.target * 100);
    var achColor = achPct >= 100 ? 'var(--green)' : achPct >= 70 ? 'var(--gold)' : 'var(--red)';
    targetHtml = '<div style="margin-top:8px">' +
      '<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text2);margin-bottom:3px">' +
        '<span>年度目標達成率</span><span style="color:' + achColor + ';font-weight:800">' + achPct + '%' + (c.contrib ? '　貢獻度 ' + c.contrib : '') + '</span>' +
      '</div>' +
      '<div style="background:var(--border);border-radius:4px;height:6px"><div style="width:' + Math.min(achPct,100) + '%;background:' + achColor + ';border-radius:4px;height:6px"></div></div>' +
      '<div style="font-size:11px;color:var(--text2);margin-top:2px">目標 ' + fmt(c.target) + '　實績 ' + fmt(c.thisYearAmount) + '</div>' +
    '</div>';
  }
  return '<div class="product-card" style="grid-template-columns:1fr;cursor:pointer" onclick="toggleCustomerExpand(' + idx + ', \'' + nameEsc + '\')">' +
    '<div class="prod-info">' +
      '<div class="prod-summary-row">' +
        '<div class="prod-summary-main"><div class="prod-title">' + c.name + gradeHtml + '</div></div>' +
        '<div class="prod-summary-stats">' +
          '<div class="prod-stat"><div class="ps-label">今年業績</div><div class="ps-value">' + fmt(c.thisYearAmount) + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">YOY</div><div class="ps-value" style="color:' + yoyColor + '">' + yoyText + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">最後下單</div><div class="ps-value">' + (c.lastOrderDate || '無') + (c.daysSinceLastOrder !== null ? '（' + c.daysSinceLastOrder + '天前）' : '') + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">拜訪次數</div><div class="ps-value">' + c.visits + '</div></div>' +
          '<div class="prod-stat"><div class="ps-label">平均毛利率</div><div class="ps-value" style="color:' + (c.avgMarginPct === null ? 'var(--text2)' : (c.avgMarginPct < 15 ? 'var(--red)' : 'var(--text1)')) + '">' + (c.avgMarginPct === null ? '—' : c.avgMarginPct + '%') + '</div></div>' +
        '</div>' +
      '</div>' +
      targetHtml +
      '<div class="action-badge" style="background:' + info.color + '15;border:1px solid ' + info.color + '30;color:' + info.color + ';padding:4px 8px;border-radius:6px;font-size:11px;font-weight:800;display:inline-block;margin-top:6px">' + info.label + '</div>' +
      '<div style="margin-top:8px;font-size:11px;font-weight:800;color:var(--text2)">📊 業績表現</div>' +
      renderYoyBar(c) +
      renderLowMarginDeals(c.lowMarginDeals) +
      (c.activeDisplayCount > 0 ? '<div style="margin-top:8px;font-size:11px;font-weight:800;color:var(--text2)">🏪 版面陳列</div>' + renderActiveDisplays(c) : '') +
      '<div style="margin-top:8px;font-size:11px;font-weight:800;color:var(--text2)">🤝 互動紀錄</div>' +
      (c.lastNote ? '<div style="margin-top:4px;font-size:12px;color:var(--text2)">最新備註（' + (c.lastNoteDate || '') + '）：' + c.lastNote + '</div>' : '') +
      renderCatBreakdown(c.catCounts) +
      (c.contractHealth ? '<div style="margin-top:8px;font-size:11px;font-weight:800;color:var(--text2)">📋 合約狀態</div>' + renderContractBadge(c) : '') +
      '<div id="cust-expand-' + idx + '" style="display:none;margin-top:10px;border-top:1px solid var(--border);padding-top:10px" onclick="event.stopPropagation()"></div>' +
    '</div>' +
  '</div>';
}

var CONTRACT_HEALTH_COLOR = {
  '嚴重': '#e5484d', '逾期': '#e5484d', '待續約': '#f5a623', '暫停續約': '#f5a623', '已續約': '#3ecf8e', '正常': 'var(--text2)'
};

function renderContractBadge(c) {
  if (!c.contractHealth) return '';
  var color = CONTRACT_HEALTH_COLOR[c.contractHealth] || 'var(--text2)';
  var html = '<div style="margin-left:6px;display:inline-block">' +
    '<span style="background:' + color + '15;border:1px solid ' + color + '30;color:' + color + ';padding:4px 8px;border-radius:6px;font-size:11px;font-weight:800">合約：' + c.contractHealth + '</span>';
  if (c.contractBalance !== null) {
    html += ' <span style="font-size:11px;color:' + (c.contractBalance < 0 ? 'var(--red)' : 'var(--text2)') + '">餘額 ' + fmt(c.contractBalance) + '</span>';
  }
  if (c.contractExpiry) {
    html += ' <span style="font-size:11px;color:var(--text2)">到期 ' + c.contractExpiry + '</span>';
  }
  html += '</div>';
  return html;
}

function renderActiveDisplays(c) {
  if (!c.activeDisplayCount) return '';
  return '<div style="margin-top:4px;font-size:12px;color:var(--text2)">現正陳列 ' + c.activeDisplayCount + ' 個 SKU，點擊卡片查看版面陳列牆 →</div>';
}

function renderYoyBar(c) {
  var max = Math.max(c.thisYearAmount, c.lastYearAmount, 1);
  var thisPct = Math.round(c.thisYearAmount / max * 100);
  var lastPct = Math.round(c.lastYearAmount / max * 100);
  return '<div style="margin-top:8px;display:flex;flex-direction:column;gap:3px">' +
    '<div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text2)">' +
      '<span style="width:34px;flex-shrink:0">今年</span>' +
      '<div style="flex:1;background:var(--bg2);border-radius:3px;height:8px;overflow:hidden"><div style="width:' + thisPct + '%;height:100%;background:var(--gold)"></div></div>' +
      '<span style="width:50px;text-align:right;flex-shrink:0">' + fmt(c.thisYearAmount) + '</span>' +
    '</div>' +
    '<div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text2)">' +
      '<span style="width:34px;flex-shrink:0">去年同期</span>' +
      '<div style="flex:1;background:var(--bg2);border-radius:3px;height:8px;overflow:hidden"><div style="width:' + lastPct + '%;height:100%;background:var(--text2)"></div></div>' +
      '<span style="width:50px;text-align:right;flex-shrink:0">' + fmt(c.lastYearAmount) + '</span>' +
    '</div>' +
  '</div>';
}

function renderCatBreakdown(catCounts) {
  if (!catCounts) return '';
  var total = 0;
  Object.keys(catCounts).forEach(function(k) { total += catCounts[k]; });
  if (total === 0) return '';
  var order = ['客訴', '送樣', '帳款', '版面', '送貨退貨', '案件', '客情', '其他'];
  var html = '<div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">';
  order.forEach(function(cat) {
    var n = catCounts[cat] || 0;
    if (n === 0) return;
    var pct = Math.round(n / total * 100);
    var color = CAT_COLOR[cat] || 'var(--text2)';
    html += '<span style="font-size:11px;color:' + color + ';border:1px solid ' + color + '40;border-radius:5px;padding:2px 6px">' + cat + ' ' + n + ' (' + pct + '%)</span>';
  });
  html += '</div>';
  return html;
}

function renderLowMarginDeals(deals) {
  if (!deals || deals.length === 0) return '';
  var uid = 'lmd-' + Math.random().toString(36).slice(2,7);
  var html = '<div style="margin-top:6px">' +
    '<button class="btn" style="padding:3px 8px;font-size:11px;color:var(--red);border-color:var(--red)40" onclick="event.stopPropagation();var el=document.getElementById(\'' + uid + '\');el.style.display=el.style.display===\'none\'?\'block\':\'none\'">⚠ 低毛利案件 ' + deals.length + ' 筆 ▾</button>' +
    '<div id="' + uid + '" style="display:none;margin-top:4px;display:none">' +
    '<table style="font-size:11px;border-collapse:collapse;width:100%">' +
    '<tr style="color:var(--text2)"><th style="text-align:left;padding:2px 6px">日期</th><th style="text-align:left;padding:2px 6px">SKU</th><th style="text-align:left;padding:2px 6px">案名</th><th style="text-align:right;padding:2px 6px">數量</th><th style="text-align:right;padding:2px 6px">金額</th><th style="text-align:right;padding:2px 6px;color:var(--red)">毛利率</th></tr>';
  deals.forEach(function(d) {
    var c = d.marginPct < 0 ? 'var(--red)' : '#f5a623';
    html += '<tr style="border-top:1px solid var(--border)20">' +
      '<td style="padding:2px 6px;color:var(--text2)">' + d.date + '</td>' +
      '<td style="padding:2px 6px">' + d.sku + '</td>' +
      '<td style="padding:2px 6px;color:var(--text2);max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (d.note || '-') + '</td>' +
      '<td style="padding:2px 6px;text-align:right;color:var(--text2)">' + d.qty + '片</td>' +
      '<td style="padding:2px 6px;text-align:right">' + fmtNum(d.amount) + '</td>' +
      '<td style="padding:2px 6px;text-align:right;color:' + c + ';font-weight:800">' + d.marginPct + '%</td>' +
    '</tr>';
  });
  html += '</table></div></div>';
  return html;
}

function renderWarRoomDisplays(data) {
  if (!data || !data.displays || data.displays.length === 0) return '';
  var html = '<div style="margin-bottom:14px">';
  html += '<div style="font-size:13px;font-weight:800;color:var(--gold);margin-bottom:6px">🏪 版面陳列牆（現正陳列 ' + data.displayCount + ' 個 SKU / ' + (data.displays.length) + ' 版，近一年銷售 ' + fmt(data.totalAmt) + '・' + data.totalPing + ' 坪）</div>';
  html += '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px">';
  data.displays.forEach(function(g) {
    var daysText = g.days !== null ? '已上架 ' + g.days + ' 天' : '';
    var imgHtml = g.photoUrl ?
      '<div style="width:100%;aspect-ratio:1;background-image:url(\'' + g.photoUrl + '\');background-size:cover;background-position:center;border-radius:6px 6px 0 0"></div>' :
      '<div style="width:100%;aspect-ratio:1;background:var(--bg2);border-radius:6px 6px 0 0;display:flex;align-items:center;justify-content:center;color:var(--text2);font-size:11px">無照片</div>';
    html += '<div style="border:1px solid var(--border);border-radius:6px;overflow:hidden;background:var(--bg2)">' +
      imgHtml +
      '<div style="padding:6px">' +
        '<div style="font-size:12px;font-weight:800">' + g.series + '</div>' +
        '<div style="font-size:11px;color:var(--text2)">' + daysText + '</div>' +
        '<div style="font-size:12px;color:var(--gold);font-weight:800;margin-top:2px">' + fmt(g.totalAmt) + '・' + g.totalPing.toFixed(1) + ' 坪</div>' +
        '<div style="margin-top:4px;display:flex;flex-direction:column;gap:2px">' +
          g.items.map(function(it) {
            var crown = it.sku === g.topSku && g.totalAmt > 0 ? ' 👑' : '';
            var stockColor = it.stockPing <= 0 ? 'var(--red)' : (it.stockPing < 5 ? '#f5a623' : 'var(--text2)');
            return '<div style="font-size:10px;color:var(--text2)">' + it.sku + crown +
              '　餘 <span style="color:' + stockColor + '">' + it.stockPing + ' 坪</span>' +
              (it.reservedQty > 0 ? '　保留 ' + it.reservedQty + ' 片' : '') +
            '</div>';
          }).join('') +
        '</div>' +
      '</div>' +
    '</div>';
  });
  html += '</div></div>';
  return html;
}

function renderCatPie(catCounts) {
  if (!catCounts) return '';
  var order = ['客訴', '送樣', '帳款', '版面', '送貨退貨', '案件', '客情', '其他'];
  var total = 0;
  order.forEach(function(cat) { total += (catCounts[cat] || 0); });
  if (total === 0) return '';
  var gradParts = [];
  var acc = 0;
  var legend = '';
  order.forEach(function(cat) {
    var n = catCounts[cat] || 0;
    if (n === 0) return;
    var color = CAT_COLOR[cat] || 'var(--text2)';
    var pct = n / total * 100;
    gradParts.push(color + ' ' + acc + '% ' + (acc + pct) + '%');
    acc += pct;
    legend += '<div style="display:flex;align-items:center;gap:5px;font-size:11px;color:var(--text2)">' +
      '<span style="width:8px;height:8px;border-radius:2px;background:' + color + ';display:inline-block"></span>' +
      cat + ' ' + n + ' (' + Math.round(n / total * 100) + '%)</div>';
  });
  return '<div style="display:flex;align-items:center;gap:14px">' +
    '<div style="width:80px;height:80px;border-radius:50%;background:conic-gradient(' + gradParts.join(',') + ');flex-shrink:0"></div>' +
    '<div style="display:flex;flex-direction:column;gap:3px;flex-wrap:wrap">' + legend + '</div>' +
  '</div>';
}

function toggleCustomerExpand(idx, customer) {
  var el = document.getElementById('cust-expand-' + idx);
  if (!el) return;
  if (el.style.display === 'none' || el.style.display === '') {
    el.style.display = 'block';
    if (!el.dataset.loaded) {
      el.dataset.loaded = '1';
      loadCustomerExpand(el, customer);
    }
  } else {
    el.style.display = 'none';
  }
}

function loadCustomerExpand(el, customer) {
  el.innerHTML = '<div style="color:var(--text2);font-size:12px">載入中...</div>';

  var c = (window._customerData || []).find(function(x) { return x.name === customer; });
  var headerHtml = '';
  if (c) {
    var pie = renderCatPie(c.catCounts);
    if (pie) {
      headerHtml += '<div style="margin-bottom:10px"><div style="font-size:12px;font-weight:800;color:var(--gold);margin-bottom:6px">🤝 互動類型分布</div>' + pie + '</div>';
    }
  }

  var warRoomHtml = '';
  var timelineHtml = '';
  var salesHtml = '';
  var custCtx = c || {};
  var chatId = 'ai-chat-' + Math.random().toString(36).slice(2,8);
  var renderAll = function() {
    if (warRoomHtml === null || timelineHtml === null || salesHtml === null) return;
    el.innerHTML = headerHtml + salesHtml + warRoomHtml +
      '<div style="margin-top:10px"><button class="btn" style="padding:6px 12px;font-size:12px" onclick="event.stopPropagation();var el=this.nextElementSibling;el.style.display=el.style.display===\'none\'?\'block\':\'none\'">📜 互動紀錄明細 ▾</button>' +
      '<div style="display:none;margin-top:10px">' + timelineHtml + '</div></div>' +
      renderAiChat(chatId, customer);
    initAiChat(chatId, custCtx);
  };

  apiGet('customer-sales-breakdown', { customer: customer }, function(res) {
    salesHtml = res.success ? renderSalesBreakdown(res.data) : '';
    renderAll();
  }, function() { salesHtml = ''; renderAll(); });

  apiGet('customer-warroom', { customer: customer }, function(res) {
    warRoomHtml = (res.success ? renderWarRoomDisplays(res.data) : '');
    renderAll();
  }, function() { warRoomHtml = ''; renderAll(); });

  apiGet('customer-timeline', { customer: customer }, function(res) {
    if (!res.success) { timelineHtml = '<div style="color:var(--text2)">載入失敗</div>'; renderAll(); return; }
    var rows = res.data.timeline || [];
    if (rows.length === 0) { timelineHtml = '<div style="color:var(--text2)">無歷史記錄</div>'; renderAll(); return; }
    var typeColor = { '銷售': '#3ecf8e', '拜訪': '#60a5fa', '備註': 'var(--gold)' };
    var html = '<div style="display:flex;flex-direction:column;gap:8px">';
    rows.forEach(function(r) {
      var c = typeColor[r.type] || 'var(--text2)';
      var catBadge = (r.type === '拜訪' && r.category) ?
        '<span style="font-size:10px;color:' + (CAT_COLOR[r.category] || 'var(--text2)') + ';border:1px solid ' + (CAT_COLOR[r.category] || 'var(--text2)') + '40;border-radius:4px;padding:1px 5px;margin-right:6px">' + r.category + '</span>' : '';
      html += '<div style="display:flex;gap:10px;border-top:1px solid var(--border);padding-top:6px;font-size:12px">' +
        '<div style="width:90px;color:var(--text2);flex-shrink:0">' + r.date + '</div>' +
        '<div style="width:48px;flex-shrink:0;color:' + c + ';font-weight:800">' + r.type + '</div>' +
        '<div style="flex:1;min-width:0">' + catBadge + (r.desc || '') + (r.sales ? '　<span style="color:var(--text2)">（' + r.sales + '）</span>' : '') + '</div>' +
        '</div>';
    });
    html += '</div>';
    timelineHtml = html;
    renderAll();
  }, function() { timelineHtml = ''; renderAll(); });

  warRoomHtml = null; timelineHtml = null; salesHtml = null;
}

function renderSalesBreakdown(data) {
  if (!data || !data.skus || data.skus.length === 0) return '';
  var periods = [
    { key: 'month',    label: '當月' },
    { key: 'quarter',  label: '當季' },
    { key: 'half',     label: '近半年' },
    { key: 'prevYear', label: data.prevYearLabel || '前年全年' }
  ];
  var totals = data.totals || {};
  var skus = data.skus;

  // 找各時段最大值，用來畫橫條比例
  var maxByPeriod = {};
  periods.forEach(function(p) {
    maxByPeriod[p.key] = Math.max.apply(null, skus.map(function(s){ return s[p.key] || 0; })) || 1;
  });

  var html = '<div style="margin-bottom:14px"><div style="font-size:12px;font-weight:800;color:var(--gold);margin-bottom:8px">📦 銷售品項分析</div>';

  // 時段總額概覽
  html += '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">';
  periods.forEach(function(p) {
    html += '<div style="background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:6px 10px;min-width:80px">' +
      '<div style="font-size:10px;color:var(--text2)">' + p.label + '</div>' +
      '<div style="font-size:14px;font-weight:800;color:var(--gold)">' + fmt(totals[p.key] || 0) + '</div>' +
    '</div>';
  });
  html += '</div>';

  // 選擇時段 tab
  var tabId = 'sbtab-' + Math.random().toString(36).slice(2,6);
  html += '<div id="' + tabId + '-tabs" style="display:flex;gap:4px;margin-bottom:8px">';
  periods.forEach(function(p, i) {
    html += '<button class="btn" style="padding:3px 10px;font-size:11px' + (i===2?';border-color:var(--gold);color:var(--gold)':'') + '" onclick="event.stopPropagation();showSalesTab(\'' + tabId + '\',\'' + p.key + '\',this)">' + p.label + '</button>';
  });
  html += '</div>';

  // 每個時段的 SKU 排行
  periods.forEach(function(p, i) {
    var sorted = skus.slice().sort(function(a,b){ return b[p.key]-a[p.key]; }).filter(function(s){ return s[p.key]>0; });
    html += '<div id="' + tabId + '-' + p.key + '" style="display:' + (i===2?'block':'none') + '">';
    if (sorted.length === 0) { html += '<div style="font-size:12px;color:var(--text2)">此時段無銷售記錄</div>'; }
    sorted.slice(0, 10).forEach(function(s, si) {
      var pct = Math.round(s[p.key] / maxByPeriod[p.key] * 100);
      var color = si===0 ? 'var(--gold)' : (si<=2 ? '#60a5fa' : 'var(--text2)');
      var pingKey = p.key + 'Ping';
      html += '<div style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:4px">' +
        '<span style="width:16px;text-align:right;color:var(--text2);font-size:10px;flex-shrink:0">' + (si===0?'🥇':si===1?'🥈':si===2?'🥉':(si+1)) + '</span>' +
        '<span style="width:90px;flex-shrink:0;color:var(--text1);font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + s.series + '">' + s.sku + '</span>' +
        '<div style="flex:1;background:var(--bg2);border-radius:3px;height:8px;overflow:hidden"><div style="width:' + pct + '%;height:100%;background:' + color + '"></div></div>' +
        '<span style="width:50px;text-align:right;flex-shrink:0;font-weight:800">' + fmt(s[p.key]) + '</span>' +
        '<span style="width:36px;text-align:right;flex-shrink:0;color:var(--text2);font-size:10px">' + (s[pingKey]||0) + '坪</span>' +
      '</div>';
    });
    html += '</div>';
  });

  html += '</div>';
  return html;
}

function showSalesTab(tabId, period, btn) {
  ['month','quarter','half','prevYear'].forEach(function(k) {
    var el = document.getElementById(tabId + '-' + k);
    if (el) el.style.display = k === period ? 'block' : 'none';
  });
  var tabs = document.getElementById(tabId + '-tabs');
  if (tabs) tabs.querySelectorAll('button').forEach(function(b) {
    b.style.borderColor = ''; b.style.color = '';
  });
  if (btn) { btn.style.borderColor = 'var(--gold)'; btn.style.color = 'var(--gold)'; }
}

function renderAiChat(chatId, customer) {
  return '<div style="margin-top:14px;border-top:1px solid var(--border);padding-top:12px">' +
    '<div style="font-size:12px;font-weight:800;color:var(--gold);margin-bottom:8px">🤖 AI 客戶顧問</div>' +
    '<div id="' + chatId + '-msgs" style="display:flex;flex-direction:column;gap:8px;max-height:320px;overflow-y:auto;margin-bottom:8px;padding:4px 0"></div>' +
    '<div style="display:flex;gap:6px;align-items:flex-end">' +
      '<textarea id="' + chatId + '-input" placeholder="問任何關於 ' + customer + ' 的問題，例如：這個客戶最近業績下滑的原因？該怎麼挽救？" rows="2" style="flex:1;background:var(--bg2);border:1px solid var(--border);color:var(--text1);border-radius:6px;padding:8px;font-size:12px;resize:none;font-family:inherit" onkeydown="if((event.metaKey||event.ctrlKey)&&event.key===\'Enter\'){event.stopPropagation();sendAiChat(\'' + chatId + '\')}"></textarea>' +
      '<button class="btn" style="padding:8px 14px;font-size:12px" onclick="event.stopPropagation();sendAiChat(\'' + chatId + '\')">送出<br><span style="font-size:9px;color:var(--text2)">⌘↵</span></button>' +
    '</div>' +
    '<div id="' + chatId + '-status" style="font-size:11px;color:var(--text2);margin-top:4px;min-height:16px"></div>' +
  '</div>';
}

var _aiChatStore = {};
function initAiChat(chatId, custCtx) {
  _aiChatStore[chatId] = { history: [], ctx: custCtx };
}

function sendAiChat(chatId) {
  var inputEl = document.getElementById(chatId + '-input');
  var msgsEl  = document.getElementById(chatId + '-msgs');
  var statusEl = document.getElementById(chatId + '-status');
  if (!inputEl || !msgsEl) return;
  var msg = inputEl.value.trim();
  if (!msg) return;

  var store = _aiChatStore[chatId] || { history: [], ctx: {} };

  // 顯示用戶訊息
  msgsEl.innerHTML += '<div style="display:flex;justify-content:flex-end"><div style="background:var(--gold)18;border:1px solid var(--gold)30;border-radius:10px 10px 2px 10px;padding:8px 12px;max-width:80%;font-size:12px;white-space:pre-wrap">' + msg.replace(/</g,'&lt;') + '</div></div>';
  inputEl.value = '';
  statusEl.textContent = 'AI 思考中...';
  msgsEl.scrollTop = msgsEl.scrollHeight;

  fetch('api.php?action=customer-ai-chat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ customer: store.ctx.name || '', message: msg, history: store.history, context: store.ctx })
  })
  .then(function(r){ return r.json(); })
  .then(function(res) {
    statusEl.textContent = '';
    if (!res.success) {
      msgsEl.innerHTML += '<div style="color:var(--red);font-size:12px">❌ ' + (res.msg||'錯誤') + '</div>';
    } else {
      var reply = res.reply || '';
      // 簡單 markdown 粗體支援
      var formatted = reply.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');
      msgsEl.innerHTML += '<div style="display:flex;gap:6px"><div style="width:22px;height:22px;border-radius:50%;background:var(--gold)20;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0">🤖</div><div style="background:var(--bg2);border:1px solid var(--border);border-radius:2px 10px 10px 10px;padding:8px 12px;max-width:85%;font-size:12px;line-height:1.6">' + formatted + '</div></div>';
      store.history.push({ role: 'user', content: msg });
      store.history.push({ role: 'model', content: reply });
      if (store.history.length > 20) store.history = store.history.slice(-20);
    }
    msgsEl.scrollTop = msgsEl.scrollHeight;
  })
  .catch(function(e) {
    statusEl.textContent = '';
    msgsEl.innerHTML += '<div style="color:var(--red);font-size:12px">❌ 連線錯誤</div>';
  });
}

function showLifecycle(sku) {
  var title = document.getElementById('modal-title');
  var body = document.getElementById('modal-body');
  title.textContent = sku + ' 產品生命週期';
  body.innerHTML = '載入中...';
  document.getElementById('modal-detail').classList.remove('hidden');

  apiGet('product-lifecycle', { sku: sku }, function(res) {
    if (!res.success) { body.innerHTML = '<div style="color:var(--text2)">載入失敗</div>'; return; }
    var d = res.data;
    var html = '<div style="display:flex;flex-direction:column;gap:10px">';

    html += '<div class="prod-stats">' +
      '<div class="prod-stat"><div class="ps-label">首次進貨日</div><div class="ps-value">' + (d.firstInDate || '未記錄') + '</div></div>' +
      '<div class="prod-stat"><div class="ps-label">距今天數</div><div class="ps-value">' + (d.daysSinceArrival === null ? '—' : d.daysSinceArrival + ' 天') + '</div></div>' +
      '<div class="prod-stat"><div class="ps-label">最新進貨日</div><div class="ps-value">' + (d.latestInDate || '未記錄') + '</div></div>' +
      '<div class="prod-stat"><div class="ps-label">目前上架通路</div><div class="ps-value text-gold">' + d.displayCount + ' 家</div></div>' +
      '</div>';

    if (d.monthlyHistory && d.monthlyHistory.length > 0) {
      html += '<div class="ps-label" style="margin-top:8px">每月觀察記錄</div>';
      html += '<div style="overflow-x:auto"><table style="width:100%;font-size:12px;border-collapse:collapse">' +
        '<tr style="color:var(--text2);text-align:right"><th style="text-align:left;padding:4px">月份</th><th style="padding:4px">庫存(坪)</th><th style="padding:4px">歷史銷售(坪)</th><th style="padding:4px">月均銷(坪)</th><th style="padding:4px">MOS</th><th style="padding:4px">未售天數</th><th style="padding:4px">保留筆數</th><th style="padding:4px">保留數量</th></tr>' +
        d.monthlyHistory.map(function(r) {
          return '<tr style="border-top:1px solid var(--border);text-align:right">' +
            '<td style="text-align:left;padding:4px">' + r.date + '</td>' +
            '<td style="padding:4px">' + (r.stockPing ?? '-') + '</td>' +
            '<td style="padding:4px">' + (r.totalPings ?? '-') + '</td>' +
            '<td style="padding:4px">' + (r.monthlySpeedPings ?? '-') + '</td>' +
            '<td style="padding:4px">' + (r.mos ?? '-') + '</td>' +
            '<td style="padding:4px">' + (r.daysSinceLastSale === null || r.daysSinceLastSale === undefined ? '-' : r.daysSinceLastSale) + '</td>' +
            '<td style="padding:4px">' + (r.reservedCount ?? 0) + '</td>' +
            '<td style="padding:4px">' + (r.reservedQty ?? 0) + '</td>' +
            '</tr>';
        }).join('') +
        '</table></div>';
    } else {
      html += '<div style="color:var(--text2);font-size:12px;margin-top:8px">尚無每月觀察記錄，將於下次重建快取後開始累積。</div>';
    }

    if (d.displays && d.displays.length > 0) {
      html += '<div class="ps-label" style="margin-top:8px">目前陳列通路（' + d.displays.length + '）</div>';
      html += '<div style="display:flex;flex-direction:column;gap:6px;font-size:12px">' +
        d.displays.map(function(disp) {
          return '<div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:4px"><span>' + disp.cust + '</span><span style="color:var(--text2)">' + (disp.date || '未記錄') + '</span></div>';
        }).join('') +
        '</div>';
    }

    html += '</div>';
    body.innerHTML = html;
  });
}

function closeDetail() {
  document.getElementById('modal-detail').classList.add('hidden');
}

// ── 全域 AI 顧問 ──────────────────────────────────────────
var _globalAiHistory = [];

function buildGlobalAiContext() {
  var tab = (typeof currentTab !== 'undefined') ? currentTab : '';
  var ctx = {};
  if (tab === 'products' && window._normalData) {
    ctx.products = (window._normalData.sleeper||[]).concat(window._normalData.normal||[]).slice(0,40).map(function(p){
      return { sku:p.sku, seriesCn:p.seriesCn||p.series||'', stockPing:p.stockPing||0, monthlySpeedPings:p.monthlySpeedPings||0, grade:p.grade||p.mosLevel||0, marginPct:p.marginPct||0 };
    });
  } else if (tab === 'reports' && window._strategyReport) {
    var r = window._strategyReport;
    ctx.periodTotal = r.periodTotal||0;
    ctx.yoyPct = r.yoyPct||null;
    ctx.topProducts = (r.rankings||r.topProducts||[]).slice(0,15).map(function(p){ return {sku:p.sku,seriesCn:p.seriesCn||p.series||'',totalPings:p.totalPings||p.pings||0,totalAmt:p.totalAmt||p.amt||0}; });
    ctx.trend = (r.trend||[]).map(function(m){ return {month:m.month,amt:m.amt||0,lastYearAmt:m.lastYearAmt||0}; });
  } else if (tab === 'bonus' && (window._dashboard||window._bonusData)) {
    var d = window._dashboard||{};
    ctx.thisYearAmt = d.thisYearAmt||d.totalAmt||0;
    ctx.yoyPct = d.yoyPct||null;
    ctx.topSkus = (window._bonusData||window._sleeperData||[]).slice(0,20).map(function(p){ return {sku:p.sku,seriesCn:p.seriesCn||p.series||'',stockPing:p.stockPing||0,monthlySpeedPings:p.monthlySpeedPings||0,grade:p.mosLevel||0}; });
  } else if (tab === 'customers' && window._customerSummary) {
    ctx = window._customerSummary;
  }
  return { tab: tab, context: ctx };
}

function toggleGlobalAi() {
  var drawer = document.getElementById('global-ai-drawer');
  var isOpen = drawer.style.transform === 'translateY(0px)' || drawer.style.transform === 'translateY(0%)';
  drawer.style.transform = isOpen ? 'translateY(100%)' : 'translateY(0%)';
  if (!isOpen) {
    setTimeout(function(){ var inp = document.getElementById('global-ai-input'); if(inp) inp.focus(); }, 200);
  }
}

function sendGlobalAi() {
  var inputEl = document.getElementById('global-ai-input');
  var msgsEl = document.getElementById('global-ai-msgs');
  var statusEl = document.getElementById('global-ai-status');
  var msg = inputEl.value.trim();
  if (!msg) return;

  msgsEl.innerHTML += '<div style="display:flex;justify-content:flex-end;margin-bottom:8px"><div style="background:var(--gold)18;border:1px solid var(--gold)30;border-radius:10px 10px 2px 10px;padding:8px 12px;max-width:80%;font-size:13px;white-space:pre-wrap">' + msg.replace(/</g,'&lt;') + '</div></div>';
  inputEl.value = '';
  statusEl.textContent = 'AI 思考中...';
  msgsEl.scrollTop = msgsEl.scrollHeight;

  var tabCtx = buildGlobalAiContext();

  fetch('api.php?action=global-ai-chat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message: msg, history: _globalAiHistory, tab: tabCtx.tab, context: tabCtx.context })
  })
  .then(function(r){ return r.json(); })
  .then(function(res) {
    statusEl.textContent = '';
    if (!res.success) {
      msgsEl.innerHTML += '<div style="color:var(--red);font-size:13px;margin-bottom:8px">❌ ' + (res.msg||'錯誤') + '</div>';
    } else {
      var reply = res.reply || '';
      var formatted = reply.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');
      msgsEl.innerHTML += '<div style="display:flex;gap:8px;margin-bottom:8px"><div style="width:26px;height:26px;border-radius:50%;background:var(--gold)20;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-top:2px">🤖</div><div style="background:var(--bg2);border:1px solid var(--border);border-radius:2px 10px 10px 10px;padding:10px 14px;max-width:85%;font-size:13px;line-height:1.7">' + formatted + '</div></div>';
      _globalAiHistory.push({ role:'user', content: msg });
      _globalAiHistory.push({ role:'model', content: reply });
      if (_globalAiHistory.length > 20) _globalAiHistory = _globalAiHistory.slice(-20);
    }
    msgsEl.scrollTop = msgsEl.scrollHeight;
  })
  .catch(function(){ statusEl.textContent=''; msgsEl.innerHTML += '<div style="color:var(--red);font-size:13px;margin-bottom:8px">❌ 連線錯誤</div>'; });
}

</script>

<!-- 全域 AI 浮動按鈕 -->
<button onclick="toggleGlobalAi()" style="position:fixed;bottom:28px;right:28px;z-index:9998;width:52px;height:52px;border-radius:50%;background:var(--gold);border:none;cursor:pointer;font-size:22px;box-shadow:0 4px 16px rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;color:#000" title="AI 業務顧問">🤖</button>

<!-- AI 對話抽屜 -->
<div id="global-ai-drawer" style="position:fixed;bottom:0;right:0;width:420px;max-width:100vw;height:560px;background:var(--bg1);border:1px solid var(--border);border-bottom:none;border-radius:12px 12px 0 0;z-index:9997;transform:translateY(100%);transition:transform 0.3s ease;display:flex;flex-direction:column;box-shadow:0 -4px 24px rgba(0,0,0,0.4)">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);flex-shrink:0">
    <div>
      <span style="font-size:14px;font-weight:800;color:var(--gold)">🤖 AI 業務顧問</span>
      <span style="font-size:11px;color:var(--text2);margin-left:8px">自動讀取當前分頁資料</span>
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="_globalAiHistory=[];document.getElementById('global-ai-msgs').innerHTML='';document.getElementById('global-ai-status').textContent=''" style="background:none;border:1px solid var(--border);color:var(--text2);border-radius:5px;padding:3px 8px;cursor:pointer;font-size:11px">清除</button>
      <button onclick="toggleGlobalAi()" style="background:none;border:none;color:var(--text2);cursor:pointer;font-size:18px;padding:0 4px">×</button>
    </div>
  </div>
  <div id="global-ai-msgs" style="flex:1;overflow-y:auto;padding:14px 16px"></div>
  <div id="global-ai-status" style="padding:0 16px 4px;font-size:11px;color:var(--text2);min-height:18px;flex-shrink:0"></div>
  <div style="padding:10px 16px 14px;display:flex;gap:8px;flex-shrink:0;border-top:1px solid var(--border)">
    <textarea id="global-ai-input" placeholder="問任何關於當前分頁資料的問題... (⌘↵送出)" rows="2" style="flex:1;background:var(--bg2);border:1px solid var(--border);color:var(--text1);border-radius:8px;padding:8px 10px;font-size:13px;resize:none;font-family:inherit" onkeydown="if((event.metaKey||event.ctrlKey)&&event.key==='Enter')sendGlobalAi()"></textarea>
    <button onclick="sendGlobalAi()" style="background:var(--gold);border:none;color:#000;border-radius:8px;padding:8px 14px;cursor:pointer;font-size:13px;font-weight:800;flex-shrink:0">送出</button>
  </div>
</div>

</body>
</html>
