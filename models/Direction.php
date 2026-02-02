<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;

/**
 * Direction model - Направления глобального проекта
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property string $title Название направления
 * @property string $description Описание направления
 * @property string $status Статус (draft/active/archived)
 * @property array|null $manager_ids Массив ObjectId руководителей направления
 * @property int $sort_order Порядок сортировки
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class Direction extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    /**
     * @var array виртуальное поле для выбора руководителей в формах
     */
    public $manager_ids_array = [];

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'directions';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'description',
            'status',
            'manager_ids',
            'sort_order',
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
            [['title', 'status'], 'required'],
            [['title', 'description', 'status'], 'string'],
            [['status'], 'in', 'range' => [
                self::STATUS_DRAFT,
                self::STATUS_ACTIVE,
                self::STATUS_ARCHIVED,
            ]],
            [['sort_order'], 'integer', 'min' => 0],
            [['manager_ids', 'manager_ids_array'], 'safe'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'title' => 'Название направления',
            'description' => 'Описание',
            'status' => 'Статус',
            'manager_ids' => 'Руководители',
            'sort_order' => 'Порядок сортировки',
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
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
                if (empty($this->status)) {
                    $this->status = self::STATUS_ACTIVE;
                }
                if ($this->sort_order === null) {
                    // Получаем максимальный sort_order и добавляем 1
                    $maxOrder = static::find()->max('sort_order');
                    $this->sort_order = ($maxOrder ?? 0) + 1;
                }
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
            return true;
        }
        return false;
    }

    /**
     * Gets all managers of the direction
     *
     * @return User[]
     */
    public function getManagers()
    {
        if (!empty($this->manager_ids)) {
            $managerIds = [];
            foreach ($this->manager_ids as $id) {
                if ($id instanceof \MongoDB\BSON\ObjectId) {
                    $managerIds[] = $id;
                } elseif (is_string($id)) {
                    try {
                        $managerIds[] = new \MongoDB\BSON\ObjectId($id);
                    } catch (\Exception $e) {
                        // Пропускаем невалидные ID
                    }
                }
            }
            if (!empty($managerIds)) {
                return User::find()->where(['_id' => ['$in' => $managerIds]])->all();
            }
        }
        return [];
    }

    /**
     * Gets projects in this direction
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getProjects()
    {
        return $this->hasMany(Project::class, ['direction_id' => '_id']);
    }

    /**
     * Gets active projects count in this direction
     *
     * @return int
     */
    public function getActiveProjectsCount()
    {
        return Project::find()
            ->where(['direction_id' => $this->_id])
            ->andWhere(['!=', 'status', Project::STATUS_FINISHED])
            ->andWhere(['$or' => [
                ['is_legacy' => false],
                ['is_legacy' => ['$exists' => false]],
            ]])
            ->count();
    }

    /**
     * Gets total tasks count in this direction
     *
     * @return int
     */
    public function getTasksCount()
    {
        $projectIds = Project::find()
            ->select(['_id'])
            ->where(['direction_id' => $this->_id])
            ->andWhere(['$or' => [
                ['is_legacy' => false],
                ['is_legacy' => ['$exists' => false]],
            ]])
            ->column();
        
        if (empty($projectIds)) {
            return 0;
        }
        
        return Task::find()
            ->where(['project_id' => ['$in' => $projectIds]])
            ->andWhere(['$or' => [
                ['is_archived' => false],
                ['is_archived' => ['$exists' => false]],
            ]])
            ->count();
    }

    /**
     * Gets status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_ACTIVE => 'Активное',
            self::STATUS_ARCHIVED => 'Архивное',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Статусы для выпадающего списка
     *
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_ACTIVE => 'Активное',
            self::STATUS_ARCHIVED => 'Архивное',
        ];
    }

    /**
     * Проверяет, может ли пользователь управлять направлением
     * Только админ и глобальный руководитель
     *
     * @param User $user
     * @return bool
     */
    public function canManage($user)
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }
        
        $userGlobalRole = GlobalProjectRole::getUserRole($user->_id);
        return $userGlobalRole === GlobalProjectRole::ROLE_RECTOR;
    }
}
