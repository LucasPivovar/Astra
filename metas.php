<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

include('./db.php');
$user_id = $_SESSION['user_id'];
$message = '';

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); 
}

// Processar progresso diário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['progress_meta_id'])) {
    $meta_id = $_POST['progress_meta_id'];
    $today = date('Y-m-d');
    
    try {
        // Verificar se a meta pertence ao usuário
        $check_stmt = $pdo->prepare("SELECT * FROM metas WHERE id = :meta_id AND user_id = :user_id");
        $check_stmt->bindParam(':meta_id', $meta_id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        $meta = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($meta) {
            // Verificar se já marcou progresso hoje
            $progress_check = $pdo->prepare("SELECT * FROM daily_progress WHERE meta_id = :meta_id AND progress_date = :today");
            $progress_check->bindParam(':meta_id', $meta_id);
            $progress_check->bindParam(':today', $today);
            $progress_check->execute();
            
            if ($progress_check->rowCount() == 0) {
                // Inserir progresso do dia
                $progress_stmt = $pdo->prepare("INSERT INTO daily_progress (meta_id, progress_date, created_at) VALUES (:meta_id, :progress_date, :created_at)");
                $progress_stmt->bindParam(':meta_id', $meta_id);
                $progress_stmt->bindParam(':progress_date', $today);
                $progress_stmt->bindParam(':created_at', date('Y-m-d H:i:s'));
                
                if ($progress_stmt->execute()) {
                    // Verificar se a meta deve ser marcada como concluída
                    $target_date = new DateTime($meta['target_date']);
                    $created_date = new DateTime($meta['created_at']);
                    $today_date = new DateTime($today);
                    
                    // Se chegou na data alvo, marcar como concluída
                    if ($today_date >= $target_date) {
                        $complete_stmt = $pdo->prepare("UPDATE metas SET completed = 1, updated_at = :updated_at WHERE id = :meta_id");
                        $complete_stmt->bindParam(':updated_at', date('Y-m-d H:i:s'));
                        $complete_stmt->bindParam(':meta_id', $meta_id);
                        $complete_stmt->execute();
                        
                        $_SESSION['message'] = '<p class="success">Parabéns! Meta concluída!</p>';
                    } else {
                        $_SESSION['message'] = '<p class="success">Progresso do dia marcado!</p>';
                    }
                } else {
                    $_SESSION['message'] = '<p class="error">Erro ao marcar progresso.</p>';
                }
            } else {
                $_SESSION['message'] = '<p class="error">Você já marcou o progresso de hoje para esta meta.</p>';
            }
        } else {
            $_SESSION['message'] = '<p class="error">Meta não encontrada.</p>';
        }
        
        header('Location: metas.php');
        exit;
    } catch (PDOException $e) {
        $message .= '<p class="error">Erro no banco de dados: ' . $e->getMessage() . '</p>';
    }
}

// Processar exclusão de meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meta_id'])) {
    $meta_id = $_POST['delete_meta_id'];
    
    try {
        // Verificar se a meta pertence ao usuário atual
        $check_stmt = $pdo->prepare("SELECT * FROM metas WHERE id = :meta_id AND user_id = :user_id");
        $check_stmt->bindParam(':meta_id', $meta_id);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Excluir progressos relacionados primeiro
            $delete_progress = $pdo->prepare("DELETE FROM daily_progress WHERE meta_id = :meta_id");
            $delete_progress->bindParam(':meta_id', $meta_id);
            $delete_progress->execute();
            
            // Excluir a meta
            $delete_stmt = $pdo->prepare("DELETE FROM metas WHERE id = :meta_id AND user_id = :user_id");
            $delete_stmt->bindParam(':meta_id', $meta_id);
            $delete_stmt->bindParam(':user_id', $user_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['message'] = '<p class="success">Meta excluída com sucesso!</p>';
            } else {
                $_SESSION['message'] = '<p class="error">Erro ao excluir a meta.</p>';
            }
        } else {
            $_SESSION['message'] = '<p class="error">Meta não encontrada ou você não tem permissão para excluí-la.</p>';
        }
        
        header('Location: metas.php');
        exit;
    } catch (PDOException $e) {
        $message .= '<p class="error">Erro no banco de dados: ' . $e->getMessage() . '</p>';
    }
}

