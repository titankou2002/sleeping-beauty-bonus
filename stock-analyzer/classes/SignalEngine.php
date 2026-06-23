<?php
class SignalEngine
{
    private $ta;

    public function __construct()
    {
        $this->ta = new TechnicalAnalysis();
    }

    public function analyzeStock(string $stockId, array $quotes, array $peData = [], array $instData = [], array $marginData = []): array
    {
        if (count($quotes) < 10) return ['error' => 'insufficient data'];

        $lastQuote = end($quotes);
        $price = $lastQuote['close'];
        $prevClose = $quotes[count($quotes) - 2]['close'] ?? $price;
        $change = $price - $prevClose;
        $changePct = $prevClose > 0 ? round($change / $prevClose * 100, 2) : 0;

        $technical = $this->ta->analyze($quotes, $price);
        $risk = $this->ta->calcRiskLevel($technical['signals']);
        $stopLoss = $this->ta->calcStopLoss($price);

        // Valuation assessment
        $valuation = $this->assessValuation($peData, $price);

        // Chip analysis
        $chip = $this->assessChipFlow($instData, $marginData);

        // Position sizing
        $position = $this->calcPositionSize($price, $stopLoss['stopLoss'], $risk);

        // Overall recommendation
        $recommendation = $this->generateRecommendation($technical, $valuation, $chip, $risk);

        return [
            'stockId' => $stockId,
            'price' => $price,
            'change' => $change,
            'changePct' => $changePct,
            'date' => $lastQuote['date'],
            'technical' => $technical,
            'risk' => $risk,
            'stopLoss' => $stopLoss,
            'valuation' => $valuation,
            'chip' => $chip,
            'position' => $position,
            'recommendation' => $recommendation,
        ];
    }

    private function assessValuation(array $peData, float $price): array
    {
        if (empty($peData)) return ['zone' => 'unknown', 'label' => '無估值資料', 'per' => 0, 'pbr' => 0];

        $latestPe = end($peData);
        $per = $latestPe['per'] ?? 0;
        $pbr = $latestPe['pbr'] ?? 0;

        $peValues = array_filter(array_column($peData, 'per'), function($v) { return $v > 0; });
        if (empty($peValues)) return ['zone' => 'unknown', 'label' => '無 PER 資料', 'per' => $per, 'pbr' => $pbr];

        sort($peValues);
        $count = count($peValues);
        $p25 = $peValues[(int)($count * 0.25)] ?? 0;
        $p50 = $peValues[(int)($count * 0.50)] ?? 0;
        $p75 = $peValues[(int)($count * 0.75)] ?? 0;

        $zone = 'fair';
        $label = '合理';
        if ($per > 0 && $per <= $p25) { $zone = 'cheap'; $label = '便宜'; }
        elseif ($per > $p75) { $zone = 'expensive'; $label = '偏貴'; }
        elseif ($per > $p50) { $zone = 'fair_high'; $label = '合理偏高'; }

        return [
            'zone' => $zone,
            'label' => $label,
            'per' => round($per, 2),
            'pbr' => round($pbr, 2),
            'perP25' => round($p25, 2),
            'perP50' => round($p50, 2),
            'perP75' => round($p75, 2),
            'perMin' => round(min($peValues), 2),
            'perMax' => round(max($peValues), 2),
            'percentile' => $count > 0 ? round(count(array_filter($peValues, function($v) use ($per) { return $v <= $per; })) / $count * 100, 1) : 50,
        ];
    }

    private function assessChipFlow(array $instData, array $marginData): array
    {
        if (empty($instData)) return ['trend' => 'unknown', 'label' => '無籌碼資料'];

        $foreignNet = 0;
        $trustNet = 0;
        $dealerNet = 0;
        $totalNet = 0;
        $days = count($instData);
        $buyDays = 0;
        $sellDays = 0;
        $consecutiveSell = 0;
        $consecutiveBuy = 0;

        foreach ($instData as $d) {
            $net = ($d['foreignNet'] ?? 0) + ($d['trustNet'] ?? 0) + ($d['dealerNet'] ?? 0);
            $foreignNet += $d['foreignNet'] ?? 0;
            $trustNet += $d['trustNet'] ?? 0;
            $dealerNet += $d['dealerNet'] ?? 0;
            $totalNet += $net;
            if ($net > 0) $buyDays++;
            else $sellDays++;
        }

        $lastFew = array_slice($instData, -5);
        $recentTrend = 0;
        foreach ($lastFew as $d) {
            $net = ($d['foreignNet'] ?? 0) + ($d['trustNet'] ?? 0) + ($d['dealerNet'] ?? 0);
            $recentTrend += $net;
        }
        for ($i = count($instData) - 1; $i >= 0; $i--) {
            $net = ($instData[$i]['foreignNet'] ?? 0) + ($instData[$i]['trustNet'] ?? 0) + ($instData[$i]['dealerNet'] ?? 0);
            if ($net < 0) $consecutiveSell++;
            else break;
        }
        for ($i = count($instData) - 1; $i >= 0; $i--) {
            $net = ($instData[$i]['foreignNet'] ?? 0) + ($instData[$i]['trustNet'] ?? 0) + ($instData[$i]['dealerNet'] ?? 0);
            if ($net > 0) $consecutiveBuy++;
            else break;
        }

        $trend = 'neutral';
        $label = '籌碼中性';
        if ($recentTrend > 0 && $buyDays > $sellDays) { $trend = 'bullish'; $label = '法人買超'; }
        elseif ($recentTrend < 0 && $sellDays > $buyDays) { $trend = 'bearish'; $label = '法人賣超'; }

        $chipAlerts = [];
        if ($consecutiveSell >= 3) {
            $chipAlerts[] = "法人連賣 {$consecutiveSell} 日";
        }
        if ($consecutiveBuy >= 3) {
            $chipAlerts[] = "法人連買 {$consecutiveBuy} 日";
        }

        $marginChange = 0;
        if (!empty($marginData)) {
            $marginChange = ($marginData['marginBuy'] ?? 0) - ($marginData['marginSell'] ?? 0);
        }

        $chipWarning = '';
        if ($trend === 'bearish' && $marginChange > 0) {
            $chipWarning = '法人賣超與融資增加使反彈品質存疑';
        }

        return [
            'trend' => $trend,
            'label' => $label,
            'foreignNet' => $foreignNet,
            'trustNet' => $trustNet,
            'dealerNet' => $dealerNet,
            'totalNet' => $totalNet,
            'buyDays' => $buyDays,
            'sellDays' => $sellDays,
            'consecutiveSell' => $consecutiveSell,
            'consecutiveBuy' => $consecutiveBuy,
            'marginChange' => $marginChange,
            'alerts' => $chipAlerts,
            'warning' => $chipWarning,
            'days' => $days,
        ];
    }

