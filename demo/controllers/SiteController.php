<?php
namespace frontend\controllers;

use common\models\Auth;
use common\models\Member;
use frontend\models\LoginForm;
use frontend\models\SignupForm;
use Yii;
use yii\authclient\ClientInterface;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\HttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'oauth' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
                //'successUrl' => ['/site/contact'], // 根据code获取用户信息后, 再次跳转到应用的哪个页面, 如果不指定就跳到Yii::$app->user->returnUrl
            ],
        ];
    }

    /*
     * @param ClientInterface $client
     */

    /*
     Web QQ $attributes:
        [
            'ret' => 0,
            'msg' => '',
            'is_lost' => 0,
            'nickname' => '江枫',
            'gender' => '男',
            'province' => '湖北',
            'city' => '武汉',
            'year' => '2005',
            'constellation' => '',
            'figureurl' => 'http://qzapp.qlogo.cn/qzapp/xx/30',
            'figureurl_1' => 'http://qzapp.qlogo.cn/qzapp/xx/50',
            'figureurl_2' => 'http://qzapp.qlogo.cn/qzapp/xx/100',
            'figureurl_qq_1' => 'http://thirdqq.qlogo.cn/xx/40',
            'figureurl_qq_2' => 'http://thirdqq.qlogo.cn/xx/100',
            'is_yellow_vip' => '0',
            'vip' => '0',
            'yellow_vip_level' => '0',
            'level' => '0',
            'is_yellow_year_vip' => '0',
            'id' => '2F8E0C3BBD75BD4E1F35E366D5FFDFE7',
            'username' => '江枫',
        ],

        weixin $attributes => [
            'openid' => 'oZzNy0dX0b7yewQRaZN4Umc8jjPU',
            'nickname' => 'xxx',
            'sex' => 1,
            'language' => 'zh_CN',
            'city' => 'Perth',
            'province' => 'Western Australia',
            'country' => 'Australia',
            'headimgurl' => 'http://thirdwx.qlogo.cn/mmopen/vi_32/xxx',
            'privilege' => [],
            'unionid' => 'oBo-101QzyYn-uf_6hlsZAhQr9H8',
        ],

        github $attributes:
        [
            'login' => 'hbhe',
            'id' => 6417343,
            'node_id' => 'MDQ6VXNlcjY0MTczNDM=',
            'avatar_url' => 'https://avatars3.githubusercontent.com/u/6417343?v=4',
            'gravatar_id' => '',
            'url' => 'https://api.github.com/users/hbhe',
            'html_url' => 'https://github.com/hbhe',
            'followers_url' => 'https://api.github.com/users/hbhe/followers',
            'following_url' => 'https://api.github.com/users/hbhe/following{/other_user}',
            'gists_url' => 'https://api.github.com/users/hbhe/gists{/gist_id}',
            'starred_url' => 'https://api.github.com/users/hbhe/starred{/owner}{/repo}',
            'subscriptions_url' => 'https://api.github.com/users/hbhe/subscriptions',
            'organizations_url' => 'https://api.github.com/users/hbhe/orgs',
            'repos_url' => 'https://api.github.com/users/hbhe/repos',
            'events_url' => 'https://api.github.com/users/hbhe/events{/privacy}',
            'received_events_url' => 'https://api.github.com/users/hbhe/received_events',
            'type' => 'User',
            'site_admin' => false,
            'name' => 'hbhe',
            'company' => 'wstech',
            'blog' => 'http://mywordpress.cn',
            'location' => 'Wuhan, Hubei Province, China',
            'email' => '53bs@sina.com',
            'hireable' => true,
            'bio' => 'Experienced Yii2 PHP developer, like wordpress, mysql, C/C++',
            'public_repos' => 9,
            'public_gists' => 0,
            'followers' => 5,
            'following' => 1,
            'created_at' => '2014-01-16T07:31:20Z',
            'updated_at' => '2018-06-25T09:39:32Z',
            'private_gists' => 0,
            'total_private_repos' => 0,
            'owned_private_repos' => 0,
            'disk_usage' => 28891,
            'collaborators' => 0,
            'two_factor_authentication' => false,
            'plan' => [
                'name' => 'free',
                'space' => 976562499,
                'collaborators' => 0,
                'private_repos' => 0,
            ],
        ],
    */
    public function onAuthSuccess(ClientInterface $client)
    {
        $attributes = $client->getUserAttributes();
        Yii::info([__METHOD__, __LINE__, $attributes]);
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
        if (YII_ENV_DEV) { // test
            $client_info['client'] = ['name' => 'qq'];
            $client_info['attributes'] = ['id' => 'ABC', 'username' => 'jack', 'avatar_url' => 'http://thirdqq.qlogo.cn/qqapp/101489472/2F8E0C3BBD75BD4E1F35E366D5FFDFE7/100'];
        }
        Yii::info($client_info);
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

