<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'openaiApiKey' => (function () {
        $k = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? '';
        $k = is_string($k) ? trim($k) : '';
        return $k !== '' ? $k : '';
    })(),
];
