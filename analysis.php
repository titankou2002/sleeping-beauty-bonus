<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>新品生命週期分析</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23c29d66'/%3E%3Ctext x='32' y='46' font-family='Arial, sans-serif' font-size='36' font-weight='900' fill='%23000000' text-anchor='middle'%3EN%3C/text%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+TC:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
  <style>
:root {
  --bg: #0a0a0a;
  --surface: #0d0d0d;
  --surface2: rgba(255,255,255,0.04);
  --border: rgba(194,157,102,0.15);
  --text: #ffffff;
  --text2: rgba(255,255,255,0.45);
  --gold: #c29d66;
  --gold-soft: rgba(194,157,102,0.1);
  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --radius-xl: 18px;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: "Inter", -apple-system, "Noto Sans TC", sans-serif;
  min-height: 100vh; -webkit-font-smoothing: antialiased;
}
.app { display: flex; flex-direction: column; min-height: 100dvh; }
.topbar {
  position: sticky; top: 0; z-index: 100;
  background: rgba(10,10,10,0.88);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(194,157,102,0.2);
}
.topbar-inner {
  max-width: 1400px; margin: 0 auto;
  padding: 0 20px; height: 56px;
  display: flex; align-items: center; gap: 16px;
}
.logo {
  font-size: 16px; font-weight: 900; letter-spacing: 2px;
  color: var(--gold); text-shadow: 0 0 20px rgba(194,157,102,0.3);
  white-space: nowrap;
}
.main { max-width: 1400px; margin: 0 auto; padding: 20px; width: 100%; }
.ctrl-bar {
  display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
  margin-bottom: 20px;
}
.ctrl-label { font-size: 13px; color: var(--text2); font-weight: 600; }
select, button {
  font-family: inherit; font-size: 13px; font-weight: 600;
  padding: 8px 16px; border-radius: var(--radius-md);
  background: var(--surface2); color: var(--text);
  border: 1px solid var(--border); cursor: pointer;
  transition: all 0.2s;
}
select:hover, button:hover { border-color: var(--gold); background: var(--gold-soft); }
.btn-primary {
  background: linear-gradient(135deg, #c29d66, #d4b483);
  color: #000; border: none; font-weight: 800;
}
.btn-primary:hover { background: linear-gradient(135deg, #d4b483, #e6cba0); }
.grid-4 {
  display: grid; gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  margin-bottom: 20px;
}
.stat-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 14px 16px;
}
.stat-card .label { font-size: 11px; color: var(--text2); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
.stat-card .value { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; line-height: 1.1; }
.stat-card .sub { font-size: 11px; color: var(--text2); margin-top: 2px; }
.chart-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 20px;
  margin-bottom: 20px;
}
.chart-card .title { font-size: 15px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 12px; }
.chart-wrap { position: relative; height: 380px; }

.section-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); margin-bottom: 20px; overflow: hidden;
}
.section-card .section-title {
  padding: 14px 20px; font-size: 15px; font-weight: 700;
  border-bottom: 1px solid var(--border);
  display: flex; justify-content: space-between; align-items: center;
  cursor: pointer; user-select: none;
}
.section-card .section-title:hover { background: var(--surface2); }
.section-card .section-body { padding: 16px 20px; }

