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
            <h1>💱 Курсы валют в реальном времени</h1>
            <div class="currency-selector">
                <label>Базовая валюта:</label>
        <div class="currency-toggle">
            <button class="currency-btn active" data-currency="USD" id="btnUSD" onclick="switchToUSD()">
                <span class="currency-emoji">🇺🇸</span>
                <span class="currency-text">USD</span>
            </button>
            <button class="currency-btn" data-currency="RUB" id="btnRUB" onclick="switchToRUB()">
                <span class="currency-emoji">🇷🇺</span>
                <span class="currency-text">RUB</span>
            </button>
        </div>
            </div>
        </header>
        
        <div class="converter-section">
            <h2>🔄 Конвертер валют</h2>
            <div class="converter-form">
                <div class="input-group">
                    <label for="amount">Сумма:</label>
                    <input type="number" id="amount" placeholder="Введите сумму" step="0.01" min="0">
                </div>
                <div class="input-group">
                    <label for="fromCurrency">Из валюты:</label>
                    <select id="fromCurrency">
                        <option value="USD">🇺🇸 USD</option>
                        <option value="RUB">🇷🇺 RUB</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="toCurrency">В валюту:</label>
                    <select id="toCurrency">
                        <option value="RUB">🇷🇺 RUB</option>
                        <option value="USD">🇺🇸 USD</option>
                    </select>
                </div>
                <button id="convertBtn" class="convert-button">Конвертировать</button>
            </div>
            <div class="conversion-result" id="conversionResult" style="display: none;">
                <div class="result-amount" id="resultAmount"></div>
                <div class="result-rate" id="resultRate"></div>
            </div>
        </div>

        <div class="currency-grid" id="currencyGrid">
            <div class="loading">Загрузка данных...</div>
        </div>
        
        <div class="last-update" id="lastUpdate">
            Последнее обновление: <span id="updateTime">-</span>
        </div>
        
        <div class="info-note" id="infoNote">
            <small>💡 Все курсы показаны в обратном виде: сколько долларов стоит 1 единица валюты</small>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnUSD = document.getElementById('btnUSD');
            const btnRUB = document.getElementById('btnRUB');
            console.log('Проверка кнопок:');
            console.log('btnUSD:', btnUSD);
            console.log('btnRUB:', btnRUB);
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
