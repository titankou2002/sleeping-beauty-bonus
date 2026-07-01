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
  --gold-glow: rgba(194,157,102,0.2);
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
  max-width: 1400px; margin: 0 auto;
  padding: 0 20px;
  height: 56px;
  display: flex; align-items: center; gap: 16px;
}
.logo {
  font-size: 16px; font-weight: 900; letter-spacing: 2px;
  color: var(--gold);
  text-shadow: 0 0 20px rgba(194,157,102,0.3);
  white-space: nowrap;
}
.main {
  max-width: 1400px; margin: 0 auto; padding: 20px;
  width: 100%;
}
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
select:hover, button:hover {
  border-color: var(--gold); background: var(--gold-soft);
}
.btn-primary {
  background: linear-gradient(135deg, #c29d66, #d4b483);
  color: #000; border: none; font-weight: 800;
}
.btn-primary:hover { background: linear-gradient(135deg, #d4b483, #e6cba0); }
.grid-4 {
  display: grid; gap: 12px;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  margin-bottom: 20px;
}
.stat-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 16px 18px;
}
.stat-card .label { font-size: 11px; color: var(--text2); font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px; }
.stat-card .value { font-size: 28px; font-weight: 800; letter-spacing: -0.5px; }
.stat-card .sub { font-size: 12px; color: var(--text2); margin-top: 2px; }
.chart-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 20px;
  margin-bottom: 20px;
  position: relative;
}
.chart-card .title {
  font-size: 15px; font-weight: 700; margin-bottom: 14px;
  display: flex; align-items: center; gap: 12px;
}
.chart-wrap { position: relative; height: 380px; }
.product-table-wrap {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); overflow: hidden;
}
.product-table-wrap .header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 20px; border-bottom: 1px solid var(--border);
}
.product-table-wrap .header .title { font-size: 15px; font-weight: 700; }
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
td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.04); }
tr:hover td { background: var(--surface2); }
.grade-A { color: #22c55e; }
.grade-B { color: #f59e0b; }
.grade-C { color: #ef4444; }
.grade-badge {
  display: inline-block; padding: 2px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 800;
}
.grade-badge.A { background: rgba(34,197,94,0.15); color: #22c55e; }
.grade-badge.B { background: rgba(245,158,11,0.15); color: #f59e0b; }
.grade-badge.C { background: rgba(239,68,68,0.15); color: #ef4444; }
.mono { font-feature-settings: "tnum" 1; }
.ttfs-good { color: #22c55e; }
.ttfs-ok { color: #f59e0b; }
.ttfs-bad { color: #ef4444; }
.ttfs-na { color: var(--text2); }
.loading {
  position: fixed; inset: 0; z-index: 999;
  display: flex; align-items: center; justify-content: center;
  background: rgba(10,10,10,0.7);
  backdrop-filter: blur(4px);
}
.loading.hidden { display: none; }
.spinner {
  width: 40px; height: 40px; border-radius: 50%;
  border: 3px solid var(--border); border-top-color: var(--gold);
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.tooltip {
  position: relative;
}
.tooltip:hover::after {
  content: attr(data-tip);
  position: absolute; bottom: calc(100% + 6px); left: 50%;
  transform: translateX(-50%);
  background: #111; border: 1px solid var(--border);
  padding: 6px 10px; border-radius: var(--radius-sm);
  font-size: 11px; white-space: nowrap; z-index: 10;
  color: var(--text2);
}
.empty-state {
  text-align: center; padding: 60px 20px; color: var(--text2);
}
.empty-state .icon { font-size: 40px; margin-bottom: 12px; }
.empty-state .msg { font-size: 15px; font-weight: 600; }
@media (max-width: 600px) {
  .grid-4 { grid-template-columns: repeat(2, 1fr); }
  .stat-card .value { font-size: 22px; }
  .chart-wrap { height: 260px; }
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

  <div class="main">
    <div class="ctrl-bar">
      <span class="ctrl-label">分析期間：</span>
      <select id="sel-months">
        <option value="12">12 個月</option>
        <option value="24" selected>24 個月</option>
        <option value="36">36 個月</option>
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
      <div class="chart-wrap">
        <canvas id="cohort-chart"></canvas>
      </div>
    </div>

    <div class="chart-card" id="individual-panel" style="display:none">
      <div class="title">
        <span>個別產品月銷售</span>
        <span style="font-size:11px;color:var(--text2);font-weight:400">前 20 支（按總額排序）</span>
      </div>
      <div class="chart-wrap">
        <canvas id="individual-chart"></canvas>
      </div>
    </div>

    <div class="product-table-wrap" id="table-wrap">
      <div class="header">
        <div class="title">新品打分卡 <span id="product-count" style="font-size:12px;color:var(--text2);font-weight:400"></span></div>
        <span style="font-size:11px;color:var(--text2)">點擊欄位排序</span>
      </div>
      <div class="table-scroll">
        <table id="product-table">
          <thead>
            <tr>
              <th data-key="grade">級別</th>
              <th data-key="sku">SKU</th>
              <th data-key="series">系列</th>
              <th data-key="firstInDate">首次進貨</th>
              <th data-key="ttfs">TTFS</th>
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

<script>
var API_BASE = 'api.php';
var chartData = null;
var cohortChart = null;
var individualChart = null;
var showIndividual = false;

function showLoading(v) {
  document.getElementById('loading').classList.toggle('hidden', !v);
}
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
function fmtDate(d) {
  if (!d) return '-';
  var p = d.split('/');
  return p[0] + '/' + p[1] + '/' + p[2];
}
function ttfsClass(v) {
  if (v < 0) return 'ttfs-na';
  if (v <= 1) return 'ttfs-good';
  if (v <= 3) return 'ttfs-ok';
  return 'ttfs-bad';
}
function ttfsLabel(v) {
  if (v < 0) return '無交易';
  if (v === 0) return '當月即售';
  return v + ' 月';
}

function loadData() {
  var months = document.getElementById('sel-months').value;
  showLoading(true);
  fetch(API_BASE + '?action=new-product-analysis&months=' + months)
    .then(function(r) { return r.json(); })
    .then(function(res) {
      showLoading(false);
      if (!res.success) { toast(res.msg || '載入失敗', true); return; }
      chartData = res.data;
      renderSummary(res.data.summary);
      renderCohortCurve(res.data);
      renderTable(res.data.products);
      document.getElementById('btn-toggle-individual').style.display = 'inline-block';
    })
    .catch(function(err) {
      showLoading(false);
      toast('連線失敗: ' + err, true);
    });
}

function renderSummary(s) {
  var html = '';
  html += '<div class="stat-card"><div class="label">新品總數</div><div class="value">' + s.totalProducts + '</div><div class="sub">分析期間內首次進貨</div></div>';
  html += '<div class="stat-card"><div class="label">A 級 (明星)</div><div class="value grade-A">' + s.gradeA + '</div><div class="sub">佔 ' + (s.totalProducts ? (s.gradeA / s.totalProducts * 100).toFixed(1) : 0) + '%</div></div>';
  html += '<div class="stat-card"><div class="label">B 級 (一般)</div><div class="value grade-B">' + s.gradeB + '</div><div class="sub">佔 ' + (s.totalProducts ? (s.gradeB / s.totalProducts * 100).toFixed(1) : 0) + '%</div></div>';
  html += '<div class="stat-card"><div class="label">C 級 (陣亡)</div><div class="value grade-C">' + s.gradeC + '</div><div class="sub">佔 ' + (s.totalProducts ? (s.gradeC / s.totalProducts * 100).toFixed(1) : 0) + '%</div></div>';
  html += '<div class="stat-card"><div class="label">平均 TTFS</div><div class="value">' + (s.avgTTFS < 0 ? '-' : s.avgTTFS + ' 月') + '</div><div class="sub">首次交易到上市平均月數</div></div>';
  html += '<div class="stat-card"><div class="label">平均銷售家數</div><div class="value">' + s.avgCustomerCount + '</div><div class="sub">每支新品平均客戶數</div></div>';
  html += '<div class="stat-card"><div class="label">平均上架家數</div><div class="value">' + s.avgDisplayCount + '</div><div class="sub">每支新品累計上架</div></div>';
  html += '<div class="stat-card"><div class="label">平均總銷售額</div><div class="value">' + fmtNum(s.avgTotalAmount) + '</div><div class="sub">每支新品總銷售</div></div>';
  document.getElementById('summary-grid').innerHTML = html;
}

function renderCohortCurve(d) {
  var canvas = document.getElementById('cohort-chart');
  var ctx = canvas.getContext('2d');
  if (cohortChart) { cohortChart.destroy(); }

  var labels = d.cohortCurve.map(function(c) { return c.label; });
  var median = d.cohortCurve.map(function(c) { return c.median; });
  var p25 = d.cohortCurve.map(function(c) { return c.p25; });
  var p75 = d.cohortCurve.map(function(c) { return c.p75; });

  cohortChart = new Chart(ctx, {
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
        tension: 0.3,
        fill: false
      }, {
        label: 'P75',
        data: p75,
        borderColor: 'rgba(194,157,102,0.25)',
        borderWidth: 1.5,
        borderDash: [4, 4],
        pointRadius: 0,
        tension: 0.3,
        fill: false
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
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: {
          position: 'top',
          labels: { color: 'rgba(255,255,255,0.6)', font: { size: 11 }, boxWidth: 14, padding: 12 }
        },
        tooltip: {
          backgroundColor: '#111',
          borderColor: 'rgba(194,157,102,0.3)',
          borderWidth: 1,
          titleColor: '#c29d66',
          bodyColor: '#fff',
          callbacks: {
            label: function(ctx) {
              return ctx.dataset.label + ': ' + fmtNum(ctx.parsed.y);
            }
          }
        }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.04)', drawOnChartArea: true },
          ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 10 } }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: {
            color: 'rgba(255,255,255,0.35)',
            font: { size: 10 },
            callback: function(v) { return fmtNum(v); }
          }
        }
      }
    }
  });
}

function toggleProducts() {
  showIndividual = !showIndividual;
  document.getElementById('individual-panel').style.display = showIndividual ? 'block' : 'none';
  document.getElementById('btn-toggle-individual').textContent = showIndividual ? '隱藏個別產品線' : '顯示個別產品線';
  if (showIndividual && chartData) renderIndividualProducts(chartData);
}

function renderIndividualProducts(d) {
  var canvas = document.getElementById('individual-chart');
  var ctx = canvas.getContext('2d');
  if (individualChart) { individualChart.destroy(); }

  var top = d.products.slice(0, 20);
  var labels = d.cohortCurve.map(function(c) { return c.month; });
  var datasets = top.map(function(p, i) {
    var hue = (i * 137) % 360;
    return {
      label: p.sku,
      data: p.monthlySales,
      borderColor: 'hsla(' + hue + ', 60%, 55%, 0.6)',
      borderWidth: 1.2,
      pointRadius: 0,
      tension: 0.2,
      fill: false
    };
  });

  individualChart = new Chart(ctx, {
    type: 'line',
    data: { labels: labels, datasets: datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: {
          position: 'right',
          labels: { color: 'rgba(255,255,255,0.5)', font: { size: 10 }, boxWidth: 10, padding: 6 }
        },
        tooltip: {
          backgroundColor: '#111',
          borderColor: 'rgba(194,157,102,0.3)',
          borderWidth: 1,
          bodyColor: '#fff',
          callbacks: {
            label: function(ctx) { return ctx.dataset.label + ': ' + fmtNum(ctx.parsed.y); }
          }
        }
      },
      scales: {
        x: {
          title: { display: true, text: '上市後第 N 月', color: 'rgba(255,255,255,0.35)', font: { size: 10 } },
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 10 } }
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(255,255,255,0.04)' },
          ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 10 }, callback: function(v) { return fmtNum(v); } }
        }
      }
    }
  });
}