.grade-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.grade-pill {
  padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 800;
}
.grade-pill.A { background: rgba(34,197,94,0.15); color: #22c55e; }
.grade-pill.B { background: rgba(59,130,246,0.15); color: #60a5fa; }
.grade-pill.C { background: rgba(245,158,11,0.15); color: #f59e0b; }
.grade-pill.D { background: rgba(239,68,68,0.15); color: #ef4444; }
.grade-A, .text-A { color: #22c55e; }
.grade-B, .text-B { color: #60a5fa; }
.grade-C, .text-C { color: #f59e0b; }
.grade-D, .text-D { color: #ef4444; }

.series-badge {
  display: inline-block; padding: 2px 8px; border-radius: 4px;
  font-size: 11px; font-weight: 700; margin-right: 4px;
}
.series-badge.A { background: rgba(34,197,94,0.12); color: #22c55e; }
.series-badge.B { background: rgba(59,130,246,0.12); color: #60a5fa; }
.series-badge.C { background: rgba(245,158,11,0.12); color: #f59e0b; }
.series-badge.D { background: rgba(239,68,68,0.12); color: #ef4444; }

.table-card { border-radius: var(--radius-lg); overflow: hidden; background: var(--surface); border: 1px solid var(--border); margin-bottom: 20px; }
.table-card .header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 14px 20px; border-bottom: 1px solid var(--border);
}
.table-card .header .title { font-size: 15px; font-weight: 700; }
.table-scroll { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th {
  text-align: left; padding: 10px 12px;
  color: var(--text2); font-weight: 600; font-size: 11px;
  text-transform: uppercase; letter-spacing: 0.5px;
  border-bottom: 1px solid var(--border);
  white-space: nowrap; cursor: pointer; user-select: none;
}
th:hover { color: var(--gold); }
th.sorted { color: var(--gold); }
td { padding: 9px 12px; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
tr:hover td { background: var(--surface2); }
.mono { font-feature-settings: "tnum" 1; }
.ttfs-0 { color: #22c55e; }
.ttfs-1 { color: #22c55e; }
.ttfs-2 { color: #60a5fa; }
.ttfs-3 { color: #60a5fa; }
.ttfs-4 { color: #f59e0b; }
.ttfs-na { color: var(--text2); }
.rep-tag {
  display: inline-block; padding: 1px 6px; border-radius: 3px;
  font-size: 10px; font-weight: 600; margin: 1px 2px 1px 0;
  background: rgba(255,255,255,0.06); color: var(--text2);
}

.loading {
  position: fixed; inset: 0; z-index: 999;
  display: flex; align-items: center; justify-content: center;
  background: rgba(10,10,10,0.7); backdrop-filter: blur(4px);
}
.loading.hidden { display: none; }
.spinner { width: 40px; height: 40px; border-radius: 50%; border: 3px solid var(--border); border-top-color: var(--gold); animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.detail-overlay {
  position: fixed; inset: 0; z-index: 200;
  background: rgba(0,0,0,0.6); backdrop-filter: blur(6px);
  display: none; align-items: center; justify-content: center;
  padding: 20px;
}
.detail-overlay.open { display: flex; }
.detail-panel {
  background: #111; border: 1px solid var(--border);
  border-radius: var(--radius-xl); padding: 24px;
  max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto;
  position: relative;
}
.detail-close {
  position: absolute; top: 12px; right: 16px;
  background: none; border: none; color: var(--text2);
  font-size: 24px; cursor: pointer;
}
.detail-close:hover { color: var(--text); }
.detail-title { font-size: 18px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px; }
.detail-field {}
.detail-field .dl { font-size: 11px; color: var(--text2); font-weight: 600; margin-bottom: 2px; }
.detail-field .dd { font-size: 15px; font-weight: 700; }
.detail-section-title { font-size: 14px; font-weight: 700; margin: 14px 0 10px; padding-top: 12px; border-top: 1px solid var(--border); }
.pie-wrap { height: 260px; position: relative; }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text2); }
.empty-state .icon { font-size: 40px; margin-bottom: 12px; }
.empty-state .msg { font-size: 15px; font-weight: 600; }

@media (max-width: 600px) {
  .grid-4 { grid-template-columns: repeat(2, 1fr); }
  .stat-card .value { font-size: 20px; }
  .chart-wrap { height: 260px; }
  .detail-grid { grid-template-columns: 1fr; }
}
  </style>
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="topbar-inner">
      <div class="logo">新品生命週期分析</div>
    </div>
  </div>

  <div id="loading" class="loading">
    <div class="spinner"></div>
  </div>

  <div id="detail-overlay" class="detail-overlay" onclick="if(event.target===this)closeDetail()">
    <div class="detail-panel">
      <button class="detail-close" onclick="closeDetail()">&times;</button>
      <div id="detail-content"></div>
    </div>
  </div>

  <div class="main">
    <div class="ctrl-bar">
      <span class="ctrl-label">分析期間：</span>
      <select id="sel-months">
        <option value="12">12 個月</option>
        <option value="24" selected>24 個月</option>
        <option value="36">36 個月</option>
      </select>
      <select id="sel-series-filter" onchange="applySeriesFilter()">
        <option value="">全部系列</option>
      </select>
      <select id="sel-grade-filter" onchange="applySeriesFilter()">
        <option value="">全部分級</option>
        <option value="A">A 明星</option>
        <option value="B">B 潛力</option>
        <option value="C">C 觀察</option>
        <option value="D">D 弱勢</option>
      </select>
      <button class="btn-primary" onclick="loadData()">載入分析</button>
      <button onclick="toggleProducts()" id="btn-toggle-individual" style="display:none">顯示個別產品線</button>
    </div>

    <div id="summary-grid" class="grid-4"></div>

    <div class="chart-card">
      <div class="title">
        <span>新品群組滲透曲線</span>
        <span style="font-size:11px;color:var(--text2);font-weight:400">中位數銷售額 (P25 / P75)</span>
      </div>
      <div class="chart-wrap"><canvas id="cohort-chart"></canvas></div>
    </div>

    <div class="chart-card" id="individual-panel" style="display:none">
      <div class="title">
        <span>個別產品月銷售</span>
        <span style="font-size:11px;color:var(--text2);font-weight:400">前 20 支（按總額排序）</span>
      </div>
      <div class="chart-wrap"><canvas id="individual-chart"></canvas></div>
    </div>

    <div id="grade-groups-section" class="section-card" style="display:none">
      <div class="section-title" onclick="toggleSection(this)">
        <span>分級下探分析</span>
        <span style="font-size:11px;color:var(--text2)" id="grade-toggle-label">展開</span>
      </div>
      <div class="section-body" id="grade-groups-body" style="display:none"></div>
    </div>

    <div id="series-section" class="section-card" style="display:none">
      <div class="section-title" onclick="toggleSection(this)">
        <span>系列分類總覽</span>
        <span style="font-size:11px;color:var(--text2)" id="series-toggle-label">展開</span>
      </div>
      <div class="section-body" id="series-body" style="display:none"></div>
    </div>

    <div class="section-card" id="table-wrap">
      <div class="section-title" onclick="toggleSection(this)">
        <span>新品排行榜 <span id="product-count" style="font-size:12px;color:var(--text2);font-weight:400"></span></span>
        <span style="font-size:11px;color:var(--text2)">展開</span>
      </div>
      <div id="table-body-wrap" style="display:none">
        <div style="padding:10px 20px;border-bottom:1px solid var(--border);color:var(--text2);font-size:12px">點擊欄位排序 · 點選 SKU 開啟詳細</div>
        <div class="table-scroll">
          <table id="product-table">
            <thead>
              <tr>
                <th data-key="grade">級別</th>
                <th data-key="sku">SKU</th>
                <th data-key="series">系列</th>
                <th data-key="firstInDate">首次進貨</th>
                <th data-key="ttfs">TTFS</th>
                <th data-key="customers">銷售客戶</th>
                <th data-key="totalAmount">總銷售額</th>
                <th data-key="totalCount">交易次數</th>
                <th data-key="customerCount">銷售家數</th>
                <th data-key="displayCount">上架家數</th>
                <th data-key="score">分數</th>
              </tr>
            </thead>
            <tbody id="table-body"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var API_BASE = 'api.php';
var allData = null;
var cohortChart = null;
var individualChart = null;
var showIndividual = false;
var sortKey = 'score';
var sortAsc = false;
var pieChart = null;

function showLoading(v) { document.getElementById('loading').classList.toggle('hidden', !v); }
function toast(msg, isError) {
  var t = document.createElement('div');
  t.textContent = msg;
  t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:' + (isError ? '#ef4444' : '#0a0a0a') + ';border:2px solid ' + (isError ? '#ef4444' : 'var(--gold)') + ';color:#fff;padding:14px 28px;border-radius:10px;font-weight:700;z-index:1000;cursor:pointer;max-width:90vw;text-align:center;line-height:1.4;';
  t.onclick = function(){ t.remove(); };
  document.body.appendChild(t);
  setTimeout(function(){ t.remove(); }, isError ? 12000 : 5000);
}
function fmtNum(n) {
  if (n === null || n === undefined || isNaN(n)) return '-';
  if (Math.abs(n) >= 10000) return (n / 10000).toFixed(1) + '萬';
  if (Number.isInteger(n)) return n.toLocaleString();
  return n.toFixed(n % 1 === 0 ? 0 : 1);
}
function fmtDate(d) { if (!d) return '-'; var p = d.split('/'); return p[0] + '/' + p[1] + '/' + p[2]; }
function ttfsClass(v) {
  if (v < 0) return 'ttfs-na';
  return 'ttfs-' + Math.min(v, 4);
}
function ttfsLabel(v) {
  if (v < 0) return '從未交易';
  if (v === 0) return '當月即成交';
  return v + ' 月';
}
function gradeClass(g) { return 'grade-' + g; }

function loadData() {
  var months = document.getElementById('sel-months').value;
  showLoading(true);
  fetch(API_BASE + '?action=new-product-analysis&months=' + months)
    .then(function(r) { return r.json(); })
    .then(function(res) {
      showLoading(false);
      if (!res.success) { toast(res.msg || '載入失敗', true); return; }
      allData = res.data;
      renderSummary(res.data.summary);
      renderCohortCurve(res.data);
      renderGradeGroups(res.data.gradeGroups);
      renderSeriesGroups(res.data.seriesGroups);
      populateSeriesFilter(res.data.seriesGroups);
      applySeriesFilter();
      document.getElementById('btn-toggle-individual').style.display = 'inline-block';
      document.getElementById('grade-groups-section').style.display = '';
      document.getElementById('series-section').style.display = '';
    })
    .catch(function(err) { showLoading(false); toast('連線失敗: ' + err, true); });
}

function renderSummary(s) {
  var html = '';
  html += '<div class="stat-card"><div class="label">新品總數</div><div class="value">' + s.totalProducts + '</div><div class="sub">分析期間內首次進貨</div></div>';
  html += '<div class="stat-card"><div class="label">A 明星</div><div class="value grade-A">' + s.gradeA + '</div><div class="sub">' + (s.totalProducts ? (s.gradeA / s.totalProducts * 100).toFixed(1) : 0) + '%</div></div>';
  html += '<div class="stat-card"><div class="label">B 潛力</div><div class="value grade-B">' + s.gradeB + '</div><div class="sub">' + (s.totalProducts ? (s.gradeB / s.totalProducts * 100).toFixed(1) : 0) + '%</div></div>';
  html += '<div class="stat-card"><div class="label">C 觀察</div><div class="value grade-C">' + s.gradeC + '</div><div class="sub">' + (s.totalProducts ? (s.gradeC / s.totalProducts * 100).toFixed(1) : 0) + '%</div></div>';
  html += '<div class="stat-card"><div class="label">D 弱勢</div><div class="value grade-D">' + s.gradeD + '</div><div class="sub">' + (s.totalProducts ? (s.gradeD / s.totalProducts * 100).toFixed(1) : 0) + '%</div></div>';
  html += '<div class="stat-card"><div class="label">平均 TTFS</div><div class="value">' + (s.avgTTFS < 0 ? '-' : s.avgTTFS + ' 月') + '</div><div class="sub">上市到首單平均月數</div></div>';
  html += '<div class="stat-card"><div class="label">平均銷售家數</div><div class="value">' + s.avgCustomerCount + '</div><div class="sub">每支新品</div></div>';
  html += '<div class="stat-card"><div class="label">平均上架家數</div><div class="value">' + s.avgDisplayCount + '</div><div class="sub">每支新品累計</div></div>';
  html += '<div class="stat-card"><div class="label">平均總銷售額</div><div class="value">' + fmtNum(s.avgTotalAmount) + '</div><div class="sub">每支新品總和</div></div>';
  document.getElementById('summary-grid').innerHTML = html;
}

function renderCohortCurve(d) {
  var canvas = document.getElementById('cohort-chart');
  if (cohortChart) { cohortChart.destroy(); }
  var labels = d.cohortCurve.map(function(c) { return c.label; });
  var median = d.cohortCurve.map(function(c) { return c.median; });
  var p25 = d.cohortCurve.map(function(c) { return c.p25; });
  var p75 = d.cohortCurve.map(function(c) { return c.p75; });
  cohortChart = new Chart(canvas, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: '中位數',
        data: median,
        borderColor: '#c29d66',
        backgroundColor: 'rgba(194,157,102,0.1)',
        pointBackgroundColor: '#c29d66',
        pointRadius: 3,
        borderWidth: 2.5,
        tension: 0.3
      }, {
        label: 'P75',
        data: p75,
        borderColor: 'rgba(194,157,102,0.25)',
        borderWidth: 1.5,
        borderDash: [4, 4],
        pointRadius: 0,
        tension: 0.3
      }, {
        label: 'P25',
        data: p25,
        borderColor: 'rgba(194,157,102,0.25)',
        borderWidth: 1.5,
        borderDash: [4, 4],
        pointRadius: 0,
        tension: 0.3,
        fill: '-1',
        backgroundColor: 'rgba(194,157,102,0.05)'
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { position: 'top', labels: { color: 'rgba(255,255,255,0.6)', font: { size: 11 }, boxWidth: 14, padding: 12 } },
        tooltip: { backgroundColor: '#111', borderColor: 'rgba(194,157,102,0.3)', borderWidth: 1, titleColor: '#c29d66', bodyColor: '#fff',
          callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + fmtNum(ctx.parsed.y); } }
        }
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 10 } } },
        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 10 }, callback: function(v) { return fmtNum(v); } } }
      }
    }
  });
}

function renderGradeGroups(gg) {
  var body = document.getElementById('grade-groups-body');
  var grades = ['A', 'B', 'C', 'D'];
  var html = '';
  grades.forEach(function(g) {
    var grp = gg[g];
    if (!grp || grp.productCount === 0) {
      html += '<div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border)">';
      html += '<div style="display:flex;align-items:center;gap:12px;margin-bottom:6px"><span class="grade-pill ' + g + '">' + g + '</span><span style="font-weight:700">' + (g === 'A' ? '明星' : g === 'B' ? '潛力' : g === 'C' ? '觀察' : '弱勢') + '</span></div>';
      html += '<span style="color:var(--text2)">0 支產品</span></div>';
      return;
    }
    html += '<div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border)">';
    html += '<div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;flex-wrap:wrap">';
    html += '<span class="grade-pill ' + g + '">' + g + '</span>';
    html += '<span style="font-weight:700">' + grp.label + '</span>';
    html += '<span style="font-size:12px;color:var(--text2)">' + grp.productCount + ' 支 · 總額 ' + fmtNum(grp.totalAmount) + '</span></div>';
    html += '<div style="font-size:12px;color:var(--text2);margin-bottom:8px">' + grp.desc + '</div>';
    html += '<div style="font-size:12px;color:var(--gold);margin-bottom:10px">💡 ' + grp.suggestion + '</div>';
    html += '<div style="display:flex;gap:6px;flex-wrap:wrap">';
    (grp.seriesBreakdown || []).forEach(function(s) {
      html += '<span class="series-badge ' + g + '" style="cursor:pointer" onclick="openSeriesDetail(\'' + s.series.replace(/'/g, "\\'") + '\')" title="點擊查看此系列詳細">' + s.series + ' (' + s.count + '支, ' + fmtNum(s.amount) + ')</span>';
    });
    html += '</div></div>';
  });
  body.innerHTML = html;
}

function renderSeriesGroups(sgs) {
  var body = document.getElementById('series-body');
  if (!sgs || sgs.length === 0) { body.innerHTML = '<div class="empty-state"><div class="msg">暫無系列資料</div></div>'; return; }
  var html = '<div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">';
  sgs.forEach(function(sg) {
    var g = sg.mainGrade || 'C';
    html += '<div style="background:var(--surface2);border-radius:var(--radius-md);padding:14px;border:1px solid var(--border);cursor:pointer" onclick="openSeriesDetail(\'' + sg.series.replace(/'/g, "\\'") + '\')">';
    html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">';
    html += '<span class="series-badge ' + g + '" style="font-size:13px;cursor:pointer">' + sg.series + '</span>';
    html += '<span class="grade-pill ' + g + '">' + g + '</span></div>';
    html += '<div style="font-size:12px;color:var(--text2)">' + sg.productCount + ' 支產品 · 總額 ' + fmtNum(sg.totalAmount) + '</div>';
    html += '<div style="font-size:12px;color:var(--text2);margin-bottom:4px">平均分數 ' + sg.avgScore + ' · 均額 ' + fmtNum(sg.avgSales) + '</div>';
    html += '<div class="grade-pills">';
    ['A','B','C','D'].forEach(function(g2) {
      if (sg.grades[g2] > 0) html += '<span class="grade-pill ' + g2 + '">' + g2 + ': ' + sg.grades[g2] + '</span>';
    });
    html += '</div></div>';
  });
  html += '</div>';
  body.innerHTML = html;
}

function populateSeriesFilter(sgs) {
  var sel = document.getElementById('sel-series-filter');
  sel.innerHTML = '<option value="">全部系列</option>';
  sgs.forEach(function(sg) {
    sel.innerHTML += '<option value="' + sg.series + '">' + sg.series + ' (' + sg.productCount + '支)</option>';
  });
}

function applySeriesFilter() {
  if (!allData) return;
  var seriesFilter = document.getElementById('sel-series-filter').value;
  var gradeFilter = document.getElementById('sel-grade-filter').value;
  var filtered = allData.products;
  if (seriesFilter) filtered = filtered.filter(function(p) { return p.series === seriesFilter; });
  if (gradeFilter) filtered = filtered.filter(function(p) { return p.grade === gradeFilter; });
  renderTable(filtered);
}

function toggleSection(el) {
  var body = el.nextElementSibling;
  var label = el.querySelector('span:last-child');
  if (body.style.display === 'none') { body.style.display = ''; label.textContent = '收合'; }
  else { body.style.display = 'none'; label.textContent = '展開'; }
}

function toggleProducts() {
  showIndividual = !showIndividual;
  document.getElementById('individual-panel').style.display = showIndividual ? 'block' : 'none';
  document.getElementById('btn-toggle-individual').textContent = showIndividual ? '隱藏個別產品線' : '顯示個別產品線';
  if (showIndividual && allData) renderIndividualProducts(allData);
}

function renderIndividualProducts(d) {
  var canvas = document.getElementById('individual-chart');
  if (individualChart) { individualChart.destroy(); }
  var top = d.products.slice(0, 20);
  var labels = d.cohortCurve.map(function(c) { return c.month; });
  var datasets = top.map(function(p, i) {
    var hue = (i * 137) % 360;
    return { label: p.sku, data: p.monthlySales, borderColor: 'hsla(' + hue + ', 60%, 55%, 0.6)', borderWidth: 1.2, pointRadius: 0, tension: 0.2 };
  });
  individualChart = new Chart(canvas, {
    type: 'line',
    data: { labels: labels, datasets: datasets },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { position: 'right', labels: { color: 'rgba(255,255,255,0.5)', font: { size: 10 }, boxWidth: 10, padding: 6 } },
        tooltip: { backgroundColor: '#111', borderColor: 'rgba(194,157,102,0.3)', borderWidth: 1, bodyColor: '#fff', callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + fmtNum(ctx.parsed.y); } } }
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 10 } } },
        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 10 }, callback: function(v) { return fmtNum(v); } } }
      }
    }
  });
}

