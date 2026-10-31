<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\widgets\form\ActiveForm;
use humhub\helpers\Html;
use humhub\assets\ClipboardJsAsset;
use yii\helpers\Json;
use yii\helpers\Url;

ClipboardJsAsset::register($this);
$this->registerJs('$("#humdav-copy-token").on("click", function () { clipboard.writeText(' . Json::htmlEncode($token) . '); });');

?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading"><i class="fa far fa-address-card"></i> <span><strong>HumDAV</strong> Generate Token</span></div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin(['enableClientValidation' => true]); ?>

                    <?= $form->field($userToken, 'name') ?>

                    <div class="rounded bg-light p-3 mb-3">
                        <p>Your token: <b><?= Html::encode($token) ?></b></p>
                        <?= Html::button('<i class="fa fa-clipboard" aria-hidden="true"></i> Copy to clipboard', ['id' => 'humdav-copy-token', 'class' => 'btn btn-link']) ?>
                        <hr>
                        <p><i class="fa fa-exclamation-triangle text-danger"></i> &nbsp;You cannot view or edit this token later.</p>
                        <p><i class="fa fa-info-circle text-info"></i> &nbsp;Please use this token for one device or application only.</p>
                    </div>
                    <br />
                    <?= Html::submitButton("Save, activate token and back", ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
                    <a class="btn btn-secondary" href="<?= Url::to(['index']); ?>">Back</a>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
