<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>集團月報</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23c29d66'/%3E%3Ctext x='32' y='46' font-family='Arial,sans-serif' font-size='40' font-weight='900' fill='%230a0a0a' text-anchor='middle'%3EG%3C/text%3E%3C/svg%3E">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root{--bg:#0a0a0a;--paper:#111;--grid:rgba(194,157,102,.28);--line:rgba(255,255,255,.10);--text:#f6f1e6;--muted:#a9a39a;--accent:#c29d66;--gold:#f0cb84;--blue:#60a5fa;--green:#22c55e;--orange:#f59e0b;--red:#ef4444;--purple:#a78bfa;--co-sb:#ff2a85;--co-ad:#10b981;--co-xy:#38bdf8}
    *{box-sizing:border-box}
    body{margin:0;font-family:"Noto Sans TC","PingFang TC",sans-serif;background:radial-gradient(circle at top,rgba(194,157,102,.12),transparent 35%) var(--bg);color:var(--text)}
    .page{max-width:1480px;margin:0 auto;padding:24px}
    .topbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;margin-bottom:24px;border-bottom:1px solid var(--line);padding-bottom:16px}
    .title-wrap{display:flex;align-items:center;gap:12px}
    .brand-logo{width:40px;height:40px;border-radius:10px;background:var(--accent);color:var(--bg);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900}
    .title-wrap h1{margin:0;font-size:26px;font-weight:900;color:var(--gold)}
    .controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    select,button,a.btn-link{height:40px;border:1px solid rgba(194,157,102,.45);background:rgba(255,255,255,.04);color:var(--text);font-size:13px;font-weight:700;padding:0 14px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;border-radius:4px;transition:all .2s}
    select:focus,button:hover,a.btn-link:hover{border-color:var(--gold);background:rgba(194,157,102,.15);outline:none}
    button.btn-primary{background:linear-gradient(180deg,rgba(194,157,102,.4),rgba(194,157,102,.15));border-color:var(--gold);color:var(--gold)}
    .loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(10,10,10,.85);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:1000}
    .loading-overlay.hidden{display:none}
    .spinner{width:50px;height:50px;border:3px solid rgba(194,157,102,.2);border-radius:50%;border-top-color:var(--gold);animation:spin 1s ease-in-out infinite;margin-bottom:16px}
    @keyframes spin{to{transform:rotate(360deg)}}
    .loading-text{font-size:16px;color:var(--gold);font-weight:700}

    .section{background:var(--paper);border:1px solid var(--line);border-radius:8px;padding:24px;margin-bottom:24px;box-shadow:0 4px 20px rgba(0,0,0,.3)}
    .section-title{font-size:17px;font-weight:800;color:var(--gold);margin:0 0 18px;display:flex;align-items:center;gap:8px}

    .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
    .kpi{background:rgba(255,255,255,.03);border:1px solid var(--line);border-radius:6px;padding:16px}
    .kpi .lb{font-size:12px;color:var(--muted);margin-bottom:4px}
    .kpi .vl{font-size:22px;font-weight:800}
    .kpi .sb{font-size:12px;margin-top:4px}
    .up{color:var(--red);font-weight:800}.dn{color:var(--green);font-weight:800}

    .tabs{display:flex;gap:0;margin-bottom:16px;border-bottom:1px solid var(--line)}
    .tab{padding:8px 16px;font-size:13px;font-weight:700;color:var(--muted);cursor:pointer;border:none;background:none;border-bottom:2px solid transparent;transition:all .2s}
    .tab.active{color:var(--gold);border-bottom-color:var(--gold)}

    .co3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    @media(max-width:900px){.co3{grid-template-columns:1fr}}
    .cc{background:rgba(255,255,255,.02);border:1px solid var(--line);border-radius:8px;padding:16px 20px}
    .cc .nm{font-size:14px;font-weight:800;margin-bottom:8px;display:flex;align-items:center;gap:8px}
    .dot{width:10px;height:10px;border-radius:50%;display:inline-block;flex-shrink:0}
    .cc .v1{font-size:20px;font-weight:800}
    .cc .s1{font-size:12px;margin-top:2px}
    .cc .mt{font-size:12px;color:var(--muted);margin-top:8px;border-top:1px solid var(--line);padding-top:8px;display:flex;flex-direction:column;gap:4px}
    .cc .link-row{margin-top:10px;padding-top:8px;border-top:1px solid var(--line)}
    .btn-report{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border:1px solid rgba(194,157,102,.5);border-radius:4px;background:rgba(194,157,102,.08);color:var(--gold);font-size:12px;font-weight:700;text-decoration:none;transition:all .2s}
    .btn-report:hover{background:var(--accent);color:var(--bg);transform:translateY(-1px)}

    .tbl{width:100%;border-collapse:collapse;font-size:13px}
    .tbl th{text-align:left;font-weight:700;font-size:12px;color:var(--muted);padding:8px;border-bottom:1px solid var(--line);white-space:nowrap}
    .tbl td{padding:8px;border-bottom:1px solid var(--line)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl .r{text-align:right}
    .tbl-wrap{overflow-x:auto}

    .pill{display:inline-block;font-size:11px;padding:2px 8px;border-radius:999px;font-weight:700;margin:1px 2px}
    .pill-sb{background:rgba(255,42,133,.15);color:var(--co-sb)}.pill-ad{background:rgba(16,185,129,.15);color:var(--co-ad)}.pill-xy{background:rgba(56,189,248,.15);color:var(--co-xy)}

    .alert-box{border-radius:6px;padding:10px 14px;font-size:13px;margin-top:8px;display:flex;align-items:flex-start;gap:8px}
    .alert-warn{background:rgba(245,158,11,.1);color:var(--orange)}
    .alert-danger{background:rgba(239,68,68,.1);color:var(--red)}
    .badge-alert{display:inline-flex;align-items:center;gap:3px;font-size:11px;padding:2px 8px;border-radius:999px;font-weight:700}
    .badge-danger{background:rgba(239,68,68,.15);color:var(--red)}
    .badge-new{background:rgba(167,139,250,.15);color:var(--purple)}
    .badge-surge{background:rgba(34,197,94,.15);color:var(--green)}

    .bar-row{display:flex;align-items:center;gap:8px;margin:3px 0;font-size:12px}
    .bar-row .bar-label{width:80px;text-align:right;color:var(--muted);flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .bar-row .bar-track{flex:1;height:18px;background:rgba(255,255,255,.04);border-radius:3px;overflow:hidden}
    .bar-row .bar-val{font-size:11px;width:55px;flex-shrink:0;color:var(--muted);text-align:right}

    .chart-wrap{position:relative;width:100%}
    .charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:24px}
    @media(max-width:1024px){.charts-grid{grid-template-columns:1fr}}

    .modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.75);z-index:1000;display:flex;align-items:center;justify-content:center;padding:24px}
    .modal-box{background:var(--paper);border:1px solid var(--line);border-radius:12px;max-width:900px;width:100%;max-height:85vh;overflow-y:auto;padding:24px;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.5)}
    .modal-close{position:absolute;top:12px;right:16px;background:none;border:none;color:var(--muted);font-size:24px;cursor:pointer;padding:4px 8px;line-height:1}
    .modal-close:hover{color:var(--text)}
    .modal-title{font-size:17px;font-weight:800;color:var(--gold);margin:0 0 16px;display:flex;align-items:center;gap:8px}
    .health-tag{display:inline-block;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:700;margin-right:6px}
    .ht-normal{background:rgba(34,197,94,.15);color:var(--green)}
    .ht-overdue{background:rgba(245,158,11,.15);color:var(--orange)}
    .ht-severe{background:rgba(239,68,68,.15);color:var(--red)}
    .ht-pending{background:rgba(96,165,250,.15);color:var(--blue)}
    .ht-renewed{background:rgba(167,139,250,.15);color:var(--purple)}
    .ht-watch{background:rgba(245,158,11,.15);color:var(--orange)}
    .ht-warning{background:rgba(239,68,68,.2);color:var(--red)}
    .ht-danger{background:rgba(220,38,38,.25);color:var(--red)}
    .ht-black{background:rgba(0,0,0,.3);color:#888}
    .detail-btn{background:none;border:1px solid rgba(194,157,102,.4);color:var(--gold);font-size:12px;font-weight:700;padding:4px 12px;border-radius:4px;cursor:pointer;transition:all .2s}
    .detail-btn:hover{background:rgba(194,157,102,.15);border-color:var(--gold)}

    .mgr-tab-content { margin-top: 14px; }
    .mgr-form-group { margin-bottom: 16px; }
    .mgr-form-group label { display: block; font-size: 13px; font-weight: 700; color: var(--gold); margin-bottom: 6px; }
    .mgr-textarea { width: 100%; height: 110px; background: rgba(255,255,255,0.03); border: 1px solid var(--line); border-radius: 6px; color: var(--text); padding: 10px 14px; font-size: 13px; line-height: 1.6; resize: vertical; transition: all 0.2s; }
    .mgr-textarea:focus { border-color: var(--gold); background: rgba(194,157,102,0.06); outline: none; }
    .mgr-textarea:disabled { color: var(--muted); opacity: 0.6; cursor: not-allowed; }
    .btn-save-report { height: 36px; padding: 0 16px; border: 1px solid var(--gold); border-radius: 4px; background: linear-gradient(180deg, rgba(194,157,102,0.3), rgba(194,157,102,0.1)); color: var(--gold); font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
    .btn-save-report:hover:not(:disabled) { background: var(--accent); color: var(--bg); transform: translateY(-1px); }
    .btn-save-report:disabled { border-color: var(--line); color: var(--muted); background: rgba(255,255,255,0.03); cursor: not-allowed; }
  </style>
</head>
<body>

<div id="loading" class="loading-overlay">
  <div class="spinner"></div>
  <div class="loading-text">彙整集團三公司數據中...</div>
</div>

<div class="page">
  <div class="topbar">
    <div class="title-wrap">
      <div class="brand-logo">G</div>
      <h1>集團月報</h1>
    </div>
    <div class="controls">
      <select id="year"></select>
      <select id="month"></select>
      <button class="btn-primary" onclick="loadReport()">📊 載入報表</button>
      <button style="background:rgba(194,157,102,.15);border-color:rgba(194,157,102,.5);color:var(--gold)" onclick="rebuildAllCaches()">🔄 同步全部快取</button>
      <span id="cache-info-group" style="font-size:11px;color:var(--muted)"></span>
      <a class="btn-link" href="meeting.php">高雅瓷月報</a>
      <a class="btn-link" href="index.php">回首頁</a>
    </div>
  </div>

  <div id="content"></div>
</div>
<div id="modal-root"></div>

<script>
function driveUrlToDirect(url) {
  const s = String(url || '');
  let m = s.match(/\/file\/d\/([^\/]+)/);
  if (m) return 'https://drive.google.com/thumbnail?id=' + m[1] + '&sz=w1200';
  m = s.match(/[?&]id=([^&]+)/);
  if (m) return 'https://drive.google.com/thumbnail?id=' + m[1] + '&sz=w1200';
  return s;
}
function escapeHtml(s) {
  return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
function toggleOverlapRows() {
  const rows = document.querySelectorAll('.overlap-row-more');
  const btn = document.getElementById('toggle-overlap-btn');
  if (rows.length === 0) return;
  const isHidden = rows[0].style.display === 'none';
  rows.forEach(r => r.style.display = isHidden ? '' : 'none');
  btn.textContent = isHidden ? '收折其餘合約客戶' : '展開其餘 ' + rows.length + ' 筆合約客戶';
}

const URL_SB_REPORT = 'meeting.php';
const URL_AD_REPORT = '<?= URL_ANDYGA_REPORT ?>';
const URL_XY_REPORT = '<?= URL_XIYENA_REPORT ?>';
const CO_KEYS = ['sleepingBeauty','andyga','xiyena'];
const CO_LABELS = {sleepingBeauty:'高雅瓷',andyga:'安帝嘉',xiyena:'喜悅納'};
const CO_COLORS = {sleepingBeauty:'#ff2a85',andyga:'#10b981',xiyena:'#38bdf8'};
const CO_PILL = {sleepingBeauty:'pill-sb',andyga:'pill-ad',xiyena:'pill-xy'};

const now = new Date();
const def = new Date(now.getFullYear(), now.getMonth()-1, 1);
const yearSel = document.getElementById('year');
const monthSel = document.getElementById('month');
for(let y=now.getFullYear();y>=2024;y--) yearSel.innerHTML+=`<option value="${y}">${y}年</option>`;
for(let m=1;m<=12;m++) monthSel.innerHTML+=`<option value="${m}">${m}月</option>`;
yearSel.value=def.getFullYear(); monthSel.value=def.getMonth()+1;

let charts = [];

function fmtW(n){if(n==null)return'-';return Math.round(n/10000).toLocaleString('zh-TW')+' 萬'}
function fmtP(n){if(n==null)return'-';return Number(n).toFixed(1)+' 坪'}
function fmtN(n){if(n==null)return'-';return Math.round(n).toLocaleString('zh-TW')}
function fmtYoy(v){if(v==null)return'-';if(v>0)return`<span class="up">▲ +${v.toFixed(1)}%</span>`;if(v<0)return`<span class="dn">▼ ${v.toFixed(1)}%</span>`;return`<span style="color:var(--muted)">${v.toFixed(1)}%</span>`}
function pct(a,b){return b>0?((a/b)*100).toFixed(1):0}
function reportUrl(key,y,m){
  if(key==='sleepingBeauty')return`${URL_SB_REPORT}?year=${y}&month=${m}`;
  if(key==='andyga')return`${URL_AD_REPORT}&year=${y}&month=${m}`;
  return`${URL_XY_REPORT}&year=${y}&month=${m}`;
}

let groupAiExplainLoading = false;
let groupAiExplainCached = {};

function toggleGroupAiExplain(){
  const el=document.getElementById('group-ai-explain');
  if(!el)return;
  if(el.style.display!=='none'){
    el.style.display='none';
    return;
  }
  el.style.display='block';
  if(groupAiExplainCached[yearSel.value+'-'+monthSel.value]){
    el.innerHTML=groupAiExplainCached[yearSel.value+'-'+monthSel.value];
    return;
  }
  if(groupAiExplainLoading)return;
  groupAiExplainLoading=true;
  el.innerHTML='<div style="text-align:center;color:var(--muted)"><div class="spinner" style="width:24px;height:24px;margin:0 auto 8px"></div>AI 分析中...</div>';
  const y=yearSel.value, m=monthSel.value;
  fetch(`api.php?action=group-ai-explain&year=${y}&month=${m}`)
    .then(r=>r.json())
    .then(res=>{
      groupAiExplainLoading=false;
      if(res.success){
        const html='<div style="white-space:pre-wrap">'+escapeHtml(res.reply)+'</div>';
        groupAiExplainCached[y+'-'+m]=html;
        el.innerHTML=html;
      } else {
        el.innerHTML='<div style="color:var(--red)">⚠ 分析失敗：'+(res.msg||'')+'</div>';
      }
    })
    .catch(err=>{
      groupAiExplainLoading=false;
      el.innerHTML='<div style="color:var(--red)">⚠ 連線錯誤</div>';
    });
}

function loadReport(){
  document.getElementById('loading').classList.remove('hidden');
  const y=yearSel.value, m=monthSel.value;
  fetch(`api.php?action=group-detailed-report&year=${y}&month=${m}`)
    .then(r=>r.json())
    .then(res=>{
      document.getElementById('loading').classList.add('hidden');
      if(!res.success){alert('載入失敗: '+(res.msg||''));return}
      renderAll(res.data);
      loadManagerReports(y, m);
    })
    .catch(err=>{
      document.getElementById('loading').classList.add('hidden');
      console.error(err);alert('伺服器連線錯誤');
    });
}

function renderAll(d){
  charts.forEach(c=>c.destroy()); charts=[];
  _groupData=d;
  const y=d.year, m=d.month, g=d.group, cos=d.companies;
  let html='';

  // === 1. 集團總覽 ===
  html+=`<div class="section"><div class="section-title">📊 集團總覽 — ${y} 年 ${m} 月</div><div class="kpi-grid">
    <div class="kpi"><div class="lb">集團本月營收</div><div class="vl">${fmtW(g.kpis.sales)}</div><div class="sb">${fmtYoy(g.kpis.salesYoyPct)} <span style="font-size:10px;color:var(--muted)">vs 去年同期</span></div></div>
    <div class="kpi"><div class="lb">YTD 累計營收</div><div class="vl">${fmtW(g.kpis.ytdSales)}</div><div class="sb">${fmtYoy(g.kpis.ytdYoyPct)} <span style="font-size:10px;color:var(--muted)">vs 去年同期</span></div></div>
    <div class="kpi"><div class="lb">出貨坪數</div><div class="vl">${fmtP(g.kpis.pings)}</div></div>
    <div class="kpi"><div class="lb">合約客戶總數</div><div class="vl">${g.contractTotal.active} 家</div><div class="sb">目標 ${fmtW(g.contractTotal.monthlyTarget)}</div></div>
  </div>
  <details style="margin-top:12px"><summary style="cursor:pointer;font-size:12px;color:var(--muted)">📈 各月營收趨勢（分公司顏色）</summary>
    <div class="chart-wrap" style="height:280px;margin-top:12px"><canvas id="monthlyStackChart"></canvas></div>
  </details>
  <div style="margin-top:16px;text-align:center">
    <button class="detail-btn" onclick="toggleGroupAiExplain()" style="font-size:13px;padding:6px 18px">🤖 AI 白話分析</button>
    <div id="group-ai-explain" style="display:none;margin-top:12px;padding:16px;background:rgba(194,157,102,.06);border:1px solid rgba(194,157,102,.2);border-radius:8px;font-size:14px;line-height:1.8;text-align:left"></div>
  </div></div>`;

  // === 2. 各公司比較 ===
  html+=`<div class="section"><div class="section-title">📑 各公司比較</div>
    <div class="tabs"><button class="tab active" onclick="switchTab(this,'cmp-month')">當月</button><button class="tab" onclick="switchTab(this,'cmp-ytd')">當年累計</button><button class="tab" onclick="switchTab(this,'cmp-quarter')">季比較</button></div>`;

  // 當月
  html+=`<div id="cmp-month" class="tab-pane"><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const kp=c.kpis||{};
    if(!c.success){
      html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>
        <div style="color:var(--red);font-size:13px;margin:8px 0">⚠ 讀取失敗：${c.error||'快取工作表不存在或無權限'}</div>
        <div class="link-row"><a class="btn-report" href="${reportUrl(k,y,m)}" target="_blank">🔗 開啟${c.name}月報</a></div></div>`;
      return;
    }
    const shareP = g.kpis.sales>0 ? pct(kp.sales||0,g.kpis.sales)+'%' : '-';
    html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>
      <div class="v1">${fmtW(kp.sales)}</div><div class="s1">${fmtYoy(kp.salesYoyPct)}</div>
      <div class="mt"><span>坪數 ${fmtP(kp.pings)} · 佔集團 ${shareP}</span><span>合約 ${c.contract.active} 家 · 目標 ${fmtW(c.contract.monthlyTarget)}</span></div>
      <div class="link-row"><a class="btn-report" href="${reportUrl(k,y,m)}" target="_blank">🔗 開啟${c.name}月報</a></div></div>`;
  });
  html+=`</div></div>`;

  // 當年累計
  html+=`<div id="cmp-ytd" class="tab-pane" style="display:none"><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const kp=c.kpis||{};
    const shareP = g.kpis.ytdSales>0 ? pct(kp.ytdSales||0,g.kpis.ytdSales)+'%' : '-';
    const ytdYoy = (kp.ytdPrevSales||0)>0 ? (((kp.ytdSales||0)-(kp.ytdPrevSales||0))/(kp.ytdPrevSales||1)*100) : 0;
    html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>
      <div class="v1">${fmtW(kp.ytdSales)}</div><div class="s1">${fmtYoy(ytdYoy)}</div>
      <div class="mt"><span>佔集團 ${shareP}</span><span>去年同期 ${fmtW(kp.ytdPrevSales)}</span></div>
      <div class="link-row"><a class="btn-report" href="${reportUrl(k,y,m)}" target="_blank">🔗 開啟${c.name}月報</a></div></div>`;
  });
  html+=`</div></div>`;

  // 季比較
  html+=`<div id="cmp-quarter" class="tab-pane" style="display:none"><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const q=c.quarter;
    const qqYoy = q.lastYear.amount>0 ? ((q.current.amount-q.lastYear.amount)/q.lastYear.amount*100) : 0;
    const qqChg = q.prev.amount>0 ? ((q.current.amount-q.prev.amount)/q.prev.amount*100) : 0;
    html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>
      <div class="v1">${q.current.label}: ${fmtW(q.current.amount)}</div>
      <div class="s1">${q.prev.label}: ${fmtW(q.prev.amount)} ${fmtYoy(qqChg)}</div>
      <div class="mt"><span>去年 ${q.lastYear.label}: ${fmtW(q.lastYear.amount)} ${fmtYoy(qqYoy)}</span></div></div>`;
  });
  html+=`</div></div></div>`;

  // === 3. 趨勢圖 & 佔比圖 ===
  html+=`<div class="charts-grid">
    <div class="section"><div class="section-title">📈 年度月銷趨勢</div><div class="chart-wrap" style="height:320px"><canvas id="trendChart"></canvas></div></div>
    <div class="section"><div class="section-title">🍩 當月營收佔比</div><div class="chart-wrap" style="height:320px"><canvas id="shareChart"></canvas></div></div>
  </div>`;

  // === 4. 產品銷售比較 ===
  html+=`<div class="section"><div class="section-title">📦 產品銷售比較</div>`;
  // 尺寸 by 金額
  html+=`<div style="font-size:14px;font-weight:700;color:var(--muted);margin-bottom:8px">依尺寸 — 金額（萬元）</div>`;
  html+=`<div class="chart-wrap" style="height:260px"><canvas id="sizeAmtChart"></canvas></div>`;
  // 尺寸 by 坪數
  html+=`<div style="font-size:14px;font-weight:700;color:var(--muted);margin:20px 0 8px">依尺寸 — 坪數</div>`;
  html+=`<div class="chart-wrap" style="height:260px"><canvas id="sizePingChart"></canvas></div>`;
  // 產品種類
  html+=`<div style="font-size:14px;font-weight:700;color:var(--muted);margin:20px 0 8px">產品種類佔比</div><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const cats=c.products.byCategory||{};
    const entries=Object.entries(cats).sort((a,b)=>b[1].amount-a[1].amount).slice(0,6);
    const total=entries.reduce((s,e)=>s+e[1].amount,0);
    html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>`;
    entries.forEach(([cat,v])=>{
      const p=total>0?(v.amount/total*100).toFixed(0):0;
      html+=`<div class="bar-row"><span class="bar-label">${cat||'未分類'}</span><div class="bar-track"><div style="width:${p}%;height:100%;background:${c.color};border-radius:3px"></div></div><span class="bar-val">${p}%</span></div>`;
    });
    html+=`<div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
      <button class="detail-btn" onclick="showProductDetail('${k}','category')">種類明細</button>
      <button class="detail-btn" onclick="showProductDetail('${k}','brand')">品牌明細</button>
      <button class="detail-btn" onclick="showProductDetail('${k}','size')">尺寸明細</button>
    </div></div>`;
  });
  html+=`</div></div>`;

  // === 4.5. 熱銷產品比較 ===
  html+=`<div class="section"><div class="section-title">🏆 熱銷產品比較（前十大）</div><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const topProds=c.topProducts||[];
    html+=`<div class="hot-prod-col">
      <div class="hot-prod-header" style="background:${c.color}15;color:${c.color};font-weight:800;text-align:center;padding:8px;border-radius:4px;margin-bottom:12px;font-size:14px;border:1px solid ${c.color}30">${c.name}</div>`;
    for(let i=0; i<10; i++) {
      const row = topProds[i];
      if(row) {
        html+=`<div class="hot-prod-card" style="background:rgba(255,255,255,.02);border:1px solid var(--line);border-radius:6px;padding:10px;margin-bottom:10px;display:flex;flex-direction:column;align-items:center;text-align:center;">`;
        if (row.imageUrl) {
          html+=`<div class="hot-prod-img-wrap" style="width:100%;height:110px;overflow:hidden;border-radius:4px;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;">
            <img class="hot-prod-img" src="${escapeHtml(driveUrlToDirect(row.imageUrl))}" alt="" style="width:100%;height:100%;object-fit:contain;" onerror="this.remove(); this.parentNode.classList.add('is-empty'); this.parentNode.textContent='無圖片';">
          </div>`;
        } else {
          html+=`<div class="hot-prod-img-wrap is-empty" style="width:100%;height:110px;overflow:hidden;border-radius:4px;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px;">無圖片</div>`;
        }
        const roundedPings = Math.round(row.pings);
        const cleanAmt = fmtW(row.amount).replace(/\s/g, '');
        const sizeStr = row.size ? ` (${row.size})` : '';
        html+=`<div class="hot-prod-txt-main" style="font-size:13px;font-weight:700;margin-top:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;" title="${escapeHtml(row.sku)} ${escapeHtml(row.series || '未分類')}${escapeHtml(sizeStr)}">${escapeHtml(row.sku)} ${escapeHtml(row.series || '未分類')}${escapeHtml(sizeStr)}</div>
          <div class="hot-prod-txt-sub" style="font-size:12px;color:var(--muted);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%;">${roundedPings}坪/${cleanAmt}</div>
        </div>`;
      } else {
        html+=`<div class="hot-prod-card" style="background:rgba(255,255,255,.01);border:1px dashed var(--line);border-radius:6px;padding:10px;margin-bottom:10px;display:flex;flex-direction:column;align-items:center;text-align:center;">
          <div class="hot-prod-img-wrap is-empty" style="width:100%;height:110px;overflow:hidden;border-radius:4px;background:rgba(255,255,255,.02);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px;border:1px dashed var(--line);">無產品</div>
          <div class="hot-prod-txt-main" style="font-size:13px;font-weight:700;margin-top:8px;color:var(--muted);">—</div>
          <div class="hot-prod-txt-sub" style="font-size:12px;color:var(--muted);margin-top:4px;">—</div>
        </div>`;
      }
    }
    html+=`</div>`;
  });
  html+=`</div></div>`;

  // === 5. 合約客戶狀況 ===
  html+=`<div class="section"><div class="section-title">📋 合約客戶狀況</div><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const ct=c.contract;
    const hc=ct.healthCounts||{};
    const total=ct.active||1;
    const warnPct=total>0?(((hc['觀察']||0)+(hc['警示']||0)+(hc['危險']||0)+(hc['黑死']||0))/total*100).toFixed(0):0;
    const healthPct = ct.healthPct != null ? ct.healthPct : 0;
    html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>
      <div style="font-size:13px;display:flex;flex-direction:column;gap:4px">
        <span>合約客戶 <b>${ct.active}</b> 家</span>
        <span>🟢 正常 ${hc['正常']||0} · 🟡 觀察 ${hc['觀察']||0} · 🟠 警示 ${hc['警示']||0} · 🔴 危險 ${hc['危險']||0}</span>
        <span>⚫ 黑死 ${hc['黑死']||0} · 異常率 ${warnPct}%</span>
        <span>合約月目標 <b>${fmtW(ct.monthlyTarget)}</b></span>
        <span>簽約店實銷 <b>${fmtW(ct.signedStoreSales)}</b></span>
        <span>健康度 <b style="font-size:16px" class="${Number(healthPct)>=75?'up':'dn'}">${healthPct}%</b></span>
      </div>
      <div style="margin-top:8px;height:8px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden;display:flex">
        <div style="width:${total>0?(((hc['正常']||0))/total*100).toFixed(0):0}%;background:var(--green)"></div>
        <div style="width:${total>0?(((hc['觀察']||0)+(hc['警示']||0)+(hc['危險']||0)+(hc['黑死']||0))/total*100).toFixed(0):0}%;background:var(--red)"></div>
      </div>
      <div style="font-size:11px;color:var(--muted);margin-top:4px;display:flex;gap:12px"><span style="color:var(--green)">■ 正常</span><span style="color:var(--red)">■ 異常（觀察/警示/危險/黑死）</span></div>
      <div class="link-row" style="display:flex;gap:8px;flex-wrap:wrap"><button class="detail-btn" onclick="showContractDetail('${k}')">📋 合約客戶明細</button><a class="btn-report" href="${reportUrl(k,d.year,d.month)}" target="_blank">🔗 ${c.name}月報</a></div></div>`;
  });
  html+=`</div>`;

  // 合約目標 vs 實際柱狀圖
  html+=`<div class="chart-wrap" style="height:220px;margin-top:20px"><canvas id="contractChart"></canvas></div>`;

  // 合約健康度摘要
  const hcTotal = g.contractTotal.healthCounts || {};
  const hcAll = (hcTotal['正常']||0)+(hcTotal['觀察']||0)+(hcTotal['警示']||0)+(hcTotal['危險']||0)+(hcTotal['黑死']||0);
  const hcHealthy = (hcTotal['正常']||0);
  const hcHealthPct = hcAll > 0 ? (hcHealthy/hcAll*100).toFixed(1) : 0;
  html+=`<div style="font-size:12px;color:var(--muted);margin-top:8px;display:flex;gap:16px;flex-wrap:wrap">
    <span style="color:var(--green)">正常 ${hcTotal['正常']||0}</span>
    <span style="color:var(--orange)">觀察 ${hcTotal['觀察']||0}</span>
    <span style="color:var(--orange)">警示 ${hcTotal['警示']||0}</span>
    <span style="color:var(--red)">危險 ${hcTotal['危險']||0}</span>
    <span style="color:#888">黑死 ${hcTotal['黑死']||0}</span>
    <span style="color:var(--gold);font-weight:700">健康度 ${hcHealthPct}%</span>
  </div>`;

  // 重覆合約客戶
  if(d.overlapContracts && d.overlapContracts.length>0){
    const sortedOC=[...d.overlapContracts].sort((a,b)=>b.actual-a.actual);
    html+=`<div style="font-size:14px;font-weight:700;color:var(--muted);margin:20px 0 8px">跨公司合約客戶</div>
    <div class="tbl-wrap"><table class="tbl"><tr><th>客戶</th><th>簽約公司</th><th class="r">合計月目標</th><th class="r">本月實際</th><th class="r">集團當月佔比</th><th class="r">上月佔比</th></tr>`;
    sortedOC.forEach((oc, idx)=>{
      const pills=Object.keys(oc.companies).map(k=>`<span class="pill ${CO_PILL[k]}">${CO_LABELS[k]}</span>`).join('');
      
      const curPct = g.kpis.sales > 0 ? (oc.actual / g.kpis.sales * 100) : 0;
      const lastPct = (g.kpis.lastMonthSales && g.kpis.lastMonthSales > 0) ? ((oc.actualLastMonth || 0) / g.kpis.lastMonthSales * 100) : 0;
      const diff = curPct - lastPct;
      
      let statusHtml = '';
      if (diff > 0) {
        statusHtml = `<span class="up">${lastPct.toFixed(2)}% ▲</span>`;
      } else if (diff < 0) {
        statusHtml = `<span class="dn">${lastPct.toFixed(2)}% ▼</span>`;
      } else {
        statusHtml = `<span style="color:var(--muted)">${lastPct.toFixed(2)}%</span>`;
      }

      const rowClass = idx >= 10 ? 'class="overlap-row-more" style="display:none"' : '';
      html+=`<tr ${rowClass}><td>${oc.name}</td><td>${pills}</td><td class="r">${fmtW(oc.totalTarget)}</td><td class="r" style="font-weight:700">${fmtW(oc.actual)}</td><td class="r">${curPct.toFixed(2)}%</td><td class="r">${statusHtml}</td></tr>`;
    });
    html+=`</table></div>`;
    if (sortedOC.length > 10) {
      html+=`<div style="margin-top:10px;text-align:center;"><button class="detail-btn" id="toggle-overlap-btn" onclick="toggleOverlapRows()">展開其餘 ${sortedOC.length - 10} 筆合約客戶</button></div>`;
    }
  }
  html+=`</div>`;

  // === 6. 前二十大客戶 ===
  html+=`<div class="section"><div class="section-title">🏆 集團前二十大客戶</div><div class="tbl-wrap"><table class="tbl">
    <tr><th>#</th><th>客戶</th><th class="r">高雅瓷</th><th class="r">安帝嘉</th><th class="r">喜悅納</th><th class="r">合計</th><th class="r">去年同期</th><th class="r">YOY</th><th>AI 示警</th></tr>`;
  d.top20.forEach((c,i)=>{
    const sb=c.companies.sleepingBeauty; const ad=c.companies.andyga; const xy=c.companies.xiyena;
    const yoyStr=c.isNew?'<span class="up">新客</span>':fmtYoy(c.yoy);
    let alertHtml='';
    c.alerts.forEach(a=>{
      if(a==='衰退警告')alertHtml+=`<span class="badge-alert badge-danger">⚠ 衰退</span>`;
      else if(a==='急升')alertHtml+=`<span class="badge-alert badge-surge">↑ 急升</span>`;
      else if(a==='新客')alertHtml+=`<span class="badge-alert badge-new">✦ 新進</span>`;
      else if(a==='流失')alertHtml+=`<span class="badge-alert badge-danger">⚠ 流失</span>`;
    });
    html+=`<tr><td>${i+1}</td><td>${c.name}</td>
      <td class="r">${sb?fmtW(sb.curMonth):'—'}</td>
      <td class="r">${ad?fmtW(ad.curMonth):'—'}</td>
      <td class="r">${xy?fmtW(xy.curMonth):'—'}</td>
      <td class="r" style="font-weight:700">${fmtW(c.totalMonth)}</td>
      <td class="r">${fmtW(c.totalPrevMonth)}</td>
      <td class="r">${yoyStr}</td>
      <td>${alertHtml}</td></tr>`;
  });
  html+=`</table></div></div>`;

  // === 7. 跨公司客戶 ===
  if(d.crossCompany && d.crossCompany.length>0){
    html+=`<div class="section"><div class="section-title">🔗 跨公司客戶（在 2 家以上消費）</div><div class="tbl-wrap"><table class="tbl">
      <tr><th>客戶</th><th>涵蓋公司</th><th class="r">集團佔比</th><th class="r">合計營收</th></tr>`;
    d.crossCompany.forEach(c=>{
      const pills = Object.keys(c.companies).map(k => {
        const coSales = cos[k].kpis ? cos[k].kpis.sales : 0;
        const custCoSales = c.companies[k].curMonth || 0;
        const share = coSales > 0 ? (custCoSales / coSales * 100).toFixed(1) : '0.0';
        return `<span class="pill ${CO_PILL[k]}" title="${CO_LABELS[k]}實銷: ${fmtW(custCoSales)}">${CO_LABELS[k]} ${fmtW(custCoSales).replace(/\s/g, '')} (${share}%)</span>`;
      }).join('');
      const groupShare = g.kpis.sales > 0 ? (c.totalMonth / g.kpis.sales * 100).toFixed(2) : '0.00';
      html+=`<tr><td>${c.name}</td><td>${pills}</td><td class="r">${groupShare}%</td><td class="r" style="font-weight:700">${fmtW(c.totalMonth)}</td></tr>`;
    });
    html+=`</table></div></div>`;
  }

  // === 8. 品牌銷售比較 ===
  html+=`<div class="section"><div class="section-title">🏷️ 品牌銷售比較</div>
    <div class="chart-wrap" style="height:320px"><canvas id="brandChart"></canvas></div></div>`;

  // === 9. AI 綜合警示 ===
  if(d.alerts && d.alerts.length>0){
    html+=`<div class="section"><div class="section-title">⚠️ AI 綜合警示</div>`;
    d.alerts.forEach(a=>{
      const cls=a.includes('流失')||a.includes('衰退')?'alert-danger':'alert-warn';
      html+=`<div class="alert-box ${cls}">⚠ ${a}</div>`;
    });
    html+=`</div>`;
  }

  // === 10. 庫存比較 ===
  html+=`<div class="section"><div class="section-title">📦 庫存比較</div>`;
  // 集團庫存加總
  const invTypes=[{key:'normal',label:'正常品',color:'var(--green)'},{key:'sleeper',label:'睡美人',color:'var(--orange)'},{key:'discontinued',label:'不續辦',color:'var(--red)'}];
  let groupInv={normal:{cost:0,pings:0},sleeper:{cost:0,pings:0},discontinued:{cost:0,pings:0},total:{cost:0,pings:0}};
  CO_KEYS.forEach(k=>{const inv=cos[k].inventory||{};
    invTypes.forEach(t=>{groupInv[t.key].cost+=(inv[t.key]||{}).cost||0;groupInv[t.key].pings+=(inv[t.key]||{}).pings||0;});
    groupInv.total.cost+=(inv.total||{}).cost||0;groupInv.total.pings+=(inv.total||{}).pings||0;
  });

  // 集團 KPI
  html+=`<div class="kpi-grid" style="margin-bottom:16px">
    <div class="kpi"><div class="lb">集團庫存總額</div><div class="vl">${fmtW(groupInv.total.cost)}</div><div class="sb">${fmtP(groupInv.total.pings)}</div></div>`;
  invTypes.forEach(t=>{
    const p=groupInv.total.cost>0?pct(groupInv[t.key].cost,groupInv.total.cost):'0';
    html+=`<div class="kpi"><div class="lb" style="color:${t.color}">${t.label}</div><div class="vl">${fmtW(groupInv[t.key].cost)}</div><div class="sb">佔 ${p}% · ${fmtP(groupInv[t.key].pings)}</div></div>`;
  });
  html+=`</div>`;

  // 各公司庫存明細
  html+=`<div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const inv=c.inventory||{};
    const total=(inv.total||{}).cost||0;
    html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>
      <div style="font-size:13px;margin-bottom:8px">庫存總額 <b>${fmtW(total)}</b> · ${fmtP((inv.total||{}).pings||0)}</div>`;
    invTypes.forEach(t=>{
      const v=(inv[t.key]||{});
      const p=total>0?pct(v.cost||0,total):'0';
      const w=Math.min(Number(p),100);
      html+=`<div class="bar-row"><span class="bar-label" style="color:${t.color}">${t.label}</span><div class="bar-track"><div style="width:${w}%;height:100%;background:${t.color};border-radius:3px"></div></div><span class="bar-val">${fmtW(v.cost||0)}</span></div>
      <div style="font-size:11px;color:var(--muted);margin:0 0 4px 88px">${p}% · ${fmtP(v.pings||0)} · ${v.skuCount||0} 品項</div>`;
    });
    html+=`</div>`;
  });
  html+=`</div>`;

  // 庫存佔比圖
  html+=`<div class="chart-wrap" style="height:260px;margin-top:16px"><canvas id="invChart"></canvas></div>`;
  html+=`</div>`;

  // === 11. 主管報告 ===
  html+=`<div class="section" id="mgr-report-section"><div class="section-title">📝 主管報告</div>
    <div class="tabs">
      <button class="tab active" onclick="switchTab(this,'mgr-sb')">高雅瓷</button>
      <button class="tab" onclick="switchTab(this,'mgr-ad')">安帝嘉</button>
      <button class="tab" onclick="switchTab(this,'mgr-xy')">喜悅納</button>
    </div>
    
    <!-- 高雅瓷 -->
    <div id="mgr-sb" class="tab-pane mgr-tab-content">
      <div class="mgr-form-group">
        <label>📢 行銷計畫</label>
        <textarea id="sb-mkt" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <div class="mgr-form-group">
        <label>💬 集團內部溝通</label>
        <textarea id="sb-comm" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <div class="mgr-form-group">
        <label>📄 其它報告</label>
        <textarea id="sb-other" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <button id="sb-save-btn" class="btn-save-report" onclick="saveReport('sleepingBeauty')" disabled>💾 儲存高雅瓷報告</button>
    </div>
    
    <!-- 安帝嘉 -->
    <div id="mgr-ad" class="tab-pane mgr-tab-content" style="display:none">
      <div class="mgr-form-group">
        <label>📢 行銷計畫</label>
        <textarea id="ad-mkt" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <div class="mgr-form-group">
        <label>💬 集團內部溝通</label>
        <textarea id="ad-comm" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <div class="mgr-form-group">
        <label>📄 其它報告</label>
        <textarea id="ad-other" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <button id="ad-save-btn" class="btn-save-report" onclick="saveReport('andyga')" disabled>💾 儲存安帝嘉報告</button>
    </div>
    
    <!-- 喜悅納 -->
    <div id="mgr-xy" class="tab-pane mgr-tab-content" style="display:none">
      <div class="mgr-form-group">
        <label>📢 行銷計畫</label>
        <textarea id="xy-mkt" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <div class="mgr-form-group">
        <label>💬 集團內部溝通</label>
        <textarea id="xy-comm" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <div class="mgr-form-group">
        <label>📄 其它報告</label>
        <textarea id="xy-other" class="mgr-textarea" placeholder="主管報告載入中..." disabled></textarea>
      </div>
      <button id="xy-save-btn" class="btn-save-report" onclick="saveReport('xiyena')" disabled>💾 儲存喜悅納報告</button>
    </div>
  </div>`;

  document.getElementById('content').innerHTML=html;
  renderCharts(d);
}

