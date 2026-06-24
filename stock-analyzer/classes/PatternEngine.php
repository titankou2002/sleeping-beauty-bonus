<?php
class PatternEngine
{
    /**
     * Detect all K-line patterns from quotes array.
     *
     * @param array $quotes  Each element: ['date','open','high','low','close','volume']
     * @return array  Array of pattern arrays with keys: code, name, type, desc, strength, date
     */
    public function detectPatterns(array $quotes)
    {
        if (count($quotes) < 5) return [];

        $patterns = [];

        // Single candle patterns - scan last 5 candles
        $startIdx = max(0, count($quotes) - 5);
        for ($i = $startIdx; $i < count($quotes); $i++) {
            $single = $this->detectSingleCandle($quotes, $i);
            foreach ($single as $p) {
                $patterns[] = $p;
            }
        }

        // Multi candle patterns - scan last 5 candles
        for ($i = $startIdx; $i < count($quotes); $i++) {
            $multi = $this->detectMultiCandle($quotes, $i);
            foreach ($multi as $p) {
                $patterns[] = $p;
            }
        }

        // Price action patterns - full array
        $priceAction = $this->detectPriceAction($quotes);
        foreach ($priceAction as $p) {
            $patterns[] = $p;
        }

        return $patterns;
    }

    /**
     * Quick scan - returns just pattern code strings.
     *
     * @param array $quotes
     * @return array  Array of pattern code strings
     */
    public function quickScan(array $quotes)
    {
        $patterns = $this->detectPatterns($quotes);
        $codes = [];
        foreach ($patterns as $p) {
            $codes[] = $p['code'];
        }
        return array_unique($codes);
    }

    // ========== SINGLE CANDLE PATTERNS ==========

    private function detectSingleCandle(array $quotes, $idx)
    {
        $patterns = [];
        $q = $quotes[$idx];

        $open = (float)($q['open'] ?? 0);
        $high = (float)($q['high'] ?? 0);
        $low = (float)($q['low'] ?? 0);
        $close = (float)($q['close'] ?? 0);
        $date = $q['date'] ?? '';

        $body = abs($close - $open);
        $range = $high - $low;
        if ($range <= 0) return $patterns;

        $bodyRatio = $body / $range;
        $isUp = $close >= $open;
        $isDown = $close < $open;

        $upperShadow = $high - max($open, $close);
        $lowerShadow = min($open, $close) - $low;

        // Trend detection for hammer/hanging man
        $trend = $this->detectTrend($quotes, $idx, 5);

        // 十字線 (Doji)
        if ($bodyRatio < 0.1) {
            $patterns[] = [
                'code' => 'doji',
                'name' => '十字線',
                'type' => 'neutral',
                'desc' => '開盤與收盤價幾乎相同，顯示多空雙方力量均衡，市場猶豫不決',
                'strength' => 1,
                'date' => $date,
            ];
        }

        // 長上影線 (Long upper shadow)
        if ($body > 0 && $upperShadow > $body * 2 && $upperShadow > $range * 0.5) {
            $patterns[] = [
                'code' => 'long_upper_shadow',
                'name' => '長上影線',
                'type' => 'bearish',
                'desc' => '盤中曾大幅上漲但收盤回落，上方賣壓沉重，短線偏空',
                'strength' => 2,
                'date' => $date,
            ];
        }

        // 長下影線 (Long lower shadow)
        if ($body > 0 && $lowerShadow > $body * 2 && $lowerShadow > $range * 0.5) {
            // 錘子線 (Hammer) - long lower shadow in downtrend
            if ($trend === 'down') {
                $patterns[] = [
                    'code' => 'hammer',
                    'name' => '錘子線',
                    'type' => 'bullish',
                    'desc' => '下跌趨勢中出現長下影線，下方有強力買盤支撐，可能止跌回升',
                    'strength' => 2,
                    'date' => $date,
                ];
            }
            // 吊燈線 (Hanging man) - long lower shadow in uptrend
            elseif ($trend === 'up') {
                $patterns[] = [
                    'code' => 'hanging_man',
                    'name' => '吊燈線',
                    'type' => 'bearish',
                    'desc' => '上漲趨勢中出現長下影線，雖然收盤拉回但暗示賣壓浮現，注意反轉',
                    'strength' => 2,
                    'date' => $date,
                ];
            } else {
                $patterns[] = [
                    'code' => 'long_lower_shadow',
                    'name' => '長下影線',
                    'type' => 'bullish',
                    'desc' => '盤中曾大幅下跌但尾盤拉回，下方有買盤支撐',
                    'strength' => 1,
                    'date' => $date,
                ];
            }
        }

        // 大陽線 (Large bullish)
        if ($isUp && $bodyRatio > 0.7) {
            $patterns[] = [
                'code' => 'large_bullish',
                'name' => '大陽線',
                'type' => 'bullish',
                'desc' => '實體紅K佔比大，多方強勢進攻，後市看漲機會高',
                'strength' => 2,
                'date' => $date,
            ];
        }

        // 大陰線 (Large bearish)
        if ($isDown && $bodyRatio > 0.7) {
            $patterns[] = [
                'code' => 'large_bearish',
                'name' => '大陰線',
                'type' => 'bearish',
                'desc' => '實體黑K佔比大，空方強勢壓制，後市看跌機會高',
                'strength' => 2,
                'date' => $date,
            ];
        }

        // 紡錘線 (Spinning top): small body with long shadows on both sides
        if ($bodyRatio > 0.1 && $bodyRatio < 0.35
            && $upperShadow > $body
            && $lowerShadow > $body) {
            $patterns[] = [
                'code' => 'spinning_top',
                'name' => '紡錘線',
                'type' => 'neutral',
                'desc' => '實體小、上下影線長，多空角力激烈但未分勝負，後續方向不明',
                'strength' => 1,
                'date' => $date,
            ];
        }

        return $patterns;
    }

