<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;

/**
 * GlobalProjectRole model - Роли внутри глобального проекта
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $user_id ссылка на пользователя
 * @property string $role роль внутри глобального проекта (rector / global_manager / global_executor)
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class GlobalProjectRole extends ActiveRecord
{
    const ROLE_RECTOR = 'rector'; // Ректор - главный в глобальном проекте, создает задачи
    const ROLE_GLOBAL_MANAGER = 'global_manager'; // Глобальный менеджер - управляет задачами
    const ROLE_GLOBAL_EXECUTOR = 'global_executor'; // Глобальный исполнитель - выполняет задачи

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'global_project_roles';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'user_id',
            'role',
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
            [['user_id', 'role'], 'required'],
            [['role'], 'in', 'range' => [
                self::ROLE_RECTOR,
                self::ROLE_GLOBAL_MANAGER,
                self::ROLE_GLOBAL_EXECUTOR,
            ]],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => '_id'],
            [['user_id'], 'unique', 'message' => 'Роль для этого пользователя уже назначена.'],
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
            'user_id' => 'Пользователь',
            'role' => 'Роль в глобальном проекте',
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
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
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
     * Gets role label
     *
     * @return string
     */
    public function getRoleLabel()
    {
        $labels = [
            self::ROLE_RECTOR => 'Ректор',
            self::ROLE_GLOBAL_MANAGER => 'Глобальный менеджер',
            self::ROLE_GLOBAL_EXECUTOR => 'Глобальный исполнитель',
        ];
        return $labels[$this->role] ?? $this->role;
    }

    /**
     * Проверяет, является ли пользователь ректором в глобальном проекте
     *
     * @param \MongoDB\BSON\ObjectId|string $userId
     * @return bool
     */
    public static function isRector($userId)
    {
        if (is_string($userId)) {
            $userId = new \MongoDB\BSON\ObjectId($userId);
        }
        return static::find()
            ->where(['user_id' => $userId, 'role' => self::ROLE_RECTOR])
            ->exists();
    }

    /**
     * Получает роль пользователя в глобальном проекте
     *
     * @param \MongoDB\BSON\ObjectId|string $userId
     * @return string|null
     */
    public static function getUserRole($userId)
    {
        if (is_string($userId)) {
            $userId = new \MongoDB\BSON\ObjectId($userId);
        }
        $role = static::find()
            ->where(['user_id' => $userId])
            ->one();
        return $role ? $role->role : null;
    }
}

