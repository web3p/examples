<?php

$dir = dirname(__FILE__);

require($dir . '/vendor/autoload.php');

use Web3\Web3;

$web3 = new Web3('https://bsc-dataseed.binance.org/');
$eth = $web3->eth;

// BSC chain id, remember to set the right chain id
// Find chain id here: https://chainlist.org/
$chainId = 56;

/**
 * erc20Json
 * Openzeppelin abi from https://github.com/OpenZeppelin/openzeppelin-contracts
 */
$erc20JsonFile = file_get_contents($dir . '/ERC20.json');
$erc20Json = json_decode($erc20JsonFile);

/**
 * erc721Json
 * Openzeppelin abi from https://github.com/OpenZeppelin/openzeppelin-contracts
 */
$erc721JsonFile = file_get_contents($dir . '/ERC721.json');
$erc721Json = json_decode($erc721JsonFile);

/**
 * testPrivateKey
 * Never use this in real world
 * 
 * @var string
 */
$testPrivateKey = '';

/**
 * testAddress
 * Never use this in real world
 * 
 * @var string
 */
$testAddress = '';

/**
 * testAddress2
 * Never use this in real world
 * 
 * @var string
 */
$testAddress2 = '';

/**
 * settings for testing uniswap based DEX
 * 
 * $testUNIAbi
 * uniswap router abi
 * 
 * @var string
 * 
 * $testUNIRouterAddress
 * uniswap router address
 * 
 * @var string
 */
$uniV2JsonFile = file_get_contents($dir . '/UNIV2Router.json');
$uniV2Json = json_decode($uniV2JsonFile);
$testUNIRouterAddress = '0x10ed43c718714eb63d5aa57b78b54704e256024e';