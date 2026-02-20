<?php

namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * Файл базы знаний чат-бота. Файлы в runtime/chatbot_uploads, в БД — метаданные и текст.
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property string $original_name
 * @property string $stored_path
 * @property string $content_text
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property bool $is_active
 */
class ChatbotFile extends ActiveRecord
{
    const CONTEXT_MAX_CHARS = 80000;

    public static function collectionName()
    {
        return 'chatbot_files';
    }

    public function attributes()
    {
        return ['_id', 'original_name', 'stored_path', 'content_text', 'created_at', 'is_active'];
    }

    public function rules()
    {
        return [
            [['original_name', 'stored_path'], 'required'],
            [['original_name', 'stored_path', 'content_text'], 'string'],
            [['created_at'], 'safe'],
            [['is_active'], 'boolean'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
                if ($this->is_active === null) {
                    $this->is_active = true;
                }
            }
            return true;
        }
        return false;
    }

    /** Активен ли файл для бота (по умолчанию да, если поле не задано). */
    public function getIsActiveForContext()
    {
        if ($this->is_active === null) {
            return true;
        }
        return (bool) $this->is_active;
    }

    public static function getContextForPrompt()
    {
        $files = static::find()->orderBy(['created_at' => SORT_ASC])->all();
        $parts = [];
        $total = 0;
        foreach ($files as $f) {
            if (!$f->getIsActiveForContext()) {
                continue;
            }
            $text = trim($f->content_text ?? '');
            if ($text === '') continue;
            $block = "--- Файл: " . $f->original_name . " ---\n" . $text;
            if ($total + strlen($block) > self::CONTEXT_MAX_CHARS) {
                $block = mb_substr($block, 0, self::CONTEXT_MAX_CHARS - $total - 20) . "\n...";
            }
            $parts[] = $block;
            $total += strlen($block);
            if ($total >= self::CONTEXT_MAX_CHARS) break;
        }
        return implode("\n\n", $parts);
    }
}
