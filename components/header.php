<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se a sessão já está ativa
if (!isset($_SESSION)) {
    session_start();
}

// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/../db.php'; // Certifique-se de que o caminho está correto

$error = '';
$successMessage = '';
$isLoggedIn = isset($_SESSION['user_id']);

// Processa as solicitações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Login
    if (isset($_POST['login'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php"); // Redireciona para a página do chat
            exit;
        } else {
            $error = 'Credenciais inválidas';
        }
    }

    // Registro
    if (isset($_POST['register'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = 'Senhas não coincidem';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $error = 'Usuário já existe';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                    $stmt->execute([$username, $hashedPassword]);
                    $successMessage = 'Registro realizado! Faça login.';
                } catch (PDOException $e) {
                    $error = 'Erro no registro: ' . $e->getMessage();
                }
            }
        }
    }

    // Logout
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: index.php'); // Redireciona para a página inicial
        exit;
    }
}

// Função para sanitizar entradas
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Astra</title>
  <link rel="stylesheet" href="./styles/header.css">
</head>
<body>
  <nav>
    <h1 class="blue title nome-empresa">Astra</h1>
    <ul class="btn-menu">
      <li><a href="#" class="lineA">Comunidade</a></li>
      <li><a href="bot.php" class="lineA">IA Assistente</a></li>
      <li><a href="#" class="lineA">Metas</a></li>
      <li><a href="index.php" class="lineA">Início</a></li>
    </ul>

    <?php if ($isLoggedIn): ?>
      <div class="user-panel">
        <span><?= htmlspecialchars($_SESSION['username']) ?></span>
        <form method="POST" action="index.php" style="display: inline;">
          <input type="hidden" name="logout" value="1">
          <button type="submit" class="button">Sair</button>
        </form>
      </div>
    <?php else: ?>
      <button id="btnLogin" class="loginButton button">Entre no Astra</button>
    <?php endif; ?>

    <!-- Modal de login só é necessário se o usuário não estiver logado -->
    <?php if (!$isLoggedIn): ?>
    <section id="modal" class="modalContainer">
      <div class="backgroundModal">
        <?php if (!empty($error)): ?>
          <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        <?php if (!empty($successMessage)): ?>
          <div class="alert success"><?= $successMessage ?></div>
        <?php endif; ?>

        <h2 id="titleModal">Entre em Sua Conta</h2>
        <form method="POST" id="formLogin" action="index.php">
          <input type="hidden" name="login" value="1">
          <label for="login_username">Usuário</label>
          <input type="text" id="login_username" name="username" required>
          <label for="login_password">Senha</label>
          <input type="password" id="login_password" name="password" required>
          <button type="submit" class="button">Fazer Login</button>
        </form>

        <form method="POST" id="formSignUp" action="index.php" style="display: none">
          <input type="hidden" name="register" value="1">
          <label for="signup_username">Usuário</label>
          <input type="text" id="signup_username" name="username" required>
          <label for="signup_password">Senha</label>
          <input type="password" id="signup_password" name="password" required>
          <label for="confirm_password">Confirme a Senha</label>
          <input type="password" id="confirm_password" name="confirm_password" required>
          <button type="submit" class="button">Registrar</button>
        </form>
        <p id="textSignUp">Não tem uma conta? <span id="signUp" class="register blue">Registre-se agora</span></p>
      </div>
    </section>
    <?php endif; ?>
  </nav>
  <script src="./scripts/header.js"></script>
</body>
</html>