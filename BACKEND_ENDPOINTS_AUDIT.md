# AffiDeck Backend Endpoints Audit

This is a revised, action-ready backend handoff for a Laravel backend. It translates frontend flows into concrete API responsibilities, including required endpoints, auth rules, sample request/response shapes, webhooks, and external integrations.

Goal: give your backend team a clear checklist for implementing server-side resources and the external APIs you'll need to integrate.

---

## How to read this document
- Each section lists the primary endpoints, the intended auth (public / auth / admin), brief request/response examples, and implementation notes (pagination, filtering, roles).
- If you want, I can convert this into an OpenAPI spec or Postman collection next.

---

## 1) Core Shell / Session (bootstrap)
Purpose: supply the app shell with current user, permissions, nav, ticker and notification counts.

- GET `/api/me` — Authenticated
	- Response: { id, name, email, roles:[], plan, balance, level, streak, unreadCounts: { notifications }, permissions: [] }
	- Notes: include minimal profile and feature flags to drive UI (e.g., canCreateOffers).

- POST `/api/logout` — Authenticated
	- Response: 204

- GET `/api/navigation` — Authenticated
	- Response: nav tree filtered by permissions/plan

- GET `/api/live-feed` — Public / Authenticated variant
	- Response: paginated ticker items [{ type, text, link, ts }]
	- Notes: cache short TTL; offer SSE/WebSocket for live push.

- GET `/api/notifications` — Authenticated
	- Query: `?page=&per_page=&unread_only=`
	- Response: paginated notifications

- PATCH `/api/notifications/{id}` — Authenticated
	- Body: { read: true }

- POST `/api/notifications/mark-all-read` — Authenticated

- GET `/api/notifications/stream` — Authenticated (SSE / WebSocket)
	- Purpose: server push for real-time notification/ticker updates

---

## 2) Auth (required)
Auth flows expected by frontend:

- POST `/api/auth/login` — Public
	- Body: { email, password }
	- Response: { access_token, refresh_token, user }

- POST `/api/auth/register` — Public
	- Body: { name, email, password, referrer_code? }

- POST `/api/auth/refresh` — Authenticated (refresh token)

- POST `/api/auth/password/email`, POST `/api/auth/password/reset` — Public

- POST `/api/auth/oauth/{provider}` — Public
	- For social login (Google, Apple, Facebook). Return same tokens.

Auth notes:
- Use JWT or Laravel Sanctum; support access+refresh tokens for long sessions.
- Expose role/scopes in `/api/me`.

---

## 3) Dashboard / Analytics (data endpoints)

- GET `/api/dashboard/summary` — Authenticated
	- Response: { earnings, clicks, conversions, epc, delta24h, balance }

- GET `/api/dashboard/chart` — Authenticated
	- Query: `?range=24h|7d|30d|90d` -> return timeseries buckets

- GET `/api/dashboard/top-offers` — Authenticated

- POST `/api/dashboard/export` — Authenticated
	- Body: { range, metrics } => enqueue export job, return job id

Notes: analytics can be backed by event store/warehouse; consider async aggregations & caching.

---

## 4) Offers (CRUD + tracking)
Key frontend flows: list, details modal, create/edit, request approval, tracking link generation, favorite.

- GET `/api/offers` — Public (with authenticated enhancements)
	- Query: `?q=&category=&geo=&sort=&page=`
	- Response: paginated offers with performance summary

- GET `/api/offers/{id}` — Public

- POST `/api/offers` — Authenticated (role: advertiser/partner)
	- Body: { name, type, payout, tags, categories, geo, creatives[] }

- PATCH `/api/offers/{id}` — Authenticated + ownership/policy

- DELETE `/api/offers/{id}` — Authenticated + ownership/admin

- POST `/api/offers/{id}/tracking-link` — Authenticated
	- Response: { tracking_url }

- POST `/api/offers/{id}/request-approval` — Authenticated

