<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProfileView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_another_user_profile_records_view_and_deduplicates(): void
    {
        $seller = User::factory()->create(['username' => 'Coach_Alex']);
        $visitor = User::factory()->create(['username' => 'Runner_Max']);

        $this->actingAs($visitor)->get(route('users.show', $seller))->assertOk();
        $this->assertDatabaseCount('profile_views', 1);

        $this->actingAs($visitor)->get(route('users.show', $seller))->assertOk();
        $this->assertDatabaseCount('profile_views', 1);

        $this->actingAs($visitor)->getJson(route('users.modal', $seller))->assertOk();
        $this->assertDatabaseCount('profile_views', 2);
    }

    public function test_viewing_own_profile_does_not_record_view(): void
    {
        $seller = User::factory()->create(['username' => 'Coach_Self']);
        $this->actingAs($seller)->get(route('users.show', $seller))->assertOk();
        $this->assertDatabaseCount('profile_views', 0);
    }

    public function test_analytics_dashboard_renders_visitors_and_stats(): void
    {
        $seller = User::factory()->create(['username' => 'Coach_Pro']);
        $visitor = User::factory()->create(['username' => 'Athlete_One']);

        $this->actingAs($visitor)->get(route('users.show', $seller));

        $this->actingAs($seller)
            ->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Аналитика просмотров профиля')
            ->assertSee('Athlete_One');

        $this->assertSame(1, ProfileView::where('profile_user_id', $seller->id)->count());
    }
}