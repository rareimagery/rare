import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';

/**
 * POST /api/ai/generate
 *
 * Thin proxy from Next.js to Drupal's ai_provider module.
 * FloatingBuilder.tsx hits this — it never talks to Drupal directly.
 */
export async function POST(request: NextRequest) {
  // Auth check — creator must be logged in.
  const session = await getServerSession();
  if (!session) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  try {
    const body = await request.json();

    const drupalUrl = `${process.env.DRUPAL_BASE_URL}/jsonapi/ai-provider/generate`;

    const response = await fetch(drupalUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${process.env.DRUPAL_API_TOKEN}`,
      },
      body: JSON.stringify(body),
    });

    const data = await response.json();
    return NextResponse.json(data, { status: response.status });
  }
  catch (error) {
    console.error('AI generate proxy error:', error);
    return NextResponse.json(
      { error: 'Failed to reach AI service.' },
      { status: 502 }
    );
  }
}
