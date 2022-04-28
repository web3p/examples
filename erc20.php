<?php

require('./exampleBase.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;


$mainAccount = $testAddress2;
$ownAccount = $testAddress;

$contract = new Contract($web3->provider, $erc20Json->abi);

echo 'Start to deploy erc20 contract' . PHP_EOL;

// deploy erc20 token
$data = $contract->bytecode($erc20Json->bytecode)->getData('Web3Token', 'W3');
$nonce = getNonce($eth, $ownAccount);
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'gas' => '0xfd240',
    'gasPrice' => '0x' . Utils::toWei('5', 'gwei')->toHex(),
    'data' => '0x' . $data,
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
$contractAddress = $transaction->contractAddress;
echo "\nContract has been created:) block number: " . $transaction->blockNumber . PHP_EOL . "Contract address: " . $contractAddress . PHP_EOL;
$contract = $contract->at($contractAddress);
$estimatedGas = '0x200b20';

$contract->at($contractAddress)->estimateGas('transfer', $mainAccount, 10, [
    'from' => $ownAccount,
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    $estimatedGas = '0x' . $result->toHex();
});
$data = $contract->getData('transfer', $mainAccount, 10);
$nonce = $nonce->add(Utils::toBn(1));
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'to' => $contractAddress,
    'gas' => $estimatedGas,
    'gasPrice' => '0x' . Utils::toWei('5', 'gwei')->toHex(),
    'data' => '0x' . $data,
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

$contract->call('balanceOf', $ownAccount, function ($err, $result) {
    if ($err !== null) {
        throw $err;
    }
    if ($result) {
        echo 'Token balance of signer: ' . $result[0]->toHex() . PHP_EOL;
    }
});

$contract->call('balanceOf', $mainAccount, function ($err, $result) {
    if ($err !== null) {
        throw $err;
    }
    if ($result) {
        echo 'Token balance of other account: ' . $result[0]->toHex() . PHP_EOL;
    }
});
