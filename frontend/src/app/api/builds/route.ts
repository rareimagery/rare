import { NextRequest, NextResponse } from 'next/server'
import { validateDrupalSession } from '@/lib/drupalAuth'
import { getBuilds, saveBuilds } from '@/lib/drupalBuilds'
import { v4 as uuidv4 } from 'uuid'

// GET — fetch all saved builds for this store
export async function GET(req: NextRequest) {
  const session =
    req.cookies.get('session_token')?.value ||
    req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const builds = await getBuilds(user.storeId)
  return NextResponse.json({ builds })
}

// POST — save a new build
export async function POST(req: NextRequest) {
  const session =
    req.cookies.get('session_token')?.value ||
    req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { label, code } = await req.json()
  if (!label || !code) {
    return NextResponse.json({ error: 'label and code required' }, { status: 400 })
  }

  const builds = await getBuilds(user.storeId)
  const newBuild = { id: uuidv4(), label, code, createdAt: new Date().toISOString() }
  const updated = [...builds, newBuild]

  // U1: Forward session cookie for write auth
  const sessionCookie = req.headers.get('cookie') ?? ''
  await saveBuilds(user.storeId, updated, sessionCookie)

  return NextResponse.json({ build: newBuild })
}

// DELETE — remove a build by id
export async function DELETE(req: NextRequest) {
  const session =
    req.cookies.get('session_token')?.value ||
    req.headers.get('authorization')?.replace('Bearer ', '')
  const user = await validateDrupalSession(session)
  if (!user) return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })

  const { id } = await req.json()
  const builds = await getBuilds(user.storeId)
  const updated = builds.filter((b) => b.id !== id)

  // U1: Forward session cookie for write auth
  const sessionCookie = req.headers.get('cookie') ?? ''
  await saveBuilds(user.storeId, updated, sessionCookie)

  return NextResponse.json({ ok: true })
}
