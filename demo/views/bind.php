<?php
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

common\assets\FontAwesome::register($this);
?>

<?php
$cat = Html::getInputId($model,'bind_acc');
$js=<<<EOD
    $("#$cat").change( function() {
        var cat = $('input:radio:checked').val();
        if (cat == 1) {
            $("#account").show();
        } else {
            $("#account").hide();
        }
    }).change();
EOD;
    $this->registerJs($js, yii\web\View::POS_READY);
?>

<div class="oauth-buttons">
<?php $form = ActiveForm::begin(['id' => 'bind-form']); ?>

    <img src = "<?= ArrayHelper::getValue($client_info, 'attributes.avatar_url')?>" class="avatar" />
    <div><span><?= ArrayHelper::getValue($client_info, 'attributes.username')?></span></div>

    <?php echo $form->field($model, 'bind_acc')->radioList(['0' => '创建新账号', '1' => '绑定已有账号'], ['class' => 'bind_acc'])->label('选择') ?>

    <div id="account">
        <?php echo $form->field($model, 'mobile')->textInput(['class' => '', 'id' => 'mobile'])->label('手机') ?>
        <?php echo $form->field($model, 'password')->passwordInput()->label('密码') ?>
    </div>

    <div class="form-group">
        <?php echo Html::submitButton('确定', ['class' => 'btn btn-primary', 'name' => 'bind-button']) ?>
    </div>

<?php ActiveForm::end(); ?>
</div>

