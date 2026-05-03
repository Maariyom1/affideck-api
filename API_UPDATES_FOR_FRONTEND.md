# AffiDeck API Updates — Frontend Integration Guide

This document summarizes all backend updates implemented on May 2–3, 2026 so the frontend team can integrate quickly.

**Quick status**: new endpoints implemented and tested; migrations applied. Run the test suite locally with `php artisan test`.

**Auth**
- Login: `POST /api/auth/login` — returns `access_token`, `refresh_token`, `user`.
- Use header: `Authorization: Bearer <access_token>` for protected routes.

**New / Updated Endpoints (high level)**

- Offers
  - `POST /api/offers/{id}/request-approval` — Authenticated. Creates an approval request. Response: 201 with approval record `{id, offer_id, requested_by, requested_at, status}`.
  - `POST /api/offers/{id}/favorite` — Authenticated. Persists favorite; response 201 with favorite record.

- Communities
  - `GET /api/communities` — list (paginated)
  - `GET /api/communities/{id}` — detail
  - `POST /api/communities` — create
  - `PATCH /api/communities/{id}` — update (owner/mod)
  - `POST /api/communities/{id}/join` — join community
  - `POST /api/communities/{id}/members/{userId}/role` — change member role
  - `GET /api/communities/{id}/posts` — posts list
  - `POST /api/communities/{id}/posts` — create post

- Capital
  - `GET /api/capital/eligibility` — checks eligibility (stub rule: account age > 7 days)
  - `POST /api/capital/applications` — create application
  - `GET /api/capital/applications` — list user's applications
  - `GET /api/capital/applications/{id}` — show
  - `PATCH /api/capital/applications/{id}` — update (user/admin policy)

- Referrals
  - `GET /api/referrals` — list
  - `GET /api/referrals/share-link` — get or create referral code + link
  - `POST /api/referrals/share` — log share (stub)
  - `GET /api/referrals/conversions` — list conversions
  - `GET /api/referrals/commissions` — aggregated commissions

- Settings & KYC
  - `GET /api/settings` — user settings
  - `PATCH /api/settings/account` — update preferences
  - `PATCH /api/settings/payment-methods` — update payment methods
  - `GET /api/settings/payouts` — list payouts (stub)
  - `POST /api/settings/verify-identity` — starts KYC; currently a mock service. Returns `202` and stores KYC info in settings.preferences. Example response: `{ data: { status: 'started', id: 'mock_xxx' } }`.

- Uploads
  - `POST /api/uploads/sign` — returns a mock signed URL in dev `{ data: { signed_url: '...' } }`.
  - `POST /api/uploads` — server upload (multipart `file`)
  - `DELETE /api/uploads/{id}` — delete (owner-only)

Notes about behavior and shape
- All paginated responses follow `{ data: [...], meta: { current_page, per_page, total, last_page } }`.
- Error responses follow standard Laravel JSON: validation 422 with `errors`, auth failures 401/403 with `message`.
- Offer approval flow: when a user calls `POST /api/offers/{id}/request-approval`, an approval record is created in `offer_approvals` and the offer `status` becomes `pending_approval`.
- Favorites: `offer_favorites` table persists pairs `{offer_id, user_id}`. There is no `DELETE` unfavorite endpoint yet — can be added on request.

Integration / Provider Notes
- KYC
  - Dev/test: mock KYC provider is used. To enable mock explicitly, set `KYC_PROVIDER=mock` in `.env` or `services.kyc.provider=mock` in runtime config.
  - Production: integrate Stripe Identity or Persona; backend `app/Services/KycService.php` is the integration point.

- Payments (Stripe)
  - `app/Services/PaymentService.php` contains a stub for creating payment intents. To enable Stripe, set `STRIPE_SECRET` and implement SDK calls in that service.

- Uploads (S3)
  - `UploadController::sign()` returns a mock signed URL. For S3 presigned URLs, configure `filesystems.disks.s3` and replace the stub in `UploadController` to use `Storage::disk('s3')->temporaryUrl(...)`.

- Search (Algolia)
  - `app/Services/SearchService.php` is a placeholder. To use Algolia/Scout, set `ALGOLIA_APP_ID` and `ALGOLIA_API_KEY` and implement the search logic there.

Files to review (backend locations)
- Controllers (business logic)
  - [app/Http/Controllers/Api/OfferController.php](app/Http/Controllers/Api/OfferController.php)
  - [app/Http/Controllers/Api/CommunityController.php](app/Http/Controllers/Api/CommunityController.php)
  - [app/Http/Controllers/Api/ReferralController.php](app/Http/Controllers/Api/ReferralController.php)
  - [app/Http/Controllers/Api/UploadController.php](app/Http/Controllers/Api/UploadController.php)
  - [app/Http/Controllers/Api/SettingsController.php](app/Http/Controllers/Api/SettingsController.php)

- Services
  - [app/Services/KycService.php](app/Services/KycService.php)
  - [app/Services/PaymentService.php](app/Services/PaymentService.php)
  - [app/Services/SearchService.php](app/Services/SearchService.php)

- New migrations/tables (DB)
  - [database/migrations/2026_05_02_000014_create_offer_favorites_table.php](database/migrations/2026_05_02_000014_create_offer_favorites_table.php)
  - [database/migrations/2026_05_02_000015_create_offer_approvals_table.php](database/migrations/2026_05_02_000015_create_offer_approvals_table.php)
  - plus earlier-created community/capital/referral/upload/settings migrations (see `database/migrations/`).

Testing
- New feature tests added: `tests/Feature/NewEndpointsFeatureTest.php` (covers approval, favorites, KYC, uploads sign, referrals & community flows).
- Existing test suites still pass. To run tests:
```bash
php artisan test
```

Quick examples
- Example: request approval (frontend)
```bash
curl -X POST "/api/offers/123/request-approval" -H "Authorization: Bearer $ACCESS_TOKEN"
```

- Example: favorite an offer
```bash
curl -X POST "/api/offers/123/favorite" -H "Authorization: Bearer $ACCESS_TOKEN"
```

Next recommended frontend tasks
- Wire the new endpoints into UI flows for approval requests and favorites.
- For uploads, implement a two-step flow: call `POST /api/uploads/sign` to obtain a signed URL (when S3 configured) then PUT the file directly to S3; fallback to `POST /api/uploads` for server uploads.
- For KYC, use the `POST /api/settings/verify-identity` endpoint to start verification; poll backend or handle webhook updates once real provider is enabled.

If you want, I can:
- Extend OpenAPI (`openapi.yaml`) with these new endpoints (I can produce a full patch).
- Add admin review endpoints for `offer_approvals` (approve/deny).
- Add unfavorite and favorites listing endpoints.

If you prefer, I will now update `openapi.yaml` to include all these endpoints — say "Update OpenAPI" and I'll patch it next.

---
Generated: 2026-05-03
