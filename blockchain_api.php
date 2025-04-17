<?php
require_once 'config/database.php';
require_once 'blockchain/BlockchainManager.php';

session_start();

// Initialize the blockchain manager
$blockchainManager = new BlockchainManager($pdo);

// Set the content type to JSON
header('Content-Type: application/json');

// Get the action from the request
$action = $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'get_chain':
        // Get the entire blockchain
        $chain = $blockchainManager->getChain();
        echo json_encode([
            'success' => true,
            'chain' => $chain
        ]);
        break;

    case 'get_pending_transactions':
        // Get all pending transactions
        $pendingTransactions = $blockchainManager->getPendingTransactions();
        echo json_encode([
            'success' => true,
            'pending_transactions' => $pendingTransactions
        ]);
        break;

    case 'add_transaction':
        // Add a new transaction to the blockchain
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid request method'
            ]);
            break;
        }

        // Get the transaction data from the request
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid transaction data'
            ]);
            break;
        }

        // Add the transaction to the blockchain
        $blockchainManager->addTransaction($data);
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction added successfully'
        ]);
        break;

    case 'mine_block':
        // Mine a new block
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'error' => 'User not logged in'
            ]);
            break;
        }

        // Get the mining reward address
        $miningRewardAddress = $_POST['mining_reward_address'] ?? $_SESSION['user_id'];

        // Mine the block
        $block = $blockchainManager->minePendingTransactions($miningRewardAddress);
        
        echo json_encode([
            'success' => true,
            'message' => 'Block mined successfully',
            'block' => $block
        ]);
        break;

    case 'get_balance':
        // Get the balance of an address
        $address = $_GET['address'] ?? '';
        
        if (empty($address)) {
            echo json_encode([
                'success' => false,
                'error' => 'Address is required'
            ]);
            break;
        }

        $balance = $blockchainManager->getBalanceOfAddress($address);
        
        echo json_encode([
            'success' => true,
            'address' => $address,
            'balance' => $balance
        ]);
        break;

    case 'get_transactions':
        // Get all transactions for an address
        $address = $_GET['address'] ?? '';
        
        if (empty($address)) {
            echo json_encode([
                'success' => false,
                'error' => 'Address is required'
            ]);
            break;
        }

        $transactions = $blockchainManager->getAllTransactionsForAddress($address);
        
        echo json_encode([
            'success' => true,
            'address' => $address,
            'transactions' => $transactions
        ]);
        break;

    case 'validate_chain':
        // Validate the blockchain
        $isValid = $blockchainManager->isChainValid();
        
        echo json_encode([
            'success' => true,
            'is_valid' => $isValid
        ]);
        break;

    default:
        // Invalid action
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        break;
} 