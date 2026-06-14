/**
 * 睡美人戰情室 — 產品年度銷售快取系統
 * 
 * 將「經銷銷售報表」彙總成「年度 / 月份 / 產品 / 客戶」層級的快取表，
 * 供睡美人產品總覽與分析使用，避免每次載入都掃描 2.5 萬筆原始銷貨明細。
 */

const SALES_YEAR_CACHE_SHEET = '產品年度銷售快取';
const SALES_YEAR_CACHE_VERSION = 'v1';
const SALES_YEAR_CACHE_HEADERS = [
  '年度', '月份', '產品編號', '客戶名稱',
  '銷售片數', '銷售坪數', '銷售金額', '交易筆數',
  '退貨片數', '總片數', '最後交易日', '產品名稱',
  '更新時間', '快取版本'
];

/**
 * 重建指定年度的銷售快取
 * @param {Array<number>} years 
 */
function rebuildSalesYearCache(years) {
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(30000)) {
    return { success: false, error: '年度銷售快取正在重建中，請稍後再試。' };
  }

  try {
    const targetYears = normalizeSalesCacheYears_(years);
    const ss = getMainSs();
    const salesRows = getSalesSheetData();
    if (!salesRows || salesRows.length < 2) {
      return { success: false, error: '找不到經銷銷售報表資料。' };
    }

    const headers = salesRows[0];
    const idx = {
      date: findHeaderIndex(headers, ['單據日期', '銷貨日期', '日期']),
      code: findHeaderIndex(headers, ['產品編號', '編號', '品號']),
      customer: findHeaderIndex(headers, ['客戶名稱', '客戶']),
      qty: findHeaderIndex(headers, ['數量', '銷貨數量', '片數']),
      amount: findHeaderIndex(headers, ['金額', '銷貨金額']),
      type: findHeaderIndex(headers, ['類別', '性質', '單據類別']),
      productName: findHeaderIndex(headers, ['產品名稱', '品名']),
      note: findHeaderIndex(headers, ['產品備註', '備註', '說明'])
    };

    if (idx.date === -1 || idx.code === -1 || idx.qty === -1 || idx.amount === -1) {
      return { success: false, error: '經銷銷售報表缺少必要欄位：日期、產品編號、數量或金額。' };
    }

    // 建立編號價目表每坪片數 (perPing) 快取
    const perPingMap = getPricePerPingMap_();
    const aggregate = {};
    let scanned = 0;
    let included = 0;
    const updatedAt = Utilities.formatDate(new Date(), 'GMT+8', 'yyyy/MM/dd HH:mm:ss');

    for (let i = 1; i < salesRows.length; i++) {
      const row = salesRows[i];
      scanned++;

      const date = parseDate(row[idx.date]);
      if (!date) continue;

      const year = date.getFullYear();
      if (targetYears.indexOf(year) === -1) continue;

      const code = normalizeSalesCacheCode_(row[idx.code]);
      if (!code) continue;

      const qty = optFloat(row[idx.qty]);
      const amount = optFloat(row[idx.amount]);
      if (qty === 0 && amount === 0) continue;

      const productName = idx.productName !== -1 ? String(row[idx.productName] || '').trim() : '';
      const note = idx.note !== -1 ? String(row[idx.note] || '').trim() : '';
      
      // 排除樣品與陳列單（比照戰情室 isSampleRow 邏輯）
      const custCodeVal = idx.customer !== -1 ? String(row[idx.customer] || '') : '';
      if (amount === 0 || 
          custCodeVal.endsWith('-S') || 
          custCodeVal.endsWith('-S1') ||
          /樣品|陳列|贈|SAMPLE|送樣|扣帶/i.test(productName + ' ' + note + ' ' + custCodeVal)) {
        continue;
      }

      const typeText = idx.type !== -1 ? String(row[idx.type] || '').trim() : '';
      const isReturn = typeText.indexOf('退') !== -1 || qty < 0;
      const month = date.getMonth() + 1;
      const customer = mergeCustomer(idx.customer !== -1 ? row[idx.customer] : '');
      const key = [year, month, code, customer].join('|');
      const perPing = perPingMap[code] || 36;

      if (!aggregate[key]) {
        aggregate[key] = {
          year: year,
          month: month,
          code: code,
          customer: customer,
          qty: 0,
          pings: 0,
          amount: 0,
          count: 0,
          returnQty: 0,
          totalQty: 0,
          lastTxDate: null,
          productName: productName
        };
      }

      const item = aggregate[key];
      if (productName && !item.productName) item.productName = productName;

      if (isReturn) {
        item.returnQty += Math.abs(qty);
        continue;
      }

      const absQty = Math.abs(qty);
      item.qty += absQty;
      item.pings += absQty / perPing;
      item.amount += amount;
      item.count += 1;
      item.totalQty += absQty;
      if (!item.lastTxDate || date > item.lastTxDate) item.lastTxDate = date;
      included++;
    }

    const rows = Object.keys(aggregate).sort().map(key => {
      const item = aggregate[key];
      return [
        item.year,
        item.month,
        item.code,
        item.customer,
        Math.round(item.qty * 100) / 100,
        Math.round(item.pings * 100) / 100,
        Math.round(item.amount),
        item.count,
        Math.round(item.returnQty * 100) / 100,
        Math.round(item.totalQty * 100) / 100,
        item.lastTxDate ? Utilities.formatDate(item.lastTxDate, 'GMT+8', 'yyyy/MM/dd') : '',
        item.productName,
        updatedAt,
        SALES_YEAR_CACHE_VERSION
      ];
    });

    const cacheSheet = ensureSalesYearCacheSheet_(ss);
    const mergeInfo = mergeSalesYearRows_(cacheSheet, targetYears, rows);
    const mergedRows = mergeInfo.rows;
    
    cacheSheet.clearContents();
    cacheSheet.getRange(1, 1, 1, SALES_YEAR_CACHE_HEADERS.length).setValues([SALES_YEAR_CACHE_HEADERS]);
    if (mergedRows.length > 0) {
      writeRowsInChunks_(cacheSheet, 2, 1, mergedRows, 1200);
    }
    cacheSheet.setFrozenRows(1);

    // 清空快取記憶體
    _clearCache();

    return {
      success: true,
      years: targetYears,
      scannedRows: scanned,
      includedRows: included,
      cacheRows: rows.length,
      keptOldRows: mergeInfo.keptOldRows,
      replacedYears: mergeInfo.replacedYears,
      totalRowsAfterMerge: mergedRows.length,
      updatedAt: updatedAt
    };
  } catch (e) {
    return { success: false, error: e.message };
  } finally {
    lock.releaseLock();
  }
}

