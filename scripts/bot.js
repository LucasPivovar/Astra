class ChatInterface {
  constructor() {
    this.elements = {
      chatBox: document.getElementById('chat-box'),
      userInput: document.getElementById('user-input'),
      emptyMessage: document.getElementById('empty-message'),
      conversationList: document.getElementById('conversation-list')
    };
    this.apiEndpoint = 'gemini.php';
    this.typingSpeed = 30; // milissegundos por caractere
    this.currentConversationId = null;

    // Verificar autenticação antes de inicializar
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
      
      // Obter ID do usuário da sessão
      this.userId = data.user_id;
      
      if (!this.userId) {
        alert('Erro ao obter informações do usuário.');
        window.location.href = 'index.php';
        return;
      }
      
      // Configurar a interface do chat
      this.initEventListeners();
      await this.loadConversations();
      
      // Carregar conversa existente ou iniciar nova
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

  generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      const r = Math.random() * 16 | 0,
            v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
  }

  initEventListeners() {
    // Enviar mensagem ao pressionar Enter
    this.elements.userInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') this.sendMessage();
    });

    // Enviar mensagem ao clicar no botão
    const sendButton = document.getElementById('send-button');
    if (sendButton) {
      sendButton.addEventListener('click', () => this.sendMessage());
    }

    // Iniciar nova conversa
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
      
      conversationList.innerHTML = ''; // Limpar lista atual

      // Exibir mensagem se não houver conversas
      if (!data.conversations || data.conversations.length === 0) {
        const emptyItem = document.createElement('li');
        emptyItem.textContent = 'Nenhuma conversa encontrada';
        emptyItem.classList.add('empty-list');
        conversationList.appendChild(emptyItem);
        
        // Exibir mensagem vazia se não há conversas
        this.showEmptyMessage();
        return;
      }

      // Criar itens para cada conversa
      data.conversations.forEach(conversation => {
        const listItem = document.createElement('li');
        const date = new Date(conversation.last_message_time);
        listItem.textContent = `Conversa ${date.toLocaleDateString()}`;
        listItem.dataset.idConversa = conversation.id_conversa;
        
        // Destacar conversa atual
        if (this.currentConversationId === conversation.id_conversa) {
          listItem.classList.add('active-conversation');
        }

        // Adicionar evento de clique
        listItem.addEventListener('click', () => {
          document.querySelectorAll('#conversation-list li').forEach(item => {
            item.classList.remove('active-conversation');
          });
          listItem.classList.add('active-conversation');
          this.loadSpecificConversation(conversation.id_conversa);
        });

        conversationList.appendChild(listItem);
      });
      
      // Verificar se deve mostrar mensagem vazia
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
      // Atualizar ID da conversa atual
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
  
      // Limpar chat atual
      this.clearChat();
  
      // Exibir mensagens da conversa
      if (data.messages && data.messages.length > 0) {
        data.messages.forEach(message => {
          // Usar o novo formato de mensagens
          if (message.type && message.content) {
            this.displayMessage(message.type, message.content, false);
          }
        });
        
        // Ocultar mensagem vazia pois há mensagens
        this.hideEmptyStateMessage();
      } else {
        // Mostrar estado vazio se não houver mensagens
        this.showEmptyMessage();
      }
  
      this.scrollToBottom();
      
      // Atualizar UI para refletir conversa atual
      document.querySelectorAll('#conversation-list li').forEach(item => {
        item.classList.toggle('active-conversation', item.dataset.idConversa === idConversa);
      });
    } catch (error) {
      console.error('Erro ao carregar conversa específica:', error);
      
      // Mostrar mensagem de erro no chat
      this.elements.chatBox.innerHTML = '';
      const errorElement = document.createElement('div');
      errorElement.className = 'error-message';
      errorElement.textContent = 'Erro ao carregar conversa. Tente novamente mais tarde.';
      this.elements.chatBox.appendChild(errorElement);
    }
  }

  clearChat() {
    this.elements.chatBox.innerHTML = '';
  }

  startNewConversation() {
    // Gerar novo ID de conversa
    const newConversationId = this.generateUUID();
    
    // Criar nova conversa no banco
    this.createNewConversation(newConversationId).then(success => {
      if (success) {
        this.currentConversationId = newConversationId;
        this.clearChat();
        
        // Mostrar mensagem de boas-vindas
        this.showEmptyMessage();
        
        // Remover seleção de todos os itens da lista
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
  
  // Novo método para verificar se deve mostrar mensagem vazia
  checkEmptyState() {
    const hasActiveConversation = document.querySelectorAll('#conversation-list li.active-conversation').length > 0;
    const hasMessages = this.elements.chatBox.children.length > 0;
    
    if (!hasActiveConversation || (hasActiveConversation && !hasMessages)) {
      this.showEmptyMessage();
    } else {
      this.hideEmptyStateMessage();
    }
  }
  
  // Mostrar mensagem vazia
  showEmptyMessage() {
    if (this.elements.emptyMessage) {
      this.elements.emptyMessage.style.display = 'block';
    }
  }
  
  // Ocultar mensagem vazia
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

  sendMessage() {
    const userInput = this.elements.userInput.value.trim();
  
    if (!userInput || !this.currentConversationId) return;
  
    // Adicionar um ID único para esta mensagem
    const messageId = `msg_${Date.now()}`;
    
    // Desabilitar o botão de envio temporariamente para evitar duplos cliques
    const sendButton = document.getElementById('send-button');
    if (sendButton) {
      sendButton.disabled = true;
      setTimeout(() => { sendButton.disabled = false; }, 1000); // Reabilitar após 1 segundo
    }
  
    // Ocultar mensagem de boas-vindas
    this.hideEmptyStateMessage();
    
    // Exibir mensagem do usuário imediatamente
    this.displayMessage('user', userInput);
    this.clearUserInput();
  
    // Inicializar o conjunto de mensagens enviadas se ainda não existir
    if (!window.sentMessages) window.sentMessages = new Set();
    
    // Verificar se esta mensagem específica já foi salva
    const userMessageHash = `user_${userInput.substring(0, 50)}_${this.currentConversationId}`;
    if (!window.sentMessages.has(userMessageHash)) {
      window.sentMessages.add(userMessageHash);
      
      // Salvar mensagem do usuário
      this.saveMessageToServer('user', userInput);
    }
  
    // Obter resposta da IA
    this.fetchBotResponse(userInput)
      .then(responseText => {
        // Exibir resposta do bot com efeito de digitação
        this.displayMessage('bot', responseText, true);
        
        // Criar ID único para a resposta do bot
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
        
        // Também salvar mensagem de erro com verificação
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
      
      // Recarregar lista de conversas após respostas do bot
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

// Inicializar a interface de chat quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
  new ChatInterface();
});