// Processar atualização de meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meta_id'])) {
    $meta_id = $_POST['update_meta_id'];
    $title = trim($_POST['edit_title']);
    $description = trim($_POST['edit_description']);
    $target_date = $_POST['edit_target_date'];
    $completed = isset($_POST['edit_completed']) ? 1 : 0;
    $updated_at = date('Y-m-d H:i:s');
    
    // Validação básica
    if (empty($title)) {
        $_SESSION['message'] = '<p class="error">O título da meta é obrigatório.</p>';
    } else {
        try {
            // Verificar se a meta pertence ao usuário atual
            $check_stmt = $pdo->prepare("SELECT * FROM metas WHERE id = :meta_id AND user_id = :user_id");
            $check_stmt->bindParam(':meta_id', $meta_id);
            $check_stmt->bindParam(':user_id', $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // A meta pertence ao usuário, podemos atualizar
                $update_stmt = $pdo->prepare("UPDATE metas SET title = :title, description = :description, 
                                            target_date = :target_date, completed = :completed, 
                                            updated_at = :updated_at WHERE id = :meta_id");
                
                $update_stmt->bindParam(':title', $title);
                $update_stmt->bindParam(':description', $description);
                $update_stmt->bindParam(':target_date', $target_date);
                $update_stmt->bindParam(':completed', $completed);
                $update_stmt->bindParam(':updated_at', $updated_at);
                $update_stmt->bindParam(':meta_id', $meta_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['message'] = '<p class="success">Meta atualizada com sucesso!</p>';
                } else {
                    $_SESSION['message'] = '<p class="error">Erro ao atualizar a meta.</p>';
                }
            } else {
                $_SESSION['message'] = '<p class="error">Meta não encontrada ou você não tem permissão para editá-la.</p>';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = '<p class="error">Erro no banco de dados: ' . $e->getMessage() . '</p>';
        }
        
        header('Location: metas.php');
        exit;
    }
}

// Criar nova meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title-meta'])) {
    $title = trim($_POST['title-meta']);
    $description = trim($_POST['desc-meta']);
    $target_date = $_POST['data-meta'];
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');
    $completed = 0;
    
    // Validação básica
    if (empty($title)) {
        $_SESSION['message'] = '<p class="error">O título da meta é obrigatório.</p>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO metas (user_id, title, description, target_date, completed, created_at, updated_at) 
                                 VALUES (:user_id, :title, :description, :target_date, :completed, :created_at, :updated_at)");
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':target_date', $target_date);
            $stmt->bindParam(':completed', $completed);
            $stmt->bindParam(':created_at', $created_at);
            $stmt->bindParam(':updated_at', $updated_at);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = '<p class="success">Meta criada com sucesso!</p>';
            } else {
                $_SESSION['message'] = '<p class="error">Erro ao criar a meta.</p>';
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = '<p class="error">Erro no banco de dados: ' . $e->getMessage() . '</p>';
        }
    }
    
    header('Location: metas.php');
    exit;
}

// Função para calcular progresso
function calculateProgress($meta_id, $created_at, $target_date, $pdo) {
    $created_date = new DateTime($created_at);
    $target_date_obj = new DateTime($target_date);
    $today = new DateTime();
    
    // Calcular total de dias
    $total_days = $created_date->diff($target_date_obj)->days;
    
    if ($total_days == 0) {
        return 100;
    }
    
    // Contar dias com progresso
    $progress_stmt = $pdo->prepare("SELECT COUNT(*) as progress_days FROM daily_progress WHERE meta_id = :meta_id");
    $progress_stmt->bindParam(':meta_id', $meta_id);
    $progress_stmt->execute();
    $progress_data = $progress_stmt->fetch(PDO::FETCH_ASSOC);
    $progress_days = $progress_data['progress_days'];
    
    // Calcular porcentagem
    $percentage = ($progress_days / $total_days) * 100;
    return min(100, $percentage);
}