/**
 * 讀取年度銷售快取中的完整明細
 */
function getSalesYearCacheRows() {
  const ss = getMainSs();
  const sh = ss.getSheetByName(SALES_YEAR_CACHE_SHEET);
  if (!sh || sh.getLastRow() < 2) return [];
  return sh.getRange(2, 1, sh.getLastRow() - 1, SALES_YEAR_CACHE_HEADERS.length).getValues();
}

/**
 * 建立快取工作表（若不存在）
 */
function ensureSalesYearCacheSheet_(ss) {
  let sh = ss.getSheetByName(SALES_YEAR_CACHE_SHEET);
  if (!sh) sh = ss.insertSheet(SALES_YEAR_CACHE_SHEET);
  return sh;
}

/**
 * 取得編號價目表的「片/坪」對位表
 */
function getPricePerPingMap_() {
  const map = {};
  const data = getPriceSheetData();
  if (data.length < 2) return map;

  const h = data[0];
  const idxCode = findHeaderIndex(h, ['編號', '產品編號']);
  const idxPerPing = findHeaderIndex(h, ['片/坪']);

  if (idxCode === -1 || idxPerPing === -1) return map;

  for (let i = 1; i < data.length; i++) {
    const code = normalizeSalesCacheCode_(data[i][idxCode]);
    if (!code) continue;
    map[code] = optFloat(data[i][idxPerPing]) || 36;
  }
  return map;
}

/**
 * 分段寫入資料（避免鎖表或過大寫入逾時）
 */
function writeRowsInChunks_(sheet, startRow, startCol, rows, chunkSize) {
  if (!rows || rows.length === 0) return;
  const size = chunkSize || 1000;
  for (let i = 0; i < rows.length; i += size) {
    const part = rows.slice(i, i + size);
    sheet.getRange(startRow + i, startCol, part.length, part[0].length).setValues(part);
    SpreadsheetApp.flush();
  }
}

/**
 * 合併新舊快取資料（僅覆蓋所選年度）
 */
function mergeSalesYearRows_(sheet, targetYears, newRows) {
  const targetYearNums = targetYears.map(Number);
  const targetYearSet = {};
  targetYearNums.forEach(y => targetYearSet[String(y)] = true);

  const oldRows = [];
  const lastRow = sheet.getLastRow();
  
  if (lastRow >= 2) {
    const oldData = sheet.getRange(2, 1, lastRow - 1, SALES_YEAR_CACHE_HEADERS.length).getValues();
    for (let i = 0; i < oldData.length; i++) {
      const row = oldData[i];
      const yrVal = parseInt(row[0], 10);
      if (isNaN(yrVal)) continue;
      
      const yrStr = String(yrVal);
      if (!targetYearSet[yrStr]) {
        oldRows.push(row);
      }
    }
  }

  return {
    rows: oldRows.concat(newRows),
    keptOldRows: oldRows.length,
    replacedYears: targetYearNums
  };
}

function normalizeSalesCacheYears_(years) {
  const nowYear = new Date().getFullYear();
  let list = [];
  if (Array.isArray(years)) {
    list = years;
  } else if (years) {
    list = String(years).split(/[,，\s]+/);
  } else {
    list = [nowYear, nowYear - 1, nowYear - 2];
  }

  const uniq = {};
  list.forEach(y => {
    const n = parseInt(y, 10);
    if (n > 1900 && n < 2200) uniq[n] = true;
  });
  return Object.keys(uniq).map(Number).sort();
}

function normalizeSalesCacheCode_(code) {
  return String(code || '').trim().toUpperCase().replace(/[\s\-]/g, '');
}
