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
      <h1 class = "title title-metas">Minhas Metas</h1>
      
      <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
      <?php endif; ?>
      
      <button id="btn-criar-meta" class="button"> + Adicionar Meta</button>
      
      <div id="form-container" style="display:none;">
        <form action="metas.php" method="post">
          <div class="form-group">
            <label for="title-meta">Título da Meta:</label>
            <input type="text" name="title-meta" id="title-meta" required>
          </div>
          
          <div class="form-group">
            <label for="desc-meta">Descrição:</label>
            <textarea name="desc-meta" id="desc-meta" rows="3"></textarea>
          </div>
          
          <div class="form-group">
            <label for="data-meta">Data Alvo:</label>
            <input type="date" name="data-meta" id="data-meta" required>
          </div>
          
          <button type="submit" id="enviar-meta" class="button">Criar</button>
          <button type="button" id="cancelar-meta" class="button button-secondary">Cancelar</button>
        </form>
      </div>
      
      <!-- Exibir metas existentes em cards -->
      <div class="metas-list">
        <?php if (empty($metas)): ?>
          <p>Você ainda não possui metas. Crie uma nova meta para começar!</p>
        <?php else: ?>
          <div class="cards">
            <?php foreach ($metas as $meta): ?>
              <div class="card <?php echo $meta['completed'] ? 'card-completed' : ''; ?>">
                <div class="card-header">
                  <span class="status-badge" <?php echo $meta['completed'] ? 'completed' : 'pending'; ?>>
                    <?php echo $meta['completed'] ? 'Concluída' : 'Em Progresso'; ?>
                  </span>
                  <h3 class = "title-metax"><?php echo htmlspecialchars($meta['title']); ?></h3>
                  <p class="card-description"><?php echo htmlspecialchars($meta['description']); ?></p>
                <div class="card-body">
                  <p class="card-date"><img src="./assets/Clock.svg" alt=""> <?php echo date('d/m/Y', strtotime($meta['target_date'])); ?></p>
                  <progress class = "barra-progresso" value = "50" max = "100"></progress>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
    <script src = "./scripts/metas.js"></script>
</body>
</html>