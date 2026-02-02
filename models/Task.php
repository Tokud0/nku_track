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
 * @property array|null $executor_user_ids массив ObjectId исполнителей (для глобальных проектов)
 * @property array|null $responsible_user_ids массив ObjectId ответственных (для глобальных проектов)
 * @property \MongoDB\BSON\ObjectId|null $responsible_user_id ответственный (глобальный менеджер для глобальных проектов) - устаревшее, используйте responsible_user_ids
 * @property \MongoDB\BSON\ObjectId $creator_id кто создал (руководитель)
 * @property \MongoDB\BSON\UTCDateTime|null $start_date
 * @property \MongoDB\BSON\UTCDateTime|null $due_date
 * @property int $progress 0–100%
 * @property array $subtasks массив подзадач (to-do лист) [['text' => string, 'completed' => bool], ...]
 * @property array $attachments массив вложений
 * @property bool $is_archived флаг архивации задачи
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
            'executor_user_ids',
            'responsible_user_ids',
            'responsible_user_id',
            'creator_id',
            'start_date',
            'due_date',
            'progress',
            'subtasks',
            'attachments',
            'is_archived',
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
            [['executor_user_ids', 'responsible_user_ids', 'attachments', 'subtasks', 'responsible_user_id'], 'safe'],
            [['is_archived'], 'boolean'],
            [['is_archived'], 'default', 'value' => false],
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
            'responsible_user_id' => 'Ответственный',
            'responsible_user_ids' => 'Ответственные',
            'creator_id' => 'Создатель',
            'start_date' => 'Дата начала',
            'due_date' => 'Срок выполнения',
            'progress' => 'Прогресс (%)',
            'attachments' => 'Вложения',
            'is_archived' => 'В архиве',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата обновления',
        ];
    }

    /**
     * Валидация исполнителя: один из типов назначения (один из трёх полей ИЛИ executor_user_ids для поиска любого)
     */
    public function validateExecutor($attribute, $params)
    {
        $hasSearchAny = !empty($this->executor_user_ids) && is_array($this->executor_user_ids);
        if ($hasSearchAny) {
            return; // «Поиск любого сотрудника» — исполнители в executor_user_ids (и/или заявки)
        }
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
            
            // Обрабатываем множественных исполнителей для глобальных проектов
            if ($this->executor_user_ids && is_array($this->executor_user_ids)) {
                $processedIds = [];
                foreach ($this->executor_user_ids as $userId) {
                    if (is_string($userId) && preg_match('/^[0-9a-fA-F]{24}$/', $userId)) {
                        try {
                            $processedIds[] = new \MongoDB\BSON\ObjectId($userId);
                        } catch (\Exception $e) {
                            // Пропускаем невалидные ID
                        }
                    } elseif ($userId instanceof \MongoDB\BSON\ObjectId) {
                        $processedIds[] = $userId;
                    }
                }
                $this->executor_user_ids = $processedIds;
            } else            if (empty($this->executor_user_ids)) {
                $this->executor_user_ids = null;
            }
            
            // Обрабатываем множественных ответственных для глобальных проектов
            if ($this->responsible_user_ids && is_array($this->responsible_user_ids)) {
                $processedIds = [];
                foreach ($this->responsible_user_ids as $userId) {
                    if (is_string($userId) && preg_match('/^[0-9a-fA-F]{24}$/', $userId)) {
                        try {
                            $processedIds[] = new \MongoDB\BSON\ObjectId($userId);
                        } catch (\Exception $e) {
                            // Пропускаем невалидные ID
                        }
                    } elseif ($userId instanceof \MongoDB\BSON\ObjectId) {
                        $processedIds[] = $userId;
                    }
                }
                $this->responsible_user_ids = $processedIds;
            } else if (empty($this->responsible_user_ids)) {
                $this->responsible_user_ids = null;
            }
            
            // Преобразуем responsible_user_id в ObjectId если это строка (для обратной совместимости)
            if (!empty($this->responsible_user_id) && is_string($this->responsible_user_id)) {
                try {
                    $this->responsible_user_id = new \MongoDB\BSON\ObjectId($this->responsible_user_id);
                } catch (\Exception $e) {
                    $this->responsible_user_id = null;
                }
            }
            
            // Автоматически рассчитываем прогресс на основе подзадач
            $total = $this->getTotalSubtasksCount();
            if ($total > 0) {
                $this->progress = $this->calculateProgress();
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
                if ($this->subtasks === null) {
                    $this->subtasks = [];
                }
                if ($this->executor_user_ids === null) {
                    $this->executor_user_ids = null;
                }
                if ($this->responsible_user_ids === null) {
                    $this->responsible_user_ids = null;
                }
                if ($this->is_archived === null) {
                    $this->is_archived = false;
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
     * Gets responsible user (global manager for global projects)
     * Устаревший метод - используйте getResponsibleUsers() для получения массива
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getResponsibleUser()
    {
        return $this->hasOne(User::class, ['_id' => 'responsible_user_id']);
    }

    /**
     * Gets all responsible users (for global projects)
     *
     * @return array массив User моделей
     */
    public function getResponsibleUsers()
    {
        $users = [];
        
        // Для глобальных проектов: множественные ответственные
        if ($this->responsible_user_ids && is_array($this->responsible_user_ids) && !empty($this->responsible_user_ids)) {
            $userIds = [];
            foreach ($this->responsible_user_ids as $userId) {
                if (is_string($userId)) {
                    try {
                        $userIds[] = new \MongoDB\BSON\ObjectId($userId);
                    } catch (\Exception $e) {
                        // Пропускаем невалидные ID
                    }
                } elseif ($userId instanceof \MongoDB\BSON\ObjectId) {
                    $userIds[] = $userId;
                }
            }
            if (!empty($userIds)) {
                $responsibleUsers = User::find()
                    ->where(['_id' => ['$in' => $userIds]])
                    ->all();
                $users = array_merge($users, $responsibleUsers);
            }
        }
        
        // Для обратной совместимости: если есть старый responsible_user_id
        if ($this->responsible_user_id) {
            $user = User::findOne(['_id' => $this->responsible_user_id]);
            if ($user) {
                // Проверяем, не добавлен ли уже этот пользователь
                $alreadyAdded = false;
                foreach ($users as $existingUser) {
                    if ((string)$existingUser->_id === (string)$user->_id) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                if (!$alreadyAdded) {
                    $users[] = $user;
                }
            }
        }
        
        return array_unique($users, SORT_REGULAR);
    }

    /**
     * Gets all users that should see this task
     *
     * @return array массив User моделей
     */
    public function getAssignedUsers()
    {
        $users = [];
        
        // Для глобальных проектов: множественные исполнители
        if ($this->executor_user_ids && is_array($this->executor_user_ids) && !empty($this->executor_user_ids)) {
            $userIds = [];
            foreach ($this->executor_user_ids as $userId) {
                if (is_string($userId)) {
                    try {
                        $userIds[] = new \MongoDB\BSON\ObjectId($userId);
                    } catch (\Exception $e) {
                        // Пропускаем невалидные ID
                    }
                } elseif ($userId instanceof \MongoDB\BSON\ObjectId) {
                    $userIds[] = $userId;
                }
            }
            if (!empty($userIds)) {
                $globalUsers = User::find()
                    ->where(['_id' => ['$in' => $userIds]])
                    ->all();
                $users = array_merge($users, $globalUsers);
            }
        }
        
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

        // Исполнители из одобренных заявок (прикрепление из другого подразделения)
        $approvedRequests = TaskExecutorRequest::getApprovedByTask($this->_id);
        foreach ($approvedRequests as $req) {
            $user = User::findOne(['_id' => $req->user_id]);
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

    /**
     * Получает количество выполненных подзадач
     *
     * @return int
     */
    public function getCompletedSubtasksCount()
    {
        if (!is_array($this->subtasks) || empty($this->subtasks)) {
            return 0;
        }
        
        $completed = 0;
        foreach ($this->subtasks as $subtask) {
            if (isset($subtask['completed']) && $subtask['completed'] === true) {
                $completed++;
            }
        }
        
        return $completed;
    }

    /**
     * Получает общее количество подзадач
     *
     * @return int
     */
    public function getTotalSubtasksCount()
    {
        if (!is_array($this->subtasks)) {
            return 0;
        }
        return count($this->subtasks);
    }

    /**
     * Получает формат прогресса "X/Y" на основе подзадач
     *
     * @return string
     */
    public function getProgressFormat()
    {
        $total = $this->getTotalSubtasksCount();
        if ($total === 0) {
            return '0/0';
        }
        $completed = $this->getCompletedSubtasksCount();
        return $completed . '/' . $total;
    }

    /**
     * Автоматически рассчитывает прогресс на основе подзадач
     * Если есть подзадачи, прогресс рассчитывается от них
     * Если подзадач нет, используется значение progress
     *
     * @return int процент выполнения (0-100)
     */
    public function calculateProgress()
    {
        $total = $this->getTotalSubtasksCount();
        if ($total > 0) {
            $completed = $this->getCompletedSubtasksCount();
            return round(($completed / $total) * 100);
        }
        return $this->progress ?? 0;
    }

}

