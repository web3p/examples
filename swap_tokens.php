<?php

require('./exampleBase.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;



$contract = new Contract($web3->provider, $testUNIAbi);
$ownAccount = $testAddress;
$ownBalance = getBalance($eth, $ownAccount);

// get chain id
$chainId = getChainId($web3->net);

echo 'Start to swap tokens' . PHP_EOL;

// swap usdc to dai on polygon
$contract = $contract->at($testUNIRouterAddress);
$path = [
    '0x2791Bca1f2de4661ED88A30C99A7a9449Aa84174',
    '0x8f3cf7ad23cd3cadbd9735aff958023239c6a063'
];
$amountIn = '0x' . Utils::toWei('10', 'mwei')->toHex();
$amountOut = '0x' . Utils::toWei('9.6', 'ether')->toHex();
// make sure the function call will be successfully
$contract->call('swapExactTokensForTokens', $amountIn, $amountOut, $path, $testAddress, 1700000000, [
    'from' => $testAddress
], function ($err, $result) use ($path, &$amountOut) {
    if ($err !== null) {
        throw $err;
    }
    if ($result && isset($result['amounts']) && count($result['amounts']) == count($path)) {
        echo 'Expect token output: ' . $result['amounts'][1]->toString() . PHP_EOL;
        $amountOut = '0x' . $result['amounts'][1]->toHex();
    }
});
$estimatedGas = '0x' . Utils::toWei('200', 'kwei')->toHex();
$gasPrice = '0x' . Utils::toWei('40', 'gwei')->toHex();
$contract->estimateGas('swapExactTokensForTokens', $amountIn, $amountOut, $path, $testAddress, 1700000000, [
    'from' => $testAddress
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    if ($result) {
        echo 'Estimate gas: ' . $result->toString() . PHP_EOL;
    }
    $estimatedGas = '0x' . $result->toHex();
});
$data = $contract->getData('swapExactTokensForTokens', $amountIn, $amountOut, $path, $testAddress, 1700000000);
$nonce = getNonce($eth, $ownAccount);
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
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $ownAccount, &$txHash) {
    if ($err !== null) {
        throw $err;
    }
    echo 'Swap tokens tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});

$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}

echo "Transaction was confirmed" . PHP_EOL;
