<?php

/**
 * @author A. Kerem Gök
 * Döviz kuru API endpoint'i
 */

header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Oturum açmanız gerekiyor']));
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            // Cache kontrolü
            $cacheFile = '../cache/exchange_rates.json';
            $cacheTime = 3600; // 1 saat

            if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
                // Cache'den oku
                $rates = json_decode(file_get_contents($cacheFile), true);
            } else {
                // API'den yeni veri al
                $rates = getExchangeRates();

                // Cache klasörünü kontrol et
                if (!is_dir('../cache')) {
                    mkdir('../cache', 0777, true);
                }

                // Cache'e kaydet
                file_put_contents($cacheFile, json_encode($rates));
            }

            if (empty($rates)) {
                throw new Exception('Döviz kurları alınamadı');
            }

            // Desteklenen para birimleri
            $supportedCurrencies = [
                'USD' => 'Amerikan Doları',
                'EUR' => 'Euro',
                'GBP' => 'İngiliz Sterlini',
                'TRY' => 'Türk Lirası'
            ];

            // Sadece desteklenen para birimlerini filtrele
            $filteredRates = array_intersect_key($rates, $supportedCurrencies);

            echo json_encode([
                'success' => true,
                'data' => [
                    'rates' => $filteredRates,
                    'currencies' => $supportedCurrencies,
                    'last_update' => date('Y-m-d H:i:s', filemtime($cacheFile) ?? time())
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'POST':
        // Para birimi çevirisi
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            checkToken($data['csrf_token'] ?? '');

            if (!isset($data['amount'], $data['from'], $data['to'])) {
                throw new Exception('Geçersiz parametreler');
            }

            $rates = getExchangeRates();

            if (empty($rates)) {
                throw new Exception('Döviz kurları alınamadı');
            }

            // TRY'den hedef para birimine çevir
            if ($data['from'] === 'TRY') {
                $result = $data['amount'] / $rates[$data['to']];
            }
            // Hedef para biriminden TRY'ye çevir
            else if ($data['to'] === 'TRY') {
                $result = $data['amount'] * $rates[$data['from']];
            }
            // Farklı para birimleri arası çeviri
            else {
                $tryAmount = $data['amount'] * $rates[$data['from']]; // Önce TRY'ye çevir
                $result = $tryAmount / $rates[$data['to']]; // Sonra hedef para birimine
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'amount' => $data['amount'],
                    'from' => $data['from'],
                    'to' => $data['to'],
                    'result' => round($result, 2)
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Geçersiz metod']);
        break;
}
