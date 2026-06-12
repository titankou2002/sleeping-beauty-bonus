<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>高雅瓷月報表</title>
  <style>
    :root {
      --bg: #0a0a0a;
      --paper: #111111;
      --grid: rgba(194,157,102,0.28);
      --line: rgba(255,255,255,0.10);
      --soft: rgba(255,255,255,0.03);
      --text: #f6f1e6;
      --muted: #a9a39a;
      --accent: #c29d66;
      --accent-strong: #f0cb84;
      --blue: #60a5fa;
      --green: #22c55e;
      --orange: #f59e0b;
      --red: #ef4444;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Noto Sans TC", "PingFang TC", sans-serif;
      background: radial-gradient(circle at top, rgba(194,157,102,0.10), transparent 30%), var(--bg);
      color: var(--text);
    }
    .page {
      max-width: 1480px;
      margin: 0 auto;
      padding: 24px;
    }
    .topbar {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }
    .title-wrap h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 900;
      letter-spacing: 0.02em;
    }
    .controls {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }
    select, button, a.btn-link {
      height: 42px;
      border: 1px solid rgba(194,157,102,0.45);
      background: rgba(255,255,255,0.04);
      color: var(--text);
      font-size: 14px;
      font-weight: 700;
      padding: 0 14px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    button {
      cursor: pointer;
      background: linear-gradient(180deg, rgba(194,157,102,0.2), rgba(194,157,102,0.1));
    }
    .sheet {
      background: var(--paper);
      border: 1px solid var(--grid);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 16px 40px rgba(0,0,0,0.25);
      margin-bottom: 18px;
    }
    .sheet-title {
      padding: 10px 14px;
      font-size: 22px;
      font-weight: 900;
      border-bottom: 1px solid var(--grid);
      background: linear-gradient(180deg, rgba(194,157,102,0.14), rgba(194,157,102,0.05));
    }
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .kpi-cell {
      border-right: 1px solid var(--line);
      border-bottom: 1px solid var(--line);
      min-height: 110px;
      padding: 12px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .kpi-cell:nth-child(4n) { border-right: 0; }
    .kpi-label {
      color: var(--muted);
      font-size: 13px;
      font-weight: 700;
    }
    .kpi-value {
      font-size: 36px;
      font-weight: 900;
      line-height: 1;
    }
    .kpi-sub {
      font-size: 13px;
      color: var(--muted);
      font-weight: 700;
    }
    .red { color: var(--red); }
    .yellow { background: linear-gradient(180deg, rgba(194,157,102,0.24), rgba(194,157,102,0.10)); }
    .soft { background: rgba(255,255,255,0.02); }
    .table-wrap {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid var(--line);
      padding: 8px 10px;
      font-size: 13px;
      vertical-align: top;
    }
    th {
      background: rgba(194,157,102,0.12);
      text-align: center;
      font-weight: 900;
    }
    td.num { text-align: right; font-variant-numeric: tabular-nums; }
    td.center { text-align: center; }
    .section-pad { padding: 12px; }
    .stack {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .mini-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 0;
      border-top: 1px solid var(--line);
      border-left: 1px solid var(--line);
    }
    .mini-card {
      border-right: 1px solid var(--line);
      border-bottom: 1px solid var(--line);
      padding: 12px;
      background: rgba(255,255,255,0.03);
    }
    .mini-card h3 {
      margin: 0 0 8px;
      font-size: 13px;
      color: var(--muted);
    }
    .mini-card .v {
      font-size: 28px;
      font-weight: 900;
    }
    .health-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
      padding: 12px;
    }
    .health-card {
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 16px;
      background: rgba(255,255,255,0.025);
      min-height: 132px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .health-card .k {
      color: var(--muted);
      font-size: 12px;
      font-weight: 700;
    }
    .health-card .n {
      font-size: 42px;
      font-weight: 900;
      line-height: 1;
    }
    .health-card .d {
      font-size: 12px;
      color: var(--muted);
    }
    .health-card.is-danger .n {
      color: var(--red);
    }
    .rank-board {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .rank-row {
      display: grid;
      grid-template-columns: 1fr 1.4fr 84px;
      gap: 12px;
      align-items: center;
    }
    .rank-name {
      font-size: 15px;
      font-weight: 800;
    }
    .rank-sub {
      margin-top: 4px;
      font-size: 12px;
      color: var(--muted);
    }
    .rank-track {
      height: 14px;
      border-radius: 999px;
      background: rgba(255,255,255,0.08);
      overflow: hidden;
      position: relative;
    }
    .rank-fill {
      height: 100%;
      border-radius: 999px;
      background: linear-gradient(90deg, rgba(194,157,102,0.65), var(--accent-strong));
    }
    .rank-val {
      text-align: right;
      font-size: 15px;
      font-weight: 900;
      color: var(--accent-strong);
    }
    .item-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .item-row {
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.03);
      padding: 10px 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .item-main { font-weight: 800; }
    .item-sub { color: var(--muted); font-size: 12px; margin-top: 4px; }
    .pill {
      display: inline-block;
      padding: 3px 8px;
      border: 1px solid rgba(194,157,102,0.35);
      background: rgba(194,157,102,0.10);
      font-size: 11px;
      font-weight: 800;
    }
    .chart-grid {
      display: grid;
      grid-template-columns: 1.3fr 1fr;
      gap: 16px;
      padding: 12px;
    }
    .analysis-grid {
      display: grid;
      grid-template-columns: 1fr 360px;
      gap: 16px;
      padding: 12px;
    }
    .chart-card {
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 14px;
      background: rgba(255,255,255,0.025);
    }
    .bar-chart {
      display: flex;
      align-items: flex-end;
      gap: 10px;
      min-height: 220px;
      padding-top: 18px;
      position: relative;
      padding-left: 40px;
    }
    .bar-axis {
      position: absolute;
      left: 0;
      top: 18px;
      bottom: 28px;
      width: 36px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: flex-end;
      color: var(--muted);
      font-size: 11px;
      font-weight: 700;
    }
    .bar-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }
    .bar-pair {
      width: 100%;
      height: 180px;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      gap: 6px;
    }
    .bar {
      width: calc(50% - 3px);
      border-radius: 10px 10px 0 0;
      min-height: 6px;
    }
    .bar.prev { background: rgba(255,255,255,0.18); }
    .bar.curr { background: linear-gradient(180deg, var(--accent-strong), rgba(194,157,102,0.35)); }
    .bar-label {
      color: var(--muted);
      font-size: 12px;
      font-weight: 700;
    }
    .bar-val {
      font-size: 11px;
      color: var(--text);
    }
    .chart-legend {
      display: flex;
      gap: 14px;
      align-items: center;
      margin-bottom: 12px;
      color: var(--muted);
      font-size: 12px;
      font-weight: 700;
    }
    .legend-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .legend-swatch {
      width: 14px;
      height: 14px;
      border-radius: 4px;
    }
    .product-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
      padding: 12px;
    }
    .product-card {
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 14px;
      background: rgba(255,255,255,0.03);
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .product-hero {
      width: 100%;
      height: 160px;
      border-radius: 14px;
      overflow: hidden;
      background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
      border: 1px solid rgba(255,255,255,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .product-thumb {
      width: 100%;
      height: 100%;
      border-radius: 12px;
      object-fit: contain;
    }
    .product-body {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .product-name {
      font-size: 16px;
      font-weight: 900;
      color: var(--accent-strong);
      margin: 0 0 6px;
    }
    .product-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 10px;
    }
    .product-stats {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px;
    }
    .stat-box {
      padding: 8px 10px;
      border-radius: 10px;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.05);
    }
    .stat-box .t {
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 4px;
    }
    .stat-box .v {
      font-size: 15px;
      font-weight: 800;
    }
    .series-lines {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .series-line {
      display: grid;
      grid-template-columns: 110px 1fr 64px 74px 60px;
      gap: 8px;
      font-size: 12px;
    }
    .series-sku { font-weight: 700; color: #0f3f88; }
    .hint {
      color: var(--muted);
      font-size: 12px;
    }
    .donut-wrap {
      display: flex;
      gap: 16px;
      align-items: center;
    }
    .donut {
      width: 180px;
      height: 180px;
      border-radius: 50%;
      flex: 0 0 180px;
      position: relative;
      background: conic-gradient(var(--accent) 0deg, rgba(255,255,255,0.08) 0deg);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
    }
    .donut::after {
      content: "";
      position: absolute;
      inset: 24px;
      border-radius: 50%;
      background: var(--paper);
      border: 1px solid rgba(255,255,255,0.06);
    }
    .donut-center {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1;
      text-align: center;
      pointer-events: none;
    }
    .donut-center .big {
      font-size: 22px;
      font-weight: 900;
      color: var(--accent-strong);
    }
    .legend {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .legend-row {
      display: grid;
      grid-template-columns: 14px 1fr auto auto;
      gap: 10px;
      align-items: center;
      font-size: 13px;
    }
    .legend-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }
    .series-card-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
      padding: 12px;
    }
    .series-card {
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 16px;
      background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
    }
    .series-head {
      display: grid;
      grid-template-columns: 72px 1.2fr 1fr 1fr;
      gap: 14px;
      align-items: center;
      margin-bottom: 12px;
    }
    .series-rank {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      border: 1px solid rgba(194,157,102,0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      font-weight: 900;
      color: var(--accent-strong);
      background: rgba(194,157,102,0.08);
    }
    .series-title {
      font-size: 18px;
      font-weight: 900;
      margin-bottom: 4px;
    }
    .series-meta {
      color: var(--muted);
      font-size: 12px;
    }
    .series-kpis {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 10px;
    }
    .series-kpi {
      padding: 10px 12px;
      border-radius: 12px;
      background: rgba(255,255,255,0.025);
      border: 1px solid rgba(255,255,255,0.05);
    }
    .series-kpi .t {
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 4px;
    }
    .series-kpi .v {
      font-size: 18px;
      font-weight: 900;
    }
    @media (max-width: 980px) {
      .kpi-grid, .mini-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .health-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .analysis-grid { grid-template-columns: 1fr; }
      .product-grid { grid-template-columns: 1fr; }
      .series-head { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .page { padding: 12px; }
      .kpi-grid, .mini-grid, .health-grid { grid-template-columns: 1fr; }
      .kpi-cell:nth-child(4n) { border-right: 1px solid var(--line); }
      .series-line, .product-grid, .chart-grid, .analysis-grid { grid-template-columns: 1fr; }
      .donut-wrap { flex-direction: column; align-items: flex-start; }
      .rank-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div class="title-wrap">
        <h1>高雅瓷月報表</h1>
      </div>
      <div class="controls">
        <select id="year"></select>
        <select id="month"></select>
        <button onclick="loadMeeting()">載入會議報表</button>
        <a class="btn-link" href="index.php">回戰情室</a>
      </div>
    </div>

    <div id="app"></div>
  </div>

  <script>
    const API_BASE = 'api.php';
    const now = new Date();
    const defaultMeetingDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const yearSel = document.getElementById('year');
    const monthSel = document.getElementById('month');
    for (let y = now.getFullYear(); y >= 2024; y--) {
      yearSel.innerHTML += `<option value="${y}">${y} 年</option>`;
    }
    for (let m = 1; m <= 12; m++) {
      monthSel.innerHTML += `<option value="${m}">${m} 月</option>`;
    }
    yearSel.value = defaultMeetingDate.getFullYear();
    monthSel.value = defaultMeetingDate.getMonth() + 1;

    function truncNum(n) {
      n = Number(n || 0);
      return n < 0 ? Math.ceil(n) : Math.floor(n);
    }
    function fmtWan(n) { return truncNum((n || 0) / 10000) + '萬'; }
    function fmtInt(n) { return String(truncNum(n || 0)); }
    function fmtPct(n) { return truncNum(n || 0) + '%'; }
    function fmtDate(s) { return s || '—'; }
    function driveUrlToDirect(url) {
      const s = String(url || '');
      const m = s.match(/\/file\/d\/([^\/]+)/);
      if (m) return 'https://drive.google.com/thumbnail?id=' + m[1] + '&sz=w200';
      return s;
    }
    function escapeHtml(s) {
      return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }
    function pctRaw(n) {
      return Math.max(0, Number(n || 0));
    }
    function palette(i) {
      const colors = ['#d8b273', '#34d399', '#60a5fa', '#f59e0b', '#ef4444', '#a78bfa', '#22c55e', '#f97316'];
      return colors[i % colors.length];
    }

    function apiGet(action, params) {
      let url = API_BASE + '?action=' + action;
      Object.keys(params || {}).forEach(k => {
        url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
      });
      return fetch(url).then(r => r.json());
    }

    function buildKpiSheet(d) {
      const s = d.summary || {};
      return `
        <section class="sheet">
          <div class="sheet-title">高雅瓷-${d.label} 會議總覽</div>
          <div class="kpi-grid">
            <div class="kpi-cell soft">
              <div class="kpi-label">本月業績</div>
              <div class="kpi-value red">${fmtWan(s.sales)}</div>
              <div class="kpi-sub">本月銷售金額</div>
            </div>
            <div class="kpi-cell soft">
              <div class="kpi-label">去年同期</div>
              <div class="kpi-value">${fmtWan(s.salesYoyBase)}</div>
              <div class="kpi-sub">YOY ${fmtPct(s.salesYoyPct)}</div>
            </div>
            <div class="kpi-cell">
              <div class="kpi-label">本月坪數</div>
              <div class="kpi-value">${fmtInt(s.pings)}</div>
              <div class="kpi-sub">坪</div>
            </div>
            <div class="kpi-cell">
              <div class="kpi-label">交易筆數</div>
              <div class="kpi-value">${fmtInt(s.txCount)}</div>
              <div class="kpi-sub">筆</div>
            </div>
          </div>
        </section>
      `;
    }

    function buildMonthCompareSheet(d) {
      const rows = d.monthCompare || [];
      const visibleRows = rows.filter(r => r.month <= d.month);
      const max = Math.max(1, ...visibleRows.flatMap(r => [r.previous || 0, r.current || 0]));
      return `
        <section class="sheet">
          <div class="sheet-title">年度月銷比較</div>
          <div class="chart-grid">
            <div class="chart-card">
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>月份</th>
                      <th>${d.year - 1}</th>
                      <th>${d.year}</th>
                      <th>差額</th>
                      <th>YOY</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${visibleRows.map(r => `
                      <tr>
                        <td class="center">${r.month}月</td>
                        <td class="num">${fmtWan(r.previous)}</td>
                        <td class="num">${fmtWan(r.current)}</td>
                        <td class="num">${fmtWan((r.current || 0) - (r.previous || 0))}</td>
                        <td class="num">${fmtPct(r.yoyPct)}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
            <div class="chart-card">
              <div class="chart-legend">
                <span class="legend-chip"><span class="legend-swatch" style="background:rgba(255,255,255,0.18)"></span>${d.year - 1}</span>
                <span class="legend-chip"><span class="legend-swatch" style="background:linear-gradient(180deg, var(--accent-strong), rgba(194,157,102,0.35))"></span>${d.year}</span>
              </div>
              <div class="bar-chart">
                <div class="bar-axis">
                  <span>100%</span>
                  <span>75%</span>
                  <span>50%</span>
                  <span>25%</span>
                  <span>0%</span>
                </div>
                ${visibleRows.map(r => `
                  <div class="bar-col">
                    <div class="bar-pair">
                      <div class="bar prev" style="height:${Math.max(8, Math.round((r.previous || 0) / max * 100))}%"></div>
                      <div class="bar curr" style="height:${Math.max(8, Math.round((r.current || 0) / max * 100))}%"></div>
                    </div>
                    <div class="bar-label">${r.month}月</div>
                    <div class="bar-val">${fmtWan(r.current)}</div>
                  </div>
                `).join('')}
              </div>
            </div>
          </div>
        </section>
      `;
    }

    function buildListSheet(title, subtitle, rows, renderRow) {
      return `
        <section class="sheet">
          <div class="sheet-title">${title}</div>
          <div class="section-pad">
            <div class="hint" style="margin-bottom:12px">${subtitle}</div>
            <div class="item-list">
              ${(rows || []).map(renderRow).join('') || '<div class="hint">本期尚無資料</div>'}
            </div>
          </div>
        </section>
      `;
    }

    function buildRankBoard(rows, valueKey, valueFmt, subBuilder) {
      const max = Math.max(1, ...(rows || []).map(r => Number(r[valueKey] || 0)));
      return `
        <div class="rank-board">
          ${(rows || []).map((r, idx) => {
            const pct = Math.max(6, Math.round((Number(r[valueKey] || 0) / max) * 100));
            return `
              <div class="rank-row">
                <div>
                  <div class="rank-name">${escapeHtml(r.name)}</div>
                  <div class="rank-sub">${subBuilder ? subBuilder(r, idx) : ''}</div>
                </div>
                <div class="rank-track"><div class="rank-fill" style="width:${pct}%"></div></div>
                <div class="rank-val">${valueFmt(r[valueKey] || 0)}</div>
              </div>
            `;
          }).join('') || '<div class="hint">本期尚無資料</div>'}
        </div>
      `;
    }

    function buildSeriesSheet(d) {
      const rows = d.seriesRanking || [];
      return `
        <section class="sheet">
          <div class="sheet-title">${d.year}.${d.month} 熱銷系列分析</div>
          <div class="series-card-list">
            ${rows.map((r, idx) => `
              <div class="series-card">
                <div class="series-head">
                  <div class="series-rank">${idx + 1}</div>
                  <div>
                    <div class="series-title">${escapeHtml(r.seriesCn || r.series || '未分類系列')}</div>
                    <div class="series-meta">${escapeHtml(r.brand || '未分類廠牌')} / ${escapeHtml(r.series || '—')}</div>
                  </div>
                  <div class="series-kpis">
                    <div class="series-kpi"><div class="t">總坪數</div><div class="v">${fmtInt(r.totalPings)}</div></div>
                    <div class="series-kpi"><div class="t">銷售金額</div><div class="v">${fmtWan(r.totalAmount)}</div></div>
                    <div class="series-kpi"><div class="t">本月佔比</div><div class="v">${fmtPct(r.sharePct)}</div></div>
                  </div>
                </div>
                <div class="table-wrap">
                  <table>
                    <thead>
                      <tr><th>SKU</th><th>品名 / 尺寸</th><th>坪數</th><th>金額</th><th>系列占比</th></tr>
                    </thead>
                    <tbody>
                      ${(r.items || []).slice(0, 6).map(item => `
                        <tr>
                          <td class="series-sku">${escapeHtml(item.sku)}</td>
                          <td>${escapeHtml(item.name || '—')}</td>
                          <td class="num">${fmtInt(item.pings)}</td>
                          <td class="num">${fmtWan(item.amount)}</td>
                          <td class="num">${fmtPct((r.totalAmount || 0) > 0 ? (item.amount || 0) / r.totalAmount * 100 : 0)}</td>
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>
              </div>
            `).join('')}
          </div>
        </section>
      `;
    }

    function buildDonutCard(title, rows, total, kind) {
      const list = (rows || []).slice(0, 6);
      const safeTotal = Math.max(1, Number(total || 0));
      let cursor = 0;
      const gradient = list.map((row, idx) => {
        const pct = (Number(row.amount || 0) / safeTotal) * 100;
        const color = palette(idx);
        const start = cursor;
        cursor += pct;
        return `${color} ${start}% ${cursor}%`;
      }).join(', ');
      return `
        <div class="chart-card">
          <div class="hint" style="margin-bottom:12px">${title}</div>
          <div class="donut-wrap">
            <div class="donut" style="background:conic-gradient(${gradient || 'rgba(255,255,255,0.08) 0 100%'})">
              <div class="donut-center">
                <div class="big">${fmtWan(total)}</div>
                <div class="hint">${kind}</div>
              </div>
            </div>
            <div class="legend">
              ${list.map((row, idx) => `
                <div class="legend-row">
                  <span class="legend-dot" style="background:${palette(idx)}"></span>
                  <span>${escapeHtml(row.name)}</span>
                  <span>${fmtWan(row.amount)}</span>
                  <span>${fmtPct(row.sharePct)}</span>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      `;
    }

    function buildCategorySheet(d) {
      const rows = d.categoryRanking || [];
      const sizeRows = d.sizeRanking || [];
      return `
        <section class="sheet">
          <div class="sheet-title">產品大類與尺寸分析</div>
          <div class="analysis-grid">
            <div class="chart-card">
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>產品大類</th>
                      <th>銷售金額</th>
                      <th>金額佔比</th>
                      <th>銷售坪數</th>
                      <th>交易筆數</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${rows.map(r => `
                      <tr>
                        <td>${escapeHtml(r.name)}</td>
                        <td class="num">${fmtWan(r.amount)}</td>
                        <td class="num">${fmtPct(r.sharePct)}</td>
                        <td class="num">${fmtInt(r.pings)}</td>
                        <td class="num">${fmtInt(r.count)}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
            ${buildDonutCard('產品大類占比', rows, (d.summary || {}).sales || 0, '依銷售金額')}
          </div>
          <div class="analysis-grid">
            <div class="chart-card">
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>尺寸</th>
                      <th>銷售金額</th>
                      <th>金額佔比</th>
                      <th>銷售坪數</th>
                      <th>交易筆數</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${sizeRows.map(r => `
                      <tr>
                        <td>${escapeHtml(r.name)}</td>
                        <td class="num">${fmtWan(r.amount)}</td>
                        <td class="num">${fmtPct(r.sharePct)}</td>
                        <td class="num">${fmtInt(r.pings)}</td>
                        <td class="num">${fmtInt(r.count)}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
            ${buildDonutCard('尺寸占比', sizeRows, (d.summary || {}).sales || 0, '依銷售金額')}
          </div>
        </section>
      `;
    }

    function buildContractSheet(d) {
      const s = (d.contracts || {}).summary || {};
      const health = (d.contracts || {}).healthCounts || [];
      const risk = (d.contracts || {}).topRisk || [];
      const notes = (d.contracts || {}).notes || [];
      const healthMap = {};
      health.forEach(row => { healthMap[row.name] = row.count; });
      return `
        <section class="sheet">
          <div class="sheet-title">合約狀況</div>
          <div class="mini-grid">
            <div class="mini-card"><h3>合約總數</h3><div class="v">${fmtInt(s.active)}</div></div>
            <div class="mini-card"><h3>45 天內到期</h3><div class="v">${fmtInt(s.expiringSoon)}</div></div>
            <div class="mini-card"><h3>逾期 / 嚴重</h3><div class="v red">${fmtInt(s.overdue)}</div></div>
            <div class="mini-card"><h3>合約餘額</h3><div class="v">${fmtWan(s.balance)}</div></div>
          </div>
          <div class="health-grid">
            <div class="health-card"><div class="k">正常</div><div class="n">${fmtInt(healthMap['正常'] || 0)}</div><div class="d">穩定履約</div></div>
            <div class="health-card is-danger"><div class="k">逾期</div><div class="n">${fmtInt(healthMap['逾期'] || 0)}</div><div class="d">需追蹤回收</div></div>
            <div class="health-card is-danger"><div class="k">嚴重</div><div class="n">${fmtInt(healthMap['嚴重'] || 0)}</div><div class="d">優先處理</div></div>
            <div class="health-card"><div class="k">待續</div><div class="n">${fmtInt(healthMap['待續'] || 0)}</div><div class="d">應提早續談</div></div>
            <div class="health-card"><div class="k">已續</div><div class="n">${fmtInt(healthMap['已續'] || 0)}</div><div class="d">本期已完成</div></div>
            <div class="health-card"><div class="k">其它未續約</div><div class="n">${fmtInt(healthMap['其它未續約'] || 0)}</div><div class="d">另列說明</div></div>
            <div class="health-card"><div class="k">未分類</div><div class="n">${fmtInt(healthMap['未分類'] || 0)}</div><div class="d">待補資料</div></div>
          </div>
          <div class="section-pad stack">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>其它沖完未續約說明</th><th>筆數</th></tr>
                </thead>
                <tbody>
                  ${notes.map(r => `<tr><td>${escapeHtml(r.name)}</td><td class="num">${fmtInt(r.count)}</td></tr>`).join('') || '<tr><td colspan="2" class="center">本期無額外說明</td></tr>'}
                </tbody>
              </table>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>客戶</th>
                    <th>健康度</th>
                    <th>餘額</th>
                    <th>業務</th>
                    <th>最後票期</th>
                  </tr>
                </thead>
                <tbody>
                  ${risk.map(r => `
                    <tr>
                      <td>${escapeHtml(r.customer)}</td>
                      <td class="center">${escapeHtml(r.health)}</td>
                      <td class="num">${fmtWan(r.balance)}</td>
                      <td class="center">${escapeHtml(r.sales || '—')}</td>
                      <td class="center">${fmtDate(r.lastDue)}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </section>
      `;
    }

    function buildFieldSheet(d) {
      const f = d.fieldActivity || {};
      const s = f.summary || {};
      const under = f.underVisitedCustomers || [];
      const reps = f.repEfficiency || [];
      return `
        <section class="sheet">
          <div class="sheet-title">外勤與客戶經營</div>
          <div class="mini-grid">
            <div class="mini-card"><h3>本期拜訪次數</h3><div class="v">${fmtInt(s.totalVisits)}</div></div>
            <div class="mini-card"><h3>拜訪客戶數</h3><div class="v">${fmtInt(s.visitedCustomers)}</div></div>
            <div class="mini-card"><h3>外勤公里數</h3><div class="v">${fmtInt(s.totalKm)}</div></div>
            <div class="mini-card"><h3>每次拜訪產值</h3><div class="v">${fmtWan(s.salesPerVisit)}</div></div>
          </div>
          <div class="section-pad stack">
            <div class="item-list">
              <div class="item-row"><div><div class="item-main">拜訪與業績關連</div><div class="item-sub">越接近 100% 代表拜訪與業績正相關越高</div></div><div class="pill">${fmtPct((s.visitSalesCorrelation || 0) * 100)}</div></div>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>很少去但有業績</th><th>業績</th><th>拜訪次數</th><th>最近拜訪</th></tr>
                </thead>
                <tbody>
                  ${under.map(r => `
                    <tr>
                      <td>${escapeHtml(r.name)}</td>
                      <td class="num">${fmtWan(r.salesAmount)}</td>
                      <td class="num">${fmtInt(r.visits)}</td>
                      <td class="center">${fmtDate(r.lastVisit)}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>業務</th><th>業績</th><th>拜訪</th><th>客戶數</th><th>公里</th><th>每公里產值</th></tr>
                </thead>
                <tbody>
                  ${reps.map(r => `
                    <tr>
                      <td>${escapeHtml(r.name)}</td>
                      <td class="num">${fmtWan(r.salesAmount)}</td>
                      <td class="num">${fmtInt(r.visits)}</td>
                      <td class="num">${fmtInt(r.customerCount)}</td>
                      <td class="num">${fmtInt(r.km)}</td>
                      <td class="num">${fmtWan(r.salesPerKm || 0)}</td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </section>
      `;
    }

    function buildTopSheets(d) {
      const projects = d.topProjects || [];
      return (
        `
          <section class="sheet">
            <div class="sheet-title">前 10 大客戶分析</div>
            <div class="section-pad">
              <div class="hint" style="margin-bottom:12px">客戶分析保留專案資訊作為輔助，不另外拆紅字。</div>
              ${buildRankBoard(d.topCustomers || [], 'amount', fmtWan, function(item, idx) {
                const linkedProject = projects[idx];
                const p = linkedProject ? ' / 參考專案 ' + linkedProject.name : '';
                return '交易 ' + fmtInt(item.count || 0) + ' 筆 / 坪數 ' + fmtInt(item.pings || 0) + p;
              })}
            </div>
          </section>
        ` +
        `
          <section class="sheet">
            <div class="sheet-title">業務銷售排行</div>
            <div class="section-pad">
              <div class="hint" style="margin-bottom:12px">主管會先看人，再看客與系列。</div>
              ${buildRankBoard(d.topSales || [], 'amount', fmtWan, function(item) {
                return '交易 ' + fmtInt(item.count || 0) + ' 筆 / 坪數 ' + fmtInt(item.pings || 0);
              })}
            </div>
          </section>
        `
      );
    }

    function buildHotProductsSheet(d) {
      const rows = d.topProductsDetailed || [];
      return `
        <section class="sheet">
          <div class="sheet-title">熱銷產品分析</div>
          <div class="section-pad">
            <div class="hint" style="margin-bottom:12px">把單片圖統一橫向呈現，搭配金額、坪數、筆數、佔比一起看。</div>
          </div>
          <div class="product-grid">
            ${rows.map(row => `
              <div class="product-card">
                <div class="product-hero">
                  <img class="product-thumb" src="${escapeHtml(driveUrlToDirect(row.imageUrl || ''))}" alt="" onerror="this.closest('.product-hero').style.display='none'">
                </div>
                <div class="product-body">
                  <div class="product-name">${escapeHtml(row.sku)}</div>
                  <div class="product-meta">
                    <span class="pill">${escapeHtml(row.series || '未分類')}</span>
                    <span class="pill">${escapeHtml(row.category || '未分類')}</span>
                    <span class="pill">${escapeHtml(row.size || '未標尺寸')}</span>
                  </div>
                  <div class="item-sub" style="margin-bottom:10px">${escapeHtml(row.name || '未命名產品')}</div>
                  <div class="product-stats">
                    <div class="stat-box"><div class="t">銷售金額</div><div class="v">${fmtWan(row.amount)}</div></div>
                    <div class="stat-box"><div class="t">銷售坪數</div><div class="v">${fmtInt(row.pings)}</div></div>
                    <div class="stat-box"><div class="t">交易筆數</div><div class="v">${fmtInt(row.count)}</div></div>
                    <div class="stat-box"><div class="t">本月佔比</div><div class="v">${fmtPct(row.sharePct)}</div></div>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>
        </section>
      `;
    }

    function render(d) {
      document.getElementById('app').innerHTML =
        buildKpiSheet(d) +
        buildMonthCompareSheet(d) +
        buildTopSheets(d) +
        buildSeriesSheet(d) +
        buildHotProductsSheet(d) +
        buildCategorySheet(d) +
        buildContractSheet(d) +
        buildFieldSheet(d);
    }

    function loadMeeting() {
      document.getElementById('app').innerHTML = '<div class="sheet"><div class="sheet-title">載入中…</div></div>';
      apiGet('meeting-report', {
        year: yearSel.value,
        month: monthSel.value
      }).then(res => {
        if (!res.success) {
          document.getElementById('app').innerHTML = '<div class="sheet"><div class="sheet-title">載入失敗</div><div class="section-pad">' + escapeHtml(res.msg || '未知錯誤') + '</div></div>';
          return;
        }
        render(res.data);
      }).catch(err => {
        document.getElementById('app').innerHTML = '<div class="sheet"><div class="sheet-title">載入失敗</div><div class="section-pad">' + escapeHtml(String(err)) + '</div></div>';
      });
    }

    loadMeeting();
  </script>
</body>
</html>
