<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="AstraAI ChatBot - Assistente virtual para apoio contra vícios">
    <title>AstraAI ChatBot</title>
    
    <!-- Stylesheets -->
    <?php
        include('./componentes/header.php')
    ?>
    <link rel="stylesheet" href="./Style/chatbot.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>
    <div class="content">
        <aside>
            <div id="clear-history">
                <button type="button" id="clear-history-btn" class="text-button"> <span>+</span> Nova Conversa</button>
            </div>
        </aside>
        <main id="chat-container">    
            <section id="chat-box" aria-live="polite">
                <div id="empty-message" class="welcome-message">
                    Aqui para apoiar você na jornada contra o vício. Conte-me como está se sentindo hoje.
                </div>

            </section>
            
            <footer class="input-container">
                <input 
                    type="text" 
                    id="user-input" 
                    placeholder="Comece digitando 'Hoje estou...'" 
                    aria-label="Digite sua mensagem"
                >
                <button 
                    type="button" 
                    id="send-button" 
                    aria-label="Enviar mensagem"
                >
                    <img src="./svgs/upArrow.svg" alt="" class="btn_enviar">
                </button>
            </footer>
        </main>
    </div>

    <!-- Scripts -->
    <script src="./Script/chatbot.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize clear history button functionality
            document.getElementById('clear-history-btn').addEventListener('click', function() {
                clearChatHistory();
            });
            
            // Function to clear chat history
            function clearChatHistory() {
                // Clear localStorage
                localStorage.removeItem('chatHistory');
                
                // Clear PHP session
                fetch("clear_session.php")
                    .then(response => response.json())
                    .then(data => {
                        console.log("Session cleared successfully");
                    })
                    .catch(error => {
                        console.error("Error clearing session:", error);
                    });
                
                // Reset UI - remova a parte que adiciona outro botão de limpar histórico
                const chatBox = document.getElementById('chat-box');
                chatBox.innerHTML = `
                    <div id="empty-message" class="welcome-message">
                        Aqui para apoiar você na jornada contra o vício. Conte-me como está se sentindo hoje.
                    </div>
                `; 
                // Note que removi a div com o botão "Limpar histórico" daqui
            }
        });
    </script>
</body>
</html>