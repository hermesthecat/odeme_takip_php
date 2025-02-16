<?php
require_once __DIR__ . '/../config/config.php';

class ExchangeRate {
    private $conn;
    private $table = 'exchange_rates';
    private $api_url;
    private $api_key;

    public function __construct($db) {
        $this->conn = $db;
        $this->api_url = EXCHANGE_RATE_API_URL;
        $this->api_key = EXCHANGE_RATE_API_KEY;
    }

    public function updateRates() {
        try {
            // Fetch new rates from API
            $response = file_get_contents($this->api_url . "?source=TRY&access_key=" . $this->api_key);
            $data = json_decode($response, true);

            if(!$data || !isset($data['quotes'])) {
                throw new Exception("Invalid API response");
            }

            $this->conn->beginTransaction();

            // Prepare update statement
            $query = "INSERT INTO " . $this->table . " (currency, rate) VALUES (:currency, :rate) 
                     ON DUPLICATE KEY UPDATE rate = :rate, updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->conn->prepare($query);

            // Update each currency rate
            foreach(['USD', 'EUR', 'GBP'] as $currency) {
                $rate_key = 'TRY' . $currency;
                if(!isset($data['quotes'][$rate_key])) {
                    continue;
                }

                $rate = 1 / $data['quotes'][$rate_key]; // Convert to TRY base
                
                $stmt->bindParam(":currency", $currency);
                $stmt->bindParam(":rate", $rate);
                $stmt->execute();
            }

            // Always ensure TRY is set to 1
            $stmt->bindValue(":currency", 'TRY');
            $stmt->bindValue(":rate", 1.0);
            $stmt->execute();

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            if($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function getRates() {
        $query = "SELECT currency, rate, updated_at FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $rates = [];
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rates[$row['currency']] = [
                'rate' => $row['rate'],
                'updated_at' => $row['updated_at']
            ];
        }

        return $rates;
    }

    public function getRate($currency) {
        $query = "SELECT rate FROM " . $this->table . " WHERE currency = :currency";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":currency", $currency);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return floatval($row['rate']);
        }
        return null;
    }

    public function convert($amount, $from_currency, $to_currency) {
        if($from_currency === $to_currency) {
            return $amount;
        }

        $from_rate = $this->getRate($from_currency);
        $to_rate = $this->getRate($to_currency);

        if($from_rate === null || $to_rate === null) {
            return null;
        }

        // Convert to TRY first (base currency), then to target currency
        return ($amount / $from_rate) * $to_rate;
    }

    public function needsUpdate() {
        $query = "SELECT MAX(updated_at) as last_update FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(!$row['last_update']) {
                return true;
            }

            $last_update = new DateTime($row['last_update']);
            $now = new DateTime();
            
            // Check if last update was more than 1 hour ago
            $diff = $now->diff($last_update);
            $hours = $diff->h + ($diff->days * 24);
            
            return $hours >= 1;
        }

        return true;
    }

    public function validateCurrency($currency) {
        return in_array($currency, ['TRY', 'USD', 'EUR', 'GBP']);
    }

    public function getLastUpdateTime() {
        $query = "SELECT MAX(updated_at) as last_update FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['last_update'] ? new DateTime($row['last_update']) : null;
        }

        return null;
    }

    public function formatAmount($amount, $currency) {
        $formats = [
            'TRY' => ['decimals' => 2, 'symbol' => '₺'],
            'USD' => ['decimals' => 2, 'symbol' => '$'],
            'EUR' => ['decimals' => 2, 'symbol' => '€'],
            'GBP' => ['decimals' => 2, 'symbol' => '£']
        ];

        if(!isset($formats[$currency])) {
            return number_format($amount, 2) . ' ' . $currency;
        }

        $format = $formats[$currency];
        return $format['symbol'] . number_format($amount, $format['decimals']);
    }

    public function getCurrencySymbol($currency) {
        $symbols = [
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£'
        ];

        return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
    }

    public function getCurrentRatesFormatted() {
        $rates = $this->getRates();
        $formatted = [];

        foreach($rates as $currency => $data) {
            if($currency === 'TRY') continue;

            $formatted[$currency] = [
                'symbol' => $this->getCurrencySymbol($currency),
                'rate' => $this->formatAmount(1 / $data['rate'], $currency),
                'updated_at' => (new DateTime($data['updated_at']))->format('Y-m-d H:i:s')
            ];
        }

        return $formatted;
    }

    private function fallbackToStaticRates() {
        $static_rates = [
            'TRY' => 1.000000,
            'USD' => 0.032787,
            'EUR' => 0.030120,
            'GBP' => 0.025840
        ];

        $query = "INSERT INTO " . $this->table . " (currency, rate) VALUES (:currency, :rate) 
                 ON DUPLICATE KEY UPDATE rate = :rate";
        
        $stmt = $this->conn->prepare($query);

        foreach($static_rates as $currency => $rate) {
            $stmt->bindParam(":currency", $currency);
            $stmt->bindParam(":rate", $rate);
            $stmt->execute();
        }
    }
}