function switchTab(btn, id){
  btn.parentElement.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  const section=btn.closest('.section');
  section.querySelectorAll('.tab-pane').forEach(p=>p.style.display='none');
  section.querySelector('#'+id).style.display='';
}

function renderCharts(d){
  const g=d.group, cos=d.companies;
  const labels=Array.from({length:12},(_,i)=>(i+1)+'月');
  const fill0=()=>new Array(12).fill(0);

  const getSales=(key)=>{
    const c=cos[key]; if(!c.success||!c.monthlySales)return fill0();
    return c.monthlySales.map(n=>Math.round(n/10000));
  };

  // Trend chart
  const sbS=getSales('sleepingBeauty'), adS=getSales('andyga'), xyS=getSales('xiyena');
  const gpS=g.monthlySales.map(n=>Math.round(n/10000));
  const ctx1=document.getElementById('trendChart').getContext('2d');
  charts.push(new Chart(ctx1,{type:'line',data:{labels,datasets:[
    {label:'高雅瓷',data:sbS,borderColor:'#ff2a85',backgroundColor:'transparent',borderWidth:2,tension:.2,pointRadius:2},
    {label:'安帝嘉',data:adS,borderColor:'#10b981',backgroundColor:'transparent',borderWidth:2,tension:.2,pointRadius:2},
    {label:'喜悅納',data:xyS,borderColor:'#38bdf8',backgroundColor:'transparent',borderWidth:2,tension:.2,pointRadius:2}
  ]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{x:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a'}},y:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+' 萬'}}}}}));

  // Share chart
  const vals=CO_KEYS.map(k=>{const c=cos[k];return c.success&&c.kpis?Math.round((c.kpis.sales||0)/10000):0});
  const total=vals.reduce((a,b)=>a+b,0);
  const shareLabels=CO_KEYS.map((k,i)=>`${CO_LABELS[k]} ${total>0?((vals[i]/total)*100).toFixed(1):'0'}%`);
  const ctx2=document.getElementById('shareChart').getContext('2d');
  charts.push(new Chart(ctx2,{type:'doughnut',data:{labels:shareLabels,datasets:[{data:vals,backgroundColor:CO_KEYS.map(k=>CO_COLORS[k]),borderWidth:1,borderColor:'#111'}]},options:{responsive:true,maintainAspectRatio:false,cutout:'55%',plugins:{legend:{position:'right',labels:{color:'#f6f1e6',font:{weight:'bold'}}},tooltip:{callbacks:{label:c=>{const v=c.parsed||0;return` ${c.label}: ${v} 萬`}}}}}}));

  // Size amount chart
  const allSizes=new Set();
  CO_KEYS.forEach(k=>{Object.keys(cos[k].products.bySize||{}).forEach(s=>allSizes.add(s))});
  const sizeLabels=[...allSizes].sort((a,b)=>{
    const sumA=CO_KEYS.reduce((s,k)=>s+((cos[k].products.bySize||{})[a]||{amount:0}).amount,0);
    const sumB=CO_KEYS.reduce((s,k)=>s+((cos[k].products.bySize||{})[b]||{amount:0}).amount,0);
    return sumB-sumA;
  }).slice(0,8);
  const ctx3=document.getElementById('sizeAmtChart').getContext('2d');
  charts.push(new Chart(ctx3,{type:'bar',data:{labels:sizeLabels,datasets:CO_KEYS.map(k=>({label:CO_LABELS[k],data:sizeLabels.map(s=>Math.round(((cos[k].products.bySize||{})[s]||{amount:0}).amount/10000)),backgroundColor:CO_COLORS[k]}))},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{x:{grid:{display:false},ticks:{color:'#a9a39a',font:{size:11},maxRotation:45}},y:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'萬'}}}}}));

  // Size pings chart
  const ctx4=document.getElementById('sizePingChart').getContext('2d');
  charts.push(new Chart(ctx4,{type:'bar',data:{labels:sizeLabels,datasets:CO_KEYS.map(k=>({label:CO_LABELS[k],data:sizeLabels.map(s=>Math.round(((cos[k].products.bySize||{})[s]||{pings:0}).pings*10)/10),backgroundColor:CO_COLORS[k]}))},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{x:{grid:{display:false},ticks:{color:'#a9a39a',font:{size:11},maxRotation:45}},y:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'坪'}}}}}));

  // Contract chart
  const ctx5=document.getElementById('contractChart').getContext('2d');
  charts.push(new Chart(ctx5,{type:'bar',data:{labels:CO_KEYS.map(k=>CO_LABELS[k]),datasets:[
    {label:'合約目標',data:CO_KEYS.map(k=>Math.round(cos[k].contract.monthlyTarget/10000)),backgroundColor:'rgba(255,255,255,.06)',borderColor:'rgba(255,255,255,.2)',borderWidth:1},
    {label:'簽約店實銷',data:CO_KEYS.map(k=>cos[k].contract?Math.round((cos[k].contract.signedStoreSales||0)/10000):0),backgroundColor:CO_KEYS.map(k=>CO_COLORS[k])}
  ]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{x:{grid:{display:false},ticks:{color:'#a9a39a'}},y:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'萬'}}}}}));

  // Brand chart
  const allBrands=new Set();
  CO_KEYS.forEach(k=>{Object.keys(cos[k].products.byBrand||{}).forEach(b=>{if(b&&b!=='未知')allBrands.add(b)})});
  const brandLabels=[...allBrands].sort((a,b)=>{
    const sumA=CO_KEYS.reduce((s,k)=>s+((cos[k].products.byBrand||{})[a]||{amount:0}).amount,0);
    const sumB=CO_KEYS.reduce((s,k)=>s+((cos[k].products.byBrand||{})[b]||{amount:0}).amount,0);
    return sumB-sumA;
  }).slice(0,10);
  // Inventory chart
  const invEl=document.getElementById('invChart');
  if(invEl){
    const ctx7=invEl.getContext('2d');
    charts.push(new Chart(ctx7,{type:'bar',data:{labels:CO_KEYS.map(k=>CO_LABELS[k]),datasets:[
      {label:'正常品',data:CO_KEYS.map(k=>Math.round(((cos[k].inventory||{}).normal||{}).cost||0)/10000),backgroundColor:'#22c55e'},
      {label:'睡美人',data:CO_KEYS.map(k=>Math.round(((cos[k].inventory||{}).sleeper||{}).cost||0)/10000),backgroundColor:'#f59e0b'},
      {label:'不續辦',data:CO_KEYS.map(k=>Math.round(((cos[k].inventory||{}).discontinued||{}).cost||0)/10000),backgroundColor:'#ef4444'}
    ]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{x:{stacked:true,grid:{display:false},ticks:{color:'#a9a39a'}},y:{stacked:true,grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'萬'}}}}}));
  }

  const ctx6=document.getElementById('brandChart').getContext('2d');
  charts.push(new Chart(ctx6,{type:'bar',data:{labels:brandLabels,datasets:CO_KEYS.map(k=>({label:CO_LABELS[k],data:brandLabels.map(b=>Math.round(((cos[k].products.byBrand||{})[b]||{amount:0}).amount/10000)),backgroundColor:CO_COLORS[k]}))},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{y:{stacked:true,grid:{display:false},ticks:{color:'#a9a39a',font:{size:11}}},x:{stacked:true,grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'萬'}}}}}));

  // 各月營收趨勢堆疊長條圖（集團總覽下方摺疊區）
  const msEl=document.getElementById('monthlyStackChart');
  if(msEl){
    const msLabels=[];
    for(let mi=1;mi<=d.month;mi++)msLabels.push(mi+'月');
    charts.push(new Chart(msEl.getContext('2d'),{type:'bar',data:{labels:msLabels,datasets:CO_KEYS.map(k=>({label:CO_LABELS[k],data:msLabels.map((_,idx)=>{const ms=cos[k].monthlySales||[];return Math.round((ms[idx]||0)/10000)}),backgroundColor:CO_COLORS[k]}))},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}},tooltip:{callbacks:{label:c=>{const ds=c.dataset;const total=ds.data.reduce((a,b)=>a+b,0);return` ${c.dataset.label}: ${c.parsed||0} 萬 (佔比 ${c.parsed>0?((c.parsed||0)/total*100).toFixed(1):0}%)`}}}},scales:{x:{stacked:true,grid:{display:false},ticks:{color:'#a9a39a'}},y:{stacked:true,grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'萬'}}}}}));
  }
}

