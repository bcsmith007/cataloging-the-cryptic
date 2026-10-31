<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Constraint;
use App\Models\Puzzle;
use App\Models\Video;
use App\Services\CatalogStatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogStatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the /stats page returns 200 OK and contains expected analytics sections.
     */
    public function test_stats_page_returns_successful_response(): void
    {
        // Create sample videos
        Video::factory()->create([
            'title' => 'Standard Sudoku Masterclass',
            'video_type' => 'youtube',
            'duration_seconds' => 1800,
            'view_count' => 50000,
            'published_at' => Carbon::parse('2024-05-15 12:00:00'),
        ]);

        Video::factory()->create([
            'title' => 'Quick Sudoku Hint #shorts',
            'video_type' => 'youtube_short',
            'duration_seconds' => 45,
            'view_count' => 15000,
            'published_at' => Carbon::parse('2024-06-20 18:00:00'),
        ]);

        Video::factory()->create([
            'title' => 'Marathon Live Stream Replay',
            'video_type' => 'live_stream',
            'duration_seconds' => 7200,
            'view_count' => 30000,
            'published_at' => Carbon::parse('2024-07-10 20:00:00'),
        ]);

        $response = $this->get(route('stats'));

        $response->assertStatus(200);
        $response->assertSee('Catalog Analytics & Trends', false);
        $response->assertSee('Total Views');
        $response->assertSee('Release Volume & Viewership Trends Over Time', false);
        $response->assertSee('Video Format Breakdown');
        $response->assertSee('Duration Sweet Spot Analysis');
        $response->assertSee('Publishing Cadence & Day-of-Week Performance', false);
        $response->assertSee('Most Viewed Video');
        $response->assertSee('Channel Peak Months');
        $response->assertSee('Total Puzzles by Category');
        $response->assertSee('Unclassified');
        $response->assertSee('Standard Solves');
        $response->assertSee('YouTube Shorts');
        $response->assertSee('Live Streams');
        $response->assertSee('Patreon Exclusives');
        $response->assertDontSee('https://cdn.jsdelivr.net');
    }

    /**
     * Test that multi-puzzle videos count each puzzle individually by category,
     * and videos with no puzzles are counted as unclassified.
     */
    public function test_category_chart_aggregates_multiple_puzzles_and_unclassified_videos(): void
    {
        $catVariant = Category::factory()->create(['name' => 'Variant Sudoku']);
        $catGas = Category::factory()->create(['name' => 'GAS']);

        $puzzle1 = Puzzle::factory()->create(['category_id' => $catVariant->id, 'title' => 'Puzzle 1']);
        $puzzle2 = Puzzle::factory()->create(['category_id' => $catGas->id, 'title' => 'Puzzle 2']);
        $puzzle3 = Puzzle::factory()->create(['category_id' => $catVariant->id, 'title' => 'Puzzle 3']);

        // Video A has 2 puzzles (1 Variant + 1 GAS)
        $videoA = Video::factory()->create([
            'title' => 'Double Solve Video',
            'published_at' => Carbon::parse('2024-03-01 12:00:00'),
            'view_count' => 10000,
        ]);
        $videoA->puzzles()->attach($puzzle1->id, ['order_in_video' => 1]);
        $videoA->puzzles()->attach($puzzle2->id, ['order_in_video' => 2]);

        // Video B has 1 puzzle (1 Variant)
        $videoB = Video::factory()->create([
            'title' => 'Single Variant Video',
            'published_at' => Carbon::parse('2024-04-01 12:00:00'),
            'view_count' => 20000,
        ]);
        $videoB->puzzles()->attach($puzzle3->id, ['order_in_video' => 1]);

        // Video C has NO puzzles (Unclassified)
        $videoC = Video::factory()->create([
            'title' => 'Pending Indexing Video',
            'published_at' => Carbon::parse('2024-05-01 12:00:00'),
            'view_count' => 30000,
        ]);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $categoryStats */
        $categoryStats = $response->viewData('categoryStats');
        $this->assertIsArray($categoryStats);

        $variantStat = collect($categoryStats)->firstWhere('name', 'Variant Sudoku');
        $gasStat = collect($categoryStats)->firstWhere('name', 'GAS');
        $unclassifiedStat = collect($categoryStats)->firstWhere('name', 'Unclassified');

        $this->assertNotNull($variantStat);
        $this->assertSame(2, $variantStat['count']); // 2 puzzles across 2 videos
        $this->assertSame(2, $variantStat['distinct_videos']);

        $this->assertNotNull($gasStat);
        $this->assertSame(1, $gasStat['count']); // 1 puzzle from Video A
        $this->assertSame(1, $gasStat['distinct_videos']);

        $this->assertNotNull($unclassifiedStat);
        $this->assertSame(1, $unclassifiedStat['count']); // Video C
        $this->assertTrue($unclassifiedStat['is_unclassified']);

        // Verify yearly progression has entries for 2024
        /** @var array $categoryYearly */
        $categoryYearly = $response->viewData('categoryYearly');
        $year2024 = collect($categoryYearly)->firstWhere('year', '2024');
        $this->assertNotNull($year2024);
        $this->assertSame(2, $year2024['categories']['Variant Sudoku']);
        $this->assertSame(1, $year2024['categories']['GAS']);
        $this->assertSame(1, $year2024['categories']['Unclassified']);

        // Verify monthly progression has entries for March, April, and May 2024
        /** @var array $categoryMonthly */
        $categoryMonthly = $response->viewData('categoryMonthly');
        $this->assertIsArray($categoryMonthly);
        $mar2024 = collect($categoryMonthly)->firstWhere('month', '2024-03');
        $apr2024 = collect($categoryMonthly)->firstWhere('month', '2024-04');
        $may2024 = collect($categoryMonthly)->firstWhere('month', '2024-05');

        $this->assertNotNull($mar2024);
        $this->assertSame(1, $mar2024['categories']['Variant Sudoku']);
        $this->assertSame(1, $mar2024['categories']['GAS']);

        $this->assertNotNull($apr2024);
        $this->assertSame(1, $apr2024['categories']['Variant Sudoku']);

        $this->assertNotNull($may2024);
        $this->assertSame(1, $may2024['categories']['Unclassified']);
    }

    /**
     * Test that duration sweet spot analysis separates YouTube Shorts regardless of length,
     * and groups standard videos < 15 mins into the < 15 mins bracket.
     */
    public function test_duration_sweet_spot_separates_shorts_even_if_longer_than_one_minute(): void
    {
        // 1. YouTube Short that is longer than 60 seconds (75 seconds)
        Video::factory()->create([
            'title' => 'Extended Short #shorts',
            'video_type' => 'youtube_short',
            'duration_seconds' => 75,
            'view_count' => 12000,
            'published_at' => Carbon::parse('2024-08-01 12:00:00'),
        ]);

        // 2. Standard video shorter than 15 minutes (500 seconds)
        Video::factory()->create([
            'title' => 'Short Standard Video',
            'video_type' => 'youtube',
            'duration_seconds' => 500,
            'view_count' => 25000,
            'published_at' => Carbon::parse('2024-08-02 12:00:00'),
        ]);

        // 3. Standard video in 15 - 30 minutes bracket (1200 seconds)
        Video::factory()->create([
            'title' => 'Medium Standard Video',
            'video_type' => 'youtube',
            'duration_seconds' => 1200,
            'view_count' => 45000,
            'published_at' => Carbon::parse('2024-08-03 12:00:00'),
        ]);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $durationBrackets */
        $durationBrackets = $response->viewData('durationBrackets');
        $this->assertIsArray($durationBrackets);

        $shortsBracket = collect($durationBrackets)->firstWhere('bracket', 'Shorts');
        $under15Bracket = collect($durationBrackets)->firstWhere('bracket', '< 15 mins');
        $bracket15To30 = collect($durationBrackets)->firstWhere('bracket', '15 - 30 mins');

        $this->assertNotNull($shortsBracket);
        $this->assertSame(1, $shortsBracket['count']); // Contains the 75s Short
        $this->assertSame(12000, $shortsBracket['median_views']);
        $this->assertSame(12000, $shortsBracket['avg_views']);

        $this->assertNotNull($under15Bracket);
        $this->assertSame(1, $under15Bracket['count']); // Contains the 500s standard video
        $this->assertSame(25000, $under15Bracket['median_views']);
        $this->assertSame(25000, $under15Bracket['avg_views']);

        $this->assertNotNull($bracket15To30);
        $this->assertSame(1, $bracket15To30['count']); // Contains the 1200s video
        $this->assertSame(45000, $bracket15To30['median_views']);
        $this->assertSame(45000, $bracket15To30['avg_views']);
    }

    /**
     * Test that the stats page computes medians instead of averages to resist outlier skew.
     */
    public function test_stats_page_uses_median_as_measure_of_central_tendency_to_avoid_outlier_skew(): void
    {
        // Video 1: 10k views, 600s duration (10 mins)
        Video::factory()->create([
            'video_type' => 'youtube',
            'duration_seconds' => 600,
            'view_count' => 10000,
            'published_at' => Carbon::parse('2024-01-10 12:00:00'),
        ]);

        // Video 2: 20k views, 1200s duration (20 mins)
        Video::factory()->create([
            'video_type' => 'youtube',
            'duration_seconds' => 1200,
            'view_count' => 20000,
            'published_at' => Carbon::parse('2024-01-15 12:00:00'),
        ]);

        // Video 3: 1,000,000 views (viral runaway hit!), 3600s duration (60 mins)
        Video::factory()->create([
            'video_type' => 'youtube',
            'duration_seconds' => 3600,
            'view_count' => 1000000,
            'published_at' => Carbon::parse('2024-01-20 12:00:00'),
        ]);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $kpis */
        $kpis = $response->viewData('kpis');

        // Mean/average would be (10000 + 20000 + 1000000) / 3 = 343,333 views
        // Median is 20,000 views!
        $this->assertSame(20000, $kpis['median_views']);
        $this->assertNotSame(343333, $kpis['median_views']);

        // Mean duration would be (600 + 1200 + 3600) / 3 = 1800s
        // Median is 1200s (20m)!
        $this->assertSame(1200, $kpis['median_seconds']);
        $this->assertSame('20m', $kpis['median_duration_formatted']);

        // Verify HTML displays median labels
        $response->assertSee('Median Video Length');
        $response->assertSee('Median 20,000 views per video');
        $response->assertSee('Median Views / Video');
        $response->assertSee('Median Views by Length');
    }

    /**
     * Test that /videos/stats redirects to /stats with 301.
     */
    public function test_videos_stats_redirects_to_canonical_stats_route(): void
    {
        $response = $this->get('/videos/stats');

        $response->assertRedirect(route('stats'));
        $response->assertStatus(301);
    }

    /**
     * Test that header includes link to stats page.
     */
    public function test_header_includes_stats_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee(route('stats'));
        $response->assertSee('>Stats<', false);
    }

    /**
     * Test that stats page includes variant sudoku constraints pie chart breakdown.
     */
    public function test_stats_page_includes_variant_sudoku_constraints_pie_chart_breakdown(): void
    {
        $variantCategory = Category::factory()->create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);

        $classicCategory = Category::factory()->create([
            'name' => 'Classic Sudoku',
            'slug' => 'classic-sudoku',
        ]);

        $arrowConstraint = Constraint::factory()->create([
            'name' => 'Arrow',
            'slug' => 'arrow',
        ]);

        $thermoConstraint = Constraint::factory()->create([
            'name' => 'Thermo',
            'slug' => 'thermo',
        ]);

        $sandwichConstraint = Constraint::factory()->create([
            'name' => 'Sandwich',
            'slug' => 'sandwich',
        ]);

        // Puzzle 1 (Variant Sudoku) has Arrow + Thermo
        $puzzle1 = Puzzle::factory()->create([
            'title' => 'Thermo Arrow Variant',
            'category_id' => $variantCategory->id,
        ]);
        $puzzle1->constraints()->attach([$arrowConstraint->id, $thermoConstraint->id]);

        // Puzzle 2 (Variant Sudoku) has Arrow only
        $puzzle2 = Puzzle::factory()->create([
            'title' => 'Pure Arrow Variant',
            'category_id' => $variantCategory->id,
        ]);
        $puzzle2->constraints()->attach([$arrowConstraint->id]);

        // Puzzle 3 (Classic Sudoku) has Sandwich (should NOT be included in variant constraints breakdown)
        $puzzle3 = Puzzle::factory()->create([
            'title' => 'Classic Sandwich Sudoku',
            'category_id' => $classicCategory->id,
        ]);
        $puzzle3->constraints()->attach([$sandwichConstraint->id]);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $constraintStats */
        $constraintStats = $response->viewData('constraintStats');
        $this->assertIsArray($constraintStats);
        $this->assertCount(2, $constraintStats);

        $arrowStat = collect($constraintStats)->firstWhere('name', 'Arrow');
        $thermoStat = collect($constraintStats)->firstWhere('name', 'Thermo');
        $sandwichStat = collect($constraintStats)->firstWhere('name', 'Sandwich');

        $this->assertNotNull($arrowStat);
        $this->assertSame(2, $arrowStat['count']);
        $this->assertSame(66.7, $arrowStat['percentage']);

        $this->assertNotNull($thermoStat);
        $this->assertSame(1, $thermoStat['count']);
        $this->assertSame(33.3, $thermoStat['percentage']);

        $this->assertNull($sandwichStat);

        $this->assertSame(3, $response->viewData('totalConstraintOccurrences'));
        $this->assertSame(2, $response->viewData('totalVariantPuzzles'));

        // Verify HTML displays the tile and canvas
        $response->assertSee('Variant Sudoku Constraints Breakdown');
        $response->assertSee('id="constraintPieChart"', false);
        $response->assertSee('Arrow');
        $response->assertSee('Thermo');
    }

    /**
     * Test that category chart includes monthly progression spanning 14 months
     * and that the frontend contains the 12-month navigation controls.
     */
    public function test_category_chart_includes_monthly_progression_with_twelve_month_window(): void
    {
        $catVariant = Category::factory()->create(['name' => 'Variant Sudoku', 'slug' => 'variant-sudoku']);
        $catClassic = Category::factory()->create(['name' => 'Classic Sudoku', 'slug' => 'classic-sudoku']);

        // Create 14 consecutive monthly videos from 2024-01 through 2025-02
        $start = Carbon::parse('2024-01-15 12:00:00');
        for ($i = 0; $i < 14; $i++) {
            $pubDate = $start->copy()->addMonths($i);
            $video = Video::factory()->create([
                'title' => 'Video for Month '.$pubDate->format('Y-m'),
                'published_at' => $pubDate,
                'view_count' => 1000 * ($i + 1),
            ]);

            if ($i % 2 === 0) {
                $puzzle = Puzzle::factory()->create([
                    'title' => 'Variant Puzzle '.$pubDate->format('Y-m'),
                    'category_id' => $catVariant->id,
                ]);
                $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);
            } else {
                $puzzle = Puzzle::factory()->create([
                    'title' => 'Classic Puzzle '.$pubDate->format('Y-m'),
                    'category_id' => $catClassic->id,
                ]);
                $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);
            }
        }

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $categoryMonthly */
        $categoryMonthly = $response->viewData('categoryMonthly');
        $this->assertIsArray($categoryMonthly);
        $this->assertCount(14, $categoryMonthly);

        $this->assertSame('2024-01', $categoryMonthly[0]['month']);
        $this->assertSame('Jan 2024', $categoryMonthly[0]['label']);
        $this->assertSame(1, $categoryMonthly[0]['categories']['Variant Sudoku']);
        $this->assertSame(0, $categoryMonthly[0]['categories']['Classic Sudoku']);

        $this->assertSame('2025-02', $categoryMonthly[13]['month']);
        $this->assertSame('Feb 2025', $categoryMonthly[13]['label']);
        $this->assertSame(0, $categoryMonthly[13]['categories']['Variant Sudoku']);
        $this->assertSame(1, $categoryMonthly[13]['categories']['Classic Sudoku']);

        // Verify HTML elements for monthly controls
        $response->assertSee('id="btn-cat-monthly"', false);
        $response->assertSee('Monthly');
        $response->assertSee('id="cat-monthly-nav"', false);
        $response->assertSee('id="btn-cat-prev-month"', false);
        $response->assertSee('id="btn-cat-next-month"', false);
        $response->assertSee('id="cat-monthly-range-label"', false);
        $response->assertSee('filter: function (tooltipItem)', false);
    }

    /**
     * Test that stats page detects stale cached payload missing categoryMonthly and recomputes automatically.
     */
    public function test_stats_page_handles_stale_cached_payload_gracefully(): void
    {
        // Populate cache with an old payload lacking categoryMonthly
        Cache::put('catalog_analytics_summary_v3', [
            'stale_data' => true,
        ], 1800);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);
        $this->assertIsArray($response->viewData('categoryMonthly'));
        $this->assertIsArray($response->viewData('constraintStats'));
    }

    /**
     * Test that stats page computes and displays dynamic analytical observations.
     */
    public function test_stats_page_provides_dynamic_observations(): void
    {
        // 1. Standard video (1800s = 30m, 60,000 views)
        Video::factory()->create([
            'video_type' => 'youtube',
            'duration_seconds' => 1800,
            'view_count' => 60000,
            'published_at' => Carbon::parse('2024-06-01 12:00:00'),
        ]);

        // 2. YouTube Short (45s, 40,000 views)
        Video::factory()->create([
            'video_type' => 'youtube_short',
            'duration_seconds' => 45,
            'view_count' => 40000,
            'published_at' => Carbon::parse('2024-06-02 12:00:00'),
        ]);

        // 3. Variant puzzle with dynamic constraint
        $variantCat = Category::factory()->create(['name' => 'Variant Sudoku', 'slug' => 'variant-sudoku']);
        $arrowConstraint = Constraint::factory()->create(['name' => 'Arrow Clues', 'slug' => 'arrow-clues']);
        $puzzle = Puzzle::factory()->create(['category_id' => $variantCat->id]);
        $puzzle->constraints()->attach($arrowConstraint->id);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $observations */
        $observations = $response->viewData('observations');
        $this->assertIsArray($observations);
        $this->assertArrayHasKey('format', $observations);
        $this->assertArrayHasKey('duration', $observations);
        $this->assertArrayHasKey('constraints', $observations);

        // Standard solves = 60,000 / 100,000 = 60%
        $this->assertStringContainsString('60%', $observations['format']);
        $this->assertStringContainsString('40%', $observations['format']);
        $this->assertStringContainsString('45s', $observations['format']);

        // Peak duration bracket
        $this->assertStringContainsString('15 - 30 mins', $observations['duration']);
        $this->assertStringContainsString('60,000 views', $observations['duration']);

        // Constraint mention
        $this->assertStringContainsString('Arrow Clues', $observations['constraints']);

        // Assert rendered in view
        $response->assertSee($observations['format']);
        $response->assertSee($observations['duration']);
        $response->assertSee($observations['constraints']);
    }

    /**
     * Test that intro and outro music statistics are properly computed, untagged videos are ignored,
     * and both pie charts and shared legend are rendered on the stats page.
     */
    public function test_stats_page_computes_and_renders_intro_and_outro_music_pie_charts(): void
    {
        // Video 1: Both intro and outro tagged
        Video::factory()->create([
            'title' => 'Video 1',
            'intro_music' => "Mark's Outro Music - Riffclown",
            'outro_music' => "Mozart's Sonata No. 16",
        ]);

        // Video 2: Both intro and outro tagged
        Video::factory()->create([
            'title' => 'Video 2',
            'intro_music' => "Mark's Outro Music - Riffclown",
            'outro_music' => "Mark's Outro Music - Riffclown",
        ]);

        // Video 3: Only intro tagged
        Video::factory()->create([
            'title' => 'Video 3',
            'intro_music' => "Mozart's Sonata No. 16",
            'outro_music' => null,
        ]);

        // Video 4: Untagged (both null or empty) - must be ignored!
        Video::factory()->create([
            'title' => 'Untagged Video',
            'intro_music' => null,
            'outro_music' => '',
        ]);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $musicStats */
        $musicStats = $response->viewData('musicStats');
        $this->assertIsArray($musicStats);

        // Untagged videos must be excluded from totals
        $this->assertSame(3, $musicStats['total_intro_tagged']);
        $this->assertSame(2, $musicStats['total_outro_tagged']);
        $this->assertSame(2, $musicStats['distinct_tracks']);

        // Check intro breakdown
        $introTracks = collect($musicStats['intro'])->pluck('count', 'track')->all();
        $this->assertSame(2, $introTracks["Mark's Outro Music - Riffclown"]);
        $this->assertSame(1, $introTracks["Mozart's Sonata No. 16"]);

        // Check outro breakdown
        $outroTracks = collect($musicStats['outro'])->pluck('count', 'track')->all();
        $this->assertSame(1, $outroTracks["Mark's Outro Music - Riffclown"]);
        $this->assertSame(1, $outroTracks["Mozart's Sonata No. 16"]);

        // Check color consistency: each track must have the exact same color in intro, outro, and legend
        $legendColors = collect($musicStats['legend'])->pluck('color', 'track')->all();
        foreach ($musicStats['intro'] as $item) {
            $this->assertSame($legendColors[$item['track']], $item['color']);
        }
        foreach ($musicStats['outro'] as $item) {
            $this->assertSame($legendColors[$item['track']], $item['color']);
        }

        // View assertions
        $response->assertSee('Intro & Outro Music Selections', false);
        $response->assertSee('3 Tagged Intros');
        $response->assertSee('2 Tagged Outros');
        $response->assertSee('introMusicChart');
        $response->assertSee('outroMusicChart');
        $response->assertSee('Music Selections Legend');
        $response->assertSee('Mark&#039;s Outro Music - Riffclown', false);
        $response->assertSee('Mozart&#039;s Sonata No. 16', false);
    }

    /**
     * Test that the stats page handles catalog when no music is tagged.
     */
    public function test_stats_page_handles_catalog_with_no_music_tagged(): void
    {
        Video::factory()->create([
            'title' => 'No Music Video',
            'intro_music' => null,
            'outro_music' => null,
        ]);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $musicStats */
        $musicStats = $response->viewData('musicStats');
        $this->assertSame(0, $musicStats['total_intro_tagged']);
        $this->assertSame(0, $musicStats['total_outro_tagged']);
        $this->assertCount(0, $musicStats['intro']);
        $this->assertCount(0, $musicStats['outro']);
        $this->assertCount(0, $musicStats['legend']);

        $response->assertSee('No intro or outro music indexed in the catalog yet.');
    }

    /**
     * Test that the stats page combines the tail ~25% of variant constraints into an "Other" wedge
     * linking to /constraints instead of an individual constraint.
     */
    public function test_stats_page_combines_tail_constraints_around_twenty_five_percent_into_other_wedge(): void
    {
        $variantCategory = Category::factory()->create([
            'name' => 'Variant Sudoku',
            'slug' => 'variant-sudoku',
        ]);

        // Define 8 constraints with counts that total 100 occurrences
        // Top 3 = 75%, Tail 5 = 25%
        $specs = [
            ['name' => 'Thermo Lines', 'slug' => 'thermo-lines', 'count' => 40],
            ['name' => 'Arrow Clues', 'slug' => 'arrow-clues', 'count' => 20],
            ['name' => 'German Whispers', 'slug' => 'german-whispers', 'count' => 15],
            ['name' => 'Renban Lines', 'slug' => 'renban-lines', 'count' => 7],
            ['name' => 'Kropki Dots', 'slug' => 'kropki-dots', 'count' => 6],
            ['name' => 'Killer Cages', 'slug' => 'killer-cages', 'count' => 5],
            ['name' => 'Palindromes', 'slug' => 'palindromes', 'count' => 4],
            ['name' => 'Between Lines', 'slug' => 'between-lines', 'count' => 3],
        ];

        foreach ($specs as $spec) {
            $constraint = Constraint::factory()->create([
                'name' => $spec['name'],
                'slug' => $spec['slug'],
            ]);

            for ($i = 0; $i < $spec['count']; $i++) {
                $puzzle = Puzzle::factory()->create([
                    'category_id' => $variantCategory->id,
                ]);
                $puzzle->constraints()->attach($constraint->id);
            }
        }

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array $constraintStats */
        $constraintStats = $response->viewData('constraintStats');
        $this->assertIsArray($constraintStats);

        // Expected: 3 prominent constraints + 1 "Other" wedge = 4 elements
        $this->assertCount(4, $constraintStats);

        $thermo = collect($constraintStats)->firstWhere('name', 'Thermo Lines');
        $arrow = collect($constraintStats)->firstWhere('name', 'Arrow Clues');
        $whispers = collect($constraintStats)->firstWhere('name', 'German Whispers');
        $other = collect($constraintStats)->firstWhere('name', 'Other');

        $this->assertNotNull($thermo);
        $this->assertSame(40, $thermo['count']);
        $this->assertSame(40.0, $thermo['percentage']);
        $this->assertFalse($thermo['is_other']);
        $this->assertSame(route('constraints.show', 'thermo-lines'), $thermo['url']);

        $this->assertNotNull($arrow);
        $this->assertSame(20, $arrow['count']);
        $this->assertSame(20.0, $arrow['percentage']);

        $this->assertNotNull($whispers);
        $this->assertSame(15, $whispers['count']);
        $this->assertSame(15.0, $whispers['percentage']);

        // Verify "Other" wedge characteristics
        $this->assertNotNull($other, 'An "Other" wedge must be created for the tail 25%');
        $this->assertSame(25, $other['count']);
        $this->assertSame(25.0, $other['percentage']);
        $this->assertTrue($other['is_other']);
        $this->assertNull($other['slug']);
        $this->assertSame(route('constraints.index'), $other['url']);
        $this->assertSame(5, $other['distinct_constraints_count']);

        // Verify total metrics
        $this->assertSame(100, $response->viewData('totalConstraintOccurrences'));
        $this->assertSame(8, $response->viewData('totalUniqueConstraints'));

        // HTML Assertions: Check badge displays all 8 unique constraints
        $response->assertSee('8 Unique Constraints');
        $response->assertSee('100 Tagged Rulesets');

        // Check that "Other" links to the /constraints/ directory page
        $response->assertSee(route('constraints.index'));
        $response->assertSee('Other');

        // Check observations mention actual constraint names and not "Other"
        /** @var array $observations */
        $observations = $response->viewData('observations');
        $this->assertStringNotContainsString('Other', $observations['constraints']);
        $this->assertStringContainsString('Thermo Lines', $observations['constraints']);
    }

    /**
     * Test that CatalogStatsService caches home and dashboard stats and automatically invalidates them when catalog models change.
     */
    public function test_catalog_stats_service_invalidates_cached_stats_when_models_change(): void
    {
        Video::factory()->create([
            'title' => 'Initial Video',
            'view_count' => 1000,
            'published_at' => now()->subDay(),
        ]);

        // Prime both caches via HTTP requests
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertStatus(200);
        $this->assertSame(1, $homeResponse->viewData('stats')['total_videos']);

        $statsResponse = $this->get(route('stats'));
        $statsResponse->assertStatus(200);
        $this->assertSame(1, $statsResponse->viewData('kpis')['total_videos']);

        $this->assertTrue(Cache::has(CatalogStatsService::HOME_CACHE_KEY));
        $this->assertTrue(Cache::has(CatalogStatsService::DASHBOARD_CACHE_KEY));

        // Creating a new Video must automatically flush both caches
        Video::factory()->create([
            'title' => 'Second Video',
            'view_count' => 2000,
            'published_at' => now(),
        ]);

        $this->assertFalse(Cache::has(CatalogStatsService::HOME_CACHE_KEY));
        $this->assertFalse(Cache::has(CatalogStatsService::DASHBOARD_CACHE_KEY));

        // Subsequent requests reflect the updated counts
        $updatedHome = $this->get(route('home'));
        $this->assertSame(2, $updatedHome->viewData('stats')['total_videos']);

        $updatedStats = $this->get(route('stats'));
        $this->assertSame(2, $updatedStats->viewData('kpis')['total_videos']);
    }

    /**
     * Test that the stats page renders the dynamic anti-collision category color resolver and card indicators.
     */
    public function test_stats_page_renders_dynamic_category_color_resolver_for_bar_charts(): void
    {
        $cat1 = Category::factory()->create(['name' => 'Variant Sudoku', 'slug' => 'variant-sudoku']);
        $cat2 = Category::factory()->create(['name' => 'Pencil Puzzle', 'slug' => 'pencil-puzzle']);
        $cat3 = Category::factory()->create(['name' => 'Video Game', 'slug' => 'video-game']);

        foreach ([$cat1, $cat2, $cat3] as $idx => $cat) {
            $video = Video::factory()->create([
                'title' => 'Video '.$cat->name,
                'published_at' => now()->subDays($idx + 1),
            ]);
            $puzzle = Puzzle::factory()->create([
                'title' => 'Puzzle '.$cat->name,
                'category_id' => $cat->id,
            ]);
            $video->puzzles()->attach($puzzle->id, ['order_in_video' => 1]);
        }

        $response = $this->get(route('stats'));
        $response->assertStatus(200);
        $response->assertSee('resolveDynamicCategoryColors', false);
        $response->assertSee('getColorContrastDistance', false);
        $response->assertSee('syncCategoryCardColorDots', false);
        $response->assertSee('category-color-dot', false);
    }

    /**
     * Test that Patreon Exclusive videos are separated out from Standard Solves in the format breakdown.
     */
    public function test_format_breakdown_separates_patreon_exclusive_videos_from_standard_solves(): void
    {
        // 2 public Standard Solves
        Video::factory()->count(2)->create([
            'video_type' => 'youtube',
            'access_level' => 'public',
            'duration_seconds' => 1800,
            'view_count' => 40000,
            'published_at' => Carbon::parse('2024-09-01 12:00:00'),
        ]);

        // 1 Patreon Exclusive video (video_type = 'youtube', access_level = 'exclusive')
        Video::factory()->create([
            'video_type' => 'youtube',
            'access_level' => 'exclusive',
            'duration_seconds' => 2400,
            'view_count' => 5000,
            'published_at' => Carbon::parse('2024-09-05 12:00:00'),
        ]);

        // 1 YouTube Short
        Video::factory()->create([
            'video_type' => 'youtube_short',
            'access_level' => 'public',
            'duration_seconds' => 50,
            'view_count' => 15000,
            'published_at' => Carbon::parse('2024-09-10 12:00:00'),
        ]);

        $response = $this->get(route('stats'));
        $response->assertStatus(200);

        /** @var array<string, array<string, mixed>> $formatStats */
        $formatStats = $response->viewData('formatStats');
        $this->assertIsArray($formatStats);
        $this->assertArrayHasKey('youtube', $formatStats);
        $this->assertArrayHasKey('youtube_short', $formatStats);
        $this->assertArrayHasKey('live_stream', $formatStats);
        $this->assertArrayHasKey('patreon_exclusive', $formatStats);

        // Standard Solves should only count the 2 public youtube videos
        $this->assertSame('Standard Solves', $formatStats['youtube']['label']);
        $this->assertSame(2, $formatStats['youtube']['count']);
        $this->assertSame(50.0, $formatStats['youtube']['percentage']);
        $this->assertSame(80000, $formatStats['youtube']['total_views']);

        // Patreon Exclusives should count the 1 exclusive video
        $this->assertSame('Patreon Exclusives', $formatStats['patreon_exclusive']['label']);
        $this->assertSame(1, $formatStats['patreon_exclusive']['count']);
        $this->assertSame(25.0, $formatStats['patreon_exclusive']['percentage']);
        $this->assertSame(5000, $formatStats['patreon_exclusive']['total_views']);
        $this->assertSame(2400, $formatStats['patreon_exclusive']['median_duration_seconds']);
    }
}
