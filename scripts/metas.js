document.addEventListener('DOMContentLoaded', function() {
  const btnCriarMeta = document.getElementById('btn-criar-meta');
  const formContainer = document.getElementById('form-container');
  const btnCancelar = document.getElementById('cancelar-meta');
  const btnCancelarAtualizacao = document.getElementById('cancelar-atualizacao');
  const editModal = document.getElementById('edit-modal');
  const closeModal = document.querySelector('.close');
  
  // Funções para o formulário de criar meta
  btnCriarMeta.addEventListener('click', function() {
    formContainer.style.display = 'flex';
  });
  
  btnCancelar.addEventListener('click', function() {
    formContainer.style.display = 'none';
  });

  if (formContainer) {
    window.addEventListener('click', (e) => {
      if (e.target === formContainer) {
        formContainer.style.display = 'none';
      }
    });
  }
  
  // Funções para o modal de edição
  // Fechar o modal quando clicar no X
  if (btnCancelarAtualizacao) {
    btnCancelarAtualizacao.addEventListener('click', function() {
      editModal.style.display = 'none';
    });
  }
  
  // Fechar o modal quando clicar fora dele
  window.addEventListener('click', function(event) {
    if (event.target === editModal) {
      editModal.style.display = 'none';
    }
  });
  
  // Abrir o modal de edição quando clicar no botão editar
  const editButtons = document.querySelectorAll('.edit');
  editButtons.forEach(button => {
    button.addEventListener('click', function() {
      // Pegar os dados do botão
      const metaId = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const description = this.getAttribute('data-description');
      const targetDate = this.getAttribute('data-target-date');
      const completed = this.getAttribute('data-completed') === '1';
      
      // Preencher o formulário do modal
      document.getElementById('update_meta_id').value = metaId;
      document.getElementById('edit_title').value = title;
      document.getElementById('edit_description').value = description;
      document.getElementById('edit_target_date').value = targetDate;
      document.getElementById('edit_completed').checked = completed;
      
      // Mostrar o modal
      editModal.style.display = 'flex';
    });
  });
  //função de ver mais
  const verMais = document.querySelector('.ver-mais')
  verMais.addEventListener('click', () => {
    const desc = document.querySelector('.card-description');
    const expanded = desc.classList.toggle('expanded');
    verMais.textContent = expanded ? 'Ver menos' : 'Ver mais';
  });
  //compara tamanho para ver mais
  window.onload = function () {
    const listaCards = document.querySelectorAll('.card-header');
    listaCards.forEach(function(card){
      const desc = card.querySelector('.card-description');
      const verMaisCard = card.querySelector('.ver-mais');

      if (!verMaisCard || !desc) return;
      
      if(desc.scrollHeight == 34) {
        card.style.setProperty('height', card.scrollHeight  +  'px');
        verMaisCard.style.display = 'none';
      }
    });
  }
  
});