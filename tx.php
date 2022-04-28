<?php

require('./exampleBase.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;
use Web3p\EthereumTx\EIP1559Transaction;

$mainAccount = $testAddress2;
$ownAccount = $testAddress;
$ownBalance = getBalance($eth, $ownAccount);

// send 0.01 bnb back to main account
$nonce = getNonce($eth, $ownAccount);
$value = Utils::toWei('0.01', 'ether');
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'to' => $mainAccount,
    'gas' => '0xfd240',
    'gasPrice' => '0x' . Utils::toWei('5', 'gwei')->toHex(),
    'value' => '0x' . $value->toHex(),
    'chainId' => $chainId
]);
$transaction->sign($testPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $mainAccount, $ownAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});
$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}
echo "Transaction has been confirmed. " . " transaction hash: " . $txHash . " block number: " . $transaction->blockNumber . PHP_EOL;

// BSC didn't support EIP1559 yet
// $nonce = $nonce->add(Utils::toBn(1));
// $value = Utils::toWei('0.01', 'ether');
// $transaction = new EIP1559Transaction([
//     'nonce' => '0x' . $nonce->toHex(),
//     'to' => $mainAccount,
//     'gas' => '0xfd240',
//     'gasPrice' => '0x' . Utils::toWei('5', 'gwei')->toHex(),
//     'value' => '0x' . $value->toHex(),
//     'chainId' => $chainId,
//     'maxPriorityFeePerGas' => '0x1',
//     'maxFeePerGas' => Utils::toWei('5', 'gwei')->toHex(),
// ]);
// $transaction->sign($testPrivateKey);
// $txHash = '';
// $eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $mainAccount, $ownAccount, &$txHash) {
//     if ($err !== null) {
//         echo 'Error: ' . $err->getMessage();
//         return;
//     }
//     echo 'Tx hash: ' . $transaction . PHP_EOL;
//     $txHash = $transaction;
// });
// $transaction = confirmTx($eth, $txHash);
// if (!$transaction) {
//     throw new Error('Transaction was not confirmed.');
// }
// echo "Transaction has been confirmed. " . " transaction hash: " . $txHash . " block number: " . $transaction->blockNumber . PHP_EOL;