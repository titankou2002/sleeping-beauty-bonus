# 睡美人戰情室 (sleeping_beauty_bonus) 開發進度與更新記錄
**日期**：2026-06-11
**當前狀態**：已修復完成，程式碼已 Push 至 GitHub，等待 cPanel 拉取部署。

---

## 🌌 今日開發目標與背景
將原本在 Google Apps Script (GAS) 版本中開發好的**銷售快取優化**、**版面記錄神器整合 (陳列樣板明細)**、**去化分析 (MOS 診斷)** 與**樣品過濾邏輯**，完整移植到您以 PHP 運作的網頁專案 `sleeping-beauty-bonus` 中，以配合您「GitHub 儲存庫 ➜ cPanel 部署至網域」的工作流。

---

## 🛠️ 1. Git 同步障礙排除 (跨電腦 Lock 衝突)
**狀況**：
在進行程式碼讀取與比對時，發現 Git 執行任何指令都停滯或報錯。
**原因**：
您在另一台電腦操作 Git 時可能異常中斷或當機，導致三個 Lock 檔案 (`.git/index.lock`、`main.lock`、`origin/main.lock`) 被 Google Drive 同步了過來，鎖死了這台電腦的 Git 工作區。
**解決方案**：
1. 本地執行指令強制清除這三個 `.lock` 鎖定檔案。
2. 成功與 GitHub 遠端進行 `git fetch`。
3. 執行 `git reset --hard origin/main` 將工作區與您在另一台電腦上 commit 推送的進度完全對齊，確保代碼基底最新且乾淨。

---

## 📝 2. 核心功能移轉與程式碼修改

### ⚙️ A. 專案配置更新 (`config.php`)
- 新增快取 Sheet 名稱 `define('CACHE_SHEET', '產品年度銷售快取');`
- 新增版面記錄神器試算表 ID：`define('SS_ID_LAYOUT', '1zTTl3IjrwZvYdxvX3UZk6YLVaGF7m_DBhb_tWM2LjW0');`
- 新增版面上架分頁名稱：`define('LAYOUT_SHEET', '版面上架清單');`

### 🧠 B. 後台 API 邏輯增強 (`api.php`)
1. **GoogleSheetsClient 方法擴充**：
   - 新增 `clearSheet($sheetName)`：使用 Sheets API 的 `:clear` 介面，在每次快取重建時，先清空舊的資料列。
   - **修正 HTTP 400 錯誤**：在批次寫入 (`writeRows`) 及更新儲存格 (`updateCell`, `updateRowRange`) 的 `PUT` 請求 URL 後方，統一補上必要的查詢參數 `?valueInputOption=USER_ENTERED`，解決了試算表寫入失敗的問題。
2. **SleeperService 方法新增**：
   - `isSampleRow($custCode, $custName, $note, $amt)`：智慧過濾樣品、陳列、贈送單（比照 GAS 邏輯，排除代號結尾 `-S`/`-S1`、排除含 `樣品/陳列/SAMPLE/扣帶` 等關鍵字，以及金額為 0 的列）。
   - `getActiveDisplaysMap()`：透過專屬 Client 讀取版面上架清單，自動篩選未下架（`下架日期` 為空）的活躍陳列。自動將 Google Drive 原始照片連結解析並重組為 lh3 高解析度直連圖網址 (`lh3.googleusercontent.com/d/ID=w1000`)。
   - `rebuildSalesYearCache($years)`：將經銷銷售報表以 SKU 及客戶為維度，重新彙總片數、坪數、金額、最後交易日等快取資訊，分批寫入 Sheet。
   - `loadSalesStats($metaMap)`：高速統計載入器。優先嘗試讀取快取工作表，讀取失敗或快取未建立時，會自動無縫 fallback 至掃描原始報表。
3. **產品總覽邏輯重構**：
   - `getSleeperProductOverview()`、`getDiscontinuedProductOverview()`、`getNormalProductOverview()` 全部改為使用 `loadSalesStats` 讀取銷售狀態。
   - 整合陳列資訊，返回 `displayCount` (店家數) 與 `displays` (明細陣列)。
   - 計算去化指標：`mos` (去化月數)、`action` (去化建議)、`actionColor` (建議標籤色)、`stagnantReason` (停滯原因) 等。
4. **API Router 路由**：
   - 註冊 `rebuild-cache`、`active-displays` API 接口。
   - 修復了 `api.php` 原本被截斷且無法抵達的死代碼區塊，將其規範為 `trial-sheet` case。

