<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>高雅瓷月報表</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23dc2626'/%3E%3Ctext x='32' y='46' font-family='Arial, sans-serif' font-size='40' font-weight='900' fill='%23ffffff' text-anchor='middle'%3ET%3C/text%3E%3C/svg%3E">
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
    .title-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .brand-logo {
      width: 40px;
      height: 40px;
      flex: 0 0 40px;
      border-radius: 10px;
      background: #dc2626;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: 900;
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
    .mode-switch {
      display: inline-flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .mode-btn {
      min-width: 112px;
    }
    .mode-btn.is-active {
      background: linear-gradient(180deg, rgba(194,157,102,0.36), rgba(194,157,102,0.16));
      border-color: rgba(240,203,132,0.7);
      color: var(--accent-strong);
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
      grid-template-columns: repeat(5, minmax(0, 1fr));
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
    .kpi-pct { font-size: 16px; font-weight: 900; }
    .kpi-pct.up { color: var(--red); }
    .kpi-pct.down { color: var(--green); }
    .kpi-pct.flat { color: var(--text); }
    .kpi-pct.share { color: var(--accent-strong); }
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
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 12px;
      padding: 12px;
    }
    .health-card {
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 16px;
      background: rgba(255,255,255,0.025);
      min-height: 120px;
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
    .health-card.is-danger .k, .health-line.is-danger .status { color: var(--red); }
    .health-card.is-warn .n, .health-line.is-warn .status { color: #facc15; }
    .health-card.is-good .n, .health-line.is-good .status { color: var(--green); }
    .health-card.is-info .n, .health-line.is-info .status { color: var(--blue); }
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
      grid-template-columns: minmax(0, 1fr) 520px;
      gap: 16px;
      padding: 12px;
    }
    .chart-card {
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 14px;
      background: rgba(255,255,255,0.025);
    }
    .chart-card.wide {
      min-width: 0;
    }
    .chart-card.wider {
      grid-column: span 2;
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
    .bar.curr.up { background: linear-gradient(180deg, rgba(74,222,128,0.95), rgba(34,197,94,0.35)); }
    .bar.curr.down { background: linear-gradient(180deg, rgba(248,113,113,0.95), rgba(239,68,68,0.35)); }
    .bar-chart.triple {
      gap: 8px;
    }
    .bar-triple {
      width: 100%;
      height: 180px;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      gap: 4px;
    }
    .bar-chart.triple .bar {
      width: calc(33.333% - 3px);
    }
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
      grid-template-columns: repeat(3, minmax(0, 1fr));
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
      gap: 12px;
      min-height: 100%;
    }
    .product-hero {
      width: 100%;
      aspect-ratio: 16 / 9;
      border-radius: 14px;
      overflow: hidden;
      background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
      border: 1px solid rgba(255,255,255,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .product-hero.is-empty {
      border-style: dashed;
      color: var(--muted);
      font-size: 13px;
      font-weight: 700;
    }
    .product-thumb {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
      background: #181818;
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
    .product-note {
      min-height: 34px;
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
      gap: 24px;
      align-items: center;
      min-width: 0;
    }
    .donut {
      width: 220px;
      height: 220px;
      border-radius: 50%;
      flex: 0 0 220px;
      position: relative;
      background: conic-gradient(var(--accent) 0deg, rgba(255,255,255,0.08) 0deg);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
    }
    .donut::after {
      content: "";
      position: absolute;
      inset: 28px;
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
      min-width: 0;
    }
    .legend-row {
      display: grid;
      grid-template-columns: 14px minmax(120px, 1fr) 88px 72px;
      gap: 10px;
      align-items: center;
      font-size: 13px;
      min-width: 0;
    }
    .legend-row span:nth-child(2) {
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
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
    .expander {
      border: 1px solid var(--line);
      border-radius: 16px;
      background: rgba(255,255,255,0.02);
      overflow: hidden;
    }
    .expander + .expander {
      margin-top: 14px;
    }
    .expander summary {
      list-style: none;
      cursor: pointer;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      background: rgba(255,255,255,0.02);
      width: 100%;
    }
    .expander summary::-webkit-details-marker {
      display: none;
    }
    .expander-title {
      font-size: 16px;
      font-weight: 900;
    }
    .expander-sub {
      font-size: 12px;
      color: var(--muted);
      margin-top: 4px;
    }
    .expander-meta {
      font-size: 14px;
      font-weight: 900;
      color: var(--accent-strong);
      white-space: nowrap;
      flex-shrink: 0;
    }
    .expander-icon {
      margin-left: 8px;
      opacity: 0.8;
    }
    .expander-body {
      padding: 0 12px 12px;
    }
    .bucket-grid,
    .brand-grid,
    .top-grid,
    .sales-grid {
      display: grid;
      gap: 12px;
    }
    .bucket-grid { grid-template-columns: 1.05fr 1.75fr; }
    .brand-grid { grid-template-columns: 1.05fr 1fr 1fr; }
    .top-grid,
    .sales-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .bucket-summary,
    .simple-line,
    .brand-line,
    .customer-item-line,
    .sales-customer-line {
      display: grid;
      gap: 10px;
      align-items: center;
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 10px 12px;
      background: rgba(255,255,255,0.025);
    }
    .bucket-summary {
      grid-template-columns: minmax(84px, 110px) minmax(0, 1fr) 84px;
    }
    .bucket-title,
    .brand-title,
    .customer-line-title,
    .sales-line-title {
      font-size: 15px;
      font-weight: 900;
    }
    .bucket-meta,
    .brand-meta,
    .customer-line-sub,
    .sales-line-sub {
      color: var(--muted);
      font-size: 12px;
      margin-top: 4px;
    }
    .meta-num {
      font-size: 14px;
      font-weight: 900;
      color: var(--text);
    }
    .meta-num.share {
      color: var(--accent-strong);
    }
    .bucket-badge {
      color: var(--accent-strong);
      font-size: 22px;
      font-weight: 900;
      text-align: right;
    }
    .click-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 3px 8px;
      border-radius: 999px;
      border: 1px solid rgba(194,157,102,0.35);
      color: var(--accent-strong);
      font-size: 11px;
      font-weight: 800;
      margin-top: 6px;
    }
    .short-chip-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      padding-top: 8px;
    }
    .short-chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 10px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.03);
      font-size: 12px;
      font-weight: 700;
    }
    .short-chip strong {
      color: var(--accent-strong);
    }
    .country-brand-col {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .stack-col {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .country-brand-split {
      display: flex;
      gap: 16px;
      align-items: center;
      margin-bottom: 10px;
    }
    .country-mini-donut {
      width: 128px;
      height: 128px;
      border-radius: 50%;
      position: relative;
      flex: 0 0 128px;
      border: 1px solid var(--line);
    }
    .country-mini-donut::after {
      content: '';
      position: absolute;
      inset: 20px;
      border-radius: 50%;
      background: var(--panel);
      border: 1px solid var(--line);
    }
    .country-mini-center {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1;
      font-size: 14px;
      font-weight: 900;
      color: var(--accent-strong);
      text-align: center;
      padding: 0 18px;
      line-height: 1.2;
    }
    .country-mini-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 0;
      flex: 1;
    }
    .country-mini-row {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 92px;
      gap: 8px;
      align-items: center;
      font-size: 13px;
    }
    .country-mini-row .left {
      min-width: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: var(--muted);
    }
    .country-mini-row .right {
      text-align: right;
      font-weight: 900;
      color: var(--accent-strong);
      white-space: nowrap;
    }
    .country-brand-head {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      gap: 8px;
    }
    .country-brand-head .k {
      font-size: 18px;
      font-weight: 900;
    }
    .country-brand-head .v {
      color: var(--accent-strong);
      font-size: 18px;
      font-weight: 900;
    }
    .brand-line {
      grid-template-columns: minmax(0, 1fr) 88px;
    }
    .brand-kpis {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 6px;
      color: var(--muted);
      font-size: 12px;
    }
    .brand-kpis span strong {
      color: var(--text);
    }
    .yoy-tag {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 44px;
      padding: 4px 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 900;
      background: rgba(255,255,255,0.05);
    }
    .yoy-tag.up { color: #86efac; }
    .yoy-tag.down { color: #fca5a5; }
    .yoy-tag.flat { color: var(--muted); }
    .bar-yoy {
      margin-top: 6px;
      font-size: 11px;
      font-weight: 900;
      text-align: center;
    }
    .bar-yoy.up { color: #86efac; }
    .bar-yoy.down { color: #fca5a5; }
    .bar-yoy.flat { color: var(--muted); }
    .simple-line {
      grid-template-columns: minmax(0, 1.2fr) 92px 132px;
    }
    .simple-line .item-main {
      font-weight: 800;
      min-width: 0;
      line-height: 1.45;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .simple-line .status {
      font-weight: 900;
      text-align: center;
      white-space: nowrap;
    }
    .simple-line .metric {
      font-size: 13px;
      font-weight: 800;
      text-align: right;
      white-space: nowrap;
    }
    .simple-line.is-danger .status { color: var(--red); }
    .simple-line.is-warn .status { color: #facc15; }
    .simple-line.is-good .status { color: var(--green); }
    .simple-line.is-info .status { color: var(--blue); }
    .contract-lines .simple-line {
      grid-template-columns: minmax(0, 1.35fr) 76px 118px;
    }
    .customer-item-line {
      grid-template-columns: minmax(0, 1.2fr) 84px 84px 64px;
    }
    .sales-customer-line {
      grid-template-columns: minmax(0, 1fr) 84px 64px;
    }
    .cover-tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 3px 9px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 900;
      border: 1px solid rgba(255,255,255,0.08);
      margin-left: 6px;
    }
    .cover-core { color: #c084fc; }
    .cover-watch { color: #f87171; }
    .cover-ok { color: #86efac; }
    .cover-spread { color: #facc15; }
    .cover-breath { animation: breathe 1.8s ease-in-out infinite; }
    .advisor-slot { margin-bottom: 12px; }
    .advisor-card {
      border: 1px solid var(--line);
      border-left: 4px solid var(--accent-strong);
      border-radius: 12px;
      padding: 12px 14px;
      background: rgba(255,255,255,0.025);
    }
    .advisor-card.lv-good { border-left-color: #4ade80; }
    .advisor-card.lv-warn { border-left-color: #facc15; }
    .advisor-card.lv-danger { border-left-color: #f87171; }
    .advisor-card.lv-info { border-left-color: #60a5fa; }
    .advisor-title {
      font-size: 14px;
      font-weight: 900;
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      list-style: none;
    }
    .advisor-title::-webkit-details-marker { display: none; }
    .advisor-title::before { content: '▸'; font-size: 11px; }
    .advisor-card[open] .advisor-title::before { content: '▾'; }
    .advisor-card.lv-good .advisor-title { color: #4ade80; }
    .advisor-card.lv-warn .advisor-title { color: #facc15; }
    .advisor-card.lv-danger .advisor-title { color: #f87171; }
    .advisor-card.lv-info .advisor-title { color: #60a5fa; }
    .advisor-points {
      margin: 6px 0 0;
      padding-left: 18px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .advisor-points li {
      font-size: 13px;
      line-height: 1.6;
      color: var(--text);
    }
    .advisor-points strong { color: var(--accent-strong); }
    .advisor-loading { font-size: 12px; }
    @keyframes breathe {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: .6; transform: scale(1.03); }
    }
    .sku-item-line {
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) 84px 84px 64px;
      gap: 10px;
      align-items: center;
      padding: 10px 12px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: rgba(255,255,255,0.02);
    }
    .sku-item-line .item-main, .sku-customer-line .item-main {
      font-weight: 800;
      min-width: 0;
      line-height: 1.45;
    }
    .sku-item-line .metric, .sku-customer-line .metric {
      text-align: right;
      font-size: 13px;
      font-weight: 800;
      white-space: nowrap;
    }
    .sku-customer-line {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 84px 84px 64px;
      gap: 10px;
      align-items: center;
      padding: 8px 10px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .contract-lines {
      display: flex;
      flex-direction: column;
      gap: 8px;
      padding-top: 8px;
    }
    .health-line {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(180px, 260px);
      gap: 10px;
      padding: 10px 12px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: rgba(255,255,255,0.025);
      align-items: center;
    }
    .health-line .customer {
      font-weight: 800;
      min-width: 0;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .health-line .status,
    .health-line .elapsed {
      font-size: 13px;
      font-weight: 800;
      text-align: right;
    }
    .health-line .status-wrap {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 8px;
      min-width: 0;
    }
    @media (max-width: 980px) {
      .kpi-grid, .mini-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .health-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .analysis-grid { grid-template-columns: 1fr; }
      .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .series-head { grid-template-columns: 1fr; }
      .bucket-grid,
      .brand-grid,
      .top-grid,
      .sales-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .page { padding: 12px; }
      .kpi-grid, .mini-grid, .health-grid { grid-template-columns: 1fr; }
      .kpi-cell:nth-child(4n) { border-right: 1px solid var(--line); }
      .series-line, .chart-grid, .analysis-grid { grid-template-columns: 1fr; }
      .donut-wrap { flex-direction: column; align-items: flex-start; }
      .rank-row { grid-template-columns: 1fr; }
      .product-grid { grid-template-columns: 1fr; }
      .health-line { grid-template-columns: 1fr; }
      .bucket-summary,
      .simple-line,
      .brand-line,
      .customer-item-line,
      .sales-customer-line,
      .sku-item-line,
      .sku-customer-line,
      .country-mini-row { grid-template-columns: 1fr; }
      .country-brand-split { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="topbar">
      <div class="title-wrap">
        <div class="brand-logo">T</div>
        <h1>高雅瓷月報表</h1>
      </div>
      <div class="controls">
        <select id="year"></select>
        <select id="month"></select>
        <button onclick="loadMeeting()">載入會議報表</button>
        <a class="btn-link" style="color:var(--accent-strong);" href="group_meeting.php">集團比較表</a>
        <a class="btn-link" href="index.php">回戰情室</a>
      </div>
    </div>

    <div id="app"></div>
  </div>

  <script>
    const API_BASE = 'api.php';
    const now = new Date();
    const defaultMeetingDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    let currentMeetingTab = 'monthly';
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
    function fmtWan(n) {
      const wan = (n || 0) / 10000;
      if (Math.abs(wan) < 1) return (Math.round(wan * 10) / 10) + '萬';
      return truncNum(wan) + '萬';
    }
    function fmtInt(n) { return String(truncNum(n || 0)); }
    function fmtPct(n) {
      const v = Number(n || 0);
      if (Math.abs(v) < 1) return (Math.round(v * 10) / 10) + '%';
      return truncNum(v) + '%';
    }
    function fmtDate(s) { return s || '—'; }
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
    function pctRaw(n) {
      return Math.max(0, Number(n || 0));
    }
    function palette(i) {
      const colors = ['#d8b273', '#34d399', '#60a5fa', '#f59e0b', '#ef4444', '#a78bfa', '#22c55e', '#f97316'];
      return colors[i % colors.length];
    }

    function boldify(s) {
      return escapeHtml(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    }
    function advisorSlot(key) {
      return `<div class="advisor-slot" data-advisor-key="${key}"><div class="advisor-loading hint">💡 顧問解讀載入中…</div></div>`;
    }
    function renderAdvisorCard(section) {
      if (!section) return '<div class="hint">暫無顧問解讀</div>';
      const level = ['good', 'warn', 'danger', 'info'].includes(section.level) ? section.level : 'info';
      return `
        <details class="advisor-card lv-${level}">
          <summary class="advisor-title">💡 顧問解讀：${escapeHtml(section.title || '')}</summary>
          <ul class="advisor-points">
            ${(section.points || []).map(p => `<li>${boldify(p)}</li>`).join('')}
          </ul>
        </details>
      `;
    }
    const advisorCache = {};
    function fillAdvisorSlots(sections) {
      document.querySelectorAll('.advisor-slot').forEach(el => {
        const key = el.getAttribute('data-advisor-key');
        el.innerHTML = renderAdvisorCard(sections[key]);
      });
    }
    function loadAdvisor(year, month) {
      const cacheKey = year + '-' + month;
      if (advisorCache[cacheKey]) {
        fillAdvisorSlots(advisorCache[cacheKey]);
        return;
      }
      apiGet('ai-advisor', { year, month }).then(res => {
        if (!res.success) {
          document.querySelectorAll('.advisor-slot').forEach(el => {
            el.innerHTML = '<div class="hint">顧問解讀載入失敗：' + escapeHtml(res.msg || '未知錯誤') + '</div>';
          });
          return;
        }
        const sections = (res.data || {}).sections || {};
        advisorCache[cacheKey] = sections;
        fillAdvisorSlots(sections);
      }).catch(err => {
        document.querySelectorAll('.advisor-slot').forEach(el => {
          el.innerHTML = '<div class="hint">顧問解讀載入失敗：' + escapeHtml(String(err)) + '</div>';
        });
      });
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
          <div class="section-pad">${advisorSlot('kpi')}</div>
          <div class="kpi-grid">
            <div class="kpi-cell soft">
              <div class="kpi-label">本月業績</div>
              <div class="kpi-value red">${fmtWan(s.sales)}</div>
              <div class="kpi-sub">本月銷售金額</div>
            </div>
            <div class="kpi-cell soft">
              <div class="kpi-label">去年同期</div>
              <div class="kpi-value">${fmtWan(s.salesYoyBase)}</div>
              <div class="kpi-sub">YOY <span class="kpi-pct ${trendClass(s.salesYoyPct)}">${fmtPct(s.salesYoyPct)}</span></div>
            </div>
            <div class="kpi-cell">
              <div class="kpi-label">坪數 / 交易筆數</div>
              <div class="kpi-value">${fmtInt(s.pings)} / ${fmtInt(s.txCount)}</div>
              <div class="kpi-sub">坪 / 筆</div>
            </div>
            <div class="kpi-cell yellow">
              <div class="kpi-label">睡美人業績</div>
              <div class="kpi-value">${fmtWan(s.sleeperSales)}</div>
              <div class="kpi-sub">佔總業績 <span class="kpi-pct share">${fmtPct(s.sleeperPct)}</span></div>
            </div>
            <div class="kpi-cell">
              <div class="kpi-label">專案銷售</div>
              <div class="kpi-value">${fmtWan(s.projectSales)}</div>
              <div class="kpi-sub">佔總業績 <span class="kpi-pct share">${fmtPct(s.projectPct)}</span></div>
            </div>
          </div>
        </section>
      `;
    }

    function buildMonthCompareSheet(d) {
      const rows = d.monthCompare || [];
      const visibleRows = rows.filter(r => r.month <= d.month);
      const max = Math.max(1, ...visibleRows.flatMap(r => [r.previous || 0, r.current || 0]));
      const cumPrev = visibleRows.reduce((sum, r) => sum + Number(r.previous || 0), 0);
      const cumCurr = visibleRows.reduce((sum, r) => sum + Number(r.current || 0), 0);
      const cumDelta = cumCurr - cumPrev;
      const cumPct = cumPrev > 0 ? (cumDelta / cumPrev * 100) : 0;
      return `
        <section class="sheet">
          <div class="sheet-title">年度月銷比較</div>
          <div class="section-pad">${advisorSlot('monthlyCompare')}</div>
          <div class="section-pad">
            <div class="mini-grid" style="margin-bottom:16px">
              <div class="mini-card"><h3>同期 1~${d.month} 月去年</h3><div class="v">${fmtWan(cumPrev)}</div><div class="hint">${d.year - 1} / 1~${d.month} 月</div></div>
              <div class="mini-card"><h3>同期 1~${d.month} 月今年</h3><div class="v">${fmtWan(cumCurr)}</div><div class="hint">${d.year} / 1~${d.month} 月</div></div>
              <div class="mini-card"><h3>同期累積差額</h3><div class="v ${trendClass(cumDelta)}">${fmtWan(cumDelta)}</div><div class="hint">今年 - 去年</div></div>
              <div class="mini-card"><h3>同期累積達成</h3><div class="v ${trendClass(cumPct)}">${fmtPct(cumPct)}</div><div class="hint">對去年同期</div></div>
            </div>
          </div>
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
                        <td class="num"><span class="yoy-tag ${trendClass((r.current || 0) - (r.previous || 0))}">${fmtWan((r.current || 0) - (r.previous || 0))}</span></td>
                        <td class="num"><span class="yoy-tag ${trendClass(r.yoyPct)}">${fmtPct(r.yoyPct)}</span></td>
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
                      <div class="bar curr ${trendClass(r.yoyPct)}" style="height:${Math.max(8, Math.round((r.current || 0) / max * 100))}%"></div>
                    </div>
                    <div class="bar-label">${r.month}月</div>
                    <div class="bar-val">${fmtWan(r.current)}</div>
                    <div class="bar-yoy ${trendClass(r.yoyPct)}">${fmtPct(r.yoyPct)}</div>
                  </div>
                `).join('')}
              </div>
            </div>
          </div>
        </section>
      `;
    }

    function buildMeetingTabs() {
      return "";
    }

    function buildHealthShipmentSheet(d) {
      const s = d.summary || {};
      const buckets = d.shipmentBuckets || [];
      const donutRows = buckets.map(row => ({ name: row.name, amount: Number(row.count || 0), sharePct: Number(row.customerSharePct || 0) }));
      const salesDonutRows = buckets.map(row => ({ name: row.name, amount: Number(row.amount || 0), sharePct: Number(row.salesSharePct || 0) }));
      const totalHomes = buckets.reduce((sum, row) => sum + Number(row.count || 0), 0);
      const totalSales = buckets.reduce((sum, row) => sum + Number(row.amount || 0), 0);
      const signedRows = d.signedCustomerSalesRows || [];
      return `
        <section class="sheet">
          <div class="sheet-title">簽約健康度與出貨家數</div>
          <div class="section-pad">${advisorSlot('health')}</div>
          <div class="mini-grid">
            <div class="mini-card"><h3>每月簽約總額</h3><div class="v">${fmtWan(s.signedMonthlyTarget)}</div><div class="hint">依合約健康名單統計</div></div>
            <div class="mini-card"><h3>簽約店家實銷</h3><div class="v">${fmtWan(s.signedStoreSales)}</div><div class="hint">本月簽約店實際銷售</div></div>
            <div class="mini-card"><h3>簽約店健康度</h3><div class="v">${fmtPct(s.signedHealthPct)}</div><div class="hint">實銷 / 簽約總額</div></div>
            <div class="mini-card"><h3>追平去年達成率</h3><div class="v">${fmtPct(s.catchUpPct)}</div><div class="hint">本月 / 去年同期</div></div>
          </div>
          <details class="expander section-pad">
            <summary>
              <div>
                <div class="expander-title">簽約健康度逐月記錄</div>
                <div class="expander-sub">每月簽約總額 / 簽約店家實銷 / 健康度的歷史變化。</div>
              </div>
              <div class="expander-meta"><span class="expander-icon">⌄</span></div>
            </summary>
            <div class="expander-body">
              <div class="table-wrap" id="signed-health-history">
                <div class="hint">載入中…</div>
              </div>
            </div>
          </details>
          <div class="bucket-grid section-pad">
            <div class="stack-col">
              ${buildDonutCard('出貨家數結構', donutRows, totalHomes, '依出貨家數', fmtInt)}
              ${buildDonutCard('依銷售佔比', salesDonutRows, totalSales, '依銷售金額')}
            </div>
            <div class="chart-card wide">
              <div class="hint" style="margin-bottom:12px">家數 / 佔比 / 拜訪效率 / 單次拜訪產值</div>
              <div class="item-list">
                ${buckets.map(row => `
                  <details class="expander">
                    <summary>
                      <div>
                        <div class="bucket-title">${escapeHtml(row.name)}</div>
                        <div class="bucket-meta">家數 <span class="meta-num">${fmtInt(row.count)}</span> / 家數佔比 <span class="meta-num share">${fmtPct(row.customerSharePct)}</span> / 業績佔比 <span class="meta-num share">${fmtPct(row.salesSharePct)}</span> / 平均拜訪 <span class="meta-num">${fmtInt(row.avgVisitsPerCustomer || 0)}</span> 次 / 單次拜訪產值 <span class="meta-num">${fmtWan(row.salesPerVisit || 0)}</span></div>
                        <div class="click-badge">點家數看客戶 <span class="expander-icon">▾</span></div>
                      </div>
                      <div class="bucket-badge">${fmtWan(row.amount)}</div>
                    </summary>
                    <div class="expander-body">
                      <div class="short-chip-list">
                        ${(row.customers || []).map(c => `
                          <span class="short-chip">${escapeHtml(((c.shortName || c.name || '').slice(0, 2) || c.name || '客戶'))} <strong>${fmtWan(c.amount)}</strong> ${fmtInt(c.visits || 0)}次</span>
                        `).join('') || '<span class="hint">本級距無客戶</span>'}
                      </div>
                    </div>
                  </details>
                `).join('')}
              </div>
            </div>
          </div>
          <details class="expander section-pad">
            <summary>
              <div>
                <div class="expander-title">簽約店家實銷名單</div>
                <div class="expander-sub">目前統計 ${fmtWan(s.signedStoreSales)}，用來檢查是否有遺漏簽約店家。</div>
              </div>
              <div class="expander-meta">${fmtInt(signedRows.length)} 家<span class="expander-icon">⌄</span></div>
            </summary>
            <div class="expander-body">
              <div class="item-list">
                ${(signedRows || []).map(row => `
                  <div class="simple-line">
                    <div class="item-main">${escapeHtml(row.name)}</div>
                    <div class="metric">${fmtWan(row.amount)}</div>
                    <div class="metric">${fmtPct((s.signedStoreSales || 0) > 0 ? (row.amount || 0) / s.signedStoreSales * 100 : 0)}</div>
                  </div>
                `).join('') || '<div class="hint">本期無簽約店家實銷</div>'}
              </div>
            </div>
          </details>
        </section>
      `;
    }

    function buildBrandCountrySheet(d) {
      const brandRows = d.brandSales || [];
      const total = brandRows.reduce((sum, row) => sum + Number(row.amount || 0), 0);
      const countryBrands = d.countryBrandRanking || {};
      const countryKeys = ['義大利', '西班牙'];
      const countryShareMap = Object.fromEntries(brandRows.map(r => [r.name, Number(r.sharePct || 0)]));
      return `
        <section class="sheet">
          <div class="sheet-title">國別與廠牌分析</div>
          <div class="section-pad">${advisorSlot('brandCountry')}</div>
          <div class="chart-grid">
            ${buildDonutCard('義大利 / 西班牙', brandRows.map(r => ({...r, name: r.name || '未分類'})), total, '依銷售金額')}
            <div class="chart-card">
              <div class="hint" style="margin-bottom:12px">國別銷售排名</div>
              <div class="rank-board">
                ${brandRows.map(r => `
                  <div class="rank-row">
                    <div><div class="rank-name">${escapeHtml(r.name)}廠牌</div></div>
                    <div class="rank-track"><div class="rank-fill" style="width:${Math.max(6, Math.round(Number(r.sharePct || 0)))}%"></div></div>
                    <div class="rank-val">${fmtWan(r.amount)}/${fmtPct(r.sharePct)}</div>
                  </div>
                `).join('')}
              </div>
            </div>
          </div>
          <div class="brand-grid section-pad">
            ${countryKeys.map(country => {
              const rows = (countryBrands[country] || []).slice(0, 8);
              const countryTotal = rows.reduce((sum, row) => sum + Number(row.amount || 0), 0);
              const countryShare = countryShareMap[country] || 0;
              let cursor = 0;
              const donutGradient = rows.map((row, idx) => {
                const pct = countryTotal > 0 ? (Number(row.amount || 0) / countryTotal) * 100 : 0;
                const start = cursor;
                cursor += pct;
                return `${palette(idx)} ${start}% ${cursor}%`;
              }).join(', ');
              return `
                <div class="chart-card country-brand-col">
                  <div class="country-brand-head">
                    <div>
                      <div class="k">${country}廠牌</div>
                      <div class="country-brand-total">${fmtWan(countryTotal)}/${fmtPct(countryShare)}</div>
                    </div>
                  </div>
                  <div class="country-brand-split">
                    <div class="country-mini-donut" style="background:conic-gradient(${donutGradient || 'rgba(255,255,255,0.08) 0 100%'})">
                      <div class="country-mini-center">${fmtWan(countryTotal)}</div>
                    </div>
                    <div class="country-mini-list">
                      ${rows.slice(0, 4).map((row, idx) => `
                        <div class="country-mini-row">
                          <div class="left"><span class="legend-dot" style="background:${palette(idx)}"></span> ${escapeHtml(row.name)}</div>
                          <div class="right">${fmtPct(row.sharePct)}</div>
                        </div>
                      `).join('')}
                    </div>
                  </div>
                  ${rows.map(row => `
                    <details class="expander">
                      <summary>
                        <div>
                          <div class="brand-title">${escapeHtml(row.name)} ${fmtWan(row.amount)}/${fmtPct(row.sharePct)}</div>
                          <div class="brand-kpis">
                            <span>系列數 <strong>${fmtInt(row.seriesCount)}</strong></span>
                            <span>銷量 <strong>${fmtInt(row.pings)}</strong></span>
                          </div>
                        </div>
                        <div class="bucket-badge">${fmtWan(row.amount)}/${fmtPct(row.sharePct)}</div>
                      </summary>
                      <div class="expander-body">
                        <div class="item-list">
                          ${(row.seriesRows || []).filter(series => Number(series.amount || 0) > 0).map(series => `
                            <div class="simple-line">
                              <div class="item-main">${escapeHtml(series.name)}</div>
                              <div class="status">${fmtPct(series.sharePct)}</div>
                              <div class="metric"></div>
                            </div>
                          `).join('') || '<div class="hint">本廠牌無系列資料</div>'}
                        </div>
                      </div>
                    </details>
                  `).join('') || '<div class="hint">本期無資料</div>'}
                </div>
              `;
            }).join('')}
          </div>
        </section>
      `;
    }

    function buildThreeYearCompareSheet(d, cumulative) {
      const source = cumulative ? (d.threeYearCumulative || []) : (d.threeYearCompare || []);
      const yearMap = {};
      source.forEach(row => { yearMap[row.year] = row; });
      const months = Array.from({ length: d.month }, (_, i) => i + 1);
      const rows = months.map(month => {
        const y24 = Number((yearMap[d.year - 2]?.months || []).find(m => Number(m.month) === month)?.amount || 0);
        const y25 = Number((yearMap[d.year - 1]?.months || []).find(m => Number(m.month) === month)?.amount || 0);
        const y26 = Number((yearMap[d.year]?.months || []).find(m => Number(m.month) === month)?.amount || 0);
        const avg = (y24 + y25) / 2;
        const delta = y26 - avg;
        const growthPct = avg > 0 ? (delta / avg * 100) : 0;
        return { month, y24, y25, y26, avg, delta, growthPct };
      });
      const max = Math.max(1, ...rows.flatMap(r => [r.y24, r.y25, r.y26]));
      const totals = [d.year - 2, d.year - 1, d.year].map((year, idx) => ({
        year,
        total: Number((yearMap[year]?.months || []).reduce((sum, m) => sum + Number(m.amount || 0), 0)),
        color: palette(idx)
      }));
      return `
        <section class="sheet">
          <div class="sheet-title">近三年月銷比較</div>
          <div class="section-pad">${advisorSlot('threeYear')}</div>
          <div class="chart-grid">
            <div class="chart-card">
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>月份</th>
                      <th>${d.year - 2}</th>
                      <th>${d.year - 1}</th>
                      <th>平均月銷</th>
                      <th>${d.year}</th>
                      <th>差額</th>
                      <th>成長%</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${rows.map(r => `
                      <tr>
                        <td class="center">${r.month}月</td>
                        <td class="num">${fmtWan(r.y24)}</td>
                        <td class="num">${fmtWan(r.y25)}</td>
                        <td class="num">${fmtWan(r.avg)}</td>
                        <td class="num">${fmtWan(r.y26)}</td>
                        <td class="num"><span class="yoy-tag ${trendClass(r.delta)}">${fmtWan(r.delta)}</span></td>
                        <td class="num"><span class="yoy-tag ${trendClass(r.growthPct)}">${fmtPct(r.growthPct)}</span></td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
            <div class="chart-card">
              <div class="chart-legend">
                <span class="legend-chip"><span class="legend-swatch" style="background:${palette(0)}"></span>${d.year - 2}</span>
                <span class="legend-chip"><span class="legend-swatch" style="background:${palette(1)}"></span>${d.year - 1}</span>
                <span class="legend-chip"><span class="legend-swatch" style="background:${palette(2)}"></span>${d.year}</span>
              </div>
              <div class="bar-chart triple">
                ${rows.map(r => `
                  <div class="bar-col">
                    <div class="bar-triple">
                      <div class="bar curr" style="height:${Math.max(8, Math.round((r.y24 / max) * 100))}%; background:${palette(0)}"></div>
                      <div class="bar curr" style="height:${Math.max(8, Math.round((r.y25 / max) * 100))}%; background:${palette(1)}"></div>
                      <div class="bar curr ${trendClass(r.growthPct)}" style="height:${Math.max(8, Math.round((r.y26 / max) * 100))}%"></div>
                    </div>
                    <div class="bar-label">${r.month}月</div>
                    <div class="bar-yoy ${trendClass(r.growthPct)}">${fmtPct(r.growthPct)}</div>
                  </div>
                `).join('')}
              </div>
              <div class="mini-grid" style="margin-top:16px">
                ${totals.map(row => `
                  <div class="mini-card">
                    <h3>${row.year}</h3>
                    <div class="v" style="color:${row.color}">${fmtWan(row.total)}</div>
                    <div class="hint">年度總額</div>
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

    function trendClass(n) {
      const v = Number(n || 0);
      if (v > 0) return 'up';
      if (v < 0) return 'down';
      return 'flat';
    }

    function coverMeta(count) {
      const c = Number(count || 0);
      if (c <= 4) return { cls: 'cover-core cover-breath', text: `80% 業績來自 ${fmtInt(c)} 客` };
      if (c <= 6) return { cls: 'cover-watch cover-breath', text: `80% 業績來自 ${fmtInt(c)} 客` };
      if (c <= 10) return { cls: 'cover-ok', text: `80% 業績來自 ${fmtInt(c)} 客` };
      if (c <= 15) return { cls: 'cover-spread', text: `80% 業績來自 ${fmtInt(c)} 客` };
      return { cls: '', text: `80% 業績來自 ${fmtInt(c)} 客` };
    }

    function buildSeriesSheet(d) {
      const rows = d.seriesRanking || [];
      return `
        <section class="sheet">
          <div class="sheet-title">${d.year}.${d.month} 熱銷系列分析</div>
          <div class="section-pad">
            ${advisorSlot('series')}
            <div class="hint" style="margin-bottom:12px">點系列名稱看 SKU 明細，再點 SKU 看客戶銷售明細。</div>
          </div>
          <div class="series-card-list">
            ${rows.map((r, idx) => `
              <details class="series-card expander">
                <summary>
                  <div class="series-head" style="margin:0; width:100%;">
                    <div class="series-rank">${idx + 1}</div>
                    <div>
                      <div class="series-title">${escapeHtml(r.seriesCn || r.series || '未分類系列')} <span class="expander-icon">▾</span></div>
                      <div class="series-meta">${escapeHtml(r.brand || '未分類廠牌')} / ${escapeHtml(r.series || '—')}</div>
                    </div>
                    <div class="series-kpis">
                      <div class="series-kpi"><div class="t">總坪數</div><div class="v">${fmtInt(r.totalPings)}</div></div>
                      <div class="series-kpi"><div class="t">銷售金額</div><div class="v">${fmtWan(r.totalAmount)}</div></div>
                      <div class="series-kpi"><div class="t">本月佔比</div><div class="v">${fmtPct(r.sharePct)}</div></div>
                    </div>
                  </div>
                </summary>
                <div class="expander-body">
                  <div class="item-list">
                    ${(r.items || []).slice(0, 8).map(item => `
                      <details class="expander">
                        <summary>
                          <div class="sku-item-line" style="border:none; background:transparent; padding:0; width:100%;">
                            <div class="item-main">${escapeHtml(item.sku)} <span class="expander-icon">▾</span></div>
                            <div class="metric">${fmtInt(item.pings)}</div>
                            <div class="metric">${fmtWan(item.amount)}</div>
                            <div class="metric">${fmtPct((r.totalAmount || 0) > 0 ? (item.amount || 0) / r.totalAmount * 100 : 0)}</div>
                          </div>
                        </summary>
                        <div class="expander-body">
                          <div class="item-list">
                            ${(item.customers || []).map(c => `
                              <div class="sku-customer-line">
                                <div class="item-main">${escapeHtml(c.name)}</div>
                                <div class="metric">${fmtInt(c.pings)}</div>
                                <div class="metric">${fmtWan(c.amount)}</div>
                                <div class="metric">${fmtPct(c.sharePct || 0)}</div>
                              </div>
                            `).join('') || '<div class="hint">此 SKU 本期無客戶明細</div>'}
                          </div>
                        </div>
                      </details>
                    `).join('')}
                  </div>
                </div>
              </details>
            `).join('')}
          </div>
        </section>
      `;
    }

    function buildDonutCard(title, rows, total, kind, valueFmt) {
      const list = (rows || []).slice(0, 6);
      const safeTotal = Math.max(1, Number(total || 0));
      const formatter = valueFmt || fmtWan;
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
                <div class="big">${formatter(total)}</div>
                <div class="hint">${kind}</div>
              </div>
            </div>
            <div class="legend">
              ${list.map((row, idx) => `
                <div class="legend-row">
                  <span class="legend-dot" style="background:${palette(idx)}"></span>
                  <span>${escapeHtml(row.name)}</span>
                  <span>${formatter(row.amount)}</span>
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
          <div class="section-pad">
            ${advisorSlot('category')}
            ${buildDonutCard('產品大類占比', rows, (d.summary || {}).sales || 0, '依銷售金額')}
          </div>
          <details class="expander">
            <summary>
              <div>
                <div class="expander-title">產品大類細節</div>
                <div class="expander-sub">點開看品類金額、佔比、坪數與筆數。</div>
              </div>
              <div class="expander-meta">${rows.length} 類<span class="expander-icon">⌄</span></div>
            </summary>
            <div class="expander-body">
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
          </details>
          <div class="section-pad">
            ${buildDonutCard('尺寸占比', sizeRows, (d.summary || {}).sales || 0, '依銷售金額')}
          </div>
          <details class="expander">
            <summary>
              <div>
                <div class="expander-title">尺寸細節</div>
                <div class="expander-sub">點開看尺寸金額、佔比、坪數與筆數。</div>
              </div>
              <div class="expander-meta">${sizeRows.length} 種尺寸<span class="expander-icon">⌄</span></div>
            </summary>
            <div class="expander-body">
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
          </details>
        </section>
      `;
    }

    function buildContractSheet(d) {
      const s = (d.contracts || {}).summary || {};
      const health = (d.contracts || {}).healthCounts || [];
      const notes = (d.contracts || {}).notes || [];
      const detailGroups = (d.contracts || {}).detailGroups || {};
      const healthMap = {};
      health.forEach(row => { healthMap[row.name] = row.count; });
      const overdueSevereCount = (healthMap['逾期'] || 0) + (healthMap['嚴重'] || 0);
      const overdueSevereRows = detailGroups.overdueSevere || [];
      const pendingRows = detailGroups.pendingRenewal || [];
      const renewedRows = detailGroups.renewed || [];
      const otherOpenRows = detailGroups.otherOpen || [];
      const contractLineClass = health => {
        const text = String(health || '');
        if (text.includes('嚴重')) return 'is-danger';
        if (text.includes('逾期') || text.includes('待續')) return 'is-warn';
        if (text.includes('已續')) return 'is-info';
        return 'is-good';
      };
      const buildContractLines = rows => `
        <div class="contract-lines">
          ${rows.map(r => `
            <div class="simple-line ${contractLineClass(r.health)}">
              <div class="item-main">${escapeHtml(r.customer || '未填客戶')}</div>
              <div class="status">${escapeHtml(r.health || '—')}</div>
              <div class="metric">${escapeHtml(r.elapsed || fmtDate(r.lastDue))}</div>
            </div>
          `).join('') || '<div class="hint">本期無資料</div>'}
        </div>
      `;
      return `
        <section class="sheet">
          <div class="sheet-title">合約狀況</div>
          <div class="section-pad">${advisorSlot('contract')}${advisorSlot('inventory')}</div>
          <div class="mini-grid">
            <div class="mini-card"><h3>合約總數</h3><div class="v">${fmtInt(s.active)}</div></div>
            <div class="mini-card"><h3>45 天內到期</h3><div class="v">${fmtInt(s.expiringSoon)}</div></div>
            <div class="mini-card"><h3>合約餘額</h3><div class="v">${fmtWan(s.balance)}</div></div>
          </div>
          <div class="health-grid" style="grid-template-columns:repeat(3,minmax(0,1fr));">
            <details class="health-card is-good expander">
              <summary>
                <div>
                  <div class="k">正常</div>
                  <div class="n">${fmtInt(healthMap['正常'] || 0)}</div>
                  <div class="d">點開看正常客戶</div>
                </div>
                <div class="expander-icon">⌄</div>
              </summary>
              <div class="expander-body">${buildContractLines((detailGroups.normal || []))}</div>
            </details>
            <details class="health-card is-warn expander">
              <summary>
                <div>
                  <div class="k">逾期</div>
                  <div class="n">${fmtInt(healthMap['逾期'] || 0)}</div>
                  <div class="d">點開看客戶 / 逾期天數</div>
                </div>
                <div class="expander-icon">⌄</div>
              </summary>
              <div class="expander-body">${buildContractLines(overdueSevereRows.filter(r => String(r.health || '').includes('逾期')))}</div>
            </details>
            <details class="health-card is-danger expander">
              <summary>
                <div>
                  <div class="k">嚴重</div>
                  <div class="n">${fmtInt(healthMap['嚴重'] || 0)}</div>
                  <div class="d">點開看客戶 / 嚴重逾期</div>
                </div>
                <div class="expander-icon">⌄</div>
              </summary>
              <div class="expander-body">${buildContractLines(overdueSevereRows.filter(r => String(r.health || '').includes('嚴重')))}</div>
            </details>
            <details class="health-card is-warn expander">
              <summary>
                <div>
                  <div class="k">待續約</div>
                  <div class="n">${fmtInt(healthMap['待續'] || 0)}</div>
                  <div class="d">點開看客戶 / 待續時間</div>
                </div>
                <div class="expander-icon">⌄</div>
              </summary>
              <div class="expander-body">${buildContractLines(pendingRows)}</div>
            </details>
            <details class="health-card is-info expander">
              <summary>
                <div>
                  <div class="k">已續約</div>
                  <div class="n">${fmtInt(healthMap['已續'] || 0)}</div>
                  <div class="d">點開看已續約客戶</div>
                </div>
                <div class="expander-icon">⌄</div>
              </summary>
              <div class="expander-body">${buildContractLines(renewedRows)}</div>
            </details>
          </div>
          <details class="expander" style="margin:0 12px 12px">
            <summary>
              <div>
                <div class="expander-title">未續約 / 另列說明</div>
                <div class="expander-sub">把其它未續約與沖完未續約說明集中在這裡。</div>
              </div>
              <div class="expander-meta">${fmtInt((healthMap['其它未續約'] || 0) + notes.reduce((sum, r) => sum + Number(r.count || 0), 0))}<span class="expander-icon">⌄</span></div>
            </summary>
            <div class="expander-body">
              ${buildContractLines(otherOpenRows)}
              <div class="table-wrap" style="margin-top:12px">
                <table>
                  <thead><tr><th>其它沖完未續約說明</th><th>筆數</th></tr></thead>
                  <tbody>
                    ${notes.map(r => `<tr><td>${escapeHtml(r.name)}</td><td class="num">${fmtInt(r.count)}</td></tr>`).join('') || '<tr><td colspan="2" class="center">本期無額外說明</td></tr>'}
                  </tbody>
                </table>
              </div>
            </div>
          </details>
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
          <div class="section-pad">${advisorSlot('field')}</div>
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
            <details class="expander">
              <summary>
                <div>
                  <div class="expander-title">很少去但有業績</div>
                  <div class="expander-sub">先看異常客戶，再決定補拜訪重點。</div>
                </div>
                <div class="expander-meta">${under.length} 客</div>
              </summary>
              <div class="expander-body">
                ${buildRankBoard(under, 'salesAmount', fmtWan, function(item) {
                  return '拜訪 ' + fmtInt(item.visits) + ' 次 / 最近 ' + fmtDate(item.lastVisit);
                })}
              </div>
            </details>
            <details class="expander" open>
              <summary>
                <div>
                  <div class="expander-title">業務外勤效率</div>
                  <div class="expander-sub">先看誰有效率，再展開公里與客戶數明細。</div>
                </div>
                <div class="expander-meta">${reps.length} 人</div>
              </summary>
              <div class="expander-body">
                ${buildRankBoard(reps, 'salesAmount', fmtWan, function(item) {
                  return '拜訪 ' + fmtInt(item.visits) + ' 次 / 客戶 ' + fmtInt(item.customerCount) + ' / ' + fmtInt(item.km) + ' km / 每公里 ' + fmtWan(item.salesPerKm || 0);
                })}
              </div>
            </details>
          </div>
        </section>
      `;
    }

    function buildTopSheets(d) {
      return (
        `
          <section class="sheet">
            <div class="sheet-title">前 10 大客戶分析</div>
            <div class="section-pad">
              ${advisorSlot('topCustomers')}
              <div class="hint" style="margin-bottom:12px">按客戶名下探中文系列、SKU、片數、金額與佔比，依案名與金額排序。</div>
              <div class="top-grid">
                ${(d.topCustomers || []).map(item => `
                  <details class="expander">
                    <summary>
                      <div>
                        <div class="customer-line-title">${escapeHtml(item.name)}</div>
                        <div class="customer-line-sub">交易 ${fmtInt(item.count || 0)} 筆 / 坪數 ${fmtInt(item.pings || 0)}</div>
                      </div>
                      <div class="bucket-badge">${fmtWan(item.amount)}</div>
                    </summary>
                    <div class="expander-body">
                      <div class="item-list">
                        ${(item.items || []).map(row => `
                          <div class="customer-item-line">
                            <div class="item-main">${escapeHtml(row.seriesCn || row.sku)}</div>
                            <div class="metric">${fmtInt(row.count)}</div>
                            <div class="metric">${fmtWan(row.amount)}</div>
                            <div class="metric">${fmtPct(row.sharePct || 0)}</div>
                          </div>
                        `).join('') || '<div class="hint">此客戶本期無產品明細</div>'}
                      </div>
                    </div>
                  </details>
                `).join('')}
              </div>
            </div>
          </section>
        ` +
        `
          <section class="sheet">
            <div class="sheet-title">業務銷售排行</div>
            <div class="section-pad">
              ${advisorSlot('topSales')}
              <div class="sales-grid">
                ${(d.topSales || []).map(item => {
                  const meta = coverMeta(item.top80CustomerCount || 0);
                  return `
                    <details class="expander">
                      <summary>
                        <div>
                          <div class="sales-line-title">${escapeHtml(item.name)}</div>
                          <div class="sales-line-sub">交易 ${fmtInt(item.count || 0)} 筆 / 坪數 ${fmtInt(item.pings || 0)} / <span class="cover-tag ${meta.cls}">${meta.text}</span></div>
                        </div>
                        <div class="bucket-badge">${fmtWan(item.amount)}</div>
                      </summary>
                      <div class="expander-body">
                        <div class="item-list">
                          ${(item.customers || []).map(row => `
                            <div class="sales-customer-line ${row.isCore80 ? meta.cls : ''}">
                              <div class="item-main">${escapeHtml(row.name)}</div>
                              <div class="metric">${fmtWan(row.amount)}</div>
                              <div class="metric">${fmtPct(row.sharePct || 0)}</div>
                            </div>
                          `).join('') || '<div class="hint">此業務本期無客戶明細</div>'}
                        </div>
                      </div>
                    </details>
                  `;
                }).join('')}
              </div>
            </div>
          </section>
        `
      );
    }

    function buildHotProductsSheet(d) {
      const rows = (d.topProductsDetailed || []).slice(0, 9);
      const padded = rows.concat(Array.from({ length: Math.max(0, 9 - rows.length) }, () => null));
      return `
        <section class="sheet">
          <div class="sheet-title">熱銷產品分析</div>
          <div class="section-pad">
            <div class="hint" style="margin-bottom:12px">九宮格看前 9 名，圖片統一橫向，資訊都放在圖片下方。</div>
          </div>
          <div class="product-grid">
            ${padded.map(row => row ? `
              <div class="product-card">
                ${row.imageUrl ? `
                  <div class="product-hero">
                    <img class="product-thumb" src="${escapeHtml(driveUrlToDirect(row.imageUrl || ''))}" alt="" onerror="this.remove(); this.parentNode.classList.add('is-empty'); this.parentNode.textContent='無圖片';">
                  </div>
                ` : `
                  <div class="product-hero is-empty">無圖片</div>
                `}
                <div class="product-body">
                  <div class="product-name">${escapeHtml(row.sku)}</div>
                  <div class="product-meta">
                    <span class="pill">${escapeHtml(row.series || '未分類')}</span>
                    <span class="pill">${escapeHtml(row.category || '未分類')}</span>
                    <span class="pill">${escapeHtml(row.size || '未標尺寸')}</span>
                  </div>
                  <div class="product-stats">
                    <div class="stat-box"><div class="t">銷售金額</div><div class="v">${fmtWan(row.amount)}</div></div>
                    <div class="stat-box"><div class="t">銷售坪數</div><div class="v">${fmtInt(row.pings)}</div></div>
                    <div class="stat-box"><div class="t">交易筆數</div><div class="v">${fmtInt(row.count)}</div></div>
                    <div class="stat-box"><div class="t">本月佔比</div><div class="v">${fmtPct(row.sharePct)}</div></div>
                  </div>
                </div>
              </div>
            ` : `
              <div class="product-card">
                <div class="product-hero is-empty">無圖片</div>
                <div class="product-body">
                  <div class="product-name">—</div>
                  <div class="product-meta"><span class="pill">未補資料</span></div>
                  <div class="product-stats">
                    <div class="stat-box"><div class="t">銷售金額</div><div class="v">0萬</div></div>
                    <div class="stat-box"><div class="t">銷售坪數</div><div class="v">0</div></div>
                    <div class="stat-box"><div class="t">交易筆數</div><div class="v">0</div></div>
                    <div class="stat-box"><div class="t">本月佔比</div><div class="v">0%</div></div>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>
        </section>
      `;
    }

    function render(d) {
      const monthlyView =
        buildKpiSheet(d) +
        buildHealthShipmentSheet(d) +
        buildBrandCountrySheet(d) +
        buildMonthCompareSheet(d) +
        buildThreeYearCompareSheet(d, false) +
        buildTopSheets(d) +
        buildSeriesSheet(d) +
        buildHotProductsSheet(d) +
        buildCategorySheet(d) +
        buildContractSheet(d) +
        buildFieldSheet(d);
      document.getElementById('app').innerHTML = monthlyView;
      loadAdvisor(d.year, d.month);
      loadSignedHealthHistory();
    }

    let signedHealthHistoryCache = null;
    function loadSignedHealthHistory() {
      const el = document.getElementById('signed-health-history');
      if (!el) return;
      const render = rows => {
        if (!rows.length) { el.innerHTML = '<div class="hint">尚無歷史記錄，每月生成顧問解讀時會自動累積。</div>'; return; }
        el.innerHTML = `
          <table>
            <thead><tr><th>年月</th><th>簽約總額</th><th>簽約店家實銷</th><th>健康度</th></tr></thead>
            <tbody>
              ${rows.slice().reverse().map(r => {
                const k = r.kpi || {};
                return `<tr><td class="center">${r.year}.${r.month}</td><td class="num">${fmtWan(k.signedMonthlyTarget)}</td><td class="num">${fmtWan(k.signedStoreSales)}</td><td class="num">${fmtPct(k.signedHealthPct)}</td></tr>`;
              }).join('')}
            </tbody>
          </table>
        `;
      };
      if (signedHealthHistoryCache) { render(signedHealthHistoryCache); return; }
      apiGet('report-history', {}).then(res => {
        if (!res.success) { el.innerHTML = '<div class="hint">載入失敗：' + escapeHtml(res.msg || '未知錯誤') + '</div>'; return; }
        signedHealthHistoryCache = res.data || [];
        render(signedHealthHistoryCache);
      }).catch(err => { el.innerHTML = '<div class="hint">載入失敗：' + escapeHtml(String(err)) + '</div>'; });
    }

    function switchMeetingTab(tab) {
      currentMeetingTab = 'monthly';
      loadMeeting();
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