// Função para verificar se pode marcar progresso hoje
function canMarkProgressToday($meta_id, $pdo) {
    $today = date('Y-m-d');
    $check_stmt = $pdo->prepare("SELECT * FROM daily_progress WHERE meta_id = :meta_id AND progress_date = :today");
    $check_stmt->bindParam(':meta_id', $meta_id);
    $check_stmt->bindParam(':today', $today);
    $check_stmt->execute();
    
    return $check_stmt->rowCount() == 0;
}

// Função para obter detalhes completos do progresso
function getProgressDetails($meta_id, $created_at, $target_date, $pdo) {
    $created_date = new DateTime($created_at);
    $target_date_obj = new DateTime($target_date);
    $today = new DateTime();
    
    // Calcular total de dias
    $total_days = $created_date->diff($target_date_obj)->days;
    
    // Contar dias com progresso
    $progress_stmt = $pdo->prepare("SELECT COUNT(*) as progress_days FROM daily_progress WHERE meta_id = :meta_id");
    $progress_stmt->bindParam(':meta_id', $meta_id);
    $progress_stmt->execute();
    $progress_data = $progress_stmt->fetch(PDO::FETCH_ASSOC);
    $progress_days = $progress_data['progress_days'];
    
    // Calcular dias restantes
    $days_remaining = max(0, $target_date_obj->diff($today)->days);
    if ($today > $target_date_obj) {
        $days_remaining = 0;
    }
    
    // Calcular porcentagem
    $percentage = $total_days > 0 ? ($progress_days / $total_days) * 100 : 100;
    $percentage = min(100, $percentage);
    
    // Obter histórico de progresso
    $history_stmt = $pdo->prepare("SELECT progress_date FROM daily_progress WHERE meta_id = :meta_id ORDER BY progress_date DESC LIMIT 7");
    $history_stmt->bindParam(':meta_id', $meta_id);
    $history_stmt->execute();
    $recent_progress = $history_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    return [
        'total_days' => $total_days,
        'progress_days' => $progress_days,
        'days_remaining' => $days_remaining,
        'percentage' => round($percentage, 1),
        'recent_progress' => $recent_progress,
        'days_missed' => max(0, $total_days - $progress_days - $days_remaining)
    ];
}

