// app/api/auth/x/callback/route.ts
import { TwitterApi } from 'twitter-api-v2';
import { getToken } from 'next-auth/jwt';

export async function GET(req) {
  const token = await getToken({ req });
  const client = new TwitterApi(token.accessToken); // User Context

  const me = await client.v2.me({
    'user.fields': 'profile_image_url,profile_banner_url,public_metrics,username'
  });

  // Top posts
  const tweets = await client.v2.userTimeline(me.data.id, {
    max_results: 10,
    'tweet.fields': 'public_metrics,text,created_at,attachments'
  });

  // Best followers (sample + rank by their follower count)
  const followers = await client.v2.followers(me.data.id, {
    max_results: 5,
    'user.fields': 'public_metrics,username,profile_image_url'
  });

  // Optional: Let Grok AI pick the real “best” 3
  const grokPrompt = `Rank these followers by potential value to a store: ${JSON.stringify(followers.data)}`;
  const grokResponse = await fetch('https://api.x.ai/v1/chat/completions', { /* your Grok key */ });

  // Send to Drupal
  await fetch('https://your-drupal-site.com/jsonapi/node/store', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: 'Basic YOUR_DRUPAL_API_KEY' },
    body: JSON.stringify({
      data: {
        type: 'node--store',
        attributes: {
          title: `@${me.data.username}'s Store`,
          field_x_username: me.data.username,
          field_top_posts: JSON.stringify(tweets.data),
          field_best_followers: JSON.stringify(followers.data),
          field_status: 'pending'
        },
        relationships: {
          field_pfp: { data: { /* upload image logic */ } },
          field_background: { /* same */ }
        }
      }
    })
  });

  // Redirect to store dashboard
}