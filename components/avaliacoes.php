<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// Função para salvar avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avaliacao'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?login_required=true");
        exit;
    }

    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $rating = (int) $_POST['rating'];
    $comment = sanitizeInput($_POST['comment']);
    
    // Obter ou gerar o avatar_id do usuário
    $avatar_id = isset($_SESSION['avatar_id']) ? $_SESSION['avatar_id'] : rand(1, 3);

    // Validação básica
    if ($rating < 1 || $rating > 5) {
        $error = "Por favor, selecione entre 1 e 5 estrelas.";
    } elseif (empty($comment)) {
        $error = "Por favor, adicione um comentário à sua avaliação.";
    } else {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM avaliacoes WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            
            if ($checkStmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE avaliacoes SET rating = ?, comment = ?, avatar_id = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$rating, $comment, $avatar_id, $userId]);
                $successMessage = "Sua avaliação foi atualizada com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO avaliacoes (user_id, username, rating, comment, avatar_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$userId, $username, $rating, $comment, $avatar_id]);
                $successMessage = "Sua avaliação foi enviada com sucesso!";
                
                if (!isset($_SESSION['avatar_id'])) {
                    $_SESSION['avatar_id'] = $avatar_id;
                }
            }
        } catch (PDOException $e) {
            $error = "Erro ao salvar avaliação: " . $e->getMessage();
        }
    }
}

// Função para obter avaliações (limitado a 3 para exibição na página inicial)
function getAvaliacoes($pdo, $limit = 3) {
    try {
        $limit = intval($limit);
        $stmt = $pdo->query("SELECT username, rating, comment, created_at, avatar_id FROM avaliacoes ORDER BY created_at DESC LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar avaliações: " . $e->getMessage());
        return [];
    }
}

// Função para obter todas as avaliações (para o modal "Ver todas")
function getAllAvaliacoes($pdo) {
    try {
        $stmt = $pdo->query("SELECT username, rating, comment, created_at, avatar_id FROM avaliacoes ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar todas as avaliações: " . $e->getMessage());
        return [];
    }
}

// Obter avaliação do usuário atual, se existir
function getUserAvaliacao($pdo, $userId) {
    if (!$userId) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT rating, comment, avatar_id FROM avaliacoes WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar avaliação do usuário: " . $e->getMessage());
        return null;
    }
}

$avaliacoes = getAvaliacoes($pdo, 3);

$userAvaliacao = isset($_SESSION['user_id']) ? getUserAvaliacao($pdo, $_SESSION['user_id']) : null;

if ($userAvaliacao && isset($userAvaliacao['avatar_id'])) {
    $_SESSION['avatar_id'] = $userAvaliacao['avatar_id'];
} elseif (!isset($_SESSION['avatar_id'])) {
    $_SESSION['avatar_id'] = rand(1, 3);
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>

<section class="avaliacoes">
    <h1 class="purple title">Avaliações</h1>
    
    <div class="avaliacoes-container">
        <?php if (empty($avaliacoes)): ?>
            <p class="sem-avaliacoes">Ainda não há avaliações. Seja o primeiro a avaliar!</p>
        <?php else: ?>
            <?php foreach ($avaliacoes as $avaliacao): ?>
                <div class="avaliacao-item">
                    <div class="avaliacao-header">
                        <img src="./assets/avatar-<?= isset($avaliacao['avatar_id']) ? $avaliacao['avatar_id'] : rand(1, 3) ?>.png" alt="Avatar do usuário" class="avatar-pequeno">
                        <span class="nome-avaliacao"><?= sanitizeInput($avaliacao['username']) ?></span>
                        <div class="estrelas-pequenas">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="estrela-pequena <?= ($i <= $avaliacao['rating']) ? 'active' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="data-avaliacao"><?= date('d/m/Y', strtotime($avaliacao['created_at'])) ?></span>
                    </div>
                    <p class="comentario-avaliacao"><?= sanitizeInput($avaliacao['comment']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="avaliacoes-botoes">
        <button id="btnAvaliar" class="button">Avalie-nos</button>
        <button id="btnVerTodas" class="button-outline">Ver Todas as avaliações</button>
    </div>
    
    <!-- Modal para adicionar avaliação -->
    <div id="modalAvaliacao" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Sua avaliação</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert success"><?= $successMessage ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="rating-container">
                    <div class="estrelas-selecao">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="estrela-selecao <?= ($userAvaliacao && $i <= $userAvaliacao['rating']) ? 'active' : '' ?>" 
                                  data-value="<?= $i ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating" value="<?= $userAvaliacao ? $userAvaliacao['rating'] : 0 ?>">
                </div>
                
                <div class="form-group">
                    <label for="comment">Seu comentário:</label>
                    <textarea name="comment" id="comment" rows="4" required><?= $userAvaliacao ? $userAvaliacao['comment'] : '' ?></textarea>
                </div>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <p class="login-alert">Faça login para enviar sua avaliação</p>
                <?php else: ?>
                    <button type="submit" name="submit_avaliacao" class="button">
                        <?= $userAvaliacao ? 'Atualizar avaliação' : 'Enviar avaliação' ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Modal para visualizar todas as avaliações -->
    <div id="modalTodasAvaliacoes" class="modal">
        <div class="modal-content modal-todas">
            <span class="close close-todas">&times;</span>
            <h2>Todas as avaliações</h2>
            
            <div class="todas-avaliacoes-container">
                <?php 
                $todasAvaliacoes = getAllAvaliacoes($pdo);
                if (empty($todasAvaliacoes)): 
                ?>
                    <p class="sem-avaliacoes">Ainda não há avaliações. Seja o primeiro a avaliar!</p>
                <?php else: ?>
                    <?php foreach ($todasAvaliacoes as $avaliacao): ?>
                        <div class="avaliacao-item">
                            <div class="avaliacao-header">
                                <img src="./assets/avatar-<?= isset($avaliacao['avatar_id']) ? $avaliacao['avatar_id'] : rand(1, 3) ?>.png" alt="Avatar do usuário" class="avatar-pequeno">
                                <span class="nome-avaliacao"><?= sanitizeInput($avaliacao['username']) ?></span>
                                <div class="estrelas-pequenas">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="estrela-pequena <?= ($i <= $avaliacao['rating']) ? 'active' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="data-avaliacao"><?= date('d/m/Y', strtotime($avaliacao['created_at'])) ?></span>
                            </div>
                            <p class="comentario-avaliacao"><?= sanitizeInput($avaliacao['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>