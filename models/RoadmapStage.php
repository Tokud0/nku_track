<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;

/**
 * RoadmapStage model - Этап дорожной карты
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $roadmap_id ссылка на дорожную карту
 * @property string $name название этапа
 * @property int $start_month начало этапа (месяц от начала, начиная с 0)
 * @property int $end_month конец этапа (месяц от начала, начиная с 0)
 * @property string $description описание этапа
 * @property int $order порядок отображения
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class RoadmapStage extends ActiveRecord
{
    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'roadmap_stages';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'roadmap_id',
            'name',
            'start_month',
            'end_month',
            'description',
            'order',
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
            [['roadmap_id', 'name', 'start_month', 'end_month'], 'required'],
            [['roadmap_id'], 'exist', 'targetClass' => Roadmap::class, 'targetAttribute' => '_id'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['start_month', 'end_month', 'order'], 'integer', 'min' => 0],
            [['end_month'], 'compare', 'compareAttribute' => 'start_month', 'operator' => '>='],
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
            'roadmap_id' => 'Дорожная карта',
            'name' => 'Название этапа',
            'start_month' => 'Начало (месяц)',
            'end_month' => 'Конец (месяц)',
            'description' => 'Описание этапа',
            'order' => 'Порядок',
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
            if ($insert && $this->order === null) {
                // Устанавливаем порядок как максимальный + 1
                $maxOrder = static::find()
                    ->where(['roadmap_id' => $this->roadmap_id])
                    ->max('order');
                $this->order = ($maxOrder !== null) ? $maxOrder + 1 : 0;
            }
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
            return true;
        }
        return false;
    }

    /**
     * Gets roadmap
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getRoadmap()
    {
        return $this->hasOne(Roadmap::class, ['_id' => 'roadmap_id']);
    }

    /**
     * Gets goals for this stage
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getGoals()
    {
        return $this->hasMany(RoadmapStageGoal::class, ['stage_id' => '_id'])->orderBy(['order' => SORT_ASC]);
    }

    /**
     * Gets formatted time range
     *
     * @return string
     */
    public function getTimeRange()
    {
        return "от {$this->start_month} до {$this->end_month} месяцев";
    }
}

