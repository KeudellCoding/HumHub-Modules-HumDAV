<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\models;

use Yii;
use yii\data\ActiveDataProvider;

class UserTokenSearch extends UserToken {
    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [[self::getSearchableFields(), 'safe']];
    }

    public function scenarios() {
        return [self::SCENARIO_DEFAULT => self::getSearchableFields()];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params) {
        $query = UserToken::find()->where(['user_id' => Yii::$app->user->id]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

        $dataProvider->setSort(['attributes' => self::getSearchableFields()]);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['like', 'last_time_used', $this->last_time_used]);
        $query->andFilterWhere(['like', 'last_time_used_by_ip', $this->last_time_used_by_ip]);
        $query->andFilterWhere(['like', 'last_time_used_by_user_agent', $this->last_time_used_by_user_agent]);
        $query->andFilterWhere(['like', 'created_at', $this->created_at]);
        $query->andFilterWhere(['like', 'created_by_ip', $this->created_by_ip]);
        $query->andFilterWhere(['like', 'created_by_user_agent', $this->created_by_user_agent]);

        return $dataProvider;
    }

    private static function getSearchableFields() {
        return [
            'name',
            'last_time_used',
            'last_time_used_by_ip',
            'last_time_used_by_user_agent',
            'created_at',
            'created_by_ip',
            'created_by_user_agent'
        ];
    }
}