    private function calcPositionSize(float $price, float $stopLoss, array $risk): array
    {
        $totalCapital = 1000000; // default 100萬
        $maxRiskPct = 2; // max 2% capital at risk per trade
        $maxRiskAmount = $totalCapital * $maxRiskPct / 100;

        $riskPerShare = abs($price - $stopLoss);
        if ($riskPerShare <= 0) $riskPerShare = $price * 0.065;

        $shares = $riskPerShare > 0 ? floor($maxRiskAmount / $riskPerShare) : 0;
        $shares = $shares - ($shares % 1000); // round to 整張 (1000 shares)
        if ($shares < 1000) $shares = 1000;

        $investAmount = $shares * $price;
        $investPct = $totalCapital > 0 ? round($investAmount / $totalCapital * 100, 1) : 0;

        $riskMultiplier = 1;
        if ($risk['level'] === 'bearish') $riskMultiplier = 0.5;
        elseif ($risk['level'] === 'slight_bearish') $riskMultiplier = 0.7;
        elseif ($risk['level'] === 'bullish') $riskMultiplier = 1.2;

        $adjustedShares = (int)(($shares * $riskMultiplier) / 1000) * 1000;
        if ($adjustedShares < 1000) $adjustedShares = 1000;

        return [
            'suggestedShares' => $adjustedShares,
            'suggestedLots' => $adjustedShares / 1000,
            'investAmount' => $adjustedShares * $price,
            'investPct' => round($adjustedShares * $price / $totalCapital * 100, 1),
            'riskAmount' => $adjustedShares * $riskPerShare,
            'riskPct' => round($adjustedShares * $riskPerShare / $totalCapital * 100, 2),
            'stopLoss' => $stopLoss,
        ];
    }

    private function generateRecommendation(array $technical, array $valuation, array $chip, array $risk): array
    {
        $score = $risk['score'];
        $reasons = [];

        // Technical
        $bullishSignals = array_filter($technical['signals'], function($s) { return $s['type'] === 'bullish'; });
        $bearishSignals = array_filter($technical['signals'], function($s) { return $s['type'] === 'bearish'; });

        if (count($bullishSignals) > count($bearishSignals)) {
            $reasons[] = '技術面偏多（' . count($bullishSignals) . ' 個多方信號）';
        } elseif (count($bearishSignals) > count($bullishSignals)) {
            $reasons[] = '技術面偏空（' . count($bearishSignals) . ' 個空方信號）';
        }

        // Valuation
        if ($valuation['zone'] === 'cheap') { $score += 10; $reasons[] = '估值便宜'; }
        elseif ($valuation['zone'] === 'expensive') { $score -= 10; $reasons[] = '估值偏貴'; }

        // Chip
        if ($chip['trend'] === 'bullish') { $score += 10; $reasons[] = $chip['label']; }
        elseif ($chip['trend'] === 'bearish') { $score -= 10; $reasons[] = $chip['label']; }
        if ($chip['warning']) $reasons[] = $chip['warning'];

        $score = max(0, min(100, $score));

        $action = '觀望';
        $actionIcon = '→';
        if ($score >= 70) { $action = '偏多操作'; $actionIcon = '↑↑'; }
        elseif ($score >= 55) { $action = '等回檔'; $actionIcon = '→'; }
        elseif ($score <= 30) { $action = '風險升高'; $actionIcon = '↓↓'; }
        elseif ($score <= 45) { $action = '減碼/觀望'; $actionIcon = '↓'; }

        return [
            'action' => $action,
            'actionIcon' => $actionIcon,
            'score' => $score,
            'reasons' => $reasons,
            'summary' => implode('。', array_slice($reasons, 0, 2)) . '。',
        ];
    }
}
