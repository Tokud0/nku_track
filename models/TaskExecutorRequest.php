<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;

/**
 * Заявка на прикрепление сотрудника (из другого подразделения) к задаче.
 * Руководитель или топ-менеджер подразделения исполнителя одобряет или отклоняет.
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $task_id
 * @property \MongoDB\BSON\ObjectId $user_id исполнитель
 * @property string $status pending | approved | rejected
 * @property \MongoDB\BSON\ObjectId $requested_by кто создал задачу
 * @property \MongoDB\BSON\ObjectId|null $decided_by кто одобрил/отклонил (руководитель/топ-менеджер подразделения исполнителя)
 * @property \MongoDB\BSON\UTCDateTime|null $decided_at
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class TaskExecutorRequest extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public static function collectionName()
    {
        return 'task_executor_requests';
    }

    public function attributes()
    {
        return [
            '_id',
            'task_id',
            'user_id',
            'status',
            'requested_by',
            'decided_by',
            'decided_at',
            'created_at',
            'updated_at',
        ];
    }

    public function rules()
    {
        return [
            [['task_id', 'user_id', 'requested_by', 'status'], 'required'],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED]],
            [['task_id', 'user_id', 'requested_by', 'decided_by'], 'safe'],
            [['decided_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'task_id' => 'Задача',
            'user_id' => 'Исполнитель',
            'status' => 'Статус',
            'requested_by' => 'Кто запросил',
            'decided_by' => 'Кто решил',
            'decided_at' => 'Дата решения',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
            if ($this->task_id && is_string($this->task_id)) {
                $this->task_id = new \MongoDB\BSON\ObjectId($this->task_id);
            }
            if ($this->user_id && is_string($this->user_id)) {
                $this->user_id = new \MongoDB\BSON\ObjectId($this->user_id);
            }
            if ($this->requested_by && is_string($this->requested_by)) {
                $this->requested_by = new \MongoDB\BSON\ObjectId($this->requested_by);
            }
            if ($this->decided_by && is_string($this->decided_by)) {
                $this->decided_by = new \MongoDB\BSON\ObjectId($this->decided_by);
            }
            return true;
        }
        return false;
    }

    public function getTask()
    {
        return $this->hasOne(Task::class, ['_id' => 'task_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['_id' => 'user_id']);
    }

    public function getRequestedByUser()
    {
        return $this->hasOne(User::class, ['_id' => 'requested_by']);
    }

    /**
     * Все заявки по задаче (любой статус)
     * @param \MongoDB\BSON\ObjectId|string $taskId
     * @return TaskExecutorRequest[]
     */
    public static function getAllByTask($taskId)
    {
        $id = is_string($taskId) ? new \MongoDB\BSON\ObjectId($taskId) : $taskId;
        return static::find()
            ->where(['task_id' => $id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
    }

    /**
     * Заявки по задаче со статусом approved
     * @param \MongoDB\BSON\ObjectId|string $taskId
     * @return TaskExecutorRequest[]
     */
    public static function getApprovedByTask($taskId)
    {
        $id = is_string($taskId) ? new \MongoDB\BSON\ObjectId($taskId) : $taskId;
        return static::find()
            ->where(['task_id' => $id, 'status' => self::STATUS_APPROVED])
            ->all();
    }

    /**
     * Ожидающие заявки по подразделению (исполнитель из этого подразделения)
     * @param \MongoDB\BSON\ObjectId|string $departmentId
     * @return TaskExecutorRequest[]
     */
    public static function getPendingByDepartment($departmentId)
    {
        $id = is_string($departmentId) ? new \MongoDB\BSON\ObjectId($departmentId) : $departmentId;
        $users = User::find()
            ->where(['department_id' => $id])
            ->select(['_id'])
            ->all();
        $userIds = array_map(function ($u) { return $u->_id; }, $users);
        if (empty($userIds)) {
            return [];
        }
        return static::find()
            ->where(['user_id' => ['$in' => $userIds], 'status' => self::STATUS_PENDING])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }
}
