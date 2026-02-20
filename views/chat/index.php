<?php

use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = 'Scroll';
$this->params['breadcrumbs'][] = $this->title;

$welcomeRu = 'Добро пожаловать! Я интеллектуальный помощник Scroll. Я помогаю разобраться с функционалом KU Track и отвечаю на вопросы пользователей. Задайте ваш вопрос ниже.';
$welcomeKk = 'Қош келдіңіз! Мен Scroll интеллектуалды көмекшісімін. Мен KU Track функционалымен танысуға көмектесемін және пайдаланушылардың сұрақтарына жауап беремін. Төменде сұрағыңызды қойыңыз.';
$welcomeEn = 'Welcome! I am Scroll, an intelligent assistant. I help you understand the functionality of KU Track and answer user questions. Ask your question below.';
?>
<div class="chat-page" id="chat-page" data-welcome-ru="<?= Html::encode($welcomeRu) ?>" data-welcome-kk="<?= Html::encode($welcomeKk) ?>" data-welcome-en="<?= Html::encode($welcomeEn) ?>">
    <h1 class="chat-page__title"><?= Html::encode($this->title) ?></h1>

    <div class="chat-page__box">
        <div class="chat-page__lang-bar">
            <span class="chat-page__lang-label">Язык:</span>
            <button type="button" class="chat-page__lang-btn is-active" data-lang="ru" aria-pressed="true">Русский</button>
            <button type="button" class="chat-page__lang-btn" data-lang="kk" aria-pressed="false">Қазақша</button>
            <button type="button" class="chat-page__lang-btn" data-lang="en" aria-pressed="false">English</button>
        </div>
        <div id="chat-page-messages" class="chat-page__messages" role="log" aria-label="Чат"></div>
        <div class="chat-page__footer">
            <input type="text" id="chat-page-input" class="chat-page__input" placeholder="Сообщение..." maxlength="2000" aria-label="Введите сообщение" data-placeholder-ru="Сообщение..." data-placeholder-kk="Хабарласыңыз..." data-placeholder-en="Message...">
            <button type="button" id="chat-page-send" class="chat-page__send" aria-label="Отправить">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>
<?php
$this->registerJsFile('@web/js/chat-page.js', ['position' => \yii\web\View::POS_END]);
$this->registerCss("
.chat-page { max-width: 800px; margin: 0 auto; }
.chat-page__title { margin-bottom: var(--space-lg); }
.chat-page__box { border: 1px solid var(--border); border-radius: var(--radius-lg); background: var(--surface); overflow: hidden; display: flex; flex-direction: column; height: 70vh; min-height: 430px; }
.chat-page__messages { flex: 1; overflow-y: auto; padding: var(--space-md); display: flex; flex-direction: column; gap: var(--space-sm); }
.chat-page__msg { max-width: 85%; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-md); word-break: break-word; white-space: pre-wrap; font-size: var(--font-size-sm); }
.chat-page__msg--user { align-self: flex-end; background: var(--primary); color: #fff; }
.chat-page__msg--assistant { align-self: flex-start; background: var(--surface-2); color: var(--text); border: 1px solid var(--border); }
.chat-page__row { display: flex; align-items: flex-start; gap: var(--space-sm); max-width: 85%; }
.chat-page__row--assistant { align-self: flex-start; }
.chat-page__avatar { flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--primary); }
.chat-page__avatar-img { width: 100%; height: 100%; object-fit: cover; }
.chat-page__avatar--icon { font-size: 1.25rem; }
.chat-page__typing-bubble { padding: 12px 16px; border-radius: var(--radius-md); background: var(--surface-2); border: 1px solid var(--border); display: flex; align-items: center; gap: 4px; }
.chat-page__typing-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--muted, #8E939B); animation: chat-page-typing 1.4s ease-in-out infinite both; }
.chat-page__typing-dot:nth-child(1) { animation-delay: 0s; }
.chat-page__typing-dot:nth-child(2) { animation-delay: 0.2s; }
.chat-page__typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes chat-page-typing { 0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; } 40% { transform: scale(1.2); opacity: 1; } }
.chat-page__msg a.chat-page__link { color: var(--primary, #2563eb); text-decoration: underline; word-break: break-all; }
.chat-page__msg a.chat-page__link:hover { text-decoration: none; }
.chat-page__footer { flex-shrink: 0; display: flex; gap: var(--space-xs); padding: var(--space-md); border-top: 1px solid var(--border); }
.chat-page__input { flex: 1; padding: var(--space-sm) var(--space-md); border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface-2); color: var(--text); }
.chat-page__input:focus { outline: none; border-color: var(--primary); }
.chat-page__send { width: 44px; height: 44px; padding: 0; border: none; border-radius: var(--radius-sm); background: var(--primary); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.chat-page__send:hover:not(:disabled) { background: var(--primary-hover); }
.chat-page__send:disabled { opacity: 0.6; cursor: not-allowed; }
.chat-page__lang-bar { flex-shrink: 0; display: flex; align-items: center; gap: var(--space-sm); padding: var(--space-sm) var(--space-md); border-bottom: 1px solid var(--border); background: var(--surface-2); }
.chat-page__lang-label { font-size: var(--font-size-sm); color: var(--muted); margin-right: var(--space-xs); }
.chat-page__lang-btn { padding: 6px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface); color: var(--text); font-size: var(--font-size-sm); cursor: pointer; }
.chat-page__lang-btn:hover { border-color: var(--primary); color: var(--primary); }
.chat-page__lang-btn.is-active { background: var(--primary); color: #fff; border-color: var(--primary); }
");
