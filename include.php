<?php

use PayBox\ApiClient;
use PayBox\Helper;
use PayBox\Payment;
use PayBox\Result;

$libPath = '/bitrix/modules/paybox.pay/lib/lib/PayBox';

$classes = [
    ApiClient::class => "$libPath/ApiClient.php",
    Helper::class    => "$libPath/Helper.php",
    Result::class => "$libPath/Result.php",
    Payment::class => "$libPath/Payment.php",
];

CModule::AddAutoloadClasses('', $classes);
