import { useState, useRef, useEffect } from "react";

const SYSTEM_PROMPT = `You are the RareImagery Next.js Architect — an elite AI frontend engineer specialized in building the RareImagery X Creator Marketplace.

ABOUT THE PROJECT:
- RareImagery is a marketplace where X (Twitter) creators get their own storefront at creatorname.rareimagery.net
- Grok AI auto-pulls each creator's PFP, banner, top posts & top 8 followers to populate their store
- Backend: Drupal 10.3 + PostgreSQL 16 on Hostinger Ubuntu VPS
- Frontend: Next.js 14 (App Router) with Tailwind CSS
- Domain: rareimagery.net

YOUR JOB:
When the user describes a page, component, or feature, generate complete, production-ready Next.js 14 code.

CODE RULES:
- Always use Next.js 14 App Router conventions (app/ directory, page.tsx, layout.tsx)
- Use Tailwind CSS for all styling
- Use TypeScript
- Use 'use client' directive when needed for interactive components
- Import from next/image, next/link, next/font as appropriate
- For API calls to Grok/xAI, use the xAI API: fetch('https://api.x.ai/v1/chat/completions') with model 'grok-3'
- Environment variables: XAI_API_KEY, NEXT_PUBLIC_BASE_DOMAIN=rareimagery.net, DATABASE_URL
- Creator subdomains are handled via Next.js middleware reading the hostname
- Make the design dark, premium, X/Twitter-native aesthetic — deep blacks, sharp whites, electric accent colors

RESPONSE FORMAT:
1. Brief description of what you're building (2-3 sentences)
2. The complete code block(s) with filename headers like: \`\`\`tsx // app/page.tsx
3. Any setup notes (env vars needed, packages to install, etc.)

Always write COMPLETE, copy-paste ready code. Never truncate. Never use placeholder comments like "// add logic here".`;

const WELCOME_MESSAGE = {
  role: "assistant",
  content: `# RareImagery Next.js Architect 🏗️

I'm your AI frontend builder for the **RareImagery X Creator Marketplace**.

Tell me what to build and I'll generate complete, production-ready Next.js 14 code instantly.

**Some things to try:**
- *"Build the creator storefront page that shows their X profile, top posts, and products"*
- *"Create the marketplace homepage with featured creators and a search bar"*  
- *"Build the Grok AI auto-import button that fetches a creator's X profile data"*
- *"Create the product listing component with buy now + tip the creator buttons"*
- *"Build the wildcard subdomain middleware for creatorname.rareimagery.net routing"*

What do you want to build first?`,
};

function CodeBlock({ code, filename }) {
  const [copied, setCopied] = useState(false);
  const copy = () => {
    navigator.clipboard.writeText(code);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };
  return (
    <div className="code-block">
      <div className="code-header">
        <span className="filename">{filename || "code"}</span>
        <button onClick={copy} className="copy-btn">
          {copied ? "✓ Copied" : "Copy"}
        </button>
      </div>
      <pre className="code-body"><code>{code}</code></pre>
    </div>
  );
}

