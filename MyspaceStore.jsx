// MyspaceStore.jsx
// Drop into: app/stores/[creator]/MyspaceStore.tsx
// Theme config comes from Drupal field_store_theme (JSON field)

'use client'
import { useState, useEffect, useRef } from 'react'

// ─── DEFAULT THEME (store owner overrides via console) ──────────────────────
const DEFAULT_THEME = {
  bgColor: '#000033',
  bgTile: 'stars',         // 'stars' | 'hearts' | 'skulls' | 'custom'
  bgTileCustomUrl: '',
  accentColor: '#ff00ff',
  secondColor: '#00ffff',
  textColor: '#ffffff',
  tableBorderColor: '#ff00ff',
  tableBgColor: '#000066',
  font: 'comic',           // 'comic' | 'impact' | 'cursive' | 'times'
  glitterText: true,
  cursorTrail: true,
  marqueeText: '✨ Welcome to my store! ✨ Thanks for visiting! ✨ Leave a comment! ✨',
  songUrl: '',             // mp3/ogg URL — store owner pastes their track
  songTitle: 'My Song',
  songArtist: 'Unknown Artist',
  profileMood: '🎵 Feeling creative',
  onlineNow: true,
  visitorCount: 2847,
}

const FONT_MAP = {
  comic: '"Comic Sans MS", "Comic Sans", cursive',
  impact: 'Impact, "Arial Narrow", sans-serif',
  cursive: '"Brush Script MT", "Segoe Script", cursive',
  times: '"Times New Roman", Times, serif',
}

const TILE_PATTERNS = {
  stars: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30'%3E%3Ctext y='20' font-size='16'%3E⭐%3C/text%3E%3C/svg%3E")`,
  hearts: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30'%3E%3Ctext y='20' font-size='16'%3E💗%3C/text%3E%3C/svg%3E")`,
  skulls: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30'%3E%3Ctext y='20' font-size='16'%3E💀%3C/text%3E%3C/svg%3E")`,
}

