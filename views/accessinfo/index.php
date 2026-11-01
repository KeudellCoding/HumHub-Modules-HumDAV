<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use humhub\libs\ActionColumn;
use humhub\helpers\Html;
use humhub\modules\humdav\models\UserToken;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\GridView;
use yii\helpers\Url;

?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading"><i class="fa far fa-address-card"></i> <span><strong>HumDAV</strong> <?= Yii::t('HumdavModule.base', 'Access Infos') ?></span></div>
                <div class="panel-body">
                    <ul id="tabs" class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a id="login-options-tab" class="nav-link active" href="#login-options" data-bs-toggle="tab" role="tab" aria-controls="login-options" aria-selected="true"><?= Yii::t('HumdavModule.base', 'Login Options') ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a id="auto-config-files-tab" class="nav-link" href="#auto-config-files" data-bs-toggle="tab" role="tab" aria-controls="auto-config-files" aria-selected="false"><?= Yii::t('HumdavModule.base', 'Automatic Configuration Files') ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a id="settings-tab" class="nav-link" href="#settings" data-bs-toggle="tab" role="tab" aria-controls="settings" aria-selected="false"><?= Yii::t('HumdavModule.base', 'Settings') ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a id="troubleshooting-tab" class="nav-link" href="#troubleshooting" data-bs-toggle="tab" role="tab" aria-controls="troubleshooting" aria-selected="false"><?= Yii::t('HumdavModule.base', 'Troubleshooting') ?></a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="login-options" role="tabpanel" aria-labelledby="login-options-tab">
                            <div class="">
                                <div class="row">
                                    <div class="col-md-7">
                                        <h4><?= Yii::t('HumdavModule.base', 'Default login options:') ?></h4>
                                        <p>
                                            <?= Yii::t('HumdavModule.base', 'You can enter the following configuration details into your device (') ?><a href="https://wiki.davical.org/index.php/CardDAV_Clients" target="_blank" rel="noopener noreferrer"><?= Yii::t('HumdavModule.base', 'List with some CardDAV clients') ?></a>):
                                            <dl>
                                                <dt><?= Yii::t('HumdavModule.base', 'Type:') ?></dt>
                                                <dd>CardDAV</dd>

                                                <dt><?= Yii::t('HumdavModule.base', 'CardDAV Url:') ?></dt>
                                                <dd><?=Url::to(['/humdav/remote/addressbooks/'.Yii::$app->user->identity->guid.'/'], true)?></dd>

                                                <dt><?= Yii::t('HumdavModule.base', 'CardDAV Principal URL (iOS and macOS):') ?></dt>
                                                <dd><?=Url::to(['/humdav/remote/principals/'.Yii::$app->user->identity->guid.'/'], true)?></dd>

                                                <dt><?= Yii::t('HumdavModule.base', 'Username:') ?></dt>
                                                <dd><?=Html::encode(Yii::$app->user->identity->username)?></dd>

                                                <dt><?= Yii::t('HumdavModule.base', 'Password:') ?></dt>
                                                <dd><i><?=$enablePasswordAuth ? Yii::t('HumdavModule.base', 'Your HumHub password or a valid token') : Yii::t('HumdavModule.base', 'A valid token')?></i></dd>

                                                <dt><?= Yii::t('HumdavModule.base', 'Auth Type:') ?></dt>
                                                <dd>Basic</dd>

                                                <?php if ($calendarEnabled) { ?>
                                                    <hr>
                                                    <dt><?= Yii::t('HumdavModule.base', 'CalDAV URL (Calendar module):') ?></dt>
                                                    <dd><?= Html::encode($calDavUrl) ?></dd>
                                                    <dd><?= Yii::t('HumdavModule.base', 'Use the credentials from the Calendar export dialog. Automatic configuration profiles include the Calendar credentials when CalDAV is selected.') ?></dd>
                                                <?php } ?>

                                            </dl>
                                        </p>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="float-end">
                                            <?= Button::success(Yii::t('HumdavModule.base', 'Add new Token'))->icon('add')->sm()->link(['generate-token']) ?>
                                        </div>
                                        <h4><?= Yii::t('HumdavModule.base', 'Login tokens:') ?></h4>
                                        <?= GridView::widget([
                                            'dataProvider' => $dataProvider,
                                            'filterModel' => $searchModel,
                                            'tableOptions' => ['class' => 'table table-hover'],
                                            'columns' => [
                                                ['attribute' => 'name'],
                                                [
                                                    'attribute' => 'last_time_used',
                                                    'value' => function (UserToken $userToken) {
                                                        if (empty($userToken->last_time_used))
                                                            return Yii::t('HumdavModule.base', 'Never');
                                                        else
                                                            return $userToken->last_time_used;
                                                    }
                                                ],
                                                [
                                                    'class' => ActionColumn::class,
                                                    'actions' => function (UserToken $userToken, $key, $index) {
                                                        if (!$userToken->canEdit()) return [];

                                                        return [
                                                            Yii::t('HumdavModule.base', 'More Information') => ['token-info'],
                                                            '---',
                                                            Yii::t('HumdavModule.base', 'Revoke Access') => ['revoke-token'],
                                                        ];
                                                    }
                                                ],
                                            ],
                                        ]) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="auto-config-files" role="tabpanel" aria-labelledby="auto-config-files-tab">
                            <p>
                                <?= Yii::t('HumdavModule.base', 'If you visit this page with iOS or macOS, you can download an automatically generated configuration file here:') ?>
                            </p>
                            <?= Html::beginForm(['/humdav/accessinfo/mobileconfig'], 'post', ['target' => '_blank', 'data-pjax' => '0']) ?>
                                <div class="form-check mb-3">
                                    <?= Html::checkbox('include_caldav', $calendarEnabled, [
                                        'id' => 'mobileconfig-include-caldav',
                                        'class' => 'form-check-input',
                                        'uncheck' => '0',
                                        'disabled' => !$calendarEnabled,
                                    ]) ?>
                                    <?= Html::label(Yii::t('HumdavModule.base', 'Include CalDAV configuration from the Calendar module'), 'mobileconfig-include-caldav', ['class' => 'form-check-label']) ?>
                                </div>
                                <?php if (!$calendarEnabled) { ?>
                                    <p class="text-muted"><?= Yii::t('HumdavModule.base', 'Enable the Calendar module to include CalDAV. CardDAV is always included.') ?></p>
                                <?php } else { ?>
                                    <p class="form-text"><?= Yii::t('HumdavModule.base', 'CardDAV is always included. CalDAV credentials are managed separately by the Calendar module.') ?></p>
                                <?php } ?>
                                <?= Html::submitButton(Yii::t('HumdavModule.base', 'Download iOS Configuration File'), ['name' => 'target', 'value' => 'ios', 'class' => 'btn btn-secondary']) ?>
                                <?= Html::submitButton(Yii::t('HumdavModule.base', 'Download macOS Configuration File'), ['name' => 'target', 'value' => 'osx', 'class' => 'btn btn-secondary']) ?>
                            <?= Html::endForm() ?>
                        </div>
                        <div class="tab-pane" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                            <?php $form = ActiveForm::begin(['enableClientValidation' => true]); ?>

                            <?= $form->field($userSettingsModel, 'provide_groups_as_separate_addressbooks')->checkbox(); ?>

                            <?= Html::submitButton("Save", ['class' => 'btn btn-primary', 'data-ui-loader' => '']); ?>

                            <?php ActiveForm::end(); ?>
                        </div>
                        <div class="tab-pane" id="troubleshooting" role="tabpanel" aria-labelledby="troubleshooting-tab">
                            <p>
                                <?= Yii::t('HumdavModule.base', 'If authentication is not possible, this may have the following reasons:') ?>
                                <ul>
                                    <li><?= Yii::t('HumdavModule.base', 'The HumDAV module has not been activated yet.') ?></li>
                                    <li><?= Yii::t('HumdavModule.base', 'For calendar access, the Calendar module must be enabled.') ?></li>
                                    <li><?= Yii::t('HumdavModule.base', 'You have not been authorized for access.') ?></li>
                                    <li><?= Yii::t('HumdavModule.base', 'You have a typo somewhere.') ?></li>
                                    <li><?= Yii::t('HumdavModule.base', 'Also check the upper and lower case of your username.') ?></li>
                                    <li><?= Yii::t('HumdavModule.base', 'You are using a URL from an old version of this module.') ?></li>
                                </ul>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
