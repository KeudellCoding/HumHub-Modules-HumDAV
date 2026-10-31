<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\widgets\form\ActiveForm;
use humhub\helpers\Html;
use yii\helpers\Url;

?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading"><i class="fa far fa-address-card"></i> <span><strong>HumDAV</strong> Token Info</span></div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($userToken, 'name')->textInput() ?>
                    
                    <dl>
                        <dt><?= $userToken->getAttributeLabel('last_time_used') ?>:</dt>
                        <dd><?= Html::encode($userToken->last_time_used ?? '-') ?></dd>

                        <dt><?= $userToken->getAttributeLabel('last_time_used_by_ip') ?>:</dt>
                        <dd><?= Html::encode($userToken->last_time_used_by_ip ?? '-') ?></dd>

                        <dt><?= $userToken->getAttributeLabel('last_time_used_by_user_agent') ?>:</dt>
                        <dd><?= Html::encode($userToken->last_time_used_by_user_agent ?? '-') ?></dd>

                        <dt><?= $userToken->getAttributeLabel('created_at') ?>:</dt>
                        <dd><?= Html::encode($userToken->created_at) ?></dd>

                        <dt><?= $userToken->getAttributeLabel('created_by_ip') ?>:</dt>
                        <dd><?= Html::encode($userToken->created_by_ip) ?></dd>

                        <dt><?= $userToken->getAttributeLabel('created_by_user_agent') ?>:</dt>
                        <dd><?= Html::encode($userToken->created_by_user_agent) ?></dd>
                    </dl>
                    
                    <?= Html::submitButton("Save and back", ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
                    <a class="btn btn-secondary" href="<?= Url::to(['index']); ?>">Back</a>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
