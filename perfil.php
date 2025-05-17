<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$user_data = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Erro ao buscar dados do usuário: ' . $e->getMessage();
}

try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_profiles'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $sql = "CREATE TABLE user_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            bio TEXT,
            profile_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
    }
} catch (PDOException $e) {
    $error_message = 'Erro ao verificar ou criar tabela de perfis: ' . $e->getMessage();
}

$profile_data = null;
try {
    // Fix: Query the user_profiles table using user_id column, not the users table
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile_data) {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = 'Erro ao buscar ou criar perfil: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['update_profile'])) {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $bio = sanitizeInput($_POST['bio']);
        
        if ($username !== $user_data['username']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error_message = 'Este nome de usuário já está em uso.';
            }
        }
        
        if ($email !== $user_data['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                $error_message = 'Este email já está em uso.';
            }
        }
        
        if (empty($error_message)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $user_id]);
                
                $stmt = $pdo->prepare("UPDATE user_profiles SET bio = ? WHERE user_id = ?");
                $stmt->execute([$bio, $user_id]);
                
                $_SESSION['username'] = $username;
                
                $success_message = 'Perfil atualizado com sucesso!';
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error_message = 'Erro ao atualizar perfil: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!password_verify($current_password, $user_data['password'])) {
            $error_message = 'Senha atual incorreta.';
        } 
        elseif ($new_password !== $confirm_password) {
            $error_message = 'As novas senhas não coincidem.';
        } 
        elseif (strlen($new_password) < 6) {
            $error_message = 'A nova senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success_message = 'Senha alterada com sucesso!';
            } catch (PDOException $e) {
                $error_message = 'Erro ao alterar senha: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; 
        
        $file = $_FILES['profile_image'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $error_message = 'Apenas imagens JPEG, PNG e GIF são permitidas.';
        } 
        elseif ($file['size'] > $max_size) {
            $error_message = 'O tamanho máximo do arquivo é 5MB.';
        } else {
            $upload_dir = __DIR__ . '/uploads/profile_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            $db_file_path = 'uploads/profile_images/' . $file_name;
        
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    if (!empty($profile_data['profile_image']) && 
                        $profile_data['profile_image'] != 'assets/default-profile.png' && 
                        file_exists(__DIR__ . '/' . $profile_data['profile_image'])) {
                        unlink(__DIR__ . '/' . $profile_data['profile_image']);
                    }
                    
                    error_log("Saving profile_image path: " . $db_file_path . " for user_id: " . $user_id);
                    
                    $stmt = $pdo->prepare("UPDATE user_profiles SET profile_image = ? WHERE user_id = ?");
                    $result = $stmt->execute([$db_file_path, $user_id]);
                    
                    if ($result) {
                        $success_message = 'Foto de perfil atualizada com sucesso!';
                        
                        if ($stmt->rowCount() == 0) {
                            error_log("Profile image update query didn't affect any rows for user_id: " . $user_id);
                            
                            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, profile_image) VALUES (?, ?) 
                                                   ON DUPLICATE KEY UPDATE profile_image = ?");
                            $stmt->execute([$user_id, $db_file_path, $db_file_path]);
                        }
                        
                        $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        error_log("Profile data after update: " . print_r($profile_data, true));
                    } else {
                        $error_message = 'Erro ao atualizar o banco de dados.';
                        error_log("Database update failed for profile image. Error info: " . print_r($stmt->errorInfo(), true));
                    }
                } catch (PDOException $e) {
                    $error_message = 'Erro ao atualizar foto de perfil: ' . $e->getMessage();
                    error_log("PDOException when updating profile image: " . $e->getMessage());
                }
            } else {
                $error_message = 'Erro ao fazer upload da imagem. Verifique as permissões do diretório.';
                error_log("Failed to move uploaded file to: " . $file_path);
            }
        }
    }
}

$profile_image = 'assets/default-profile.png'; 
if (!empty($profile_data['profile_image']) && file_exists(__DIR__ . '/' . $profile_data['profile_image'])) {
    $profile_image = $profile_data['profile_image'];
}

// Define a bio do usuário
$bio = !empty($profile_data['bio']) ? $profile_data['bio'] : '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil</title>
    <link rel="stylesheet" href="./styles/header.css">
    <link rel="stylesheet" href="./styles/perfil.css">
    <link rel="shortcut icon" type="imagex/png" href="./assets/logo.svg">
    <?php
        include('./components/header.php')
    ?>
</head>
<body>    
    <main class="profile-container">
        <div class="profile-header">
            <h1>Meu Perfil</h1>
            <?php if (!empty($success_message)): ?>
                <div class="alert success"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert error"><?= $error_message ?></div>
            <?php endif; ?>
        </div>
        
        <div class="profile-content">
            <div class="profile-sidebar">
                <div class="profile-image-container">
                    <img src="<?= $profile_image ?>" alt="Foto de perfil" class="profile-image">
                    <form action="" method="POST" enctype="multipart/form-data" class="profile-image-form">
                        <div class="file-upload">
                            <label for="profile_image" class="file-upload-label">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 15v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                                </svg>
                                Alterar foto
                            </label>
                            <input type="file" name="profile_image" id="profile_image" accept="image/*" class="file-upload-input">
                        </div>
                        <button type="submit" id="submit-profile-image" style="display:none;">Enviar</button>
                    </form>
                </div>
                
                <div class="profile-stats">
                    <h3>@<?= htmlspecialchars($user_data['username']) ?></h3>
                    <p>Membro desde: <?= date('d/m/Y', strtotime($user_data['created_at'] ?? 'now')) ?></p>
                </div>
            </div>
            
            <div class="profile-main">
                <div class="profile-section" id="info-section">
                    <h2>Informações Pessoais</h2>
                    <form action="" method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="username">Nome de Usuário</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user_data['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Biografia</label>
                            <textarea id="bio" name="bio" rows="5" maxlength="500"><?= htmlspecialchars($bio) ?></textarea>
                            <small>Conte um pouco sobre você (máximo 500 caracteres)</small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="button">Salvar Alterações</button>
                    </form>
                </div>
                
                <div class="profile-section" id="password-section">
                    <h2>Alterar Senha</h2>
                    <form action="" method="POST" class="profile-form" id="password-form">
                        <div class="form-group">
                            <label for="current_password">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nova Senha</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nova Senha</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="button">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('profile_image').addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const fileType = this.files[0].type;
                    if (!['image/jpeg', 'image/png', 'image/gif'].includes(fileType)) {
                        alert('Apenas imagens JPEG, PNG e GIF são permitidas.');
                        this.value = ''; 
                        return;
                    }
                    
                    if (this.files[0].size > 5 * 1024 * 1024) {
                        alert('O tamanho máximo do arquivo é 5MB.');
                        this.value = ''; 
                        return;
                    }
                    
                    document.getElementById('submit-profile-image').style.display = 'block';
                    
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.querySelector('.profile-image').src = e.target.result;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            document.getElementById('submit-profile-image').addEventListener('click', function(e) {
                e.preventDefault();
                this.textContent = 'Enviando...';
                this.disabled = true;
                document.querySelector('.profile-image-form').submit();
            });
            
            document.getElementById('password-form').addEventListener('submit', function(e) {
                var newPassword = document.getElementById('new_password').value;
                var confirmPassword = document.getElementById('confirm_password').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('As senhas não coincidem!');
                }
            });
        });
    </script>
</body>
</html>