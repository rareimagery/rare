'use client'

import { useState, useRef } from 'react'
import BuildLibrary from './BuildLibrary'
import LivePreview from './LivePreview'

type Tab = 'generate' | 'preview' | 'saved'

interface FloatingBuilderProps {
  theme?: string // U2: store theme name passed from BuilderGate
}

export default function FloatingBuilder({ theme }: FloatingBuilderProps) {
  const [open, setOpen] = useState(false)
  const [tab, setTab] = useState<Tab>('generate')
  const [prompt, setPrompt] = useState('')
  const [result, setResult] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [saveLabel, setSaveLabel] = useState('')
  const [saving, setSaving] = useState(false)

  // U3: Streaming generation handler
  async function handleGenerate() {
    if (!prompt.trim()) return
    setLoading(true)
    setError('')
    setResult('')

    try {
      const res = await fetch('/api/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        // U2: Pass theme in request body
        body: JSON.stringify({ message: prompt, theme }),
      })

      if (res.status === 429) {
        setError('Rate limit reached. Try again in an hour.')
        return
      }
      if (!res.ok) {
        setError('Something went wrong.')
        return
      }

      // U3: Read streaming response chunk-by-chunk
      const reader = res.body!.getReader()
      const decoder = new TextDecoder()
      let accumulated = ''
      let firstChunk = true

      while (true) {
        const { done, value } = await reader.read()
        if (done) break
        accumulated += decoder.decode(value, { stream: true })
        setResult(accumulated)

        // Auto-switch to preview on first chunk arrival
        if (firstChunk) {
          setTab('preview')
          firstChunk = false
        }
      }
    } catch {
      setError('Request failed. Check your connection.')
    } finally {
      setLoading(false)
    }
  }

  async function handleSave() {
    if (!result || !saveLabel.trim()) return
    setSaving(true)
    try {
      await fetch('/api/builds', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ label: saveLabel.trim(), code: result }),
      })
      setSaveLabel('')
    } finally {
      setSaving(false)
    }
  }

  function handleLoad(code: string) {
    setResult(code)
    setTab('preview')
  }

  if (!open) {
    return (
      <button
        onClick={() => setOpen(true)}
        className="fixed bottom-6 right-6 z-50 px-4 py-3 bg-purple-700 text-white
                   text-sm font-semibold rounded-full shadow-lg hover:bg-purple-800
                   transition-colors"
      >
        Page Builder
      </button>
    )
  }

  return (
    <div className="fixed bottom-6 right-6 z-50 w-[480px] max-h-[80vh] flex flex-col
                    bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">

      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 bg-purple-700 text-white">
        <span className="text-sm font-semibold">Page Builder</span>
        <button onClick={() => setOpen(false)} className="text-white/70 hover:text-white text-lg leading-none">&times;</button>
      </div>

      {/* Tabs */}
      <div className="flex border-b border-gray-200">
        {(['generate', 'preview', 'saved'] as Tab[]).map(t => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`flex-1 py-2 text-xs font-medium capitalize transition-colors
              ${tab === t ? 'border-b-2 border-purple-700 text-purple-700' : 'text-gray-500 hover:text-gray-700'}`}
          >
            {t === 'saved' ? 'Saved Builds' : t}
          </button>
        ))}
      </div>

      {/* Tab Content */}
      <div className="flex-1 overflow-y-auto">

        {tab === 'generate' && (
          <div className="p-4 space-y-3">
            <textarea
              className="w-full h-28 p-3 border border-gray-200 rounded-lg text-sm
                         focus:outline-none focus:ring-2 focus:ring-purple-600 resize-none"
              placeholder="Describe the component or section you need..."
              value={prompt}
              onChange={e => setPrompt(e.target.value)}
            />
            <button
              onClick={handleGenerate}
              disabled={loading || !prompt.trim()}
              className="w-full py-2 bg-purple-700 text-white text-sm font-medium rounded-lg
                         hover:bg-purple-800 disabled:opacity-50 transition-colors"
            >
              {loading ? 'Generating...' : 'Generate'}
            </button>
            {error && <p className="text-xs text-red-500">{error}</p>}
            {result && (
              <div className="relative">
                <pre className="p-3 bg-gray-900 text-gray-100 text-xs rounded-lg overflow-x-auto whitespace-pre-wrap">
                  {result}
                </pre>
                <button
                  onClick={() => navigator.clipboard.writeText(result)}
                  className="absolute top-2 right-2 px-2 py-1 text-xs bg-gray-700
                             text-gray-300 rounded hover:bg-gray-600"
                >
                  Copy
                </button>
              </div>
            )}
            {result && (
              <div className="flex gap-2">
                <input
                  className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm
                             focus:outline-none focus:ring-2 focus:ring-purple-600"
                  placeholder="Name this build..."
                  value={saveLabel}
                  onChange={e => setSaveLabel(e.target.value)}
                />
                <button
                  onClick={handleSave}
                  disabled={saving || !saveLabel.trim()}
                  className="px-4 py-2 bg-gray-900 text-white text-sm rounded-lg
                             hover:bg-gray-700 disabled:opacity-50 transition-colors"
                >
                  {saving ? 'Saving...' : 'Save'}
                </button>
              </div>
            )}
          </div>
        )}

        {tab === 'preview' && (
          <LivePreview code={result} />
        )}

        {tab === 'saved' && (
          <BuildLibrary onLoad={handleLoad} />
        )}
      </div>
    </div>
  )
}
