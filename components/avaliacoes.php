<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../db.php';

// Define sanitizeInput function at the beginning of the file to fix the error
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avaliacao'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?login_required=true");
        exit;
    }

    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $rating = (int) $_POST['rating'];
    $comment = sanitizeInput($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $error = "Por favor, selecione entre 1 e 5 estrelas.";
    } elseif (empty($comment)) {
        $error = "Por favor, adicione um comentário à sua avaliação.";
    } else {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM avaliacoes WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            
            if ($checkStmt->rowCount() > 0) {
                $stmt = $pdo->prepare("UPDATE avaliacoes SET rating = ?, comment = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$rating, $comment, $userId]);
                $successMessage = "Sua avaliação foi atualizada com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO avaliacoes (user_id, username, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$userId, $username, $rating, $comment]);
                $successMessage = "Sua avaliação foi enviada com sucesso!";
            }
        } catch (PDOException $e) {
            $error = "Erro ao salvar avaliação: " . $e->getMessage();
        }
    }
}

function getAvaliacoes($pdo, $limit = 3) {
    try {
        $limit = intval($limit);
        $stmt = $pdo->query("
            SELECT a.username, a.rating, a.comment, a.created_at, u.profile_image 
            FROM avaliacoes a
            LEFT JOIN user_profiles u ON a.user_id = u.user_id
            ORDER BY a.created_at DESC 
            LIMIT $limit
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar avaliações: " . $e->getMessage());
        return [];
    }
}

function getAllAvaliacoes($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT a.username, a.rating, a.comment, a.created_at, u.profile_image
            FROM avaliacoes a
            LEFT JOIN user_profiles u ON a.user_id = u.user_id
            ORDER BY a.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar todas as avaliações: " . $e->getMessage());
        return [];
    }
}

function getUserAvaliacao($pdo, $userId) {
    if (!$userId) return null;
    
    try {
        $stmt = $pdo->prepare("
            SELECT a.rating, a.comment, u.profile_image
            FROM avaliacoes a
            LEFT JOIN user_profiles u ON a.user_id = u.user_id
            WHERE a.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar avaliação do usuário: " . $e->getMessage());
        return null;
    }
}

$avaliacoes = getAvaliacoes($pdo, 3);

$userAvaliacao = isset($_SESSION['user_id']) ? getUserAvaliacao($pdo, $_SESSION['user_id']) : null;

// Remove the duplicate function definition that was causing the error
// The function is now defined at the top of the file

function getProfileImage($profileImage) {
    if (!empty($profileImage) && file_exists($profileImage)) {
        return $profileImage;
    }
    return "./assets/default-profile.png";
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
                        <img src="<?= getProfileImage($avaliacao['profile_image']) ?>" alt="Avatar do usuário" class="avatar-pequeno">
                        <div class="estrelas-pequenas">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="estrela-pequena <?= ($i <= $avaliacao['rating']) ? 'active' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="comentario-avaliacao scrollbar"><?= sanitizeInput($avaliacao['comment']) ?></p>
                    <p><span class="nome-avaliacao"><?= sanitizeInput($avaliacao['username']) ?></span></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="avaliacoes-botoes">
        <button id="btnAvaliar" class="button button-avaliar">Avalie-nos</button>
        <button id="btnVerTodas" class="button-outline">Ver Todas as avaliações</button>
    </div>
    
    <!-- Modal para adicionar avaliação -->
    <div id="modalAvaliacao" class="modal modalContainer">
        <div class="modal-content backgroundModal">
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
                    <textarea name="comment" id="comment" class="scrollbar" rows="4" placeholder="Digite aqui sua avaliação" required><?= $userAvaliacao ? $userAvaliacao['comment'] : '' ?></textarea>
                </div>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <p class="login-alert">* Faça login para enviar sua avaliação</p>
                <?php else: ?>
                    <button type="submit" name="submit_avaliacao" class="button">
                        <?= $userAvaliacao ? 'Atualizar avaliação' : 'Enviar avaliação' ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Modal para visualizar todas as avaliações -->
    <div id="modalTodasAvaliacoes" class="modal modalContainer">
        <div class="modal-content modal-todas backgroundModal scrollbar">
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
                                <img src="<?= getProfileImage($avaliacao['profile_image']) ?>" alt="Avatar do usuário" class="avatar-pequeno">
                                <span class="nome-avaliacao"><?= sanitizeInput($avaliacao['username']) ?></span>
                                <div class="estrelas-pequenas">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="estrela-pequena <?= ($i <= $avaliacao['rating']) ? 'active' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="comentario-avaliacao"><?= sanitizeInput($avaliacao['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>