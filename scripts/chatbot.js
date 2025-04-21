
  class ChatInterface {
    constructor() {
      this.elements = {
        chatBox: document.getElementById('chat-box'),
        userInput: document.getElementById('user-input'),
        emptyMessage: document.getElementById('empty-message')
      };
      this.apiEndpoint = 'astra_ai.php';
      this.typingSpeed = 30;
      this.maxHistoryLength = 10;
      
      this.initEventListeners();
      this.loadChatHistory();
    }
    initEventListeners() {
      this.elements.userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          this.sendMessage();
        }
      });
      
      const sendButton = document.getElementById('send-button');
      if (sendButton) {
        sendButton.addEventListener('click', () => this.sendMessage());
      }
    }
    
    sendMessage() {
      const userInput = this.elements.userInput.value.trim();
      
      if (!userInput) return;
      
      this.hideEmptyStateMessage();
      this.displayMessage('user', userInput);
      this.saveMessageToHistory('user', userInput);
      this.clearUserInput();
      
      this.fetchBotResponse(userInput)
        .then(responseText => {
          this.displayMessage('bot', responseText, true); 
          this.saveMessageToHistory('bot', responseText);
        })
        .catch(() => {
          const errorText = 'Falha ao buscar resposta.';
          this.displayMessage('bot', errorText, true);
          this.saveMessageToHistory('bot', errorText);
        });
      
      this.scrollToBottom();
    }
    
    /**
     * Fetch response from the chatbot API
     * @param {string} userMessage 
     * @returns {Promise<string>} 
     */
    async fetchBotResponse(userMessage) {
      const response = await fetch(this.apiEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: userMessage })
      });
      
      const data = await response.json();
      
      if (data.error) {
        throw new Error(data.error);
      }
      
      return data.response;
    }
    
    /**
     * Display a message in the chat interface
     * @param {string} role - 'user' or 'bot'
     * @param {string} content - Message content
     * @param {boolean} useTypingEffect - Whether to animate the text
     */
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
    
    /**
     * Animate text appearing character by character
     * @param {HTMLElement} element - Target element to type into
     * @param {string} text - Full text content to type
     */
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
    
    /**
     * Save message to local storage history
     * @param {string} role - 'user' or 'bot'
     * @param {string} content - Message content
     */
    saveMessageToHistory(role, content) {
      let history = this.getChatHistory();
      
      history.push({
        role,
        content,
        timestamp: new Date().toISOString()
      });
      
      if (history.length > this.maxHistoryLength) {
        history = history.slice(-this.maxHistoryLength);
      }
      
      localStorage.setItem('chatHistory', JSON.stringify(history));
    }
    
    /**
     * Retrieve chat history from local storage
     * @returns {Array} Chat history array
     */
    getChatHistory() {
      return JSON.parse(localStorage.getItem('chatHistory')) || [];
    }
    
    loadChatHistory() {
      const history = this.getChatHistory();
      
      if (history.length > 0) {
        this.hideEmptyStateMessage();
        
        history.forEach(message => {
          this.displayMessage(message.role, message.content);
        });
        
        this.scrollToBottom();
      }
    }
    
    hideEmptyStateMessage() {
      if (this.elements.emptyMessage) {
        this.elements.emptyMessage.style.display = 'none';
      }
    }
    
    clearUserInput() {
      this.elements.userInput.value = '';
    }
    
    scrollToBottom() {
      this.elements.chatBox.scrollTop = this.elements.chatBox.scrollHeight;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    new ChatInterface();
  });