(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.aiChat = {
    attach: function (context) {
      const app = context.querySelector('#ai-admin-app');
      if (!app || app.dataset.initialized) return;
      app.dataset.initialized = 'true';

      const settings = drupalSettings.rareimagery_ai || {};
      const tools = settings.tools || [];
      let provider = 'claude';
      let conversation = [];
      let busy = false;

      // Build UI
      app.innerHTML = `
        <div class="ai-chat-container">
          <div class="ai-chat-header">
            <h2>AI Admin</h2>
            <div class="ai-provider-toggle">
              <button data-provider="claude" class="active">Claude</button>
              <button data-provider="xai">Grok</button>
            </div>
          </div>
          <div class="ai-chat-messages" id="ai-messages"></div>
          <div class="ai-tools-bar">${tools.map(t => '<span>' + t + '</span>').join('')}</div>
          <div class="ai-chat-input">
            <textarea id="ai-input" placeholder="Tell the AI what to do..." rows="1"></textarea>
            <button id="ai-send">Send</button>
          </div>
        </div>
      `;

      const messagesEl = app.querySelector('#ai-messages');
      const inputEl = app.querySelector('#ai-input');
      const sendBtn = app.querySelector('#ai-send');

      // Provider toggle
      app.querySelectorAll('.ai-provider-toggle button').forEach(btn => {
        btn.addEventListener('click', function () {
          app.querySelectorAll('.ai-provider-toggle button').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          provider = this.dataset.provider;
        });
      });

      function addMessage(text, type) {
        const div = document.createElement('div');
        div.className = 'ai-message ' + type;
        if (type === 'tool-call') {
          div.innerHTML = '<pre>' + escapeHtml(text) + '</pre>';
        } else {
          div.textContent = text;
        }
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return div;
      }

      function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
      }

      async function send() {
        const message = inputEl.value.trim();
        if (!message || busy) return;

        busy = true;
        sendBtn.disabled = true;
        inputEl.value = '';

        addMessage(message, 'user');
        const typingEl = addMessage('Thinking...', 'ai-typing');

        try {
          const resp = await fetch(settings.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, provider, conversation }),
          });

          const data = await resp.json();
          typingEl.remove();

          if (data.error) {
            addMessage(data.error, 'error');
          } else {
            // Show tool calls
            if (data.tool_results && data.tool_results.length) {
              data.tool_results.forEach(tr => {
                const summary = tr.tool + '(' + JSON.stringify(tr.input) + ')\n→ ' +
                  JSON.stringify(tr.output, null, 2).substring(0, 500);
                addMessage(summary, 'tool-call');
              });
            }

            // Show response
            if (data.response) {
              addMessage(data.response, 'assistant');
            }

            // Update conversation for context continuity
            if (data.messages) {
              conversation = data.messages;
            }
          }
        } catch (err) {
          typingEl.remove();
          addMessage('Error: ' + err.message, 'error');
        }

        busy = false;
        sendBtn.disabled = false;
        inputEl.focus();
      }

      sendBtn.addEventListener('click', send);
      inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          send();
        }
        // Auto-resize
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
      });

      // Welcome message
      addMessage('AI Admin ready. Using ' + tools.length + ' tools. Type a command like "show site status" or "list all users".', 'assistant');
    }
  };

})(Drupal, drupalSettings);
