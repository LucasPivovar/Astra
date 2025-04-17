<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

$isLoggedIn = isset($_SESSION['user_id']);

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    $responseData = [
        'success' => true,
        'user_id' => $user_id
    ];
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT id_conversa, MAX(timestamp) AS last_message_time
                FROM chat_history
                WHERE user_id = :user_id
                GROUP BY id_conversa
                ORDER BY last_message_time DESC
            ");
            $stmt->execute([':user_id' => $user_id]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $responseData['conversations'] = $conversations;
        } catch (Exception $e) {
            error_log('Error fetching conversations: ' . $e->getMessage());
            $responseData['conversations'] = [];
        }
    } else {
        $responseData['conversations'] = [];
        $responseData['db_message'] = 'Database connection not available';
    }
    
    echo json_encode($responseData);
    exit;
}
if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = 'bot.php';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AstraAI ChatBot</title>
    <link rel="stylesheet" href="./styles/bot.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
    function checkLoginStatus() {
        console.log('Session check: User ' + (<?= json_encode($isLoggedIn) ?> ? 'is logged in' : 'is NOT logged in'));
        <?php if ($isLoggedIn): ?>
        console.log('User ID in session: <?= $_SESSION['user_id'] ?>');
        console.log('Username in session: <?= $_SESSION['username'] ?>');
        <?php endif; ?>
    }
    </script>
</head>
<body onload="checkLoginStatus()">
    <?php include('./components/header.php'); ?>

    <div class="content">
        <aside>
            <div id="clear-history">
                <button type="button" id="clear-history-btn" class="text-button">
                    <span>+</span> Nova Conversa
                </button>
                <ul id="conversation-list">
                </ul>
            </div>
        </aside>
        <main id="chat-container">
            <section id="chat-box" aria-live="polite">
                <div id="empty-message" class="welcome-message">
                    Aqui para apoiar você na jornada contra o vício. Conte-me como está se sentindo hoje.
                </div>
            </section>

            <footer class="input-container">
                <input 
                    type="text" 
                    id="user-input" 
                    placeholder="Comece digitando 'Hoje estou...'" 
                    aria-label="Digite sua mensagem"
                >
                <button 
                    type="button" 
                    id="send-button" 
                    aria-label="Enviar mensagem"
                >
                    <img src="./assets/UpArrow.svg" alt="" class="btn_enviar">
                </button>
            </footer>
        </main>
    </div>

    <script src="./scripts/bot.js"></script>
</body>
</html>