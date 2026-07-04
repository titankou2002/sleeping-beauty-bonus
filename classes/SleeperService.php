<?php
// ====== SleeperService ======
class SleeperService
{
    use AiTrait,
    BonusTrait,
    CustomerTrait,
    DataTrait,
    MailTrait,
    ProductTrait,
    ProjectTrait,
    RepTrait,
    ReportTrait;

    private static $salesMerge = ['薛佶姈' => '高弘治', '陳育瑋' => '陳勁多'];
    private $areaMap = null;
    private static $customerSuffixes = [
        '企業股份有限公司', '開發有限公司', '股份有限公司', '有限公司',
        '企業', '建材', '國際', '磁磚', '磁藝', '工程', '設計部', '出貨',
        '保留收訂', '公司', '實業', '精品', '工作室', '開發', '室內裝修',
        '室內設計', '設計', '行銷', '商行', '建業', '綜合'
    ];
    private static $logisticsAddressKeywords = [
        '貨運', '物流', '運輸', '托運', '倉庫', '貨櫃', '轉運', '集運', '加工',
        '倉儲', '車隊', '站所', '物流中心', '轉運站'
    ];

    private $gs;

    public function __construct($gs)
    {
        $this->gs = $gs;
    }

    private $clientsCache = [];



    private function normalizeCustomerName($name)
    {
        return preg_replace('/[()（）【】\[\]「」『』,，.。\/／:：\s]+/u', '', trim((string)$name));
    }

