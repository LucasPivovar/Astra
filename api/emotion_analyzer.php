<?php
/**
 * Emotion Analyzer - Uma solução simples para análise de emoções sem dependências externas
 */

// Lista de palavras-chave associadas a diferentes emoções
$emotionKeywords = [
    'felicidade' => ['feliz', 'alegre', 'contente', 'animado', 'satisfeito', 'empolgado', 'sorrir', 'sorriso', 'alegria', 'entusiasmado', 'ótimo', 'maravilhoso', 'divertido', 'gostei', 'adoro', 'amo'],
    
    'tristeza' => ['triste', 'chateado', 'deprimido', 'melancólico', 'infeliz', 'desanimado', 'chorar', 'lágrimas', 'sofrendo', 'sofrimento', 'pesar', 'dor', 'angústia', 'desapontado', 'desesperançado', 'sozinho', 'solitário'],
    
    'raiva' => ['irritado', 'bravo', 'furioso', 'frustrado', 'irado', 'revoltado', 'ódio', 'detesto', 'odeio', 'nervoso', 'indignado', 'chateado', 'problema', 'culpa', 'injusto'],
    
    'medo' => ['assustado', 'apreensivo', 'receoso', 'preocupado', 'nervoso', 'ansioso', 'ansiedade', 'pavor', 'terror', 'medo', 'pânico', 'inseguro', 'insegurança', 'tenso', 'tensão', 'temor'],
    
    'surpresa' => ['surpreso', 'espantado', 'chocado', 'atônito', 'impressionado', 'incrível', 'inacreditável', 'uau', 'caramba', 'nossa', 'inesperado', 'incrédulo'],
    
    'desejo' => ['quero', 'desejo', 'gostaria', 'preciso', 'necessito', 'anseio', 'ansioso por', 'vontade', 'queria', 'pretendo', 'objetivo', 'meta', 'sonho'],
    
    'esperança' => ['espero', 'esperança', 'otimista', 'confiante', 'acredito', 'fé', 'melhora', 'melhorar', 'conseguir', 'superar', 'vencer', 'possível', 'futuro', 'amanhã'],
    
    'cansaço' => ['cansado', 'exausto', 'esgotado', 'fatigado', 'sem energia', 'desgastado', 'fraco', 'sono', 'dormir', 'descansar', 'esforço', 'difícil', 'pesado'],
    
    'confusão' => ['confuso', 'perdido', 'desorientado', 'incerto', 'dúvida', 'não entendo', 'complicado', 'complexo', 'difícil de entender', 'não sei', 'indeciso'],
    
    'calma' => ['calmo', 'tranquilo', 'sereno', 'relaxado', 'em paz', 'sossegado', 'zen', 'aliviado', 'descansado', 'controlado', 'equilibrado'],
    
    'ansiedade' => ['ansioso', 'preocupado', 'nervoso', 'aflito', 'ansiedade', 'angustiado', 'inquieto', 'agitado', 'tenso', 'estressado', 'preocupação', 'nervosismo', 'pânico'],
    
    'culpa' => ['culpado', 'arrependido', 'remorso', 'vergonha', 'erro', 'falta', 'falha', 'problema', 'desculpa', 'perdão', 'perdoe']
];

/**
 * Analisa as emoções presentes no texto
 * 
 * @param string $text O texto a ser analisado
 * @return array Informações sobre as emoções detectadas
 */
function analyzeEmotion($text) {
    global $emotionKeywords;
    
    // Normaliza o texto
    $text = mb_strtolower($text);
    
    // Inicializa contadores
    $emotionScores = [];
    foreach ($emotionKeywords as $emotion => $keywords) {
        $emotionScores[$emotion] = 0;
    }
    
    // Conta ocorrências de palavras-chave
    foreach ($emotionKeywords as $emotion => $keywords) {
        foreach ($keywords as $keyword) {
            // Procura por palavras completas
            $count = preg_match_all('/\b' . preg_quote($keyword, '/') . '\b/u', $text);
            $emotionScores[$emotion] += $count;
        }
    }
    
    // Determina a emoção dominante
    $dominantEmotion = 'neutra'; // Padrão
    $maxScore = 0;
    
    foreach ($emotionScores as $emotion => $score) {
        if ($score > $maxScore) {
            $maxScore = $score;
            $dominantEmotion = $emotion;
        }
    }
    
    // Se nenhuma emoção for detectada, mantém como neutra
    if ($maxScore == 0) {
        $dominantEmotion = 'neutra';
    }
    
    // Prepara os dados de retorno
    return [
        'scores' => $emotionScores,
        'dominant_emotion' => $dominantEmotion,
        'intensity' => $maxScore
    ];
}

