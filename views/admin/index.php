<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use yii\helpers\Html;
use yii\helpers\Url;
use humhub\widgets\ActiveForm;
use humhub\modules\user\widgets\UserPickerField;

?>

<div class="panel panel-default">
    <div class="panel-heading">HumHub DAV Access configuration</div>
    <div class="panel-body">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'active')->checkbox(); ?>
        
        <hr />

        <?= $form->field($model, 'enabled_users')->widget(UserPickerField::class); ?>

        <hr />

        <?= $form->field($model, 'include_address')->checkbox(); ?>
        <?= $form->field($model, 'include_profile_image')->checkbox(); ?>
        <?= $form->field($model, 'include_birthday')->checkbox(); ?>
        <?= $form->field($model, 'include_gender')->checkbox(); ?>
        <?= $form->field($model, 'include_phone_numbers')->checkbox(); ?>
        <?= $form->field($model, 'include_url')->checkbox(); ?>

        <hr />

        <?= $form->field($model, 'enable_browser_plugin')->checkbox(); ?>

        <hr />

        <?= Html::submitButton("Save", ['class' => 'btn btn-primary', 'data-ui-loader' => '']); ?>
        <a class="btn btn-default" href="<?= Url::to(['/admin/module']); ?>">Back to modules</a>
        <a class="btn btn-warning" href="<?= Url::to(['/humdav/admin/update']); ?>" data-ui-loader="">Pull module update</a>
        <a>Current Version: <?= $version_infos['local_version']; ?>, Online Version: <?= $version_infos['github_version']; ?></a>

        <?php ActiveForm::end(); ?>
    </div>
</div>