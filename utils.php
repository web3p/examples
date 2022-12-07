<?php

function getBalance($eth, $account) {
    $balance = 0;
    $eth->getBalance($account, function ($err, $rawBalance) use (&$balance) {
        if ($err !== null) {
            throw $err;
        }
        $balance = $rawBalance;
    });
    return $balance;
}

function getNonce($eth, $account) {
    $nonce = 0;
    $eth->getTransactionCount($account, function ($err, $count) use (&$nonce) {
        if ($err !== null) {
            throw $err;
        }
        $nonce = $count;
    });
    return $nonce;
}

function getTransactionReceipt($eth, $txHash) {
    $tx;
    $eth->getTransactionReceipt($txHash, function ($err, $transaction) use (&$tx) {
        if ($err !== null) {
            throw $err;
        }
        $tx = $transaction;
    });
    return $tx;
}

function getChainId($net) {
    $version;
    $net->version(function ($err, $ver) use (&$version) {
        if ($err !== null) {
            throw $err;
        }
        $version = $ver;
    });
    return $version;
}

function confirmTx($eth, $txHash) {
    $transaction = null;
    while (!$transaction) {
        $transaction = getTransactionReceipt($eth, $txHash);
        if ($transaction) {
            return $transaction;
        } else {
            echo "Sleep one second and wait transaction to be confirmed" . PHP_EOL;
            sleep(1);
        }
    }
}