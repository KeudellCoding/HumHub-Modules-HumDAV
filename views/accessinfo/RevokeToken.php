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
                <div class="panel-heading"><i class="fa far fa-address-card"></i> <span><strong>HumDAV</strong> Revoke Token</span></div>
                <div class="panel-body">
                    <p><strong>Are you sure you want to disable access with this token?</strong></p>
                    <div class="rounded bg-light p-3 mb-3">
                        <b><?= Html::encode($userToken->name) ?></b>
                        <hr>
                        <p><i class="fa fa-exclamation-triangle text-danger"></i> &nbsp;Access with this token will no longer be possible.</p>
                    </div>

                    <br />

                    <?php
                    $form = ActiveForm::begin();
                    echo Html::submitButton('Revoke access with this token', ['class' => 'btn btn-danger', 'data-ui-loader' => '', 'name' => 'revoke-token-action', 'value'=> 'revoke']);
                    ?>
                    <a class="btn btn-primary" href="<?= Url::to(['index']); ?>">Cancel</a>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
