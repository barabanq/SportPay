<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\ProfileView;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProfileAnalyticsService
{
    public const DEDUP_WINDOW_MINUTES = 30;

    public function recordView(
        User $profileUser,
        ?User $viewer = null,
        ?Request $request = null,
        string $source = 'profile_page'
    ): ?ProfileView {
        if (! Schema::hasTable('profile_views')) {
            return null;
        }

        if ($viewer !== null && (int) $viewer->id === (int) $profileUser->id) {
            return null;
        }

        $resolvedSource = $this->resolveSource($request, $source);
        $ipHash = $this->makeFingerprint($request, $viewer);

        $recentExists = ProfileView::query()
            ->where('profile_user_id', $profileUser->id)
            ->where('source', $resolvedSource)
            ->where('created_at', '>=', now()->subMinutes(self::DEDUP_WINDOW_MINUTES))
            ->where(function ($q) use ($viewer, $ipHash): void {
                if ($viewer !== null) {
                    $q->where('viewer_id', $viewer->id);
                } else {
                    $q->where('ip_hash', $ipHash);
                }
            })
            ->exists();

        if ($recentExists) {
            return null;
        }

        return ProfileView::create([
            'profile_user_id' => $profileUser->id,
            'viewer_id'       => $viewer?->id,
            'source'          => $resolvedSource,
            'ip_hash'         => $ipHash,
        ]);
    }

    public function getDashboardStats(User $user): array
    {
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();
        $prevSevenDaysStart = $now->copy()->subDays(13)->startOfDay();
        $prevSevenDaysEnd = $now->copy()->subDays(7)->endOfDay();
        $thirtyDaysAgo = $now->copy()->subDays(29)->startOfDay();

        $baseQuery = ProfileView::where('profile_user_id', $user->id);

        $viewsToday = (clone $baseQuery)->where('created_at', '>=', $todayStart)->count();
        $views7d = (clone $baseQuery)->where('created_at', '>=', $sevenDaysAgo)->count();
        $viewsPrev7d = (clone $baseQuery)->whereBetween('created_at', [$prevSevenDaysStart, $prevSevenDaysEnd])->count();
        $views30d = (clone $baseQuery)->where('created_at', '>=', $thirtyDaysAgo)->count();
        $viewsTotal = (clone $baseQuery)->count();

        $uniqueVisitors30d = (clone $baseQuery)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->get(['viewer_id', 'ip_hash'])
            ->map(fn (ProfileView $v) => $v->viewer_id ? 'u_' . $v->viewer_id : 'ip_' . $v->ip_hash)
            ->unique()
            ->count();

        if ($viewsPrev7d > 0) {
            $trend7dPercent = (int) round((($views7d - $viewsPrev7d) / $viewsPrev7d) * 100);
        } else {
            $trend7dPercent = $views7d > 0 ? 100 : 0;
        }

        $orders30d = Order::where('seller_id', $user->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $conversionRate = $uniqueVisitors30d > 0
            ? round(min(100.0, ($orders30d / $uniqueVisitors30d) * 100), 1)
            : 0.0;

        $start14d = $now->copy()->subDays(13)->startOfDay();
        $recentViews = (clone $baseQuery)
            ->where('created_at', '>=', $start14d)
            ->get(['created_at', 'viewer_id', 'ip_hash']);

        $groupedByDay = $recentViews->groupBy(
            fn (ProfileView $v) => $v->created_at?->format('Y-m-d') ?? $now->format('Y-m-d')
        );

        $dailyChart = [];
        $maxDailyViews = 1;

        for ($i = 13; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $key = $day->format('Y-m-d');
            $dayItems = $groupedByDay->get($key, collect());
            $total = $dayItems->count();
            $uniques = $dayItems
                ->map(fn (ProfileView $v) => $v->viewer_id ? 'u_' . $v->viewer_id : 'ip_' . $v->ip_hash)
                ->unique()
                ->count();

            if ($total > $maxDailyViews) {
                $maxDailyViews = $total;
            }

            $dailyChart[] = [
                'date'    => $key,
                'label'   => $day->format('d.m'),
                'day_ru'  => match ($day->dayOfWeekIso) {
                    1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', default => 'Вс',
                },
                'total'   => $total,
                'uniques' => $uniques,
            ];
        }

        foreach ($dailyChart as &$point) {
            $point['height_pct'] = $point['total'] > 0
                ? max(10, (int) round(($point['total'] / $maxDailyViews) * 100))
                : 4;
        }
        unset($point);

        $sourcesRaw = (clone $baseQuery)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->pluck('total', 'source')
            ->all();

        $sourcesBreakdown = [];
        $totalForSources = max(1, array_sum($sourcesRaw));
        foreach ($sourcesRaw as $src => $cnt) {
            $sourcesBreakdown[] = [
                'source'  => (string) $src,
                'label'   => ProfileView::sourceLabel((string) $src),
                'count'   => (int) $cnt,
                'percent' => (int) round(((int) $cnt / $totalForSources) * 100),
            ];
        }

        $recentVisitors = ProfileView::with('viewer')
            ->where('profile_user_id', $user->id)
            ->latest('id')
            ->limit(25)
            ->get();

        return [
            'views_today'         => $viewsToday,
            'views_7d'            => $views7d,
            'views_prev_7d'       => $viewsPrev7d,
            'trend_7d_percent'    => $trend7dPercent,
            'views_30d'           => $views30d,
            'views_total'         => $viewsTotal,
            'unique_visitors_30d' => $uniqueVisitors30d,
            'orders_30d'          => $orders30d,
            'conversion_rate'     => $conversionRate,
            'daily_chart'         => $dailyChart,
            'sources_breakdown'   => $sourcesBreakdown,
            'recent_visitors'     => $recentVisitors,
        ];
    }

    private function resolveSource(?Request $request, string $default): string
    {
        if ($request === null) {
            return $default;
        }

        $explicit = (string) $request->query('from', '');
        if (in_array($explicit, ['profile_page', 'seller_modal', 'lot_page', 'order_page', 'catalog'], true)) {
            return $explicit;
        }

        $referer = (string) $request->headers->get('referer', '');
        if (str_contains($referer, '/lots/')) {
            return 'lot_page';
        }
        if (str_contains($referer, '/orders/')) {
            return 'order_page';
        }
        if (str_contains($referer, 'seller=')) {
            return 'catalog';
        }

        return $default;
    }

    private function makeFingerprint(?Request $request, ?User $viewer): string
    {
        $ip = $request?->ip() ?? '127.0.0.1';
        $ua = substr((string) ($request?->userAgent() ?? 'cli'), 0, 120);
        $uid = $viewer?->id ?? 'guest';

        return hash('sha256', "{$ip}|{$ua}|{$uid}");
    }
}