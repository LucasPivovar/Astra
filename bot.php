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
    <title>Assistente Virtual</title>
    <link rel="stylesheet" href="./styles/bot.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" type="imagex/png" href="./assets/logo.svg">

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
    <div class="header-container">
        <button id="toggle-sidebar" aria-label="Toggle sidebar">
            <img src="./assets/sidebar.svg" alt="Toggle sidebar">
        </button>
        <div class="header-title">
            <h1>AstraAI</h1>
        </div>
        <?php include('./components/header.php'); ?>
    </div>

    <div class="content">
        
        <aside id="sidebar" class="sidebar">
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
    <script src="./scripts/chatbot.js" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            const isMobileView = () => window.innerWidth < 1024;
            
            if (!isMobileView()) {
                sidebar.classList.add('desktop-view');
            }
            
            function toggleSidebar() {
                if (isMobileView()) {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('active');
                    
                    const isOpen = sidebar.classList.contains('open');
                    toggleButton.setAttribute('aria-expanded', isOpen);
                    
                    if (isMobileView()) {
                        document.body.style.overflow = isOpen ? 'hidden' : '';
                    }
                }
            }
            
            toggleButton.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
            
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && sidebar.classList.contains('open') && isMobileView()) {
                    toggleSidebar();
                }
            });
            
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleButton.setAttribute('aria-controls', 'sidebar');
            document.addEventListener('click', function(e) {
                if (e.target.closest('.conversation-item') || e.target.closest('#clear-history-btn')) {
                    if (isMobileView() && sidebar.classList.contains('open')) {
                        setTimeout(toggleSidebar, 100);
                    }
                }
            }, true);
            
            window.addEventListener('resize', function() {
                if (isMobileView()) {
                    sidebar.classList.remove('desktop-view');
                } else {
                    sidebar.classList.add('desktop-view');
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }
            });
            
            let touchStartX = 0;
            let touchEndX = 0;
            
            document.addEventListener('touchstart', function(event) {
                touchStartX = event.changedTouches[0].screenX;
            }, false);
            
            document.addEventListener('touchend', function(event) {
                touchEndX = event.changedTouches[0].screenX;
                handleSwipe();
            }, false);
            
            function handleSwipe() {
                if (isMobileView()) {
                    const swipeDistance = touchEndX - touchStartX;
                    const isSignificantSwipe = Math.abs(swipeDistance) > 50;
                    
                    if (isSignificantSwipe) {
                        if (swipeDistance > 0 && !sidebar.classList.contains('open') && touchStartX < 50) {
                            toggleSidebar();
                        }
                        else if (swipeDistance < 0 && sidebar.classList.contains('open')) {
                            toggleSidebar();
                        }
                    }
                }
            }
        });
        // Pintar o elemento do nav em que o usuário está presente 
        const colorMenu = document.querySelectorAll('.btn-menu li a')
        colorMenu[2].classList.add('purple')
    </script>
</body>
</html>