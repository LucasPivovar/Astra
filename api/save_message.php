<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../db.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Acesso direto não permitido.']);
    exit;
}

// Get the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate required data
if (!isset($data['user_id']) || !isset($data['id_conversa']) || !isset($data['message_type']) || !isset($data['message_content'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

// Extract data
$userId = $data['user_id'];
$conversationId = $data['id_conversa'];
$messageType = $data['message_type'];
$messageContent = $data['message_content'];

// Verify that user ID matches the session user
if ($userId != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
    exit;
}

try {
    // Check if this conversation exists
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) AS count 
        FROM chat_history 
        WHERE id_conversa = :id_conversa
    ");
    $checkStmt->execute([':id_conversa' => $conversationId]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // If this is a new conversation, create a placeholder entry
    if ($result['count'] == 0) {
        $createStmt = $pdo->prepare("
            INSERT INTO chat_history (user_id, id_conversa, timestamp) 
            VALUES (:user_id, :id_conversa, NOW())
        ");
        
        $createStmt->execute([
            ':user_id' => $userId,
            ':id_conversa' => $conversationId
        ]);
    }
    
    // Now handle the actual message
    if ($messageType === 'user') {
        // Insert a new user message
        $query = "INSERT INTO chat_history (user_id, id_conversa, user_message, timestamp) 
                  VALUES (:user_id, :id_conversa, :message_content, NOW())";
        $params = [
            ':user_id' => $userId,
            ':id_conversa' => $conversationId,
            ':message_content' => $messageContent
        ];
    } else if ($messageType === 'bot') {
        // Find the latest message from this user in this conversation that doesn't have a bot response yet
        $findStmt = $pdo->prepare("
            SELECT id FROM chat_history 
            WHERE user_id = :user_id 
            AND id_conversa = :id_conversa 
            AND user_message IS NOT NULL 
            AND bot_response IS NULL 
            ORDER BY timestamp DESC 
            LIMIT 1
        ");
        $findStmt->execute([
            ':user_id' => $userId,
            ':id_conversa' => $conversationId
        ]);
        
        $result = $findStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Update existing record with bot response
            $query = "UPDATE chat_history 
                      SET bot_response = :message_content, timestamp = NOW() 
                      WHERE id = :id";
            $params = [
                ':message_content' => $messageContent,
                ':id' => $result['id']
            ];
        } else {
            // Create a new record with just the bot response
            $query = "INSERT INTO chat_history (user_id, id_conversa, bot_response, timestamp) 
                      VALUES (:user_id, :id_conversa, :message_content, NOW())";
            $params = [
                ':user_id' => $userId,
                ':id_conversa' => $conversationId,
                ':message_content' => $messageContent
            ];
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo de mensagem inválido.']);
        exit;
    }

    // Execute the query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    echo json_encode([
        'success' => true, 
        'message' => 'Mensagem salva com sucesso.',
        'conversation_id' => $conversationId
    ]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar mensagem: ' . $e->getMessage()]);
}