<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>每日戰報</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23c29d66'/%3E%3Ctext x='32' y='46' font-family='Arial,sans-serif' font-size='40' font-weight='900' fill='%230a0a0a' text-anchor='middle'%3ED%3C/text%3E%3C/svg%3E">
  <style>
    :root{--bg:#0a0a0a;--paper:#111;--grid:rgba(194,157,102,.28);--line:rgba(255,255,255,.10);--text:#f6f1e6;--muted:#a9a39a;--accent:#c29d66;--gold:#f0cb84;--blue:#60a5fa;--green:#22c55e;--orange:#f59e0b;--red:#ef4444;--purple:#a78bfa;--co-sb:#c29d66;--co-ad:#10b981;--co-xy:#38bdf8}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:"Noto Sans TC","PingFang TC",sans-serif;background:radial-gradient(circle at top,rgba(194,157,102,.12),transparent 35%) var(--bg);color:var(--text);min-height:100vh}
    .page{max-width:1400px;margin:0 auto;padding:20px 16px}

    /* Topbar */
    .topbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:20px;border-bottom:1px solid var(--line);padding-bottom:14px}
    .title-wrap{display:flex;align-items:center;gap:10px}
    .brand-logo{width:36px;height:36px;border-radius:8px;background:var(--accent);color:var(--bg);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;flex-shrink:0}
    h1{font-size:22px;font-weight:900;color:var(--gold)}
    .controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    input[type=date],select,button,a.btn-link{height:36px;border:1px solid rgba(194,157,102,.45);background:rgba(255,255,255,.04);color:var(--text);font-size:13px;font-weight:600;padding:0 12px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;border-radius:4px;transition:all .2s}
    input[type=date]:focus,select:focus,button:hover,a.btn-link:hover{border-color:var(--gold);background:rgba(194,157,102,.12);outline:none}
    button.btn-primary{background:linear-gradient(180deg,rgba(194,157,102,.4),rgba(194,157,102,.15));border-color:var(--gold);color:var(--gold)}
    #last-update{font-size:11px;color:var(--muted)}

    /* Loading */
    .loading-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(10,10,10,.85);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:1000}
    .loading-overlay.hidden{display:none}
    .spinner{width:44px;height:44px;border:3px solid rgba(194,157,102,.2);border-radius:50%;border-top-color:var(--gold);animation:spin 1s ease-in-out infinite;margin-bottom:14px}
    @keyframes spin{to{transform:rotate(360deg)}}
    .loading-text{font-size:15px;color:var(--gold);font-weight:700}

    /* Grand KPI */
    .grand-kpi{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px}
    .gk{background:var(--paper);border:1px solid var(--line);border-radius:8px;padding:14px 16px;text-align:center}
    .gk .lbl{font-size:11px;color:var(--muted);margin-bottom:6px}
    .gk .val{font-size:22px;font-weight:900;color:var(--gold)}

    /* Company grid */
    .co-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    @media(max-width:960px){.co-grid{grid-template-columns:1fr}}

    .co-card{background:var(--paper);border:1px solid var(--line);border-radius:8px;overflow:hidden}
    .co-header{padding:10px 14px;font-size:14px;font-weight:800;display:flex;align-items:center;justify-content:space-between}
    .co-header .last-tx{font-size:11px;font-weight:400;opacity:.75}
    .co-body{padding:12px 14px}

    /* KPI row inside card */
    .kpi-row{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:12px}
    .kpi-box{background:rgba(255,255,255,.03);border:1px solid var(--line);border-radius:6px;padding:10px 12px}
    .kpi-box .lb{font-size:10px;color:var(--muted);margin-bottom:4px}
    .kpi-box .vl{font-size:17px;font-weight:800}
    .kpi-box .yoy{font-size:11px;margin-top:3px}
    .up{color:#ef4444;font-weight:700}.dn{color:#22c55e;font-weight:700}.flat{color:var(--muted)}

    /* Section title */
    .sec-title{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin:10px 0 6px;display:flex;align-items:center;gap:6px}
    .sec-title::after{content:'';flex:1;height:1px;background:var(--line)}

    /* Tables */
    .tbl{width:100%;border-collapse:collapse;font-size:12px}
    .tbl th{text-align:left;font-weight:600;font-size:11px;color:var(--muted);padding:5px 6px;border-bottom:1px solid var(--line);white-space:nowrap}
    .tbl th.r,.tbl td.r{text-align:right}
    .tbl td{padding:5px 6px;border-bottom:1px solid rgba(255,255,255,.04)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl-wrap{overflow-x:auto}

    .no-data{font-size:13px;color:var(--muted);padding:16px 0;text-align:center}

    /* Collapsible */
    .collapsible-btn{width:100%;background:none;border:none;color:var(--muted);font-size:12px;text-align:left;padding:6px 0;cursor:pointer;display:flex;align-items:center;gap:4px;border-top:1px solid var(--line);margin-top:8px}
    .collapsible-btn:hover{color:var(--text)}
    .collapsible-content{overflow:hidden;transition:max-height .3s ease;max-height:0}
    .collapsible-content.open{max-height:4000px}
    .chevron{transition:transform .3s;display:inline-block}
    .open + .chevron,.open .chevron{transform:rotate(180deg)}

    /* 客戶可展開列 */
    tr.cust-row{cursor:pointer;transition:background .15s}
    tr.cust-row:hover{background:rgba(194,157,102,.10)}
    tr.cust-row.open{background:rgba(194,157,102,.14)}
    tr.cust-row td{font-weight:600}
    .cust-caret{display:inline-block;width:10px;color:var(--muted);transition:transform .2s;font-size:9px}
    tr.cust-row.open .cust-caret{transform:rotate(90deg);color:var(--gold)}
    tr.detail-row{display:none}
    tr.detail-row.show{display:table-row}
    tr.detail-row > td{padding:0 0 6px 0;background:rgba(0,0,0,.25)}
    .detail-tbl{width:100%;border-collapse:collapse;font-size:11px}
    .detail-tbl th{text-align:left;font-weight:600;font-size:10px;color:var(--muted);padding:4px 6px 4px 18px}
    .detail-tbl td{padding:3px 6px 3px 18px;border-bottom:1px solid rgba(255,255,255,.04);color:var(--text)}
    .detail-tbl tr:last-child td{border-bottom:none}
    .detail-tbl .r{text-align:right}
    .month-amt{color:#60a5fa}

    /* KPI 可點擊 */
    .kpi-box.clickable{cursor:pointer;transition:border-color .2s,background .2s}
    .kpi-box.clickable:hover{border-color:var(--gold);background:rgba(194,157,102,.10)}
    .kpi-hint{font-size:9px;color:var(--muted);margin-top:2px}
    .mb-list{margin-top:6px;border-top:1px solid var(--line);padding-top:6px;display:none}
    .mb-list.show{display:block}
    .mb-row{display:flex;justify-content:space-between;font-size:11px;padding:2px 0}
    .mb-row .mb-m{color:var(--muted)}
    .mb-row.cur{color:var(--gold);font-weight:700}
    .mb-bar{height:3px;background:rgba(167,139,250,.35);border-radius:2px;margin-top:1px}

    /* Error */
    .err-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444;padding:12px;border-radius:6px;font-size:13px}
  </style>
</head>
<body>
<div id="loading" class="loading-overlay">
  <div class="spinner"></div>
  <div class="loading-text">載入每日戰報中…</div>
</div>

<div class="page">
  <div class="topbar">
    <div class="title-wrap">
      <div class="brand-logo">D</div>
      <h1>每日戰報</h1>
    </div>
    <div class="controls">
      <input type="date" id="date-input" onchange="load()">
      <button class="btn-primary" onclick="load()">查詢</button>
      <button onclick="load()">🔄 重整</button>
      <a class="btn-link" href="group_meeting.php">📊 集團月報</a>
      <span id="last-update"></span>
    </div>
  </div>

  <div id="error-area" style="display:none" class="err-box"></div>

  <div class="grand-kpi">
    <div class="gk"><div class="lbl">集團今日</div><div class="val" id="g-today">—</div></div>
    <div class="gk"><div class="lbl">集團本月</div><div class="val" id="g-month">—</div></div>
    <div class="gk"><div class="lbl">年累計</div><div class="val" id="g-ytd">—</div></div>
  </div>

  <div class="co-grid" id="co-grid"></div>
</div>

<script>
const COL = {高雅瓷:'#c29d66', 安帝嘉:'#10b981', 喜悅納:'#38bdf8'};

function fmtW(v) {
  if (v === null || v === undefined) return '—';
  if (Math.abs(v) >= 1e8) return (v/1e8).toFixed(1) + '億';
  if (Math.abs(v) >= 1e4) return (v/1e4).toFixed(1) + '萬';
  return Math.round(v).toLocaleString();
}
function yoyHtml(cur, ly) {
  if (!ly) return '';
  const p = (cur - ly) / ly * 100;
  const cls = p > 0 ? 'up' : p < 0 ? 'dn' : 'flat';
  const arrow = p > 0 ? '▲' : p < 0 ? '▼' : '—';
  return `<span class="${cls}">${arrow} ${p > 0 ? '+' : ''}${p.toFixed(1)}%</span>`;
}

function today() {
  return new Date().toISOString().slice(0,10);
}

async function load() {
  const di = document.getElementById('date-input');
  const date = di.value || today();
  di.value = date;
  document.getElementById('loading').classList.remove('hidden');
  document.getElementById('error-area').style.display = 'none';

  try {
    const r = await fetch(`api.php?action=daily-report&date=${date}`);
    const data = await r.json();
    if (!data.success) throw new Error(data.msg || '載入失敗');
    render(data);
    document.getElementById('last-update').textContent = '更新：' + new Date().toLocaleTimeString('zh-Hant');
  } catch(e) {
    const ea = document.getElementById('error-area');
    ea.textContent = '載入失敗：' + e.message;
    ea.style.display = 'block';
  } finally {
    document.getElementById('loading').classList.add('hidden');
  }
}

function render(data) {
  document.getElementById('g-today').textContent = fmtW(data.grandToday);
  document.getElementById('g-month').textContent = fmtW(data.grandMonth);
  document.getElementById('g-ytd').textContent   = fmtW(data.grandYtd);

  const grid = document.getElementById('co-grid');
  grid.innerHTML = data.companies.map(d => buildCoCard(d)).join('');
}

function buildCoCard(d) {
  const c = COL[d.co.name] || '#aaa';
  const lastTx = d.lastTxDate ? ` <span class="last-tx">最後交易 ${d.lastTxDate.slice(5).replace('-','/')}</span>` : '';

  // KPI row
  const mYoy  = yoyHtml(d.monthTotal, d.lyMonthTotal);
  const yYoy  = yoyHtml(d.ytdTotal, d.lyYtdTotal);
  const kpiHtml = `
    <div class="kpi-row">
      <div class="kpi-box">
        <div class="lb">今日</div>
        <div class="vl" style="color:${d.todayTotal>0?'#22c55e':''}">${fmtW(d.todayTotal)}</div>
      </div>
      <div class="kpi-box">
        <div class="lb">本月</div>
        <div class="vl" style="color:#60a5fa">${fmtW(d.monthTotal)}</div>
        <div class="yoy">同比 ${mYoy || '—'} <span style="font-size:10px;color:var(--muted)">去年 ${fmtW(d.lyMonthTotal)}</span></div>
      </div>
      <div class="kpi-box clickable" onclick="toggleMonthly(this)">
        <div class="lb">年累計</div>
        <div class="vl" style="color:#a78bfa">${fmtW(d.ytdTotal)}</div>
        <div class="yoy">同比 ${yYoy || '—'} <span style="font-size:10px;color:var(--muted)">去年 ${fmtW(d.lyYtdTotal)}</span></div>
        <div class="kpi-hint">▸ 點看各月</div>
        <div class="mb-list" onclick="event.stopPropagation()">${monthlyHtml(d.monthlyBreakdown)}</div>
      </div>
      <div class="kpi-box">
        <div class="lb">交易家數</div>
        <div class="vl">${d.custCount || 0} <span style="font-size:11px;font-weight:600;color:var(--muted)">家</span></div>
        <div class="yoy" style="color:var(--muted);font-size:10px">${d.displayItems.length} 筆出貨</div>
      </div>
    </div>`;

  // 當日明細：客戶彙總，點擊展開品項
  let txHtml = '';
  const custRows = d.custRows || [];
  if (custRows.length > 0) {
    const rows = custRows.map(cr => {
      const det = (cr.items || []).map(i => `
        <tr>
          <td>${i.seriesCn || '—'}</td>
          <td>${i.code}</td>
          <td class="r">${i.qty > 0 ? Math.round(i.qty) + '片' : ''}</td>
          <td class="r">${fmtW(i.amt)}</td>
        </tr>`).join('');
      return `
      <tr class="cust-row" onclick="toggleCust(this)">
        <td><span class="cust-caret">▶</span> ${cr.name}</td>
        <td class="r">${fmtW(cr.amt)}</td>
        <td class="r month-amt">${fmtW(cr.monthAmt)}</td>
        <td>${cr.sales || ''}</td>
      </tr>
      <tr class="detail-row"><td colspan="4">
        <table class="detail-tbl">
          <thead><tr><th>系列</th><th>品號</th><th class="r">片數</th><th class="r">金額</th></tr></thead>
          <tbody>${det}</tbody>
        </table>
      </td></tr>`;
    }).join('');
    txHtml = `
      <div class="sec-title">📋 ${d.displayLabel} 明細 <span style="font-size:10px;font-weight:400;text-transform:none">點客戶看品項</span></div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr>
            <th>客戶</th><th class="r">當日</th><th class="r">當月累積</th><th>業務</th>
          </tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>`;
  } else {
    txHtml = `<div class="no-data">今日暫無出貨資料</div>`;
  }

  // Series ranking
  let seriesHtml = '';
  if (d.seriesRanking.length > 0) {
    const rows = d.seriesRanking.map((s,i) => `
      <tr>
        <td style="color:var(--muted)">${i+1}</td>
        <td><strong>${s.series}</strong></td>
        <td style="color:var(--muted);font-size:11px">${s.skus.map(k=>k.sku).join(' ')}</td>
        <td class="r">${s.pings.toFixed(1)} 坪</td>
        <td class="r" style="color:var(--muted)">${s.pct}%</td>
        <td class="r">${fmtW(s.amount)}</td>
      </tr>`).join('');
    seriesHtml = `
      <button class="collapsible-btn" onclick="toggleCollapse(this)">
        🏆 熱銷系列 Top${d.seriesRanking.length} <span class="chevron" style="transform:rotate(180deg)">▾</span>
      </button>
      <div class="collapsible-content open">
        <div class="tbl-wrap" style="margin-top:8px">
          <table class="tbl">
            <thead><tr>
              <th>#</th><th>系列</th><th>品號</th>
              <th class="r">坪數</th><th class="r">佔比</th><th class="r">金額</th>
            </tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`;
  }

  // Sales rep
  let salesHtml = '';
  if (d.salesRanking.length > 0) {
    const rows = d.salesRanking.map(s => `
      <tr>
        <td>${s.name}</td>
        <td class="r">${fmtW(s.today)}</td>
        <td class="r">${fmtW(s.month)}</td>
      </tr>`).join('');
    salesHtml = `
      <button class="collapsible-btn" onclick="toggleCollapse(this)">
        👤 業務統計 <span class="chevron" style="transform:rotate(180deg)">▾</span>
      </button>
      <div class="collapsible-content open">
        <div class="tbl-wrap" style="margin-top:8px">
          <table class="tbl">
            <thead><tr><th>業務</th><th class="r">今日</th><th class="r">本月</th></tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      </div>`;
  }

  return `
    <div class="co-card">
      <div class="co-header" style="background:${c}20;border-bottom:2px solid ${c}">
        <span style="color:${c}">${d.co.name}</span>${lastTx}
      </div>
      <div class="co-body">
        ${kpiHtml}
        ${txHtml}
        ${seriesHtml}
        ${salesHtml}
      </div>
    </div>`;
}

function toggleCollapse(btn) {
  const content = btn.nextElementSibling;
  const chevron = btn.querySelector('.chevron');
  const open = content.classList.toggle('open');
  chevron.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
}

// 點客戶列展開／收合該客戶的品項明細
function toggleCust(tr) {
  const open = tr.classList.toggle('open');
  const detail = tr.nextElementSibling;
  if (detail && detail.classList.contains('detail-row')) {
    detail.classList.toggle('show', open);
  }
}

// 年累計 KPI 點開看 1 月至今各月金額
function toggleMonthly(box) {
  box.querySelector('.mb-list').classList.toggle('show');
  const hint = box.querySelector('.kpi-hint');
  const shown = box.querySelector('.mb-list').classList.contains('show');
  hint.textContent = shown ? '▾ 收合各月' : '▸ 點看各月';
}

function monthlyHtml(mb) {
  if (!mb || !mb.length) return '<div class="mb-row"><span class="mb-m">無資料</span></div>';
  const max = Math.max(...mb.map(x => x.amt), 1);
  const cur = mb.length;
  return mb.map(x => `
    <div class="mb-row ${x.m === cur ? 'cur' : ''}">
      <span class="mb-m">${x.m} 月</span><span>${fmtW(x.amt)}</span>
    </div>
    <div class="mb-bar" style="width:${Math.max(2, x.amt / max * 100)}%"></div>`).join('');
}

// Init
document.getElementById('date-input').value = today();
load();
</script>
</body>
</html>
