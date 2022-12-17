<?php

require('./exampleBase.php');
require('./utils.php');

use Web3\Utils;
use Web3\Contract;
use Web3p\EthereumTx\Transaction;

// get chain id
$chainId = getChainId($web3->net);

$contract = new Contract($web3->provider, $uniV2Json->abi);
$ownAccount = $testAddress;

echo 'Start to swap eth to tokens' . PHP_EOL;

// swap eth to token
$contract = $contract->at($testUNIRouterAddress);
$amountIn = Utils::toWei('0.01', 'ether');
$amountOut;
$path = [
    '0xb4fbf271143f4fbf7b91a5ded31805e42b2208d6',
    '0x1f9840a85d5af5bf1d1762f925bdaddc4201f984'
];

// get swap amounts
$getAmountsOutRes = getUniV2AmountsOut($contract, $amountIn, $path, [
    'from' => $testAddress
]);
echo 'Expect token output: ' . $getAmountsOutRes[1]->toString() . PHP_EOL;
$amountOut = $getAmountsOutRes[1];

$estimatedGas;
$gasPrice = '0x' . Utils::toWei('50', 'gwei')->toHex();
$contract->estimateGas('swapExactETHForTokens', $amountOut, $path, $testAddress, 1700000000, [
    'from' => $testAddress,
    'value' => '0x' . $amountIn->toHex()
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    $estimatedGas = $result->multiply(Utils::toBn(2));
});
$data = $contract->getData('swapExactETHForTokens', $amountOut, $path, $testAddress, 1700000000);
$nonce = getNonce($eth, $ownAccount);
$transaction = new Transaction([
    'nonce' => '0x' . $nonce->toHex(),
    'gas' => '0x' . $estimatedGas->toHex(),
    'gasPrice' => $gasPrice,
    'data' => '0x' . $data,
    'value' => '0x' . $amountIn->toHex(),
    'chainId' => $chainId,
    'to' => $testUNIRouterAddress
]);
$transaction->sign($testPrivateKey);
$txHash = '';
$eth->sendRawTransaction('0x' . $transaction->serialize(), function ($err, $transaction) use ($eth, $ownAccount, &$txHash) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Swap eth to tokens tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});

$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}

echo "Transaction was confirmed, let's swap back to eth" . PHP_EOL;
$token = new Contract($web3->provider, $erc20Json->abi);
$token = $token->at($path[1]);

// swap amountIn and amountOut
$tmp = $amountIn;
$amountIn = $amountOut;
$amountOut = $tmp;

$allowance;
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
    $nonce = $nonce->add(Utils::toBn(1));
    $transaction = new Transaction([
        'nonce' => '0x' . $nonce->toHex(),
        'gas' => '0x' . $estimatedApproveGas->toHex(),
        'gasPrice' => $gasPrice,
        'data' => '0x' . $data,
        'chainId' => $chainId,
        'to' => $path[1]
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
}

// swap back
$newPath = [
    $path[1],
    $path[0]
];

// get swap amounts
$getAmountsOutRes = getUniV2AmountsOut($contract, $amountIn, $newPath, [
    'from' => $testAddress
]);
echo 'Expect token output: ' . $getAmountsOutRes[1]->toString() . PHP_EOL;
$amountOut = $getAmountsOutRes[1];

$contract->estimateGas('swapExactTokensForETH', $amountIn, $amountOut, $newPath, $testAddress, 1700000000, [
    'from' => $testAddress
], function ($err, $result) use (&$estimatedGas) {
    if ($err !== null) {
        throw $err;
    }
    $estimatedGas = $result->multiply(Utils::toBn(2));
});

$data = $contract->getData('swapExactTokensForETH', $amountIn, $amountOut, $newPath, $testAddress, 1700000000);
$nonce = $nonce->add(Utils::toBn(1));
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
        echo 'Error: ' . $err->getMessage();
        return;
    }
    echo 'Swap tokens to eth tx hash: ' . $transaction . PHP_EOL;
    $txHash = $transaction;
});
$transaction = confirmTx($eth, $txHash);
if (!$transaction) {
    throw new Error('Transaction was not confirmed.');
}
echo "Congratulation! The tokens did swap back to eth!" . PHP_EOL;