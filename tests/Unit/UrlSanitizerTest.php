<?php

namespace Tests\Unit;

use App\Services\UrlSanitizer;
use PHPUnit\Framework\TestCase;

class UrlSanitizerTest extends TestCase
{
    /**
     * Test that user-provided YouTube redirect URL extracts the clean destination URL.
     */
    public function test_unwraps_exact_user_youtube_redirect_url(): void
    {
        $ytRedirectUrl = 'https://www.youtube.com/redirect?event=video_description&redir_token=QUlxb01IWWlDTG44Uk56clpLeGZzQVY5bXp6MXxBSTVJdTV2ZDVVQjd1NW83SUdYTTIzR3BlNkN5aHVrOExJSUZzck9HRUFEXzBUZHFXWENlZHJYNllhRllGUnl0Zk9FYW1maTdCUFR3VHlMUHh6SE9KNDZiQU5GNWZ2QjFydGRM&q=https%3A%2F%2Fwww.thetimes.co.uk%2Fpuzzleclub%2Fcrosswordclub%2Fpuzzles%2Fcrossword%2F45741&v=GeSkpiATWLQ';

        $expected = 'https://www.thetimes.co.uk/puzzleclub/crosswordclub/puzzles/crossword/45741';

        $this->assertSame($expected, UrlSanitizer::unwrapYouTubeRedirect($ytRedirectUrl));
    }

    /**
     * Test redirect unwrapping across domain variations: m.youtube.com, apex youtube.com, http, and protocol-relative.
     */
    public function test_unwraps_redirect_across_domain_and_scheme_variations(): void
    {
        // 1. Mobile YouTube domain
        $mobileUrl = 'https://m.youtube.com/redirect?event=video_description&q=https%3A%2F%2Fsudokupad.app%2Fctc%2F12345';
        $this->assertSame('https://sudokupad.app/ctc/12345', UrlSanitizer::unwrapYouTubeRedirect($mobileUrl));

        // 2. Apex domain without www
        $apexUrl = 'https://youtube.com/redirect?q=https%3A%2F%2Flogic-masters.de%2Fpuzzle%2F999&v=abc';
        $this->assertSame('https://logic-masters.de/puzzle/999', UrlSanitizer::unwrapYouTubeRedirect($apexUrl));

        // 3. HTTP scheme
        $httpUrl = 'http://www.youtube.com/redirect?q=https%3A%2F%2Fpenpa-edit.app%2Fpenpa';
        $this->assertSame('https://penpa-edit.app/penpa', UrlSanitizer::unwrapYouTubeRedirect($httpUrl));

        // 4. Without scheme (e.g. pasted as www.youtube.com/redirect...)
        $schemelessUrl = 'www.youtube.com/redirect?q=https%3A%2F%2Fsudokupad.app%2Ftest';
        $this->assertSame('https://sudokupad.app/test', UrlSanitizer::unwrapYouTubeRedirect($schemelessUrl));
    }

    /**
     * Test target URL containing query parameters, hashes, and double URL-encoding.
     */
    public function test_preserves_target_query_parameters_and_decodes_nested(): void
    {
        // Target URL with its own query parameters
        $withParams = 'https://www.youtube.com/redirect?q=https%3A%2F%2Fsudokupad.app%2Fapp%3Fpuzzleid%3Dfpuzzles123%26setting%3Dtrue%23instructions&v=abc';
        $this->assertSame('https://sudokupad.app/app?puzzleid=fpuzzles123&setting=true#instructions', UrlSanitizer::unwrapYouTubeRedirect($withParams));

        // Double encoded target
        $doubleEncoded = 'https://www.youtube.com/redirect?q=https%253A%252F%252Fexample.com%252Fpuzzle';
        $this->assertSame('https://example.com/puzzle', UrlSanitizer::unwrapYouTubeRedirect($doubleEncoded));

        // Nested redirect
        $nested = 'https://www.youtube.com/redirect?q=https%3A%2F%2Fwww.youtube.com%2Fredirect%3Fq%3Dhttps%253A%252F%252Ffinal.com';
        $this->assertSame('https://final.com', UrlSanitizer::unwrapYouTubeRedirect($nested));
    }

    /**
     * Test that non-redirect URLs are untouched.
     */
    public function test_leaves_non_redirect_urls_untouched(): void
    {
        // Standard YouTube video watch URL
        $watchUrl = 'https://www.youtube.com/watch?v=GeSkpiATWLQ';
        $this->assertSame($watchUrl, UrlSanitizer::unwrapYouTubeRedirect($watchUrl));

        // YouTube Shorts URL
        $shortsUrl = 'https://www.youtube.com/shorts/GeSkpiATWLQ';
        $this->assertSame($shortsUrl, UrlSanitizer::unwrapYouTubeRedirect($shortsUrl));

        // SudokuPad URL
        $sudokuPadUrl = 'https://sudokupad.app/ctc/12345';
        $this->assertSame($sudokuPadUrl, UrlSanitizer::unwrapYouTubeRedirect($sudokuPadUrl));

        // Penpa URL
        $penpaUrl = 'https://swaroopg92.github.io/penpa-edit/?m=solve&p=123';
        $this->assertSame($penpaUrl, UrlSanitizer::unwrapYouTubeRedirect($penpaUrl));

        // Null and empty
        $this->assertNull(UrlSanitizer::unwrapYouTubeRedirect(null));
        $this->assertSame('', UrlSanitizer::unwrapYouTubeRedirect(''));
        $this->assertSame('', UrlSanitizer::unwrapYouTubeRedirect('   '));
    }

    /**
     * Test HTML entities in redirect URL (e.g. &amp;q=...).
     */
    public function test_unwraps_html_entity_encoded_query_strings(): void
    {
        $entityUrl = 'https://www.youtube.com/redirect?event=video_description&amp;q=https%3A%2F%2Fsudokupad.app%2Fctc%2F123&amp;v=GeSkpiATWLQ';
        $this->assertSame('https://sudokupad.app/ctc/123', UrlSanitizer::unwrapYouTubeRedirect($entityUrl));
    }

    /**
     * Test scanning and unwrapping all redirect links embedded in arbitrary text or markdown.
     */
    public function test_unwraps_all_redirect_links_in_arbitrary_text(): void
    {
        $text = "Rules:\nPlay the puzzle at https://www.youtube.com/redirect?event=video_description&q=https%3A%2F%2Fsudokupad.app%2Fctc%2F12345&v=abc and enjoy!";
        $expected = "Rules:\nPlay the puzzle at https://sudokupad.app/ctc/12345 and enjoy!";

        $this->assertSame($expected, UrlSanitizer::unwrapAllInText($text));
    }

    /**
     * Test that unsafe destination URI schemes (such as javascript: or data:) are not unwrapped.
     */
    public function test_rejects_unsafe_destination_schemes_when_unwrapping(): void
    {
        $malicious = 'https://www.youtube.com/redirect?q=javascript%3Aalert(1)';
        $this->assertSame($malicious, UrlSanitizer::unwrapYouTubeRedirect($malicious));
    }
}
