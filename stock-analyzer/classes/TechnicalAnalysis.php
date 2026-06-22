<?php
class TechnicalAnalysis
{
    public function analyze(array $quotes, float $currentPrice = 0): array
    {
        if (count($quotes) < 5) return [];

        $closes = array_column($quotes, 'close');
        $highs = array_column($quotes, 'high');
        $lows = array_column($quotes, 'low');
        $volumes = array_column($quotes, 'volume');

        if (!$currentPrice) $currentPrice = end($closes);

        $ma5 = $this->ma($closes, 5);
        $ma20 = $this->ma($closes, 20);
        $ma60 = $this->ma($closes, 60);

        $rsi14 = $this->rsi($closes, 14);
        $kd = $this->kd($highs, $lows, $closes, 9);
        $macd = $this->macd($closes);
        $bollinger = $this->bollinger($closes, 20);

        $avgVol20 = count($volumes) >= 20
            ? array_sum(array_slice($volumes, -20)) / 20
            : array_sum($volumes) / max(1, count($volumes));
        $lastVol = end($volumes);
        $volumeRatio = $avgVol20 > 0 ? $lastVol / $avgVol20 : 1;

        $signals = $this->generateSignals($closes, $ma5, $ma20, $ma60, $rsi14, $kd, $macd, $bollinger, $volumeRatio);

        return [
            'price' => $currentPrice,
            'ma5' => end($ma5) ?: 0,
            'ma20' => end($ma20) ?: 0,
            'ma60' => end($ma60) ?: 0,
            'rsi14' => end($rsi14) ?: 0,
            'k' => end($kd['k']) ?: 0,
            'd' => end($kd['d']) ?: 0,
            'macdLine' => end($macd['macd']) ?: 0,
            'macdSignal' => end($macd['signal']) ?: 0,
            'macdHist' => end($macd['histogram']) ?: 0,
            'bollingerUpper' => end($bollinger['upper']) ?: 0,
            'bollingerMiddle' => end($bollinger['middle']) ?: 0,
            'bollingerLower' => end($bollinger['lower']) ?: 0,
            'volumeRatio' => round($volumeRatio, 2),
            'avgVolume20' => round($avgVol20),
            'signals' => $signals,
            'maSeries' => [
                'ma5' => array_slice($ma5, -60),
                'ma20' => array_slice($ma20, -60),
                'ma60' => array_slice($ma60, -60),
            ],
            'rsiSeries' => array_slice($rsi14, -60),
            'kdSeries' => [
                'k' => array_slice($kd['k'], -60),
                'd' => array_slice($kd['d'], -60),
            ],
            'macdSeries' => [
                'macd' => array_slice($macd['macd'], -60),
                'signal' => array_slice($macd['signal'], -60),
                'histogram' => array_slice($macd['histogram'], -60),
            ],
        ];
    }

