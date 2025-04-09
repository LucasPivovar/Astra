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

  // Troca entre formulários (Login e Registro)
  const signUpLink = document.getElementById('signUp');
  if (signUpLink) {
    signUpLink.addEventListener('click', (e) => {
      e.preventDefault();

      const forms = {
        login: document.getElementById('formLogin'),
        signUp: document.getElementById('formSignUp')
      };

      if (forms.login && forms.signUp) {
        forms.login.style.display = 'none';
        forms.signUp.style.display = 'block';
        document.getElementById('titleModal').textContent = 'Cadastrar';
      }
    });
  }

  // Fecha o modal ao clicar fora
  window.addEventListener('click', (e) => {
    const modal = document.getElementById('modal');
    if (modal && e.target === modal) {
      modal.style.display = 'none';
    }
  });
});