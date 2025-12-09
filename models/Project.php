<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;
use app\models\Department;
use app\models\Task;

/**
 * Project model
 *
 * @property \MongoDB\BSON\ObjectId $_id
 * @property string $title
 * @property string $description
 * @property string $goals
 * @property \MongoDB\BSON\UTCDateTime $start_date
 * @property \MongoDB\BSON\UTCDateTime $end_date
 * @property string $status draft / active / review / finished / frozen
 * @property \MongoDB\BSON\ObjectId $manager_id
 * @property array $executors массив ObjectId исполнителей
 * @property \MongoDB\BSON\ObjectId|null $department_id ссылка на подразделение
 * @property int $progress процент выполнения (0-100)
 * @property \MongoDB\BSON\UTCDateTime|null $last_report_date
 * @property \MongoDB\BSON\UTCDateTime|null $next_report_deadline
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
class Project extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_REVIEW = 'review';
    const STATUS_FINISHED = 'finished';
    const STATUS_FROZEN = 'frozen';

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'projects';
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
            'goals',
            'start_date',
            'end_date',
            'status',
            'manager_id',
            'executors',
            'department_id',
            'progress',
            'last_report_date',
            'next_report_deadline',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @var string виртуальное поле для дат в формах
     */
    public $start_date_str;
    public $end_date_str;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['title', 'manager_id', 'status'], 'required'],
            [['title', 'description', 'goals', 'status'], 'string'],
            [['department_id'], 'exist', 'targetClass' => Department::class, 'targetAttribute' => '_id', 'skipOnEmpty' => true],
            [['status'], 'in', 'range' => [
                self::STATUS_DRAFT,
                self::STATUS_ACTIVE,
                self::STATUS_REVIEW,
                self::STATUS_FINISHED,
                self::STATUS_FROZEN
            ]],
            [['progress'], 'integer', 'min' => 0, 'max' => 100],
            [['manager_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => '_id'],
            [['executors'], 'each', 'rule' => ['exist', 'targetClass' => User::class, 'targetAttribute' => '_id']],
            [['start_date', 'end_date', 'last_report_date', 'next_report_deadline', 'created_at', 'updated_at', 'start_date_str', 'end_date_str'], 'safe'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'title' => 'Название проекта',
            'description' => 'Описание',
            'goals' => 'Цели проекта',
            'start_date' => 'Дата начала',
            'end_date' => 'Дата окончания',
            'status' => 'Статус',
            'manager_id' => 'Руководитель',
            'executors' => 'Исполнители',
            'department_id' => 'Подразделение',
            'progress' => 'Прогресс (%)',
            'last_report_date' => 'Дата последнего отчёта',
            'next_report_deadline' => 'Следующий дедлайн',
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
                    $this->status = self::STATUS_DRAFT;
                }
                if ($this->progress === null) {
                    $this->progress = 0;
                }
            }
            $this->updated_at = new \MongoDB\BSON\UTCDateTime();
            
            // Автоматический расчет прогресса на основе отчетов
            $this->calculateProgress();
            
            return true;
        }
        return false;
    }

    /**
     * Автоматический расчет прогресса на основе отчетов
     */
    public function calculateProgress()
    {
        $reports = Report::find()
            ->where(['project_id' => $this->_id, 'status' => Report::STATUS_ACCEPTED])
            ->all();
        
        if (empty($reports)) {
            return;
        }

        // Можно реализовать более сложную логику расчета прогресса
        // Например, на основе количества принятых отчетов или других метрик
        // Пока оставляем базовую логику
    }

    /**
     * Gets manager of the project
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getManager()
    {
        return $this->hasOne(User::class, ['_id' => 'manager_id']);
    }

    /**
     * Gets executors of the project
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getExecutors()
    {
        return User::find()->where(['_id' => ['$in' => $this->executors ?: []]]);
    }

    /**
     * Gets project specification (1:1 relation)
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getSpec()
    {
        return $this->hasOne(ProjectSpec::class, ['project_id' => '_id']);
    }

    /**
     * Gets reports for the project
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getReports()
    {
        return $this->hasMany(Report::class, ['project_id' => '_id']);
    }

    /**
     * Gets comments for the project
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['project_id' => '_id']);
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
     * Gets tasks for the project
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['project_id' => '_id']);
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
            self::STATUS_ACTIVE => 'Активный',
            self::STATUS_REVIEW => 'На проверке',
            self::STATUS_FINISHED => 'Завершен',
            self::STATUS_FROZEN => 'Заморожен',
        ];
        return $labels[$this->status] ?? $this->status;
    }
}

