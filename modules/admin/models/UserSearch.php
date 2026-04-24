<?php

namespace app\modules\admin\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\User;

/**
 * UserSearch represents the model behind the search form of `app\models\User`.
 */
class UserSearch extends User
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fio', 'email', 'role', 'department_id'], 'safe'],
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
        $query = User::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
                'pageSizeLimit' => [1, 100],
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        if (!empty($this->fio)) {
            $query->andFilterWhere(['like', 'fio', $this->fio]);
        }
        if (!empty($this->email)) {
            $query->andFilterWhere(['like', 'email', $this->email]);
        }
        if (!empty($this->role)) {
            $query->andFilterWhere(['role' => $this->role]);
        }
        if (!empty($this->department_id)) {
            $departmentId = is_string($this->department_id) ? new \MongoDB\BSON\ObjectId($this->department_id) : $this->department_id;
            $query->andFilterWhere(['department_id' => $departmentId]);
        }

        return $dataProvider;
    }
}

