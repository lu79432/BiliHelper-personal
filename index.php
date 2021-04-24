<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2021 ~ 2022
 */


require 'vendor/autoload.php';


$filename = isset($argv[1]) ? $argv[1] : 'user';

$app = new BiliHelper\Core\App(__DIR__);
$app->load($filename . '.conf');
$app->start();
