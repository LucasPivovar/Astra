<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/db.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => true, 'message' => 'Usuário não autenticado.']);
    exit;
}

// Get the JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate required data
if (!isset($data['message']) || !isset($data['conversation_id'])) {
    echo json_encode(['error' => true, 'message' => 'Dados incompletos.']);
    exit;
}

$userMessage = $data['message'];
$conversationId = $data['conversation_id'];
$userId = $_SESSION['user_id'];

try {
    // Get API key from .env file
    $apiKey = loadApiKey();
    
    // Get context from this specific conversation only
    $stmt = $pdo->prepare("
        SELECT user_message, bot_response 
        FROM chat_history 
        WHERE user_id = :user_id 
        AND id_conversa = :conversation_id 
        AND (user_message IS NOT NULL OR bot_response IS NOT NULL) 
        ORDER BY timestamp ASC 
        LIMIT 10
    ");
    
    $stmt->execute([
        ':user_id' => $userId,
        ':conversation_id' => $conversationId
    ]);
    
    $conversationHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build the context string
    $context = "Você é um assistente terapêutico chamado AstraAI focado em tratar dependência química. Mantenha suas respostas empáticas, úteis e baseadas em evidências científicas atuais sobre tratamento de dependência química. Não julgue o usuário, independente do que ele compartilhar. Ofereça suporte e encorajamento.\n\nHistórico da conversa:\n";
    
    foreach ($conversationHistory as $message) {
        if (!empty($message['user_message'])) {
            $context .= "Usuário: " . $message['user_message'] . "\n";
        }
        if (!empty($message['bot_response'])) {
            $context .= "Assistente: " . $message['bot_response'] . "\n";
        }
    }
    
    $context .= "\nUsuário: " . $userMessage . "\nAssistente:";
    
    // Make real API call to Gemini
    $botResponse = callGeminiAPI($context, $apiKey);
    
    // Save the message to database
    saveToDatabase($pdo, $userId, $conversationId, $userMessage, $botResponse);
    
    echo json_encode([
        'error' => false,
        'response' => $botResponse,
        'conversation_id' => $conversationId
    ]);
    
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode(['error' => true, 'message' => 'Erro ao processar a mensagem: ' . $e->getMessage()]);
}

/**
 * Load API key from .env file
 */
function loadApiKey() {
    $envPath = __DIR__ . '/.env';
    
    if (!file_exists($envPath)) {
        throw new Exception('Environment file not found');
    }
    
    $envContent = file_get_contents($envPath);
    if (!preg_match('/^API_KEY\s*=\s*(.+)$/m', $envContent, $matches)) {
        throw new Exception('API key not found in .env file');
    }
    
    return trim($matches[1]);
}

/**
 * Call Gemini API with the provided context
 */
function callGeminiAPI($prompt, $apiKey) {
    $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    
    $requestData = [
        "contents" => [
            [
                "parts" => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "?key=" . $apiKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    
    $responseText = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Erro na API do Gemini: HTTP $httpCode");
        throw new Exception('Google Gemini API error: ' . ($curlError ?: "HTTP $httpCode"));
    }
    
    $responseData = json_decode($responseText, true);
    if ($responseData === null) {
        error_log("Resposta inválida da API: $responseText");
        throw new Exception('Invalid JSON response from API');
    }
    
    if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Unexpected API response format');
    }
    
    return trim($responseData['candidates'][0]['content']['parts'][0]['text']);
}

/**
 * Save messages to database
 */
function saveToDatabase($pdo, $userId, $conversationId, $userMessage, $botResponse) {
    try {
        // Insert user message
        $stmt = $pdo->prepare("
            INSERT INTO chat_history (
                user_id, 
                id_conversa, 
                user_message, 
                bot_response, 
                timestamp
            ) VALUES (
                :user_id, 
                :id_conversa, 
                :user_message, 
                :bot_response, 
                NOW()
            )
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':id_conversa' => $conversationId,
            ':user_message' => $userMessage,
            ':bot_response' => $botResponse
        ]);
        
    } catch (Exception $e) {
        error_log('Database error: ' . $e->getMessage());
        // Continue execution - don't break the conversation flow
    }
}
?>