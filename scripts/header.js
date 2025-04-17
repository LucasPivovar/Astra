document.addEventListener('DOMContentLoaded', () => {
  // Abre o modal ao clicar no botão de login
  const btnLogin = document.getElementById('btnLogin');
  if (btnLogin) {
    btnLogin.addEventListener('click', () => {
      const modal = document.getElementById('modal');
      if (modal) {
        modal.style.display = 'block';
      }
    });
  }
  
  // Elementos do formulário
  const formLogin = document.getElementById('formLogin');
  const formSignUp = document.getElementById('formSignUp');
  const titleModal = document.getElementById('titleModal');
  const textSignUp = document.getElementById('textSignUp');
  
  // Usa delegação de eventos para capturar cliques em links de alternância
  document.addEventListener('click', function(e) {
    // Verifica se o elemento clicado é o link de registro
    if (e.target && e.target.id === 'signUp') {
      e.preventDefault();
      formLogin.style.display = 'none';
      formSignUp.style.display = 'block';
      titleModal.textContent = 'Cadastrar';
      textSignUp.innerHTML = 'Já tem uma conta? <span id="loginLink" class="register blue">Faça login</span>';
    }
    
    // Verifica se o elemento clicado é o link de login
    if (e.target && e.target.id === 'loginLink') {
      e.preventDefault();
      formSignUp.style.display = 'none';
      formLogin.style.display = 'block';
      titleModal.textContent = 'Entre em Sua Conta';
      textSignUp.innerHTML = 'Não tem uma conta? <span id="signUp" class="register blue">Registre-se agora</span>';
    }
  });
  
  // Fecha o modal ao clicar fora
  window.addEventListener('click', (e) => {
    const modal = document.getElementById('modal');
    if (modal && e.target === modal) {
      modal.style.display = 'none';
    }
  });

  // Código para o modal de perfil do usuário
  const userProfileBtn = document.getElementById('userProfileBtn');
  const userProfileModal = document.getElementById('userProfileModal');
  const closeProfileModal = document.getElementById('closeProfileModal');
  const viewProfileBtn = document.getElementById('viewProfileBtn');
  const settingsBtn = document.getElementById('settingsBtn');
  
  // Verifica se os elementos existem antes de adicionar os event listeners
  if (userProfileBtn && userProfileModal) {
    // Abrir modal quando clicar no nome do usuário
    userProfileBtn.addEventListener('click', function(e) {
      e.preventDefault(); // Previne comportamento padrão se for um link
      console.log('Botão de perfil clicado'); // Debug
      
      // Mostra o modal independentemente do dispositivo
      userProfileModal.style.display = 'flex';
    });
    
    // Manipuladores para as opções do perfil
    if (viewProfileBtn) {
      viewProfileBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // Aqui você pode implementar a navegação para a página de perfil
        alert('Funcionalidade de visualização de perfil será implementada em breve!');
      });
    }
    
    if (settingsBtn) {
      settingsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // Aqui você pode implementar a navegação para a página de configurações
        alert('Funcionalidade de configurações será implementada em breve!');
      });
    }
    
    // Fechar modal quando clicar no botão fechar
    if (closeProfileModal) {
      closeProfileModal.addEventListener('click', function() {
        userProfileModal.style.display = 'none';
      });
    }
    
    // Fechar o modal quando clicar fora dele
    window.addEventListener('click', function(event) {
      if (event.target === userProfileModal) {
        userProfileModal.style.display = 'none';
      }
    });
  } else {
    console.error('Elementos do modal de perfil não encontrados');
  }
  
  // Atualizar comportamento se a janela for redimensionada
  window.addEventListener('resize', function() {
    if (userProfileModal && userProfileModal.style.display === 'flex' && window.innerWidth < 768) {
      // Opcional: você pode decidir se quer fechar o modal em telas muito pequenas
      // userProfileModal.style.display = 'none';
    }
  });
});