let _groupData=null;

function showModal(html){
  document.getElementById('modal-root').innerHTML=`<div class="modal-overlay" onclick="if(event.target===this)closeModal()"><div class="modal-box"><button class="modal-close" onclick="closeModal()">✕</button>${html}</div></div>`;
}
function closeModal(){document.getElementById('modal-root').innerHTML='';}

function showContractDetail(companyKey){
  if(!_groupData)return;
  const c=_groupData.companies[companyKey];
  const ct=c.contract; const detail=ct.detail||[];
  const hc=ct.healthCounts||{};
  const buckets=['正常','觀察','警示','危險','黑死'];
  const htClass={'正常':'ht-normal','觀察':'ht-watch','警示':'ht-warning','危險':'ht-danger','黑死':'ht-black'};
  const htLabel={'正常':'🟢 健康','觀察':'🟡 觀察','警示':'🟠 警示','危險':'🔴 危險','黑死':'⚫ 黑死'};

  let h=`<div class="modal-title"><span class="dot" style="background:${c.color};width:12px;height:12px"></span>${c.name} — 合約客戶明細</div>`;
  h+=`<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">`;
  buckets.forEach(b=>{
    if(!hc[b])return;
    const cls=htClass[b]||'';
    h+=`<span class="health-tag ${cls}">${b} ${hc[b]}</span>`;
  });
  h+=`</div>`;

  buckets.forEach(b=>{
    const items=detail.filter(d=>d.health===b);
    if(items.length===0)return;
    const cls=htClass[b]||'';
    h+=`<div style="margin-top:16px"><div style="font-size:14px;font-weight:700;margin-bottom:8px"><span class="health-tag ${cls}">${b}</span> ${items.length} 筆</div>`;
    h+=`<div class="tbl-wrap"><table class="tbl"><tr><th>#</th><th>健康度</th><th>客戶</th><th>沖帳方式</th><th>合約總額</th><th>張數</th><th>餘額</th><th>餘額%</th><th>月消耗</th><th>最後票期</th><th>預期</th></tr>`;
    items.forEach((d,i)=>{
      const brColor = d.balRatio >= 90 ? 'color:var(--red)' : d.balRatio >= 70 ? 'color:var(--orange)' : '';
      const htColor = htClass[d.health] ? `class="${htClass[d.health]}"` : '';
      h+=`<tr><td>${i+1}</td><td ${htColor}>${htLabel[d.health]||d.health}</td><td>${d.name}</td><td>${d.paymentMethod||'—'}</td>
        <td class="r">${fmtW(d.totalContract)}</td><td class="r">${d.qty||1}</td>
        <td class="r" style="${brColor};font-weight:700">${fmtW(d.balance)}</td>
        <td class="r" style="${brColor}">${d.balRatio}%</td>
        <td class="r">${d.consumption>0?fmtW(d.consumption):'—'}</td>
        <td style="font-size:11px">${d.lastDue||'—'}</td>
        <td style="font-size:11px;color:var(--muted)">${d.dueText||'—'}</td></tr>`;
    });
    h+=`</table></div>`;
  });

  showModal(h);
}

