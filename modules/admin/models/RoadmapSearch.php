<?php

namespace app\modules\admin\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Department;

/**
 * RoadmapSearch represents the model behind the search form for roadmaps by department.
 */
class RoadmapSearch extends Model
{
    public $department_id;
    public $department_name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['department_id', 'department_name'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Department::find()
            ->where(['parent_id' => null]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageSizeLimit' => [1, 100],
            ],
            'sort' => [
                'defaultOrder' => [
                    'name' => SORT_ASC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Фильтрация по ID подразделения
        if (!empty($this->department_id)) {
            $departmentId = is_string($this->department_id) ? new \MongoDB\BSON\ObjectId($this->department_id) : $this->department_id;
            $query->andFilterWhere(['_id' => $departmentId]);
        }

        // Фильтрация по названию подразделения
        if (!empty($this->department_name)) {
            $query->andFilterWhere(['like', 'name', $this->department_name]);
        }

        return $dataProvider;
    }
}

