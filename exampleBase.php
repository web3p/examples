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
$testUNIAbi = '[
  {
    "inputs":[
      {"internalType":"uint256","name":"amountOutMin","type":"uint256"},
      {"internalType":"address[]","name":"path","type":"address[]"},
      {"internalType":"address","name":"to","type":"address"},
      {"internalType":"uint256","name":"deadline","type":"uint256"}
    ],
    "name":"swapExactETHForTokens",
    "outputs":[
      {"internalType":"uint256[]","name":"amounts","type":"uint256[]"}
    ],
    "stateMutability":"payable",
    "type":"function"
  },
  {
    "inputs":[
      {"internalType":"uint256","name":"amountIn","type":"uint256"},
      {"internalType":"uint256","name":"amountOutMin","type":"uint256"},
      {"internalType":"address[]","name":"path","type":"address[]"},
      {"internalType":"address","name":"to","type":"address"},
      {"internalType":"uint256","name":"deadline","type":"uint256"}
    ],
    "name":"swapExactTokensForTokens",
    "outputs":[
      {"internalType":"uint256[]","name":"amounts","type":"uint256[]"}
    ],
    "stateMutability":"nonpayable",
    "type":"function"
  },
  {
    "inputs":[
      {"internalType":"uint256","name":"amountIn","type":"uint256"},
      {"internalType":"uint256","name":"amountOutMin","type":"uint256"},
      {"internalType":"address[]","name":"path","type":"address[]"},
      {"internalType":"address","name":"to","type":"address"},
      {"internalType":"uint256","name":"deadline","type":"uint256"}
    ],
    "name":"swapExactTokensForETH",
    "outputs":[
      {"internalType":"uint256[]","name":"amounts","type":"uint256[]"}
    ],
    "stateMutability":"nonpayable",
    "type":"function"
  }
]';
$testUNIRouterAddress = '0x10ed43c718714eb63d5aa57b78b54704e256024e';