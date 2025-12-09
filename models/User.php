<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use yii\web\IdentityInterface;
use Yii;
use app\models\Task;

/**
 * User model
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property string $fio
 * @property string $email
 * @property string $password_hash
 * @property string $role rector / manager / executor / admin
 * @property \MongoDB\BSON\ObjectId|null $department_id ссылка на подразделение
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    const ROLE_RECTOR = 'rector';
    const ROLE_MANAGER = 'manager';
    const ROLE_EXECUTOR = 'executor';
    const ROLE_ADMIN = 'admin';

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'users';
    }

    /**
     * @var string виртуальное поле для пароля (используется в формах)
     */
    public $password;

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'fio',
            'email',
            'password_hash',
            'role',
            'department_id',
            'created_at',
            'updated_at',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function hasAttribute($name)
    {
        return $name === 'password' || parent::hasAttribute($name);
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['fio', 'email', 'role'], 'required'],
            [['email'], 'email'],
            [['email'], 'unique', 'when' => function($model) {
                return $model->isNewRecord || $model->isAttributeChanged('email');
            }],
            [['role'], 'in', 'range' => [self::ROLE_RECTOR, self::ROLE_MANAGER, self::ROLE_EXECUTOR, self::ROLE_ADMIN]],
            [['fio', 'email', 'password_hash', 'role'], 'string'],
            [['department_id'], 'exist', 'targetClass' => Department::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            [['password'], 'string', 'min' => 6, 'skipOnEmpty' => true],
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
            'fio' => 'ФИО',
            'email' => 'Email',
            'password' => 'Пароль',
            'password_hash' => 'Хэш пароля',
            'role' => 'Роль',
            'department_id' => 'Подразделение',
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
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        if (empty($id)) {
            return null;
        }
        
        try {
            // Конвертируем строку в ObjectId для MongoDB
            if (is_string($id)) {
                $id = new \MongoDB\BSON\ObjectId($id);
            }
            return static::findOne(['_id' => $id]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        // Not implemented for this application
        return null;
    }

    /**
     * Finds user by email
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return (string)$this->_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        // Not implemented for MongoDB
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        // Not implemented for MongoDB
        return false;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Gets projects where user is manager
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getProjectsAsManager()
    {
        return $this->hasMany(Project::class, ['manager_id' => '_id']);
    }

    /**
     * Gets projects where user is executor
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getProjectsAsExecutor()
    {
        return Project::find()->where(['executors' => ['$in' => [$this->_id]]]);
    }

    /**
     * Gets department
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['_id' => 'department_id']);
    }

    /**
     * Gets tasks assigned to user
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getTasksAssigned()
    {
        return $this->hasMany(Task::class, ['executor_id' => '_id']);
    }
}
