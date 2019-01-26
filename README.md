
Yii2 for QQ, 微信等第三方授权登录
===============

安装
------------

执行
```
php composer.phar require hbhe/yii2-authclient "*"
```

或者增加
```
"hbhe/yii2-authclient": "*"
```
到`composer.json`文件中.


用法说明
-------------
**配置**

```
'components' => [
    'authClientCollection' => [
        'class' => 'yii\authclient\Collection',
        'clients' => [
            'qq' => [
                // 在connect.qq.com网站上配置时, 设置回调域要指定到页面如http://xxx.com/site/oauth (不只是域名)
                'class' => 'hbhe\authclient\Qq',
                'clientId' => '101489472',
                'clientSecret' => '90f6db22535e7a9bdf9c8658a04ab847',
                'has_unionid' => false
                'normalizeUserAttributeMap' => [
                    'username' => 'nickname',
                    'avatar_url' => 'figureurl_qq_2',
                ]
            ],

            'weixin' => [
                // 在 https://open.weixin.qq.com 网站上配置时, 授权回调设为xxx.com, 不用具体到页面
                'class' => 'hbhe\authclient\Weixin',
                'clientId' => 'wx6e268e291898a15b',
                'clientSecret' => '0e4956942eaeaf901667557a25fe861d', 
                'normalizeUserAttributeMap' => [
                    'id' => 'unionid',
                    'username' => 'nickname',
                    'avatar_url' => 'headimgurl',
                ]
            ],

            'github' => [
                'class' => 'yii\authclient\clients\GitHub',
                'clientId' => '9dc99c02c5f8cafd9391',
                'clientSecret' => '52033e4158449b453a56436bf9d9821d0c6c3a56',
                'normalizeUserAttributeMap' => [
                    'username' => 'name',
                    'avatar_url' => 'avatar_url',
                ]
            ],
        ]
    ],

    // other components
]
```

**Controller**
```
class SiteController extends Controller
{
    public function actions()
    {
        return [
            'oauth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    public function onAuthSuccess(ClientInterface $client)
    {
        $attributes = $client->getUserAttributes();   
        Yii::info([__METHOD__, __LINE__, $attributes]);

        // 以下为业务处理逻辑， 仅供参考
        $auth = Auth::find()->where([
            'oauth_client' => $client->getName(), // $client->id
            'oauth_client_user_id' => ArrayHelper::getValue($attributes, 'id')
        ])->one();

        if (!empty($auth->member)) {
            Yii::error([__METHOD__, $auth->member->toArray(), $client]);
            Yii::$app->user->login($auth->member, 2 * 3600); // Yii::$app->params['user.rememberMeDuration']
            return true;
        }

        Yii::$app->session->set('client_info', [
            'client' => $client,
            'attributes' => $attributes,
        ]);

        // 跳转到绑定页面，选择要么绑定已有账号, 要么新建空账号
        return Yii::$app->controller->redirect(['/site/bind']);
    }

    public function actionBind()
    {
        $client_info = Yii::$app->session->get('client_info');
        $model = new DynamicModel([
            'bind_acc' => 1,
            'mobile' => '',
            'password' => '',
        ]);
        $model->addRule(['password', 'mobile'], 'string', ['max' => 32]);
        $model->addRule(['bind_acc'], 'safe');

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->validate()) {            
            if ($model->bind_acc) { // 如果选择绑定已有账号
                if (empty($model->mobile)) {
                    $model->addError('mobile', '手机号不能为空!');
                    goto end;
                }
                $user = Member::findOne(['mobile' => $model->mobile]);
                if (!$user || !$user->validatePassword($model->password)) {
                    $model->addError('password', '无效的账号或者密码不正确!');
                    goto end;
                }

            } else { // 如果选择创建新账号
                $user = new Member();
                $user->name =  ArrayHelper::getValue($client_info, 'attributes.username');
                $user->avatar_base_url =  ArrayHelper::getValue($client_info, 'attributes.avatar_url');
                $user->setPassword('123456');
                if (!$user->save(false)) {
                    Yii::error([__METHOD__, __LINE__, $user->errors]);
                    throw new HttpException(401, '新建账号失败');
                }
            }
            $auth = Auth::find()->where([
                'oauth_client' => ArrayHelper::getValue($client_info, 'client.name'),
                'oauth_client_user_id' => ArrayHelper::getValue($client_info, 'attributes.id'),
            ])->one();
            if (null === $auth) {
                $auth = new Auth();
            }
            $auth->setAttributes([
                'oauth_client' => ArrayHelper::getValue($client_info, 'client.name'),
                'oauth_client_user_id' => ArrayHelper::getValue($client_info, 'attributes.id'),
                'nickname' => ArrayHelper::getValue($client_info, 'attributes.username'),
                'avatar_url' => ArrayHelper::getValue($client_info, 'attributes.avatar_url'),
            ]);
            $auth->user_id = $user->id;
            if (!$auth->save()) {
                Yii::error([__METHOD__, __LINE__, $auth->errors]);
                if ((!$model->bind_acc) && (!$user->mobile)) { // 如果空账号已经新建，还得删掉它
                    $user->delete();
                }
                throw new HttpException(401, '绑定失败');
            }

            Yii::$app->user->login($user, 2 * 3600);
            $this->goHome();            
        }

        end:
        return $this->render('//bind', [
            'model' => $model,
            'client_info' => $client_info
        ]);
    }

    public function actionLogin()
    {
        $model = new LoginForm();
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('//login', [
                'model' => $model
            ]);
        }
    }
}
```

