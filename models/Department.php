<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;

/**
 * Department model
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property string $name название подразделения
 * @property string $description описание подразделения
 * @property \MongoDB\BSON\ObjectId|null $parent_id ссылка на родительское подразделение (null для основных подразделений)
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class Department extends ActiveRecord
{
    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'departments';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'name',
            'description',
            'parent_id',
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
            [['name'], 'required'],
            [['name'], 'unique', 'filter' => function($query) {
                // Уникальность имени в рамках одного родителя
                if ($this->parent_id) {
                    $query->andWhere(['parent_id' => $this->parent_id]);
                } else {
                    $query->andWhere(['parent_id' => null]);
                }
                if (!$this->isNewRecord) {
                    $query->andWhere(['!=', '_id', $this->_id]);
                }
            }],
            [['name', 'description'], 'string', 'max' => 255],
            [['parent_id'], 'exist', 'targetClass' => Department::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            [['parent_id'], 'validateParentNotSelf'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * Валидация: департамент не может быть родителем самому себе
     */
    public function validateParentNotSelf($attribute, $params)
    {
        if ($this->parent_id && (string)$this->parent_id === (string)$this->_id) {
            $this->addError($attribute, 'Департамент не может быть родителем самому себе.');
        }
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'name' => 'Название подразделения',
            'description' => 'Описание',
            'parent_id' => 'Родительское подразделение',
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
     * Gets users in this department (directly attached, not through subdepartments)
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['department_id' => '_id'])
            ->andWhere(['subdepartment_id' => null]);
    }

    /**
     * Gets all users in this department including those in subdepartments
     *
     * @return array
     */
    public function getAllUsers()
    {
        $subdepartments = $this->getSubdepartments()->all();
        $subdepartmentIds = [];
        foreach ($subdepartments as $subdept) {
            $subdepartmentIds[] = $subdept->_id;
        }
        
        // Пользователи напрямую в подразделении
        $directUsers = User::find()
            ->where(['department_id' => $this->_id])
            ->andWhere(['subdepartment_id' => null])
            ->all();
        
        // Пользователи в департаментах
        $subdepartmentUsers = [];
        if (!empty($subdepartmentIds)) {
            $subdepartmentUsers = User::find()
                ->where(['subdepartment_id' => ['$in' => $subdepartmentIds]])
                ->all();
        }
        
        return array_merge($directUsers, $subdepartmentUsers);
    }

    /**
     * Gets parent department
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Department::class, ['_id' => 'parent_id']);
    }

    /**
     * Gets subdepartments (child departments)
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getSubdepartments()
    {
        return $this->hasMany(Department::class, ['parent_id' => '_id']);
    }

    /**
     * Checks if this is a main department (not a subdepartment)
     *
     * @return bool
     */
    public function isMainDepartment()
    {
        return $this->parent_id === null;
    }

    /**
     * Checks if this is a subdepartment
     *
     * @return bool
     */
    public function isSubdepartment()
    {
        return $this->parent_id !== null;
    }
}

