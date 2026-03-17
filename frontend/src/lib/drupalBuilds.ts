const DRUPAL_BASE = process.env.DRUPAL_BASE_URL
const SERVICE_TOKEN = process.env.DRUPAL_SERVICE_TOKEN

export interface Build {
  id: string
  label: string
  code: string
  createdAt: string
}

/**
 * Fetch all saved builds for a store.
 * Uses Bearer token auth (reads work fine with service token).
 */
export async function getBuilds(storeId: string): Promise<Build[]> {
  const res = await fetch(
    `${DRUPAL_BASE}/jsonapi/commerce_store/online/${storeId}?fields[commerce_store--online]=field_page_builds`,
    {
      headers: {
        Authorization: `Bearer ${SERVICE_TOKEN}`,
        Accept: 'application/vnd.api+json',
      },
      cache: 'no-store',
    }
  )
  if (!res.ok) return []
  const data = await res.json()
  return data.data?.attributes?.field_page_builds ?? []
}

/**
 * Save builds to a store entity via PATCH.
 *
 * U1 FIX: Uses cookie session auth + CSRF token instead of Bearer token.
 * Drupal Commerce entity writes require session auth — Basic Auth / Bearer
 * tokens fail with 403 on PATCH operations for commerce_store entities.
 */
export async function saveBuilds(
  storeId: string,
  builds: Build[],
  sessionCookie: string
): Promise<void> {
  // Fetch CSRF token from Drupal (required for session-authed writes)
  const tokenRes = await fetch(`${DRUPAL_BASE}/session/token`, {
    headers: { Cookie: sessionCookie },
  })
  const csrfToken = await tokenRes.text()

  await fetch(`${DRUPAL_BASE}/jsonapi/commerce_store/online/${storeId}`, {
    method: 'PATCH',
    headers: {
      Cookie: sessionCookie,
      'X-CSRF-Token': csrfToken,
      'Content-Type': 'application/vnd.api+json',
      Accept: 'application/vnd.api+json',
    },
    body: JSON.stringify({
      data: {
        type: 'commerce_store--online',
        id: storeId,
        attributes: { field_page_builds: builds },
      },
    }),
  })
}