/**
 * Personaliza o prompt com base na emoção detectada
 */
function getEmotionBasedPrompt($emotion, $intensity) {
    $promptAdditions = [
        'felicidade' => "O usuário parece estar feliz. Mantenha esse clima positivo e reforce esse estado de espírito enquanto aborda os temas de recuperação com otimismo.",
        
        'tristeza' => "O usuário parece estar triste. Ofereça compreensão e suporte emocional. Use uma abordagem gentil e compassiva, mostrando que você entende como pode ser difícil, mas que há esperança.",
        
        'raiva' => "O usuário parece estar irritado ou frustrado. Mantenha-se calmo e não leve para o lado pessoal. Valide os sentimentos dele sem alimentar mais raiva. Ofereça perspectivas construtivas.",
        
        'medo' => "O usuário parece estar com medo ou ansioso. Ofereça garantias realistas e informações que possam ajudar a acalmar os temores. Evite frases como 'não se preocupe' que podem parecer dispensivas.",
        
        'surpresa' => "O usuário parece surpreso. Explique as informações com clareza e forneça contexto para ajudar na compreensão.",
        
        'desejo' => "O usuário está expressando desejos ou objetivos. Apoie essas aspirações positivas e ofereça orientações práticas sobre como alcançá-las na jornada de recuperação.",
        
        'esperança' => "O usuário está demonstrando esperança. Reforce esse sentimento positivo e forneça informações e estratégias que possam ajudar a transformar essa esperança em progresso real.",
        
        'cansaço' => "O usuário parece cansado ou sobrecarregado. Reconheça esse sentimento e sugira pequenos passos gerenciáveis. Lembre-o da importância do autocuidado e descanso na recuperação.",
        
        'confusão' => "O usuário parece confuso. Forneça informações claras e simples. Pergunte se há algo específico que precisa ser esclarecido e esteja aberto a reformular sua explicação.",
        
        'calma' => "O usuário está em um estado calmo. É um bom momento para introduzir novas informações ou técnicas que possam ser úteis em sua jornada de recuperação.",
        
        'ansiedade' => "O usuário está demonstrando ansiedade. Ofereça técnicas de respiração ou mindfulness que possam ajudar no momento. Valide seus sentimentos e lembre-o que a ansiedade é comum durante a recuperação.",
        
        'culpa' => "O usuário está expressando culpa ou arrependimento. Enfatize a importância do perdão próprio no processo de recuperação. Ajude-o a transformar esses sentimentos em motivação para mudança positiva.",
        
        'neutra' => "O usuário não está demonstrando uma emoção forte. Mantenha um tom de suporte e informativo."
    ];
    
    // Ajusta o prompt com base na intensidade da emoção
    $basePrompt = $promptAdditions[$emotion] ?? $promptAdditions['neutra'];
    
    if ($intensity > 3) {
        return "[INFORMAÇÃO INTERNA: $basePrompt A intensidade dessa emoção é alta ($intensity), então dê atenção especial a isso em sua resposta.]";
    } else if ($intensity > 0) {
        return "[INFORMAÇÃO INTERNA: $basePrompt]";
    } else {
        return "[INFORMAÇÃO INTERNA: Mantenha um tom equilibrado e atento às necessidades do usuário.]";
    }
}

/**
 * Versão aprimorada da função callGeminiAPI que usa análise de emoções
 */
function enhancedCallGeminiAPI($prompt, $apiKey, $userMessage) {
    // Analisa as emoções do usuário
    $emotionData = analyzeEmotion($userMessage);
    
    // Define a variável como global para poder acessá-la em outras funções
    $GLOBALS['emotionData'] = $emotionData;
    
    // Adiciona informações sobre a emoção detectada ao prompt
    $emotionPrompt = "\n" . getEmotionBasedPrompt($emotionData['dominant_emotion'], $emotionData['intensity']) . "\n";
    
    $enhancedPrompt = $prompt . $emotionPrompt;
    
    // Chama a API do Gemini com o prompt aprimorado
    return callGeminiAPI($enhancedPrompt, $apiKey);
}

/**
 * Salva o histórico de emoções no banco de dados
 */
function saveEmotionHistory($pdo, $userId, $conversationId, $emotionData) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO emotion_history (
                user_id,
                conversation_id,
                emotion,
                intensity,
                timestamp
            ) VALUES (
                :user_id,
                :conversation_id,
                :emotion,
                :intensity,
                NOW()
            )
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':conversation_id' => $conversationId,
            ':emotion' => $emotionData['dominant_emotion'],
            ':intensity' => $emotionData['intensity']
        ]);
        
    } catch (Exception $e) {
        error_log('Error saving emotion data: ' . $e->getMessage());
        // Continue execution
    }
}
?>