<?php

namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * Report model - Executor reports
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $project_id
 * @property \MongoDB\BSON\ObjectId $executor_id
 * @property array $fields embedded document с текстовыми полями отчёта
 * @property array $attachments массив файлов
 * @property string $status sent / accepted / revision
 * @property string|null $manager_comment комментарий руководителя
 * @property \MongoDB\BSON\UTCDateTime $created_at
 */
class Report extends ActiveRecord
{
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REVISION = 'revision';

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'reports';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'project_id',
            'executor_id',
            'fields',
            'attachments',
            'status',
            'manager_comment',
            'created_at',
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['project_id', 'executor_id', 'status'], 'required'],
            [['status'], 'in', 'range' => [
                self::STATUS_SENT,
                self::STATUS_ACCEPTED,
                self::STATUS_REVISION
            ]],
            [['project_id'], 'exist', 'targetClass' => Project::class, 'targetAttribute' => '_id'],
            [['executor_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => '_id'],
            [['fields', 'attachments'], 'safe'],
            [['manager_comment'], 'string'],
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
            'project_id' => 'Проект',
            'executor_id' => 'Исполнитель',
            'fields' => 'Поля отчёта',
            'attachments' => 'Вложения',
            'status' => 'Статус',
            'manager_comment' => 'Комментарий руководителя',
            'created_at' => 'Дата отправки',
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
                if (empty($this->status)) {
                    $this->status = self::STATUS_SENT;
                }
                // Инициализация пустых массивов
                if ($this->fields === null) {
                    $this->fields = [];
                }
                if ($this->attachments === null) {
                    $this->attachments = [];
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Gets project
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['_id' => 'project_id']);
    }

    /**
     * Gets executor
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getExecutor()
    {
        return $this->hasOne(User::class, ['_id' => 'executor_id']);
    }

    /**
     * Gets comments for the report
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['report_id' => '_id']);
    }

    /**
     * Gets status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_SENT => 'Отправлен',
            self::STATUS_ACCEPTED => 'Принят',
            self::STATUS_REVISION => 'На доработке',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Gets field value by name
     *
     * @param string $fieldName
     * @return mixed|null
     */
    public function getFieldValue($fieldName)
    {
        return isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : null;
    }

    /**
     * Sets field value
     *
     * @param string $fieldName
     * @param mixed $value
     */
    public function setFieldValue($fieldName, $value)
    {
        if ($this->fields === null) {
            $this->fields = [];
        }
        $this->fields[$fieldName] = $value;
    }
}

