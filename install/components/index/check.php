<?php

use Bitrix\Sale\Order;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

CModule::IncludeModule("sale");
CModule::IncludeModule("paybox.pay");

$strScriptName = PayBoxSignature::getOurScriptName();
$arrRequest = PayBoxIO::getRequest();
$objShop = CSalePaySystemAction::GetList('', array("PAY_SYSTEM_ID" => $arrRequest['PAYMENT_SYSTEM']));
$arrShop = $objShop->Fetch();

if (!empty($arrShop)) {
    $arrShopParams = unserialize($arrShop['PARAMS']);
} else {
    PayBoxIO::makeResponse(
        $strScriptName,
        '',
        'error',
        'Please re-configure the module PayBox in Bitrix CMS. The payment system should have a name ' . $arrRequest['PAYMENT_SYSTEM']
    );
}

$strSecretKey = $arrShopParams['SECRET_KEY']['VALUE'];

$strSalt = $arrRequest["pg_salt"];

$nOrderAmount = $arrRequest["pg_amount"];

if ($arrShopParams['ORDER_ID_TYPE']['VALUE'] === 'ORDER_NUMBER') {
    $nOrderId = Order::loadByAccountNumber($arrRequest['pg_order_id'])->getId();
} else {
    $nOrderId = (int)$arrRequest['pg_order_id'];
}

/*
 * Signature check
 */
if (!PayBoxSignature::check($arrRequest['pg_sig'], $strScriptName, $arrRequest, $strSecretKey)) {
    PayBoxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'signature is not valid',
        $strSalt
    );
}

if (!($arrOrder = CSaleOrder::GetByID($nOrderId))) {
    PayBoxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'order not found',
        $strSalt
    );
}

if ($nOrderAmount != $arrOrder['PRICE']) {
    PayBoxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'amount is not correct',
        $strSalt
    );
}

if ($arrOrder['PAYED'] === "Y") {
    PayBoxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        "ok",
        "Order already payed",
        $strSalt
    );
}

if ($arrOrder['CANCELED'] === "Y") {
    PayBoxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'Order canceled',
        $strSalt
    );
}

PayBoxIO::makeResponse(
    $strScriptName,
    $strSecretKey,
    "ok",
    "",
    $strSalt
);
