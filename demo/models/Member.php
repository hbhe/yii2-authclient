<?php

namespace common\models;

use trntv\filekit\behaviors\UploadBehavior;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "{{%member}}".
 *
 * @property integer $id
 * @property string $sid
 * @property string $mobile
 * @property string $name
 * @property string $auth_key
 * @property string $access_token
 * @property string $password_hash
 * @property string $email
 * @property integer $status
 * @property string $avatar_path
 * @property string $avatar_base_url
 * @property string $created_at
 * @property string $updated_at
 * @property string $logged_at
 */
class Member extends \common\models\ActiveRecord implements IdentityInterface
{
    const ROLE_MEMBER = 0;
    const ROLE_AGENT = 1;

    const STATUS_NOT_ACTIVE = 1;
    const STATUS_ACTIVE = 0;

    public $picture;

    public $verify_code;
    public $password;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%member}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'logged_at'], 'safe'],
            [['mobile'], 'string', 'max' => 16],
            [['name', 'auth_key'], 'string', 'max' => 32],
            [['access_token'], 'string', 'max' => 40],
            [['password_hash', 'email', 'avatar_path', 'avatar_base_url'], 'string', 'max' => 255],
            [['mobile'], 'unique'],
            [['mobile'], 'match', 'pattern' => '/^1\d{10}$/', 'message' => '手机格式不正确'],
        ];
    }

    public function validateVerifyCode($attribute, $params, $validator)
    {
        if ('verify_code' == $attribute) {
            if (!Util::checkVerifyCode($this->mobile, $this->$attribute)) {
                $this->addError($attribute, "校验码不正确");
            }
        }
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            [
                'class' => TimestampBehavior::className(),
                'value' => date('Y-m-d H:i:s'),
            ],

            'sid' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'sid'
                ],
                'value' => Yii::$app->getSecurity()->generateRandomString()
            ],

            'auth_key' => [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'auth_key'
                ],
                'value' => Yii::$app->getSecurity()->generateRandomString()
            ],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sid' => 'SID', // no use
            'mobile' => '手机',
            'name' => '姓名',
            'auth_key' => '重置 Key',
            'access_token' => 'Access Token',
            'password_hash' => '密码',
            'email' => 'Email',
            'status' => '状态',
            'avatar_path' => 'Avatar Path',
            'avatar_base_url' => '头像',
            'gender' => '性别',
            'created_at' => '注册时间',
            'updated_at' => '更新时间',
            'logged_at' => '登录时间',
        ];
    }


    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::find()
            ->active()
            ->andWhere(['id' => $id])
            ->one();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::find()
            ->active()
            ->andWhere(['or', ['mobile' => $token], ['access_token' => $token]])
            ->one();
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::find()
            ->andWhere(['mobile' => $username, 'status' => self::STATUS_ACTIVE])
            ->one();
    }

    public function isMe()
    {
        return (!Yii::$app->user->isGuest) && Yii::$app->user->id == $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password_hash);
    }

    public function getPublicIdentity()
    {
        return $this->name;
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
    }

    public function afterDelete()
    {
        foreach ($this->auths as $model) {
            $model->delete();
        }

        parent::afterDelete();
    }

    /**
     * @inheritdoc
     * @return MemberQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new MemberQuery(get_called_class());
    }

    public function getAvatarImageUrl()
    {
        if (empty($this->avatar_base_url) && empty($this->avatar_path)) {
            return Yii::getAlias('@web/static/images/avatar.jpg'); // default
        } else if (empty($this->avatar_path)) {
            return $this->avatar_base_url;
        }
        return $this->avatar_base_url . '/' . $this->avatar_path;
    }

}