function renderTable(products) {
  var body = document.getElementById('table-body');
  document.getElementById('product-count').textContent = products.length + ' 支';

  var sorted = products.slice();
  sorted.sort(function(a, b) {
    var va = a[sortKey], vb = b[sortKey];
    if (sortKey === 'customers') {
      va = (va || []).join(',');
      vb = (vb || []).join(',');
      return sortAsc ? va.localeCompare(vb) : vb.localeCompare(va);
    }
    if (typeof va === 'string') return sortAsc ? va.localeCompare(vb) : vb.localeCompare(va);
    if (va == null) return 1;
    if (vb == null) return -1;
    return sortAsc ? (va - vb) : (vb - va);
  });

  body.innerHTML = '';
  sorted.forEach(function(p) {
    var custs = (p.customers || []).slice(0, 5).map(function(c) { return '<span class="rep-tag">' + c + '</span>'; }).join('');
    if ((p.customers || []).length > 5) custs += ' <span style="font-size:10px;color:var(--text2)">+ ' + (p.customers.length - 5) + '</span>';
    var img = p.imageUrl ? '<img src="' + p.imageUrl + '" style="width:24px;height:24px;border-radius:4px;object-fit:cover;vertical-align:middle;margin-right:6px">' : '';
    body.innerHTML += '<tr>'
      + '<td><span class="grade-pill ' + p.grade + '">' + p.grade + '</span></td>'
      + '<td><a href="javascript:openDetail(\'' + p.sku + '\')" style="color:var(--gold);text-decoration:none;font-weight:600">' + img + '<span class="mono">' + p.sku + '</span></a></td>'
      + '<td>' + (p.series || '-') + '</td>'
      + '<td class="mono">' + fmtDate(p.firstInDate) + '</td>'
      + '<td class="mono ' + ttfsClass(p.ttfs) + '" title="' + (p.ttfsDesc || '') + '">' + ttfsLabel(p.ttfs) + '</td>'
      + '<td>' + custs + '</td>'
      + '<td class="mono">' + fmtNum(p.totalAmount) + '</td>'
      + '<td class="mono">' + p.totalCount + '</td>'
      + '<td class="mono">' + p.customerCount + '</td>'
      + '<td class="mono">' + p.displayCount + '</td>'
      + '<td class="mono" style="font-weight:700">' + p.score + '</td>'
      + '</tr>';
  });
}

