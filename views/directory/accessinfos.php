<?php
/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

use yii\helpers\Url;
use humhub\widgets\AjaxButton;
use humhub\modules\directory\widgets\Menu;

\humhub\assets\JqueryKnobAsset::register($this);
?>

<div class="container">
    <div class="row">
        <div class="col-md-2">
            <?= Menu::widget(); ?>
        </div>
        <div class="col-md-10">
            <div class="panel panel-default">
                <div class="panel-heading"><strong>HumDAV</strong> Access Infos</div>
                <div class="panel-body">
                    <p>
                        If you visit this page with an iPad or iPhone, you can download an automatically generated configuration file here:
                        <br>
                        <a href="<?=Url::to(['/humdav/generate/mobileconfig'])?>" target="_blank">Download iOS Configuration File</a>
                    </p>
                    <hr>
                    <p>
                        Otherwise you can enter the following configuration into your device:
                        <dl>
                            <dt>Type:</dt>
                            <dd>CardDAV</dd>

                            <dt>DAV Url:</dt>
                            <dd><?=Url::to(['/humdav/remote/addressbooks/'.Yii::$app->user->identity->username.'/'], true)?></dd>

                            <dt>Username:</dt>
                            <dd><?=Yii::$app->user->identity->username?></dd>

                            <dt>Password:</dt>
                            <dd><i>Your HumHub Password</i></dd>

                            <dt>Email:</dt>
                            <dd><?=Yii::$app->user->identity->email?></dd>
                        </dl>
                    </p>
                    <hr>
                    <p>
                        If authentication is not possible, this may have the following reasons:
                        <ul>
                            <li>The module has not been activated yet.</li>
                            <li>You have not been authorized for access.</li>
                            <li>You have a typo somewhere.</li>
                            <li>Also check the upper and lower case of your username.</li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>