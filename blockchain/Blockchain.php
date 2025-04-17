<?php
require_once 'Block.php';

/**
 * Blockchain class to manage the blockchain operations
 */
class Blockchain {
    private $chain;
    private $difficulty;
    private $pendingTransactions;
    private $miningReward;

    /**
     * Constructor for creating a new blockchain
     * 
     * @param int $difficulty The mining difficulty (number of leading zeros required)
     * @param float $miningReward The reward for mining a block
     */
    public function __construct($difficulty = 2, $miningReward = 10) {
        $this->chain = [$this->createGenesisBlock()];
        $this->difficulty = $difficulty;
        $this->pendingTransactions = [];
        $this->miningReward = $miningReward;
    }

    /**
     * Create the first block in the chain (genesis block)
     * 
     * @return Block The genesis block
     */
    private function createGenesisBlock() {
        return new Block(0, ["message" => "Genesis Block"], "0");
    }

    /**
     * Get the latest block in the chain
     * 
     * @return Block The latest block
     */
    public function getLatestBlock() {
        return $this->chain[count($this->chain) - 1];
    }

    /**
     * Add a new transaction to the pending transactions
     * 
     * @param array $transaction The transaction data
     */
    public function addTransaction($transaction) {
        $this->pendingTransactions[] = $transaction;
    }

    /**
     * Mine pending transactions and add a new block to the chain
     * 
     * @param string $miningRewardAddress The address to receive the mining reward
     * @return Block The newly mined block
     */
    public function minePendingTransactions($miningRewardAddress) {
        // Create a new block with all pending transactions
        $block = new Block(
            count($this->chain),
            $this->pendingTransactions,
            $this->getLatestBlock()->hash
        );

        // Mine the block
        $block->mineBlock($this->difficulty);

        // Add the block to the chain
        $this->chain[] = $block;

        // Reset pending transactions and add mining reward
        $this->pendingTransactions = [
            [
                'from' => 'system',
                'to' => $miningRewardAddress,
                'amount' => $this->miningReward,
                'type' => 'mining_reward',
                'timestamp' => time()
            ]
        ];

        return $block;
    }

    /**
     * Get the balance of an address
     * 
     * @param string $address The address to check the balance for
     * @return float The balance of the address
     */
    public function getBalanceOfAddress($address) {
        $balance = 0;
        $transactions = $this->getAllTransactionsForAddress($address);

        foreach ($transactions as $transaction) {
            if ($transaction['to'] === $address) {
                $balance += $transaction['amount'];
            }
            if ($transaction['from'] === $address) {
                $balance -= $transaction['amount'];
            }
        }

        return $balance;
    }

    /**
     * Get all transactions for an address
     * 
     * @param string $address The address to get transactions for
     * @return array All transactions involving the address
     */
    public function getAllTransactionsForAddress($address) {
        $transactions = [];

        foreach ($this->chain as $block) {
            foreach ($block->data as $transaction) {
                if ($transaction['from'] === $address || $transaction['to'] === $address) {
                    $transactions[] = $transaction;
                }
            }
        }

        return $transactions;
    }

    /**
     * Check if the blockchain is valid
     * 
     * @return bool True if the blockchain is valid, false otherwise
     */
    public function isChainValid() {
        for ($i = 1; $i < count($this->chain); $i++) {
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i - 1];

            // Check if the current block's hash is valid
            if ($currentBlock->hash !== $currentBlock->calculateHash()) {
                return false;
            }

            // Check if the current block points to the previous block
            if ($currentBlock->previousHash !== $previousBlock->hash) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the entire blockchain
     * 
     * @return array The blockchain
     */
    public function getChain() {
        return $this->chain;
    }

    /**
     * Get all pending transactions
     * 
     * @return array The pending transactions
     */
    public function getPendingTransactions() {
        return $this->pendingTransactions;
    }
} 