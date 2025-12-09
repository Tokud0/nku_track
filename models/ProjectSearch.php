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
     * @param \MongoDB\BSON\ObjectId|null $executorId Фильтр по исполнителю
     * @param \MongoDB\BSON\ObjectId|null $departmentId Фильтр по подразделению
     *
     * @return ActiveDataProvider
     */
    public function search($params, $managerId = null, $executorId = null, $departmentId = null)
    {
        $query = Project::find();

        // Фильтр по менеджеру
        if ($managerId) {
            $query->andWhere(['manager_id' => $managerId]);
        }

        // Фильтр по исполнителю
        if ($executorId) {
            $query->andWhere(['executors' => ['$in' => [$executorId]]]);
        }
        
        // Фильтр по подразделению
        if ($departmentId) {
            $deptId = is_string($departmentId) ? new \MongoDB\BSON\ObjectId($departmentId) : $departmentId;
            $query->andWhere(['department_id' => $deptId]);
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
        if (!empty($this->department_id)) {
            $departmentId = is_string($this->department_id) ? new \MongoDB\BSON\ObjectId($this->department_id) : $this->department_id;
            $query->andFilterWhere(['department_id' => $departmentId]);
        }

        return $dataProvider;
    }
}

