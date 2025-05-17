document.addEventListener('DOMContentLoaded', () => {
  // Modal de login/cadastro e seus elementos
  const btnLogin = document.getElementById('btnLogin');
  const modal = document.getElementById('modal');
  const formLogin = document.getElementById('formLogin');
  const formSignUp = document.getElementById('formSignUp');
  const titleModal = document.getElementById('titleModal');
  const textSignUp = document.getElementById('textSignUp');
  const cmaButton = document.getElementById('cmaButton')

  // Modal de perfil do usuário
  const userProfileBtn = document.getElementById('userProfileBtn');
  const userProfileModal = document.getElementById('userProfileModal');
  const closeProfileModal = document.getElementById('closeProfileModal');
  
  // Menu mobile
  const btnHamburguer = document.getElementById('btn-hamburguer');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileMenuContent = mobileMenu ? mobileMenu.querySelector('.mobile-menu-content') : null;
  const mobileLoginBtn = document.getElementById('mobile-login-btn');
  
  // Abrir modal de login/cadastro
  if (btnLogin) {
    btnLogin.addEventListener('click', (e) => {
      e.preventDefault();
      openLoginModal();
    });
  }

  if (cmaButton) {
    cmaButton.addEventListener('click', (e) => {
      e.preventDefault();
      openLoginModal();
    });
  }
  
  // Função para abrir o modal de login
  function openLoginModal() {
    if (modal) {
      modal.style.display = 'flex';
      // Garantir que o formulário de login está visível por padrão
      formLogin.style.display = 'flex';
      formSignUp.style.display = 'none';
      titleModal.textContent = 'Entre em Sua Conta';
      textSignUp.innerHTML = 'Não tem uma conta? <span id="signUp" class="register purple">Registre-se agora</span>';
    }
  }
  
  // Alternar entre formulários de login e registro
  document.addEventListener('click', function(e) {
    // Alternar para o formulário de registro
    if (e.target && e.target.id === 'signUp') {
      e.preventDefault();
      formLogin.style.display = 'none';
      formSignUp.style.display = 'flex';
      titleModal.textContent = 'Cadastrar';
      textSignUp.innerHTML = 'Já tem uma conta? <span id="loginLink" class="register purple">Faça login</span>';
    }
    
    // Alternar para o formulário de login
    if (e.target && e.target.id === 'loginLink') {
      e.preventDefault();
      formSignUp.style.display = 'none';
      formLogin.style.display = 'flex';
      titleModal.textContent = 'Entre em Sua Conta';
      textSignUp.innerHTML = 'Não tem uma conta? <span id="signUp" class="register purple">Registre-se agora</span>';
    }
  });
  
  // Fechar modal ao clicar fora
  if (modal) {
    window.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
  }
  
  // Gerenciamento do modal de perfil do usuário
  if (userProfileBtn && userProfileModal) {
    userProfileBtn.addEventListener('click', function(e) {
      e.preventDefault();
      userProfileModal.style.display = 'flex';
    });
    
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
  }
  
  // Gerenciamento do menu mobile
  if (btnHamburguer && mobileMenu) {
    // Abrir/fechar menu mobile ao clicar no botão hambúrguer
    btnHamburguer.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      toggleMobileMenu();
    });
    
    // Função para alternar a visibilidade do menu mobile
    function openMobileMenu() {
      mobileMenu.classList.add('open');
    }
    
    function closeMobileMenu() {
      mobileMenu.classList.remove('open');
    }
    
    function toggleMobileMenu() {
      if (mobileMenu.classList.contains('open')) {
        closeMobileMenu();
      } else {
        openMobileMenu();
      }
    }
    
    // Fechar menu mobile ao clicar no fundo escuro (overlay)
    mobileMenu.addEventListener('click', function(event) {
      // Se o clique foi direto no menu mobile (fundo escuro) e não no seu conteúdo
      if (event.target === mobileMenu) {
        closeMobileMenu();
      }
    });
    
    // Prevenir a propagação de cliques do conteúdo do menu para o overlay
    if (mobileMenuContent) {
      mobileMenuContent.addEventListener('click', function(event) {
        event.stopPropagation();
      });
    }
    
    // Fechar menu mobile ao redimensionar a janela para desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 1024 && mobileMenu.classList.contains('open')) {
        closeMobileMenu();
      }
    });
    
    // Fechar menu ao clicar em links do menu mobile
    const mobileMenuLinks = mobileMenu.querySelectorAll('.mobile-menu-link');
    mobileMenuLinks.forEach(link => {
      link.addEventListener('click', function() {
        closeMobileMenu();
      });
    });
  }
  
  if (mobileLoginBtn && modal) {
    mobileLoginBtn.addEventListener('click', function(e) {
      e.preventDefault();
      
      if (mobileMenu && mobileMenu.classList.contains('open')) {
        mobileMenu.classList.remove('open');
      }
      
      openLoginModal();
    });
  }
});