    private function generateSignals(array $closes, array $ma5, array $ma20, array $ma60, array $rsi, array $kd, array $macd, array $boll, float $volRatio): array
    {
        $signals = [];
        $n = count($closes);
        $price = $closes[$n - 1] ?? 0;

        $curMa5 = end($ma5);
        $curMa20 = end($ma20);
        $curMa60 = end($ma60);
        $prevMa5 = $ma5[count($ma5) - 2] ?? 0;
        $prevMa20 = $ma20[count($ma20) - 2] ?? 0;

        // MA crossovers
        if ($prevMa5 && $prevMa20 && $curMa5 && $curMa20) {
            if ($prevMa5 < $prevMa20 && $curMa5 >= $curMa20) {
                $signals[] = ['type' => 'bullish', 'name' => 'MA5 上穿 MA20', 'desc' => '短線動能轉強，黃金交叉', 'weight' => 3];
            }
            if ($prevMa5 > $prevMa20 && $curMa5 <= $curMa20) {
                $signals[] = ['type' => 'bearish', 'name' => 'MA5 下穿 MA20', 'desc' => '短線動能轉弱，死亡交叉', 'weight' => 3];
            }
        }

        // Price vs MA60 (季線)
        if ($curMa60 > 0) {
            if ($price < $curMa60 * 0.97) {
                $signals[] = ['type' => 'bearish', 'name' => '跌破季線', 'desc' => sprintf('收盤 %.2f < MA60 %.2f', $price, $curMa60), 'weight' => 2];
            }
            $prevClose = $closes[$n - 2] ?? 0;
            if ($prevClose < $curMa60 && $price >= $curMa60) {
                $signals[] = ['type' => 'bullish', 'name' => '站上季線', 'desc' => '收盤突破季線，中期趨勢轉多', 'weight' => 2];
            }
        }

        // RSI
        $curRsi = end($rsi);
        if ($curRsi > 0) {
            if ($curRsi <= RSI_OVERSOLD) {
                $signals[] = ['type' => 'bullish', 'name' => 'RSI 超賣', 'desc' => sprintf('RSI %.1f ≤ %d，可能反彈', $curRsi, RSI_OVERSOLD), 'weight' => 2];
            }
            if ($curRsi >= RSI_OVERBOUGHT) {
                $signals[] = ['type' => 'bearish', 'name' => 'RSI 超買', 'desc' => sprintf('RSI %.1f ≥ %d，注意回檔', $curRsi, RSI_OVERBOUGHT), 'weight' => 2];
            }
        }

        // KD
        $curK = end($kd['k']);
        $curD = end($kd['d']);
        $prevK = $kd['k'][count($kd['k']) - 2] ?? 0;
        $prevD = $kd['d'][count($kd['d']) - 2] ?? 0;
        if ($prevK && $prevD && $curK && $curD) {
            if ($prevK < $prevD && $curK >= $curD && $curK < 30) {
                $signals[] = ['type' => 'bullish', 'name' => 'KD 低檔黃金交叉', 'desc' => sprintf('K%.1f 上穿 D%.1f，低檔反轉', $curK, $curD), 'weight' => 3];
            }
            if ($prevK > $prevD && $curK <= $curD && $curK > 70) {
                $signals[] = ['type' => 'bearish', 'name' => 'KD 高檔死亡交叉', 'desc' => sprintf('K%.1f 下穿 D%.1f，高檔反轉', $curK, $curD), 'weight' => 3];
            }
        }

        // MACD
        $curMacd = end($macd['macd']);
        $curSig = end($macd['signal']);
        $prevMacd = $macd['macd'][count($macd['macd']) - 2] ?? 0;
        $prevSig = $macd['signal'][count($macd['signal']) - 2] ?? 0;
        if ($prevMacd < $prevSig && $curMacd >= $curSig) {
            $signals[] = ['type' => 'bullish', 'name' => 'MACD 黃金交叉', 'desc' => 'DIF 上穿 MACD，趨勢轉多', 'weight' => 2];
        }
        if ($prevMacd > $prevSig && $curMacd <= $curSig) {
            $signals[] = ['type' => 'bearish', 'name' => 'MACD 死亡交叉', 'desc' => 'DIF 下穿 MACD，趨勢轉空', 'weight' => 2];
        }

        // Bollinger Bands
        $curUpper = end($boll['upper']);
        $curLower = end($boll['lower']);
        if ($curLower > 0 && $price <= $curLower) {
            $signals[] = ['type' => 'bullish', 'name' => '觸及布林下軌', 'desc' => '股價碰到布林帶下軌，可能反彈', 'weight' => 1];
        }
        if ($curUpper > 0 && $price >= $curUpper) {
            $signals[] = ['type' => 'bearish', 'name' => '觸及布林上軌', 'desc' => '股價碰到布林帶上軌，注意壓力', 'weight' => 1];
        }

        // Volume
        if ($volRatio >= MIN_VOLUME_RATIO) {
            $dir = $price >= ($closes[$n - 2] ?? $price) ? 'bullish' : 'bearish';
            $signals[] = ['type' => $dir, 'name' => '爆量', 'desc' => sprintf('成交量為 20 日均量的 %.1f 倍', $volRatio), 'weight' => 1];
        }

        usort($signals, fn($a, $b) => $b['weight'] <=> $a['weight']);
        return $signals;
    }

    public function calcRiskLevel(array $signals): array
    {
        $bullish = 0;
        $bearish = 0;
        foreach ($signals as $s) {
            if ($s['type'] === 'bullish') $bullish += $s['weight'];
            else $bearish += $s['weight'];
        }
        $total = $bullish + $bearish;
        if ($total === 0) return ['level' => 'neutral', 'label' => '中性', 'score' => 50, 'dots' => [2, 2, 1]];

        $score = round($bullish / $total * 100);

        if ($score >= 70) return ['level' => 'bullish', 'label' => '偏多', 'score' => $score, 'dots' => [0, 1, 4]];
        if ($score >= 55) return ['level' => 'slight_bullish', 'label' => '略偏多', 'score' => $score, 'dots' => [1, 1, 3]];
        if ($score >= 45) return ['level' => 'neutral', 'label' => '中性', 'score' => $score, 'dots' => [2, 1, 2]];
        if ($score >= 30) return ['level' => 'slight_bearish', 'label' => '略偏空', 'score' => $score, 'dots' => [3, 1, 1]];
        return ['level' => 'bearish', 'label' => '風險升高', 'score' => $score, 'dots' => [4, 1, 0]];
    }

