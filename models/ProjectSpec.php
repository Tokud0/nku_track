<?php

namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * ProjectSpec model - Technical specification for project
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property \MongoDB\BSON\ObjectId $project_id
 * @property string $tz_text подробное ТЗ
 * @property array $milestones массив этапов
 * @property array $metrics массив метрик успеха
 * @property string $report_period daily / weekly / biweekly / monthly / custom
 * @property int|null $custom_period_days если пользователь выбрал custom
 * @property array $report_template динамические поля отчета
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class ProjectSpec extends ActiveRecord
{
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_BIWEEKLY = 'biweekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_CUSTOM = 'custom';

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'project_specs';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return [
            '_id',
            'project_id',
            'tz_text',
            'milestones',
            'metrics',
            'report_period',
            'custom_period_days',
            'report_template',
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
            [['project_id', 'tz_text', 'report_period'], 'required'],
            [['tz_text'], 'string'],
            [['report_period'], 'in', 'range' => [
                self::PERIOD_DAILY,
                self::PERIOD_WEEKLY,
                self::PERIOD_BIWEEKLY,
                self::PERIOD_MONTHLY,
                self::PERIOD_CUSTOM
            ]],
            [['custom_period_days'], 'integer', 'min' => 1],
            [['project_id'], 'exist', 'targetClass' => Project::class, 'targetAttribute' => '_id'],
            [['milestones', 'metrics', 'report_template'], 'safe'],
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
            'project_id' => 'Проект',
            'tz_text' => 'Техническое задание',
            'milestones' => 'Этапы',
            'metrics' => 'Метрики успеха',
            'report_period' => 'Период отчетности',
            'custom_period_days' => 'Количество дней (для custom)',
            'report_template' => 'Шаблон отчета',
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
            // Убеждаемся, что project_id является ObjectId
            if ($this->project_id && is_string($this->project_id)) {
                try {
                    $this->project_id = new \MongoDB\BSON\ObjectId($this->project_id);
                } catch (\Exception $e) {
                    // Если не удалось конвертировать, оставляем как есть
                }
            }
            
            if ($insert) {
                $this->created_at = new \MongoDB\BSON\UTCDateTime();
                // Инициализация пустых массивов
                if ($this->milestones === null) {
                    $this->milestones = [];
                }
                if ($this->metrics === null) {
                    $this->metrics = [];
                }
                if ($this->report_template === null) {
                    $this->report_template = [];
                }
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
            return true;
        }
        return false;
    }

    /**
     * Gets project
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getProject()
    {
        return $this->hasOne(Project::class, ['_id' => 'project_id']);
    }

    /**
     * Gets report period label
     *
     * @return string
     */
    public function getReportPeriodLabel()
    {
        $labels = [
            self::PERIOD_DAILY => 'Ежедневно',
            self::PERIOD_WEEKLY => 'Еженедельно',
            self::PERIOD_BIWEEKLY => 'Раз в две недели',
            self::PERIOD_MONTHLY => 'Ежемесячно',
            self::PERIOD_CUSTOM => 'Произвольный период',
        ];
        return $labels[$this->report_period] ?? $this->report_period;
    }

    /**
     * Validates milestones structure
     *
     * @param array $milestones
     * @return bool
     */
    public function validateMilestones($milestones)
    {
        if (!is_array($milestones)) {
            return false;
        }

        foreach ($milestones as $milestone) {
            if (!isset($milestone['name']) || !isset($milestone['deadline'])) {
                return false;
            }
        }

        return true;
    }
}