function MessageContent({ content }) {
  const parts = [];
  const codeBlockRegex = /```(\w+)?\s*(?:\/\/\s*([^\n]+)\n)?([\s\S]*?)```/g;
  let lastIndex = 0;
  let match;

  while ((match = codeBlockRegex.exec(content)) !== null) {
    if (match.index > lastIndex) {
      parts.push({ type: "text", content: content.slice(lastIndex, match.index) });
    }
    parts.push({
      type: "code",
      lang: match[1] || "tsx",
      filename: match[2] || null,
      code: match[3].trim(),
    });
    lastIndex = match.index + match[0].length;
  }
  if (lastIndex < content.length) {
    parts.push({ type: "text", content: content.slice(lastIndex) });
  }

  return (
    <div className="message-content">
      {parts.map((part, i) =>
        part.type === "code" ? (
          <CodeBlock key={i} code={part.code} filename={part.filename || `${part.lang} snippet`} />
        ) : (
          <div key={i} className="prose-text" dangerouslySetInnerHTML={{
            __html: part.content
              .replace(/^### (.+)$/gm, '<h3>$1</h3>')
              .replace(/^## (.+)$/gm, '<h2>$1</h2>')
              .replace(/^# (.+)$/gm, '<h1>$1</h1>')
              .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
              .replace(/\*(.+?)\*/g, '<em>$1</em>')
              .replace(/`(.+?)`/g, '<code>$1</code>')
              .replace(/^- (.+)$/gm, '<li>$1</li>')
              .replace(/\n\n/g, '</p><p>')
              .replace(/^(?!<[h|l|p])/gm, '')
          }} />
        )
      )}
    </div>
  );
}

export default function RareImageryBuilder() {
  const [messages, setMessages] = useState([WELCOME_MESSAGE]);
  const [input, setInput] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const bottomRef = useRef(null);
  const textareaRef = useRef(null);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, loading]);

  const send = async () => {
    const text = input.trim();
    if (!text || loading) return;

    const userMsg = { role: "user", content: text };
    const newMessages = [...messages, userMsg];
    setMessages(newMessages);
    setInput("");
    setLoading(true);
    setError(null);

    try {
      const apiMessages = newMessages
        .filter((m) => m !== WELCOME_MESSAGE)
        .map((m) => ({ role: m.role, content: m.content }));

      if (apiMessages.length === 0 || apiMessages[0].role !== "user") {
        apiMessages.unshift({ role: "user", content: text });
      }

      const response = await fetch("https://api.x.ai/v1/chat/completions", {
        method: "POST",
        headers: { "Content-Type": "application/json", Authorization: `Bearer ${process.env.XAI_API_KEY}` },
        body: JSON.stringify({
          model: "grok-3-mini",
          max_tokens: 4096,
          messages: [
            { role: "system", content: SYSTEM_PROMPT },
            ...apiMessages,
          ],
        }),
      });

      if (!response.ok) throw new Error(`API error: ${response.status}`);
      const data = await response.json();
      const reply = data.content?.find((b) => b.type === "text")?.text || "No response.";
      setMessages((prev) => [...prev, { role: "assistant", content: reply }]);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleKey = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      send();
    }
  };

  const QUICK_PROMPTS = [
    "Build the creator storefront homepage",
    "Subdomain middleware for creatorname.rareimagery.net",
    "Grok AI auto-import X profile button",
    "Product listing + buy now component",
    "Marketplace homepage with creator grid",
  ];

  return (
    <>
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
          font-family: 'Syne', sans-serif;
          background: #080808;
          color: #e8e8e8;
        }

        .app {
          display: flex;
          flex-direction: column;
          height: 100vh;
          max-width: 900px;
          margin: 0 auto;
          background: #080808;
        }

        .header {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 18px 24px;
          border-bottom: 1px solid #1a1a1a;
          background: #0a0a0a;
          flex-shrink: 0;
        }

        .logo-mark {
          width: 36px;
          height: 36px;
          background: #fff;
          border-radius: 6px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 800;
          font-size: 14px;
          color: #000;
          letter-spacing: -1px;
          flex-shrink: 0;
        }

        .header-text h1 {
          font-size: 15px;
          font-weight: 700;
          color: #fff;
          letter-spacing: -0.3px;
        }

        .header-text p {
          font-size: 11px;
          color: #555;
          font-family: 'JetBrains Mono', monospace;
          margin-top: 1px;
        }

        .status-dot {
          width: 7px;
          height: 7px;
          background: #22c55e;
          border-radius: 50%;
          margin-left: auto;
          box-shadow: 0 0 8px #22c55e88;
          animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
          0%, 100% { opacity: 1; }
          50% { opacity: 0.4; }
        }

        .messages {
          flex: 1;
          overflow-y: auto;
          padding: 24px;
          display: flex;
          flex-direction: column;
          gap: 20px;
          scrollbar-width: thin;
          scrollbar-color: #222 transparent;
        }

        .message {
          display: flex;
          gap: 12px;
          animation: fadeUp 0.25s ease;
        }

        @keyframes fadeUp {
          from { opacity: 0; transform: translateY(8px); }
          to { opacity: 1; transform: translateY(0); }
        }

        .avatar {
          width: 30px;
          height: 30px;
          border-radius: 6px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 12px;
          font-weight: 700;
          flex-shrink: 0;
          margin-top: 2px;
        }

        .avatar.ai { background: #111; border: 1px solid #222; color: #fff; }
        .avatar.user { background: #1d1d1d; border: 1px solid #2a2a2a; color: #888; }

        .message-bubble {
          flex: 1;
          background: #0e0e0e;
          border: 1px solid #1a1a1a;
          border-radius: 10px;
          padding: 14px 16px;
          max-width: 100%;
          overflow: hidden;
        }

        .message-bubble.user-bubble {
          background: #111;
          border-color: #222;
        }

        .prose-text { line-height: 1.7; font-size: 14px; color: #ccc; }
        .prose-text h1 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 12px; letter-spacing: -0.5px; }
        .prose-text h2 { font-size: 16px; font-weight: 700; color: #fff; margin: 14px 0 8px; }
        .prose-text h3 { font-size: 14px; font-weight: 700; color: #ddd; margin: 10px 0 6px; }
        .prose-text strong { color: #fff; font-weight: 700; }
        .prose-text em { color: #888; font-style: italic; }
        .prose-text code { font-family: 'JetBrains Mono', monospace; font-size: 12px; background: #1a1a1a; color: #e2e8f0; padding: 2px 6px; border-radius: 4px; }
        .prose-text li { margin-left: 16px; list-style: disc; margin-bottom: 4px; }
        .prose-text p { margin-bottom: 8px; }

        .code-block {
          margin: 10px 0;
          border-radius: 8px;
          overflow: hidden;
          border: 1px solid #1f1f1f;
          background: #0a0a0a;
        }

        .code-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 8px 14px;
          background: #111;
          border-bottom: 1px solid #1f1f1f;
        }

        .filename {
          font-family: 'JetBrains Mono', monospace;
          font-size: 11px;
          color: #555;
        }

        .copy-btn {
          font-family: 'JetBrains Mono', monospace;
          font-size: 11px;
          color: #555;
          background: #1a1a1a;
          border: 1px solid #2a2a2a;
          border-radius: 4px;
          padding: 3px 10px;
          cursor: pointer;
          transition: all 0.15s;
        }

        .copy-btn:hover { color: #fff; border-color: #444; background: #222; }

        .code-body {
          overflow-x: auto;
          padding: 16px;
          font-family: 'JetBrains Mono', monospace;
          font-size: 12px;
          line-height: 1.6;
          color: #a8b5c8;
          max-height: 480px;
        }

        .thinking {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 12px 16px;
          background: #0e0e0e;
          border: 1px solid #1a1a1a;
          border-radius: 10px;
          font-size: 13px;
          color: #555;
          font-family: 'JetBrains Mono', monospace;
        }

        .thinking-dots span {
          display: inline-block;
          width: 5px;
          height: 5px;
          background: #333;
          border-radius: 50%;
          margin: 0 2px;
          animation: blink 1.2s ease-in-out infinite;
        }
        .thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
        .thinking-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes blink {
          0%, 80%, 100% { background: #333; }
          40% { background: #666; }
        }

        .quick-prompts {
          padding: 0 24px 12px;
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          flex-shrink: 0;
        }

        .qp-btn {
          font-family: 'JetBrains Mono', monospace;
          font-size: 11px;
          color: #555;
          background: #0e0e0e;
          border: 1px solid #1f1f1f;
          border-radius: 20px;
          padding: 5px 12px;
          cursor: pointer;
          transition: all 0.15s;
          white-space: nowrap;
        }

        .qp-btn:hover { color: #ccc; border-color: #333; background: #131313; }

        .input-area {
          padding: 16px 24px 20px;
          border-top: 1px solid #1a1a1a;
          background: #0a0a0a;
          flex-shrink: 0;
        }

        .input-row {
          display: flex;
          gap: 10px;
          align-items: flex-end;
          background: #0e0e0e;
          border: 1px solid #222;
          border-radius: 10px;
          padding: 10px 12px;
          transition: border-color 0.2s;
        }

        .input-row:focus-within { border-color: #333; }

        textarea {
          flex: 1;
          background: transparent;
          border: none;
          outline: none;
          color: #e8e8e8;
          font-family: 'Syne', sans-serif;
          font-size: 14px;
          resize: none;
          line-height: 1.5;
          max-height: 120px;
          scrollbar-width: none;
        }

        textarea::placeholder { color: #333; }

        .send-btn {
          width: 34px;
          height: 34px;
          background: #fff;
          border: none;
          border-radius: 7px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          flex-shrink: 0;
          transition: all 0.15s;
        }

        .send-btn:hover { background: #ddd; }
        .send-btn:disabled { background: #1a1a1a; cursor: not-allowed; }
        .send-btn svg { width: 16px; height: 16px; }

        .error-msg {
          margin: 0 24px;
          padding: 10px 14px;
          background: #1a0a0a;
          border: 1px solid #3a1010;
          border-radius: 8px;
          color: #f87171;
          font-size: 12px;
          font-family: 'JetBrains Mono', monospace;
        }
      `}</style>

      <div className="app">
        <div className="header">
          <div className="logo-mark">RI</div>
          <div className="header-text">
            <h1>RareImagery Builder</h1>
            <p>Next.js Architect · Grok-powered</p>
          </div>
          <div className="status-dot" />
        </div>

        <div className="messages">
          {messages.map((msg, i) => (
            <div key={i} className="message">
              <div className={`avatar ${msg.role === "assistant" ? "ai" : "user"}`}>
                {msg.role === "assistant" ? "AI" : "U"}
              </div>
              <div className={`message-bubble ${msg.role === "user" ? "user-bubble" : ""}`}>
                <MessageContent content={msg.content} />
              </div>
            </div>
          ))}

          {loading && (
            <div className="message">
              <div className="avatar ai">AI</div>
              <div className="thinking">
                <span>Architecting your Next.js code</span>
                <div className="thinking-dots">
                  <span /><span /><span />
                </div>
              </div>
            </div>
          )}
          <div ref={bottomRef} />
        </div>

        {error && <div className="error-msg">⚠ {error}</div>}

        <div className="quick-prompts">
          {QUICK_PROMPTS.map((p, i) => (
            <button key={i} className="qp-btn" onClick={() => { setInput(p); textareaRef.current?.focus(); }}>
              {p}
            </button>
          ))}
        </div>

        <div className="input-area">
          <div className="input-row">
            <textarea
              ref={textareaRef}
              rows={1}
              value={input}
              onChange={(e) => {
                setInput(e.target.value);
                e.target.style.height = "auto";
                e.target.style.height = Math.min(e.target.scrollHeight, 120) + "px";
              }}
              onKeyDown={handleKey}
              placeholder="Describe what to build... (Shift+Enter for new line)"
            />
            <button className="send-btn" onClick={send} disabled={!input.trim() || loading}>
              <svg viewBox="0 0 24 24" fill="none" stroke={!input.trim() || loading ? "#333" : "#000"} strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <line x1="22" y1="2" x2="11" y2="13" />
                <polygon points="22 2 15 22 11 13 2 9 22 2" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
