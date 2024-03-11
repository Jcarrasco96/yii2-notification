<?php

namespace jcarrasco96\notification;

use yii\web\AssetBundle;

class NotificationAsset extends AssetBundle
{

//    public $sourcePath = '@npm/bootstrap/dist';
    public $js = [
        'js/notification.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
//        'yii\bootstrap4\BootstrapAsset',
    ];

}