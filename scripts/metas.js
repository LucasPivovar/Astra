// Pintar o elemento do nav em que o usuário está presente 
const colorMenu = document.querySelectorAll('.btn-menu li a')
colorMenu[3].classList.add('purple')

document.addEventListener('DOMContentLoaded', function() {
      const btnCriarMeta = document.getElementById('btn-criar-meta');
      const formContainer = document.getElementById('form-container');
      const btnCancelar = document.getElementById('cancelar-meta');
      
      btnCriarMeta.addEventListener('click', function() {
        formContainer.style.display = 'block';
        btnCriarMeta.style.display = 'none';
      });
      
      btnCancelar.addEventListener('click', function() {
        formContainer.style.display = 'none';
        btnCriarMeta.style.display = 'block';
        // Reset form
        document.querySelector('form').reset();
      });
    });