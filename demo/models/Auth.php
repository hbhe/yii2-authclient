<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%auth}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $oauth_client
 * @property string $oauth_client_user_id
 * @property string $nickname
 * @property string $avatar_url
 */
class Auth extends \common\models\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auth}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['oauth_client'], 'string', 'max' => 64],
            [['oauth_client_user_id', 'nickname', 'avatar_url'], 'safe'],
            [['oauth_client', 'oauth_client_user_id'], 'unique', 'targetAttribute' => ['oauth_client', 'oauth_client_user_id'], 'message' => 'The combination of Oauth Client and Oauth Client User ID has already been taken.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'oauth_client' => 'Oauth Client',
            'oauth_client_user_id' => 'Oauth Client User ID',
            'nickname' => 'Nick Name',
            'avatar_url' => 'Avatar',
        ];
    }

    public function getMember() {
        return $this->hasOne(Member::className(), ['id' => 'user_id']);
    }

    public function extraFields()
    {
        $fields = parent::extraFields();
        $fields[] = 'member';
        return $fields;
    }
}
