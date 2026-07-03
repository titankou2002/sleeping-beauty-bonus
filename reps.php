<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>業務績效分析</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23c29d66'/%3E%3Ctext x='32' y='46' font-family='Arial, sans-serif' font-size='36' font-weight='900' fill='%23000000' text-anchor='middle'%3ER%3C/text%3E%3C/svg%3E">
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
  --green: #22c55e;
  --red: #ef4444;
  --orange: #f59e0b;
  --blue: #60a5fa;
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
.logo { font-size: 16px; font-weight: 900; letter-spacing: 2px; color: var(--gold); text-shadow: 0 0 20px rgba(194,157,102,0.3); white-space: nowrap; }
.main { max-width: 1400px; margin: 0 auto; padding: 20px; width: 100%; }
.ctrl-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
select, button {
  font-family: inherit; font-size: 13px; font-weight: 600;
  padding: 8px 16px; border-radius: var(--radius-md);
  background: var(--surface2); color: var(--text);
  border: 1px solid var(--border); cursor: pointer;
  transition: all 0.2s;
}
select:hover, button:hover { border-color: var(--gold); background: var(--gold-soft); }
.btn-primary { background: linear-gradient(135deg, #c29d66, #d4b483); color: #000; border: none; font-weight: 800; }
.btn-primary:hover { background: linear-gradient(135deg, #d4b483, #e6cba0); }

.section-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); margin-bottom: 20px; overflow: hidden;
}
.section-card .section-title {
  padding: 14px 20px; font-size: 15px; font-weight: 700;
  border-bottom: 1px solid var(--border);
  display: flex; justify-content: space-between; align-items: center;
}
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
td { padding: 9px 12px; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
tr:hover td { background: var(--surface2); }
tr.selected td { background: var(--gold-soft); }
.mono { font-feature-settings: "tnum" 1; }

.health-badge {
  display: inline-block; padding: 2px 8px; border-radius: 4px;
  font-size: 10px; font-weight: 700;
}
.health-badge.growth { background: rgba(34,197,94,0.15); color: #22c55e; }
.health-badge.decline { background: rgba(239,68,68,0.15); color: #ef4444; }
.health-badge.warning { background: rgba(245,158,11,0.15); color: #f59e0b; }
.health-badge.dormant { background: rgba(100,100,100,0.15); color: #aaa; }
.health-badge.normal { background: rgba(96,165,250,0.15); color: #60a5fa; }
.health-badge.no_sales { background: rgba(100,100,100,0.15); color: #666; }
.health-badge.\u6B63\u5E38 { background: rgba(34,197,94,0.15); color: #22c55e; }
.health-badge.\u89C0\u5BDF { background: rgba(245,158,11,0.15); color: #f59e0b; }
.health-badge.\u8B66\u793A { background: rgba(239,68,68,0.2); color: #ef4444; }
.health-badge.\u639B\u9EDE { background: rgba(0,0,0,0.3); color: #888; }
.health-badge.\u5F85\u7E8C\u7D04 { background: rgba(96,165,250,0.15); color: #60a5fa; }

.cust-grid {
  display: grid; gap: 14px;
  grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
}
.cust-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--radius-lg); overflow: hidden;
}
.cust-card .cc-header {
  padding: 14px 16px;
  display: flex; justify-content: space-between; align-items: center;
  border-bottom: 1px solid var(--border);
}
.cust-card .cc-header .cc-name { font-size: 15px; font-weight: 700; }
.cust-card .cc-body { padding: 12px 16px; }
.cust-card .cc-stats { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 10px; }
.cust-card .cc-stat .cs-label { font-size: 10px; color: var(--text2); font-weight: 600; }
.cust-card .cc-stat .cs-value { font-size: 16px; font-weight: 800; }
.chart-sm { height: 140px; position: relative; }
.chart-md { height: 200px; position: relative; }
.chart-micro { height: 80px; position: relative; }
.gold { color: var(--gold); }
.green { color: var(--green); }
.red { color: var(--red); }
.orange { color: var(--orange); }
.text-muted { color: var(--text2); }

.loading { position: fixed; inset: 0; z-index: 999; display: flex; align-items: center; justify-content: center; background: rgba(10,10,10,0.7); backdrop-filter: blur(4px); }
.loading.hidden { display: none; }
.spinner { width: 40px; height: 40px; border-radius: 50%; border: 3px solid var(--border); border-top-color: var(--gold); animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state { text-align: center; padding: 60px 20px; color: var(--text2); }
.empty-state .icon { font-size: 40px; margin-bottom: 12px; }
.empty-state .msg { font-size: 15px; font-weight: 600; }

.rep-detail-wrap { display: none; }
.rep-detail-wrap.open { display: block; }
.back-btn { cursor: pointer; color: var(--gold); font-size: 13px; font-weight: 600; margin-bottom: 14px; display: inline-block; }
.back-btn:hover { text-decoration: underline; }

.ai-btn { font-size: 11px; padding: 4px 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: 6px; color: var(--text2); cursor: pointer; }
.ai-btn:hover { border-color: var(--gold); color: var(--gold); }

@media (max-width: 600px) {
  .cust-grid { grid-template-columns: 1fr; }
}
  </style>
</head>
<body>
<div class="app">
  <div class="topbar">
    <div class="topbar-inner">
      <div class="logo">業務績效分析</div>
    </div>
  </div>

  <div id="loading" class="loading">
    <div class="spinner"></div>
  </div>

  <div class="main">
    <div class="ctrl-bar">
      <span class="ctrl-label">年度：</span>
      <span id="year-label" style="font-size:14px;font-weight:700"></span>
      <button class="btn-primary" onclick="loadData()">載入資料</button>
    </div>

    <div class="section-card" id="rep-table-card">
      <div class="section-title">
        <span>業務績效排名</span>
        <span style="font-size:11px;color:var(--text2)">點選業務查看客戶詳細</span>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;padding:4px 0;border-bottom:1px solid var(--border)">
        <span style="font-size:11px;color:var(--text2);font-weight:600;margin-right:4px">排序:</span>
        <button class="sort-btn" onclick="sortRepTable('totalThisYear')" style="font-size:11px;padding:2px 8px;background:transparent;border:1px solid var(--border);border-radius:4px;color:var(--text2);cursor:pointer">今年業績</button>
        <button class="sort-btn" onclick="sortRepTable('avgYoy')" style="font-size:11px;padding:2px 8px;background:transparent;border:1px solid var(--border);border-radius:4px;color:var(--text2);cursor:pointer">平均 YOY</button>
        <button class="sort-btn" onclick="sortRepTable('customerCount')" style="font-size:11px;padding:2px 8px;background:transparent;border:1px solid var(--border);border-radius:4px;color:var(--text2);cursor:pointer">客戶數</button>
        <button class="sort-btn" onclick="sortRepTable('totalAmount')" style="font-size:11px;padding:2px 8px;background:transparent;border:1px solid var(--border);border-radius:4px;color:var(--text2);cursor:pointer">總業績</button>
      </div>
      <div class="table-scroll">
        <table id="rep-table">
          <thead>
            <tr>
              <th>#</th>
              <th>業務</th>
              <th>區域</th>
              <th style="text-align:right">客戶數</th>
              <th style="text-align:right">總業績</th>
              <th style="text-align:right">今年業績</th>
              <th style="text-align:right">去年業績</th>
              <th style="text-align:right">平均 YOY</th>
              <th style="text-align:right">達成率</th>
              <th style="text-align:left">等級分布</th>
            </tr>
          </thead>
          <tbody id="rep-table-body"></tbody>
        </table>
      </div>
    </div>

    <div class="rep-detail-wrap" id="rep-detail">
      <a class="back-btn" onclick="closeRepDetail()">← 回到業務排名</a>
      <div id="rep-detail-content"></div>
    </div>
  </div>
</div>

<script>
var API_BASE = 'api.php';
var allData = null;
var charts = [];
var _sortedReps = [];

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
function fmtPct(n) {
  if (n === null || n === undefined || isNaN(n)) return '-';
  return (n > 0 ? '+' : '') + n + '%';
}

function loadData() {
  showLoading(true);
  fetch(API_BASE + '?action=rep-analysis')
    .then(function(r) { return r.json(); })
    .then(function(res) {
      showLoading(false);
      if (!res.success) { toast(res.msg || '載入失敗', true); return; }
      allData = res.data;
      allData.reps.forEach(function(r) { r._sortField = ''; r._sortAsc = false; });
      document.getElementById('year-label').textContent = new Date().getFullYear();
      renderRepTable(res.data.reps);
    })
    .catch(function(err) { showLoading(false); toast('連線錯誤: ' + err, true); });
}

function renderRepTable(reps) {
  _sortedReps = reps;
  var body = document.getElementById('rep-table-body');
  body.innerHTML = '';
  reps.forEach(function(r, i) {
    var yoyStr = fmtPct(r.avgYoy);
    var yoyClass = r.avgYoy > 0 ? 'green' : (r.avgYoy < 0 ? 'red' : '');
    body.innerHTML += '<tr style="cursor:pointer" onclick="openRepDetail(\'' + r.name.replace(/'/g,"\\'") + '\')">'
      + '<td style="color:var(--text2);font-weight:700">' + (i + 1) + '</td>'
      + '<td style="font-weight:700">' + r.name + '</td>'
      + '<td style="color:var(--text2)">' + (r.area || '未分配') + '</td>'
      + '<td style="text-align:right">' + r.customerCount + '</td>'
      + '<td class="mono" style="text-align:right;font-weight:700">' + fmtNum(r.totalAmount) + '</td>'
      + '<td class="mono" style="text-align:right">' + fmtNum(r.totalThisYear) + '</td>'
      + '<td class="mono" style="text-align:right">' + fmtNum(r.totalLastYear) + '</td>'
      + '<td class="mono ' + yoyClass + '" style="text-align:right;font-weight:700">' + yoyStr + '</td>'
      + (function() {
          if (r.overallAchieve == null) return '<td style="text-align:right;color:var(--text2)">—</td>';
          var ac = r.overallAchieve >= 100 ? 'var(--green)' : r.overallAchieve >= 70 ? 'var(--gold)' : 'var(--red)';
          return '<td class="mono" style="text-align:right;font-weight:700;color:' + ac + '">' + r.overallAchieve + '%</td>';
        })()
      + (function() {
          if (!r.gradeDist) return '<td></td>';
          var gc = {'特':'#ff2a85','A':'var(--green)','B':'var(--blue)','C':'var(--text2)'};
          var s = '';
          ['特','A','B','C'].forEach(function(g) {
            if (r.gradeDist[g]) s += '<span style="background:' + (gc[g]) + '20;color:' + gc[g] + ';border-radius:3px;padding:0 5px;font-size:11px;font-weight:700;margin-right:3px">' + g + '\xD7' + r.gradeDist[g] + '</span>';
          });
          return '<td>' + s + '</td>';
        })()
      + '</tr>';
  });
}

function openRepDetail(name) {
  destroyCharts();
  var rep = null;
  for (var i = 0; i < allData.reps.length; i++) {
    if (allData.reps[i].name === name) { rep = allData.reps[i]; break; }
  }
  if (!rep) { toast('\u627E\u4E0D\u5230\u8A72\u696D\u52D9', true); return; }
  var index = allData.reps.indexOf(rep);
  document.getElementById('rep-table-card').style.display = 'none';
  document.getElementById('rep-detail').classList.add('open');

  var content = document.getElementById('rep-detail-content');
  var html = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px">';
  html += '<div><span style="font-size:22px;font-weight:800">' + rep.name + '</span>';
  html += '<span style="font-size:13px;color:var(--text2);margin-left:10px">' + (rep.area || '') + ' \u00B7 ' + rep.customerCount + ' \u5BB6\u5BA2\u6236 \u00B7 \u7E3D\u696D\u7E3E ' + fmtNum(rep.totalAmount) + '</span>';
  // \u7B49\u7D1A\u5206\u5E03
  if (rep.gradeDist) {
    var gradeColors = {'\u7279':'#ff2a85','A':'var(--green)','B':'var(--blue)','C':'var(--text2)'};
    Object.keys(rep.gradeDist).filter(function(g){return g!=='\u7121';}).forEach(function(g) {
      html += '<span style="margin-left:6px;background:' + (gradeColors[g]||'var(--text2)') + '20;color:' + (gradeColors[g]||'var(--text2)') + ';border:1px solid ' + (gradeColors[g]||'var(--text2)') + '40;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:800">' + g + '\xD7' + rep.gradeDist[g] + '</span>';
    });
  }
  html += '</div>';
  // \u6574\u9AD4\u9054\u6210\u7387
  var achHtml = '<span class="mono" style="font-size:15px;font-weight:700">YOY ' + fmtPct(rep.avgYoy) + '</span>';
  if (rep.overallAchieve != null) {
    var achColor = rep.overallAchieve >= 100 ? 'var(--green)' : rep.overallAchieve >= 70 ? 'var(--gold)' : 'var(--red)';
    achHtml += '<span style="margin-left:12px;font-size:13px;font-weight:700;color:' + achColor + '">\u9054\u6210\u7387 ' + rep.overallAchieve + '%</span>';
    achHtml += '<div style="margin-top:4px;background:var(--border);border-radius:4px;height:5px;width:120px"><div style="width:' + Math.min(rep.overallAchieve,100) + '%;background:' + achColor + ';border-radius:4px;height:5px"></div></div>';
  }
  html += achHtml + '</div>';

  // Sort bar
  html += '<div class="sort-bar" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;padding:6px 0;border-top:1px solid var(--border);border-bottom:1px solid var(--border)">';
  html += '<span style="font-size:11px;color:var(--text2);font-weight:600;margin-right:4px;align-self:center">\u6392\u5E8F:</span>';
  var sortOpts = [
    {key:'thisYearAmount', label:'今年業績'},
    {key:'yoyPct', label:'YOY'},
    {key:'lastOrderDate', label:'最後下單'},
    {key:'visitCount', label:'到訪次數'},
    {key:'contractHealth', label:'合約健康'},
    {key:'contractBalance', label:'合約餘額'},
    {key:'contractDue', label:'合約到期'}
  ];
  sortOpts.forEach(function(o) {
    var active = (rep._sortField === o.key);
    html += '<button class="sort-btn" data-sort="' + o.key + '" style="font-size:11px;padding:3px 10px;background:' + (active ? 'var(--gold)' : 'transparent') + ';border:1px solid ' + (active ? 'var(--gold)' : 'var(--border)') + ';border-radius:4px;color:' + (active ? '#000' : 'var(--text2)') + ';cursor:pointer;font-weight:' + (active ? '700' : '500') + '" onclick="sortCustomers(' + index + ',\'' + o.key + '\')">' + o.label + '</button>';
  });
  html += '</div>';

  html += '<div class="cust-grid" id="cust-grid-' + index + '">';
  // Will be populated by renderCustomers
  html += '</div>';
  html += '<div id="contract-area-' + index + '"></div>';
  content.innerHTML = html;

  renderCustomers(index);
}

function sortCustomers(index, field) {
  var rep = allData.reps[index];
  if (rep._sortField === field) {
    rep._sortAsc = !(rep._sortAsc || false);
  } else {
    rep._sortField = field;
    rep._sortAsc = false;
  }
  renderCustomers(index);
}

function renderCustomers(index) {
  var rep = allData.reps[index];
  var custs = rep.customers.slice();
  var field = rep._sortField || null;

  if (field === 'thisYearAmount') {
    custs.sort(function(a,b){ return rep._sortAsc ? a.thisYearAmount - b.thisYearAmount : b.thisYearAmount - a.thisYearAmount; });
  } else if (field === 'yoyPct') {
    custs.sort(function(a,b){ return rep._sortAsc ? (a.yoyPct||0) - (b.yoyPct||0) : (b.yoyPct||0) - (a.yoyPct||0); });
  } else if (field === 'lastOrderDate') {
    custs.sort(function(a,b){
      return rep._sortAsc ? (a.lastOrderDate||'').localeCompare(b.lastOrderDate||'') : (b.lastOrderDate||'').localeCompare(a.lastOrderDate||'');
    });
  } else if (field === 'visitCount') {
    custs.sort(function(a,b){ return rep._sortAsc ? (a.visits||[]).length - (b.visits||[]).length : (b.visits||[]).length - (a.visits||[]).length; });
  } else if (field === 'contractHealth') {
    var hl = {'正常':0,'觀察':1,'警示':2,'掛點':3,'待續約':4};
    custs.sort(function(a,b){
      var ha = hl[(a.contract||{}).health]||99, hb = hl[(b.contract||{}).health]||99;
      return rep._sortAsc ? ha - hb : hb - ha;
    });
  } else if (field === 'contractBalance') {
    custs.sort(function(a,b){
      var ba = (a.contract||{}).balance||0, bb = (b.contract||{}).balance||0;
      return rep._sortAsc ? ba - bb : bb - ba;
    });
  } else if (field === 'contractDue') {
    custs.sort(function(a,b){
      var da = (a.contract||{}).dueLevel||0, db = (b.contract||{}).dueLevel||0;
      return rep._sortAsc ? da - db : db - da;
    });
  } else {
    custs.sort(function(a,b){ return b.totalAmount - a.totalAmount; });
  }

  var html = '';
  custs.forEach(function(c, ci) {
    var yoyClass = c.yoyPct > 0 ? 'green' : (c.yoyPct < 0 ? 'red' : '');
    var contractStr = '-';
    var contractHealth = '';
    if (c.contract) {
      var ct = c.contract;
      contractHealth = ct.health || '';
      if (ct.totalContract) {
        var dueInfo = ct.dueText ? ' · ' + ct.dueText : '';
        contractStr = '合約 ' + fmtNum(ct.totalContract) + ' · 餘額 ' + (ct.balance != null ? fmtNum(ct.balance) : '-') + (ct.balRatio != null ? ' (' + ct.balRatio + '%)' : '') + dueInfo;
      }
    }
    var lod = c.lastOrderDate || '-';
    var lastVisit = (c.visits && c.visits.length > 0) ? c.visits[0].date : '-';
    var lastNote = (c.notes && c.notes.length > 0) ? c.notes[0].note : '';

    html += '<div class="cust-card">';
    var gradeColors = {'特':'#ff2a85','A':'var(--green)','B':'var(--blue)','C':'var(--text2)'};
    html += '<div class="cc-header">';
    html += '<span class="cc-name">' + c.name + '</span>';
    if (c.grade) html += '<span style="background:' + (gradeColors[c.grade]||'var(--text2)') + '20;color:' + (gradeColors[c.grade]||'var(--text2)') + ';border:1px solid ' + (gradeColors[c.grade]||'var(--text2)') + '40;border-radius:4px;padding:1px 7px;font-size:11px;font-weight:800;margin-left:4px">' + c.grade + '</span>';
    var HEALTH_LBL = {growth:'成長',decline:'退步',normal:'正常',warning:'注意',dormant:'休眠',no_sales:'無銷售'};
    html += '<span class="health-badge ' + c.health + '">' + (HEALTH_LBL[c.health] || c.health) + '</span>';
    if (contractHealth) html += '<span class="health-badge ' + contractHealth + '">' + contractHealth + '</span>';
    html += '</div>';
    // 目標達成進度條
    if (c.target) {
      var tachPct = Math.min(Math.round(c.thisYearAmount / c.target * 100), 100);
      var tachColor = tachPct >= 100 ? 'var(--green)' : tachPct >= 70 ? 'var(--gold)' : 'var(--red)';
      html += '<div style="margin:4px 0 6px">' +
        '<div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text2);margin-bottom:2px">' +
          '<span>目標 ' + fmtNum(c.target) + (c.contrib ? '　貢獻 ' + c.contrib : '') + '</span>' +
          '<span style="color:' + tachColor + ';font-weight:700">' + tachPct + '%</span>' +
        '</div>' +
        '<div style="background:var(--border);border-radius:3px;height:4px"><div style="width:' + tachPct + '%;background:' + tachColor + ';border-radius:3px;height:4px"></div></div>' +
      '</div>';
    }
    html += '<div class="cc-body">';
    html += '<div class="cc-stats">';
    html += '<div class="cc-stat"><div class="cs-label">\u4ECA\u5E74\u696D\u7E3E</div><div class="cs-value">' + fmtNum(c.thisYearAmount) + '</div></div>';
    html += '<div class="cc-stat"><div class="cs-label">\u53BB\u5E74\u540C\u671F</div><div class="cs-value">' + fmtNum(c.lastYearSamePeriod != null ? c.lastYearSamePeriod : c.lastYearAmount) + '</div></div>';
    html += '<div class="cc-stat"><div class="cs-label">\u53BB\u5E74\u6574\u5E74</div><div class="cs-value">' + fmtNum(c.lastYearAmount) + '</div></div>';
    html += '<div class="cc-stat"><div class="cs-label">YOY</div><div class="cs-value ' + yoyClass + '">' + fmtPct(c.yoyPct) + '</div></div>';
    html += '<div class="cc-stat"><div class="cs-label">\u4EA4\u6613\u6B21\u6578</div><div class="cs-value">' + c.saleCount + '</div></div>';
    if (c.achieveRate != null) html += '<div class="cc-stat"><div class="cs-label">\u9054\u6210\u7387</div><div class="cs-value">' + fmtPct(c.achieveRate) + '</div></div>';
    html += '<div class="cc-stat"><div class="cs-label">\u6700\u5F8C\u4E0B\u55AE</div><div class="cs-value mono" style="font-size:12px">' + lod + '</div></div>';
    html += '<div class="cc-stat"><div class="cs-label">\u6700\u5F8C\u62DC\u8A2A</div><div class="cs-value mono" style="font-size:12px">' + lastVisit + '</div></div>';
    if (contractStr !== '-') html += '<div class="cc-stat" style="grid-column:1/-1"><div class="cs-label" style="color:var(--gold)">\u5408\u7D04\u72C0\u6CC1</div><div class="cs-value mono" style="font-size:12px;color:var(--text2)">' + contractStr + '</div></div>';
    html += '</div>';

    // Monthly trend chart placeholder
    html += '<div class="chart-sm" id="trend-chart-' + index + '-' + ci + '"></div>';

    // Top series comparison
    html += '<div style="display:flex;gap:12px;margin:8px 0">';
    html += '<div style="flex:1;background:var(--surface2);border-radius:var(--radius-md);padding:10px">';
    html += '<div style="font-size:10px;color:var(--text2);font-weight:600;margin-bottom:4px">\u53BB\u5E74 Top 3</div>';
    (c.seriesTrend.lastYear || []).slice(0, 3).forEach(function(s) {
      html += '<div style="display:flex;justify-content:space-between;font-size:11px;padding:2px 0">';
      html += '<span>' + s.series + '</span><span class="mono">' + fmtNum(s.amount) + ' (' + s.pct + '%)</span></div>';
    });
    if (!c.seriesTrend.lastYear || c.seriesTrend.lastYear.length === 0) {
      html += '<div style="font-size:11px;color:var(--text2)">\u7121\u8CC7\u6599</div>';
    }
    html += '</div>';
    html += '<div style="flex:1;background:var(--surface2);border-radius:var(--radius-md);padding:10px">';
    html += '<div style="font-size:10px;color:var(--text2);font-weight:600;margin-bottom:4px">\u4ECA\u5E74 Top 3</div>';
    (c.seriesTrend.thisYear || []).slice(0, 3).forEach(function(s) {
      html += '<div style="display:flex;justify-content:space-between;font-size:11px;padding:2px 0">';
      html += '<span>' + s.series + '</span><span class="mono">' + fmtNum(s.amount) + ' (' + s.pct + '%)</span></div>';
    });
    if (!c.seriesTrend.thisYear || c.seriesTrend.thisYear.length === 0) {
      html += '<div style="font-size:11px;color:var(--text2)">\u7121\u8CC7\u6599</div>';
    }
    html += '</div>';
    html += '</div>';

    // Contract info
    html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-top:1px solid var(--border);font-size:12px">';
    html += '<span class="text-muted">\u5408\u7D04\uFF1A' + contractStr + '</span>';
    if (c.contract && c.contract.health) html += '<span class="gold">' + c.contract.health + '</span>';
    html += '</div>';

    // Recent note
    if (lastNote) {
      html += '<div style="font-size:11px;color:var(--text2);padding:4px 0;border-top:1px solid var(--border)">' + lastNote + '</div>';
    }

    // Recent visits
    if (c.visits && c.visits.length > 0) {
      html += '<div style="font-size:11px;color:var(--text2);padding:4px 0;border-top:1px solid var(--border)">';
      html += c.visits.slice(0, 2).map(function(v) { return v.date + (v.summary ? ' ' + v.summary : ''); }).join(' \u00B7 ');
      html += '</div>';
    }

    html += '</div></div>';
  });

  var grid = document.getElementById('cust-grid-' + index);
  if (grid) { grid.innerHTML = html; }

  // 合約分析專區
  var contractCusts = rep.customers.filter(function(c) { return c.contract && c.contract.totalContract; });
  var contractArea = document.getElementById('contract-area-' + index);
  if (contractArea) {
    if (contractCusts.length === 0) {
      contractArea.innerHTML = '';
    } else {
      var HEALTH_ORDER = {'正常':0,'觀察':1,'警示':2,'掛點':3,'待續約':4};
      var chHtml = '<div style="font-size:14px;font-weight:800;color:var(--gold);margin:20px 0 10px;padding-top:14px;border-top:1px solid var(--border)">📋 合約分析（' + contractCusts.length + ' 家客戶）</div>';
      chHtml += '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:12px">';
      chHtml += '<thead><tr style="border-bottom:1px solid var(--border)">';
      var ctHeaders = ['客戶','健康度','合約總額','餘額','餘額%','月消耗估算','到期'];
      ctHeaders.forEach(function(h) { chHtml += '<th style="padding:7px 10px;text-align:left;color:var(--text2);font-weight:600;white-space:nowrap">' + h + '</th>'; });
      chHtml += '</tr></thead><tbody>';
      var sorted = contractCusts.slice().sort(function(a,b) {
        var ha = HEALTH_ORDER[a.contract.health] != null ? HEALTH_ORDER[a.contract.health] : 99;
        var hb = HEALTH_ORDER[b.contract.health] != null ? HEALTH_ORDER[b.contract.health] : 99;
        return ha !== hb ? ha - hb : b.thisYearAmount - a.thisYearAmount;
      });
      sorted.forEach(function(c, ci) {
        var ct = c.contract;
        var hlth = ct.health || '—';
        var hlthClass = {'正常':'health-badge 正常','觀察':'health-badge 觀察','警示':'health-badge 警示','掛點':'health-badge 掛點','待續約':'health-badge 待續約'}[hlth] || 'health-badge';
        var bg = ci % 2 === 0 ? 'rgba(255,255,255,0.01)' : 'transparent';
        chHtml += '<tr style="border-bottom:1px solid rgba(255,255,255,0.04);background:' + bg + '">';
        chHtml += '<td style="padding:7px 10px;font-weight:600">' + c.name + '</td>';
        chHtml += '<td style="padding:7px 10px"><span class="' + hlthClass + '">' + hlth + '</span></td>';
        chHtml += '<td style="padding:7px 10px;font-variant-numeric:tabular-nums">' + fmtNum(ct.totalContract) + '</td>';
        chHtml += '<td style="padding:7px 10px;font-variant-numeric:tabular-nums">' + (ct.balance != null ? fmtNum(ct.balance) : '—') + '</td>';
        chHtml += '<td style="padding:7px 10px">' + (ct.balRatio != null ? ct.balRatio + '%' : '—') + '</td>';
        var monthly = ct.monthlyConsume != null ? fmtNum(ct.monthlyConsume) : '—';
        chHtml += '<td style="padding:7px 10px">' + monthly + '</td>';
        chHtml += '<td style="padding:7px 10px;font-size:11px;color:var(--text2)">' + (ct.dueText || '—') + '</td>';
        chHtml += '</tr>';
      });
      chHtml += '</tbody></table></div>';
      contractArea.innerHTML = chHtml;
    }
  }

  // Render charts after DOM update
  setTimeout(function() {
    custs.forEach(function(c, ci) {
      var el = document.getElementById('trend-chart-' + index + '-' + ci);
      if (!el) return;
      var ctx = document.createElement('canvas');
      el.appendChild(ctx);
      var months = c.monthlyTrend || [];
      var labels = months.map(function(m) { return m.month + '\u6708'; });
      var ty = months.map(function(m) { return m.thisYear; });
      var ly = months.map(function(m) { return m.lastYear; });
      charts.push(new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            { label: '\u4ECA\u5E74', data: ty, borderColor: '#c29d66', borderWidth: 2, pointRadius: 1.5, tension: 0.2, fill: false },
            { label: '\u53BB\u5E74', data: ly, borderColor: 'rgba(255,255,255,0.2)', borderWidth: 1.5, borderDash: [3,3], pointRadius: 0, tension: 0.2, fill: false }
          ]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: true, backgroundColor: '#111', borderColor: 'rgba(194,157,102,0.3)', borderWidth: 1, bodyColor: '#fff' } },
          scales: {
            x: { display: false, grid: { display: false } },
            y: { display: false, beginAtZero: true, grid: { display: false } }
          },
          elements: { point: { radius: 0 } }
        }
      }));
    });
  }, 50);
}

function closeRepDetail() {
  destroyCharts();
  document.getElementById('rep-table-card').style.display = '';
  document.getElementById('rep-detail').classList.remove('open');
}

function destroyCharts() {
  charts.forEach(function(c) { c.destroy(); });
  charts = [];
}

var repSortAsc = false;
var repSortField = 'totalAmount';
function sortRepTable(field) {
  if (repSortField === field) repSortAsc = !repSortAsc;
  else { repSortField = field; repSortAsc = false; }
  var reps = allData.reps.slice();
  reps.sort(function(a, b) {
    var va = a[field] || 0, vb = b[field] || 0;
    return repSortAsc ? va - vb : vb - va;
  });
  renderRepTable(reps);
}

window.addEventListener('DOMContentLoaded', function() { loadData(); });
</script>
</body>
</html>
