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

?>

<div class="panel panel-default">
    <div class="panel-heading">HumHub DAV Access configuration</div>
    <div class="panel-body">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'active')->checkbox(); ?>

        <hr>

        <?= Html::submitButton("Save", ['class' => 'btn btn-primary', 'data-ui-loader' => '']); ?>
        <a class="btn btn-default" href="<?= Url::to(['/admin/module']); ?>">Back to modules</a>

        <?php ActiveForm::end(); ?>
    </div>
</div>