document.addEventListener('DOMContentLoaded', () => {
    //Elementos do Modal de postagem
    const postModalBackground = document.getElementById('postModalBackground');
    const postModal = document.getElementById('newPostModal');
    const newPostButton = document.getElementById('newPostButton');
    const addImageBtn = document.getElementById('addImageBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');

    //Abrir Modal de Postagem
    if (newPostButton) {
        newPostButton.addEventListener('click', (e) => {
            e.preventDefault();
            openPostModal();
        });
    }
    //Fechar o Modal de postagem
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closePostModal();
        });

    }

    //Função que abre o Modal de postagem
    function openPostModal(){
        if(postModalBackground){
            postModalBackground.style.display = 'flex';
        }

        if(postModal){
            postModal.style.display = 'flex';
        }
    }

    //Função que fecha o Modal de postagem
    function closePostModal(){
        if(postModalBackground){
            postModalBackground.style.display = 'none';
        }

        if(postModal){
                postModal.style.display = 'none';
        }
    }
});