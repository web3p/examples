<?php

require('./exampleBase.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;

$contract = new Contract($web3->provider, $erc721Json->abi);
$ownAccount = $testAddress;
$ownBalance = getBalance($eth, $ownAccount);

echo 'Start to deploy erc721 contract' . PHP_EOL;

// deploy erc721 token
$data = $contract->bytecode($erc721Json->bytecode)->getData("Web3Identity", "W3");
$nonce = getNonce($eth, $ownAccount);
$gasPrice = '0x' . Utils::toWei('5', 'gwei')->toHex();
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'gas' => 3000000,
    'gasPrice' => $gasPrice,
    'data' => '0x' . $data,
    'chainId' => $chainId
]);
$transaction->sign($testPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $ownAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Deploy tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});

$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}

$contractAddress = $transaction->contractAddress;
echo "\nContract has been created:) block number: " . $transaction->blockNumber . PHP_EOL . "Contract address: " . $contractAddress . PHP_EOL;
$contract = $contract->at($contractAddress);

$contract->at($contractAddress)->estimateGas('mint', $ownAccount, 1, 'https://avatars.githubusercontent.com/u/34670362?s=400', [
    'from' => $ownAccount,
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    $estimatedGas = '0x' . $result->toHex();
});
$data = $contract->getData('mint', $ownAccount, 1, 'https://avatars.githubusercontent.com/u/34670362?s=400');
$nonce = $nonce->add(Utils::toBn(1));
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'to' => $contractAddress,
    'gas' => $estimatedGas,
    'gasPrice' => $gasPrice,
    'data' => '0x' . $data,
    'chainId' => $chainId
]);
$transaction->sign($testPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $ownAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Mint tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});

$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}

echo "Minted!!!" . PHP_EOL;