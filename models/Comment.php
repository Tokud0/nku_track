<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use app\models\Task;

/**
 * Comment model - Comments for projects and reports
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId|null $project_id
 * @property \MongoDB\BSON\ObjectId|null $report_id может быть null
 * @property \MongoDB\BSON\ObjectId|null $task_id может быть null
 * @property \MongoDB\BSON\ObjectId $author_id
 * @property string $text
 * @property \MongoDB\BSON\UTCDateTime $created_at
 */
class Comment extends ActiveRecord
{
    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'comments';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'project_id',
            'report_id',
            'task_id',
            'author_id',
            'text',
            'created_at',
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['author_id', 'text'], 'required'],
            [['text'], 'string'],
            [['project_id'], 'exist', 'targetClass' => Project::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            [['report_id'], 'exist', 'targetClass' => Report::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            [['task_id'], 'exist', 'targetClass' => Task::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            [['author_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => '_id'],
            [['created_at'], 'safe'],
            // Валидация: должен быть указан хотя бы один из project_id, report_id или task_id
            [['project_id', 'report_id', 'task_id'], 'validateAtLeastOne'],
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
            'report_id' => 'Отчет',
            'task_id' => 'Задача',
            'author_id' => 'Автор',
            'text' => 'Текст комментария',
            'created_at' => 'Дата создания',
        ];
    }

    /**
     * Валидация: должен быть указан хотя бы один из project_id, report_id или task_id
     */
    public function validateAtLeastOne($attribute, $params)
    {
        if (empty($this->project_id) && empty($this->report_id) && empty($this->task_id)) {
            $this->addError('project_id', 'Необходимо указать проект, отчет или задачу.');
            $this->addError('report_id', 'Необходимо указать проект, отчет или задачу.');
            $this->addError('task_id', 'Необходимо указать проект, отчет или задачу.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
            }
            return true;
        }
        return false;
    }

    /**
     * Gets author
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::class, ['_id' => 'author_id']);
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
     * Gets report
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getReport()
    {
        return $this->hasOne(Report::class, ['_id' => 'report_id']);
    }

    /**
     * Gets task
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getTask()
    {
        return $this->hasOne(Task::class, ['_id' => 'task_id']);
    }
}

