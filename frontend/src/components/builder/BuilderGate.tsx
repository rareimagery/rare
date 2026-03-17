'use client'

import FloatingBuilder from './FloatingBuilder'

interface BuilderGateProps {
  isOwner: boolean
  theme?: string // U2: pass store theme to builder
}

export default function BuilderGate({ isOwner, theme }: BuilderGateProps) {
  if (!isOwner) return null
  return <FloatingBuilder theme={theme} />
}
