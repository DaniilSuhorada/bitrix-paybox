<?php

use PayBox\Result;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

CModule::IncludeModule('sale');
CModule::IncludeModule('paybox.pay');

$resultHandler = new Result();
$resultHandler->handleResultRequest();
