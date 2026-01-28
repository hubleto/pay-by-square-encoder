<?php

require_once __DIR__ . '../../vendor/autoload.php';

try {
  $pbsEnc = new \Hubleto\Utilities\PayBySquareEncoder();

  $pbsEnc->setAmount(9.99);
  $pbsEnc->setIban("SK99 0000 0012 3456 7890");
  $pbsEnc->setBic("ABCDEFGH");
  $pbsEnc->setBeneficiaryName("The Beneficiary Ltd.");
  $pbsEnc->setVariableSymbol("2020123456");
  $pbsEnc->setConstantSymbol("0308");
  $pbsEnc->setSpecificSymbol("1000");
  $pbsEnc->setNote("Payment for the services");

  $pbsEnc->setXzPath("/usr/bin/xz");
  $encoded = $pbsEnc->getEncodedString();

  echo $encoded;
} catch (\Exception $e) {
  echo $e->getMessage();
}
