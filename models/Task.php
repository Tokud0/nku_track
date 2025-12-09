<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;
use app\models\Project;
use app\models\User;

/**
 * Task model
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $project_id ссылка на проект
 * @property string $title название задачи
 * @property string $description описание задачи
 * @property string $status todo | in_progress | review | done | canceled
 * @property string $priority low | medium | high | critical
 * @property \MongoDB\BSON\ObjectId|null $executor_id кто делает задачу (исполнитель)
 * @property \MongoDB\BSON\ObjectId $creator_id кто создал (руководитель)
 * @property \MongoDB\BSON\UTCDateTime|null $start_date
 * @property \MongoDB\BSON\UTCDateTime|null $due_date
 * @property int $progress 0–100%
 * @property array $attachments массив вложений
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class Task extends ActiveRecord
{
    const STATUS_TODO = 'todo';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_REVIEW = 'review';
    const STATUS_DONE = 'done';
    const STATUS_CANCELED = 'canceled';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'tasks';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'project_id',
            'title',
            'description',
            'status',
            'priority',
            'executor_id',
            'creator_id',
            'start_date',
            'due_date',
            'progress',
            'attachments',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['project_id', 'title', 'creator_id', 'status', 'priority'], 'required'],
            [['title', 'description', 'status', 'priority'], 'string'],
            [['status'], 'in', 'range' => [
                self::STATUS_TODO,
                self::STATUS_IN_PROGRESS,
                self::STATUS_REVIEW,
                self::STATUS_DONE,
                self::STATUS_CANCELED,
            ]],
            [['priority'], 'in', 'range' => [
                self::PRIORITY_LOW,
                self::PRIORITY_MEDIUM,
                self::PRIORITY_HIGH,
                self::PRIORITY_CRITICAL,
            ]],
            [['progress'], 'integer', 'min' => 0, 'max' => 100],
            // Убираем валидацию exist для MongoDB ObjectId, так как она может работать некорректно
            // Вместо этого проверяем в контроллере
            [['attachments'], 'safe'],
            [['start_date', 'due_date', 'created_at', 'updated_at'], 'safe'],
            [['project_id', 'creator_id'], 'safe'], // Помечаем как safe, так как устанавливаем в контроллере
            [['executor_id'], 'safe'],
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
            'title' => 'Название',
            'description' => 'Описание',
            'status' => 'Статус',
            'priority' => 'Приоритет',
            'executor_id' => 'Исполнитель',
            'creator_id' => 'Создатель',
            'start_date' => 'Дата начала',
            'due_date' => 'Срок выполнения',
            'progress' => 'Прогресс (%)',
            'attachments' => 'Вложения',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Убеждаемся, что ObjectId правильно установлены
            if (is_string($this->project_id)) {
                $this->project_id = new \MongoDB\BSON\ObjectId($this->project_id);
            }
            if (is_string($this->creator_id)) {
                $this->creator_id = new \MongoDB\BSON\ObjectId($this->creator_id);
            }
            if ($this->executor_id && is_string($this->executor_id)) {
                $this->executor_id = new \MongoDB\BSON\ObjectId($this->executor_id);
            }
            
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
                if (empty($this->status)) {
                    $this->status = self::STATUS_TODO;
                }
                if (empty($this->priority)) {
                    $this->priority = self::PRIORITY_MEDIUM;
                }
                if ($this->progress === null) {
                    $this->progress = 0;
                }
                if ($this->attachments === null) {
                    $this->attachments = [];
                }
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
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
     * Gets creator
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(User::class, ['_id' => 'creator_id']);
    }

    /**
     * Gets comments for the task
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['task_id' => '_id']);
    }

    /**
     * Gets status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_TODO => 'К выполнению',
            self::STATUS_IN_PROGRESS => 'В работе',
            self::STATUS_REVIEW => 'На проверке',
            self::STATUS_DONE => 'Выполнено',
            self::STATUS_CANCELED => 'Отменено',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Gets priority label
     *
     * @return string
     */
    public function getPriorityLabel()
    {
        $labels = [
            self::PRIORITY_LOW => 'Низкий',
            self::PRIORITY_MEDIUM => 'Средний',
            self::PRIORITY_HIGH => 'Высокий',
            self::PRIORITY_CRITICAL => 'Критический',
        ];
        return $labels[$this->priority] ?? $this->priority;
    }

    /**
     * Gets priority badge color
     *
     * @return string
     */
    public function getPriorityBadgeColor()
    {
        $colors = [
            self::PRIORITY_LOW => 'secondary',
            self::PRIORITY_MEDIUM => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_CRITICAL => 'danger',
        ];
        return $colors[$this->priority] ?? 'secondary';
    }

    /**
     * Gets status badge color
     *
     * @return string
     */
    public function getStatusBadgeColor()
    {
        $colors = [
            self::STATUS_TODO => 'secondary',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_REVIEW => 'warning',
            self::STATUS_DONE => 'success',
            self::STATUS_CANCELED => 'danger',
        ];
        return $colors[$this->status] ?? 'secondary';
    }
}

