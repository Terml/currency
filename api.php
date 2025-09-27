<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

function getCurrencyRates($baseCurrency = 'USD')
{
    $fiatRates = getFiatRates();
    if (!$fiatRates['success']) {
        return $fiatRates;
    }

    $cryptoRates = getCryptoRates();

    $allRates = $fiatRates['data']['rates'];

    if ($cryptoRates['success']) {
        $allRates = array_merge($allRates, $cryptoRates['data']);
        saveCryptoCache($cryptoRates['data']);
    } else {
        $cachedCrypto = getCryptoCache();
        if ($cachedCrypto) {
            $allRates = array_merge($allRates, $cachedCrypto);
        }
    }

    return [
        'success' => true,
        'data' => [
            'rates' => $allRates,
            'base' => 'USD'
        ],
        'timestamp' => time()
    ];
}

function saveCryptoCache($cryptoData)
{
    $cacheFile = 'crypto_cache.json';
    $cacheData = [
        'data' => $cryptoData,
        'timestamp' => time()
    ];
    file_put_contents($cacheFile, json_encode($cacheData));
}

function getCryptoCache()
{
    $cacheFile = 'crypto_cache.json';

    if (!file_exists($cacheFile)) {
        return null;
    }

    $cacheContent = file_get_contents($cacheFile);
    $cacheData = json_decode($cacheContent, true);

    if (!$cacheData || !isset($cacheData['timestamp'])) {
        return null;
    }

    if (time() - $cacheData['timestamp'] > 300) {
        return null;
    }

    return $cacheData['data'];
}

function getFiatRates()
{
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
            'error' => 'Не удалось получить данные о валютах'
        ];
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Ошибка при обработке данных о валютах'
        ];
    }

    return [
        'success' => true,
        'data' => $data
    ];
}

function getCryptoRates()
{
    $apis = [
        [
            'url' => 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum&vs_currencies=usd',
            'name' => 'CoinGecko',
            'parser' => 'coingecko'
        ],
        [
            'url' => 'https://api.coinbase.com/v2/exchange-rates?currency=USD',
            'name' => 'Coinbase',
            'parser' => 'coinbase'
        ],
        [
            'url' => 'https://api.binance.com/api/v3/ticker/price?symbols=["BTCUSDT","ETHUSDT"]',
            'name' => 'Binance',
            'parser' => 'binance'
        ]
    ];

    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'Currency App/1.0',
            'method' => 'GET'
        ]
    ]);

    foreach ($apis as $api) {
        $response = @file_get_contents($api['url'], false, $context);

        if ($response !== false) {
            $data = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $cryptoRates = parseCryptoData($data, $api['parser']);

                if (!empty($cryptoRates)) {
                    return [
                        'success' => true,
                        'data' => $cryptoRates,
                        'source' => $api['name']
                    ];
                }
            }
        }
    }

    return [
        'success' => false,
        'error' => 'Не удалось получить данные о криптовалютах ни от одного источника'
    ];
}

function parseCryptoData($data, $parser)
{
    $cryptoRates = [];

    switch ($parser) {
        case 'coingecko':
            if (isset($data['bitcoin']['usd'])) {
                $cryptoRates['BTC'] = $data['bitcoin']['usd'];
            }
            if (isset($data['ethereum']['usd'])) {
                $cryptoRates['ETH'] = $data['ethereum']['usd'];
            }
            break;

        case 'coinbase':
            if (isset($data['data']['rates']['BTC'])) {
                $cryptoRates['BTC'] = 1 / floatval($data['data']['rates']['BTC']);
            }
            if (isset($data['data']['rates']['ETH'])) {
                $cryptoRates['ETH'] = 1 / floatval($data['data']['rates']['ETH']);
            }
            break;

        case 'binance':
            if (isset($data[0]['price']) && strpos($data[0]['symbol'], 'BTC') !== false) {
                $cryptoRates['BTC'] = floatval($data[0]['price']);
            }
            if (isset($data[1]['price']) && strpos($data[1]['symbol'], 'ETH') !== false) {
                $cryptoRates['ETH'] = floatval($data[1]['price']);
            }
            break;
    }

    return $cryptoRates;
}

function formatCurrencyData($data, $baseCurrency = 'USD')
{
    $rates = $data['rates'];

    if ($baseCurrency === 'RUB') {
        $mainCurrencies = [
            'USD' => '🇺🇸 Доллар США',
            'CNY' => '🇨🇳 Китайский юань',
            'BTC' => '₿ Bitcoin',
            'ETH' => 'Ξ Ethereum',
        ];
    } else {
        $mainCurrencies = [
            'RUB' => '🇷🇺 Российский рубль',
            'CNY' => '🇨🇳 Китайский юань',
            'BTC' => '₿ Bitcoin',
            'ETH' => 'Ξ Ethereum',
        ];
    }

    $formattedCurrencies = [];

    foreach ($mainCurrencies as $code => $name) {
        if (isset($rates[$code])) {
            $isCrypto = in_array($code, ['BTC', 'ETH']);

            if ($baseCurrency === 'RUB') {
                if (!isset($rates['RUB'])) {
                    continue;
                }

                if ($code === 'USD') {
                    $inverseRate = round($rates['RUB'], 2);
                } elseif ($isCrypto) {
                    $usdToRub = $rates['RUB'];
                    $cryptoToUsd = $rates[$code];
                    $inverseRate = round($cryptoToUsd * $usdToRub, 2);
                } else {
                    $usdToRub = $rates['RUB'];
                    $currencyToUsd = $rates[$code];

                    $inverseRate = round($usdToRub / $currencyToUsd, 2);
                }
            } else {
                if ($isCrypto) {
                    $inverseRate = round($rates[$code], 2);
                } else {
                    $inverseRate = round(1 / $rates[$code], 6);
                }

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


    $result = getCurrencyRates($baseCurrency);

    if ($result['success']) {
        $formattedData = formatCurrencyData($result['data'], $baseCurrency);

        if (empty($formattedData)) {
            echo json_encode([
                'success' => false,
                'error' => 'Нет данных для отображения валют'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'currencies' => $formattedData,
                'timestamp' => $result['timestamp'],
                'base_currency' => $baseCurrency
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Внутренняя ошибка сервера: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
