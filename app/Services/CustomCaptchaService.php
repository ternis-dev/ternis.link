<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CustomCaptchaService
{
    /**
     * Challenge lifetime in seconds (5 minutes).
     */
    public const CHALLENGE_TTL = 300;

    /**
     * Generate a new custom privacy-preserving CAPTCHA challenge.
     *
     * Returns an associative array containing:
     * - 'svg': Standalone SVG markup with anti-OCR noise lines and arithmetic challenge.
     * - 'token': Encrypted, HMAC-signed token carrying the expected answer and expiry.
     * - 'question': Plain-text question for accessibility / screen readers.
     */
    public function generate(): array
    {
        $operations = ['+', '-', '×'];
        $op = $operations[array_rand($operations)];

        if ($op === '+') {
            $a = rand(3, 19);
            $b = rand(2, 15);
            $answer = $a + $b;
            $displayOp = '+';
        } elseif ($op === '-') {
            $a = rand(10, 25);
            $b = rand(2, 9);
            $answer = $a - $b;
            $displayOp = '-';
        } else {
            $a = rand(2, 9);
            $b = rand(2, 5);
            $answer = $a * $b;
            $displayOp = '×';
        }

        $question = "{$a} {$displayOp} {$b} = ?";
        $svg = $this->renderSvg($question);

        $payload = [
            'hash' => hash_hmac('sha256', (string) $answer, (string) config('app.key')),
            'expires_at' => time() + self::CHALLENGE_TTL,
            'nonce' => Str::random(16),
        ];

        $token = Crypt::encryptString((string) json_encode($payload));

        return [
            'svg' => $svg,
            'token' => $token,
            'question' => $question,
        ];
    }

    /**
     * Verify client answer against the encrypted challenge token.
     */
    public function verify(?string $token, ?string $answer): bool
    {
        if (! is_string($token) || trim($token) === '' || $answer === null || trim($answer) === '') {
            return false;
        }

        try {
            $decrypted = Crypt::decryptString($token);
            $payload = json_decode($decrypted, true);

            if (! is_array($payload) || ! isset($payload['hash'], $payload['expires_at'], $payload['nonce'])) {
                return false;
            }

            // Expiry check
            if (time() > (int) $payload['expires_at']) {
                return false;
            }

            // One-time consumption check to prevent replay attacks
            $nonce = (string) $payload['nonce'];
            $cacheKey = 'captcha_nonce:'.$nonce;
            if (! Cache::add($cacheKey, 1, self::CHALLENGE_TTL)) {
                return false; // Token already consumed
            }

            $cleaned = trim((string) $answer);
            $expectedHash = (string) $payload['hash'];
            $actualHash = hash_hmac('sha256', $cleaned, (string) config('app.key'));

            return hash_equals($expectedHash, $actualHash);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Render SVG with anti-OCR noise elements and arithmetic challenge text.
     */
    private function renderSvg(string $text): string
    {
        $width = 220;
        $height = 68;

        $lines = '';
        for ($i = 0; $i < 6; $i++) {
            $x1 = rand(0, $width);
            $y1 = rand(0, $height);
            $x2 = rand(0, $width);
            $y2 = rand(0, $height);
            $strokeWidth = rand(1, 2);
            $opacity = rand(20, 45) / 100;
            $lines .= "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" stroke=\"#94a3b8\" stroke-width=\"{$strokeWidth}\" opacity=\"{$opacity}\" />\n";
        }

        $dots = '';
        for ($i = 0; $i < 16; $i++) {
            $cx = rand(0, $width);
            $cy = rand(0, $height);
            $r = rand(1, 3);
            $opacity = rand(20, 50) / 100;
            $dots .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" fill=\"#64748b\" opacity=\"{$opacity}\" />\n";
        }

        // Slight text rotation between -3 and 3 degrees
        $rotation = rand(-3, 3);
        $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $centerX = (int) ($width / 2);
        $centerY = (int) ($height / 2);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" role="img" aria-label="Security arithmetic challenge: {$escapedText}" class="rounded-xl border border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800/80 shadow-inner select-none">
    <rect width="100%" height="100%" fill="none" />
    {$lines}
    {$dots}
    <text x="50%" y="54%" font-family="ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace" font-size="24" font-weight="700" fill="currentColor" text-anchor="middle" dominant-baseline="middle" transform="rotate({$rotation} {$centerX} {$centerY})" class="text-neutral-900 dark:text-neutral-100" letter-spacing="3">
        {$escapedText}
    </text>
</svg>
SVG;
    }
}