document.getElementById('product-table').addEventListener('click', function(e) {
  var th = e.target.closest('th');
  if (!th) return;
  var key = th.dataset.key;
  if (!key) return;
  if (sortKey === key) sortAsc = !sortAsc;
  else { sortKey = key; sortAsc = false; }
  document.querySelectorAll('#product-table th').forEach(function(t) { t.classList.remove('sorted'); });
  th.classList.add('sorted');
  applySeriesFilter();
});

function openDetail(sku) {
  if (!allData) return;
  var p = allData.products.find(function(x) { return x.sku === sku; });
  if (!p) return;
  var content = document.getElementById('detail-content');
  var custHtml = (p.customers || []).map(function(c) { return '<span class="rep-tag">' + c + '</span>'; }).join('');
  var areaTable = (p.areaSales || []).map(function(a) {
    return '<tr><td>' + a.area + '</td><td class="mono" style="text-align:right">' + fmtNum(a.amount) + '</td><td style="text-align:right"><div style="background:var(--surface2);border-radius:4px;overflow:hidden"><div style="background:var(--gold);height:6px;width:' + Math.min(100, a.amount / Math.max.apply(null, p.areaSales.map(function(x){return x.amount})) * 100) + '%"></div></div></td></tr>';
  }).join('');

  content.innerHTML = '<div class="detail-title">'
    + '<span class="grade-pill ' + p.grade + '">' + p.grade + '</span>'
    + '<span class="mono">' + p.sku + '</span>'
    + '<span style="font-size:13px;font-weight:400;color:var(--text2)">' + (p.series || '') + '</span>'
    + '</div>'

    + '<div class="detail-grid">'
    + '<div class="detail-field"><div class="dl">分級</div><div class="dd ' + gradeClass(p.grade) + '">' + p.grade + ' - ' + (p.gradeLabel || '') + '</div></div>'
    + '<div class="detail-field"><div class="dl">評分</div><div class="dd">' + p.score + ' / 12</div></div>'
    + '<div class="detail-field"><div class="dl">TTFS（上市到首單）</div><div class="dd ' + ttfsClass(p.ttfs) + '">' + ttfsLabel(p.ttfs) + '</div></div>'
    + '<div class="detail-field"><div class="dl">TTFS 說明</div><div class="dd" style="font-size:13px;font-weight:400;color:var(--text2)">' + (p.ttfsDesc || '') + '</div></div>'
    + '<div class="detail-field"><div class="dl">總銷售額</div><div class="dd">' + fmtNum(p.totalAmount) + '</div></div>'
    + '<div class="detail-field"><div class="dl">交易次數</div><div class="dd">' + p.totalCount + '</div></div>'
    + '<div class="detail-field"><div class="dl">銷售家數</div><div class="dd">' + p.customerCount + '</div></div>'
    + '<div class="detail-field"><div class="dl">上架家數</div><div class="dd">' + p.displayCount + '</div></div>'
    + '<div class="detail-field"><div class="dl">銷售客戶 (' + (p.customers || []).length + ' 家)</div><div class="dd" style="font-size:12px">' + custHtml + '</div></div>'
    + '<div class="detail-field"><div class="dl">首次進貨日</div><div class="dd mono">' + fmtDate(p.firstInDate) + '</div></div>'
    + '</div>'

    + '<div class="detail-section-title" style="display:flex;justify-content:space-between;align-items:center">'
    + '<span>💡 AI 銷售分析建議</span>'
    + '<button onclick="loadAiAnalysis(\'' + p.sku + '\')" id="ai-analysis-btn" style="font-size:11px;padding:6px 14px;background:linear-gradient(135deg,#c29d66,#d4b483);color:#000;border:none;border-radius:6px;font-weight:700;cursor:pointer">產生 AI 分析</button>'
    + '</div>'
    + '<div id="ai-analysis-result" style="font-size:13px;line-height:1.7;padding:4px 0 8px;color:var(--text2)"></div>'

    + '<div class="detail-section-title">區域銷售分佈</div>'
    + '<div class="pie-wrap"><canvas id="detail-pie"></canvas></div>'
    + '<table style="margin-top:10px;font-size:12px">'
    + '<thead><tr><th>區域</th><th style="text-align:right">銷售額</th><th style="width:100px"></th></tr></thead>'
    + '<tbody>' + (areaTable || '<tr><td colspan="3" style="color:var(--text2)">無區域資料</td></tr>') + '</tbody></table>';

  document.getElementById('detail-overlay').classList.add('open');

  setTimeout(function() {
    var canvas = document.getElementById('detail-pie');
    if (!canvas) return;
    if (pieChart) pieChart.destroy();
    var areas = p.areaSales || [];
    if (areas.length === 0) return;
    var colors = ['#c29d66','#22c55e','#60a5fa','#f59e0b','#ef4444','#a855f7','#ec4899','#14b8a6'];
    pieChart = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: areas.map(function(a) { return a.area; }),
        datasets: [{ data: areas.map(function(a) { return a.amount; }), backgroundColor: colors.slice(0, areas.length), borderWidth: 0 }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'right', labels: { color: 'rgba(255,255,255,0.6)', font: { size: 10 }, boxWidth: 12, padding: 8 } },
          tooltip: { backgroundColor: '#111', borderColor: 'rgba(194,157,102,0.3)', borderWidth: 1, bodyColor: '#fff', callbacks: {
            label: function(ctx) { return ctx.label + ': ' + fmtNum(ctx.parsed); }
          }}
        }
      }
    });
  }, 100);
}

