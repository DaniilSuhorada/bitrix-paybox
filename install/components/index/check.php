<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

CModule::IncludeModule("sale");
CModule::IncludeModule("paybox.pay");

$strScriptName = payboxSignature::getOurScriptName();
$arrRequest = payboxIO::getRequest();
$objShop = CSalePaySystemAction::GetList('', array("PAY_SYSTEM_ID" => $arrRequest['PAYMENT_SYSTEM']));
$arrShop = $objShop->Fetch();

if (!empty($arrShop)) {
    $arrShopParams = unserialize($arrShop['PARAMS']);
} else {
    payboxIO::makeResponse(
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
    $nOrderId = \Bitrix\Sale\Order::loadByAccountNumber($arrRequest['pg_order_id'])->getId();
} else {
    $nOrderId = (int)$arrRequest['pg_order_id'];
}

/*
 * Signature check
 */
if (!payboxSignature::check($arrRequest['pg_sig'], $strScriptName, $arrRequest, $strSecretKey)) {
    payboxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'signature is not valid',
        $strSalt
    );
}

if (!($arrOrder = CSaleOrder::GetByID($nOrderId))) {
    payboxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'order not found',
        $strSalt
    );
}

if ($nOrderAmount != $arrOrder['PRICE']) {
    payboxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'amount is not correct',
        $strSalt
    );
}

if ($arrOrder['PAYED'] === "Y") {
    payboxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        "ok",
        "Order alredy payed",
        $strSalt
    );
}

if ($arrOrder['CANCELED'] === "Y") {
    payboxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'Order canceled',
        $strSalt
    );
}

payboxIO::makeResponse(
    $strScriptName,
    $strSecretKey,
    "ok",
    "",
    $strSalt
);
