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

// Função para obter a imagem de perfil do usuário
function getUserProfileImage($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT profile_image FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && !empty($result['profile_image']) && file_exists($result['profile_image'])) {
            return $result['profile_image'];
        }
        return "./assets/default-profile.png";
    } catch (PDOException $e) {
        return "./assets/default-profile.png";
    }
}

// Obter a imagem de perfil se o usuário estiver logado
$userProfileImage = $isLoggedIn ? getUserProfileImage($pdo, $_SESSION['user_id']) : "./assets/default-profile.png";

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
        header('Location: index.php'); 
        exit;
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Astra</title>
  <link rel="stylesheet" href="./styles/header.css">
  <link rel="shortcut icon" type="imagex/png" href="./assets/logo.svg">
</head>
<body>
  <!-- Menu de navegação -->
  <nav>
    <img src="./assets/logo.svg" alt="" class="logo-empresa">
    <ul class="btn-menu">
      <li><a href="index.php" class="lineA">Início</a></li>
      <li><a href="community.php" class="lineA">Comunidade</a></li>
      <li><a href="bot.php" class="lineA">Assistente Virtual</a></li>
      <li><a href="metas.php" class="lineA">Metas</a></li>
    </ul>
    
    <!-- O botão hambúrguer só aparece para usuários logados -->
    <?php if ($isLoggedIn): ?>
      <button id="btn-hamburguer" class="hamburguer"><img src="../assets/Hamburguer.svg" alt="Menu"></button>
    <?php endif; ?>
    
    <?php if ($isLoggedIn): ?>
      <div class="user-panel">
        <img id="userProfileBtn" src="<?= $userProfileImage ?>" alt="Foto de Perfil" class="profile-img">
      </div>
      
      <!-- Modal do perfil do usuário -->
      <section id="userProfileModal" class="modalContainer">
        <div class="backgroundModal profileModal">
          <h2>Perfil do Usuário</h2>
          <!-- Adicionando a imagem de perfil no modal -->
          <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <p id="userProfileOpen">Bem-vindo(a), <?= htmlspecialchars($_SESSION['username']) ?>!</p>
          </div>
          
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
            <div class= "container-input">
              <span class="label userLoginLabel">Nome de usuário</span>
              <input type="text" name="username" required>
            </div>
            <br>
            <div class= "container-input">
              <span class="label userPasswordLabel">Senha</span>
              <input type="password" name="password" required>
            </div>
            <button type="submit" name="login" class="button formButton">Entrar</button>
          </form>
          
          <form id="formSignUp" method="POST" action="">
            <div class= "container-input">
              <span class="label userLabel">Usuário</span>
              <input type="text" name="username" required>
            </div>
            <br>
            <div class= "container-input">
              <span class="label">Email</span>
              <input type="email" name="email" required>
            </div>
            <br>
            <div class= "container-input">
              <span class="label">Senha</span>
              <input type="password" name="password" required>
            </div>
            <br>
            <div class= "container-input">
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
  
  <!-- Menu Mobile - Só exibido se o usuário estiver logado -->
  <?php if ($isLoggedIn): ?>
  <div id="mobile-menu">
    <div class="mobile-menu-content">
      <ul class="mobile-menu-items">
        <li class="mobile-menu-item">
          <img src="<?= $userProfileImage ?>" alt="Foto de Perfil" class="mobile-profile-img">
        </li>
        <li class="mobile-menu-item">
          <a href="index.php" class="mobile-menu-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-icon">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            Início
          </a>
        </li>
        <li class="mobile-menu-item">
          <a href="community.php" class="mobile-menu-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-icon">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Comunidade
          </a>
        </li>
        <li class="mobile-menu-item">
          <a href="bot.php" class="mobile-menu-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-icon">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            IA Assistente
          </a>
        </li>
        <li class="mobile-menu-item">
          <a href="#" class="mobile-menu-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-icon">
              <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
            </svg>
            Metas
          </a>
        </li>
        <li class="mobile-menu-item">
          <a href="perfil.php" class="mobile-menu-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mobile-menu-icon">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Perfil
          </a>
        </li>
        <li>
          <form method="POST" action="index.php">
            <input type="hidden" name="logout" value="1">
            <button type="submit" class="mobile-logout-btn">Sair</button>
          </form>
        </li>
      </ul>
    </div>
  </div>
  <?php endif; ?>
  
  <script src="./scripts/header.js"></script>
</body>
</html>