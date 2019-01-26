<?php
namespace hbhe\authclient;

use yii\authclient\OAuth2;
use yii\authclient\OAuthToken;
use yii\web\HttpException;
use Yii;

/**
 * 微信公众号授权
 *
 * In order to use Weixin(Wechat) OAuth you must register your application at <https://open.weixin.qq.com/> or <https://mp.weixin.qq.com/>.
 *
 * Example application configuration:
 *
 * ~~~
 * 'components' => [
 *     'authClientCollection' => [
 *         'class' => 'yii\authclient\Collection',
 *         'clients' => [
 *             'weixin_mp' => [
 *                 'class' => 'hbhe\authclient\WeixinMp',
 *                 'clientId' => 'weixin_appid',
 *                 'clientSecret' => 'weixin_appkey',
 *                 'scope' => 'snsapi_userinfo', // snsapi_base, snsapi_userinfo
 *                 'normalizeUserAttributeMap' => [
 *                      'id' => 'unionid', // 'openid'
 *                      'username' => 'nickname',
 *                      'avatar_url' => 'headimgurl',
 *                  ]
 *             ],
 *         ],
 *     ]
 *     ...
 * ]
 * ~~~
 *
 * @see https://open.weixin.qq.com/
 * @see https://open.weixin.qq.com/cgi-bin/showdocument?action=dir_list&t=resource/res_list&verify=1&lang=zh_CN
 *
 * @author hbhe <57620133@qq.com>
 * @since 1.0
 */

/*
    'attributes' =>
    [
        'openid' => 'oEvDz1DeOBelocjETSsA65ubVndo',
        'nickname' => 'xxx',
        'sex' => 2,
        'language' => 'zh_CN',
        'city' => '',
        'province' => 'xx',
        'country' => 'xx',
        'headimgurl' => 'http://thirdwx.qlogo.cn/mmop',
        'privilege' => [],
        'unionid' => 'o3zOnwfT6sbHms5tSOA_I55N0j0E',
    ],
*/
class WeixinMp extends OAuth2
{
    /**
     * @inheritdoc
     */
    // 公众号的url与微信网页互联不一样，网页互联是 'https://open.weixin.qq.com/connect/qrconnect';
    public $authUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    /**
     * @inheritdoc
     */
    public $tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';

    /**
     * @inheritdoc
     */
    public $apiBaseUrl = 'https://api.weixin.qq.com';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->scope === null) {
            $this->scope = implode(',', [
                'snsapi_base', // snsapi_base, snsapi_userinfo
            ]);
        }
    }

    /**
     * @inheritdoc
     */

    protected function defaultNormalizeUserAttributeMap()
    {
        return [
            //'id' => 'openid',
            'id' => 'unionid',
            'username' => 'nickname',
            'avatar_url' => 'headimgurl',
        ];
    }

    /**
     * @inheritdoc
     */
    public function buildAuthUrl(array $params = [])
    {
        $defaultParams = [
            'appid' => $this->clientId,
            'redirect_uri' => $this->getReturnUrl(),
            'response_type' => 'code',
        ];

        if (!empty($this->scope)) {
            $defaultParams['scope'] = $this->scope;
        }

        return parent::buildAuthUrl(array_merge($defaultParams, $params));
        //return parent::buildAuthUrl(array_merge($defaultParams, $params)) . '#wechat_redirect';
    }

    /**
     * @inheritdoc
     */
    public function fetchAccessToken($authCode, array $params = [])
    {
        $params['appid'] = $this->clientId;
        $params['secret'] = $this->clientSecret;
        return parent::fetchAccessToken($authCode, $params);
    }

    /**
     * @inheritdoc
     */
    protected function initUserAttributes()
    {
        $accessToken = $this->getAccessToken();
        // Yii::info([__METHOD__, __LINE__, $accessToken]);
        if ($this->scope == 'snsapi_base') {
            $userAttributes = [];
            $userAttributes['openid'] = $accessToken->getParam('openid');
            return $userAttributes;
        }

        // 只有snsapi_userinfo才有权限调用sns/userinfo接口
        $userAttributes = $this->api(
            "sns/userinfo",
            'GET',
            [
                'access_token' => $accessToken->getToken(),
                'openid' => $accessToken->getParam('openid'),
                'lang' => 'zh_CN'
            ]
        );

        return $userAttributes;
    }

    /**
     * @inheritdoc
     */
    protected function defaultReturnUrl()
    {
        $params = $_GET;
        unset($params['code']);
        unset($params['state']);
        $params[0] = Yii::$app->controller->getRoute();

        return Yii::$app->getUrlManager()->createAbsoluteUrl($params);
    }

    /**
     * Generates the auth state value.
     * @return string auth state value.
     */
    protected function generateAuthState()
    {
        return sha1(uniqid(get_class($this), true));
    }

    /**
     * @inheritdoc
     */
    protected function defaultName()
    {
        return 'weixin_mp';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return 'Weixin_mp';
    }

    /**
     * @inheritdoc
     */
    protected function defaultViewOptions()
    {
        return [
            'popupWidth' => 800,
            'popupHeight' => 500,
        ];
    }

}
