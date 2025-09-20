let currencyData = [];
let updateInterval;
let currentBaseCurrency = 'USD';
let conversionRates = {};

function formatTime(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString('ru-RU', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function getCurrencySymbol(currencyCode) {
    const symbols = {
        'USD': '$',
        'RUB': '₽',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥'
    };
    return symbols[currencyCode] || currencyCode;
}

async function fetchCurrencyRates() {
    try {
        console.log(`Запрашиваем данные о курсах валют для базовой валюты: ${currentBaseCurrency}`);
        
        if (currencyData.length > 0) {
            showUpdateIndicator();
        }
        
        const apiUrl = `api.php?base=${currentBaseCurrency}`;
        console.log('API URL:', apiUrl);
        
        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            const isFirstLoad = currencyData.length === 0;
            
            currencyData = data.currencies;
            
            updateConversionRates(data.currencies, data.base_currency);
            
            if (isFirstLoad) {
                updateCurrencyDisplay();
            } else {
                updateCurrencyRatesOnly();
            }
            
            updateLastUpdateTime(data.timestamp);
            console.log('Данные успешно получены:', data.currencies);
        } else {
            showError(data.error || 'Неизвестная ошибка');
        }
        
    } catch (error) {
        console.error('Ошибка при получении данных:', error);
        showError('Ошибка соединения с сервером');
    }
}

function updateCurrencyDisplay() {
    const currencyGrid = document.getElementById('currencyGrid');
    
    if (currencyData.length === 0) {
        currencyGrid.innerHTML = '<div class="error">Нет данных для отображения</div>';
        return;
    }
    
    let html = '';
    
    currencyData.forEach(currency => {
        html += `
            <div class="currency-card" data-currency="${currency.code}">
                <div class="currency-flag">${currency.name.split(' ')[0]}</div>
                <div class="currency-info">
                    <div class="currency-name">${currency.name}</div>
                    <div class="currency-code">${currency.code}</div>
                </div>
                <div class="currency-rate">
                    <span class="rate-value" data-currency="${currency.code}">${getCurrencySymbol(currency.base)}${formatCurrencyRate(currency.rate)}</span>
                    <span class="rate-base">за 1 ${currency.code}</span>
                </div>
            </div>
        `;
    });
    
    currencyGrid.innerHTML = html;
    
    setTimeout(() => {
        const cards = document.querySelectorAll('.currency-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }, 50);
}

function updateCurrencyRatesOnly() {
    if (currencyData.length === 0) return;
    
    currencyData.forEach(currency => {
        const rateElement = document.querySelector(`[data-currency="${currency.code}"].rate-value`);
        
        if (rateElement) {
            const currencySymbol = getCurrencySymbol(currency.base);
            const oldRate = parseFloat(rateElement.textContent.replace(currencySymbol, ''));
            const newRate = currency.rate;
            
            if (oldRate === newRate) return;
            
            rateElement.classList.add('updating');
            
            if (newRate > oldRate) {
                rateElement.classList.add('rate-up');
                rateElement.classList.remove('rate-down');
            } else if (newRate < oldRate) {
                rateElement.classList.add('rate-down');
                rateElement.classList.remove('rate-up');
            }
            
            setTimeout(() => {
                rateElement.textContent = `${currencySymbol}${formatCurrencyRate(newRate)}`;
                
                showRateChange(currency.code, oldRate, newRate);
            }, 150);
            
            setTimeout(() => {
                rateElement.classList.remove('updating', 'rate-up', 'rate-down');
            }, 1500);
        }
    });
}

function showRateChange(currencyCode, oldRate, newRate) {
    const changePercent = ((newRate - oldRate) / oldRate * 100).toFixed(2);
    const changeSign = newRate > oldRate ? '+' : '';
    
    const changeElement = document.createElement('div');
    changeElement.className = 'rate-change';
    changeElement.textContent = `${changeSign}${changePercent}%`;
    changeElement.style.color = newRate > oldRate ? '#27ae60' : '#e74c3c';
    
    const currencyCard = document.querySelector(`[data-currency="${currencyCode}"]`);
    if (currencyCard) {
        currencyCard.appendChild(changeElement);
        
        setTimeout(() => {
            if (changeElement.parentNode) {
                changeElement.parentNode.removeChild(changeElement);
            }
        }, 3000);
    }
}

function showUpdateIndicator() {
    const lastUpdateElement = document.getElementById('lastUpdate');
    if (lastUpdateElement) {
        lastUpdateElement.innerHTML = `
            Последнее обновление: <span id="updateTime">Обновление...</span>
            <div class="update-spinner"></div>
        `;
    }
}

function hideUpdateIndicator() {
    const updateSpinner = document.querySelector('.update-spinner');
    if (updateSpinner) {
        updateSpinner.remove();
    }
}

function updateCurrencyDescription(baseCurrency) {
    const descriptionElement = document.getElementById('currencyDescription');
    if (descriptionElement) {
        if (baseCurrency === 'RUB') {
            descriptionElement.textContent = 'Показывает сколько рублей стоит 1 единица валюты';
        } else {
            descriptionElement.textContent = 'Показывает сколько долларов стоит 1 единица валюты';
        }
    }
}

function switchBaseCurrency(newBaseCurrency) {
    console.log(`Переключение валюты: ${currentBaseCurrency} → ${newBaseCurrency}`);
    
    if (newBaseCurrency === currentBaseCurrency) {
        console.log('Валюта уже активна');
        return;
    }
    
    currentBaseCurrency = newBaseCurrency;
    
    updateCurrencyDescription(newBaseCurrency);
    updateInfoNote(newBaseCurrency);
    
    const currencyGrid = document.getElementById('currencyGrid');
    if (currencyGrid) {
        currencyGrid.innerHTML = '<div class="loading">Загрузка данных...</div>';
    }

    currencyData = [];
    
    fetchCurrencyRates();
}

function updateInfoNote(baseCurrency) {
    const infoNote = document.getElementById('infoNote');
    if (infoNote) {
        if (baseCurrency === 'RUB') {
            infoNote.innerHTML = '<small>💡 Все курсы показаны в обратном виде: сколько рублей стоит 1 единица валюты</small>';
        } else {
            infoNote.innerHTML = '<small>💡 Все курсы показаны в обратном виде: сколько долларов стоит 1 единица валюты</small>';
        }
    }
}

function updateConversionRates(currencies, baseCurrency) {
    conversionRates = {};
    
    conversionRates[baseCurrency] = 1;
    
    currencies.forEach(currency => {
        conversionRates[currency.code] = currency.rate;
    });
    
    console.log('Курсы для конвертации обновлены:', conversionRates);
}

function convertCurrency(amount, fromCurrency, toCurrency) {
    if (!conversionRates[fromCurrency] || !conversionRates[toCurrency]) {
        throw new Error('Курс валюты не найден');
    }
    
    const amountInBase = amount * conversionRates[fromCurrency];
    const convertedAmount = amountInBase / conversionRates[toCurrency];
    
    return convertedAmount;
}

function performConversion() {
    const amount = parseFloat(document.getElementById('amount').value);
    const fromCurrency = document.getElementById('fromCurrency').value;
    const toCurrency = document.getElementById('toCurrency').value;
    
    if (!amount || amount <= 0) {
        alert('Пожалуйста, введите корректную сумму');
        return;
    }
    
    if (fromCurrency === toCurrency) {
        alert('Выберите разные валюты для конвертации');
        return;
    }
    
    try {
        const convertedAmount = convertCurrency(amount, fromCurrency, toCurrency);
        const rate = conversionRates[fromCurrency] / conversionRates[toCurrency];
        
        showConversionResult(amount, fromCurrency, convertedAmount, toCurrency, rate);
        
    } catch (error) {
        console.error('Ошибка конвертации:', error);
        alert('Ошибка при конвертации. Попробуйте позже.');
    }
}

function formatCurrencyRate(rate) {
    if (rate < 0.01) {
        return rate.toFixed(3);
    } else if (rate < 1) {
        return rate.toFixed(4);
    } else {
        return rate.toFixed(2);
    }
}

function showConversionResult(amount, fromCurrency, convertedAmount, toCurrency, rate) {
    const resultElement = document.getElementById('conversionResult');
    const resultAmountElement = document.getElementById('resultAmount');
    const resultRateElement = document.getElementById('resultRate');
    
    const fromSymbol = getCurrencySymbol(fromCurrency);
    const toSymbol = getCurrencySymbol(toCurrency);
    
    resultAmountElement.textContent = `${toSymbol}${formatCurrencyRate(convertedAmount)}`;
    resultRateElement.textContent = `Курс: 1 ${fromCurrency} = ${formatCurrencyRate(rate)} ${toCurrency}`;
    
    resultElement.style.display = 'block';
    
    resultElement.style.opacity = '0';
    resultElement.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        resultElement.style.transition = 'all 0.5s ease';
        resultElement.style.opacity = '1';
        resultElement.style.transform = 'translateY(0)';
    }, 100);
}

function updateLastUpdateTime(timestamp) {
    const updateTimeElement = document.getElementById('updateTime');
    updateTimeElement.textContent = formatTime(timestamp);
    
    hideUpdateIndicator();
}

function showError(message) {
    const currencyGrid = document.getElementById('currencyGrid');
    currencyGrid.innerHTML = `
        <div class="error">
            <div class="error-icon">⚠️</div>
            <div class="error-message">${message}</div>
            <button onclick="fetchCurrencyRates()" class="retry-button">Повторить</button>
        </div>
    `;
}

function startAutoUpdate() {
    updateInterval = setInterval(fetchCurrencyRates, 30000);
    console.log('Автоматическое обновление запущено (каждые 30 секунд)');
}

function stopAutoUpdate() {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
        console.log('Автоматическое обновление остановлено');
    }
}

function toggleAutoUpdate() {
    if (updateInterval) {
        stopAutoUpdate();
    } else {
        startAutoUpdate();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Страница загружена, инициализируем приложение...');
    
    fetchCurrencyRates();
    
    startAutoUpdate();
    
    initCurrencyToggle();
    
    const convertBtn = document.getElementById('convertBtn');
    if (convertBtn) {
        convertBtn.addEventListener('click', performConversion);
    }
    
    const amountInput = document.getElementById('amount');
    if (amountInput) {
        amountInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performConversion();
            }
        });
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('retry-button')) {
            console.log('Нажата кнопка повторить, текущая валюта:', currentBaseCurrency);
            fetchCurrencyRates();
        }
    });
});

