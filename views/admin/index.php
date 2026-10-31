<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\helpers\Html;
use yii\helpers\Url;
use humhub\widgets\form\ActiveForm;
use humhub\modules\user\widgets\UserPickerField;
use humhub\modules\humdav\models\admin\EditForm;

?>

<div class="panel panel-default">
    <div class="panel-heading">HumHub DAV Access configuration</div>
    <div class="panel-body">

        <?php $form = ActiveForm::begin(['enableClientValidation' => true]); ?>

        <?= $form->field($model, 'active')->checkbox(); ?>
        
        <hr />

        <?= $form->field($model, 'instruction_location')->dropDownList(EditForm::getWidgetLocations()); ?>
        <?= $form->field($model, 'instruction_location_sort_order')->input('number', ['min' => 0]); ?>

        <hr />
        
        <?= $form->field($model, 'enabled_users')->widget(UserPickerField::class); ?>
        <?= $form->field($model, 'enable_password_auth')->checkbox(); ?>

        <hr />

        <?= $form->field($model, 'include_address')->checkbox(); ?>
        <?= $form->field($model, 'include_profile_image')->checkbox(); ?>
        <?= $form->field($model, 'include_birthday')->checkbox(); ?>
        <?= $form->field($model, 'include_gender')->checkbox(); ?>
        <?= $form->field($model, 'include_phone_numbers')->checkbox(); ?>
        <?= $form->field($model, 'include_url')->checkbox(); ?>

        <hr />

        <?= $form->field($model, 'enable_space_addressbooks')->checkbox(); ?>

        <hr />

        <?= $form->field($model, 'enable_browser_plugin')->checkbox(); ?>

        <span>Automatic account discovery uses the following redirects. Make sure your web server forwards these paths to HumHub:</span>
        <ul>
            <li><?=Url::to(['/.well-known/carddav'], true)?> &rarr; <?=Url::to(['/humdav/remote'], true)?></li>
        </ul>

        <hr />

        <?= Html::submitButton("Save", ['class' => 'btn btn-primary', 'data-ui-loader' => '']); ?>
        <a class="btn btn-secondary" href="<?= Url::to(['/admin/module']); ?>">Back to modules</a>

        <?php ActiveForm::end(); ?>
    </div>
</div>
