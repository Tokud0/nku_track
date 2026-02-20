<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\ChatbotFile;

class ChatController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['send' => ['post']],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionSend()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $message = trim(Yii::$app->request->post('message', ''));
        if ($message === '') {
            return ['ok' => false, 'error' => 'Пустое сообщение.'];
        }
        $key = Yii::$app->params['openaiApiKey'] ?? '';
        $key = is_string($key) ? trim($key) : '';
        if ($key === '') {
            return ['ok' => false, 'error' => 'Не настроен API ключ OpenAI. Задайте OPENAI_API_KEY в файле .env (ключ: https://platform.openai.com/account/api-keys).'];
        }
        $language = Yii::$app->request->post('language', 'ru');
        $language = in_array($language, ['ru', 'kk', 'en'], true) ? $language : 'ru';

        $langInstructions = [
            'ru' => 'Отвечай только на русском языке. Если в базе знаний текст на другом языке, пересказывай и отвечай на русском.',
            'kk' => 'Respond only in Kazakh (Қазақ тілінде жауап бер). If the knowledge base is in another language, answer in Kazakh.',
            'en' => 'Respond only in English. If the knowledge base is in another language, answer in English.',
        ];

        $offTopicPhrases = [
            'ru' => 'Извините, вопрос не по теме. Я отвечаю только на вопросы по KU Track.',
            'kk' => 'Кешіріңіз, сұрақ тақырыпқа жатпайды. Мен тек KU Track бойынша сұрақтарға жауап беремін.',
            'en' => 'Sorry, that question is off-topic. I only answer questions about KU Track.',
        ];

        $whoAndWhat = [
            'ru' => "На вопросы «кто ты», «что умеешь», «твои возможности», «расскажи о себе» и близкие по смыслу отвечай кратко: ты — интеллектуальный помощник Scroll по KU Track; помогаешь разобраться с функционалом системы и отвечаешь на вопросы пользователей. В 1–2 предложения.",
            'kk' => "«Кімсің», «не білесің», «мүмкіндіктерің» және мағынасы жақын сұрақтарға қысқа жауап бер: сен — KU Track бойынша Scroll интеллектуалды көмекшісісің; жүйе функционалымен танысуға көмектесесің және пайдаланушылардың сұрақтарына жауап бересің. 1–2 сөйлемде.",
            'en' => "To questions like «who are you», «what can you do», «your capabilities», «tell me about yourself» and similar, answer briefly: you are Scroll, an intelligent assistant for KU Track; you help with the system’s functionality and answer user questions. In 1–2 sentences.",
        ];

        $context = ChatbotFile::getContextForPrompt();
        $systemContent = "Ты — помощник Scroll по KU Track. " . $langInstructions[$language] . " Отвечай кратко и по делу.\n\n";
        $systemContent .= "ПРАВИЛА:\n";
        $systemContent .= "1. Единственный источник ответов — база знаний ниже. Не используй общие знания.\n";
        $systemContent .= "2. Понимай вопросы по смыслу: синонимы, перефразировка, близкие формулировки — сопоставляй с содержанием базы и отвечай по ней.\n";
        $systemContent .= "3. " . $whoAndWhat[$language] . "\n";
        $systemContent .= "4. Если вопрос не относится к содержанию базы (общие знания, посторонние темы — например «кто такой Наполеон»), ответь строго одной фразой: «" . $offTopicPhrases[$language] . "»\n";
        $systemContent .= "5. Если ответ есть в базе (в т.ч. при перефразировке или синонимах) — отвечай только на основе этой информации.\n\n";
        if ($context !== '') {
            $systemContent .= "БАЗА ЗНАНИЙ:\n\n" . $context;
        } else {
            $systemContent .= "БАЗА ЗНАНИЙ пуста. На любой вопрос по сути отвечай: «" . $offTopicPhrases[$language] . "»";
        }
        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemContent],
                ['role' => 'user', 'content' => $message],
            ],
            'max_tokens' => 1024,
        ];
        $json = json_encode($payload);
        $response = self::openaiRequest($key, $json);
        if ($response['error'] !== null) {
            return ['ok' => false, 'error' => $response['error']];
        }
        $data = json_decode($response['body'], true);
        if (isset($data['error']['message'])) {
            return ['ok' => false, 'error' => $data['error']['message']];
        }
        $text = $data['choices'][0]['message']['content'] ?? '';
        return ['ok' => true, 'text' => trim($text)];
    }

    /**
     * Запрос к OpenAI API (cURL с SSL или stream_context).
     * @param string $apiKey
     * @param string $jsonBody
     * @return array ['body' => string, 'error' => string|null]
     */
    private static function openaiRequest($apiKey, $jsonBody)
    {
        $apiKey = is_string($apiKey) ? trim($apiKey) : '';
        if ($apiKey === '') {
            return ['body' => '', 'error' => 'API ключ не задан. Укажите OPENAI_API_KEY в .env.'];
        }
        $url = 'https://api.openai.com/v1/chat/completions';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $body = curl_exec($ch);
            $errNo = curl_errno($ch);
            $errStr = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($errNo) {
                return ['body' => '', 'error' => 'Ошибка соединения: ' . $errStr . ' (код ' . $errNo . '). Проверьте интернет или доступ к api.openai.com.'];
            }
            if ($body === false) {
                return ['body' => '', 'error' => 'Пустой ответ от OpenAI.'];
            }
            return ['body' => $body, 'error' => null];
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . $apiKey . "\r\n",
                'content' => $jsonBody,
                'timeout' => 90,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            $err = error_get_last();
            $msg = $err['message'] ?? 'Нет доступа к api.openai.com. Включите cURL в PHP или проверьте сеть/файрвол.';
            return ['body' => '', 'error' => $msg];
        }
        return ['body' => $body, 'error' => null];
    }
}
