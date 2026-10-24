<?php

namespace Database\Seeders;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Constraint;
use App\Models\Host;
use App\Models\PatreonPost;
use App\Models\Puzzle;
use App\Models\Setter;
use App\Models\Video;
use Illuminate\Database\Seeder;

/**
 * Seeder for core catalog reference data and initial demonstration videos.
 *
 * Populates hosts, categories, constraints, setters, videos, puzzles, and bookmarks.
 */
class CatalogueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Core Hosts
        $simon = Host::firstOrCreate(
            ['slug' => 'simon-anthony'],
            ['name' => 'Simon Anthony', 'avatar_url' => null]
        );

        $mark = Host::firstOrCreate(
            ['slug' => 'mark-goodliffe'],
            ['name' => 'Mark Goodliffe', 'avatar_url' => null]
        );

        // 2. Core Categories
        $variantSudoku = Category::firstOrCreate(
            ['slug' => 'variant-sudoku'],
            ['name' => 'Variant Sudoku', 'description' => 'Sudoku puzzles featuring non-standard constraints and rulesets.']
        );

        $classicSudoku = Category::firstOrCreate(
            ['slug' => 'classic-sudoku'],
            ['name' => 'Classic Sudoku', 'description' => 'Pure 9x9 standard sudoku with standard rules.']
        );

        $crossword = Category::firstOrCreate(
            ['slug' => 'crossword'],
            ['name' => 'Crossword', 'description' => 'Cryptic and standard crosswords solved on the channel.']
        );

        $gas = Category::firstOrCreate(
            ['slug' => 'gas'],
            ['name' => 'GAS', 'description' => 'Generally Approachable Sudoku speed solves and mini puzzles.']
        );

        $pencilPuzzle = Category::firstOrCreate(
            ['slug' => 'pencil-puzzle'],
            ['name' => 'Pencil Puzzle', 'description' => 'Japanese and logic pencil puzzles including Star Battle, Kakuro, Slitherlink, and related genres.']
        );

        // 3. Top Constraints
        $constraintsData = [
            ['name' => 'Thermo', 'slug' => 'thermo', 'description' => 'Digits along thermometers must strictly increase from the bulb.'],
            ['name' => 'Killer', 'slug' => 'killer', 'description' => 'Digits in cages must sum to the corner total without repeats.'],
            ['name' => 'Arrow', 'slug' => 'arrow', 'description' => 'Digits along arrows sum to the value in the connected circle.'],
            ['name' => 'German Whispers', 'slug' => 'german-whispers', 'description' => 'Adjacent digits along green lines must differ by at least 5.'],
            ['name' => 'Renban', 'slug' => 'renban', 'description' => 'Digits along purple lines form non-repeating consecutive sets in any order.'],
            ['name' => 'Fog of War', 'slug' => 'fog-of-war', 'description' => 'Entering correct digits reveals surrounding hidden cells.'],
            ['name' => 'Star Battle', 'slug' => 'star-battle', 'description' => 'Place stars in the grid such that each row, column, and outlined shape contains a specified number of stars without touching.'],
            ['name' => 'Kakuro', 'slug' => 'kakuro', 'description' => 'Cross-sums number puzzle where entries sum to clue totals using digits 1-9 without duplicates in any run.'],
        ];

        $constraints = [];
        foreach ($constraintsData as $c) {
            $constraints[$c['slug']] = Constraint::firstOrCreate(['slug' => $c['slug']], $c);
        }

        // 4. Core Setters
        $phistomefel = Setter::firstOrCreate(
            ['slug' => 'phistomefel'],
            ['name' => 'Phistomefel', 'bio' => 'Renowned constructor known for profound theorem-driven geometry.']
        );

        $clover = Setter::firstOrCreate(
            ['slug' => 'clover'],
            ['name' => 'Clover', 'bio' => 'Prolific setter specializing in approachable and delightful deduction.']
        );

        $sven = Setter::firstOrCreate(
            ['slug' => 'sven-neumann'],
            ['name' => 'Sven Neumann', 'bio' => 'Software engineer and creator of SudokuPad.']
        );

        // 5. Test Video 1: Public single-puzzle video with 4 bookmarks
        $puzzle1 = Puzzle::firstOrCreate(
            ['slug' => 'chessboard-phistomefel'],
            [
                'category_id' => $variantSudoku->id,
                'title' => 'Chessboard',
                'rules_raw' => "Normal Sudoku rules apply.\n\nKnights move: Cells separated by a chess knight's move cannot contain identical digits.\n\nKing move: Cells separated by a chess king's move cannot contain identical digits.",
                'difficulty_score' => 4.5,
            ]
        );
        $puzzle1->links()->firstOrCreate(
            ['link_type' => 'sudokupad'],
            ['url' => 'https://sudokupad.app/chessboard-demo', 'order_column' => 1]
        );
        $puzzle1->links()->firstOrCreate(
            ['link_type' => 'penpa'],
            ['url' => 'https://penpa-edit.appspot.com/chessboard-demo', 'order_column' => 2]
        );
        $puzzle1->setters()->syncWithoutDetaching([$phistomefel->id]);
        $puzzle1->constraints()->syncWithoutDetaching([
            $constraints['thermo']->id,
            $constraints['killer']->id,
        ]);

        $video1 = Video::firstOrCreate(
            ['slug' => 'beware-the-sliding-horses'],
            [
                'youtube_video_id' => 'dQw4w9WgXcQ',
                'video_type' => 'youtube',
                'patreon_post_url' => null,
                'title' => 'Beware the Sliding Horses',
                'published_at' => now()->subDays(2),
                'duration_seconds' => 2415,
                'view_count' => 64200,
                'stats_synced_at' => now(),
                'youtube_thumbnail_url' => 'https://images.unsplash.com/photo-1529699211952-734e80c4d42b?auto=format&fit=crop&w=800&q=80',
                'intro_music' => 'Equinox (CTC Intro)',
                'outro_music' => 'Nocturne in C Minor',
            ]
        );
        $video1->hosts()->syncWithoutDetaching([$simon->id]);
        $video1->puzzles()->syncWithoutDetaching([
            $puzzle1->id => [
                'order_in_video' => 1,
                'start_seconds' => 120,
                'solve_duration_seconds' => 2200,
            ],
        ]);

        $bookmarksVideo1 = [
            ['timestamp_seconds' => 45, 'label' => 'Introduction and Overview'],
            ['timestamp_seconds' => 120, 'label' => 'Rules Explanation'],
            ['timestamp_seconds' => 680, 'label' => 'Initial Break-in'],
            ['timestamp_seconds' => 1950, 'label' => 'The Epiphany'],
        ];

        foreach ($bookmarksVideo1 as $bm) {
            Bookmark::firstOrCreate(
                [
                    'video_id' => $video1->id,
                    'timestamp_seconds' => $bm['timestamp_seconds'],
                ],
                array_merge($bm, ['puzzle_id' => $puzzle1->id])
            );
        }

        // 6. Test Video 2: Public multi-puzzle video (GAS format) with 2 attached puzzles
        $gasPuzzle1 = Puzzle::firstOrCreate(
            ['slug' => 'gas-warmup-clover'],
            [
                'category_id' => $gas->id,
                'title' => 'GAS Warmup',
                'rules_raw' => 'Normal Sudoku rules apply. Adjacent digits along green lines differ by at least 5.',
                'difficulty_score' => 2.0,
            ]
        );
        $gasPuzzle1->links()->firstOrCreate(
            ['link_type' => 'sudokupad'],
            ['url' => 'https://sudokupad.app/gas-warmup-demo', 'order_column' => 1]
        );
        $gasPuzzle1->setters()->syncWithoutDetaching([$clover->id]);
        $gasPuzzle1->constraints()->syncWithoutDetaching([$constraints['german-whispers']->id]);

        $gasPuzzle2 = Puzzle::firstOrCreate(
            ['slug' => 'gas-sprint-sven'],
            [
                'category_id' => $gas->id,
                'title' => 'GAS Sprint',
                'rules_raw' => 'Normal Sudoku rules apply. Digits in cages sum to the indicated clue.',
                'difficulty_score' => 2.5,
            ]
        );
        $gasPuzzle2->links()->firstOrCreate(
            ['link_type' => 'sudokupad'],
            ['url' => 'https://sudokupad.app/gas-sprint-demo', 'order_column' => 1]
        );
        $gasPuzzle2->setters()->syncWithoutDetaching([$sven->id]);
        $gasPuzzle2->constraints()->syncWithoutDetaching([$constraints['killer']->id]);

        $video2 = Video::firstOrCreate(
            ['slug' => 'speed-solving-two-approachable-sudokus'],
            [
                'youtube_video_id' => 'L_LUpnjgPso',
                'video_type' => 'youtube',
                'patreon_post_url' => null,
                'title' => 'Speed Solving Two Approachable Sudokus',
                'published_at' => now()->subDays(5),
                'duration_seconds' => 1420,
                'view_count' => 48100,
                'stats_synced_at' => now(),
                'youtube_thumbnail_url' => 'https://images.unsplash.com/photo-1580541832626-2a7131ee809f?auto=format&fit=crop&w=800&q=80',
                'intro_music' => 'Upbeat Acoustic Struts',
                'outro_music' => 'Acoustic Sunset',
            ]
        );
        $video2->hosts()->syncWithoutDetaching([$mark->id]);
        $video2->puzzles()->syncWithoutDetaching([
            $gasPuzzle1->id => [
                'order_in_video' => 1,
                'start_seconds' => 60,
                'solve_duration_seconds' => 540,
            ],
            $gasPuzzle2->id => [
                'order_in_video' => 2,
                'start_seconds' => 620,
                'solve_duration_seconds' => 750,
            ],
        ]);

        Bookmark::firstOrCreate(
            ['video_id' => $video2->id, 'timestamp_seconds' => 60],
            ['label' => 'Puzzle 1: GAS Warmup by Clover', 'puzzle_id' => $gasPuzzle1->id]
        );
        Bookmark::firstOrCreate(
            ['video_id' => $video2->id, 'timestamp_seconds' => 620],
            ['label' => 'Puzzle 2: GAS Sprint by Sven Neumann', 'puzzle_id' => $gasPuzzle2->id]
        );

        // 7. Test Video 3: Patreon release containing an exclusive video
        $patreonPost = PatreonPost::firstOrCreate(
            ['slug' => 'whispering-shadows-masterclass-patreon-post'],
            [
                'title' => 'Whispering Shadows Masterclass & Bonus Discussion',
                'patreon_url' => 'https://www.patreon.com/posts/whispering-shadows-98765432',
                'published_at' => now()->subDays(1),
                'notes' => 'Full Patreon release containing exclusive masterclass solve and bonus setter notes.',
            ]
        );

        $patreonPuzzle = Puzzle::firstOrCreate(
            ['slug' => 'whispering-shadows-phistomefel'],
            [
                'category_id' => $variantSudoku->id,
                'title' => 'Whispering Shadows',
                'access_level' => 'exclusive',
                'rules_raw' => 'Normal Sudoku rules apply. German Whispers lines and Renban lines intersect throughout the grid.',
                'difficulty_score' => 5.0,
            ]
        );
        $patreonPuzzle->links()->firstOrCreate(
            ['link_type' => 'sudokupad'],
            ['url' => 'https://sudokupad.app/whispering-shadows-demo', 'order_column' => 1]
        );
        $patreonPuzzle->setters()->syncWithoutDetaching([$phistomefel->id]);
        $patreonPuzzle->constraints()->syncWithoutDetaching([
            $constraints['german-whispers']->id,
            $constraints['renban']->id,
        ]);

        $video3 = Video::firstOrCreate(
            ['slug' => 'patreon-exclusive-whispering-shadows'],
            [
                'patreon_post_id' => $patreonPost->id,
                'youtube_video_id' => null,
                'video_type' => 'youtube',
                'access_level' => 'exclusive',
                'patreon_post_url' => 'https://www.patreon.com/posts/whispering-shadows-98765432',
                'title' => 'Patreon Exclusive: Whispering Shadows Masterclass',
                'published_at' => now()->subDays(1),
                'duration_seconds' => 3780,
                'view_count' => 8900,
                'stats_synced_at' => now(),
                'youtube_thumbnail_url' => null,
                'intro_music' => 'Classical Strings Op. 4',
                'outro_music' => 'Adagio for Strings',
            ]
        );
        $video3->hosts()->syncWithoutDetaching([$simon->id, $mark->id]);
        $video3->puzzles()->syncWithoutDetaching([
            $patreonPuzzle->id => [
                'order_in_video' => 1,
                'start_seconds' => 0,
                'solve_duration_seconds' => 3780,
            ],
        ]);
    }
}