    public function calcStopLoss(float $price, float $entryPrice = 0): array
    {
        $entry = $entryPrice ?: $price;
        $stopPct = STOP_LOSS_PCT / 100;
        $stop = round($entry * (1 + $stopPct), 2);
        return [
            'entryPrice' => $entry,
            'stopLoss' => $stop,
            'stopPct' => STOP_LOSS_PCT,
            'triggered' => $price <= $stop,
        ];
    }

    // ===== Indicator implementations =====

    private function ma(array $data, int $period): array
    {
        $result = [];
        for ($i = 0; $i < count($data); $i++) {
            if ($i < $period - 1) { $result[] = null; continue; }
            $sum = 0;
            for ($j = $i - $period + 1; $j <= $i; $j++) $sum += $data[$j];
            $result[] = round($sum / $period, 2);
        }
        return $result;
    }

    private function ema(array $data, int $period): array
    {
        $result = [];
        $k = 2 / ($period + 1);
        for ($i = 0; $i < count($data); $i++) {
            if ($i === 0) { $result[] = $data[$i]; continue; }
            $result[] = round($data[$i] * $k + $result[$i - 1] * (1 - $k), 4);
        }
        return $result;
    }

    private function rsi(array $closes, int $period = 14): array
    {
        $result = [];
        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($closes); $i++) {
            $change = $closes[$i] - $closes[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        for ($i = 0; $i < count($gains); $i++) {
            if ($i < $period - 1) { $result[] = null; continue; }
            if ($i === $period - 1) {
                $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
                $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;
            } else {
                $avgGain = ($avgGain * ($period - 1) + $gains[$i]) / $period;
                $avgLoss = ($avgLoss * ($period - 1) + $losses[$i]) / $period;
            }
            $rs = $avgLoss > 0 ? $avgGain / $avgLoss : 100;
            $result[] = round(100 - 100 / (1 + $rs), 2);
        }

        array_unshift($result, null);
        return $result;
    }

    private function kd(array $highs, array $lows, array $closes, int $period = 9): array
    {
        $k = [];
        $d = [];
        $rsv = [];

        for ($i = 0; $i < count($closes); $i++) {
            if ($i < $period - 1) {
                $rsv[] = null;
                $k[] = null;
                $d[] = null;
                continue;
            }
            $periodHighs = array_slice($highs, $i - $period + 1, $period);
            $periodLows = array_slice($lows, $i - $period + 1, $period);
            $hh = max($periodHighs);
            $ll = min($periodLows);
            $range = $hh - $ll;
            $curRsv = $range > 0 ? ($closes[$i] - $ll) / $range * 100 : 50;
            $rsv[] = $curRsv;

            $prevK = $k[$i - 1] ?? 50;
            $prevD = $d[$i - 1] ?? 50;
            $curK = round($prevK * 2 / 3 + $curRsv / 3, 2);
            $curD = round($prevD * 2 / 3 + $curK / 3, 2);
            $k[] = $curK;
            $d[] = $curD;
        }

        return ['k' => $k, 'd' => $d];
    }

    private function macd(array $closes, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        $emaFast = $this->ema($closes, $fast);
        $emaSlow = $this->ema($closes, $slow);

        $macdLine = [];
        for ($i = 0; $i < count($closes); $i++) {
            $macdLine[] = round($emaFast[$i] - $emaSlow[$i], 4);
        }

        $signalLine = $this->ema($macdLine, $signal);
        $histogram = [];
        for ($i = 0; $i < count($macdLine); $i++) {
            $histogram[] = round($macdLine[$i] - $signalLine[$i], 4);
        }

        return ['macd' => $macdLine, 'signal' => $signalLine, 'histogram' => $histogram];
    }

    private function bollinger(array $closes, int $period = 20, float $mult = 2): array
    {
        $upper = [];
        $middle = [];
        $lower = [];

        for ($i = 0; $i < count($closes); $i++) {
            if ($i < $period - 1) {
                $upper[] = null;
                $middle[] = null;
                $lower[] = null;
                continue;
            }
            $slice = array_slice($closes, $i - $period + 1, $period);
            $avg = array_sum($slice) / $period;
            $variance = 0;
            foreach ($slice as $v) $variance += ($v - $avg) ** 2;
            $std = sqrt($variance / $period);
            $middle[] = round($avg, 2);
            $upper[] = round($avg + $mult * $std, 2);
            $lower[] = round($avg - $mult * $std, 2);
        }

        return ['upper' => $upper, 'middle' => $middle, 'lower' => $lower];
    }
}