// ─── SAMPLE STORE DATA (replace with Drupal fetch) ───────────────────────────
const SAMPLE_STORE = {
  name: 'RareImagery',
  xUsername: '@rareimagery',
  bio: 'Hey!! Welcome 2 my store!! I make rare digital art and prints 🎨✨ Add me!! Check out my top products below!! xoxo',
  pfpUrl: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=rareimagery',
  bannerUrl: '',
  followerCount: 12847,
  topFollowers: [
    { name: 'xXdark_angelXx', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=angel' },
    { name: 'glitter_gurl', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=glitter' },
    { name: 'sk8r_boi_2006', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=sk8r' },
    { name: 'im_so_emo', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=emo' },
    { name: 'stardust99', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=star' },
    { name: 'n3on_butterfly', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=neon' },
    { name: 'lol_ur_so_kewl', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=kewl' },
    { name: 'xo_princess_xo', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=princess' },
  ],
  products: [
    { id: 1, name: 'Rare Galaxy Print', price: 29.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=galaxy&backgroundColor=0d1b2a', tag: 'HOT🔥' },
    { id: 2, name: 'Neon Dreams Poster', price: 19.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=neon&backgroundColor=ff006e', tag: 'NEW✨' },
    { id: 3, name: 'Pixel Heart Sticker Pack', price: 9.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=heart&backgroundColor=8338ec', tag: null },
    { id: 4, name: 'Dark Angel Tee', price: 34.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=tee&backgroundColor=1a1a2e', tag: 'LIMITED💎' },
    { id: 5, name: 'Glitter Skull Hoodie', price: 54.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=skull&backgroundColor=240046', tag: 'HOT🔥' },
    { id: 6, name: 'Y2K Butterfly Top', price: 44.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=butterfly&backgroundColor=ff4d6d', tag: 'NEW✨' },
    { id: 7, name: 'Glow-in-Dark Beanie', price: 24.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=beanie&backgroundColor=06d6a0', tag: null },
    { id: 8, name: 'Scene Queen Bag', price: 39.99, img: 'https://api.dicebear.com/7.x/shapes/svg?seed=bag&backgroundColor=ffbe0b', tag: 'SALE🏷' },
  ],
  comments: [
    { user: 'xXdark_angelXx', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=angel', text: 'omg ur store is so kewl!! luv the hoodie!! adding u 2 my top 8!! xoxo', time: '2 hours ago' },
    { user: 'glitter_gurl', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=glitter', text: 'OMG THE STICKER PACK IS SO CUTE!! already bought 3 packs lol 😍😍', time: '5 hours ago' },
    { user: 'sk8r_boi_2006', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=sk8r', text: 'sup. the galaxy print slaps. ordered it. peace ✌', time: 'yesterday' },
    { user: 'im_so_emo', avatar: 'https://api.dicebear.com/7.x/pixel-art/svg?seed=emo', text: '(>^.^)> ur store is lyke the best thing eva!! leave me a comment back plzz!!', time: 'yesterday' },
  ],
}

// ─── CURSOR TRAIL HOOK ───────────────────────────────────────────────────────
function useCursorTrail(enabled, color) {
  useEffect(() => {
    if (!enabled) return
    const sparkles = []
    const onMove = (e) => {
      const el = document.createElement('div')
      el.style.cssText = `
        position:fixed;left:${e.clientX - 8}px;top:${e.clientY - 8}px;
        width:16px;height:16px;pointer-events:none;z-index:99999;
        font-size:14px;line-height:16px;text-align:center;
        animation:sparkle-fade 0.7s forwards;
      `
      el.textContent = ['✨','⭐','💫','🌟','✦'][Math.floor(Math.random() * 5)]
      document.body.appendChild(el)
      sparkles.push(el)
      setTimeout(() => { el.remove(); sparkles.splice(sparkles.indexOf(el), 1) }, 700)
    }
    window.addEventListener('mousemove', onMove)
    return () => window.removeEventListener('mousemove', onMove)
  }, [enabled, color])
}

// ─── GLITTER TEXT COMPONENT ──────────────────────────────────────────────────
function GlitterText({ children, size = '1em', tag: Tag = 'span' }) {
  const colors = ['#ff00ff', '#00ffff', '#ffff00', '#ff6600', '#00ff00', '#ff0066', '#6600ff']
  return (
    <Tag style={{ display: 'inline-block', fontSize: size }}>
      {String(children).split('').map((char, i) => (
        <span
          key={i}
          style={{
            color: colors[i % colors.length],
            animation: `glitter ${0.3 + (i % 7) * 0.1}s infinite alternate`,
            display: 'inline-block',
            textShadow: `0 0 8px ${colors[(i + 2) % colors.length]}, 0 0 16px ${colors[(i + 4) % colors.length]}`,
          }}
        >
          {char === ' ' ? '\u00A0' : char}
        </span>
      ))}
    </Tag>
  )
}

// ─── BLINKING BADGE ──────────────────────────────────────────────────────────
function BlinkBadge({ children, color = '#ff0000' }) {
  return (
    <span style={{
      background: color, color: '#fff',
      padding: '2px 6px', fontSize: '0.7em',
      fontWeight: 'bold', border: '1px solid #fff',
      animation: 'blink 0.8s step-start infinite',
      display: 'inline-block', verticalAlign: 'middle',
    }}>
      {children}
    </span>
  )
}

// ─── TABLE PANEL COMPONENT ───────────────────────────────────────────────────
function Panel({ title, theme, children, extra }) {
  return (
    <div style={{
      border: `3px solid ${theme.tableBorderColor}`,
      marginBottom: 12,
      boxShadow: `0 0 12px ${theme.tableBorderColor}, inset 0 0 8px rgba(0,0,0,0.5)`,
    }}>
      <div style={{
        background: `linear-gradient(90deg, ${theme.tableBorderColor}, ${theme.secondColor}, ${theme.tableBorderColor})`,
        padding: '4px 8px',
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
      }}>
        <b style={{ fontFamily: FONT_MAP[theme.font], color: '#fff', textShadow: '1px 1px 0 #000', fontSize: '0.9em' }}>
          {title}
        </b>
        {extra}
      </div>
      <div style={{ background: theme.tableBgColor, padding: 10 }}>
        {children}
      </div>
    </div>
  )
}

// ─── MUSIC PLAYER ────────────────────────────────────────────────────────────
function MusicPlayer({ theme, store, songUrl, songTitle, songArtist }) {
  const audioRef = useRef(null)
  const [playing, setPlaying] = useState(false)
  const [vol, setVol] = useState(80)

  const toggle = () => {
    if (!audioRef.current) return
    if (playing) { audioRef.current.pause(); setPlaying(false) }
    else { audioRef.current.play().catch(() => {}); setPlaying(true) }
  }

  return (
    <Panel title="🎵 Now Playing" theme={theme}>
      {songUrl && <audio ref={audioRef} src={songUrl} loop preload="none" />}
      <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
        <img src={store.pfpUrl} alt="" style={{ width: 40, height: 40, border: `2px solid ${theme.accentColor}` }} />
        <div style={{ flex: 1 }}>
          <div style={{ color: theme.accentColor, fontWeight: 'bold', fontSize: '0.85em' }}>{songTitle}</div>
          <div style={{ color: theme.secondColor, fontSize: '0.75em' }}>{songArtist}</div>
        </div>
      </div>
      <div style={{ display: 'flex', gap: 6, marginTop: 8, alignItems: 'center' }}>
        <button onClick={toggle} style={{
          background: theme.accentColor, border: 'none', color: '#fff',
          padding: '4px 12px', fontFamily: FONT_MAP[theme.font],
          cursor: 'pointer', fontSize: '0.8em', fontWeight: 'bold',
          boxShadow: `0 0 8px ${theme.accentColor}`,
        }}>
          {playing ? '⏸ PAUSE' : '▶ PLAY'}
        </button>
        <span style={{ color: theme.textColor, fontSize: '0.75em' }}>VOL:</span>
        <input type="range" min="0" max="100" value={vol}
          onChange={e => { setVol(e.target.value); if (audioRef.current) audioRef.current.volume = e.target.value / 100 }}
          style={{ width: 70, accentColor: theme.accentColor }}
        />
      </div>
      {!songUrl && (
        <div style={{ color: '#888', fontSize: '0.75em', marginTop: 6, fontStyle: 'italic' }}>
          No song set — store owner can add one in the console
        </div>
      )}
    </Panel>
  )
}

// ─── MAIN STORE COMPONENT ────────────────────────────────────────────────────
export default function MyspaceStore({ storeData = SAMPLE_STORE, themeConfig = {} }) {
  const theme = { ...DEFAULT_THEME, ...themeConfig }
  const store = storeData
  const [cart, setCart] = useState([])
  const [cartOpen, setCartOpen] = useState(false)
  const [visitorCount] = useState(theme.visitorCount + Math.floor(Math.random() * 50))
  const [addedId, setAddedId] = useState(null)

  useCursorTrail(theme.cursorTrail, theme.accentColor)

  const addToCart = (product) => {
    setCart(c => {
      const exists = c.find(i => i.id === product.id)
      if (exists) return c.map(i => i.id === product.id ? { ...i, qty: i.qty + 1 } : i)
      return [...c, { ...product, qty: 1 }]
    })
    setAddedId(product.id)
    setTimeout(() => setAddedId(null), 1500)
  }

  const bgStyle = theme.bgTile === 'custom' && theme.bgTileCustomUrl
    ? { backgroundImage: `url(${theme.bgTileCustomUrl})`, backgroundRepeat: 'repeat' }
    : { backgroundImage: TILE_PATTERNS[theme.bgTile] || TILE_PATTERNS.stars, backgroundRepeat: 'repeat', backgroundColor: theme.bgColor }

  return (
    <>
      {/* ── GLOBAL STYLES ── */}
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap');
        * { box-sizing: border-box; }
        body { margin: 0; cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20'%3E%3Ctext y='16' font-size='16'%3E✨%3C/text%3E%3C/svg%3E") 10 10, auto; }
        @keyframes glitter {
          0%   { transform: scale(1) rotate(0deg); filter: brightness(1); }
          100% { transform: scale(1.2) rotate(5deg); filter: brightness(1.8); }
        }
        @keyframes blink {
          0%, 100% { opacity: 1; }
          50%       { opacity: 0; }
        }
        @keyframes sparkle-fade {
          0%   { opacity: 1; transform: scale(1) translateY(0); }
          100% { opacity: 0; transform: scale(0.3) translateY(-20px); }
        }
        @keyframes rainbow {
          0%   { color: #ff0000; text-shadow: 0 0 10px #ff0000; }
          14%  { color: #ff8800; text-shadow: 0 0 10px #ff8800; }
          28%  { color: #ffff00; text-shadow: 0 0 10px #ffff00; }
          42%  { color: #00ff00; text-shadow: 0 0 10px #00ff00; }
          57%  { color: #0088ff; text-shadow: 0 0 10px #0088ff; }
          71%  { color: #8800ff; text-shadow: 0 0 10px #8800ff; }
          85%  { color: #ff00ff; text-shadow: 0 0 10px #ff00ff; }
          100% { color: #ff0000; text-shadow: 0 0 10px #ff0000; }
        }
        @keyframes marquee {
          from { transform: translateX(100%); }
          to   { transform: translateX(-100%); }
        }
        @keyframes float {
          0%, 100% { transform: translateY(0px); }
          50%       { transform: translateY(-6px); }
        }
        @keyframes pulse-border {
          0%, 100% { border-color: #ff00ff; box-shadow: 0 0 10px #ff00ff; }
          50%       { border-color: #00ffff; box-shadow: 0 0 20px #00ffff; }
        }
        @keyframes scanlines {
          from { background-position: 0 0; }
          to   { background-position: 0 4px; }
        }
        .product-card:hover {
          transform: scale(1.04) rotate(-1deg) !important;
          z-index: 10 !important;
        }
        .add-btn:hover {
          filter: brightness(1.3);
          transform: scale(1.05);
        }
        scrollbar-width: thin;
        ::-webkit-scrollbar { width: 8px; background: #000; }
        ::-webkit-scrollbar-thumb { background: #ff00ff; border: 1px solid #00ffff; }
      `}</style>

      {/* ── PAGE WRAPPER ── */}
      <div style={{ ...bgStyle, minHeight: '100vh', fontFamily: FONT_MAP[theme.font], color: theme.textColor, fontSize: 13 }}>

        {/* Scanline overlay */}
        <div style={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, pointerEvents: 'none', zIndex: 1,
          backgroundImage: 'linear-gradient(transparent 50%, rgba(0,0,0,0.15) 50%)',
          backgroundSize: '100% 4px' }} />

        {/* ── MARQUEE ── */}
        <div style={{
          background: `linear-gradient(90deg, #000, ${theme.accentColor}, #000)`,
          padding: '4px 0', overflow: 'hidden', position: 'sticky', top: 0, zIndex: 100,
          borderBottom: `2px solid ${theme.secondColor}`,
        }}>
          <div style={{ animation: 'marquee 18s linear infinite', whiteSpace: 'nowrap', display: 'inline-block' }}>
            {[...Array(3)].map((_, i) => (
              <span key={i} style={{ marginRight: 80, fontSize: '0.85em', fontWeight: 'bold',
                textShadow: `0 0 8px ${theme.secondColor}` }}>
                {theme.marqueeText}
              </span>
            ))}
          </div>
        </div>

        {/* ── CART INDICATOR ── */}
        <div style={{ position: 'fixed', top: 32, right: 12, zIndex: 200 }}>
          <button onClick={() => setCartOpen(o => !o)} style={{
            background: theme.accentColor, border: `2px solid ${theme.secondColor}`,
            color: '#fff', padding: '6px 12px', fontFamily: FONT_MAP[theme.font],
            cursor: 'pointer', fontWeight: 'bold', fontSize: '0.85em',
            boxShadow: `0 0 16px ${theme.accentColor}`, animation: cart.length ? 'pulse-border 1s infinite' : 'none',
          }}>
            🛒 CART ({cart.reduce((a, i) => a + i.qty, 0)})
          </button>
          {cartOpen && (
            <div style={{
              position: 'absolute', right: 0, top: '110%', width: 240,
              background: theme.tableBgColor, border: `2px solid ${theme.accentColor}`,
              padding: 10, zIndex: 300,
            }}>
              <b style={{ color: theme.accentColor }}>🛒 Your Cart</b>
              {cart.length === 0 && <p style={{ color: '#888', fontSize: '0.8em' }}>Cart is empty!</p>}
              {cart.map(item => (
                <div key={item.id} style={{ display: 'flex', justifyContent: 'space-between',
                  borderBottom: `1px solid ${theme.tableBorderColor}`, padding: '4px 0', fontSize: '0.8em' }}>
                  <span>{item.name} x{item.qty}</span>
                  <span style={{ color: theme.accentColor }}>${(item.price * item.qty).toFixed(2)}</span>
                </div>
              ))}
              {cart.length > 0 && (
                <>
                  <div style={{ textAlign: 'right', marginTop: 6, fontWeight: 'bold', color: theme.secondColor }}>
                    Total: ${cart.reduce((a, i) => a + i.price * i.qty, 0).toFixed(2)}
                  </div>
                  <button style={{
                    width: '100%', marginTop: 8, background: theme.secondColor,
                    border: 'none', color: '#000', fontFamily: FONT_MAP[theme.font],
                    fontWeight: 'bold', padding: '6px 0', cursor: 'pointer',
                  }}>
                    CHECKOUT ✨
                  </button>
                </>
              )}
            </div>
          )}
        </div>

        {/* ── MAIN LAYOUT ── */}
        <div style={{ maxWidth: 960, margin: '0 auto', padding: '12px 8px', position: 'relative', zIndex: 2 }}>

          {/* ── STORE HEADER ── */}
          <div style={{
            background: `linear-gradient(135deg, ${theme.bgColor}, #000 40%, ${theme.tableBgColor})`,
            border: `3px solid ${theme.accentColor}`,
            boxShadow: `0 0 30px ${theme.accentColor}, inset 0 0 20px rgba(0,0,0,0.7)`,
            padding: 16, marginBottom: 12, animation: 'pulse-border 2s infinite',
          }}>
            <div style={{ display: 'flex', gap: 16, alignItems: 'flex-start', flexWrap: 'wrap' }}>
              {/* PFP */}
              <div style={{ textAlign: 'center', animation: 'float 3s ease-in-out infinite' }}>
                <img src={store.pfpUrl} alt={store.name}
                  style={{ width: 100, height: 100, border: `4px solid ${theme.accentColor}`,
                    boxShadow: `0 0 20px ${theme.accentColor}`, display: 'block' }} />
                {theme.onlineNow && (
                  <div style={{ marginTop: 4 }}>
                    <BlinkBadge color="#00aa00">● ONLINE</BlinkBadge>
                  </div>
                )}
              </div>
              {/* Name + Bio */}
              <div style={{ flex: 1, minWidth: 200 }}>
                <GlitterText size="1.8em" tag="h1"
                  style={{ margin: '0 0 4px 0', fontFamily: FONT_MAP[theme.font] }}>
                  {store.name}
                </GlitterText>
                <div style={{ color: theme.secondColor, fontSize: '0.8em', marginBottom: 6 }}>
                  {store.xUsername} · <span style={{ animation: 'rainbow 2s linear infinite', display: 'inline-block' }}>
                    {store.followerCount.toLocaleString()} followers
                  </span>
                </div>
                <div style={{ fontSize: '0.85em', lineHeight: 1.5, color: theme.textColor,
                  background: 'rgba(0,0,0,0.4)', padding: '6px 8px',
                  border: `1px solid ${theme.tableBorderColor}` }}>
                  {store.bio}
                </div>
                <div style={{ marginTop: 6, fontSize: '0.78em', color: theme.accentColor }}>
                  Mood: {theme.profileMood}
                </div>
              </div>
              {/* Stats */}
              <div style={{ fontSize: '0.75em', textAlign: 'center', minWidth: 90 }}>
                <div style={{ border: `1px solid ${theme.tableBorderColor}`, padding: '4px 8px',
                  marginBottom: 4, background: 'rgba(0,0,0,0.5)' }}>
                  <div style={{ color: theme.secondColor }}>VISITORS</div>
                  <div style={{ color: theme.accentColor, fontSize: '1.3em', fontWeight: 'bold',
                    animation: 'glitter 1s alternate infinite' }}>
                    {visitorCount.toLocaleString()}
                  </div>
                </div>
                <div style={{ border: `1px solid ${theme.tableBorderColor}`, padding: '4px 8px',
                  background: 'rgba(0,0,0,0.5)' }}>
                  <div style={{ color: theme.secondColor }}>PRODUCTS</div>
                  <div style={{ color: theme.accentColor, fontSize: '1.3em', fontWeight: 'bold' }}>
                    {store.products.length}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* ── TWO COLUMN LAYOUT ── */}
          <div style={{ display: 'grid', gridTemplateColumns: '200px 1fr', gap: 12 }}>

            {/* LEFT COLUMN */}
            <div>
              <MusicPlayer theme={theme} store={store}
                songUrl={theme.songUrl} songTitle={theme.songTitle} songArtist={theme.songArtist} />

              {/* About */}
              <Panel title="💀 About Me" theme={theme}>
                <div style={{ fontSize: '0.8em', lineHeight: 1.6 }}>
                  <div><span style={{ color: theme.secondColor }}>Status:</span> {theme.onlineNow ? '🟢 Online' : '🔴 Away'}</div>
                  <div><span style={{ color: theme.secondColor }}>Followers:</span> {store.followerCount.toLocaleString()}</div>
                  <div><span style={{ color: theme.secondColor }}>X:</span> <span style={{ color: theme.accentColor }}>{store.xUsername}</span></div>
                  <div style={{ marginTop: 6, color: theme.textColor }}>{store.bio}</div>
                </div>
              </Panel>

              {/* Top 8 Followers */}
              <Panel title="✨ Top 8 Followers" theme={theme}>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 6 }}>
                  {store.topFollowers.slice(0, 8).map((f, i) => (
                    <div key={i} style={{ textAlign: 'center', fontSize: '0.7em' }}>
                      <img src={f.avatar} alt={f.name}
                        style={{ width: 44, height: 44, border: `2px solid ${theme.accentColor}`,
                          display: 'block', margin: '0 auto 2px' }} />
                      <div style={{ color: theme.secondColor, overflow: 'hidden',
                        textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: 72 }}>
                        {f.name}
                      </div>
                    </div>
                  ))}
                </div>
              </Panel>

              {/* Blinkies */}
              <Panel title="🌟 My Blinkies" theme={theme}>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 4 }}>
                  {['SCENE QUEEN', 'EMO 4 LIFE', 'RARE', 'XO XO', '2000s BABY'].map(txt => (
                    <span key={txt} style={{
                      background: `linear-gradient(90deg, ${theme.accentColor}, ${theme.secondColor})`,
                      color: '#fff', padding: '2px 6px', fontSize: '0.65em',
                      fontWeight: 'bold', animation: `blink ${0.5 + Math.random() * 0.8}s step-start infinite`,
                      border: '1px solid #fff',
                    }}>{txt}</span>
                  ))}
                </div>
              </Panel>
            </div>

            {/* RIGHT COLUMN */}
            <div>
              {/* Products */}
              <Panel title="🛍 TOP 8 PRODUCTS" theme={theme}
                extra={<BlinkBadge color="#ff6600">NEW DROPS</BlinkBadge>}>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 8 }}>
                  {store.products.slice(0, 8).map(p => (
                    <div key={p.id} className="product-card" style={{
                      border: `2px solid ${theme.tableBorderColor}`,
                      background: 'rgba(0,0,0,0.6)', textAlign: 'center',
                      padding: 6, cursor: 'pointer', transition: 'transform 0.15s',
                      position: 'relative',
                    }}>
                      {p.tag && (
                        <div style={{ position: 'absolute', top: 4, right: 4, zIndex: 2 }}>
                          <BlinkBadge color={p.tag.includes('SALE') ? '#ff6600' : p.tag.includes('NEW') ? '#0066ff' : '#cc0000'}>
                            {p.tag}
                          </BlinkBadge>
                        </div>
                      )}
                      <img src={p.img} alt={p.name}
                        style={{ width: '100%', aspectRatio: '1', display: 'block',
                          border: `1px solid ${theme.tableBorderColor}`, marginBottom: 4 }} />
                      <div style={{ fontSize: '0.7em', color: theme.textColor, marginBottom: 3,
                        lineHeight: 1.3 }}>{p.name}</div>
                      <div style={{ color: theme.accentColor, fontWeight: 'bold', fontSize: '0.85em',
                        animation: 'glitter 1.5s alternate infinite', marginBottom: 4 }}>
                        ${p.price}
                      </div>
                      <button className="add-btn" onClick={() => addToCart(p)} style={{
                        width: '100%', background: addedId === p.id ? '#00aa00' : theme.secondColor,
                        border: 'none', color: addedId === p.id ? '#fff' : '#000',
                        fontFamily: FONT_MAP[theme.font], fontWeight: 'bold',
                        cursor: 'pointer', padding: '3px 0', fontSize: '0.7em',
                        transition: 'background 0.2s, transform 0.1s',
                      }}>
                        {addedId === p.id ? '✔ ADDED!' : '🛒 ADD'}
                      </button>
                    </div>
                  ))}
                </div>
              </Panel>

              {/* Comments */}
              <Panel title="💬 Comments" theme={theme}
                extra={<span style={{ fontSize: '0.75em', color: theme.secondColor }}>{store.comments.length} comments</span>}>
                {store.comments.map((c, i) => (
                  <div key={i} style={{
                    display: 'flex', gap: 8, marginBottom: 10,
                    paddingBottom: 8, borderBottom: i < store.comments.length - 1 ? `1px dashed ${theme.tableBorderColor}` : 'none',
                  }}>
                    <img src={c.avatar} alt={c.user}
                      style={{ width: 36, height: 36, border: `2px solid ${theme.accentColor}`, flexShrink: 0 }} />
                    <div style={{ flex: 1 }}>
                      <div style={{ fontSize: '0.78em' }}>
                        <span style={{ color: theme.accentColor, fontWeight: 'bold' }}>{c.user}</span>
                        <span style={{ color: '#666', marginLeft: 6 }}>{c.time}</span>
                      </div>
                      <div style={{ fontSize: '0.82em', color: theme.textColor, marginTop: 2, lineHeight: 1.4 }}>{c.text}</div>
                    </div>
                  </div>
                ))}
                {/* Leave a comment */}
                <div style={{ marginTop: 8 }}>
                  <b style={{ color: theme.secondColor, fontSize: '0.8em' }}>Leave a Comment:</b>
                  <textarea placeholder="Type ur comment here!! xD" rows={2}
                    style={{ width: '100%', background: '#000', color: theme.textColor,
                      border: `1px solid ${theme.tableBorderColor}`, fontFamily: FONT_MAP[theme.font],
                      fontSize: '0.8em', padding: 6, marginTop: 4, resize: 'none' }} />
                  <button style={{
                    background: theme.accentColor, border: 'none', color: '#fff', marginTop: 4,
                    fontFamily: FONT_MAP[theme.font], fontWeight: 'bold', padding: '4px 16px',
                    cursor: 'pointer', fontSize: '0.8em', boxShadow: `0 0 8px ${theme.accentColor}`,
                  }}>POST COMMENT ✨</button>
                </div>
              </Panel>
            </div>
          </div>

          {/* ── FOOTER ── */}
          <div style={{ textAlign: 'center', padding: '16px 0', borderTop: `2px solid ${theme.tableBorderColor}`,
            marginTop: 16, fontSize: '0.75em' }}>
            <GlitterText>{store.name} © {new Date().getFullYear()}</GlitterText>
            <div style={{ color: '#555', marginTop: 4 }}>
              Powered by{' '}
              <span style={{ color: theme.accentColor }}>RareImagery X Marketplace</span>
              {' '}· Best viewed in 800x600
            </div>
          </div>
        </div>
      </div>
    </>
  )
}