**View**

login.php, 显示第三方授权登录按钮
```
<div class="form-group">
    <?php $authAuthChoice = yii\authclient\widgets\AuthChoice::begin(['baseAuthUrl' => ['/site/oauth']]); ?>
    <ul>
        <a href="<?= $authAuthChoice->createClientUrl($authAuthChoice->clients['qq']) ?>"><span class="fa fa-qq"> QQ</span></a>
        <a href="<?= $authAuthChoice->createClientUrl($authAuthChoice->clients['weixin']) ?>"><span class="fa fa-weixin"> Weixin</span></a>
    </ul>
    <?php yii\authclient\widgets\AuthChoice::end(); ?>
</div>

```

**Model**
一个用户主账号可以对应多个第三方账号, 所以单独创建一张表auth
```
class Auth extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%auth}}';
    }

    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['oauth_client'], 'string', 'max' => 64],
            [['oauth_client_user_id', 'nickname', 'avatar_url'], 'safe'],
            [['oauth_client', 'oauth_client_user_id'], 'unique', 'targetAttribute' => ['oauth_client', 'oauth_client_user_id'], 'message' => 'The combination of Oauth Client and Oauth Client User ID has already been taken.'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => '用户主账号ID',
            'oauth_client' => '第三方名称',
            'oauth_client_user_id' => '用户的第三方ID',
            'nickname' => '昵称',
            'avatar_url' => '头像',
        ];
    }

    public function getMember() {
        return $this->hasOne(Member::className(), ['id' => 'user_id']);
    }

}

```


在前后端分离的情况下, 第三方登录流程如下：
1. 前端负责拼装跳转地址(即授权URL)，浏览器条到第三方后再跳回来，前端会拿到code；
2. 前端拿到code后, 调用后端的REST API接口，将code换成openid; 
3. 前端拿到openid(或者unionid)后, 调用后端第三方登录接口进行登录
4. 如果登录成功，接口返回主账号信息, 前端跳首页或者指定页面, 登录成功；否则调到绑定页面(或者创建新账号页面), 调用绑定接口, 返回主账号号信息, 登录成功。 

