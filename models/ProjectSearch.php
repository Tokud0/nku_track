<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Project;

/**
 * ProjectSearch represents the model behind the search form of `app\models\Project`.
 */
class ProjectSearch extends Project
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'description', 'status', 'department_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param \MongoDB\BSON\ObjectId|null $managerId Фильтр по менеджеру
     * @param \MongoDB\BSON\ObjectId|null $executorId Фильтр по исполнителю (не используется, оставлен для совместимости)
     * @param \MongoDB\BSON\ObjectId|null $departmentId Фильтр по подразделению
     * @param array $includeProjectIds Дополнительные ID проектов (например, где пользователь прикреплён к задаче)
     *
     * @return ActiveDataProvider
     */
    public function search($params, $managerId = null, $executorId = null, $departmentId = null, $includeProjectIds = [])
    {
        $query = Project::find();

        // Исключаем глобальные проекты (проекты без подразделения)
        $query->andWhere(['department_id' => ['$ne' => null]]);

        // Фильтр по менеджеру
        if ($managerId) {
            $query->andWhere(['manager_id' => $managerId]);
        }
        
        // Фильтр по подразделению ИЛИ по дополнительным проектам (где пользователь прикреплён)
        if ($departmentId || !empty($includeProjectIds)) {
            $conditions = [];
            if ($departmentId) {
                $deptId = is_string($departmentId) ? new \MongoDB\BSON\ObjectId($departmentId) : $departmentId;
                $conditions[] = ['department_id' => $deptId];
            }
            if (!empty($includeProjectIds)) {
                $conditions[] = ['_id' => ['$in' => $includeProjectIds]];
            }
            $query->andWhere(count($conditions) > 1 ? ['$or' => $conditions] : $conditions[0]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        if (!empty($this->title)) {
            $query->andFilterWhere(['like', 'title', $this->title]);
        }
        if (!empty($this->status)) {
            $query->andFilterWhere(['status' => $this->status]);
        }
        // Фильтр по подразделению из формы применяется только если departmentId не был явно установлен в null
        // Если departmentId = null (для ректора), игнорируем фильтр из формы - ректор видит все проекты
        if (!empty($this->department_id) && $departmentId !== null) {
            // Применяем фильтр из формы только если departmentId не null
            $filterDeptId = is_string($this->department_id) ? new \MongoDB\BSON\ObjectId($this->department_id) : $this->department_id;
            $query->andFilterWhere(['department_id' => $filterDeptId]);
        }
        // Если departmentId === null (явно передан), фильтр из формы игнорируется - показываются все проекты

        return $dataProvider;
    }
}