function showProductDetail(companyKey, type){
  if(!_groupData)return;
  const c=_groupData.companies[companyKey];
  const data=c.products||{};
  const map=type==='category'?data.byCategory:type==='brand'?data.byBrand:data.bySize;
  if(!map||Object.keys(map).length===0){showModal(`<div class="modal-title">${c.name} — 無資料</div>`);return;}

  const title=type==='category'?'產品種類':type==='brand'?'品牌':'尺寸';
  const entries=Object.entries(map).sort((a,b)=>b[1].amount-a[1].amount);
  const totalAmt=entries.reduce((s,e)=>s+e[1].amount,0);
  const totalPing=entries.reduce((s,e)=>s+e[1].pings,0);

  let h=`<div class="modal-title"><span class="dot" style="background:${c.color};width:12px;height:12px"></span>${c.name} — ${title}銷售明細</div>`;
  h+=`<div style="font-size:13px;color:var(--muted);margin-bottom:12px">本月合計 ${fmtW(totalAmt)} · ${fmtP(totalPing)}</div>`;
  h+=`<table class="tbl"><tr><th>${title}</th><th class="r">金額</th><th class="r">佔比</th><th class="r">坪數</th><th>佔比條</th></tr>`;
  entries.forEach(([name,v])=>{
    const p=totalAmt>0?(v.amount/totalAmt*100).toFixed(1):'0';
    const w=Math.min(Number(p),100);
    h+=`<tr><td>${name||'未分類'}</td><td class="r" style="font-weight:700">${fmtW(v.amount)}</td><td class="r">${p}%</td><td class="r">${fmtP(v.pings)}</td>
      <td><div style="width:120px;height:14px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden"><div style="width:${w}%;height:100%;background:${c.color};border-radius:3px"></div></div></td></tr>`;
  });
  h+=`</table>`;
  showModal(h);
}

