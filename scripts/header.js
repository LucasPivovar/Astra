  document.addEventListener('DOMContentLoaded', () => {   
    const btnLogin = document.getElementById('btnLogin');
    if (btnLogin) {
      btnLogin.addEventListener('click', () => {
        const modal = document.getElementById('modal');
        if (modal) {
          modal.style.display = 'block';
        }
      });
    }
    
    const formLogin = document.getElementById('formLogin');
    const formSignUp = document.getElementById('formSignUp');
    const titleModal = document.getElementById('titleModal');
    const textSignUp = document.getElementById('textSignUp');
    
    document.addEventListener('click', function(e) {
      if (e.target && e.target.id === 'signUp') {
        e.preventDefault();
        formLogin.style.display = 'none';
        formSignUp.style.display = 'flex';
        titleModal.textContent = 'Cadastrar';
        textSignUp.innerHTML = 'Já tem uma conta? <span id="loginLink" class="register purple">Faça login</span>';
      }
      
      if (e.target && e.target.id === 'loginLink') {
        e.preventDefault();
        formSignUp.style.display = 'none';
        formLogin.style.display = 'flex';
        titleModal.textContent = 'Entre em Sua Conta';
        textSignUp.innerHTML = 'Não tem uma conta? <span id="signUp" class="register purple">Registre-se agora</span>';
      }
    });
    
    window.addEventListener('click', (e) => {
      const modal = document.getElementById('modal');
      if (modal && e.target === modal) {
        modal.style.display = 'none';
      }
    });

    const userProfileBtn = document.getElementById('userProfileBtn');
    const userProfileModal = document.getElementById('userProfileModal');
    const closeProfileModal = document.getElementById('closeProfileModal');
    const viewProfileBtn = document.getElementById('viewProfileBtn');
    const settingsBtn = document.getElementById('settingsBtn');
    
    if (userProfileBtn && userProfileModal) {
      userProfileBtn.addEventListener('click', function(e) {
        e.preventDefault(); 
        console.log('Botão de perfil clicado'); 
        
        userProfileModal.style.display = 'flex';
      });
      
      if (viewProfileBtn) {
        viewProfileBtn.addEventListener('click', function(e) {
          e.preventDefault();
          window.showAlert('info', 'Funcionalidade de visualização de perfil será implementada em breve!');
        });
      }
      
      if (settingsBtn) {
        settingsBtn.addEventListener('click', function(e) {
          e.preventDefault();
          window.showAlert('info', 'Funcionalidade de configurações será implementada em breve!');
        });
      }
      
      if (closeProfileModal) {
        closeProfileModal.addEventListener('click', function() {
          userProfileModal.style.display = 'none';
        });
      }
      
      window.addEventListener('click', function(event) {
        if (event.target === userProfileModal) {
          userProfileModal.style.display = 'none';
        }
      });
    } else {
      console.error('Elementos do modal de perfil não encontrados');
    }
    
    window.addEventListener('resize', function() {
      if (userProfileModal && userProfileModal.style.display === 'flex' && window.innerWidth < 768) {
      }
    });
  });