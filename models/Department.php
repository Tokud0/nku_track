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
            [['name'], 'unique'],
            [['name', 'description'], 'string', 'max' => 255],
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
            'name' => 'Название подразделения',
            'description' => 'Описание',
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
     * Gets users in this department
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::class, ['department_id' => '_id']);
    }
}

