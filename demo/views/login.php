<?php
use common\wosotech\Util;
use rest\models\Need;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

$this->title = '个人中心';
$this->params['search_placeholder'] = '搜索您感兴趣的代理人';
$this->registerJsFile('@web/static/js/libs/require.js', [
    'data-main' => 'static/js/main',
    'data-start' => 'pages/denglu',
]);
//$this->registerCssFile('@web/static/css/my.css');
common\assets\FontAwesome::register($this);

?>

<div class="signin-alert-wrapper">
<?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
<?php echo $form->field($model, 'identity') ?>
<?php echo $form->field($model, 'password')->passwordInput() ?>
<?php echo $form->field($model, 'rememberMe')->checkbox() ?>
<div style="color:#999;margin:1em 0">
    <?php echo Yii::t('frontend', 'If you forgot your password you can reset it <a href="{link}">here</a>', [
        'link'=>yii\helpers\Url::to(['sign-in/request-password-reset'])
    ]) ?>
</div>
<div class="form-group">
    <?php echo Html::submitButton('登录', ['class' => 'btn btn-primary btn-capsule', 'name' => 'login-button']) ?>
    <a href="<?= Url::to(['site/signup']) ?>" class="btn btn-secondary btn-capsule">注册</a>
</div>

<div class="form-group">
    <?php $authAuthChoice = yii\authclient\widgets\AuthChoice::begin([
        'baseAuthUrl' => ['/site/oauth']
    ]); ?>
    <ul>
        <a href="<?= $authAuthChoice->createClientUrl($authAuthChoice->clients['qq']) ?>"><span class="fa fa-qq"> QQ</span></a>
        &nbsp;&nbsp;&nbsp;&nbsp;

        <a href="<?= $authAuthChoice->createClientUrl($authAuthChoice->clients['weixin']) ?>"><span class="fa fa-weixin"> Weixin</span></a>

    </ul>
    <?php yii\authclient\widgets\AuthChoice::end(); ?>
</div>
<?php ActiveForm::end(); ?>
</div>


<?php
/*
         <?php foreach ($authAuthChoice->getClients() as $id => $client): ?>
            <li><?php //echo $authAuthChoice->clientLink($client) ?></li>
            <li><?php echo Html::a($client->getName() . $id, $authAuthChoice->createClientUrl($client), []); ?></li>
        <?php endforeach; ?>

        &nbsp;&nbsp;&nbsp;&nbsp;
        <a href="<?= $authAuthChoice->createClientUrl($authAuthChoice->clients['github']) ?>"><span class="fa fa-github"> github</span></a>

 */