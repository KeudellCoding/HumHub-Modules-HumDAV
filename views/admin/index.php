<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use yii\helpers\Html;
use yii\helpers\Url;
use humhub\modules\ui\form\widgets\ActiveForm;
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

        <span>Since HumHub version 1.13, the .htaccess file already contains all the necessary changes to enable automatic detection (.well-known redirects). Please make sure that the current .htaccess file is on the server and/or that the following redirects are configured and/or possible:</span>
        <ul>
            <li><?=Url::to(['/.well-known/carddav'], true)?> &rarr; <?=Url::to(['/humdav/remote'], true)?></li>
            <li><?=Url::to(['/.well-known/caldav'], true)?> &rarr; <?=Url::to(['/humdav/remote'], true)?></li>
        </ul>
        <a href="https://sabre.io/dav/service-discovery/" target="_blank" rel="noopener noreferrer">Further infos</a>

        <hr />

        <?= Html::submitButton("Save", ['class' => 'btn btn-primary', 'data-ui-loader' => '']); ?>
        <a class="btn btn-default" href="<?= Url::to(['/admin/module']); ?>">Back to modules</a>

        <hr />

        <span>If you like this plugin, I would be very happy about a <a href="https://ko-fi.com/KeudellCoding" target="_blank" rel="noopener noreferrer">Ko-fi</a> :)</span>

        <?php ActiveForm::end(); ?>
    </div>
</div>