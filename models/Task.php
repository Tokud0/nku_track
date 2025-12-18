<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;
use app\models\Project;
use app\models\User;
use app\models\Department;

/**
 * Task model
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $project_id ссылка на проект
 * @property string $title название задачи
 * @property string $description описание задачи
 * @property string $status todo | in_progress | review | done | canceled
 * @property string $priority low | medium | high | critical
 * @property \MongoDB\BSON\ObjectId|null $executor_user_from_department_id пользователь из подразделения
 * @property \MongoDB\BSON\ObjectId|null $executor_subdepartment_id департамент из подразделения
 * @property \MongoDB\BSON\ObjectId|null $executor_user_from_subdepartment_id пользователь из департамента
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
            'executor_user_from_department_id',
            'executor_subdepartment_id',
            'executor_user_from_subdepartment_id',
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
            [['executor_user_from_department_id', 'executor_subdepartment_id', 'executor_user_from_subdepartment_id'], 'validateExecutor'],
            [['attachments'], 'safe'],
            [['start_date', 'due_date', 'created_at', 'updated_at'], 'safe'],
            [['project_id', 'creator_id'], 'safe'], // Помечаем как safe, так как устанавливаем в контроллере
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
            'executor_user_from_department_id' => 'Исполнитель (из подразделения)',
            'executor_subdepartment_id' => 'Исполнитель (департамент)',
            'executor_user_from_subdepartment_id' => 'Исполнитель (из департамента)',
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
     * Валидация исполнителя: должен быть указан только один тип назначения
     */
    public function validateExecutor($attribute, $params)
    {
        $executorTypesCount = 0;
        if ($this->executor_user_from_department_id) $executorTypesCount++;
        if ($this->executor_subdepartment_id) $executorTypesCount++;
        if ($this->executor_user_from_subdepartment_id) $executorTypesCount++;
        
        if ($executorTypesCount > 1) {
            $this->addError('executor_user_from_department_id', 'Можно указать только один тип назначения исполнителя.');
        }
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
            if ($this->executor_user_from_department_id && is_string($this->executor_user_from_department_id)) {
                $this->executor_user_from_department_id = new \MongoDB\BSON\ObjectId($this->executor_user_from_department_id);
            }
            if ($this->executor_subdepartment_id && is_string($this->executor_subdepartment_id)) {
                $this->executor_subdepartment_id = new \MongoDB\BSON\ObjectId($this->executor_subdepartment_id);
            }
            if ($this->executor_user_from_subdepartment_id && is_string($this->executor_user_from_subdepartment_id)) {
                $this->executor_user_from_subdepartment_id = new \MongoDB\BSON\ObjectId($this->executor_user_from_subdepartment_id);
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
     * Gets executor user (if assigned directly from department)
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getExecutorUserFromDepartment()
    {
        return $this->hasOne(User::class, ['_id' => 'executor_user_from_department_id']);
    }

    /**
     * Gets executor subdepartment (if assigned whole subdepartment)
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getExecutorSubdepartment()
    {
        return $this->hasOne(Department::class, ['_id' => 'executor_subdepartment_id']);
    }

    /**
     * Gets executor user from subdepartment (if assigned user from subdepartment)
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getExecutorUserFromSubdepartment()
    {
        return $this->hasOne(User::class, ['_id' => 'executor_user_from_subdepartment_id']);
    }

    /**
     * Gets all users that should see this task
     *
     * @return array массив User моделей
     */
    public function getAssignedUsers()
    {
        $users = [];
        
        // Если назначен пользователь из подразделения
        if ($this->executor_user_from_department_id) {
            $user = User::findOne(['_id' => $this->executor_user_from_department_id]);
            if ($user) {
                $users[] = $user;
            }
        }
        
        // Если назначен департамент
        if ($this->executor_subdepartment_id) {
            $subdepartment = Department::findOne(['_id' => $this->executor_subdepartment_id]);
            if ($subdepartment) {
                $subdepartmentUsers = User::find()
                    ->where(['subdepartment_id' => $this->executor_subdepartment_id])
                    ->all();
                $users = array_merge($users, $subdepartmentUsers);
            }
        }
        
        // Если назначен пользователь из департамента
        if ($this->executor_user_from_subdepartment_id) {
            $user = User::findOne(['_id' => $this->executor_user_from_subdepartment_id]);
            if ($user) {
                $users[] = $user;
            }
        }
        
        return array_unique($users, SORT_REGULAR);
    }

    /**
     * Checks if user is assigned to this task
     *
     * @param User $user
     * @return bool
     */
    public function isAssignedToUser($user)
    {
        $assignedUsers = $this->getAssignedUsers();
        foreach ($assignedUsers as $assignedUser) {
            if ((string)$assignedUser->_id === (string)$user->_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets executor display name (department name if whole department assigned, or user names)
     *
     * @return string
     */
    public function getExecutorDisplayName()
    {
        // Если назначен весь департамент, показываем название департамента
        if ($this->executor_subdepartment_id) {
            $subdepartment = Department::findOne(['_id' => $this->executor_subdepartment_id]);
            if ($subdepartment) {
                return $subdepartment->name;
            }
        }
        
        // Иначе показываем имена пользователей
        $assignedUsers = $this->getAssignedUsers();
        if (!empty($assignedUsers)) {
            $names = [];
            foreach ($assignedUsers as $user) {
                $names[] = $user->fio;
            }
            return implode(', ', $names);
        }
        
        return 'Не назначен';
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

