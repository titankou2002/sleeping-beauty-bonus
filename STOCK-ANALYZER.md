# 股票戰情室 - 開發進度與部署說明

## 專案概述

台股/美股技術分析 Web App，部署在 cPanel (FTP)，使用 PHP 後端 + 原生 JavaScript 前端。
- 線上位置：cPanel 主機，透過 GitHub Actions 自動部署
- Repo：`titankou2002/sleeping-beauty-bonus`
- 子目錄：`stock-analyzer/`

---

## 部署架構

### 自動部署流程
```
push to main → GitHub Actions → FTP Deploy → cPanel
```
- 部署 workflow：`.github/workflows/deploy.yml`
- 使用 `SamKirkland/FTP-Deploy-Action@v4.3.5`
- 需要的 GitHub Secrets：
  - `FTP_HOST` / `FTP_USER` / `FTP_PASSWORD` / `FTP_TARGET_DIR`
  - `TELEGRAM_BOT_TOKEN` / `TELEGRAM_CHAT_ID`
  - `LINE_CHANNEL_TOKEN` / `LINE_USER_ID`
  - `ALERT_EMAIL`
  - `GEMINI_API_KEY`
  - `GOOGLE_SERVICE_ACCOUNT`

### 手動上傳方式（如不走 CI）
1. 使用 FTP 工具（FileZilla 等）連線到 cPanel
2. 將 `stock-analyzer/` 資料夾整個上傳到網站根目錄
3. 確保 `stock-analyzer/data/` 和 `stock-analyzer/cache/` 目錄權限為 `777`
4. 在伺服器上建立 `stock-analyzer/config.local.php`：
```php
<?php
define("TELEGRAM_BOT_TOKEN", "你的TOKEN");
define("TELEGRAM_CHAT_ID", "你的CHAT_ID");
define("LINE_CHANNEL_TOKEN", "你的TOKEN");
define("LINE_USER_ID", "你的USER_ID");
define("ALERT_EMAIL", "你的EMAIL");
```

### 上傳圖示檔案到桌面
目前 `stock-analyzer/` 目錄下有以下圖示檔案可供桌面使用：
- `icon-512.png` — 512x512 高解析度 PNG（推薦放桌面）
- `icon-256.png` — 256x256 PNG
- `icon-192.png` — 192x192 PNG（Android PWA）
- `apple-touch-icon.png` — 180x180（iOS 主畫面）
- `favicon.ico` — 多尺寸 ICO（16/32/48/64/128/256）
- `icon.svg` — 向量原檔（無限放大）

桌面捷徑：下載 `icon-512.png` 或 `icon-256.png`，設為桌面捷徑圖示。

---

## 技術限制（重要！）

| 限制 | 說明 |
|------|------|
| PHP 版本 | 低於 7.4，**禁止使用** `fn()` 箭頭函數、typed properties |
| 陣列存取 | 所有 `$arr['key']` 必須用 `??` 或 `!empty()` 避免 Undefined index |
| config.php | 必須在最上面 `require_once config.local.php`，所有 define 用 `if (!defined(...))` 包裝 |
| 前端 JS | 原生 JavaScript，**禁止** ES6 arrow function `=>`，用 `function(){}` |
| 部署 config | 用 `printf`（不用 heredoc）避免空白問題 |
| Canvas 圖表 | `display: 'block'`（不能用 `''`），雙層 `requestAnimationFrame` 確保 reflow |
| UI 主題 | 深色金黑（#c29d66 gold, #0a0a0a bg），手機優先 480px max-width |

---

## 目前檔案結構

```
stock-analyzer/
├── index.php              # 前端 SPA（HTML + CSS + JS 全合一）
├── api.php                # 後端 API 路由
├── config.php             # 設定檔（含預設值）
├── config.local.php       # 本地覆蓋設定（不入版控）
├── cron.php               # 定時任務：監控清單掃描+推播
├── cron-scan.php          # 全市場掃描（背景任務）
├── telegram.php           # Telegram webhook 入口
├── favicon.ico            # 多尺寸圖示
├── icon.svg               # SVG 向量圖示
├── icon-512.png           # 512px PNG
├── icon-256.png           # 256px PNG
├── icon-192.png           # 192px PNG (Android PWA)
├── apple-touch-icon.png   # 180px PNG (iOS)
├── .htaccess              # Apache 設定
├── .gitignore
├── classes/
│   ├── TwseClient.php     # 台股資料 API 客戶端（TWSE + TPEx）
│   ├── UsStockClient.php  # 美股資料 API 客戶端
│   ├── TechnicalAnalysis.php  # 技術指標計算（MA/RSI/KD/MACD/Bollinger）
│   ├── SignalEngine.php   # 綜合信號引擎（估值+籌碼+部位+建議）
│   ├── PatternEngine.php  # K線型態辨識引擎（30種型態）
│   ├── PortfolioManager.php   # 投資組合管理
│   ├── TelegramBot.php    # Telegram 推播
│   └── LineBot.php        # LINE 推播
├── data/                  # 持久化資料（監控清單、提醒紀錄等）
└── cache/                 # API 快取（自動產生）
    └── twse/              # TWSE API 快取
```

---

## 已完成功能（全部已合併到 main）