### 🎨 C. 前端網頁介面升級 (`index.php`)
- **同步按鈕**：在「產品總覽」控制列加入 **「🔄 同步銷售快取」** 按鈕，點擊時會發送 POST 請求至 `api.php?action=rebuild-cache`。
- **陳列明細連結**：在產品卡片「目前陳列」欄位中，輸出下底線的連結 `🖼️ X 家`。點擊時觸發 `showDisplayDetails(sku)`。
- **店面實景 Modal**：在 Detail Modal 中重組輸出，展示該產品目前在所有店家的上架日期與**店面陳列實景照片**。
- **MOS 診斷標籤**：產品卡片右下角顯示停滯原因 (如 `6M無交易`)，並根據去化水準附帶不同顏色的行動徽章 (如 `🔥 折扣促銷` [紅色] / `📝 觀察/加強推廣` [黃色] / `正常去化` [綠色])。
- **Bug 修復**：補上了 template 遺漏的 `closeDetail()` 關閉 Modal JS 函數。

---

## 🚀 3. 推送與回家續讀指引
上述所有的修改已經全部由 AI 本地 Commit 並 **Push 到您的 GitHub 儲存庫 (`main` 分支)**：
- **最後的 Commit ID**：`5051082 fix: add missing valueInputOption parameter to PUT requests`

---

## 📝 4. 新增評估：戰略報表中心移植規劃（日/週/月/年報）
**評估對象**：[/戰情室](file:///Users/titankou2002/Library/CloudStorage/GoogleDrive-titankou2002@gmail.com/我的雲端硬碟/BT/Antigravity/戰情室) 下的 [Service_Report.js](file:///Users/titankou2002/Library/CloudStorage/GoogleDrive-titankou2002@gmail.com/我的雲端硬碟/BT/Antigravity/戰情室/Service_Report.js) 與 [Service_SalesYearCache.js](file:///Users/titankou2002/Library/CloudStorage/GoogleDrive-titankou2002@gmail.com/我的雲端硬碟/BT/Antigravity/戰情室/Service_SalesYearCache.js)。
**成果報告**：已在 Artifact 目錄建立 [proposed_reports.md](file:///Users/titankou2002/.gemini/antigravity/brain/d520153e-f7b7-4318-a936-a419d324d6c0/proposed_reports.md)。

---

## 🌙 今晚交辦工作事項（回家續讀與決策）

為了方便您回家在另一台電腦上續讀與決策，以下為今晚的主要工作交辦與驗證事項：

### 🔍 1. 閱讀「戰略報表中心」開發規劃
請開啟並詳讀本機資料夾中的報告：[proposed_reports.md](file:///Users/titankou2002/.gemini/antigravity/brain/d520153e-f7b7-4318-a936-a419d324d6c0/proposed_reports.md)。
該報告分析了如何利用目前 PHP 系統已建好的 `產品年度銷售快取`（或微調），來產出與 GAS 版相同的日/週/月/年戰略報表。

### 🧠 2. 針對三個核心問題進行決策
為了下一步的程式碼開發與移植，請您思考並決定：
- **問題 A（快取擴充）**：是否同意將「**負責業務**」加入快取欄位中？
  - *原因*：現有快取無業務欄位，加入後才能極速生成業務個人的月/年業績排行與獎金分析。
- **問題 B（日報/週報查詢方案）**：日、週報在 PHP 中的資料撈取方式，您偏好哪一種？
  - *方案一（局部掃描）*：日/週報直接在 PHP 中拉取「當月/上月」的原始銷貨列（不需掃描幾萬筆歷史明細，載入約 1-2 秒，架構最簡單）。
  - *方案二（快取升級）*：將月快取重構為「日級彙總快取」，支援所有報表，速度最快（<0.2秒，快取行數會略增至一萬多行）。
- **問題 C（UI 設計）**：是否在目前網頁頂部直接開闢第三個 Tab「**戰略報表**」，並設計「日報看板」、「戰略週報」、「業績月報/年報」三個子頁面與金黑漸層 UI？

### 🧪 3. 部署與功能驗證（回家上機操作）
1. **cPanel 拉取**：在網域後台 (cPanel) 將今日 Push 的 Git 程式碼 Pull 至網頁伺服器。
2. **重構快取測試**：點擊網頁上「產品總覽」的 **「🔄 同步銷售快取」**，確認 `產品年度銷售快取` 工作表是否正確更新且清空舊資料。
3. **陳列照片測試**：點擊陳列店家數，確認彈出的 Modal 中是否有正確加載 lh3 網址的店面實景照片。

祝您下班路上平安，回家用另一台電腦拉取 Google Drive 或訪問網站即可直接驗證與續讀！