function rebuildAllCaches(){
  const el=document.getElementById('cache-info-group');
  el.textContent='同步三家公司快取中...';
  fetch(`api.php?action=cron-rebuild-all&token=<?= CRON_TOKEN ?>`).then(r=>r.json()).then(res=>{
    if(res.success){
      const msgs=Object.entries(res.results).map(([k,v])=>k+(v.success?'✓':'✗')).join(' · ');
      el.textContent='同步完成：'+msgs+' ('+res.time+')';
      loadReport();
    } else { el.textContent='失敗'; }
  }).catch(()=>{el.textContent='連線失敗';});
}
function loadCacheInfoGroup(){
  fetch('api.php?action=cache-info').then(r=>r.json()).then(res=>{
    if(res.success) document.getElementById('cache-info-group').textContent='快取：'+res.lastUpdate;
  }).catch(()=>{});
}

function loadManagerReports(y, m) {
  const fields = ['sb-mkt', 'sb-comm', 'sb-other', 'ad-mkt', 'ad-comm', 'ad-other', 'xy-mkt', 'xy-comm', 'xy-other'];
  fields.forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.value = '';
      el.disabled = true;
      el.placeholder = '主管報告載入中...';
    }
  });
  
  const buttons = ['sb-save-btn', 'ad-save-btn', 'xy-save-btn'];
  buttons.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.disabled = true;
  });

  fetch(`api.php?action=get-mgr-report&year=${y}&month=${m}`)
    .then(r => r.json())
    .then(res => {
      if (res.success && res.data) {
        const d = res.data;
        const mapping = {
          'sb-mkt': d.sleepingBeauty.marketingPlan,
          'sb-comm': d.sleepingBeauty.communication,
          'sb-other': d.sleepingBeauty.otherReport,
          'ad-mkt': d.andyga.marketingPlan,
          'ad-comm': d.andyga.communication,
          'ad-other': d.andyga.otherReport,
          'xy-mkt': d.xiyena.marketingPlan,
          'xy-comm': d.xiyena.communication,
          'xy-other': d.xiyena.otherReport
        };
        
        Object.entries(mapping).forEach(([id, val]) => {
          const el = document.getElementById(id);
          if (el) {
            el.value = val || '';
            el.placeholder = '請輸入內容...';
            el.disabled = false;
          }
        });
        
        buttons.forEach(id => {
          const el = document.getElementById(id);
          if (el) el.disabled = false;
        });
      } else {
        fields.forEach(id => {
          const el = document.getElementById(id);
          if (el) el.placeholder = '載入主管報告失敗: ' + (res.msg || '未知錯誤');
        });
      }
    })
    .catch(err => {
      console.error(err);
      fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.placeholder = '連線伺服器失敗，無法讀取報告';
      });
    });
}