    // ========== MULTI CANDLE PATTERNS ==========

    private function detectMultiCandle(array $quotes, $idx)
    {
        $patterns = [];
        $count = count($quotes);

        // Need at least 2 prior candles for 3-candle patterns
        if ($idx < 1) return $patterns;

        $curr = $quotes[$idx];
        $prev = $quotes[$idx - 1];

        $cOpen = (float)($curr['open'] ?? 0);
        $cHigh = (float)($curr['high'] ?? 0);
        $cLow = (float)($curr['low'] ?? 0);
        $cClose = (float)($curr['close'] ?? 0);
        $cDate = $curr['date'] ?? '';

        $pOpen = (float)($prev['open'] ?? 0);
        $pHigh = (float)($prev['high'] ?? 0);
        $pLow = (float)($prev['low'] ?? 0);
        $pClose = (float)($prev['close'] ?? 0);

        $cBody = abs($cClose - $cOpen);
        $pBody = abs($pClose - $pOpen);
        $cIsUp = $cClose >= $cOpen;
        $cIsDown = $cClose < $cOpen;
        $pIsUp = $pClose >= $pOpen;
        $pIsDown = $pClose < $pOpen;

        // 看多吞噬 (Bullish engulfing)
        if ($pIsDown && $cIsUp && $cOpen <= $pClose && $cClose >= $pOpen && $cBody > $pBody) {
            $patterns[] = [
                'code' => 'bullish_engulfing',
                'name' => '看多吞噬',
                'type' => 'bullish',
                'desc' => '前一天黑K被今天大紅K完全包覆，多方強力反攻，看漲訊號',
                'strength' => 3,
                'date' => $cDate,
            ];
        }

        // 看空吞噬 (Bearish engulfing)
        if ($pIsUp && $cIsDown && $cOpen >= $pClose && $cClose <= $pOpen && $cBody > $pBody) {
            $patterns[] = [
                'code' => 'bearish_engulfing',
                'name' => '看空吞噬',
                'type' => 'bearish',
                'desc' => '前一天紅K被今天大黑K完全包覆，空方強力壓制，看跌訊號',
                'strength' => 3,
                'date' => $cDate,
            ];
        }

        // 烏雲罩頂 (Dark cloud cover)
        if ($pIsUp && $cIsDown && $cOpen > $pHigh && $cClose < ($pOpen + $pClose) / 2 && $cClose > $pOpen) {
            $patterns[] = [
                'code' => 'dark_cloud_cover',
                'name' => '烏雲罩頂',
                'type' => 'bearish',
                'desc' => '今天跳空高開後大跌，收盤跌破前一天紅K的中點，烏雲壓頂偏空',
                'strength' => 2,
                'date' => $cDate,
            ];
        }

        // 曙光乍現 (Piercing line)
        if ($pIsDown && $cIsUp && $cOpen < $pLow && $cClose > ($pOpen + $pClose) / 2 && $cClose < $pOpen) {
            $patterns[] = [
                'code' => 'piercing_line',
                'name' => '曙光乍現',
                'type' => 'bullish',
                'desc' => '今天跳空低開後大漲，收盤突破前一天黑K的中點，曙光乍現偏多',
                'strength' => 2,
                'date' => $cDate,
            ];
        }

        // 看多母子線 (Bullish harami)
        if ($pIsDown && $cIsUp && $pBody > 0 && $cBody < $pBody
            && $cOpen >= $pClose && $cClose <= $pOpen) {
            $patterns[] = [
                'code' => 'bullish_harami',
                'name' => '看多母子線',
                'type' => 'bullish',
                'desc' => '小紅K完全在前一天大黑K的實體之中，下跌動能減弱，可能反轉向上',
                'strength' => 1,
                'date' => $cDate,
            ];
        }

        // 看空母子線 (Bearish harami)
        if ($pIsUp && $cIsDown && $pBody > 0 && $cBody < $pBody
            && $cOpen <= $pClose && $cClose >= $pOpen) {
            $patterns[] = [
                'code' => 'bearish_harami',
                'name' => '看空母子線',
                'type' => 'bearish',
                'desc' => '小黑K完全在前一天大紅K的實體之中，上漲動能減弱，可能反轉向下',
                'strength' => 1,
                'date' => $cDate,
            ];
        }

        // 向上跳空 (Gap up)
        if ($cLow > $pHigh) {
            $patterns[] = [
                'code' => 'gap_up',
                'name' => '向上跳空',
                'type' => 'bullish',
                'desc' => '今天最低價高於昨天最高價，形成向上缺口，多方氣勢強勁',
                'strength' => 2,
                'date' => $cDate,
            ];
        }

        // 向下跳空 (Gap down)
        if ($cHigh < $pLow) {
            $patterns[] = [
                'code' => 'gap_down',
                'name' => '向下跳空',
                'type' => 'bearish',
                'desc' => '今天最高價低於昨天最低價，形成向下缺口，空方氣勢強勁',
                'strength' => 2,
                'date' => $cDate,
            ];
        }

        // === 3-candle patterns (need idx >= 2) ===
        if ($idx >= 2) {
            $prev2 = $quotes[$idx - 2];
            $p2Open = (float)($prev2['open'] ?? 0);
            $p2High = (float)($prev2['high'] ?? 0);
            $p2Low = (float)($prev2['low'] ?? 0);
            $p2Close = (float)($prev2['close'] ?? 0);
            $p2Body = abs($p2Close - $p2Open);
            $p2IsUp = $p2Close >= $p2Open;
            $p2IsDown = $p2Close < $p2Open;

            $pRange = $pHigh - $pLow;
            $pBodyRatio = ($pRange > 0) ? $pBody / $pRange : 0;

            // 晨星 (Morning star): 3 candle bottom reversal
            // Day1: large bearish, Day2: small body (gap down), Day3: large bullish closing above Day1 midpoint
            if ($p2IsDown && $p2Body > 0
                && $pBodyRatio < 0.35
                && $cIsUp
                && $pHigh < $p2Close
                && $cClose > ($p2Open + $p2Close) / 2) {
                $patterns[] = [
                    'code' => 'morning_star',
                    'name' => '晨星',
                    'type' => 'bullish',
                    'desc' => '三根K線底部反轉型態：大黑K → 小十字 → 大紅K，底部確認偏多',
                    'strength' => 3,
                    'date' => $cDate,
                ];
            }

            // 夜星 (Evening star): 3 candle top reversal
            // Day1: large bullish, Day2: small body (gap up), Day3: large bearish closing below Day1 midpoint
            if ($p2IsUp && $p2Body > 0
                && $pBodyRatio < 0.35
                && $cIsDown
                && $pLow > $p2Close
                && $cClose < ($p2Open + $p2Close) / 2) {
                $patterns[] = [
                    'code' => 'evening_star',
                    'name' => '夜星',
                    'type' => 'bearish',
                    'desc' => '三根K線頭部反轉型態：大紅K → 小十字 → 大黑K，頂部確認偏空',
                    'strength' => 3,
                    'date' => $cDate,
                ];
            }

            // 三白兵 (Three white soldiers)
            if ($p2IsUp && $pIsUp && $cIsUp
                && $pClose > $p2Close && $cClose > $pClose
                && $pOpen > $p2Open && $cOpen > $pOpen
                && $pOpen >= $p2Close * 0.98 && $cOpen >= $pClose * 0.98) {
                $patterns[] = [
                    'code' => 'three_white_soldiers',
                    'name' => '三白兵',
                    'type' => 'bullish',
                    'desc' => '連續三根紅K逐步墊高，多方步步進逼，強勢看漲型態',
                    'strength' => 3,
                    'date' => $cDate,
                ];
            }

            // 三烏鴉 (Three black crows)
            if ($p2IsDown && $pIsDown && $cIsDown
                && $pClose < $p2Close && $cClose < $pClose
                && $pOpen < $p2Open && $cOpen < $pOpen
                && $pOpen <= $p2Close * 1.02 && $cOpen <= $pClose * 1.02) {
                $patterns[] = [
                    'code' => 'three_black_crows',
                    'name' => '三烏鴉',
                    'type' => 'bearish',
                    'desc' => '連續三根黑K逐步走低，空方步步進逼，強勢看跌型態',
                    'strength' => 3,
                    'date' => $cDate,
                ];
            }

            // 島型反轉多 (Island reversal bullish)
            // Gap down before prev2, gap up after curr
            if ($idx >= 3) {
                $prev3 = $quotes[$idx - 3];
                $p3Low = (float)($prev3['low'] ?? 0);
                // Gap down into island: prev3.low > prev2.high
                // Gap up out of island: curr.low > prev.high
                if ($p3Low > $p2High && $cLow > $pHigh) {
                    $patterns[] = [
                        'code' => 'island_reversal_bullish',
                        'name' => '島型反轉多',
                        'type' => 'bullish',
                        'desc' => '股價跳空下跌後又跳空上漲，形成孤島，強烈底部反轉訊號',
                        'strength' => 3,
                        'date' => $cDate,
                    ];
                }
                // 島型反轉空 (Island reversal bearish)
                $p3High = (float)($prev3['high'] ?? 0);
                // Gap up into island: prev2.low > prev3.high
                // Gap down out of island: curr.high < prev.low
                if ($p2Low > $p3High && $cHigh < $pLow) {
                    $patterns[] = [
                        'code' => 'island_reversal_bearish',
                        'name' => '島型反轉空',
                        'type' => 'bearish',
                        'desc' => '股價跳空上漲後又跳空下跌，形成孤島，強烈頂部反轉訊號',
                        'strength' => 3,
                        'date' => $cDate,
                    ];
                }
            }
        }

        return $patterns;
    }