### PR #1-#5：基礎建設
- PHP 後端 API（查詢/分析/監控/提醒）
- 技術指標計算（MA5/20/60、RSI、KD、MACD、布林通道、量比）
- 法人籌碼分析、估值河流（PE Band）
- 前端 SPA（總覽/監控/提醒/詳情四頁）
- K線圖 + KD線圖 + PE河流圖（Canvas 繪製）
- 中文搜尋、即時報價
- 部位建議、停損計算

### PR #6：圖表修復
- 修復 detail page 因 CSS `display:none` 導致 Canvas 無法渲染
- 改用 `display:'block'` + 雙層 `requestAnimationFrame`

### PR #7：Favicon
- 新增 SVG data URI favicon

### PR #8：技術分析完整功能（最新合併）
- **PatternEngine.php**：30 種 K 線型態偵測
  - 單根 K 棒（8種）：doji, long_upper_shadow, long_lower_shadow, hammer, hanging_man, large_bullish, large_bearish, spinning_top
  - 組合型態（14種）：bullish/bearish_engulfing, dark_cloud_cover, piercing_line, bullish/bearish_harami, gap_up/down, morning_star, evening_star, three_white_soldiers, three_black_crows, island_reversal_bullish/bearish
  - 價格行為（8種）：breakout_high, break_support, cross_above/below_ma20, volume_price_divergence_bear, volume_shrink_stop, consecutive_highs/lows
- **cron-scan.php**：全市場背景掃描
  - 掃描所有上市股票（4碼），每檔抓3個月K線
  - 進度寫入 `cache/scan_progress.json`
  - 結果寫入 `cache/market_scan.json`（4小時快取）
- **api.php 新端點**：
  - `scan-market`：取得掃描結果（支援 filters/sort 參數）
  - `scan-progress`：回傳掃描進度
  - `patterns`：單股型態偵測
- **前端新功能**：
  - 指標卡片全部加中文副標 + ? 按鈕
  - 底部滑出面板（Bottom Sheet）顯示白話解說
  - 個股詳情頁「K線型態辨識」區塊
  - 總覽頁橫向篩選條（型態快篩）
  - 全新「選股」tab（型態 chips + 掃描 + 結果卡片）

---

## 待完成 / 下一步

### 尚未提交的本地變更（需 commit + push + merge）
1. **圖示檔案**：`icon.svg`, `icon-512.png`, `icon-256.png`, `icon-192.png`, `apple-touch-icon.png`, `favicon.ico`
2. **index.php 修改**：將 favicon 從 data URI 改為引用外部檔案 + 新增 apple-touch-icon

### 提交步驟
```bash
git checkout claude/code-analysis-lyzp7a
git add stock-analyzer/icon.svg stock-analyzer/icon-512.png stock-analyzer/icon-256.png stock-analyzer/icon-192.png stock-analyzer/apple-touch-icon.png stock-analyzer/favicon.ico stock-analyzer/index.php
git commit -m "feat: 獨立圖示檔案 + apple-touch-icon 支援"
git push -u origin claude/code-analysis-lyzp7a
```
然後建立 PR 合併到 main，GitHub Actions 會自動部署。

### 未來可做的功能
- [ ] PWA manifest.json（可安裝到手機桌面）
- [ ] cron-scan.php 設定 cPanel cron job（建議每4小時跑一次）
- [ ] Telegram/LINE 推播整合（偵測到特定型態時自動通知）
- [ ] 美股型態掃描（目前只做台股）
- [ ] 歷史型態回測（型態出現後的勝率統計）
- [ ] 自訂監控條件（如：RSI < 30 且出現錘子線）

---

## API 端點清單

| 端點 | 說明 |
|------|------|
| `?action=analyze&stock=2330&market=tw` | 個股完整分析 |
| `?action=analyze-full&stock=2330&market=tw` | 含 PE/籌碼/融資的完整分析 |
| `?action=search&q=台積` | 股票搜尋 |
| `?action=scan-all` | 掃描監控清單所有股票 |
| `?action=scan-market&filters=hammer,breakout_high` | 全市場掃描結果（可帶篩選） |
| `?action=scan-progress` | 掃描進度 |
| `?action=patterns&stock=2330` | 單股型態偵測 |
| `?action=watchlist` | 取得監控清單 |
| `?action=watchlist-add&stock=2330&market=tw` | 加入監控 |
| `?action=watchlist-remove&stock=2330` | 移除監控 |
| `?action=alert-history` | 提醒紀錄 |

---

## 開發注意事項

1. **分支策略**：功能開發在 `claude/xxx` 分支，完成後 PR 合併到 `main` 觸發自動部署
2. **PHP 語法**：必須用 `function($x) { return $x; }` 不能用 `fn($x) => $x`
3. **前端修改**：所有 HTML/CSS/JS 都在 `index.php` 一個檔案內
4. **API 修改**：在 `api.php` 的 switch-case 中新增端點
5. **新增技術指標**：在 `TechnicalAnalysis.php` 加計算邏輯
6. **新增型態**：在 `PatternEngine.php` 的對應 detect 方法中新增
7. **快取**：API 回應快取在 `cache/twse/` 目錄，TTL 4小時
8. **TwseClient 取股票清單**：有三層 fallback（OpenAPI → 舊端點 → TPEx OTC）
