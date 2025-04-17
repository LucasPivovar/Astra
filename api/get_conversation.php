<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Acesso direto não permitido.']);
    exit;
}

if (!isset($_GET['id_conversa'])) {
    echo json_encode(['success' => false, 'message' => 'ID da conversa não fornecido.']);
    exit;
}

$conversationId = $_GET['id_conversa'];
$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, user_message, bot_response, timestamp 
        FROM chat_history 
        WHERE user_id = :user_id 
        AND id_conversa = :id_conversa 
        ORDER BY timestamp ASC
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':id_conversa' => $conversationId
    ]);
    
    $dbMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedMessages = [];
    $seenUserMessages = [];
    $seenBotResponses = [];
    
    foreach ($dbMessages as $message) {
        if (!empty($message['user_message'])) {
            $userMessageHash = md5($message['user_message']);
            
            if (!in_array($userMessageHash, $seenUserMessages)) {
                $seenUserMessages[] = $userMessageHash;
                
                $formattedMessages[] = [
                    'type' => 'user',
                    'content' => $message['user_message'],
                    'timestamp' => $message['timestamp']
                ];
            }
        }
        
        if (!empty($message['bot_response'])) {
            $botResponseHash = md5($message['bot_response']);
            
            if (!in_array($botResponseHash, $seenBotResponses)) {
                $seenBotResponses[] = $botResponseHash;
                
                $formattedMessages[] = [
                    'type' => 'bot',
                    'content' => $message['bot_response'],
                    'timestamp' => $message['timestamp'] 
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'messages' => $formattedMessages,
        'conversation_id' => $conversationId
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar conversa: ' . $e->getMessage()]);
}
?>