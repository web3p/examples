<?php

require('./exampleBase.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;



$contract = new Contract($web3->provider, $testUNIAbi);
$ownAccount = $testAddress;
$ownBalance = getBalance($eth, $ownAccount);
// transfer some ether to test account
$value = Utils::toWei('10', 'ether');

echo 'Start to swap bnb to tokens' . PHP_EOL;

// swap bnb to token
$contract = $contract->at($testUNIRouterAddress);
$path = [
    '0xbb4cdb9cbd36b01bd1cbaebf2de08d9173bc095c',
    '0x758d08864fb6cce3062667225ca10b8f00496cc2'
];
$amountIn = '0x' . Utils::toWei('0.001', 'ether')->toHex();
$contract->call('swapExactETHForTokens', $amountIn, $path, $testAddress, 1700000000, [
    'value' => $amountIn
], function ($err, $result) use ($path) {
    if ($err !== null) {
        throw $err;
    }
    if ($result && isset($result['amounts']) && count($result['amounts']) == count($path)) {
        echo 'Expect token output: ' . $result['amounts'][1]->toString() . PHP_EOL;
    }
});
$estimatedGas = '0x' . Utils::toWei('200', 'kwei')->toHex();
$gasPrice = '0x' . Utils::toWei('5', 'gwei')->toHex();
$contract->estimateGas('swapExactETHForTokens', $amountIn, $path, $testAddress, 1700000000, [
    'from' => $testAddress,
    'value' => $amountIn
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    $estimatedGas = '0x' . $result->toHex();
});
$data = $contract->getData('swapExactETHForTokens', $amountIn, $path, $testAddress, 1700000000);
$nonce = getNonce($eth, $ownAccount);
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'gas' => $estimatedGas,
    'gasPrice' => $gasPrice,
    'data' => '0x' . $data,
    'value' => $amountIn,
    'chainId' => $chainId,
    'to' => $testUNIRouterAddress
]);
$transaction->sign($testPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $mainAccount, $ownAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Swap bnb to tokens tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});

$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}

echo "Transaction was confirmed, let's swap back to bnb" . PHP_EOL;
$token = new Contract($web3->provider, $testAbi);
$token = $token->at($path[1]);
$data = $token->getData('approve', $testUNIRouterAddress, $amountIn);
$nonce = $nonce->add(Utils::toBn(1));
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'gas' => $estimatedGas,
    'gasPrice' => $gasPrice,
    'data' => '0x' . $data,
    'chainId' => $chainId,
    'to' => $path[1]
]);
$transaction->sign($testPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $mainAccount, $ownAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Approve tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});

$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}

// swap back
$newPath = [
    $path[1],
    $path[0]
];
$contract->estimateGas('swapExactTokensForETH', $amountIn, 0, $newPath, $testAddress, 1700000000, [
    'from' => $testAddress
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    $estimatedGas = '0x' . $result->toHex();
});
$data = $contract->getData('swapExactTokensForETH', $amountIn, 0, $newPath, $testAddress, 1700000000);
$nonce = $nonce->add(Utils::toBn(1));
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'gas' => $estimatedGas,
    'gasPrice' => $gasPrice,
    'data' => '0x' . $data,
    'chainId' => $chainId,
    'to' => $testUNIRouterAddress
]);
$transaction->sign($testPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $mainAccount, $ownAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Swap tokens to bnb tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});
$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}
echo "Congratulation! The tokens did swap back to bnb!" . PHP_EOL;