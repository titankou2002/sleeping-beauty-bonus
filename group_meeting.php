<?php
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
    :root{--bg:#0a0a0a;--paper:#111;--grid:rgba(194,157,102,.28);--line:rgba(255,255,255,.10);--text:#f6f1e6;--muted:#a9a39a;--accent:#c29d66;--gold:#f0cb84;--blue:#60a5fa;--green:#22c55e;--orange:#f59e0b;--red:#ef4444;--purple:#a78bfa;--co-sb:#f59e0b;--co-ad:#60a5fa;--co-xy:#a78bfa}
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
    .pill-sb{background:rgba(245,158,11,.15);color:var(--orange)}.pill-ad{background:rgba(96,165,250,.15);color:var(--blue)}.pill-xy{background:rgba(167,139,250,.15);color:var(--purple)}

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

    .mgr{background:rgba(255,255,255,.02);border:1px solid var(--line);border-radius:6px;padding:16px;margin-top:10px}
    .mgr .ml{font-size:13px;font-weight:700;color:var(--gold);margin-bottom:6px}
    .mgr .mc{font-size:14px;line-height:1.7;padding:10px 14px;background:rgba(255,255,255,.03);border-radius:4px;min-height:40px;color:var(--muted);font-style:italic}
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
      <a class="btn-link" href="meeting.php">高雅瓷月報</a>
      <a class="btn-link" href="index.php">回首頁</a>
    </div>
  </div>

  <div id="content"></div>
</div>

<script>
const URL_SB_REPORT = 'meeting.php';
const URL_AD_REPORT = '<?= URL_ANDYGA_REPORT ?>';
const URL_XY_REPORT = '<?= URL_XIYENA_REPORT ?>';
const CO_KEYS = ['sleepingBeauty','andyga','xiyena'];
const CO_LABELS = {sleepingBeauty:'高雅瓷',andyga:'安帝嘉',xiyena:'喜悅納'};
const CO_COLORS = {sleepingBeauty:'#f59e0b',andyga:'#60a5fa',xiyena:'#a78bfa'};
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

function loadReport(){
  document.getElementById('loading').classList.remove('hidden');
  const y=yearSel.value, m=monthSel.value;
  fetch(`api.php?action=group-detailed-report&year=${y}&month=${m}`)
    .then(r=>r.json())
    .then(res=>{
      document.getElementById('loading').classList.add('hidden');
      if(!res.success){alert('載入失敗: '+(res.msg||''));return}
      renderAll(res.data);
    })
    .catch(err=>{
      document.getElementById('loading').classList.add('hidden');
      console.error(err);alert('伺服器連線錯誤');
    });
}

function renderAll(d){
  charts.forEach(c=>c.destroy()); charts=[];
  const y=d.year, m=d.month, g=d.group, cos=d.companies;
  let html='';

  // === 1. 集團總覽 ===
  html+=`<div class="section"><div class="section-title">📊 集團總覽 — ${y} 年 ${m} 月</div><div class="kpi-grid">
    <div class="kpi"><div class="lb">集團本月營收</div><div class="vl">${fmtW(g.kpis.sales)}</div><div class="sb">${fmtYoy(g.kpis.salesYoyPct)}</div></div>
    <div class="kpi"><div class="lb">YTD 累計營收</div><div class="vl">${fmtW(g.kpis.ytdSales)}</div><div class="sb">${fmtYoy(g.kpis.ytdYoyPct)}</div></div>
    <div class="kpi"><div class="lb">出貨坪數</div><div class="vl">${fmtP(g.kpis.pings)}</div></div>
    <div class="kpi"><div class="lb">合約客戶總數</div><div class="vl">${g.contractTotal.active} 家</div><div class="sb">目標 ${fmtW(g.contractTotal.monthlyTarget)}</div></div>
  </div></div>`;

  // === 2. 各公司比較 ===
  html+=`<div class="section"><div class="section-title">📑 各公司比較</div>
    <div class="tabs"><button class="tab active" onclick="switchTab(this,'cmp-month')">當月</button><button class="tab" onclick="switchTab(this,'cmp-ytd')">當年累計</button><button class="tab" onclick="switchTab(this,'cmp-quarter')">季比較</button></div>`;

  // 當月
  html+=`<div id="cmp-month" class="tab-pane"><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const kp=c.kpis||{};
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
    html+=`</div>`;
  });
  html+=`</div></div>`;

  // === 5. 合約客戶狀況 ===
  html+=`<div class="section"><div class="section-title">📋 合約客戶狀況</div><div class="co3">`;
  CO_KEYS.forEach(k=>{
    const c=cos[k]; const ct=c.contract; const kp=c.kpis||{};
    const actual=kp.sales||0; const achievePct=ct.monthlyTarget>0?pct(actual,ct.monthlyTarget):'—';
    const hc=ct.healthCounts||{};
    html+=`<div class="cc"><div class="nm"><span class="dot" style="background:${c.color}"></span>${c.name}</div>
      <div style="font-size:13px;display:flex;flex-direction:column;gap:4px">
        <span>合約客戶 <b>${ct.active}</b> 家</span>
        <span>正常 ${hc['正常']||0} · 逾期 ${hc['逾期']||0} · 嚴重 ${hc['嚴重']||0} · 待續 ${hc['待續']||0}</span>
        <span>合約月目標 <b>${fmtW(ct.monthlyTarget)}</b></span>
        <span>本月達成率 <b class="${Number(achievePct)>=100?'up':'dn'}">${achievePct}%</b></span>
      </div>
      <div class="link-row"><a class="btn-report" href="${reportUrl(k,d.year,d.month)}" target="_blank">🔗 ${c.name}月報明細</a></div></div>`;
  });
  html+=`</div>`;

  // 合約目標 vs 實際柱狀圖
  html+=`<div class="chart-wrap" style="height:220px;margin-top:20px"><canvas id="contractChart"></canvas></div>`;

  // 重覆合約客戶
  if(d.overlapContracts && d.overlapContracts.length>0){
    html+=`<div style="font-size:14px;font-weight:700;color:var(--muted);margin:20px 0 8px">跨公司合約客戶</div>
    <div class="tbl-wrap"><table class="tbl"><tr><th>客戶</th><th>簽約公司</th><th class="r">合計月目標</th><th class="r">本月實際</th><th>狀態</th></tr>`;
    d.overlapContracts.forEach(oc=>{
      const pills=Object.keys(oc.companies).map(k=>`<span class="pill ${CO_PILL[k]}">${CO_LABELS[k]}</span>`).join('');
      const status=oc.actual>=oc.totalTarget?'<span class="up">達標</span>':'<span class="dn">未達</span>';
      html+=`<tr><td>${oc.name}</td><td>${pills}</td><td class="r">${fmtW(oc.totalTarget)}</td><td class="r" style="font-weight:700">${fmtW(oc.actual)}</td><td>${status}</td></tr>`;
    });
    html+=`</table></div>`;
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
      <tr><th>客戶</th><th>涵蓋公司</th><th class="r">合計營收</th></tr>`;
    d.crossCompany.forEach(c=>{
      const pills=Object.keys(c.companies).map(k=>`<span class="pill ${CO_PILL[k]}">${CO_LABELS[k]}</span>`).join('');
      html+=`<tr><td>${c.name}</td><td>${pills}</td><td class="r" style="font-weight:700">${fmtW(c.totalMonth)}</td></tr>`;
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

  // === 10. 主管報告 ===
  html+=`<div class="section"><div class="section-title">📝 主管報告</div>
    <div class="mgr"><div class="ml">📢 行銷計畫</div><div class="mc">（經理人填寫區域）</div></div>
    <div class="mgr"><div class="ml">💬 集團內部溝通</div><div class="mc">（經理人填寫區域）</div></div>
    <div class="mgr"><div class="ml">📄 其它報告</div><div class="mc">（經理人填寫區域）</div></div>
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
    {label:'高雅瓷',data:sbS,borderColor:'#f59e0b',backgroundColor:'transparent',borderWidth:2,tension:.2,pointRadius:2},
    {label:'安帝嘉',data:adS,borderColor:'#60a5fa',backgroundColor:'transparent',borderWidth:2,tension:.2,pointRadius:2},
    {label:'喜悅納',data:xyS,borderColor:'#a78bfa',backgroundColor:'transparent',borderWidth:2,tension:.2,pointRadius:2},
    {label:'集團總和',data:gpS,borderColor:'#c29d66',borderDash:[5,5],backgroundColor:'transparent',borderWidth:2,tension:.2,pointRadius:0}
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
    {label:'實際業績',data:CO_KEYS.map(k=>cos[k].kpis?Math.round((cos[k].kpis.sales||0)/10000):0),backgroundColor:CO_KEYS.map(k=>CO_COLORS[k])}
  ]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{x:{grid:{display:false},ticks:{color:'#a9a39a'}},y:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'萬'}}}}}));

  // Brand chart
  const allBrands=new Set();
  CO_KEYS.forEach(k=>{Object.keys(cos[k].products.byBrand||{}).forEach(b=>{if(b&&b!=='未知')allBrands.add(b)})});
  const brandLabels=[...allBrands].sort((a,b)=>{
    const sumA=CO_KEYS.reduce((s,k)=>s+((cos[k].products.byBrand||{})[a]||{amount:0}).amount,0);
    const sumB=CO_KEYS.reduce((s,k)=>s+((cos[k].products.byBrand||{})[b]||{amount:0}).amount,0);
    return sumB-sumA;
  }).slice(0,10);
  const ctx6=document.getElementById('brandChart').getContext('2d');
  charts.push(new Chart(ctx6,{type:'bar',data:{labels:brandLabels,datasets:CO_KEYS.map(k=>({label:CO_LABELS[k],data:brandLabels.map(b=>Math.round(((cos[k].products.byBrand||{})[b]||{amount:0}).amount/10000)),backgroundColor:CO_COLORS[k]}))},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',plugins:{legend:{labels:{color:'#f6f1e6',font:{weight:'bold'}}}},scales:{y:{grid:{display:false},ticks:{color:'#a9a39a',font:{size:11}}},x:{grid:{color:'rgba(255,255,255,.05)'},ticks:{color:'#a9a39a',callback:v=>v+'萬'}}}}}));
}

window.onload=function(){loadReport()};
</script>
</body>
</html>
