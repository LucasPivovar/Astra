<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se a sessão já está ativa
if (!isset($_SESSION)) {
    session_start();
}

// Captura mensagem de sucesso do redirecionamento
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $successMessage = 'Perfil atualizado com sucesso!';
}

require_once __DIR__ . '/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$successMessage = isset($successMessage) ? $successMessage : '';

// Função para sanitizar entradas - Só declara se não existir
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Busca os dados do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Processa o formulário de atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $bio = sanitizeInput($_POST['bio']);
        
        // Verifica se o username ou email já estão em uso por outro usuário
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Nome de usuário ou email já estão em uso';
        } else {
            // Atualiza os dados básicos do perfil
            $updateQuery = "UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?";
            $updateParams = [$username, $email, $bio, $user_id];
            
            // Verifica se uma nova senha foi fornecida
            if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $hashedPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                    $updateQuery = "UPDATE users SET username = ?, email = ?, bio = ?, password = ? WHERE id = ?";
                    $updateParams = [$username, $email, $bio, $hashedPassword, $user_id];
                } else {
                    $error = 'As senhas não coincidem';
                }
            }
            
            // Processo de upload de imagem de perfil
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $filetype = pathinfo($filename, PATHINFO_EXTENSION);
                
                // Verifica se o tipo de arquivo é permitido
                if (in_array(strtolower($filetype), $allowed)) {
                    // Cria um nome único para o arquivo
                    $newFilename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
                    $uploadDir = 'uploads/profiles/';
                    
                    // Cria o diretório se não existir
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $destination = $uploadDir . $newFilename;
                    $imagePath = $destination; // Este é o caminho que vai para o banco de dados
                    
                    // Move o arquivo para o diretório de destino
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                        // Adiciona a imagem ao update query
                        $updateQuery = "UPDATE users SET username = ?, email = ?, bio = ?, profile_image = ? WHERE id = ?";
                        
                        // Reorganiza os parâmetros para incluir profile_image
                        if (strpos($updateQuery, "password = ?") !== false) {
                            $updateQuery = "UPDATE users SET username = ?, email = ?, bio = ?, password = ?, profile_image = ? WHERE id = ?";
                            array_splice($updateParams, -1, 0, [$imagePath]);
                        } else {
                            $updateParams = [$username, $email, $bio, $imagePath, $user_id];
                        }
                        
                        // Remove a imagem anterior se existir
                        if (!empty($user['profile_image']) && file_exists($user['profile_image']) && $user['profile_image'] != $destination) {
                            unlink($user['profile_image']);
                        }
                    } else {
                        $error = 'Erro ao fazer upload da imagem: ' . (error_get_last() ? error_get_last()['message'] : 'Motivo desconhecido');
                    }
                } else {
                    $error = 'Formato de arquivo não permitido. Use apenas JPG, JPEG, PNG ou GIF.';
                }
            }
            
            if (empty($error)) {
                try {
                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->execute($updateParams);
                    
                    // Atualiza também o nome de usuário na sessão
                    $_SESSION['username'] = $username;
                    
                    // Redireciona para garantir uma recarga completa da página
                    header("Location: perfil.php?success=1");
                    exit;
                } catch (PDOException $e) {
                    $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Astra</title>
    <link rel="stylesheet" href="./styles/header.css">
    <link rel="stylesheet" href="./styles/perfil.css">
</head>
<body>
    <!-- Incluir o cabeçalho (header.php ou similar) -->
    <?php
        include('./components/header.php')
    ?>
    <main class="profile-container">
        <div class="profile-header">
            <h1>Meu Perfil</h1>
            <p>Gerencie suas informações pessoais e preferências</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!empty($successMessage)): ?>
            <div class="alert success"><?= $successMessage ?></div>
        <?php endif; ?>

        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="profile-image-container">
                    <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Foto de perfil" class="profile-image">
                    <?php else: ?>
                        <div class="profile-image-placeholder">
                            <span><?= strtoupper(substr($user['username'], 0, 1)) ?></span>
                        </div>
                    <?php endif; ?>
                    <h2><?= htmlspecialchars($user['username']) ?></h2>
                    <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number">0</span>
                        <span class="stat-label">Metas Concluídas</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">0</span>
                        <span class="stat-label">Posts na Comunidade</span>
                    </div>
                </div>
            </div>

            <div class="profile-form-container">
                <h3>Editar Perfil</h3>
                <form method="POST" action="" enctype="multipart/form-data" class="profile-form">
                    <div class="form-group">
                        <label for="username">Nome de usuário</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="bio">Biografia</label>
                        <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="profile_image">Foto de Perfil</label>
                        <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif">
                        <div class="file-input-info">
                            <p>Formatos permitidos: JPG, PNG, GIF</p>
                            <p>Tamanho máximo: 2MB</p>
                        </div>
                    </div>

                    <h3>Alterar Senha</h3>
                    <div class="form-group">
                        <label for="new_password">Nova Senha</label>
                        <input type="password" id="new_password" name="new_password">
                        <p class="input-hint">Deixe em branco para manter a senha atual</p>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Senha</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="button primary-button">Salvar Alterações</button>
                        <a href="index.php" class="button secondary-button">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> Astra. Todos os direitos reservados.</p>
    </footer>

    <script src="./scripts/perfil.js"></script>
</body>
</html>