    // ========== PRICE ACTION PATTERNS ==========

    private function detectPriceAction(array $quotes)
    {
        $patterns = [];
        $count = count($quotes);
        if ($count < 5) return $patterns;

        $last = $quotes[$count - 1];
        $lastDate = $last['date'] ?? '';
        $lastClose = (float)($last['close'] ?? 0);
        $lastVolume = (int)($last['volume'] ?? 0);

        // Calculate MA20
        $ma20 = $this->calcMA($quotes, $count - 1, 20);

        // Previous day MA20
        $prevMa20 = ($count >= 2) ? $this->calcMA($quotes, $count - 2, 20) : 0;
        $prevClose = ($count >= 2) ? (float)($quotes[$count - 2]['close'] ?? 0) : 0;

        // 突破前高 (Breakout above recent high, look back 20 days excluding last 3)
        if ($count > 23) {
            $recentHigh = 0;
            $lookStart = max(0, $count - 23);
            $lookEnd = $count - 4; // exclude last 3 days
            for ($i = $lookStart; $i <= $lookEnd; $i++) {
                $h = (float)($quotes[$i]['high'] ?? 0);
                if ($h > $recentHigh) $recentHigh = $h;
            }
            if ($recentHigh > 0 && $lastClose > $recentHigh) {
                $patterns[] = [
                    'code' => 'breakout_high',
                    'name' => '突破前高',
                    'type' => 'bullish',
                    'desc' => '收盤價突破近20日高點（排除近3日），多方突破確認',
                    'strength' => 3,
                    'date' => $lastDate,
                ];
            }
        }

        // 跌破支撐 (Break below recent low)
        if ($count > 23) {
            $recentLow = PHP_FLOAT_MAX;
            $lookStart = max(0, $count - 23);
            $lookEnd = $count - 4;
            for ($i = $lookStart; $i <= $lookEnd; $i++) {
                $l = (float)($quotes[$i]['low'] ?? 0);
                if ($l < $recentLow) $recentLow = $l;
            }
            if ($recentLow < PHP_FLOAT_MAX && $lastClose < $recentLow) {
                $patterns[] = [
                    'code' => 'break_support',
                    'name' => '跌破支撐',
                    'type' => 'bearish',
                    'desc' => '收盤價跌破近20日低點（排除近3日），空方突破確認',
                    'strength' => 3,
                    'date' => $lastDate,
                ];
            }
        }

        // 突破月線 (Cross above MA20)
        if ($ma20 > 0 && $prevMa20 > 0 && $prevClose < $prevMa20 && $lastClose > $ma20) {
            $patterns[] = [
                'code' => 'cross_above_ma20',
                'name' => '突破月線',
                'type' => 'bullish',
                'desc' => '股價由下往上穿越20日均線，短線轉多訊號',
                'strength' => 2,
                'date' => $lastDate,
            ];
        }

        // 跌破月線 (Cross below MA20)
        if ($ma20 > 0 && $prevMa20 > 0 && $prevClose > $prevMa20 && $lastClose < $ma20) {
            $patterns[] = [
                'code' => 'cross_below_ma20',
                'name' => '跌破月線',
                'type' => 'bearish',
                'desc' => '股價由上往下跌破20日均線，短線轉空訊號',
                'strength' => 2,
                'date' => $lastDate,
            ];
        }

        // 量價背離空 (Price up + volume down for 2+ days)
        if ($count >= 3) {
            $divergeCount = 0;
            for ($i = $count - 1; $i >= max(1, $count - 3); $i--) {
                $dayClose = (float)($quotes[$i]['close'] ?? 0);
                $dayPrevClose = (float)($quotes[$i - 1]['close'] ?? 0);
                $dayVol = (int)($quotes[$i]['volume'] ?? 0);
                $dayPrevVol = (int)($quotes[$i - 1]['volume'] ?? 0);
                if ($dayClose > $dayPrevClose && $dayVol < $dayPrevVol) {
                    $divergeCount++;
                } else {
                    break;
                }
            }
            if ($divergeCount >= 2) {
                $patterns[] = [
                    'code' => 'volume_price_divergence_bear',
                    'name' => '量價背離空',
                    'type' => 'bearish',
                    'desc' => '股價連續上漲但成交量持續萎縮，上漲動能不足，留意反轉',
                    'strength' => 2,
                    'date' => $lastDate,
                ];
            }
        }

        // 量縮止跌 (Price down + volume down for 2+ days)
        if ($count >= 3) {
            $shrinkCount = 0;
            for ($i = $count - 1; $i >= max(1, $count - 3); $i--) {
                $dayClose = (float)($quotes[$i]['close'] ?? 0);
                $dayPrevClose = (float)($quotes[$i - 1]['close'] ?? 0);
                $dayVol = (int)($quotes[$i]['volume'] ?? 0);
                $dayPrevVol = (int)($quotes[$i - 1]['volume'] ?? 0);
                if ($dayClose < $dayPrevClose && $dayVol < $dayPrevVol) {
                    $shrinkCount++;
                } else {
                    break;
                }
            }
            if ($shrinkCount >= 2) {
                $patterns[] = [
                    'code' => 'volume_shrink_stop',
                    'name' => '量縮止跌',
                    'type' => 'bullish',
                    'desc' => '股價下跌但成交量持續萎縮，賣壓減輕，可能即將止跌',
                    'strength' => 2,
                    'date' => $lastDate,
                ];
            }
        }

        // 連續創高 (3+ consecutive new highs)
        if ($count >= 4) {
            $newHighCount = 0;
            for ($i = $count - 1; $i >= max(1, $count - 5); $i--) {
                $dayHigh = (float)($quotes[$i]['high'] ?? 0);
                $prevDayHigh = (float)($quotes[$i - 1]['high'] ?? 0);
                if ($dayHigh > $prevDayHigh) {
                    $newHighCount++;
                } else {
                    break;
                }
            }
            if ($newHighCount >= 3) {
                $patterns[] = [
                    'code' => 'consecutive_highs',
                    'name' => '連續創高',
                    'type' => 'bullish',
                    'desc' => '連續' . $newHighCount . '天創新高，多方氣勢如虹',
                    'strength' => 2,
                    'date' => $lastDate,
                ];
            }
        }

        // 連續創低 (3+ consecutive new lows)
        if ($count >= 4) {
            $newLowCount = 0;
            for ($i = $count - 1; $i >= max(1, $count - 5); $i--) {
                $dayLow = (float)($quotes[$i]['low'] ?? 0);
                $prevDayLow = (float)($quotes[$i - 1]['low'] ?? 0);
                if ($dayLow < $prevDayLow) {
                    $newLowCount++;
                } else {
                    break;
                }
            }
            if ($newLowCount >= 3) {
                $patterns[] = [
                    'code' => 'consecutive_lows',
                    'name' => '連續創低',
                    'type' => 'bearish',
                    'desc' => '連續' . $newLowCount . '天創新低，空方氣勢如虹',
                    'strength' => 2,
                    'date' => $lastDate,
                ];
            }
        }

        return $patterns;
    }

    // ========== HELPER METHODS ==========

    /**
     * Detect trend direction before given index.
     *
     * @return string 'up', 'down', or 'flat'
     */
    private function detectTrend(array $quotes, $idx, $lookback = 5)
    {
        if ($idx < $lookback) {
            $lookback = $idx;
        }
        if ($lookback < 2) return 'flat';

        $startClose = (float)($quotes[$idx - $lookback]['close'] ?? 0);
        $endClose = (float)($quotes[$idx - 1]['close'] ?? 0);

        if ($startClose <= 0) return 'flat';

        $changePct = ($endClose - $startClose) / $startClose * 100;

        if ($changePct > 3) return 'up';
        if ($changePct < -3) return 'down';
        return 'flat';
    }

    /**
     * Calculate simple moving average ending at given index.
     *
     * @return float  MA value or 0 if insufficient data
     */
    private function calcMA(array $quotes, $endIdx, $period)
    {
        if ($endIdx < $period - 1) return 0;

        $sum = 0;
        for ($i = $endIdx - $period + 1; $i <= $endIdx; $i++) {
            $sum += (float)($quotes[$i]['close'] ?? 0);
        }
        return $sum / $period;
    }
}