    private function displayCustomerName($name)
    {
        $s = $this->normalizeCustomerName($name);
        if ($s === '') return '未知客戶';
        if (mb_strpos($s, '漢樺') !== false || mb_strpos($s, '波爾泰') !== false) return '漢樺';
        if (mb_strpos($s, '大永') !== false || mb_strpos($s, '新大永') !== false) return '大永';
        if (mb_strpos($s, '伊特') !== false || mb_strpos($s, '喬弈') !== false || mb_strpos($s, '喬翌') !== false) return '伊特';
        if (mb_strpos($s, '鏷城') !== false || mb_strpos($s, '璞城') !== false) return '鏷城';
        if (mb_strpos($s, '鼎康') !== false || mb_strpos($s, '鼎晨') !== false) return '鼎晨';
        if (mb_strpos($s, '今冠') !== false || mb_strpos($s, '金冠') !== false) return '金冠';
        if (mb_strpos($s, '東春') !== false || mb_strpos($s, '滿財') !== false) return '東春';
        if (mb_strpos($s, '德思特尼') !== false || mb_strpos($s, '德思') !== false) return '德思特尼';
        // 客戶合併規則 (2026-06-22 新增)
        if (mb_strpos($s, '太爾') !== false) return '信義星';
        if (mb_strpos($s, '琮達') !== false) return '琮威';

        $parts = preg_split('/[-－—]/u', $s);
        $s = $parts[0] ?? $s;
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach (self::$customerSuffixes as $suffix) {
                $pos = mb_strpos($s, $suffix);
                if ($pos !== false && $pos > 0) {
                    $s = mb_substr($s, 0, $pos);
                    $changed = true;
                    break;
                }
            }
        }
        return mb_substr($s, 0, 4);
    }

    private function normalizeAddress($address)
    {
        return preg_replace('/[\s()（）【】\[\]「」『』,，.。\/／:：\-－—]+/u', '', trim((string)$address));
    }

    private function normalizeSizeLabel($size)
    {
        $size = strtoupper(trim((string)$size));
        $size = str_replace(['CM', '㎝', '＊', '*', 'Ｘ', '×', 'x'], ['', '', 'X', 'X', 'X', 'X', 'X'], $size);
        $size = preg_replace('/\s+/u', '', $size);
        $size = preg_replace('/(?<=\d)X(?=\d)/u', 'X', $size);
        if ($size === '') return '未標尺寸';
        return $size;
    }

    private function normalizeContractHealthLabel($health)
    {
        $health = trim((string)$health);
        if ($health === '') return ['bucket' => '未分類', 'note' => ''];
        if (mb_strpos($health, '嚴重') !== false) return ['bucket' => '嚴重', 'note' => ''];
        if (mb_strpos($health, '逾期') !== false) return ['bucket' => '逾期', 'note' => ''];
        if (mb_strpos($health, '待續') !== false) return ['bucket' => '待續', 'note' => ''];
        if (mb_strpos($health, '已續') !== false) return ['bucket' => '已續', 'note' => ''];
        if (mb_strpos($health, '正常') !== false) return ['bucket' => '正常', 'note' => ''];
        if (mb_strpos($health, '未續約') !== false) return ['bucket' => '其它未續約', 'note' => $health];
        return ['bucket' => '未分類', 'note' => $health];
    }

    private function isLogisticsAddress($address)
    {
        $address = trim((string)$address);
        if ($address === '') return false;
        foreach (self::$logisticsAddressKeywords as $keyword) {
            if (mb_strpos($address, $keyword) !== false) return true;
        }
        return false;
    }

    private function shortProjectName($customer, $projectRaw, $address)
    {
        $projectRaw = trim((string)$projectRaw);
        $address = trim((string)$address);
        if ($projectRaw !== '') return mb_substr($projectRaw, 0, 24, 'UTF-8');
        if ($address !== '') return $this->displayCustomerName($customer) . '｜' . mb_substr($address, 0, 18, 'UTF-8');
        return $this->displayCustomerName($customer) . '｜專案';
    }

    private function peakShavedAverage($values)
    {
        $vals = array_values(array_filter(array_map('floatval', (array)$values), function ($v) {
            return $v > 0;
        }));
        if (!count($vals)) return 0;
        sort($vals, SORT_NUMERIC);
        if (count($vals) >= 6) {
            array_shift($vals);
            if (count($vals) > 2) array_pop($vals);
            if (count($vals) > 2) array_pop($vals);
        }
        if (!count($vals)) return 0;
        return array_sum($vals) / count($vals);
    }

    private function matchesStrategyPeriod($rowYear, $rowMonth, $periodMeta)
    {
        return (int)$rowYear === (int)$periodMeta['year'] && in_array((int)$rowMonth, $periodMeta['months'], true);
    }

    private function truncateReport($n)
    {
        $n = (float)$n;
        return $n < 0 ? ceil($n) : floor($n);
    }

    private function safeRatio($num, $den)
    {
        $den = (float)$den;
        if ($den == 0.0) return 0;
        return (float)$num / $den;
    }

    private function calcPearson($pairs)
    {
        $n = count($pairs);
        if ($n < 2) return 0;
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0; $sumY2 = 0;
        foreach ($pairs as $pair) {
            $x = (float)($pair['x'] ?? 0);
            $y = (float)($pair['y'] ?? 0);
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
        }
        $num = ($n * $sumXY) - ($sumX * $sumY);
        $den = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));
        if ($den <= 0) return 0;
        return $num / $den;
    }



    private static $taskCategoryRules = [
        '客訴'   => ['客訴', '投訴', '抱怨'],
        '送樣'   => ['送樣', '退樣', '樣品'],
        '帳款'   => ['對帳', '收款', '帳單', '放帳', '請款', '收票', '對帳/收款', '收訂金', '送帳單', '送帳'],
        '版面'   => ['版面', '上架', '下架', '陳列', '新品', '補大圖', '補圖', '大圖', '換板', '換版面'],
        '送貨退貨' => ['送貨', '退貨', '入店', '配送', '收退'],
        '案件'   => ['案件', '工地', '專案', '報價', '議價', '案名'],
        '客情'   => ['送禮', '聊天', '業務更新'],
    ];




    public function calcMultiplier($grade, $margin, $priceCostRatio, $isFullClearance)
    {
        $g = strtoupper(trim($grade));

        if ($g === 'XXX') {
            if ($priceCostRatio >= 0.7) return 3;
            if ($priceCostRatio >= 0.5) return 2;
            return 1;
        }
        if ($g === 'S') {
            if ($margin > 0.15) return 3;
            if ($priceCostRatio >= 0.85) return 2;
            if ($isFullClearance) return 2;
            return 1;
        }
        if ($g === 'A') {
            if ($margin > 0.15) return 2;
            if ($isFullClearance) return 2.5;
            if ($margin >= -0.05) return 1.5;
            return 1;
        }
        if ($g === 'B') {
            if ($margin > 0.30) return 2;
            return 1;
        }
        return 1;
    }



































































    private function safeAvg($values)
    {
        $vals = array_filter($values, function ($v) { return $v >= 0; });
        return count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : -1;
    }


    private function cleanSku($v)
    {
        return strtoupper(preg_replace('/[\s\-]/', '', trim($v)));
    }

    private static $brandMerge = ['ALAPLANA' => 'STN', 'VITACER' => 'STN'];

    public function normalizeBrand($brand)
    {
        $b = strtoupper(trim((string)$brand));
        if ($b === '') return '';
        return self::$brandMerge[$b] ?? $b;
    }

    private function normalizeSalesRep($v)
    {
        $name = trim((string)$v);
        if ($name === '') return '未分配';
        if ($name === '陳育瑋') return '陳勁多';
        return $name;
    }

    private function extractProjectFromNote($note)
    {
        $note = trim((string)$note);
        if ($note === '') return '';
        if (mb_strpos($note, '專案') === false) return '';
        return $note;
    }

    private function optFloat($v)
    {
        if ($v instanceof DateTime) return 0;
        $s = preg_replace('/[^0-9.\-]/', '', (string)$v);
        $n = (float)$s;
        return is_finite($n) ? $n : 0;
    }

    public function parseDate($v)
    {
        if (!$v) return null;
        if ($v instanceof DateTime) return $v;
        if (is_numeric($v) && $v > 40000) {
            $dt = new DateTime();
            $dt->setDate(1899, 12, 30);
            $dt->modify('+' . (int)$v . ' days');
            return $dt;
        }
        $str = trim((string)$v);

        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new DateTime(sprintf('%04d-%02d-%02d', $y, (int)$m[2], (int)$m[3]));
        }
        if (preg_match('/^0*(\d{2,4})[\/\-\.](\d{1,2})$/', $str, $m)) {
            $y = (int)$m[1];
            if ($y < 1000) $y += 1911;
            return new DateTime(sprintf('%04d-%02d-01', $y, (int)$m[2]));
        }
        return null;
    }

    private function formatRocDate($dt, $withDay = true)
    {
        if (!($dt instanceof DateTime)) return '';
        $rocYear = (int)$dt->format('Y') - 1911;
        if ($rocYear <= 0) return $withDay ? $dt->format('Y/m/d') : $dt->format('Y/m');
        return $withDay
            ? sprintf('%03d/%02d/%02d', $rocYear, (int)$dt->format('m'), (int)$dt->format('d'))
            : sprintf('%03d/%02d', $rocYear, (int)$dt->format('m'));
    }

    public function findHeader($headers, $candidates)
    {
        if (!$headers || count($headers) === 0) return -1;
        $clean = [];
        foreach ($headers as $h) {
            $clean[] = preg_replace('/[\s\x{FEFF}"()（）]/u', '', trim($h));
        }
        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            $idx = array_search($target, $clean);
            if ($idx !== false) return $idx;
        }
        foreach ($candidates as $cand) {
            $target = preg_replace('/[\s"()（）]/u', '', $cand);
            foreach ($clean as $i => $c) {
                if (strpos($c, $target) !== false) return $i;
            }
        }
        return -1;
    }

    // 模糊前綴查找：先精確，再嘗試 map 的 key 為 $key 前綴（或反向）
    private function fuzzyLookup(array $map, string $key)
    {
        if (isset($map[$key])) return $map[$key];
        foreach ($map as $k => $v) {
            if (mb_strlen($k) >= 2 && mb_strpos($key, $k) === 0) return $v;
        }
        foreach ($map as $k => $v) {
            if (mb_strlen($key) >= 2 && mb_strpos($k, $key) === 0) return $v;
        }
        return null;
    }

    private function getVal($row, $idx)
    {
        return isset($row[$idx]) ? $row[$idx] : '';
    }

    private function buildProductCustomerRows($stats)
    {
        if (!$stats || empty($stats['customerStats'])) return [];
        $rows = array_values($stats['customerStats']);
        usort($rows, function ($a, $b) { return $b['amount'] <=> $a['amount']; });
        $totalAmount = max(1, (float)($stats['totalAmount'] ?? 0));
        $out = [];
        foreach (array_slice($rows, 0, 5) as $row) {
            $out[] = [
                'name' => $row['name'],
                'qty' => round($row['qty']),
                'amount' => round($row['amount']),
                'sharePct' => round(($row['amount'] / $totalAmount) * 100, 1)
            ];
        }
        return $out;
    }

    private function padToColumn($data, $colIndex)
    {
        $result = [];
        foreach ($data as $row) {
            $padded = array_fill(0, $colIndex, '');
            $padded[$colIndex - 1] = $row[0];
            $result[] = $padded;
        }
        return $result;
    }

    public function isSampleRow($custCode, $custName, $note, $amt)
    {
        $cCode = strtoupper(trim($custCode));
        if (substr($cCode, -2) === '-S' || substr($cCode, -3) === '-S1') return true;
        
        $name = trim($custName);
        $nt = trim($note);
        
        if (preg_match('/樣品|陳列|贈|SAMPLE|送樣|扣帶/ui', $name . ' ' . $nt)) {
            return true;
        }
        if ($amt == 0) return true;
        return false;
    }


    private function extractIdFromUrl($url)
    {
        if (!$url) return '';
        if (preg_match('/[-\w]{25,}(?!.*[-\w]{25,})/', $url, $matches)) {
            return $matches[0];
        }
        return '';
    }


    private function fmtW($v)
    {
        $v = (float)$v;
        if ($v >= 10000) return number_format($v / 10000, 1) . '萬';
        if ($v >= 1000) return number_format($v / 1000, 1) . '千';
        return round($v) . '元';
    }
}
