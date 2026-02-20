(function () {
    var list = document.getElementById('chat-page-messages');
    var input = document.getElementById('chat-page-input');
    var sendBtn = document.getElementById('chat-page-send');
    var baseUrl = document.body.getAttribute('data-app-url') || '';
    var pageEl = document.getElementById('chat-page');
    var currentLang = 'ru';
    var welcomeMsgEl = null;

    if (!list || !input || !sendBtn || !pageEl) return;

    function scrollToBottom() {
        list.scrollTop = list.scrollHeight;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function linkify(text) {
        var escaped = escapeHtml(text);
        escaped = escaped.replace(/\[([^\]]*)\]\((https?:\/\/[^\)\s]+)\)/g, function (_, label, url) {
            return '<a href="' + escapeHtml(url) + '" target="_blank" rel="noopener noreferrer" class="chat-page__link">' + label + '</a>';
        });
        escaped = escaped.replace(/(https?:\/\/[^\s<>"']+)/gi, function (url, _p1, offset, fullString) {
            var before = fullString.substring(0, offset);
            if (offset >= 6 && before.substring(offset - 6, offset) === 'href="') return url;
            return '<a href="' + url + '" target="_blank" rel="noopener noreferrer" class="chat-page__link">' + url + '</a>';
        });
        return escaped;
    }

    function addMessage(role, text) {
        var msgDiv = document.createElement('div');
        msgDiv.className = 'chat-page__msg chat-page__msg--' + role;
        if (role === 'assistant') {
            var row = document.createElement('div');
            row.className = 'chat-page__row chat-page__row--assistant';
            var avatar = document.createElement('div');
            avatar.className = 'chat-page__avatar chat-page__avatar--bot';
            var img = document.createElement('img');
            img.src = (baseUrl || '') + '/images/scroll-avatar.png';
            img.alt = 'Scroll';
            img.className = 'chat-page__avatar-img';
            img.onerror = function () {
                avatar.classList.add('chat-page__avatar--icon');
                avatar.innerHTML = '<i class="fas fa-robot" aria-hidden="true"></i>';
            };
            avatar.appendChild(img);
            msgDiv.innerHTML = linkify(text);
            row.appendChild(avatar);
            row.appendChild(msgDiv);
            list.appendChild(row);
            scrollToBottom();
            return msgDiv;
        }
        msgDiv.textContent = text;
        list.appendChild(msgDiv);
        scrollToBottom();
        return msgDiv;
    }

    function setLoading(on) {
        sendBtn.disabled = on;
    }

    var typingEl = null;

    function showTypingIndicator() {
        if (typingEl && typingEl.parentNode) typingEl.parentNode.removeChild(typingEl);
        var row = document.createElement('div');
        row.className = 'chat-page__row chat-page__row--assistant chat-page__typing';
        row.setAttribute('aria-live', 'polite');
        var avatar = document.createElement('div');
        avatar.className = 'chat-page__avatar chat-page__avatar--bot';
        var img = document.createElement('img');
        img.src = (baseUrl || '') + '/images/scroll-avatar.png';
        img.alt = 'Scroll';
        img.className = 'chat-page__avatar-img';
        img.onerror = function () {
            avatar.classList.add('chat-page__avatar--icon');
            avatar.innerHTML = '<i class="fas fa-robot" aria-hidden="true"></i>';
        };
        avatar.appendChild(img);
        var bubble = document.createElement('div');
        bubble.className = 'chat-page__typing-bubble';
        bubble.innerHTML = '<span class="chat-page__typing-dot"></span><span class="chat-page__typing-dot"></span><span class="chat-page__typing-dot"></span>';
        row.appendChild(avatar);
        row.appendChild(bubble);
        list.appendChild(row);
        typingEl = row;
        scrollToBottom();
    }

    function hideTypingIndicator() {
        if (typingEl && typingEl.parentNode) {
            typingEl.parentNode.removeChild(typingEl);
            typingEl = null;
        }
    }

    function setPlaceholder() {
        var key = 'data-placeholder-' + currentLang;
        input.placeholder = (input.getAttribute(key) || input.placeholder) || 'Сообщение...';
    }

    function updateWelcomeMessage() {
        var welcomeKey = 'data-welcome-' + currentLang;
        var welcomeText = pageEl.getAttribute(welcomeKey) || pageEl.getAttribute('data-welcome-ru') || '';
        if (welcomeMsgEl && welcomeText) {
            welcomeMsgEl.innerHTML = linkify(welcomeText);
        }
    }

    var welcomeKey = 'data-welcome-' + currentLang;
    var welcomeText = pageEl.getAttribute(welcomeKey) || pageEl.getAttribute('data-welcome-ru') || '';
    if (welcomeText) {
        welcomeMsgEl = addMessage('assistant', welcomeText);
    }

    var langBtns = document.querySelectorAll('.chat-page__lang-btn');
    langBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var lang = this.getAttribute('data-lang');
            if (!lang) return;
            currentLang = lang;
            langBtns.forEach(function (b) {
                b.classList.remove('is-active');
                b.setAttribute('aria-pressed', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-pressed', 'true');
            setPlaceholder();
            updateWelcomeMessage();
        });
    });
    setPlaceholder();

    function send() {
        var msg = input.value.trim();
        if (!msg) return;
        addMessage('user', msg);
        input.value = '';
        setLoading(true);
        showTypingIndicator();
        var xhr = new XMLHttpRequest();
        xhr.open('POST', baseUrl + '/chat/send');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        var csrfParam = (document.querySelector('meta[name="csrf-param"]') || {}).content;
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content;
        var body = 'message=' + encodeURIComponent(msg) + '&language=' + encodeURIComponent(currentLang);
        if (csrfParam && csrfToken) body = csrfParam + '=' + encodeURIComponent(csrfToken) + '&' + body;
        xhr.onload = function () {
            hideTypingIndicator();
            setLoading(false);
            if (xhr.status === 403) {
                addMessage('assistant', 'Войдите в систему.');
                return;
            }
            var res;
            try { res = JSON.parse(xhr.responseText); } catch (e) { res = {}; }
            if (res.ok && res.text) {
                addMessage('assistant', res.text);
            } else {
                addMessage('assistant', res.error || 'Ошибка.');
            }
        };
        xhr.onerror = function () {
            hideTypingIndicator();
            setLoading(false);
            addMessage('assistant', 'Ошибка соединения.');
        };
        xhr.send(body);
    }

    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            send();
        }
    });
})();
