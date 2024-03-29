<?php

require('./exampleBase.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;

$contract = new Contract($web3->provider, $uniV2Json->abi);
$ownAccount = $testAddress;
$ownBalance;

// get chain id
$chainId = getChainId($web3->net);

echo 'Start to swap tokens' . PHP_EOL;

// swap usdc to dai on polygon
$contract = $contract->at($testUNIRouterAddress);
$path = [
    '0x2791Bca1f2de4661ED88A30C99A7a9449Aa84174',
    '0x8f3cf7ad23cd3cadbd9735aff958023239c6a063'
];
$amountIn = Utils::toWei('0.01', 'mwei');
$amountOut = Utils::toWei('9.6', 'ether');
$nonce = getNonce($eth, $ownAccount);

// checkout balance and approved allowance
$token = new Contract($web3->provider, $erc20Json->abi);
$token = $token->at($path[0]);
$allowance;
$token->call('balanceOf', $testAddress, [
    'from' => $testAddress
], function ($err, $result) use ($path, &$amountOut, &$ownBalance) {
    if ($err !== null) {
        throw $err;
    }
    if ($result && count($result) > 0) {
        $ownBalance = $result[0];
    }
});

if ($ownBalance->compare($amountIn) < 0) {
    throw new Error('Balance not enough');
}

$token->call('allowance', $testAddress, $testUNIRouterAddress, [
    'from' => $testAddress
], function ($err, $result) use ($path, &$amountOut, &$allowance) {
    if ($err !== null) {
        throw $err;
    }
    if ($result && count($result) > 0) {
        $allowance = $result[0];
    }
});

$gasPrice = '0x' . Utils::toWei('50', 'gwei')->toHex();

// approve
if ($allowance->compare($amountIn) < 0) {
    $estimatedApproveGas;
    $token->estimateGas('approve', $testUNIRouterAddress, $amountIn, [
        'from' => $testAddress,
    ], function ($err, $result) use (&$estimatedApproveGas) {
        if ($err !== null) {
            throw $err;
        }
        $estimatedApproveGas = $result->multiply(Utils::toBn(2));
    });
    $data = $token->getData('approve', $testUNIRouterAddress, $amountIn);
    $transaction = new Transaction([
        'nonce' => '0x' . $nonce->toHex(),
        'gas' => '0x' . $estimatedApproveGas->toHex(),
        'gasPrice' => $gasPrice,
        'data' => '0x' . $data,
        'chainId' => $chainId,
        'to' => $path[0]
    ]);
    $transaction->sign($testPrivateKey);
    $txHash = '';
    $eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $ownAccount, &$txHash) {
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
    $nonce = $nonce->add(Utils::toBn(1));
}

// get swap amounts
$getAmountsOutRes = getUniV2AmountsOut($contract, $amountIn, $path, [
    'from' => $testAddress
]);
echo 'Expect token output: ' . $getAmountsOutRes[1]->toString() . PHP_EOL;
$amountOut = $getAmountsOutRes[1];

$estimatedGas;
$contract->estimateGas('swapExactTokensForTokens', $amountIn, $amountOut, $path, $testAddress, 1700000000, [
    'from' => $testAddress
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    if ($result) {
        echo 'Estimate gas: ' . $result->toString() . PHP_EOL;
    }
    $estimatedGas = $result->multiply(Utils::toBn(2));
});
$data = $contract->getData('swapExactTokensForTokens', $amountIn, $amountOut, $path, $testAddress, 1700000000);
$nonce = getNonce($eth, $ownAccount);
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'gas' => '0x' . $estimatedGas->toHex(),
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