main.php配置如下
```
'authClientCollection' => [
    'class' => 'yii\authclient\Collection',
    'clients' => [
        'qq' => [
            // 在connect.qq.com网站上配置时, 设置回调域要指定到页面如http://xxx.com/site/oauth (不只是域名)
            'class' => 'hbhe\authclient\Qq',
            'clientId' => '101489472',
            'clientSecret' => 'xxx',
            'normalizeUserAttributeMap' => [
                'username' => 'nickname',
                'avatar_url' => 'figureurl_qq_2',
            ],
            // 'has_unionid' => true,
            'validateAuthState' => false, // 当要提供输入code返回openid的api接口时, 设为false
            'returnUrl' => 'http://127.0.0.1/paipai/frontend/web/site/oauth',
        ],

        'weixin_web' => [
            // 在 https://open.weixin.qq.com 网站上配置时, 授权回调设为www.xx.com, 不用具体到页面,但是xx.com是不行的,
            'class' => 'hbhe\authclient\Weixin',
            'clientId' => 'wx2cdf9b19f4592b01',
            'clientSecret' => 'xxx',
            'scope' => 'snsapi_login',
            'normalizeUserAttributeMap' => [
                'id' => 'unionid',
                'username' => 'nickname',
                'avatar_url' => 'headimgurl',
            ],
            'validateAuthState' => false, // 当要提供输入code返回openid的api接口时, 设为false
        ],

        'weixin_mp' => [
            // 在微信公众号后台网站上配置时, 授权回调设为www.site.com, 不用具体到页面
            'class' => 'hbhe\authclient\WeixinMp',
            'clientId' => 'wxa7f2ac6ae9f4330c',
            'clientSecret' => 'xxx',
            'scope' => 'snsapi_userinfo', // snsapi_base, snsapi_userinfo
            'normalizeUserAttributeMap' => [
                'id' => 'unionid',
                'username' => 'nickname',
                'avatar_url' => 'headimgurl',
            ],
            'validateAuthState' => false, // 当要提供输入code返回openid的api接口时, 设为false
        ],

        'github' => [
            'class' => 'yii\authclient\clients\GitHub',
            'clientId' => '9dc99c02c5f8cafd9391',
            'clientSecret' => '52033e4158449b453a56436bf9d9821d0c6c3a56',
            'normalizeUserAttributeMap' => [
                'username' => 'login',
                'avatar_url' => 'avatar_url',
            ],
            'validateAuthState' => false,
            'returnUrl' => 'http://127.0.0.1/paipai/frontend/web/site/oauth',
        ],
    ]
],
```

/**
 * 根据code获取openid的接口函数
 */
```
class SiteController extends ActiveController
{
    public $modelClass = 'common\models\User';

    public function actions()
    {
        $actions['oauth'] = [
            'class' => 'yii\authclient\AuthAction',
            'successCallback' => [$this, 'onAuthSuccess'],
        ];

        return $actions;
    }

    /**
     * 根据code返回openid, 注意每个code只能使用一次
     * http://api.xxx.com/v1/site/oauth?authclient=weixin_mp&code=061aVXdz1Xz9Sa0Bstez1xKGdz1aVXdj
     * @param ClientInterface $client
     * @return bool|\yii\console\Response|\yii\web\Response
     */
    /*
    {
        "success": true,
        "data": {
            "openid": "oEvDz1DeOBelocjETSsA65ubVndo",
            "nickname": "不散的61",
            "sex": 2,
            "language": "zh_CN",
            "city": "",
            "province": "波茨坦",
            "country": "德国",
            "headimgurl": "http://thirdwx.qlogo.cn/mmopen/v",
            "privilege": [],
            "unionid": "o3zOnwfT6sbHms5tSOA_I55N0j0E",
            "id": "o3zOnwfT6sbHms5tSOA_I55N0j0E",
            "username": "不散的61",
            "avatar_url": "http://thirdwx.qlogo.cn/mm"
        }
    }
    */
    public function onAuthSuccess(ClientInterface $client)
    {
        $attributes = $client->getUserAttributes();
        Yii::info([__METHOD__, __LINE__, $attributes]);
        $response = Yii::$app->getResponse();
        $response->data = $attributes;
        return $response;
    }

}
```