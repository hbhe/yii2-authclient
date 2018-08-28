<?php

namespace hbhe\authclient;

use yii\authclient\OAuth2;
use yii\web\HttpException;
use Yii;

/**
 * QQ allows authentication via QQ OAuth.
 *
 * In order to use QQ OAuth you must register your application at <http://connect.qq.com/>.
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
 * @see http://connect.qq.com/
 * @see http://wiki.connect.qq.com/
 *
 * @author hbhe <57620133@qq.com>
 */

/*
 *
 Web QQ $attributes:
 $attributes = $client->getUserAttributes();
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
        'figureurl' => 'http://qzapp.qlogo.cn/qzapp/1014894xx/2F8E0C3BBD75BD4E1F35E366D5FFDFE7/30',
        'figureurl_1' => 'http://qzapp.qlogo.cn/qzapp/1014894xx/2F8E0C3BBD75BD4E1F35E366D5FFDFE7/50',
        'figureurl_2' => 'http://qzapp.qlogo.cn/qzapp/1014894xx/2F8E0C3BBD75BD4E1F35E366D5FFDFE7/100',
        'figureurl_qq_1' => 'http://thirdqq.qlogo.cn/qqapp/1014xx472/2F8E0C3BBD75BD4E1F35E366D5FFDFE7/40',
        'figureurl_qq_2' => 'http://thirdqq.qlogo.cn/qqapp/1014xx472/2F8E0C3BBD75BD4E1F35E366D5FFDFE7/100',
        'is_yellow_vip' => '0',
        'vip' => '0',
        'yellow_vip_level' => '0',
        'level' => '0',
        'is_yellow_year_vip' => '0',
        'id' => '2F8E0C3BBD75BD4E1F35E366D5FFDFE7',
        'username' => '江枫',
    ],
*/
class Qq extends OAuth2
{
    // 移动APP与网页应用要打通需向发邮件电请, 通过后可置为true, http://wiki.connect.qq.com/%E5%BC%80%E5%8F%91%E8%80%85%E5%8F%8D%E9%A6%88
    public $has_unionid = false;

    /**
     * @inheritdoc
     */
    public $authUrl = 'https://graph.qq.com/oauth2.0/authorize';
    /**
     * @inheritdoc
     */
    public $tokenUrl = 'https://graph.qq.com/oauth2.0/token';
    /**
     * @inheritdoc
     */
    public $apiBaseUrl = 'https://graph.qq.com';

    /**
     * @inheritdoc
     */
    public function init()
	{
        parent::init();
        if ($this->scope === null) {
            $this->scope = implode(' ', [
                'get_user_info',
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function buildAuthUrl(array $params = [])
	{
        return parent::buildAuthUrl($params);
    }

    /**
     * @inheritdoc
     */
    public function fetchAccessToken($authCode, array $params = [])
	{
		return parent::fetchAccessToken($authCode, $params);
    }

    /**
     * @inheritdoc
     */
    protected function defaultNormalizeUserAttributeMap()
    {
        return [
            'username' => 'nickname',
            'avatar_url' => 'figureurl_qq_2',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function initUserAttributes()
	{
        $user = $this->api('oauth2.0/me', 'GET');
		if ( isset($user['error']) ) {
            throw new HttpException(400, $user['error']. ':'. $user['error_description']);
		}
        \Yii::error(['unionid info', $user]);
		/*
        [
            'unionid info',
            [
                'client_id' => '101489472',
                'openid' => '2F8E0C3BBD75BD4E1F35E366D5FFDFE7',
                'unionid' => 'UID_4D3D8D1622531CED20E05745AAC5C759',
            ],
        ]
		*/
        $userAttributes = $this->api(
			"user/get_user_info",
			'GET',
			[
				'oauth_consumer_key' => $user['client_id'],
            	'openid' => $user['openid'],
			]
		);
		$userAttributes['id'] = $this->has_unionid ? $user['unionid'] : $user['openid'];
		return $userAttributes;
    }

    protected function sendRequest($request)
    {
        $response = $request->send();

        if (!$response->getIsOk()) {
            throw new \yii\authclient\InvalidResponseException($response, 'Request failed with code: ' . $response->getStatusCode() . ', message: ' . $response->getContent());
        }

        $content = $response->getContent();
        if (!empty($content)) {
            if (strpos($content, "callback(") === 0) {
                $count = 0;
                $jsonData = preg_replace('/^callback\(\s*(\\{.*\\})\s*\);$/is', '\1', $content, 1, $count);
                if ($count === 1) {
                    $response->setContent($jsonData);
                    $response->setFormat(\yii\httpclient\Client::FORMAT_JSON);
                }
            }
        }

        $arr = $response->getData();
        return $arr;
    }

    public function applyAccessTokenToRequest($request, $accessToken)
    {
        $data = $request->getData();
        $data['access_token'] = $accessToken->getToken();
        $data['unionid'] = $this->has_unionid ? 1 : 0;
        $request->setData($data);
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
    protected function defaultReturnUrl()
    {
        $params = $_GET;
        unset($params['code']);
        unset($params['state']);
        $params[0] = Yii::$app->controller->getRoute();

        return Yii::$app->getUrlManager()->createAbsoluteUrl($params);
    }

    /**
     * @inheritdoc
     */
    protected function defaultName()
	{
        return 'qq';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
	{
        return 'QQ';
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