try {
    $stmt = $pdo->prepare("SELECT * FROM metas WHERE user_id = :user_id ORDER BY target_date ASC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<p class="error">Erro ao buscar metas: ' . $e->getMessage() . '</p>';
    $metas = array();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Metas</title>
  <link rel="stylesheet" href="./styles/metas.css">
</head>
<body>
    <?php include('./components/header.php'); ?>
  <main>
    <div class="container-metas">
      <h1 class="title title-metas">Minhas Metas</h1>
      
      <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
      <?php endif; ?>
      
      <button id="btn-criar-meta" class="button"> + Adicionar Meta</button>
      
      <div id="form-container" class = "modalContainer"  style="display:none;">
        <form action="metas.php" method="post"  class = "backgroundModal">
          <h2 class = "title-modal"> Criar Meta </h2>
          <div class="form-group">
            <label for="title-meta" class = "label">Título da Meta:</label>
            <input type="text" name="title-meta" id="title-meta" required>
          </div>
          
          <div class="form-group">
            <label for="desc-meta" class = "label">Descrição:</label>
            <textarea name="desc-meta" id="desc-meta" rows="3" class = "scrollbar"></textarea>
          </div>
          
          <div class="form-group">
            <label for="data-meta" class = "label">Data Alvo:</label>
            <input type="date" name="data-meta" id="data-meta" required>
          </div>
          
          <div class = "buttons">
            <button type="submit" id="enviar-meta" class="button">Criar</button>
            <button type="button" id="cancelar-meta" class="button button-secondary">Cancelar</button>
          </div>
        </form>
      </div>
      
      <!-- Modal de Edição -->
      <div id="edit-modal" class = "modalContainer" style="display:none;">
        <div  class = "backgroundModal">
          <span class="close">&times;</span>
          <h2>Editar Meta</h2>
          <form action="metas.php" method="post">
            <input type="hidden" name="update_meta_id" id="update_meta_id">
            
            <div class="form-group">
              <label for="edit_title" class = "label">Título da Meta:</label>
              <input type="text" name="edit_title" id="edit_title" required>
            </div>
            
            <div class="form-group">
              <label for="edit_description" class = "label">Descrição:</label>
              <textarea name="edit_description" id="edit_description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
              <label for="edit_target_date" class = "label">Data Alvo:</label>
              <input type="date" name="edit_target_date" id="edit_target_date" required>
            </div>
            
            <div class="buttons">
                <input type="checkbox" name="edit_completed" id="edit_completed">
                Meta concluída
            </div>
            
            <div class = "buttons">
              <button type="submit" class="button">Atualizar</button>
              <button type="button" id="cancelar-atualizacao" class="button button-secondary">Cancelar</button>
            </div>
            
          </form>
        </div>
      </div>
      
      <!-- Exibir metas existentes em cards -->
      <div class="metas-list">
        <?php if (empty($metas)): ?>
          <p>Você ainda não possui metas. Crie uma nova meta para começar!</p>
        <?php else: ?>
        <?php foreach ($metas as $meta): ?>
          <?php 
            $progress_percentage = calculateProgress($meta['id'], $meta['created_at'], $meta['target_date'], $pdo);
            $can_mark_today = canMarkProgressToday($meta['id'], $pdo);
          ?>
          <div class="cards">
              <details>
               <summary>
                    <div class="card <?php echo $meta['completed'] ? 'card-completed' : ''; ?>">
                      <div class="card-header">
                        <span class="status-badge" <?php echo $meta['completed'] ? 'completed' : 'pending'; ?>>
                          <?php echo $meta['completed'] ? 'Concluída' : 'Em Progresso'; ?>
                        </span>
                        <h3 class="title-metax"><?php echo htmlspecialchars($meta['title']); ?></h3>
                        <p class="card-description"><?php echo htmlspecialchars($meta['description']); ?></p>
                        <span class = "ver-mais purple"> Ver mais</span>
                      </div>
                      <div class="card-body">
                        <p class="card-date"><img src="./assets/Clock.svg" alt=""> <?php echo date('d/m/Y', strtotime($meta['target_date'])); ?></p>
                        <progress class="barra-progresso" value="<?php echo $meta['completed'] ? '100' : $progress_percentage; ?>" max="100"></progress>
                        <span class="progress-text"><?php echo $meta['completed'] ? '100' : round($progress_percentage, 1); ?>%</span>
                      </div>
                  </summary>
                  <div class="buttons extra-cont">
                    <button class="edit edit-delete" data-id="<?php echo $meta['id']; ?>" 
                            data-title="<?php echo htmlspecialchars($meta['title']); ?>"
                            data-description="<?php echo htmlspecialchars($meta['description']); ?>"
                            data-target-date="<?php echo $meta['target_date']; ?>"
                            data-completed="<?php echo $meta['completed']; ?>">
                      <img src="./assets/Edit.svg" alt=""> Editar
                    </button>
        
                    <form action="metas.php" method="post" style="display:inline;">
                      <input type="hidden" name="delete_meta_id" value="<?php echo $meta['id']; ?>">
                      <button type="submit" class="delete edit-delete" onclick="return confirm('Tem certeza que deseja excluir esta meta?')"><img src="./assets/Trash 2.svg" alt="">Excluir</button>
                    </form>

                    <?php if (!$meta['completed'] && $can_mark_today): ?>
                      <form action="metas.php" method="post" style="display:inline;">
                        <input type="hidden" name="progress_meta_id" value="<?php echo $meta['id']; ?>">
                        <button type="submit" class="button">Marcar Progresso Hoje</button>
                      </form>
                    <?php elseif (!$meta['completed'] && !$can_mark_today): ?>
                      <button class="button" disabled>Progresso já marcado hoje</button>
                    <?php else: ?>
                      <button class="button" disabled>Meta Concluída</button>
                    <?php endif; ?>
                  </div>
                </div>
              </details>
          </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <script src="./scripts/metas.js">
  </script>
  <script>
    // Pintar o elemento do nav em que o usuário está presente 
    const colorMenu = document.querySelectorAll('.btn-menu li a')
    colorMenu[3].classList.add('purple', 'lineA-ativo')
    colorMenu[3].classList.remove('lineA')
  </script>
</body>
</html>