function saveReport(coKey) {
  const y = yearSel.value, m = monthSel.value;
  let prefix = 'sb';
  if (coKey === 'andyga') prefix = 'ad';
  if (coKey === 'xiyena') prefix = 'xy';
  
  const mktVal = document.getElementById(prefix + '-mkt').value;
  const commVal = document.getElementById(prefix + '-comm').value;
  const otherVal = document.getElementById(prefix + '-other').value;
  
  const btn = document.getElementById(prefix + '-save-btn');
  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = '💾 儲存中...';
  
  const formData = new FormData();
  formData.append('year', y);
  formData.append('month', m);
  formData.append('coKey', coKey);
  formData.append('marketingPlan', mktVal);
  formData.append('communication', commVal);
  formData.append('otherReport', otherVal);
  
  fetch('api.php?action=save-mgr-report', {
    method: 'POST',
    body: formData
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      if (res.success) {
        btn.textContent = '✓ 儲存成功';
        setTimeout(() => { btn.textContent = originalText; }, 2000);
      } else {
        alert('儲存失敗: ' + (res.msg || ''));
        btn.textContent = originalText;
      }
    })
    .catch(err => {
      console.error(err);
      alert('連線伺服器失敗，請稍後再試');
      btn.disabled = false;
      btn.textContent = originalText;
    });
}

window.onload=function(){loadCacheInfoGroup();loadReport();};
</script>
</body>
</html>