- POST `/api/offers/{id}/favorite` — Authenticated

- GET `/api/offers/{id}/analytics` — Authenticated

Data shape notes: include ownerId, status (draft/published/archived), approval history, defaults for geo and caps.

---

## 5) Marketplace (listings + orders)

- GET `/api/marketplace/items` — Public
- GET `/api/marketplace/items/{id}` — Public
- POST `/api/marketplace/items` — Authenticated (seller)
- PATCH `/api/marketplace/items/{id}` — Authenticated + owner
- DELETE `/api/marketplace/items/{id}` — Authenticated + owner
- POST `/api/marketplace/items/{id}/buy` — Authenticated
	- Body: { payment_method_id, qty, shipping? } -> Creates order, returns payment intent/order id

- GET `/api/marketplace/orders` — Authenticated (buyer/seller roles filter)

Notes: integrate payments provider (Stripe/PayPal) for checkout and use webhooks for order status updates.

---

## 6) Communities (membership + content)

- GET `/api/communities` — Public
- GET `/api/communities/{id}` — Public (with member flags)
- POST `/api/communities` — Authenticated
- PATCH `/api/communities/{id}` — Authenticated + owner/mod
- POST `/api/communities/{id}/join` — Authenticated
- POST `/api/communities/{id}/members/{userId}/role` — Authenticated + mod/admin
- GET `/api/communities/{id}/posts` — Public / Auth
- POST `/api/communities/{id}/posts` — Authenticated

Notes: consider pagination for feeds and moderation workflows (reports, remove post).

---

## 7) Capital (applications / approvals)

- GET `/api/capital/eligibility` — Authenticated
- POST `/api/capital/applications` — Authenticated
- GET `/api/capital/applications` — Authenticated
- GET `/api/capital/applications/{id}` — Authenticated
- PATCH `/api/capital/applications/{id}` — Authenticated + allowed

Notes: fee calculation, review state machine (submitted -> under_review -> approved -> funded -> repaying), and webhooks to payment provider for disbursement.

---

## 8) Referrals

- GET `/api/referrals` — Authenticated
- GET `/api/referrals/share-link` — Authenticated
- POST `/api/referrals/share` — Authenticated
- GET `/api/referrals/conversions` — Authenticated
- GET `/api/referrals/commissions` — Authenticated

Notes: store referral metadata and attribution windows; exportable reports.

---

## 9) Notifications & Real-time

- GET `/api/notifications` — Authenticated
- PATCH `/api/notifications/{id}` — Authenticated
- POST `/api/notifications/mark-all-read` — Authenticated
- GET `/api/notifications/stream` — Authenticated (SSE/WebSocket)

Implementation: use Laravel broadcasting (Pusher / Redis / WebSockets) or SSE endpoint for light real-time.

---

## 10) Settings, Billing, KYC

- GET `/api/settings` — Authenticated
- PATCH `/api/settings/account` — Authenticated
- PATCH `/api/settings/payment-methods` — Authenticated
- GET `/api/settings/payouts` — Authenticated
- POST `/api/settings/verify-identity` — Authenticated

Notes: Payment/Billing provider required (Stripe recommended), KYC via provider (Stripe Identity or Persona). Tax document generation may use templating and signed PDFs.

---

## 11) CMS / Public Content

Serve marketing content from stable endpoints or a small CMS table:
- GET `/api/content/{slug}` — Public
- GET `/api/blog` and `/api/blog/{slug}` — Public
- POST `/api/contact` — Public (anti-spam required)

Notes: CMS should version legal pages and allow previewing drafts.

---

## 12) Search

- GET `/api/search` — Public
- GET `/api/search/suggestions` — Public

Notes: use Algolia or Elastic for fast cross-entity queries and typeahead.

---

## 13) Uploads / Media

- POST `/api/uploads/sign` — Authenticated
	- Body: { filename, contentType } => returns signed URL for direct upload to S3/Cloudinary

