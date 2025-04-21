// Variável global para rastrear se o script já foi inicializado
if (typeof window.alertSystemInitialized === 'undefined') {
    window.alertSystemInitialized = false;

    document.addEventListener('DOMContentLoaded', function() {
        // Evitar múltiplas inicializações
        if (window.alertSystemInitialized) return;
        window.alertSystemInitialized = true;
        
        console.log('Inicializando sistema de alertas...');
        
        // Remover qualquer container de alertas existente para evitar duplicações
        let existingContainer = document.getElementById('alertContainer');
        if (existingContainer) {
            existingContainer.parentNode.removeChild(existingContainer);
        }
        
        // Criar novo container de alertas
        let alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
        
        // Armazenar últimas mensagens para evitar duplicatas
        let lastAlerts = {};
        
        // Função para mostrar alertas
        window.showAlert = function(type, message, duration = 6000) {
            // Evitar alertas duplicados em sequência rápida
            const alertKey = `${type}:${message}`;
            const now = Date.now();
            
            if (lastAlerts[alertKey] && (now - lastAlerts[alertKey]) < 1000) {
                console.log('Alerta duplicado ignorado:', alertKey);
                return null;
            }
            
            lastAlerts[alertKey] = now;
            
            // Garantir que o container está visível
            alertContainer.classList.add('show');
            
            // Criar o box do alerta
            const alertBox = document.createElement('div');
            alertBox.className = `alert-box alert-${type}`;
            
            // Definir o ícone baseado no tipo de alerta
            let icon = '';
            switch(type) {
                case 'success':
                    icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
                    break;
                case 'error':
                    icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
                    break;
                case 'warning':
                    icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
                    break;
                case 'info':
                    icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
                    break;
                default:
                    icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
            }
            
            // Montar o HTML do alerta
            alertBox.innerHTML = `
                <div class="alert-content">
                    <span class="alert-icon">${icon}</span>
                    <span class="alert-message">${message}</span>
                </div>
                <button class="close-alert">&times;</button>
            `;
            
            // Adicionar ao container
            alertContainer.appendChild(alertBox);
            
            // Efeito de entrada
            setTimeout(() => {
                alertBox.classList.add('show');
            }, 10);
            
            // Configurar botão de fechar
            const closeButton = alertBox.querySelector('.close-alert');
            closeButton.addEventListener('click', () => {
                removeAlert(alertBox);
            });
            
            // Auto-fechar após duração especificada
            if (duration > 0) {
                setTimeout(() => {
                    removeAlert(alertBox);
                }, duration);
            }
            
            return alertBox;
        };
        
        // Função para remover alertas
        window.removeAlert = function(alertBox) {
            alertBox.classList.remove('show');
            
            setTimeout(() => {
                if (alertBox && alertBox.parentNode) {
                    alertBox.parentNode.removeChild(alertBox);
                }
                
                // Se não houver mais alertas, esconder o container
                if (alertContainer && alertContainer.children.length === 0) {
                    alertContainer.classList.remove('show');
                }
            }, 300);
        };
        
        // Processar alertas de URL - apenas uma vez durante carregamento
        function processUrlAlerts() {
            const urlParams = new URLSearchParams(window.location.search);
            const alertType = urlParams.get('alert');
            const alertMessage = urlParams.get('msg');
            
            if (alertType && alertMessage) {
                showAlert(alertType, decodeURIComponent(alertMessage));
                
                // Limpar parâmetros da URL
                const newUrl = window.location.pathname;
                window.history.pushState({path: newUrl}, '', newUrl);
                return true;
            }
            return false;
        }
        
        // Processar alertas do PHP via atributos data - apenas uma vez durante carregamento
        function processPhpAlerts() {
            const phpAlertType = document.body.getAttribute('data-alert-type');
            const phpAlertMessage = document.body.getAttribute('data-alert-message');
            
            if (phpAlertType && phpAlertMessage) {
                showAlert(phpAlertType, phpAlertMessage);
                
                // Remover atributos para evitar mostrar o mesmo alerta novamente
                document.body.removeAttribute('data-alert-type');
                document.body.removeAttribute('data-alert-message');
                
                return true;
            }
            return false;
        }
        
        // Processar alertas na inicialização
        const hasUrlAlerts = processUrlAlerts();
        if (!hasUrlAlerts) {
            processPhpAlerts();
        }
        
        console.log('Sistema de alertas inicializado com sucesso');
    });
}