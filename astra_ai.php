<?php
/**
 * AstraAI API Proxy Service
 * 
 * This script interfaces with Google's Gemini AI API, handling conversation
 * context and maintaining session history.
 */

ini_set('display_errors', 0);

// Process request
try {
    $apiService = new AIApiService();
    $response = $apiService->processRequest();
    echo json_encode(['response' => $response]);
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Core service class that handles AI API interactions
 */
class AIApiService 
{
    private const MAX_HISTORY_ENTRIES = 10;
    private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    
    private $apiKey;
    private $conversationHistory = [];
    
    /**
     * Constructor initializes the service
     */
    public function __construct() 
    {
        $this->setupCORS();
        $this->loadApiKey();
        $this->startSession();
    }
    
    /**
     * Main method to process incoming requests
     * 
     * @return string Formatted AI response
     * @throws Exception If processing fails
     */
    public function processRequest(): string 
    {
        $userInput = $this->validateAndGetInput();
        $userMessage = trim($userInput['message']);
        
        $this->addToHistory('user', $userMessage);
        $aiResponse = $this->fetchAIResponse($userMessage);
        $this->addToHistory('assistant', $aiResponse);
        
        return $aiResponse;
    }
    
    /**
     * Configure CORS headers
     */
    private function setupCORS(): void 
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header("Access-Control-Allow-Methods: POST, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type");
            exit(0);
        }
    }
    
    /**
     * Load API key from environment file
     * 
     * @throws Exception If API key cannot be found
     */
    private function loadApiKey(): void 
    {
        $envPath = __DIR__ . '/.env';
        
        if (!file_exists($envPath)) {
            throw new Exception('Environment file not found');
        }
        
        $envContent = file_get_contents($envPath);
        if (!preg_match('/^API_KEY\s*=\s*(.+)$/m', $envContent, $matches)) {
            throw new Exception('API key not found in .env file');
        }
        
        $this->apiKey = trim($matches[1]);
    }
    
    /**
     * Start or resume session
     */
    private function startSession(): void 
    {
        session_start();
        
        if (!isset($_SESSION['conversation_history'])) {
            $_SESSION['conversation_history'] = [];
        }
        
        $this->conversationHistory = $_SESSION['conversation_history'];
    }
    
    /**
     * Validate input and return parsed data
     * 
     * @return array Validated input data
     * @throws Exception If input is invalid
     */
    private function validateAndGetInput(): array 
    {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (!$input || !isset($input['message'])) {
            throw new Exception('Invalid input');
        }
        
        return $input;
    }
    
    /**
     * Add message to conversation history
     * 
     * @param string $role Role of the message sender (user/assistant)
     * @param string $content Message content
     */
    private function addToHistory(string $role, string $content): void 
    {
        $this->conversationHistory[] = [
            "role" => $role, 
            "content" => $content
        ];
        
        // Keep only recent history entries
        if (count($this->conversationHistory) > self::MAX_HISTORY_ENTRIES) {
            $this->conversationHistory = array_slice(
                $this->conversationHistory, 
                -self::MAX_HISTORY_ENTRIES
            );
        }
        
        $_SESSION['conversation_history'] = $this->conversationHistory;
    }
    
    /**
     * Fetch response from AI service
     * 
     * @param string $userMessage User's message
     * @return string AI response
     * @throws Exception If API call fails
     */
    private function fetchAIResponse(string $userMessage): string 
    {
        $prompt = $this->buildPrompt($userMessage);
        $requestData = $this->buildRequestData($prompt);
        
        $response = $this->makeApiRequest($requestData);
        return $this->extractResponseText($response);
    }
    
    /**
     * Build prompt with conversation history
     * 
     * @param string $userMessage User's message
     * @return string Formatted prompt
     */
    private function buildPrompt(string $userMessage): string 
    {
        $aiRole = "Você é um assistente de IA chamado AstraAI que ajuda os usuários na sua superaçao e recuperaçao contra os vícios.
 Forneça respostas claras, com carisma, concisas e úteis. Sempre tentando ajudar na estimulaçao da dopamina sem os vicios.
 Caso a pessoa tenha vícios, você deverá fornecer respostas que ajudem a superação do vício.
 Sem citar que vai elevar a autoestima da pessoa.";
        
        $prompt = $aiRole . "\n\nConversation history:\n";
        
        foreach ($this->conversationHistory as $message) {
            $role = $message["role"];
            $content = $message["content"];
            $prompt .= "$role: $content\n";
        }
        
        return $prompt . "\nResponda à mensagem mais recente considerando o contexto da conversa.";
    }
    
    /**
     * Build request data structure
     * 
     * @param string $prompt Complete prompt text
     * @return array Request data
     */
    private function buildRequestData(string $prompt): array 
    {
        return [
            "contents" => [
                [
                    "parts" => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Make API request to AI service
     * 
     * @param array $requestData Data to send to API
     * @return array API response
     * @throws Exception If API request fails
     */
    private function makeApiRequest(array $requestData): array 
    {
        $url = self::API_BASE_URL . "?key=" . $this->apiKey;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        $responseText = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Google Gemini API error: ' . 
                ($curlError ?: "HTTP $httpCode"));
        }
        
        $responseData = json_decode($responseText, true);
        
        if ($responseData === null) {
            throw new Exception('Invalid JSON response from API');
        }
        
        return $responseData;
    }
    
    /**
     * Extract text response from API response structure
     * 
     * @param array $response API response data
     * @return string Extracted response text
     * @throws Exception If response format is unexpected
     */
    private function extractResponseText(array $response): string 
    {
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Unexpected API response format');
        }
        
        return trim($response['candidates'][0]['content']['parts'][0]['text']);
    }
}