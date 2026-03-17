'use client'

import { useEffect, useState } from 'react'
import type { Build } from '@/lib/drupalBuilds'

export default function BuildLibrary({ onLoad }: { onLoad: (code: string) => void }) {
  const [builds, setBuilds] = useState<Build[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch('/api/builds')
      .then(r => r.json())
      .then(d => setBuilds(d.builds ?? []))
      .finally(() => setLoading(false))
  }, [])

  async function handleDelete(id: string) {
    await fetch('/api/builds', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    })
    setBuilds(prev => prev.filter(b => b.id !== id))
  }

  if (loading) return <p className="p-4 text-sm text-gray-400">Loading builds...</p>
  if (!builds.length) return <p className="p-4 text-sm text-gray-400">No saved builds yet.</p>

  return (
    <ul className="divide-y divide-gray-100">
      {builds.map(b => (
        <li key={b.id} className="flex items-center gap-2 px-4 py-3">
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium text-gray-800 truncate">{b.label}</p>
            <p className="text-xs text-gray-400">{new Date(b.createdAt).toLocaleDateString()}</p>
          </div>
          <button
            onClick={() => onLoad(b.code)}
            className="px-3 py-1 text-xs bg-purple-700 text-white rounded hover:bg-purple-800"
          >
            Load
          </button>
          <button
            onClick={() => handleDelete(b.id)}
            className="px-3 py-1 text-xs bg-gray-100 text-gray-500 rounded hover:bg-gray-200"
          >
            Delete
          </button>
        </li>
      ))}
    </ul>
  )
}
