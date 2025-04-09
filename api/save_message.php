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
$title = isset($data['title']) ? $data['title'] : null;

// Verify that user ID matches the session user
if ($userId != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if this is a new conversation
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) AS count 
        FROM chat_history 
        WHERE id_conversa = :id_conversa AND user_id = :user_id
    ");
    $checkStmt->execute([
        ':id_conversa' => $conversationId,
        ':user_id' => $userId
    ]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $isNewConversation = ($result['count'] == 0);
    
    // Verificar duplicação baseada no conteúdo exato da mensagem
    $contentHash = md5($messageContent);
    
    if ($messageType === 'user') {
        // Verificar se essa mensagem de usuário já existe
        $checkDuplicateStmt = $pdo->prepare("
            SELECT COUNT(*) AS count FROM chat_history 
            WHERE id_conversa = :id_conversa 
            AND user_id = :user_id
            AND MD5(user_message) = :content_hash
            AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $checkDuplicateStmt->execute([
            ':id_conversa' => $conversationId,
            ':user_id' => $userId,
            ':content_hash' => $contentHash
        ]);
        $duplicateResult = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($duplicateResult['count'] > 0) {
            // Mensagem duplicada, não salvar novamente
            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Mensagem já existente.',
                'conversation_id' => $conversationId,
                'duplicate' => true
            ]);
            exit;
        }
        
        // Direct insert for user messages
        $query = "INSERT INTO chat_history (user_id, id_conversa, user_message, timestamp";
        $query .= ($isNewConversation && $title !== null) ? ", title) " : ") ";
        $query .= "VALUES (:user_id, :id_conversa, :message_content, NOW()";
        $query .= ($isNewConversation && $title !== null) ? ", :title)" : ")";
        
        $params = [
            ':user_id' => $userId,
            ':id_conversa' => $conversationId,
            ':message_content' => $messageContent
        ];
        
        if ($isNewConversation && $title !== null) {
            $params[':title'] = $title;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    } 
    else if ($messageType === 'bot') {
        // Verificar se essa resposta do bot já existe
        $checkDuplicateStmt = $pdo->prepare("
            SELECT COUNT(*) AS count FROM chat_history 
            WHERE id_conversa = :id_conversa 
            AND user_id = :user_id
            AND MD5(bot_response) = :content_hash
            AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $checkDuplicateStmt->execute([
            ':id_conversa' => $conversationId,
            ':user_id' => $userId,
            ':content_hash' => $contentHash
        ]);
        $duplicateResult = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($duplicateResult['count'] > 0) {
            // Resposta do bot duplicada, não salvar novamente
            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Resposta já existente.',
                'conversation_id' => $conversationId,
                'duplicate' => true
            ]);
            exit;
        }
        
        // For bot messages, find the latest user message without a response
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
        
        $row = $findStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Update existing record with bot response
            $stmt = $pdo->prepare("
                UPDATE chat_history 
                SET bot_response = :message_content, timestamp = NOW() 
                WHERE id = :id
            ");
            $stmt->execute([
                ':message_content' => $messageContent,
                ':id' => $row['id']
            ]);
        } else {
            // Create new record with just bot response
            $stmt = $pdo->prepare("
                INSERT INTO chat_history (user_id, id_conversa, bot_response, timestamp) 
                VALUES (:user_id, :id_conversa, :message_content, NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':id_conversa' => $conversationId,
                ':message_content' => $messageContent
            ]);
        }
    } else {
        throw new Exception('Tipo de mensagem inválido.');
    }
    
    // Update title for existing conversation if provided
    if (!$isNewConversation && $title !== null) {
        $updateTitleStmt = $pdo->prepare("
            UPDATE chat_history 
            SET title = :title 
            WHERE id_conversa = :id_conversa 
            AND user_id = :user_id
            ORDER BY timestamp ASC
            LIMIT 1
        ");
        
        $updateTitleStmt->execute([
            ':title' => $title,
            ':id_conversa' => $conversationId,
            ':user_id' => $userId
        ]);
    }
    
    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Mensagem salva com sucesso.',
        'conversation_id' => $conversationId
    ]);
} catch (Exception $e) {
    // Roll back transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar mensagem: ' . $e->getMessage()]);
}
?>