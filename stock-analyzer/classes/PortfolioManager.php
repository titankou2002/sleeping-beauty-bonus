<?php
class PortfolioManager
{
    private $file;

    public function __construct()
    {
        $this->file = DATA_DIR . '/portfolio.json';
    }

    public function getWatchlist(): array
    {
        $data = $this->load();
        return $data['watchlist'] ?? [];
    }

    public function addToWatchlist(string $stockId, string $market = 'tw', float $entryPrice = 0): array
    {
        $data = $this->load();
        $key = strtoupper($stockId);

        $data['watchlist'][$key] = [
            'stockId' => $key,
            'market' => $market,
            'entryPrice' => $entryPrice,
            'addedAt' => date('Y-m-d H:i:s'),
            'alerts' => true,
        ];

        $this->save($data);
        return $data['watchlist'][$key];
    }

    public function removeFromWatchlist(string $stockId): bool
    {
        $data = $this->load();
        $key = strtoupper($stockId);
        if (!isset($data['watchlist'][$key])) return false;
        unset($data['watchlist'][$key]);
        $this->save($data);
        return true;
    }

    public function updateEntryPrice(string $stockId, float $price): bool
    {
        $data = $this->load();
        $key = strtoupper($stockId);
        if (!isset($data['watchlist'][$key])) return false;
        $data['watchlist'][$key]['entryPrice'] = $price;
        $this->save($data);
        return true;
    }

    public function getSettings(): array
    {
        $data = $this->load();
        return $data['settings'] ?? [
            'totalCapital' => 1000000,
            'maxRiskPct' => 2,
            'stopLossPct' => STOP_LOSS_PCT,
            'telegramChatId' => TELEGRAM_CHAT_ID,
            'alertEmail' => ALERT_EMAIL,
            'dailyAlertTime' => '18:00',
        ];
    }

    public function updateSettings(array $settings): void
    {
        $data = $this->load();
        $data['settings'] = array_merge($this->getSettings(), $settings);
        $this->save($data);
    }

    public function getAlertHistory(): array
    {
        $data = $this->load();
        return array_slice($data['alertHistory'] ?? [], -50);
    }

    public function logAlert(string $stockId, string $type, string $message): void
    {
        $data = $this->load();
        if (!isset($data['alertHistory'])) $data['alertHistory'] = [];
        $data['alertHistory'][] = [
            'stockId' => $stockId,
            'type' => $type,
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
        ];
        if (count($data['alertHistory']) > 200) {
            $data['alertHistory'] = array_slice($data['alertHistory'], -100);
        }
        $this->save($data);
    }

    private function load(): array
    {
        if (!is_file($this->file)) return ['watchlist' => [], 'settings' => [], 'alertHistory' => []];
        return json_decode(file_get_contents($this->file), true) ?: [];
    }

    private function save(array $data): void
    {
        file_put_contents($this->file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
