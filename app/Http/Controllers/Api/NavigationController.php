<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = str_contains(strtolower((string) $user?->email), 'admin');

        return response()->json([
            'data' => [
                [
                    'label' => 'Dashboard',
                    'href' => '/dashboard',
                    'permission' => 'viewDashboard',
                ],
                [
                    'label' => 'Offers',
                    'href' => '/offers',
                    'permission' => 'viewOffers',
                ],
                [
                    'label' => 'Marketplace',
                    'href' => '/marketplace',
                    'permission' => 'viewMarketplace',
                ],
                [
                    'label' => 'Communities',
                    'href' => '/communities',
                    'permission' => 'viewCommunities',
                ],
                [
                    'label' => 'Capital',
                    'href' => '/capital',
                    'permission' => 'viewCapital',
                ],
                [
                    'label' => 'Referrals',
                    'href' => '/referrals',
                    'permission' => 'viewReferrals',
                ],
                [
                    'label' => 'Settings',
                    'href' => '/settings',
                    'permission' => 'viewSettings',
                ],
                ...($isAdmin ? [[
                    'label' => 'Admin',
                    'href' => '/admin',
                    'permission' => 'adminPanel',
                ]] : []),
            ],
        ]);
    }

    public function liveFeed(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'type' => 'offer',
                    'text' => 'AffiDeck is booting its first API slice.',
                    'link' => null,
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'AffiDeck is going to be the ultimate affiliate marketing platform.',
                    'link' => 'https://affideck.lovable.app',
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'AffiDeck is launching soon. Stay tuned!',
                    'link' => null,
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'Join our Discord community to get the latest updates and connect with other affiliates.',
                    'link' => 'https://discord.gg/affideck',
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'Follow us on Twitter for tips, news, and exclusive offers.',
                    'link' => 'https://twitter.com/affideck',
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'Check out our blog for in-depth articles on affiliate marketing strategies and best practices.',
                    'link' => 'https://affideck.lovable.app/blog',
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'We are hiring! Visit our careers page to see open positions and join our team.',
                    'link' => 'https://affideck.lovable.app/careers',
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'The future of affiliate marketing is here.',
                    'link' => null,
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'AffiDeck is committed to providing the best experience for affiliates and advertisers alike.',
                    'link' => null,
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'Get ready to take your affiliate marketing game to the next level with AffiDeck.',
                    'link' => null,
                    'ts' => now()->toISOString(),
                ],
                [
                    'type' => 'offer',
                    'text' => 'AffiDeck is more than just a platform - it\'s a community of passionate affiliates and advertisers.',
                    'link' => null,
                    'ts' => now()->toISOString(),
                ],
                // this is for the developer
                [
                    'type' => 'offer',
                    'text' => 'The dev is "Emmanuel | C\'est Bro Code" - a passionate software engineer and affiliate marketer with a vision to revolutionize the industry.',
                    'link' => null,
                    'ts' => now()->toISOString(),
                ]
            ],
            'meta' => [
                'page' => 1,
                'per_page' => 20,
                'total' => 1,
            ],
        ]);
    }
}