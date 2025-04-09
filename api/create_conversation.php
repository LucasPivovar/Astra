<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../db.php';

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
if (!isset($data['user_id']) || !isset($data['id_conversa'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

// Extract data
$userId = $data['user_id'];
$conversationId = $data['id_conversa'];

// Verify that user ID matches the session user
if ($userId != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
    exit;
}

try {
    // Check if this conversation ID already exists
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) AS count 
        FROM chat_history 
        WHERE id_conversa = :id_conversa
    ");
    $checkStmt->execute([':id_conversa' => $conversationId]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        // Conversation already exists, return success
        echo json_encode(['success' => true, 'message' => 'Conversa já existe.', 'exists' => true]);
        exit;
    }
    
    // Create a new placeholder entry to establish the conversation
    $stmt = $pdo->prepare("
        INSERT INTO chat_history (user_id, id_conversa, timestamp)
        VALUES (:user_id, :id_conversa, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':id_conversa' => $conversationId
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Nova conversa criada com sucesso.']);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao criar nova conversa: ' . $e->getMessage()]);
}