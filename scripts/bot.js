class ChatInterface {
  constructor() {
    this.elements = {
      chatBox: document.getElementById('chat-box'),
      userInput: document.getElementById('user-input'),
      emptyMessage: document.getElementById('empty-message'),
      conversationList: document.getElementById('conversation-list')
    };
    this.apiEndpoint = 'gemini.php';
    this.typingSpeed = 30; 
    this.currentConversationId = null;
    this.isListening = false;

    this.checkAuthAndInitialize();
  }

  async checkAuthAndInitialize() {
    try {
      const response = await fetch('bot.php', {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        },
        cache: 'no-store'
      });
      
      if (!response.ok) {
        throw new Error(`Erro HTTP! Status: ${response.status}`);
      }
      
      const data = await response.json();
      
      if (!data.success) {
        alert('Você precisa estar logado para usar o chat.');
        window.location.href = 'index.php';
        return;
      }
      
      this.userId = data.user_id;
      
      if (!this.userId) {
        alert('Erro ao obter informações do usuário.');
        window.location.href = 'index.php';
        return;
      }
      
      this.initEventListeners();
      this.initSpeechRecognition(); // Inicializar reconhecimento de voz
      await this.loadConversations();
      
      if (data.conversations && data.conversations.length > 0) {
        this.loadSpecificConversation(data.conversations[0].id_conversa);
      } else {
        this.startNewConversation();
      }
    } catch (error) {
      console.error('Falha na verificação de autenticação:', error);
      window.location.href = 'index.php';
    }
  }

  // Substitua o método initSpeechRecognition() existente pelo seguinte:
  toggleSpeechRecognition() {
    if (!this.recognition) {
      console.error('Reconhecimento de voz não inicializado');
      return;
    }
  
    try {
      if (this.isListening) {
        this.recognition.stop();
        this.isListening = false;
      } else {
        // Reiniciar o objeto de reconhecimento para evitar problemas de estado
        this.recognition.abort(); // Força a parada de qualquer sessão pendente
        
        // Pequeno atraso para garantir que a sessão anterior foi encerrada
        setTimeout(() => {
          try {
            this.recognition.start();
            this.isListening = true;
            this.toggleMicrophoneState(true);
          } catch (error) {
            console.error('Erro ao iniciar reconhecimento:', error);
            this.toggleMicrophoneState(false);
            
            // Verifica se o erro é de permissão e notifica o usuário
            if (error.name === 'NotAllowedError') {
              alert('Permissão para usar o microfone negada. Por favor, permita o acesso ao microfone nas configurações do seu navegador.');
            }
          }
        }, 200);
      }
    } catch (error) {
      console.error('Erro ao alternar o reconhecimento de voz:', error);
      this.isListening = false;
      this.toggleMicrophoneState(false);
    }
  }
  
  // Modificações no método initSpeechRecognition
  initSpeechRecognition() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
      console.warn('Reconhecimento de voz não suportado neste navegador');
      return;
    }
  
    // Criar uma nova instância do objeto de reconhecimento
    this.recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
    this.recognition.lang = 'pt-BR';
    this.recognition.continuous = false;
    this.recognition.interimResults = false;
    
    this.recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      console.log('Texto reconhecido:', transcript);
      this.elements.userInput.value = transcript;
      
      // Opcional: enviar automaticamente após reconhecimento
      // this.sendMessage();
    };
    
    this.recognition.onerror = (event) => {
      console.error('Erro no reconhecimento de voz:', event.error);
      
      // Tratamento específico para erros comuns
      if (event.error === 'network') {
        console.warn('Problema de rede durante o reconhecimento de voz. Verifique sua conexão.');
      } else if (event.error === 'not-allowed') {
        console.warn('Permissão para o microfone negada pelo usuário ou sistema.');
      }
      
      this.isListening = false;
      this.toggleMicrophoneState(false);
    };
    
    this.recognition.onend = () => {
      console.log('Reconhecimento de voz encerrado');
      this.isListening = false;
      this.toggleMicrophoneState(false);
    };
    
    // Criando elemento do microfone
    this.micButton = document.createElement('button');
    this.micButton.id = 'mic-button';
    this.micButton.type = 'button';
    this.micButton.className = 'mic-button';
    this.micButton.innerHTML = '<i class="fas fa-microphone"></i>';
    this.micButton.title = 'Ativar reconhecimento de voz';
    this.micButton.setAttribute('aria-label', 'Ativar reconhecimento de voz');
    
    // Procurando o contêiner de entrada e inserindo o botão antes do botão de enviar
    const inputContainer = document.querySelector('.input-container');
    const sendButton = document.getElementById('send-button');
    
    if (inputContainer && sendButton) {
      inputContainer.insertBefore(this.micButton, sendButton);
      
      // Adicionar evento de clique
      this.micButton.addEventListener('click', () => {
        console.log('Botão de microfone clicado. Estado atual:', this.isListening ? 'ouvindo' : 'não ouvindo');
        this.toggleSpeechRecognition();
      });
    } else {
      console.error('Contêiner de entrada ou botão de enviar não encontrado no DOM');
    }
  }

  toggleMicrophoneState(isActive) {
    this.isListening = isActive;
    
    if (isActive) {
      this.micButton.classList.add('listening');
      this.micButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
      this.micButton.title = 'Parar reconhecimento de voz';
    } else {
      this.micButton.classList.remove('listening');
      this.micButton.innerHTML = '<i class="fas fa-microphone"></i>';
      this.micButton.title = 'Ativar reconhecimento de voz';
    }
  }

  generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      const r = Math.random() * 16 | 0,
            v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  initEventListeners() {
    this.elements.userInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') this.sendMessage();
    });

    const sendButton = document.getElementById('send-button');
    if (sendButton) {
      sendButton.addEventListener('click', () => this.sendMessage());
    }

    const clearHistoryBtn = document.getElementById('clear-history-btn');
    if (clearHistoryBtn) {
      clearHistoryBtn.addEventListener('click', () => this.startNewConversation());
    }
  }

  async loadConversations() {
    try {
      const response = await fetch('bot.php', {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        },
        cache: 'no-store'
      });
      
      if (!response.ok) throw new Error(`Erro HTTP! Status: ${response.status}`);
      
      const data = await response.json();
      if (!data.success) throw new Error(data.message);

      const conversationList = this.elements.conversationList;
      if (!conversationList) return;
      
      conversationList.innerHTML = ''; 

      if (!data.conversations || data.conversations.length === 0) {
        const emptyItem = document.createElement('li');
        emptyItem.textContent = 'Nenhuma conversa encontrada';
        emptyItem.classList.add('empty-list');
        conversationList.appendChild(emptyItem);
        
        this.showEmptyMessage();
        return;
      }

      data.conversations.forEach(conversation => {
        const listItem = document.createElement('li');
        listItem.className = 'conversation-item';
        
        const textContainer = document.createElement('div');
        textContainer.className = 'conversation-text';
        
        const date = new Date(conversation.last_message_time);
        textContainer.textContent = `Conversa ${date.toLocaleDateString()}`;
        
        const removeButton = document.createElement('button');
        removeButton.className = 'remove-conversation-btn';
        removeButton.innerHTML = '<i class="fas fa-trash"></i>';
        removeButton.title = 'Remover conversa';
        
        removeButton.addEventListener('click', (e) => {
          e.stopPropagation(); 
          
          if (confirm('Tem certeza que deseja excluir esta conversa?')) {
            this.deleteConversation(conversation.id_conversa);
          }
        });
        
        listItem.dataset.idConversa = conversation.id_conversa;
        
        if (this.currentConversationId === conversation.id_conversa) {
          listItem.classList.add('active-conversation');
        }

        listItem.addEventListener('click', () => {
          document.querySelectorAll('#conversation-list li').forEach(item => {
            item.classList.remove('active-conversation');
          });
          listItem.classList.add('active-conversation');
          this.loadSpecificConversation(conversation.id_conversa);
        });

        listItem.appendChild(textContainer);
        listItem.appendChild(removeButton);
        conversationList.appendChild(listItem);
      });
      
      this.checkEmptyState();
    } catch (error) {
      console.error('Erro ao carregar conversas:', error);
      
      const conversationList = this.elements.conversationList;
      if (conversationList) {
        conversationList.innerHTML = '';
        const errorItem = document.createElement('li');
        errorItem.textContent = 'Erro ao carregar conversas';
        errorItem.classList.add('error-list');
        conversationList.appendChild(errorItem);
      }
    }
  }

  async loadSpecificConversation(idConversa) {
    try {
      this.currentConversationId = idConversa;
      
      const response = await fetch(`api/get_conversation.php?id_conversa=${idConversa}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        },
        cache: 'no-store'
      });
      
      if (!response.ok) throw new Error(`Erro HTTP! Status: ${response.status}`);
      
      const data = await response.json();
      if (!data.success) throw new Error(data.message);
  
      this.clearChat();
  
      if (data.messages && data.messages.length > 0) {
        data.messages.forEach(message => {
          if (message.type && message.content) {
            this.displayMessage(message.type, message.content, false);
          }
        });
        
        this.hideEmptyStateMessage();
      } else {
        this.showEmptyMessage();
      }
  
      this.scrollToBottom();
      
      document.querySelectorAll('#conversation-list li').forEach(item => {
        item.classList.toggle('active-conversation', item.dataset.idConversa === idConversa);
      });
    } catch (error) {
      console.error('Erro ao carregar conversa específica:', error);
      
      this.elements.chatBox.innerHTML = '';
      const errorElement = document.createElement('div');
      errorElement.className = 'error-message';
      errorElement.textContent = 'Erro ao carregar conversa. Tente novamente mais tarde.';
      this.elements.chatBox.appendChild(errorElement);
    }
  }

  clearChat() {
    const emptyMessageElement = this.elements.emptyMessage;
    this.elements.chatBox.innerHTML = '';
    
    if (emptyMessageElement) {
      this.elements.chatBox.appendChild(emptyMessageElement);
      this.elements.emptyMessage = emptyMessageElement;
    } else {
      const newEmptyMessage = document.createElement('div');
      newEmptyMessage.id = 'empty-message';
      newEmptyMessage.className = 'welcome-message';
      newEmptyMessage.textContent = 'Aqui para apoiar você na jornada contra o vício. Conte-me como está se sentindo hoje.';
      newEmptyMessage.style.display = 'none';
      
      this.elements.chatBox.appendChild(newEmptyMessage);
      this.elements.emptyMessage = newEmptyMessage;
    }
  }

  startNewConversation() {
    const newConversationId = this.generateUUID();
    
    this.createNewConversation(newConversationId).then(success => {
      if (success) {
        this.currentConversationId = newConversationId;
        this.clearChat();
        
        this.showEmptyMessage();
        
        document.querySelectorAll('#conversation-list li').forEach(item => {
          item.classList.remove('active-conversation');
        });
        
        this.elements.userInput.focus();
        this.loadConversations();
      } else {
        alert('Não foi possível criar uma nova conversa. Por favor, tente novamente.');
      }
    });
  }
  
  checkEmptyState() {
    const hasActiveConversation = this.currentConversationId !== null;
    
    const messageElements = Array.from(this.elements.chatBox.children).filter(
      el => el.id !== 'empty-message' && 
           !el.classList.contains('welcome-message') &&
           !el.classList.contains('error-message')
    );
    
    const hasMessages = messageElements.length > 0;
    
    if (!hasActiveConversation || (hasActiveConversation && !hasMessages)) {
      this.showEmptyMessage();
    } else {
      this.hideEmptyStateMessage();
    }
  }
  
  showEmptyMessage() {
    if (this.elements.emptyMessage) {
      this.elements.emptyMessage.style.display = 'block';
    }
  }
  
  hideEmptyStateMessage() {
    if (this.elements.emptyMessage) {
      this.elements.emptyMessage.style.display = 'none';
    }
  }
  
  async createNewConversation(conversationId) {
    try {
      const response = await fetch('api/create_conversation.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          user_id: this.userId,
          id_conversa: conversationId
        })
      });
      
      if (!response.ok) throw new Error(`Erro HTTP! Status: ${response.status}`);
      
      const data = await response.json();
      return data.success;
    } catch (error) {
      console.error('Erro ao criar nova conversa:', error);
      return false;
    }
  }

  deleteConversation(conversationId) {
    fetch('api/delete_conversation.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        id_conversa: conversationId,
        user_id: this.userId
      })
    })
    .then(response => {
      if (!response.ok) throw new Error(`Erro HTTP! Status: ${response.status}`);
      return response.json();
    })
    .then(data => {
      if (data.success) {
        const listItem = document.querySelector(`#conversation-list li[data-id-conversa="${conversationId}"]`);
        if (listItem) {
          listItem.remove();
        }
        
        this.loadConversations();
        
        if (this.currentConversationId === conversationId) {
          this.startNewConversation();
        }
      } else {
        alert('Erro ao excluir conversa: ' + (data.message || 'Erro desconhecido'));
      }
    })
    .catch(error => {
      console.error('Erro ao excluir conversa:', error);
      alert('Falha ao excluir conversa. Por favor, tente novamente.');
    });
  }

  sendMessage() {
    const userInput = this.elements.userInput.value.trim();
  
    if (!userInput || !this.currentConversationId) return;
  
    const messageId = `msg_${Date.now()}`;
    
    const sendButton = document.getElementById('send-button');
    if (sendButton) {
      sendButton.disabled = true;
      setTimeout(() => { sendButton.disabled = false; }, 1000); 
    }
  
    this.hideEmptyStateMessage();
    
    this.displayMessage('user', userInput);
    this.clearUserInput();
  
    if (!window.sentMessages) window.sentMessages = new Set();
    
    const userMessageHash = `user_${userInput.substring(0, 50)}_${this.currentConversationId}`;
    if (!window.sentMessages.has(userMessageHash)) {
      window.sentMessages.add(userMessageHash);
      
      this.saveMessageToServer('user', userInput);
    }
  
    this.fetchBotResponse(userInput)
      .then(responseText => {
        this.displayMessage('bot', responseText, true);
        
        const botMessageHash = `bot_${responseText.substring(0, 50)}_${this.currentConversationId}`;
        if (!window.sentMessages.has(botMessageHash)) {
          window.sentMessages.add(botMessageHash);
          this.saveMessageToServer('bot', responseText);
        }
      })
      .catch((error) => {
        console.error('Erro ao buscar resposta do bot:', error);
        const errorText = 'Falha ao buscar resposta. Por favor, tente novamente mais tarde.';
        this.displayMessage('bot', errorText, true);
        
        const errorHash = `error_${errorText}_${this.currentConversationId}`;
        if (!window.sentMessages.has(errorHash)) {
          window.sentMessages.add(errorHash);
          this.saveMessageToServer('bot', errorText);
        }
      });
  
    this.scrollToBottom();
  }
  
  async fetchBotResponse(userMessage) {
    try {
      const response = await fetch(this.apiEndpoint, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ 
          message: userMessage,
          conversation_id: this.currentConversationId
        })
      });

      if (!response.ok) throw new Error(`Erro HTTP! Status: ${response.status}`);
      
      const data = await response.json();
      if (data.error) throw new Error(data.message || 'Erro desconhecido');

      return data.response;
    } catch (error) {
      console.error('Detalhes do erro:', error);
      throw error;
    }
  }

  displayMessage(role, content, useTypingEffect = false) {
    const messageElement = document.createElement('div');
    messageElement.className = role === 'user' ? 'user-message' : 'bot-message';
    this.elements.chatBox.appendChild(messageElement);

    if (useTypingEffect) {
      this.animateTyping(messageElement, content);
    } else {
      messageElement.textContent = content;
    }

    this.scrollToBottom();
  }

  animateTyping(element, text) {
    let index = 0;
    element.textContent = '';

    const type = () => {
      if (index < text.length) {
        element.textContent += text.charAt(index);
        index++;
        setTimeout(type, this.typingSpeed);
        this.scrollToBottom();
      } else {
        element.style.animation = 'none';
      }
    };

    type();
  }

  async saveMessageToServer(role, content) {
    if (!this.currentConversationId) {
      console.error('Nenhuma conversa ativa');
      return;
    }
    
    try {
      const messageData = {
        user_id: this.userId,
        id_conversa: this.currentConversationId,
        message_type: role,
        message_content: content,
        timestamp: new Date().toISOString()
      };

      const response = await fetch('api/save_message.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(messageData)
      });
      
      if (!response.ok) throw new Error(`Erro HTTP: ${response.status}`);
      
      const data = await response.json();
      if (!data.success) throw new Error(data.message || 'Erro ao salvar mensagem');
      
      if (role === 'bot') {
        this.loadConversations();
      }
    } catch (error) {
      console.error('Erro ao salvar mensagem:', error);
    }
  }

  clearUserInput() {
    this.elements.userInput.value = '';
    this.elements.userInput.focus();
  }

  scrollToBottom() {
    this.elements.chatBox.scrollTop = this.elements.chatBox.scrollHeight;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  new ChatInterface();
});