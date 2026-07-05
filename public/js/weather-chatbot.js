(function () {
    const root = document.getElementById('weather-chatbot');
    if (!root) {
        return;
    }

    const panel = document.getElementById('weather-chatbot-panel');
    const trigger = document.getElementById('weather-chatbot-trigger');
    const weatherEl = document.getElementById('weather-chatbot-weather');
    const messageEl = document.getElementById('weather-chatbot-message');
    const actionEl = document.getElementById('weather-chatbot-action');
    const kittenEmoji = document.getElementById('weather-kitten-emoji');

    const weatherUrl = root.dataset.weatherUrl;
    const loginUrl = root.dataset.loginUrl;
    const isAuthenticated = root.dataset.isAuthenticated === '1';

    let weatherLoaded = false;
    let openTimeout = null;

    function setOpen(isOpen) {
        panel.hidden = !isOpen;
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        root.classList.toggle('weather-chatbot--open', isOpen);

        if (isOpen && !weatherLoaded) {
            loadWeather();
        }
    }

    function showPanel() {
        clearTimeout(openTimeout);
        setOpen(true);
    }

    function hidePanelDelayed() {
        clearTimeout(openTimeout);
        openTimeout = setTimeout(function () {
            setOpen(false);
        }, 280);
    }

    root.addEventListener('mouseenter', showPanel);
    root.addEventListener('mouseleave', hidePanelDelayed);
    root.addEventListener('focusin', showPanel);
    root.addEventListener('focusout', function (event) {
        if (!root.contains(event.relatedTarget)) {
            hidePanelDelayed();
        }
    });

    trigger.addEventListener('click', function () {
        setOpen(panel.hidden);
    });

    async function loadWeather() {
        weatherLoaded = true;

        const params = new URLSearchParams();

        if (navigator.geolocation) {
            try {
                const position = await new Promise(function (resolve, reject) {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        timeout: 5000,
                        maximumAge: 300000,
                    });
                });
                params.set('lat', String(position.coords.latitude));
                params.set('lon', String(position.coords.longitude));
            } catch (error) {
                // Ville par défaut côté serveur
            }
        }

        try {
            const response = await fetch(weatherUrl + '?' + params.toString(), {
                headers: { Accept: 'application/json' },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Erreur météo');
            }

            renderWeather(data);
        } catch (error) {
            weatherEl.innerHTML = '<span class="weather-chatbot__error">Météo indisponible</span>';
            messageEl.textContent = 'Miaou… Je n\'arrive pas à lire le ciel pour l\'instant. Réessayez plus tard !';
            kittenEmoji.textContent = '😿';
            actionEl.hidden = true;
        }
    }

    function renderWeather(data) {
        const iconUrl = 'https://openweathermap.org/img/wn/' + data.icon + '@2x.png';

        weatherEl.innerHTML =
            '<img src="' + iconUrl + '" alt="" class="weather-chatbot__icon" width="40" height="40">' +
            '<span><strong>' + data.city + '</strong> · ' + Math.round(data.temperature) + ' °C<br>' +
            '<span class="weather-chatbot__desc">' + data.description + '</span></span>';

        messageEl.textContent = data.kittenMessage;
        kittenEmoji.textContent = data.isGoodWeather ? '😺' : '🙀';

        if (data.action) {
            let targetUrl = data.action.url;

            if (!isAuthenticated && targetUrl.indexOf('/recherche') !== -1) {
                targetUrl = loginUrl;
                actionEl.textContent = 'Se connecter pour réserver';
            } else {
                actionEl.textContent = data.action.label;
            }

            actionEl.href = targetUrl;
            actionEl.hidden = false;
        }
    }
})();