function loadAiAnalysis(sku) {
  if (!allData) return;
  var p = allData.products.find(function(x) { return x.sku === sku; });
  if (!p) return;
  var btn = document.getElementById('ai-analysis-btn');
  var result = document.getElementById('ai-analysis-result');
  btn.disabled = true;
  btn.textContent = 'AI 分析中...';
  result.innerHTML = '<span style="color:var(--text2)">分析中，請稍候⋯</span>';

  fetch(API_BASE + '?action=new-product-ai-chat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sku: sku, product: p })
  })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    btn.disabled = false;
    btn.textContent = '重新分析';
    if (!res.success) {
      result.innerHTML = '<span style="color:#ef4444">❌ ' + (res.msg || '分析失敗') + '</span>';
      return;
    }
    var reply = (res.reply || '').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
    result.innerHTML = '<div style="background:var(--surface2);border-radius:var(--radius-md);padding:14px;border:1px solid var(--border);color:var(--text)">' + reply + '</div>';
  })
  .catch(function(err) {
    btn.disabled = false;
    btn.textContent = '重新分析';
    result.innerHTML = '<span style="color:#ef4444">❌ 連線錯誤: ' + err + '</span>';
  });
}

function openSeriesDetail(seriesName) {
  if (!allData) return;
  var products = allData.products.filter(function(p) { return p.series === seriesName; });
  if (products.length === 0) return;
  var content = document.getElementById('detail-content');
  var seriesInfo = (allData.seriesGroups || []).find(function(s) { return s.series === seriesName; });

  var totalAmount = 0, totalScore = 0;
  products.forEach(function(p) { totalAmount += p.totalAmount; totalScore += p.score; });
  var avgScore = products.length > 0 ? (totalScore / products.length).toFixed(1) : 0;

  var gradeCounts = {A:0, B:0, C:0, D:0};
  products.forEach(function(p) { gradeCounts[p.grade]++; });
  var gradeHtml = '';
  ['A','B','C','D'].forEach(function(g) {
    if (gradeCounts[g] > 0) gradeHtml += '<span class="grade-pill ' + g + '">' + g + ': ' + gradeCounts[g] + '</span> ';
  });

  var barLabels = [];
  var barData = [];
  var barColors = [];
  var tableRows = '';
  var sorted = products.slice().sort(function(a, b) { return b.totalAmount - a.totalAmount; });
  sorted.forEach(function(p, i) {
    barLabels.push(p.sku);
    barData.push(p.totalAmount);
    var hue = (i * 37) % 360;
    barColors.push('hsla(' + hue + ', 60%, 55%, 0.7)');
    var img = p.imageUrl ? '<img src="' + p.imageUrl + '" style="width:28px;height:28px;border-radius:4px;object-fit:cover;vertical-align:middle;margin-right:6px">' : '';
    tableRows += '<tr>'
      + '<td style="color:var(--text2);width:30px;font-weight:700">' + (i + 1) + '</td>'
      + '<td><a href="javascript:openDetail(\'' + p.sku + '\')" style="color:var(--gold);text-decoration:none;font-weight:600">' + img + '<span class="mono">' + p.sku + '</span></a></td>'
      + '<td><span class="grade-pill ' + p.grade + '">' + p.grade + '</span></td>'
      + '<td class="mono">' + fmtNum(p.totalAmount) + '</td>'
      + '<td class="mono">' + p.totalCount + '</td>'
      + '<td class="mono">' + p.customerCount + '</td>'
      + '<td class="mono ' + ttfsClass(p.ttfs) + '">' + ttfsLabel(p.ttfs) + '</td>'
      + '<td class="mono" style="font-weight:700">' + p.score + '</td></tr>';
  });

  content.innerHTML = '<div class="detail-title">'
    + '<span style="font-size:20px">📦</span>'
    + '<span>' + seriesName + '</span>'
    + '<span style="font-size:12px;font-weight:400;color:var(--text2)">' + products.length + ' 支產品</span>'
    + '<button onclick="closeDetail()" style="margin-left:auto;background:var(--surface2);border:1px solid var(--border);color:var(--text2);padding:4px 12px;border-radius:6px;cursor:pointer;font-size:12px">✕ 關閉</button>'
    + '</div>'

    + '<div class="detail-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">'
    + '<div class="detail-field"><div class="dl">總銷售額</div><div class="dd">' + fmtNum(totalAmount) + '</div></div>'
    + '<div class="detail-field"><div class="dl">平均分數</div><div class="dd">' + avgScore + ' / 12</div></div>'
    + '<div class="detail-field"><div class="dl">平均銷售家數</div><div class="dd">' + (products.length > 0 ? (products.reduce(function(s, p) { return s + p.customerCount; }, 0) / products.length).toFixed(1) : 0) + '</div></div>'
    + '<div class="detail-field"><div class="dl">平均上架家數</div><div class="dd">' + (products.length > 0 ? (products.reduce(function(s, p) { return s + p.displayCount; }, 0) / products.length).toFixed(1) : 0) + '</div></div>'
    + '</div>'
    + '<div class="grade-pills" style="margin-bottom:16px">' + gradeHtml + '</div>'

    + '<div class="detail-section-title">銷售排行長條圖</div>'
    + '<div class="pie-wrap" style="height:220px"><canvas id="series-bar-chart"></canvas></div>'

    + '<div class="detail-section-title" style="margin-top:16px">產品排行</div>'
    + '<div class="table-scroll"><table style="font-size:12px">'
    + '<thead><tr><th>#</th><th>SKU</th><th>級別</th><th>總銷售額</th><th>交易次數</th><th>銷售家數</th><th>TTFS</th><th>分數</th></tr></thead>'
    + '<tbody>' + tableRows + '</tbody></table></div>';

  document.getElementById('detail-overlay').classList.add('open');

  setTimeout(function() {
    var canvas = document.getElementById('series-bar-chart');
    if (!canvas) return;
    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: barLabels,
        datasets: [{
          label: '總銷售額',
          data: barData,
          backgroundColor: barColors,
          borderRadius: 4,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: '#111', borderColor: 'rgba(194,157,102,0.3)', borderWidth: 1, bodyColor: '#fff',
            callbacks: { label: function(ctx) { return fmtNum(ctx.parsed.x); } }
          }
        },
        scales: {
          x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 9 }, callback: function(v) { return fmtNum(v); } } },
          y: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 9 } } }
        }
      }
    });
  }, 100);
}

function closeDetail() {
  document.getElementById('detail-overlay').classList.remove('open');
  if (pieChart) { pieChart.destroy(); pieChart = null; }
  var seriesChart = Chart.getChart('series-bar-chart');
  if (seriesChart) seriesChart.destroy();
}

window.addEventListener('DOMContentLoaded', function() { loadData(); });
</script>
</body>
</html>
