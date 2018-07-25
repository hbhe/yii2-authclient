<?php
namespace hbhe\authclient;

use yii\authclient\OAuth2;
use yii\authclient\OAuthToken;
use yii\web\HttpException;
use Yii;

/**
 * Weixin(Wechat) allows authentication via Weixin(Wechat) OAuth.
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
 *              'qq' => [
 *                  'class' => 'hbhe\authclient\Qq',
 *                  'clientId' => 'qq_appid',
 *                  'clientSecret' => 'qq_appkey',
 *                  'normalizeUserAttributeMap' => [
 *                      'username' => 'nickname',
 *                      'avatar_url' => 'figureurl_qq_2',
 *                  ]
 *              ],
 *
 *             'weixin' => [
 *                 'class' => 'hbhe\authclient\Weixin',
 *                 'clientId' => 'weixin_appid',
 *                 'clientSecret' => 'weixin_appkey',
 *                  'normalizeUserAttributeMap' => [
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
    'attributes' => [
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
*/
class Weixin extends OAuth2
{
    /**
     * @inheritdoc
     */
    public $authUrl = 'https://open.weixin.qq.com/connect/qrconnect';

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
                'snsapi_login', //'snsapi_userinfo',
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
        $userAttributes = $this->api(
            "sns/userinfo",
            'GET',
            [
                'access_token' => $accessToken->getToken(),
                'openid' => $accessToken->getParam('openid'),
                'lang' => 'zh-CN'
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
        return 'weixin';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return 'Weixin';
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