- POST `/api/uploads` — Authenticated (server upload / small files)
- DELETE `/api/uploads/{id}` — Authenticated + owner

Notes: recommend direct-to-cloud uploads and storing metadata in DB.

---

## 14) Data models (recommended resources)
- users, sessions, offers, offer_favorites, offer_approvals, offer_links, marketplace_items, marketplace_orders, communities, community_members, community_posts, capital_applications, referrals, referral_commissions, notifications, settings, tax_documents, integrations, courses, lessons, uploads, cms_pages, blog_posts, jobs

---

## External APIs & Integrations (required / recommended)
This section answers your question: "Are there going to be external APIs?" — Yes. Below are integrations you will almost certainly need and why.

- Payments & Payouts: Stripe (payments + Connect) or PayPal
	- Use: process buyer payments, create payouts to sellers/affiliates, create payment intents, webhooks for payment status.

- Email Delivery & Templates: Postmark / SendGrid / Mailgun
	- Use: transactional emails (verify, reset, receipts), inbound webhooks for bounces.

- File Storage / CDN: AWS S3 + CloudFront, or Cloudinary
	- Use: direct uploads (signed URLs), image transformations, public URLs.

- Video Hosting / Streaming (if video courses): Mux or Cloudflare Stream

- Search: Algolia (hosted) or Elasticsearch
	- Use: cross-entity search & typeahead suggestions.

- SMS & Phone: Twilio
	- Use: OTP, alerts, two-factor, campaign messages.

- Identity / KYC: Stripe Identity, Persona, or Sumsub
	- Use: verify identity for payouts / capital onboarding.

- Bank Linking / Verification: Plaid (US) or SaltEdge
	- Use: verify bank accounts for payouts / faster onboarding.

- Payments Webhooks: Stripe webhooks for payment intents, transfers, chargebacks.

- Analytics: Segment (collect), Amplitude (behavior), or GA4
	- Use: event tracking, funnel analytics, marketing attribution.

- Anti-spam / Bot protection: reCAPTCHA or hCaptcha
	- Use: contact form, signup, heavy action forms.

- Notifications / Push: Firebase Cloud Messaging (FCM) or OneSignal

- CRM / Helpdesk: Intercom, HubSpot, or Zendesk
	- Use: contact form routing, user conversation history.

Notes: decide which providers early; each provider adds webhooks and secret handling into your Laravel config.

---

## Webhooks & Background Jobs
- Stripe webhooks: handle `payment_intent.succeeded`, `charge.refunded`, `checkout.session.completed`, `transfer.created`.
- Uploads: storage provider callbacks (if any).
- Messaging: Twilio status callbacks.
- Use queued jobs for heavy tasks (exports, PDF gen, image processing, report generation).

---

## Security, Pagination & Operational notes
- All list endpoints support `?page=&per_page=` and should return paginated meta.
- Use rate limiting on search, contact, and public endpoints.
- Store secrets in env; rotate webhooks signing keys; expose no sensitive data via `/api/me`.
- Add audit trails for critical flows (offer approvals, payouts, capital decisions).

---

## Priority implementation roadmap (recommended)
1. Auth/session bootstrap, `/api/me`, and nav — critical for dev speed.
2. Offers CRUD + tracking links + analytics endpoints.
3. Notifications + WebSocket/SSE for live UX.
4. Marketplace orders + payments integration (Stripe) and webhooks.
5. Uploads (signed URLs) + CMS endpoints for public pages.
6. Capital & referrals (as business rules are finalized).

---

## Next steps I can take for you
- Generate an OpenAPI (YAML) draft for the core routes (auth, offers, marketplace, uploads).
- Produce a Postman collection or example Laravel controllers + request DTOs for the top-priority endpoints.

Which of the above do you want me to generate next? (OpenAPI, Postman, or Laravel controller stubs)

