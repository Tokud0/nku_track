<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;

/**
 * Roadmap model - Дорожная карта подразделения
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $department_id ссылка на подразделение
 * @property \MongoDB\BSON\UTCDateTime|null $start_date дата начала дорожной карты
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class Roadmap extends ActiveRecord
{
    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'roadmaps';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'department_id',
            'start_date',
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
            [['department_id'], 'exist', 'targetClass' => Department::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            [['department_id'], 'unique', 'message' => 'Дорожная карта для этого подразделения уже существует.', 'skipOnEmpty' => true],
            [['start_date', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'department_id' => 'Подразделение',
            'start_date' => 'Дата начала дорожной карты',
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
     * Gets department
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['_id' => 'department_id']);
    }

    /**
     * Gets roadmap stages
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getStages()
    {
        return $this->hasMany(RoadmapStage::class, ['roadmap_id' => '_id'])->orderBy(['start_month' => SORT_ASC]);
    }

    /**
     * Проверяет, является ли дорожная карта глобальной (без привязки к департаменту)
     *
     * @return bool
     */
    public function isGlobal()
    {
        return $this->department_id === null;
    }
}

