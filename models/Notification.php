<?php

namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * Notification model - System notifications
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $user_id
 * @property string $type new_report / deadline / report_status / assign
 * @property string $message текст уведомления
 * @property string|null $link URL на проект/отчёт
 * @property bool $is_read прочитано или нет
 * @property \MongoDB\BSON\UTCDateTime $created_at
 */
class Notification extends ActiveRecord
{
    const TYPE_NEW_REPORT = 'new_report';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_REPORT_STATUS = 'report_status';
    const TYPE_ASSIGN = 'assign';

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'notifications';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'type',
            'message',
            'link',
            'is_read',
            'created_at',
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'message'], 'required'],
            [['type'], 'in', 'range' => [
                self::TYPE_NEW_REPORT,
                self::TYPE_DEADLINE,
                self::TYPE_REPORT_STATUS,
                self::TYPE_ASSIGN
            ]],
            [['message', 'link'], 'string'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => '_id'],
            [['is_read'], 'boolean'],
            [['is_read'], 'default', 'value' => false],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'user_id' => 'Пользователь',
            'type' => 'Тип уведомления',
            'message' => 'Сообщение',
            'link' => 'Ссылка',
            'is_read' => 'Прочитано',
            'created_at' => 'Дата создания',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
                if ($this->is_read === null) {
                    $this->is_read = false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Gets user
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['_id' => 'user_id']);
    }

    /**
     * Gets type label
     *
     * @return string
     */
    public function getTypeLabel()
    {
        $labels = [
            self::TYPE_NEW_REPORT => 'Новый отчет',
            self::TYPE_DEADLINE => 'Дедлайн',
            self::TYPE_REPORT_STATUS => 'Изменение статуса отчета',
            self::TYPE_ASSIGN => 'Назначение',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    /**
     * Marks notification as read
     */
    public function markAsRead()
    {
        $this->is_read = true;
        return $this->save(false);
    }

    /**
     * Marks notification as unread
     */
    public function markAsUnread()
    {
        $this->is_read = false;
        return $this->save(false);
    }
}

