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
        // Verificar se a função já existe antes de usá-la
        $username = function_exists('sanitizeInput') ? sanitizeInput($_POST['username']) : htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
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
        // Verificar se a função já existe antes de usá-la
        $username = function_exists('sanitizeInput') ? sanitizeInput($_POST['username']) : htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
        $email = function_exists('sanitizeInput') ? sanitizeInput($_POST['email']) : htmlspecialchars(trim($_POST['email']), ENT_QUOTES, 'UTF-8');
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validação de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Endereço de email inválido';
        } 
        // Validação de senhas
        elseif ($password !== $confirm_password) {
            $error = 'Senhas não coincidem';
        } else {
            // Verifica se usuário já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                $error = 'Usuário ou email já existem';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword]);
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

// Função para sanitizar entradas - Só declara se não existir
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>

<!-- codigo HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Astra</title>
  <link rel="stylesheet" href="./styles/header.css">
</head>
<body>
  <!-- Menu de navegação -->
  <nav>
    <h1 class="purple title nome-empresa">Astra</h1>
    <ul class="btn-menu">
      <li><a href="index.php" class="lineA">Início</a></li>
      <li><a href="#" class="lineA">Comunidade</a></li>
      <li><a href="bot.php" class="lineA">IA Assistente</a></li>
      <li><a href="#" class="lineA">Metas</a></li>
    </ul>
    
    <?php if ($isLoggedIn): ?>
      <div class="user-panel">
        <span id="userProfileBtn"><?= htmlspecialchars($_SESSION['username']) ?></span>
      </div>
      
      <!-- Modal do perfil do usuário -->
      <section id="userProfileModal" class="modalContainer">
        <div class="backgroundModal profileModal">
          <h2>Perfil do Usuário</h2>
          <p id="userProfileOpen">Bem-vindo(a), <?= htmlspecialchars($_SESSION['username']) ?>!</p>
          
          <div class="profile-options">
            <!-- Opção de Perfil -->
            <div class="profile-option-item">
              <a href="perfil.php" id="viewProfileBtn" class="profile-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Meu Perfil</span>
              </a>
            </div>
            
            <!-- Opção de Configurações -->
            <div class="profile-option-item">
              <a href="#" id="settingsBtn" class="profile-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Configurações</span>
              </a>
            </div>
            
            <!-- Opção de Logout -->
            <div class="profile-option-item">
              <form method="POST" action="index.php" class="logout-form">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="profile-link logout-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                  </svg>
                  <span>Sair</span>
                </button>
              </form>
            </div>
          </div>
          
          <button id="closeProfileModal" class="button">Fechar</button>
        </div>
      </section>
    <?php else: ?>
      <button id="btnLogin" class="loginButton button">Entre no Astra</button>
      
      <!-- Modal de login/cadastro -->
      <section id="modal" class="modalContainer">
        <div class="backgroundModal">
          <h2 id="titleModal">Entre em Sua Conta</h2>
          
          <?php if (!empty($error)): ?>
            <div class="alert error"><?= $error ?></div>
          <?php endif; ?>
          
          <?php if (!empty($successMessage)): ?>
            <div class="alert success"><?= $successMessage ?></div>
          <?php endif; ?>
          
          <form id="formLogin" method="POST" action="">
            <div>
              <span class="label userLoginLabel">Nome de usuário</span>
              <input type="text" name="username" required>
            </div>
            <br>
            <div>
              <span class="label">Senha</span>
              <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="button formButton">Entrar</button>
          </form>
          
          <form id="formSignUp" method="POST" action="">
            <div>
              <span class="label userLabel">Usuário</span>
              <input type="text" name="username" required>
            </div>
            <br>
            <div>
              <span class="label">Email</span>
              <input type="email" name="email" required>
            </div>
            <br>
            <div>
              <span class="label">Senha</span>
              <input type="password" name="password" required>
            </div>
            <br>
            <div>
              <span class="label">Confirmar</span>
              <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" name="register" class="button formButton">Cadastrar</button>
          </form>
          
          <br>
          <p id="textSignUp">Não tem uma conta? <span id="signUp" class="register purple">Registre-se agora</span></p>
        </div>
      </section>
    <?php endif; ?>
  </nav>
  <script src="./scripts/header.js"></script>
</body>
</html>