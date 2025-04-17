<?php
require_once 'Blockchain.php';

/**
 * BlockchainManager class to handle the persistence of the blockchain
 */
class BlockchainManager {
    private $pdo;
    private $blockchain;
    private $tableName = 'blockchain';

    /**
     * Constructor for creating a new blockchain manager
     * 
     * @param PDO $pdo The PDO database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeBlockchain();
    }

    /**
     * Initialize the blockchain from the database or create a new one
     */
    private function initializeBlockchain() {
        // Check if the blockchain table exists
        $this->createTableIfNotExists();

        // Try to load the blockchain from the database
        $blocks = $this->loadBlocksFromDatabase();

        if (empty($blocks)) {
            // Create a new blockchain if none exists
            $this->blockchain = new Blockchain();
            $this->saveBlockchain();
        } else {
            // Reconstruct the blockchain from the database
            $this->blockchain = new Blockchain();
            $this->reconstructBlockchain($blocks);
        }
    }

    /**
     * Create the blockchain table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            block_index INT NOT NULL,
            timestamp INT NOT NULL,
            data TEXT NOT NULL,
            previous_hash VARCHAR(64) NOT NULL,
            hash VARCHAR(64) NOT NULL,
            nonce INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->pdo->exec($sql);
    }

    /**
     * Load blocks from the database
     * 
     * @return array The blocks from the database
     */
    private function loadBlocksFromDatabase() {
        $stmt = $this->pdo->query("SELECT * FROM {$this->tableName} ORDER BY block_index ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reconstruct the blockchain from the database
     * 
     * @param array $blocks The blocks from the database
     */
    private function reconstructBlockchain($blocks) {
        // Clear the chain and pending transactions
        $this->blockchain = new Blockchain();
        
        // Add each block to the chain
        foreach ($blocks as $blockData) {
            $block = new Block(
                $blockData['block_index'],
                json_decode($blockData['data'], true),
                $blockData['previous_hash']
            );
            
            $block->timestamp = $blockData['timestamp'];
            $block->hash = $blockData['hash'];
            $block->nonce = $blockData['nonce'];
            
            // Replace the block in the chain
            $this->blockchain->getChain()[$blockData['block_index']] = $block;
        }
    }

    /**
     * Save the blockchain to the database
     */
    public function saveBlockchain() {
        // Clear the existing blockchain in the database
        $this->pdo->exec("TRUNCATE TABLE {$this->tableName}");
        
        // Insert each block into the database
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->tableName} 
            (block_index, timestamp, data, previous_hash, hash, nonce) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($this->blockchain->getChain() as $block) {
            $stmt->execute([
                $block->index,
                $block->timestamp,
                json_encode($block->data),
                $block->previousHash,
                $block->hash,
                $block->nonce
            ]);
        }
    }

    /**
     * Add a transaction to the blockchain
     * 
     * @param array $transaction The transaction data
     */
    public function addTransaction($transaction) {
        $this->blockchain->addTransaction($transaction);
        $this->saveBlockchain();
    }

    /**
     * Mine pending transactions and add a new block to the chain
     * 
     * @param string $miningRewardAddress The address to receive the mining reward
     * @return Block The newly mined block
     */
    public function minePendingTransactions($miningRewardAddress) {
        $block = $this->blockchain->minePendingTransactions($miningRewardAddress);
        $this->saveBlockchain();
        return $block;
    }

    /**
     * Get the balance of an address
     * 
     * @param string $address The address to check the balance for
     * @return float The balance of the address
     */
    public function getBalanceOfAddress($address) {
        return $this->blockchain->getBalanceOfAddress($address);
    }

    /**
     * Get all transactions for an address
     * 
     * @param string $address The address to get transactions for
     * @return array All transactions involving the address
     */
    public function getAllTransactionsForAddress($address) {
        return $this->blockchain->getAllTransactionsForAddress($address);
    }

    /**
     * Check if the blockchain is valid
     * 
     * @return bool True if the blockchain is valid, false otherwise
     */
    public function isChainValid() {
        return $this->blockchain->isChainValid();
    }

    /**
     * Get the entire blockchain
     * 
     * @return array The blockchain
     */
    public function getChain() {
        return $this->blockchain->getChain();
    }

    /**
     * Get all pending transactions
     * 
     * @return array The pending transactions
     */
    public function getPendingTransactions() {
        return $this->blockchain->getPendingTransactions();
    }
} 