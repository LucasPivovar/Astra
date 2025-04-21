document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const profileImageContainer = document.querySelector('.profile-image-container');
    const profileImageInput = document.getElementById('profile_image');
    const profileImage = document.querySelector('.profile-image');
    const profilePlaceholder = document.querySelector('.profile-image-placeholder');
    const profileForm = document.querySelector('.profile-form');
    
    // Torna a área da imagem clicável
    if (profileImageContainer) {
        profileImageContainer.classList.add('clickable');
        
        // Adiciona texto de ajuda
        const helpText = document.createElement('p');
        helpText.className = 'help-text';
        helpText.textContent = 'Clique para alterar a foto';
        profileImageContainer.appendChild(helpText);
        
        // Ao clicar na imagem ou placeholder, ativa o input file
        if (profileImage) {
            profileImage.addEventListener('click', function() {
                profileImageInput.click();
            });
        } else if (profilePlaceholder) {
            profilePlaceholder.addEventListener('click', function() {
                profileImageInput.click();
            });
        }
    }
    
    // Preview da imagem de perfil antes do upload
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                // Verifica o tamanho do arquivo (máximo 2MB = 2 * 1024 * 1024 bytes)
                if (file.size > 2 * 1024 * 1024) {
                    alert('O arquivo é muito grande! Por favor, escolha uma imagem de até 2MB.');
                    this.value = ''; // Limpa o input
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    // Encontra a imagem de perfil ou o placeholder
                    const profileImage = document.querySelector('.profile-image');
                    const profilePlaceholder = document.querySelector('.profile-image-placeholder');
                    
                    if (profileImage) {
                        // Se já existe uma imagem, apenas atualiza a fonte
                        profileImage.src = e.target.result;
                    } else if (profilePlaceholder) {
                        // Se existe um placeholder, substitui por uma imagem
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Foto de perfil';
                        img.className = 'profile-image';
                        
                        const container = profilePlaceholder.parentElement;
                        container.replaceChild(img, profilePlaceholder);
                        
                        // Adiciona evento de clique na nova imagem
                        img.addEventListener('click', function() {
                            profileImageInput.click();
                        });
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Validação de formulário
    if (profileForm) {
        profileForm.addEventListener('submit', function(event) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword || confirmPassword) {
                if (newPassword !== confirmPassword) {
                    event.preventDefault();
                    alert('As senhas não coincidem!');
                }
            }
        });
    }
    
    // Oculta o campo de input file visualmente, mas mantém o label para acessibilidade
    const fileInputContainer = profileImageInput.closest('.form-group');
    if (fileInputContainer) {
        fileInputContainer.style.display = 'none';
    }
});