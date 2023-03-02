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

if ($arrShopParams['ORDER_ID_TYPE']['VALUE'] === 'ORDER_NUMBER') {
    $nOrderId = \Bitrix\Sale\Order::loadByAccountNumber($arrRequest['pg_order_id'])->getId();
} else {
    $nOrderId = (int)$arrRequest['pg_order_id'];
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

if (!payboxSignature::check($arrRequest['pg_sig'], $strScriptName, $arrRequest, $strSecretKey)) {
    payboxIO::makeResponse(
        $strScriptName,
        $strSecretKey,
        'error',
        'signature is not valid',
        $strSalt
    );
}

$strStatusRevoked = $arrShopParams['STATUS_REVOKED']['VALUE'];

CSaleOrder::Update($nOrderId, array(
    'STATUS_ID' => $strStatusRevoked,
    'PS_STATUS' => $strStatusRevoked,
));

$arrRequest['pg_salt'] = uniqid();
$arrRequest['pg_status'] = 'ok';

$arrRequest['pg_sig'] = payboxSignature::make('refund.php', $arrRequest, $strSecretKey);

payboxIO::makeResponse($strScriptName, $strSecretKey, "ok", "", $arrRequest['pg_salt']);
