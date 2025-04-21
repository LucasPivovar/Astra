
document.addEventListener('DOMContentLoaded', function() {
    const btnAvaliar = document.getElementById('btnAvaliar');
    const modalAvaliacao = document.getElementById('modalAvaliacao');
    const closeAvaliacao = modalAvaliacao.querySelector('.close');
    const estrelas = document.querySelectorAll('.estrela-selecao');
    const ratingInput = document.getElementById('rating');
    
    const btnVerTodas = document.getElementById('btnVerTodas');
    const modalTodasAvaliacoes = document.getElementById('modalTodasAvaliacoes');
    const closeTodasAvaliacoes = modalTodasAvaliacoes.querySelector('.close-todas');

    btnAvaliar.addEventListener('click', function() {
        modalAvaliacao.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });

    closeAvaliacao.addEventListener('click', function() {
        modalAvaliacao.style.display = 'none';
        document.body.style.overflow = 'auto'; 
    });

    btnVerTodas.addEventListener('click', function() {
        modalTodasAvaliacoes.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });

    closeTodasAvaliacoes.addEventListener('click', function() {
        modalTodasAvaliacoes.style.display = 'none';
        document.body.style.overflow = 'auto'; 
    });

    window.addEventListener('click', function(event) {
        if (event.target === modalAvaliacao) {
            modalAvaliacao.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        if (event.target === modalTodasAvaliacoes) {
            modalTodasAvaliacoes.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    estrelas.forEach(estrela => {
        estrela.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            ratingInput.value = value;
            
            estrelas.forEach(e => e.classList.remove('active'));
            
            for (let i = 0; i < estrelas.length; i++) {
                if (i < value) {
                    estrelas[i].classList.add('active');
                }
            }
        });
    
        estrela.addEventListener('mouseover', function() {
            const value = this.getAttribute('data-value');
            
            for (let i = 0; i < estrelas.length; i++) {
                if (i < value) {
                    estrelas[i].classList.add('hover');
                } else {
                    estrelas[i].classList.remove('hover');
                }
            }
        });
        
        estrela.addEventListener('mouseout', function() {
            estrelas.forEach(e => e.classList.remove('hover'));
        });
    });

    const successMessage = document.querySelector('.alert.success');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.opacity = '0';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 500);
        }, 3000);
    }
});