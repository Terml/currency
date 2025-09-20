<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

function getCurrencyRates($baseCurrency = 'USD') {
    $apiUrl = 'https://api.exchangerate-api.com/v4/latest/USD';
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Currency App/1.0'
        ]
    ]);
    
    $response = @file_get_contents($apiUrl, false, $context);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Не удалось получить данные с сервера'
        ];
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Ошибка при обработке данных'
        ];
    }
    
    return [
        'success' => true,
        'data' => $data,
        'timestamp' => time()
    ];
}

function formatCurrencyData($data, $baseCurrency = 'USD') {
    $rates = $data['rates'];
    
    if ($baseCurrency === 'RUB') {
        $mainCurrencies = [
            'USD' => '🇺🇸 Доллар США',
            'CNY' => '🇨🇳 Китайский юань',
        ];
    } else {
        $mainCurrencies = [
            'RUB' => '🇷🇺 Российский рубль',
            'CNY' => '🇨🇳 Китайский юань',
        ];
    }
    
    $formattedCurrencies = [];
    
    foreach ($mainCurrencies as $code => $name) {
        if (isset($rates[$code])) {
            if ($baseCurrency === 'RUB') {
                if ($code === 'USD') {
                    $inverseRate = round($rates['RUB'], 2); // 1 USD = X RUB
                } else {
                    $usdToRub = $rates['RUB']; // 1 USD = X RUB
                    $currencyToUsd = $rates[$code]; // 1 USD = X валюты
                    
                    $inverseRate = round($usdToRub / $currencyToUsd, 2);
                }
            } else {
                $inverseRate = round(1 / $rates[$code], 6);
                
                if ($inverseRate < 0.01) {
                    $inverseRate = round($inverseRate, 3);
                }
            }
            
            $formattedCurrencies[] = [
                'code' => $code,
                'name' => $name,
                'rate' => $inverseRate,
                'base' => $baseCurrency,
                'original_rate' => $rates[$code]
            ];
        }
    }
    
    return $formattedCurrencies;
}

try {
    $baseCurrency = isset($_GET['base']) ? strtoupper($_GET['base']) : 'USD';
    
    if (!in_array($baseCurrency, ['USD', 'RUB'])) {
        $baseCurrency = 'USD';
    }
    
    error_log("API запрос для базовой валюты: " . $baseCurrency);
    
    $result = getCurrencyRates($baseCurrency);
    
    if ($result['success']) {
        $formattedData = formatCurrencyData($result['data'], $baseCurrency);
        
        echo json_encode([
            'success' => true,
            'currencies' => $formattedData,
            'timestamp' => $result['timestamp'],
            'base_currency' => $baseCurrency
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Внутренняя ошибка сервера: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
