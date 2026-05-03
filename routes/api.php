<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NavigationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('password/email', [AuthController::class, 'sendPasswordResetLink']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);
    Route::post('oauth/{provider}', [AuthController::class, 'oauth']);
});

// Public routes
Route::get('live-feed', [NavigationController::class, 'liveFeed']);
Route::get('offers', [OfferController::class, 'index']);
Route::get('offers/{offerId}', [OfferController::class, 'show']);
Route::get('marketplace/items', [MarketplaceController::class, 'items']);
Route::get('marketplace/items/{itemId}', [MarketplaceController::class, 'showItem']);
Route::get('search', [SearchController::class, 'index']);
Route::get('search/suggestions', [SearchController::class, 'suggestions']);
Route::get('content/{slug}', [ContentController::class, 'page']);
Route::get('blog', [ContentController::class, 'blog']);
Route::get('blog/{slug}', [ContentController::class, 'blogPost']);
Route::post('contact', [ContentController::class, 'contact']);

// Protected routes
Route::middleware('api.token')->group(function (): void {
    Route::get('me', MeController::class);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('navigation', NavigationController::class);
    
    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{notificationId}', [NotificationController::class, 'update']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::get('notifications/stream', [NotificationController::class, 'stream']);
    
    // Offers (authenticated user actions)
    Route::post('offers', [OfferController::class, 'store']);
    Route::patch('offers/{offerId}', [OfferController::class, 'update']);
    Route::delete('offers/{offerId}', [OfferController::class, 'destroy']);
    Route::post('offers/{offerId}/tracking-link', [OfferController::class, 'trackingLink']);
    Route::get('offers/{offerId}/analytics', [OfferController::class, 'analytics']);
    
    // Dashboard
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('dashboard/chart', [DashboardController::class, 'chart']);
    Route::get('dashboard/top-offers', [DashboardController::class, 'topOffers']);
    Route::post('dashboard/export', [DashboardController::class, 'export']);
    
    // Marketplace
    Route::post('marketplace/items', [MarketplaceController::class, 'storeItem']);
    Route::patch('marketplace/items/{itemId}', [MarketplaceController::class, 'updateItem']);
    Route::delete('marketplace/items/{itemId}', [MarketplaceController::class, 'destroyItem']);
    Route::post('marketplace/items/{itemId}/buy', [MarketplaceController::class, 'buyItem']);
    Route::get('marketplace/orders', [MarketplaceController::class, 'orders']);
    
    // Offers: additional actions
    Route::post('offers/{offerId}/request-approval', [OfferController::class, 'requestApproval']);
    Route::post('offers/{offerId}/favorite', [OfferController::class, 'favorite']);

    // Communities
    Route::get('communities', [\App\Http\Controllers\Api\CommunityController::class, 'index']);
    Route::get('communities/{id}', [\App\Http\Controllers\Api\CommunityController::class, 'show']);
    Route::post('communities', [\App\Http\Controllers\Api\CommunityController::class, 'store']);
    Route::patch('communities/{id}', [\App\Http\Controllers\Api\CommunityController::class, 'update']);
    Route::post('communities/{id}/join', [\App\Http\Controllers\Api\CommunityController::class, 'join']);
    Route::post('communities/{id}/members/{userId}/role', [\App\Http\Controllers\Api\CommunityController::class, 'setMemberRole']);
    Route::get('communities/{id}/posts', [\App\Http\Controllers\Api\CommunityController::class, 'posts']);
    Route::post('communities/{id}/posts', [\App\Http\Controllers\Api\CommunityController::class, 'storePost']);

    // Capital
    Route::get('capital/eligibility', [\App\Http\Controllers\Api\CapitalController::class, 'eligibility']);
    Route::post('capital/applications', [\App\Http\Controllers\Api\CapitalController::class, 'store']);
    Route::get('capital/applications', [\App\Http\Controllers\Api\CapitalController::class, 'index']);
    Route::get('capital/applications/{id}', [\App\Http\Controllers\Api\CapitalController::class, 'show']);
    Route::patch('capital/applications/{id}', [\App\Http\Controllers\Api\CapitalController::class, 'update']);

    // Referrals
    Route::get('referrals', [\App\Http\Controllers\Api\ReferralController::class, 'index']);
    Route::get('referrals/share-link', [\App\Http\Controllers\Api\ReferralController::class, 'shareLink']);
    Route::post('referrals/share', [\App\Http\Controllers\Api\ReferralController::class, 'share']);
    Route::get('referrals/conversions', [\App\Http\Controllers\Api\ReferralController::class, 'conversions']);
    Route::get('referrals/commissions', [\App\Http\Controllers\Api\ReferralController::class, 'commissions']);

    // Settings
    Route::get('settings', [\App\Http\Controllers\Api\SettingsController::class, 'show']);
    Route::patch('settings/account', [\App\Http\Controllers\Api\SettingsController::class, 'updateAccount']);
    Route::patch('settings/payment-methods', [\App\Http\Controllers\Api\SettingsController::class, 'updatePaymentMethods']);
    Route::get('settings/payouts', [\App\Http\Controllers\Api\SettingsController::class, 'payouts']);
    Route::post('settings/verify-identity', [\App\Http\Controllers\Api\SettingsController::class, 'verifyIdentity']);

    // Uploads
    Route::post('uploads/sign', [\App\Http\Controllers\Api\UploadController::class, 'sign']);
    Route::post('uploads', [\App\Http\Controllers\Api\UploadController::class, 'store']);
    Route::delete('uploads/{id}', [\App\Http\Controllers\Api\UploadController::class, 'destroy']);

    // Admin: Offer approvals
    Route::prefix('admin')->group(function (): void {
        Route::get('offer-approvals', [\App\Http\Controllers\Api\Admin\OfferApprovalController::class, 'index']);
        Route::patch('offer-approvals/{approvalId}/approve', [\App\Http\Controllers\Api\Admin\OfferApprovalController::class, 'approve']);
        Route::patch('offer-approvals/{approvalId}/deny', [\App\Http\Controllers\Api\Admin\OfferApprovalController::class, 'deny']);
    });
});