function initCurrencyToggle() {
    console.log('Инициализируем переключатель валют...');
    
    const btnUSD = document.getElementById('btnUSD');
    const btnRUB = document.getElementById('btnRUB');
    
    console.log('btnUSD найден:', !!btnUSD);
    console.log('btnRUB найден:', !!btnRUB);
    
    if (btnUSD) {
        btnUSD.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Нажата кнопка USD');
            switchToCurrency('USD');
        });
    }
    
    if (btnRUB) {
        btnRUB.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Нажата кнопка RUB');
            switchToCurrency('RUB');
        });
    }
}

function switchToCurrency(currency) {
    console.log(`Переключаем на валюту: ${currency}`);

    const btnUSD = document.getElementById('btnUSD');
    const btnRUB = document.getElementById('btnRUB');
    
    if (currency === 'USD') {
        if (btnUSD) btnUSD.classList.add('active');
        if (btnRUB) btnRUB.classList.remove('active');
    } else if (currency === 'RUB') {
        if (btnUSD) btnUSD.classList.remove('active');
        if (btnRUB) btnRUB.classList.add('active');
    }
    
    switchBaseCurrency(currency);
}

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoUpdate();
    } else {
        fetchCurrencyRates();
        startAutoUpdate();
    }
});

window.addEventListener('error', function(e) {
    console.error('JavaScript ошибка:', e.error);
    showError('Произошла ошибка в приложении');
});

window.switchToUSD = function() {
    console.log('Глобальная функция: переключение на USD');
    switchToCurrency('USD');
};

window.switchToRUB = function() {
    console.log('Глобальная функция: переключение на RUB');
    switchToCurrency('RUB');
};

window.debugCurrency = {
    getData: () => currencyData,
    fetch: fetchCurrencyRates,
    startUpdate: startAutoUpdate,
    stopUpdate: stopAutoUpdate,
    switchToUSD: () => switchToCurrency('USD'),
    switchToRUB: () => switchToCurrency('RUB')
};
