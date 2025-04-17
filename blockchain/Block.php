<?php
/**
 * Block class representing a single block in the blockchain
 */
class Block {
    public $index;
    public $timestamp;
    public $data;
    public $previousHash;
    public $hash;
    public $nonce;

    /**
     * Constructor for creating a new block
     * 
     * @param int $index The index of the block in the chain
     * @param array $data The data to be stored in the block
     * @param string $previousHash The hash of the previous block
     */
    public function __construct($index, $data, $previousHash) {
        $this->index = $index;
        $this->timestamp = time();
        $this->data = $data;
        $this->previousHash = $previousHash;
        $this->nonce = 0;
        $this->hash = $this->calculateHash();
    }

    /**
     * Calculate the hash of the block
     * 
     * @return string The calculated hash
     */
    public function calculateHash() {
        return hash('sha256', 
            $this->index . 
            $this->timestamp . 
            json_encode($this->data) . 
            $this->previousHash . 
            $this->nonce
        );
    }

    /**
     * Mine the block by finding a hash that starts with a certain number of zeros
     * 
     * @param int $difficulty The number of leading zeros required
     */
    public function mineBlock($difficulty) {
        $target = str_repeat("0", $difficulty);
        
        while (substr($this->hash, 0, $difficulty) !== $target) {
            $this->nonce++;
            $this->hash = $this->calculateHash();
        }
        
        return $this->hash;
    }
} 