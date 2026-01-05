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
 * @property \MongoDB\BSON\UTCDateTime|null $start_date конкретная дата начала этапа
 * @property \MongoDB\BSON\UTCDateTime|null $end_date конкретная дата окончания этапа
 * @property string $description описание этапа
 * @property int $order порядок отображения
 * @property bool $is_completed флаг завершения этапа
 * @property string $completion_format формат завершения этапа (текстовое поле)
 * @property \MongoDB\BSON\UTCDateTime|null $completed_at дата завершения этапа
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
            'start_date',
            'end_date',
            'description',
            'order',
            'is_completed',
            'completion_format',
            'completed_at',
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
            [['description', 'completion_format'], 'string'],
            [['start_month', 'end_month', 'order'], 'integer', 'min' => 0],
            [['end_month'], 'compare', 'compareAttribute' => 'start_month', 'operator' => '>='],
            [['is_completed'], 'boolean'],
            [['start_date', 'end_date', 'completed_at', 'created_at', 'updated_at'], 'safe'],
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
            'start_date' => 'Дата начала',
            'end_date' => 'Дата окончания',
            'description' => 'Описание этапа',
            'order' => 'Порядок',
            'is_completed' => 'Завершен',
            'completion_format' => 'Формат завершения',
            'completed_at' => 'Дата завершения',
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

    /**
     * Проверяет, завершен ли этап
     *
     * @return bool
     */
    public function isCompleted()
    {
        return !empty($this->is_completed);
    }
}

