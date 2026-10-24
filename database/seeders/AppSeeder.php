<?php

namespace Database\Seeders;

use App\Models\CtcApp;
use Illuminate\Database\Seeder;

/**
 * Seeder for official Cracking the Cryptic and partner applications.
 */
class AppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apps = [
            [
                'name' => 'Cracking the Cryptic',
                'slug' => 'cracking-the-cryptic',
                'tagline' => 'The official unified CTC hub app for mobile and Steam variety packs',
                'description' => 'The official Cracking the Cryptic app and puzzle pack platform. Includes starter puzzles and provides access to expansions including Domino Sudoku, Lines Variety Pack, GAS Volumes, Fog of War, and Constructor Worm collections.',
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2085970/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/cracking-the-cryptic/id1629992934',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.CrackingTheCryptic',
                'steam_url' => 'https://store.steampowered.com/app/2085970/Cracking_the_Cryptic/',
                'web_url' => null,
                'badge' => 'Official Hub',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Classic Sudoku',
                'slug' => 'classic-sudoku',
                'tagline' => 'Handcrafted pure deduction sudokus curated by Simon and Mark',
                'description' => "Features handcrafted classic sudoku puzzles created by world-class constructors. Includes Simon and Mark's intuitive hint system explaining why a deduction is true, not just which cell to fill.",
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1188330/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/classic-sudoku/id1488838275',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.ClassicSudoku',
                'steam_url' => 'https://store.steampowered.com/app/1188330/Classic_Sudoku/',
                'web_url' => null,
                'badge' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Sandwich Sudoku',
                'slug' => 'sandwich-sudoku',
                'tagline' => 'The beloved puzzle where outside clues sum digits between 1 and 9',
                'description' => "Numbers outside the grid indicate the sum of all digits 'sandwiched' between the 1 and the 9 in that row or column. Created by Studio Goya and Cracking the Cryptic.",
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1117310/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/sandwich-sudoku/id1476116705',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.SandwichSudoku',
                'steam_url' => 'https://store.steampowered.com/app/1117310/Sandwich_Sudoku/',
                'web_url' => null,
                'badge' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Chess Sudoku',
                'slug' => 'chess-sudoku',
                'tagline' => 'Knight, King, and Queen movement constraints meet sudoku',
                'description' => 'Cells separated by a chess knight move, king move, or along queen and bishop diagonals cannot contain duplicate digits. Introduces geometric spatial deduction to classic logic.',
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1281690/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/chess-sudoku/id1500654482',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.ChessSudoku',
                'steam_url' => 'https://store.steampowered.com/app/1250560/Chess_Sudoku/',
                'web_url' => null,
                'badge' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Thermo Sudoku',
                'slug' => 'thermo-sudoku',
                'tagline' => 'Thermometers with strictly increasing digits from bulb to tip',
                'description' => 'Digits along thermometers must strictly increase starting from the circular bulb. One of the channel foundational and most requested variant mechanics.',
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1230190/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/thermo-sudoku/id1513994223',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.ThermoSudoku',
                'steam_url' => 'https://store.steampowered.com/app/1316390/Thermo_Sudoku/',
                'web_url' => null,
                'badge' => null,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Miracle Sudoku',
                'slug' => 'miracle-sudoku',
                'tagline' => 'The viral sensation combining knight, king, and non-consecutive rules',
                'description' => "Inspired by Mitchell Thomashow's legendary puzzle with just two given digits. Features orthogonal non-consecutive digits, anti-knight moves, and anti-king moves creating breathtaking emergent logic.",
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1377260/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/id1527363795',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.MiracleSudokuCTC',
                'steam_url' => 'https://store.steampowered.com/app/1377260/Miracle_Sudoku/',
                'web_url' => null,
                'badge' => 'Featured',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Killer Sudoku',
                'slug' => 'killer-sudoku',
                'tagline' => 'Sum cages and arithmetic deduction with zero digit repeats',
                'description' => 'Outlined dotted cages contain digits that sum to the specified corner clue without repeating. A staple CTC favorite with expertly crafted step-by-step logic.',
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1471760/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/killer-sudoku-ctc/id1544165118',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.KillerSudoku',
                'steam_url' => 'https://store.steampowered.com/app/1471910/Killer_Sudoku/',
                'web_url' => null,
                'badge' => null,
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Arrow Sudoku',
                'slug' => 'arrow-sudoku',
                'tagline' => 'Connecting circled bulbs to digit sums along directional arrows',
                'description' => 'Digits along arrow shafts must sum exactly to the number placed in the connected circled bulb. Arrows can cross, overlap, and branch for deeply satisfying deductions.',
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1609140/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/arrow-sudoku/id1568407537',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.ArrowSudoku',
                'steam_url' => 'https://store.steampowered.com/app/1613680/Arrow_Sudoku/',
                'web_url' => null,
                'badge' => null,
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Lines Sudoku',
                'slug' => 'lines-sudoku',
                'tagline' => 'Renban lines, Whispers, Palindromes, and Region Sums',
                'description' => 'Features modern line mechanics: purple Renban lines forming consecutive sets, green German Whispers with differences of 5+, Dutch Whispers, palindromic lines, and region sum lines.',
                'developer' => 'Studio Goya / Cracking The Cryptic',
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/2099710/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/cracking-the-cryptic/id1629992934',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.StudioGoya.CrackingTheCryptic',
                'steam_url' => 'https://store.steampowered.com/app/2627750/Cracking_the_Cryptic__Lines_Variety_Pack/',
                'web_url' => null,
                'badge' => null,
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'SudokuPad',
                'slug' => 'sudokupad',
                'tagline' => 'The premier puzzle player app used in every Cracking the Cryptic solve',
                'description' => 'Built by software engineer Sven Neumann in close collaboration with Simon and Mark. Play custom puzzles, import community links, enjoy cosmetic pen tools, fog of war, and export shareable solves.',
                'developer' => "Sven Neumann / Sven's SudokuPad",
                'thumbnail_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/1706870/header.jpg',
                'apple_store_url' => 'https://apps.apple.com/us/app/svens-sudokupad/id1570622073',
                'google_play_url' => 'https://play.google.com/store/apps/details?id=com.svencodes.sudokupad',
                'steam_url' => 'https://store.steampowered.com/app/1706870/Svens_SudokuPad/',
                'web_url' => 'https://sudokupad.app',
                'badge' => 'Companion Player',
                'sort_order' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($apps as $appData) {
            CtcApp::updateOrCreate(
                ['slug' => $appData['slug']],
                $appData
            );
        }
    }
}
