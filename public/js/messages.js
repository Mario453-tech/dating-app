class MessageSystem {
    constructor() {
        this.messagesContainer = document.getElementById('messages-container');
        this.messageForm = document.getElementById('message-form');
        this.messageContent = document.getElementById('message-content');
        this.unreadCounter = document.getElementById('unread-messages-count');
        
        this.init();
    }

    init() {
        if (this.messagesContainer) {
            this.scrollToBottom();
            this.initMessageForm();
            this.initDeleteButtons();
            this.initTextareaAutoResize();
        }
        
        if (this.unreadCounter) {
            this.initUnreadCounter();
        }
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    initMessageForm() {
        if (!this.messageForm) return;

        this.messageForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const content = this.messageContent.value.trim();
            if (!content) return;

            const url = this.messageForm.dataset.sendUrl;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                this.addNewMessage(content, data);
                this.messageContent.value = '';
                this.messageContent.style.height = 'auto';
            });
        });
    }

    addNewMessage(content, data) {
        const messageHtml = `
            <div class="message mb-3 text-end">
                <div class="message-content d-inline-block p-2 rounded bg-primary text-white" style="max-width: 75%;">
                    ${content.replace(/\n/g, '<br>')}
                    <div class="message-meta small text-white-50">
                        ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        <i class="fas fa-clock" title="Wysłane"></i>
                    </div>
                </div>
                <div class="message-actions">
                    <button class="btn btn-sm btn-link text-danger delete-message" 
                            data-message-id="${data.id}"
                            title="Usuń wiadomość">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        this.messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
        this.scrollToBottom();
        this.initDeleteButtons();
    }

    initDeleteButtons() {
        document.querySelectorAll('.delete-message').forEach(button => {
            if (button.dataset.initialized) return;
            
            button.dataset.initialized = 'true';
            button.addEventListener('click', () => this.handleDelete(button));
        });
    }

    handleDelete(button) {
        if (!confirm('Czy na pewno chcesz usunąć tę wiadomość?')) return;

        const messageId = button.dataset.messageId;
        fetch(`/messages/${messageId}/delete`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.closest('.message').remove();
            } else if (data.error) {
                alert(data.error);
            }
        });
    }

    initTextareaAutoResize() {
        if (!this.messageContent) return;

        this.messageContent.addEventListener('input', () => {
            this.messageContent.style.height = 'auto';
            this.messageContent.style.height = (this.messageContent.scrollHeight) + 'px';
        });
    }

    initUnreadCounter() {
        setInterval(() => {
            fetch('/messages/unread-count')
                .then(response => response.json())
                .then(data => {
                    this.unreadCounter.textContent = data.count;
                    this.unreadCounter.style.display = data.count > 0 ? 'inline' : 'none';
                });
        }, 30000);
    }
}

// Inicjalizacja systemu wiadomości po załadowaniu strony
document.addEventListener('DOMContentLoaded', () => {
    new MessageSystem();
});
