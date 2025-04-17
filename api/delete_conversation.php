<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_conversa']) || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

$conversationId = $data['id_conversa'];
$userId = $data['user_id'];

if ($_SESSION['user_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'Permissão negada']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmtMessages = $pdo->prepare("
        DELETE FROM chat_history 
        WHERE id_conversa = :id_conversa AND user_id = :user_id
    ");
    $stmtMessages->execute([
        ':id_conversa' => $conversationId,
        ':user_id' => $userId
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Conversa excluída com sucesso']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Erro ao excluir conversa: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir conversa: ' . $e->getMessage()]);
}
?>