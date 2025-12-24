<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;

/**
 * RoadmapStageGoal model - Цель этапа дорожной карты
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $stage_id ссылка на этап дорожной карты
 * @property string $title название цели
 * @property string $description описание цели
 * @property int $order порядок отображения
 * @property bool $is_archived флаг архивации цели
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class RoadmapStageGoal extends ActiveRecord
{
    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'roadmap_stage_goals';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'stage_id',
            'title',
            'description',
            'order',
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
            [['stage_id', 'title'], 'required'],
            [['stage_id'], 'exist', 'targetClass' => RoadmapStage::class, 'targetAttribute' => '_id'],
            [['title'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['order'], 'integer', 'min' => 0],
            [['is_archived'], 'boolean'],
            [['is_archived'], 'default', 'value' => false],
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
            'stage_id' => 'Этап',
            'title' => 'Название цели',
            'description' => 'Описание цели',
            'order' => 'Порядок',
            'is_archived' => 'В архиве',
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
                    ->where(['stage_id' => $this->stage_id])
                    ->max('order');
                $this->order = ($maxOrder !== null) ? $maxOrder + 1 : 0;
            }
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
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
     * Gets stage
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getStage()
    {
        return $this->hasOne(RoadmapStage::class, ['_id' => 'stage_id']);
    }
}

