<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>睡美人月會報表</title>
  <style>
    :root {
      --bg: #f3f3f0;
      --paper: #ffffff;
      --grid: #161616;
      --line: #212121;
      --soft: #d9d9d4;
      --text: #111;
      --muted: #555;
      --accent: #fff200;
      --pink: #f8d9dd;
      --blue: #dce9ff;
      --green: #dff4e2;
      --orange: #ffe2c7;
      --red: #d60000;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "Noto Sans TC", "PingFang TC", sans-serif;
      background: var(--bg);
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
    .title-wrap p {
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 13px;
    }
    .controls {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }
    select, button, a.btn-link {
      height: 42px;
      border: 2px solid var(--grid);
      background: #fff;
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
      background: var(--accent);
    }
    .sheet {
      background: var(--paper);
      border: 3px solid var(--grid);
      margin-bottom: 18px;
    }
    .sheet-title {
      padding: 10px 14px;
      font-size: 22px;
      font-weight: 900;
      border-bottom: 3px solid var(--grid);
      background: #efefea;
    }
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
    .kpi-cell {
      border-right: 2px solid var(--line);
      border-bottom: 2px solid var(--line);
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
    .yellow { background: var(--accent); }
    .soft { background: #f7f7f3; }
    .table-wrap {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 2px solid var(--line);
      padding: 8px 10px;
      font-size: 13px;
      vertical-align: top;
    }
    th {
      background: #ecece7;
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
      border-top: 2px solid var(--line);
      border-left: 2px solid var(--line);
    }
    .mini-card {
      border-right: 2px solid var(--line);
      border-bottom: 2px solid var(--line);
      padding: 12px;
      background: #fafaf7;
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
    .item-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .item-row {
      border: 2px solid var(--line);
      background: #fbfbf8;
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
      border: 1px solid var(--line);
      background: #fff;
      font-size: 11px;
      font-weight: 800;
    }
    .series-lines {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .series-line {
      display: grid;
      grid-template-columns: 130px 1fr 80px;
      gap: 8px;
      font-size: 12px;
    }
    .series-sku { font-weight: 700; color: #0f3f88; }
    .hint {
      color: var(--muted);
      font-size: 12px;
    }
    @media (max-width: 980px) {
      .kpi-grid, .mini-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
      .page { padding: 12px; }
      .kpi-grid, .mini-grid { grid-template-columns: 1fr; }
      .kpi-cell:nth-child(4n) { border-right: 2px solid var(--line); }
      .series-line { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div class="title-wrap">
        <h1>睡美人月會報表</h1>
        <p>先照 Excel 會議版做，後續再拆老闆輸出模式。</p>
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
    const yearSel = document.getElementById('year');
    const monthSel = document.getElementById('month');
    for (let y = now.getFullYear(); y >= 2024; y--) {
      yearSel.innerHTML += `<option value="${y}">${y} 年</option>`;
    }
    for (let m = 1; m <= 12; m++) {
      monthSel.innerHTML += `<option value="${m}">${m} 月</option>`;
    }
    yearSel.value = now.getFullYear();
    monthSel.value = now.getMonth() + 1;

    function truncNum(n) {
      n = Number(n || 0);
      return n < 0 ? Math.ceil(n) : Math.floor(n);
    }
    function fmtWan(n) { return truncNum((n || 0) / 10000) + '萬'; }
    function fmtInt(n) { return String(truncNum(n || 0)); }
    function fmtPct(n) { return truncNum(n || 0) + '%'; }
    function fmtDate(s) { return s || '—'; }
    function escapeHtml(s) {
      return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
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
            <div class="kpi-cell yellow">
              <div class="kpi-label">次月預估缺口</div>
              <div class="kpi-value">${fmtWan(s.nextMonthGoal)}</div>
              <div class="kpi-sub">相對去年同期基準</div>
            </div>
            <div class="kpi-cell soft">
              <div class="kpi-label">前 3 業務占比</div>
              <div class="kpi-value">${fmtPct(s.top3Pct)}</div>
              <div class="kpi-sub">最大客戶占比 ${fmtPct(s.topCustomerPct)}</div>
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
            <div class="kpi-cell">
              <div class="kpi-label">平均單筆</div>
              <div class="kpi-value">${fmtWan(s.avgTicket)}</div>
              <div class="kpi-sub">客單價</div>
            </div>
            <div class="kpi-cell">
              <div class="kpi-label">會議重點</div>
              <div class="kpi-value">${fmtInt((d.topCustomers || []).length)}</div>
              <div class="kpi-sub">本月重點客戶清單</div>
            </div>
          </div>
        </section>
      `;
    }

    function buildMonthCompareSheet(d) {
      const rows = d.monthCompare || [];
      return `
        <section class="sheet">
          <div class="sheet-title">年度月銷比較</div>
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
                ${rows.map(r => `
                  <tr>
                    <td class="center">${r.month}月</td>
                    <td class="num">${fmtWan(r.previous)}</td>
                    <td class="num">${fmtWan(r.current)}</td>
                    <td class="num ${r.current - r.previous >= 0 ? '' : 'red'}">${fmtWan((r.current || 0) - (r.previous || 0))}</td>
                    <td class="num ${r.yoyPct >= 0 ? '' : 'red'}">${fmtPct(r.yoyPct)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
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

    function buildSeriesSheet(d) {
      const rows = d.seriesRanking || [];
      return `
        <section class="sheet">
          <div class="sheet-title">${d.year}.${d.month} 熱銷系列排名表</div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>名次</th>
                  <th>總坪數</th>
                  <th>廠牌</th>
                  <th>系列</th>
                  <th>中文系列</th>
                  <th>明細</th>
                </tr>
              </thead>
              <tbody>
                ${rows.map((r, idx) => `
                  <tr>
                    <td class="center">${idx + 1}</td>
                    <td class="num">${fmtInt(r.totalPings)}</td>
                    <td class="center">${escapeHtml(r.brand || '—')}</td>
                    <td class="center">${escapeHtml(r.series || '—')}</td>
                    <td class="center">${escapeHtml(r.seriesCn || '—')}</td>
                    <td>
                      <div class="series-lines">
                        ${(r.items || []).slice(0, 6).map(item => `
                          <div class="series-line">
                            <div class="series-sku">${escapeHtml(item.sku)}</div>
                            <div>${escapeHtml(item.name || '—')}</div>
                            <div class="num">${fmtInt(item.pings)}</div>
                          </div>
                        `).join('')}
                      </div>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </section>
      `;
    }

    function buildCategorySheet(d) {
      const rows = d.categoryRanking || [];
      return `
        <section class="sheet">
          <div class="sheet-title">產品大類分析</div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>產品大類</th>
                  <th>銷售金額</th>
                  <th>銷售坪數</th>
                  <th>交易筆數</th>
                </tr>
              </thead>
              <tbody>
                ${rows.map(r => `
                  <tr>
                    <td>${escapeHtml(r.name)}</td>
                    <td class="num">${fmtWan(r.amount)}</td>
                    <td class="num">${fmtInt(r.pings)}</td>
                    <td class="num">${fmtInt(r.count)}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </section>
      `;
    }

    function buildContractSheet(d) {
      const s = (d.contracts || {}).summary || {};
      const health = (d.contracts || {}).healthCounts || [];
      const risk = (d.contracts || {}).topRisk || [];
      return `
        <section class="sheet">
          <div class="sheet-title">合約狀況</div>
          <div class="mini-grid">
            <div class="mini-card"><h3>合約總數</h3><div class="v">${fmtInt(s.active)}</div></div>
            <div class="mini-card"><h3>45 天內到期</h3><div class="v">${fmtInt(s.expiringSoon)}</div></div>
            <div class="mini-card"><h3>逾期 / 嚴重</h3><div class="v red">${fmtInt(s.overdue)}</div></div>
            <div class="mini-card"><h3>合約餘額</h3><div class="v">${fmtWan(s.balance)}</div></div>
          </div>
          <div class="section-pad stack">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>健康度</th><th>筆數</th></tr>
                </thead>
                <tbody>
                  ${health.map(r => `<tr><td>${escapeHtml(r.name)}</td><td class="num">${fmtInt(r.count)}</td></tr>`).join('')}
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
      return (
        buildListSheet('前 10 大客戶分析', '這裡只看客戶，不把專案混進來。', d.topCustomers || [], row => `
          <div class="item-row">
            <div>
              <div class="item-main">${escapeHtml(row.name)}</div>
              <div class="item-sub">交易 ${fmtInt(row.count || 0)} 筆 / 坪數 ${fmtInt(row.pings || 0)}</div>
            </div>
            <div class="pill">${fmtWan(row.amount)}</div>
          </div>
        `) +
        buildListSheet('業務銷售排行', '主管會先看人，再看客與系列。', d.topSales || [], row => `
          <div class="item-row">
            <div>
              <div class="item-main">${escapeHtml(row.name)}</div>
              <div class="item-sub">交易 ${fmtInt(row.count || 0)} 筆 / 坪數 ${fmtInt(row.pings || 0)}</div>
            </div>
            <div class="pill">${fmtWan(row.amount)}</div>
          </div>
        `)
      );
    }

    function render(d) {
      document.getElementById('app').innerHTML =
        buildKpiSheet(d) +
        buildMonthCompareSheet(d) +
        buildTopSheets(d) +
        buildSeriesSheet(d) +
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
