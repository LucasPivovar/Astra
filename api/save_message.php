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

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!isset($data['user_id']) || !isset($data['id_conversa']) || !isset($data['message_type']) || !isset($data['message_content'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

$userId = $data['user_id'];
$conversationId = $data['id_conversa'];
$messageType = $data['message_type'];
$messageContent = $data['message_content'];
$title = isset($data['title']) ? $data['title'] : null;

if ($userId != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
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
    
    $contentHash = md5($messageContent);
    
    if ($messageType === 'user') {
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
            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Mensagem já existente.',
                'conversation_id' => $conversationId,
                'duplicate' => true
            ]);
            exit;
        }
        
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
            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Resposta já existente.',
                'conversation_id' => $conversationId,
                'duplicate' => true
            ]);
            exit;
        }
        
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
    
    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Mensagem salva com sucesso.',
        'conversation_id' => $conversationId
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar mensagem: ' . $e->getMessage()]);
}
?>