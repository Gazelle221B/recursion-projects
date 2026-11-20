# recursion-projects

Project submissions for the Recursion (recursionist.io) courses.

## Contents

### course46-developing-applications — Developing Applications Project
- `guess.py` — Guess the Number Game (1–100, with hints and attempt count)
- `file_manipulator.py` — CLI file manipulator (reverse / copy / duplicate-contents / replace-string)
- `md_to_html.py` — Markdown to HTML converter (uses the `markdown` library)

### course44-html-css-tailwind — HTML / CSS / Tailwind final project
- `index.html` — "Tabita" landing page (header, hero, features, pricing, FAQ, footer; Tailwind CSS via CDN, mobile-first)

### course31-os-networks — Operating Systems & Networks Project
- `local-chat-messenger/` — UDP local chat messenger (Python, AF_UNIX datagram + faker)
- `rpc/` — RPC implementation (Python server, Node.js client, JSON protocol)
- `online-chat-messenger/` — Stage 1: UDP relay chat / Stage 2: TCP room create & join with tokens + UDP chat
- `video-compressor-service/` — Stage 1: TCP mp4 upload / Stage 2: ffmpeg-based video processing protocol (compress, resize, aspect, mp3, gif, webm)

### course33-server-deployment — Server Deployment Project
- `resume-website/` — Static resume / portfolio website (home, portfolio, resume pages; projects rendered from `data/projects.json`)

### course34-server-side — Server Side Development Project (PHP)
- `food-service-simulation/` — Restaurant order simulation with PHP OOP (FoodItem / Person / Restaurant, Invoice)
- `user-lorem-ipsum/` — Random user generator with HTML / Markdown / JSON / text output and a downloadable form
- `restaurant-chain-mockup/` — Restaurant chain mockup (FileConvertible classes) plus a customizable download extension
- `markdown-to-html/` — Web app that converts Markdown to HTML (Monaco editor, display or download)
- `plantuml-server/` — PlantUML text-to-diagram service (SVG / PNG / ASCII, cheat sheet, practice prompts)

### course35-backend-design — Backend Design & Security Project (PHP)
- `text-snippet-service/` — Pastebin-style snippet sharing (unique URLs, syntax highlighting, expiry with auto-deletion)
- `image-hosting-service/` — Imgur-style image hosting (unique + delete URLs, view counter, per-IP rate limit, 30-day retention cleanup)
- `imageboard/` — Anonymous thread-based image board (PostDAO pattern, self-referencing threads/replies, hashed image names with thumbnails, seeder)
- `email-verification-system/` — Computer-parts app extension with signed-URL email verification (HMAC signature + expiry middleware, resend / change-email flows, PHPMailer-style mailer)
- `social-networking-service/` — Twitter-like SNS on a small own framework (profiles, follows, ≤255-char posts, likes, nested replies, scheduled posts via cron, OpenSSL-encrypted DMs, notifications, Trending/Following timelines with infinite scroll, seeder)
- `ecommerce-stripe/` — E-commerce webapp with Stripe sync (products created in Stripe and synced as drafts via signed webhooks, admin publish flow, cart, guest/member checkout, ratings, favorites, public wishlist, tracking numbers, buyer-only digital downloads; documented demo mode when no Stripe keys are set)

### course36-react — React (Slack Clone)
- `slack-clone/` — Slack-style team chat app (React + TypeScript + Vite, Redux Toolkit, Firebase Authentication with Google sign-in, Cloud Firestore for users / channels / messages with real-time updates, Material UI + Tailwind CSS). Firebase config is read from environment variables (see `.env.example`); set up your own Firebase project to run it.
