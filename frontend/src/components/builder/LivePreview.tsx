'use client'

import { useEffect, useRef } from 'react'

export default function LivePreview({ code }: { code: string }) {
  const iframeRef = useRef<HTMLIFrameElement>(null)
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  // Debounce srcdoc updates during streaming (every 500ms) to prevent flicker
  useEffect(() => {
    if (!iframeRef.current || !code) return

    if (debounceRef.current) clearTimeout(debounceRef.current)

    debounceRef.current = setTimeout(() => {
      if (!iframeRef.current) return
      const html = `
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.tailwindcss.com"><\/script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"><\/script>
  <style>
    body { margin: 0; background: #0a0a0a; }
    @keyframes blink { 50% { opacity: 0; } }
    @keyframes rainbow { 0%{color:#ff0080} 25%{color:#00ffff} 50%{color:#ffff00} 75%{color:#00ff80} 100%{color:#ff0080} }
  </style>
</head>
<body>
  <div id="root"></div>
  <script src="https://unpkg.com/react@18/umd/react.development.js"><\/script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"><\/script>
  <script type="text/babel" data-presets="react">
    ${code.replace(/<\/script>/g, '<\\/script>')}
    try {
      const Component = typeof exports !== 'undefined' && exports.default ? exports.default : null
      if (Component) {
        ReactDOM.render(React.createElement(Component), document.getElementById('root'))
      }
    } catch(e) {
      document.getElementById('root').innerHTML = '<p style="color:#ff4444;padding:1rem;font-family:monospace">' + e.message + '</p>'
    }
  <\/script>
</body>
</html>`
      iframeRef.current.srcdoc = html
    }, 500)

    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current)
    }
  }, [code])

  if (!code) {
    return (
      <div className="flex items-center justify-center h-48 text-sm text-gray-400">
        Generate a component to preview it here
      </div>
    )
  }

  return (
    <iframe
      ref={iframeRef}
      sandbox="allow-scripts"
      className="w-full h-[400px] border-0"
      title="Component Preview"
    />
  )
}
