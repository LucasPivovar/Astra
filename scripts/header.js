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
});