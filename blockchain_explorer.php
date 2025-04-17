<?php
require_once 'config/database.php';
require_once 'blockchain/BlockchainManager.php';

session_start();

// Initialize the blockchain manager
$blockchainManager = new BlockchainManager($pdo);

// Get the blockchain data
$chain = $blockchainManager->getChain();
$pendingTransactions = $blockchainManager->getPendingTransactions();
$isValid = $blockchainManager->isChainValid();

// Get the current user's address if logged in
$userAddress = $_SESSION['user_id'] ?? null;
$userBalance = $userAddress ? $blockchainManager->getBalanceOfAddress($userAddress) : 0;
$userTransactions = $userAddress ? $blockchainManager->getAllTransactionsForAddress($userAddress) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain Explorer - Medical Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .blockchain-status {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
        }
        .blockchain-status h5 {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .blockchain-status .badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .blockchain-status .total-blocks {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }
        .balance-card {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        .balance-card h5 {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .balance-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        .balance-card small {
            color: rgba(255, 255, 255, 0.8);
        }
        .block-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .block-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .transaction-card {
            border-left: 4px solid #28a745;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }
        .transaction-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        .hash-text {
            font-family: 'Courier New', monospace;
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.85rem;
            border: 1px solid #e9ecef;
        }
        .block-index {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        .timestamp {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 0;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Medical Inventory System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                My Account
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user_register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <h1 class="mb-4">Blockchain Explorer</h1>

        <!-- Blockchain Status -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="blockchain-status fade-in">
                    <h5>Blockchain Status</h5>
                    <span class="badge <?php echo $isValid ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $isValid ? 'Valid' : 'Invalid'; ?>
                    </span>
                    <div class="total-blocks"><?php echo count($chain); ?></div>
                    <small>Total Blocks</small>
                </div>
            </div>
            <?php if ($userAddress): ?>
            <div class="col-md-4">
                <div class="balance-card fade-in">
                    <h5>Your Balance</h5>
                    <h3><?php echo number_format($userBalance, 2); ?> coins</h3>
                    <small>Address: <?php echo $userAddress; ?></small>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pending Transactions -->
        <div class="card mb-4 fade-in">
            <div class="card-header">
                <h5 class="mb-0">Pending Transactions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pendingTransactions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <h4>No Pending Transactions</h4>
                        <p>There are no transactions waiting to be mined.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingTransactions as $transaction): ?>
                        <div class="transaction-card p-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>From:</strong> <?php echo $transaction['fromAddress']; ?></p>
                                    <p class="mb-1"><strong>To:</strong> <?php echo $transaction['toAddress']; ?></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p class="mb-1"><strong>Amount:</strong> <?php echo $transaction['amount']; ?> coins</p>
                                    <p class="mb-1 timestamp">
                                        <?php echo date('Y-m-d H:i:s', $transaction['timestamp']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Blockchain -->
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="mb-0">Blockchain</h5>
            </div>
            <div class="card-body">
                <?php if (empty($chain)): ?>
                    <div class="empty-state">
                        <i class="fas fa-cube"></i>
                        <h4>Empty Blockchain</h4>
                        <p>The blockchain is empty. No blocks have been mined yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($chain as $block): ?>
                        <div class="block-card p-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <span class="block-index">Block #<?php echo $block['index']; ?></span>
                                    <span class="timestamp float-end">
                                        <?php echo date('Y-m-d H:i:s', $block['timestamp']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <p class="mb-1"><strong>Hash:</strong> <span class="hash-text"><?php echo $block['hash']; ?></span></p>
                                    <p class="mb-1"><strong>Previous Hash:</strong> <span class="hash-text"><?php echo $block['previousHash']; ?></span></p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <h6 class="section-title">Transactions:</h6>
                                    <?php if (empty($block['data'])): ?>
                                        <p class="text-muted">No transactions in this block</p>
                                    <?php else: ?>
                                        <?php foreach ($block['data'] as $transaction): ?>
                                            <div class="transaction-card p-2">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>From:</strong> <?php echo $transaction['fromAddress']; ?></p>
                                                        <p class="mb-1"><strong>To:</strong> <?php echo $transaction['toAddress']; ?></p>
                                                    </div>
                                                    <div class="col-md-6 text-end">
                                                        <p class="mb-1"><strong>Amount:</strong> <?php echo $transaction['amount']; ?> coins</p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 