var sortKey = 'score';
var sortAsc = false;

function renderTable(products) {
  var body = document.getElementById('table-body');
  document.getElementById('product-count').textContent = products.length + ' 支';

  function renderRows(sorted) {
    body.innerHTML = '';
    sorted.forEach(function(p) {
      var img = p.imageUrl ? '<img src="' + p.imageUrl + '" style="width:28px;height:28px;border-radius:4px;object-fit:cover;vertical-align:middle;margin-right:6px">' : '';
      body.innerHTML += '<tr>'
        + '<td><span class="grade-badge ' + p.grade + '">' + p.grade + '</span></td>'
        + '<td>' + img + '<span class="mono">' + p.sku + '</span></td>'
        + '<td>' + (p.series || '-') + '</td>'
        + '<td class="mono">' + fmtDate(p.firstInDate) + '</td>'
        + '<td class="mono ' + ttfsClass(p.ttfs) + '">' + ttfsLabel(p.ttfs) + '</td>'
        + '<td class="mono">' + fmtNum(p.totalAmount) + '</td>'
        + '<td class="mono">' + p.totalCount + '</td>'
        + '<td class="mono">' + p.customerCount + '</td>'
        + '<td class="mono">' + p.displayCount + '</td>'
        + '<td class="mono" style="font-weight:700">' + p.score + '</td>'
        + '</tr>';
    });
  }

  var sorted = sortProducts(products, sortKey, sortAsc);
  renderRows(sorted);
}

function sortProducts(products, key, asc) {
  var sorted = products.slice();
  sorted.sort(function(a, b) {
    var va = a[key];
    var vb = b[key];
    if (typeof va === 'string' && typeof vb === 'string') {
      return asc ? va.localeCompare(vb) : vb.localeCompare(va);
    }
    if (va == null) return 1;
    if (vb == null) return -1;
    return asc ? (va - vb) : (vb - va);
  });
  return sorted;
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
  if (chartData) renderTable(chartData.products);
});

window.addEventListener('DOMContentLoaded', function() {
  loadData();
});
</script>
</body>
</html>
