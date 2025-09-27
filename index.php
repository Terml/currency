<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Курсы валют в реальном времени</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <header>
            <div class="currency-selector">
                <div class="currency-toggle">
                    <button class="currency-btn active" data-currency="USD" id="btnUSD" onclick="switchToUSD()">
                        <span class="currency-text">USD</span>
                    </button>
                    <button class="currency-btn" data-currency="RUB" id="btnRUB" onclick="switchToRUB()">
                        <span class="currency-text">RUB</span>
                    </button>
                </div>
            </div>
        </header>

        <div class="converter-section">
            <h2>Конвертер валют</h2>
            <div class="converter-form">
                <div class="inputs-row">
                    <div class="input-group">
                        <label for="amount">Сумма:</label>
                        <input type="number" id="amount" placeholder="Введите сумму" step="0.01" min="0">
                    </div>
                    <div class="input-group">
                        <label for="fromCurrency">Из валюты:</label>
                        <select id="fromCurrency">
                            <option value="USD">USD</option>
                            <option value="RUB">RUB</option>
                            <option value="CNY">CNY</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>
                    <button id="swapCurrencies" class="swap-button" title="Поменять валюты местами">
                        <span class="swap-icon">⇄</span>
                    </button>
                    <div class="input-group">
                        <label for="toCurrency">В валюту:</label>
                        <select id="toCurrency">
                            <option value="RUB">RUB</option>
                            <option value="USD">USD</option>
                            <option value="CNY">CNY</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>
                </div>
                <div class="buttons-row">
                    <button id="convertBtn" class="convert-button">Конвертировать</button>
                </div>
            </div>
            <div class="conversion-result" id="conversionResult" style="display: none;">
                <div class="result-amount" id="resultAmount"></div>
                <div class="result-rate" id="resultRate"></div>
            </div>
        </div>

        <div class="currency-grid" id="currencyGrid">
            <div class="loading">Загрузка данных...</div>
        </div>
    </div>
    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>

</html>