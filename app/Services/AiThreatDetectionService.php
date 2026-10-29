<?php

namespace App\Services;

use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use TypeSafeAI\SystemOneClient;
use TypeSafeAI\SystemOneRequest;
use TypeSafeAI\TypeSafeClient;

class AiThreatDetectionService
{
    public function __construct(
        private ?SystemOneClient $client = null,
    ) {
        if ($this->client === null && app()->bound(SystemOneClient::class)) {
            $this->client = app(SystemOneClient::class);
        }
    }

    public function isEnabled(): bool
    {
        return (bool) config('ai_security.enabled', true);
    }

    public function getClient(): ?SystemOneClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $apiKey = config('ai_security.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $clientOptions = [];
        if ($baseUrl = config('ai_security.base_url')) {
            $clientOptions['base_uri'] = $baseUrl;
        }

        try {
            $this->client = TypeSafeClient::createInstance(
                apiKey: $apiKey,
                clientOptions: $clientOptions,
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to initialize TypeSafeClient: '.$e->getMessage());

            return null;
        }

        return $this->client;
    }

    public function setClient(?SystemOneClient $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function isSuspiciousUrl(string $url): bool
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        $dangerousExtensions = ['.exe', '.scr', '.bat', '.vbs', '.msi', '.cmd', '.pif', '.ps1', '.apk', '.dmg', '.iso', '.jar', '.dll'];
        foreach ($dangerousExtensions as $ext) {
            if (str_ends_with($path, $ext)) {
                return true;
            }
        }

        if (preg_match('/(not-a-scam|free-crypto|free-nitro|phish|account-verify|security-update|billing-update|download-crack)/i', $host.$path)) {
            return true;
        }

        return false;
    }

    public function shouldEvaluateLink(Link $link): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        // Suspicious binaries, exploits, or scam domain patterns are always evaluated
        if ($this->isSuspiciousUrl((string) $link->destination_url)) {
            return true;
        }

        // Guest links (no user assigned)
        if ($link->user_id === null) {
            return (bool) config('ai_security.evaluate_guests', true);
        }

        $user = $link->relationLoaded('user') ? $link->user : User::find($link->user_id);
        if (! $user) {
            return false;
        }

        // Evaluate links created by accounts newer than threshold
        $newDays = (int) config('ai_security.evaluate_new_users_days', 7);
        if ($user->created_at && $user->created_at->greaterThanOrEqualTo(now()->subDays($newDays))) {
            return true;
        }

        // Evaluate accounts with very few lifetime links created
        $maxLinks = (int) config('ai_security.evaluate_max_lifetime_links', 5);
        if ($user->links()->count() <= $maxLinks) {
            return true;
        }

        return false;
    }

    public function evaluateLink(Link $link, array $context = []): ?ThreatEvaluation
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $destinationUrl = (string) $link->destination_url;
        $destinationHost = (string) parse_url($destinationUrl, PHP_URL_HOST);
        $creatorIpHash = (string) ($link->creator_ip_hash ?? $context['creator_ip_hash'] ?? '');
        $userAgent = $context['user_agent'] ?? null;

        // Check for burst attack velocity first
        if ($creatorIpHash !== '') {
            $burstVerdict = $this->checkBurstVelocity($creatorIpHash, $link);
            if ($burstVerdict !== null) {
                return $burstVerdict;
            }
        }

        $client = $this->getClient();

        if ($client === null) {
            Log::debug('TypeSafe AI evaluation skipped: No API key or client available.');

            return null;
        }

        $creatorContext = $link->user_id === null
            ? 'Guest anonymous creator'
            : sprintf(
                'User account #%s registered at %s (lifetime links: %d)',
                $link->user_id,
                $link->user?->created_at?->toIso8601String() ?? 'unknown',
                $link->user?->links()->count() ?? 1
            );

        $state = implode("\n", [
            'Destination URL: '.$destinationUrl,
            'Host: '.$destinationHost,
            'Title: '.($link->og_title ?? $link->description ?? 'None'),
            'Description: '.($link->og_description ?? 'None'),
            'Account Context: '.$creatorContext,
            'User-Agent: '.($userAgent ?? 'Unknown'),
        ]);

        $model = config('ai_security.model', 'jev');

        try {
            $request = SystemOneRequest::build($state, $model)
                ->choice('verdict', 'Analyze the destination URL and metadata for spam, phishing, deception, or malicious behavior.', [
                    'safe' => 'Benign, legitimate website or service',
                    'spam' => 'Low-quality affiliate spam, SEO link farming, deceptive marketing, junk redirects',
                    'phishing' => 'Credential harvesting, brand impersonation, deceptive login portal, fake verification',
                    'malware' => 'Drive-by download, trojan/exploit payload distribution, suspicious binary bundle',
                ])
                ->score('threat_score', 'Threat probability score from 0 (benign) to 100 (critical malicious)', [
                    'Benign, legitimate website',
                    'Mildly suspicious or low reputation',
                    'Borderline or suspected promotional spam',
                    'High probability of phishing or scam',
                    'Critical confirmed malware or credential harvester',
                ]);

            $result = $client->systemOne($request);

            $verdictAnswer = $result->choice('verdict');
            $scoreAnswer = $result->score('threat_score');

            $verdict = $verdictAnswer->choice ?? 'safe';
            $confidence = $verdictAnswer->confidence ?? 1.0;

            // ScoreAnswer::score is 0.0 .. (count(legend) - 1). Normalize to 0 .. 100.
            $maxLevel = max(1, count($scoreAnswer->legend ?? []) - 1);
            $rawScore = $scoreAnswer->score ?? 0.0;
            $threatScore = (int) round(min(100, max(0, ($rawScore / $maxLevel) * 100)));

            $quarantineThreshold = (int) config('ai_security.quarantine_threshold', 80);
            $botProtectThreshold = (int) config('ai_security.bot_protect_threshold', 60);

            $actionTaken = 'cleared';

            $isMalwareVerdict = $verdict === 'malware';
            $isHighThreatPhishing = $verdict === 'phishing' && $threatScore >= 65;
            $isSuspiciousPayload = $this->isSuspiciousUrl($destinationUrl) && in_array($verdict, ['malware', 'phishing', 'spam'], true);
            $isOverQuarantineScore = $threatScore >= $quarantineThreshold && in_array($verdict, ['phishing', 'malware'], true);

            if ($isMalwareVerdict || $isHighThreatPhishing || $isSuspiciousPayload || $isOverQuarantineScore) {
                $actionTaken = 'quarantined';
                $tags = is_array($link->tags) ? $link->tags : [];
                if (! in_array('quarantine', $tags, true)) {
                    $tags[] = 'quarantine';
                }
                if (! in_array($verdict, $tags, true)) {
                    $tags[] = $verdict;
                }
                $link->update([
                    'is_active' => false,
                    'tags' => $tags,
                ]);
                Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);
            } elseif ($threatScore >= $botProtectThreshold && in_array($verdict, ['spam', 'phishing', 'malware'])) {
                $actionTaken = 'bot_protected';
                if (! $link->bot_protection) {
                    $link->update(['bot_protection' => true]);
                    Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);
                }
            }

            return ThreatEvaluation::create([
                'link_id' => $link->id,
                'user_id' => $link->user_id,
                'creator_ip_hash' => $creatorIpHash,
                'destination_url' => $destinationUrl,
                'destination_host' => $destinationHost,
                'user_agent' => $userAgent,
                'evaluation_type' => 'link_creation',
                'verdict' => $verdict,
                'threat_score' => $threatScore,
                'confidence' => $confidence,
                'action_taken' => $actionTaken,
                'reasoning' => sprintf('Verdict %s with score %d/100 (action: %s)', $verdict, $threatScore, $actionTaken),
                'metadata' => [
                    'model' => $model,
                    'probabilities' => $verdictAnswer->probabilities ?? [],
                    'score_probabilities' => $scoreAnswer->probabilities ?? [],
                    'raw_score' => $rawScore,
                    'prompt_state' => $state,
                    'evaluated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('TypeSafe AI threat evaluation failed: '.$e->getMessage(), [
                'link_id' => $link->id,
                'exception' => $e,
            ]);

            return null;
        }
    }

    public function checkBurstVelocity(string $ipHash, Link $link): ?ThreatEvaluation
    {
        $burstKey = 'threat:burst:'.$ipHash;
        $burstLimit = (int) config('ai_security.burst_limit_per_minute', 10);

        RateLimiter::hit($burstKey, 60);

        if (RateLimiter::attempts($burstKey) > $burstLimit) {
            $link->update(['is_active' => false]);
            Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);

            return ThreatEvaluation::create([
                'link_id' => $link->id,
                'user_id' => $link->user_id,
                'creator_ip_hash' => $ipHash,
                'destination_url' => (string) $link->destination_url,
                'destination_host' => (string) parse_url($link->destination_url, PHP_URL_HOST),
                'user_agent' => null,
                'evaluation_type' => 'spam_burst',
                'verdict' => 'spam',
                'threat_score' => 95,
                'confidence' => 0.990,
                'action_taken' => 'quarantined',
                'reasoning' => sprintf('High-velocity burst detected: exceeded %d creations in 60s from IP hash %s', $burstLimit, substr($ipHash, 0, 10)),
                'metadata' => [
                    'attempts' => RateLimiter::attempts($burstKey),
                    'limit' => $burstLimit,
                ],
            ]);
        }

        return null;
    }

    public function evaluateUserAgent(string $userAgent, ?string $path = null): array
    {
        $client = $this->getClient();
        if ($client === null) {
            return [
                'verdict' => 'unverified',
                'score' => 0,
                'confidence' => 0.0,
            ];
        }

        $state = sprintf("User-Agent: %s\nPath: %s", $userAgent, $path ?? '/');

        try {
            $request = SystemOneRequest::build($state, config('ai_security.model', 'jev'))
                ->choice('client_type', 'Classify this client signature', [
                    'legitimate_browser' => 'Standard user browsing from consumer browser or webview',
                    'verified_crawler' => 'Known benign search engine or social media unfurler',
                    'benign_tool' => 'Legitimate developer tool, API client or curl testing',
                    'aggressive_scraper' => 'Automated content scraper or link harvester',
                    'exploit_scanner' => 'Vulnerability probe, credential stuffer, exploit script',
                ])
                ->score('bot_confidence', 'Probability that this is an automated non-human agent (0-100)', [
                    'Definitely human browser',
                    'Likely browser',
                    'Automated tool or crawler',
                    'Aggressive bot or scraper',
                    'Hostile scanner or exploit bot',
                ]);

            $result = $client->systemOne($request);
            $choice = $result->choice('client_type');
            $score = $result->score('bot_confidence');

            $maxLevel = max(1, count($score->legend ?? []) - 1);
            $botScore = (int) round(min(100, max(0, (($score->score ?? 0.0) / $maxLevel) * 100)));

            return [
                'verdict' => $choice->choice ?? 'legitimate_browser',
                'score' => $botScore,
                'confidence' => $choice->confidence ?? 1.0,
                'probabilities' => $choice->probabilities ?? [],
            ];
        } catch (\Throwable $e) {
            Log::warning('TypeSafe AI UA evaluation error: '.$e->getMessage());

            return [
                'verdict' => 'error',
                'score' => 0,
                'confidence' => 0.0,
            ];
        }
    }

    public function scanUrl(string $url, array $options = []): array
    {
        $url = trim($url);

        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return [
                'url' => $url,
                'host' => null,
                'is_valid' => false,
                'is_safe' => false,
                'category' => 'invalid',
                'threat_score' => 100,
                'confidence' => 1.0,
                'action_recommended' => 'reject',
                'reasoning' => 'The provided destination is not a valid URL.',
                'cached' => false,
                'scanned_at' => now()->toIso8601String(),
            ];
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return [
                'url' => $url,
                'host' => (string) parse_url($url, PHP_URL_HOST),
                'is_valid' => false,
                'is_safe' => false,
                'category' => 'invalid_scheme',
                'threat_score' => 100,
                'confidence' => 1.0,
                'action_recommended' => 'reject',
                'reasoning' => 'Only HTTP and HTTPS destination URLs are supported.',
                'cached' => false,
                'scanned_at' => now()->toIso8601String(),
            ];
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        // Check cache (1 hour)
        $cacheKey = 'url:scan:'.hash('sha256', strtolower($url));
        if (empty($options['fresh']) && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['cached'] = true;

                return $cached;
            }
        }

        // Structural safety check
        try {
            app(UnsafeUrlValidator::class)->rejectIfUnsafe($url);
        } catch (\Throwable $e) {
            $result = [
                'url' => $url,
                'host' => $host,
                'is_valid' => true,
                'is_safe' => false,
                'category' => 'intranet_blocked',
                'threat_score' => 95,
                'confidence' => 0.99,
                'action_recommended' => 'quarantine',
                'reasoning' => 'Destination points to a private intranet, loopback, or non-routable address.',
                'cached' => false,
                'scanned_at' => now()->toIso8601String(),
            ];

            Cache::put($cacheKey, $result, 3600);

            return $result;
        }

        $client = $this->getClient();
        if ($client === null) {
            return [
                'url' => $url,
                'host' => $host,
                'is_valid' => true,
                'is_safe' => true,
                'category' => 'unverified',
                'threat_score' => 0,
                'confidence' => 0.0,
                'action_recommended' => 'allow',
                'reasoning' => 'TypeSafe AI scanner unavailable (operating in fail-open mode).',
                'cached' => false,
                'scanned_at' => now()->toIso8601String(),
            ];
        }

        $state = "URL: {$url}\nHost: {$host}";

        try {
            $request = SystemOneRequest::build($state, config('ai_security.model', 'jev-latest'))
                ->choice('category', 'Classify URL destination threat risk', [
                    'safe' => 'Benign, legitimate website, public service or utility',
                    'spam' => 'Low-quality affiliate marketing, link farm, deceptive ads',
                    'phishing' => 'Credential harvester, brand impersonation, fraudulent login portal',
                    'malware' => 'Drive-by download, trojan/exploit payload distribution',
                ])
                ->score('threat_score', 'Threat probability score from 0 to 100', [
                    'Benign, legitimate website',
                    'Mildly suspicious or unknown new domain',
                    'Borderline or suspected promotional spam',
                    'High probability of phishing or scam',
                    'Critical confirmed malware or credential harvester',
                ]);

            $response = $client->systemOne($request);
            $choice = $response->choice('category');
            $score = $response->score('threat_score');

            $category = $choice->choice ?? 'safe';
            $confidence = $choice->confidence ?? 1.0;
            $maxLevel = max(1, count($score->legend ?? []) - 1);
            $threatScore = (int) round(min(100, max(0, (($score->score ?? 0.0) / $maxLevel) * 100)));

            $quarantineThreshold = (int) config('ai_security.quarantine_threshold', 80);
            $botProtectThreshold = (int) config('ai_security.bot_protect_threshold', 60);

            $isSafe = $threatScore < $botProtectThreshold && $category === 'safe';
            $action = match (true) {
                $threatScore >= $quarantineThreshold && in_array($category, ['phishing', 'malware']) => 'quarantine',
                $threatScore >= $botProtectThreshold => 'bot_protect',
                default => 'allow',
            };

            $result = [
                'url' => $url,
                'host' => $host,
                'is_valid' => true,
                'is_safe' => $isSafe,
                'category' => $category,
                'threat_score' => $threatScore,
                'confidence' => $confidence,
                'action_recommended' => $action,
                'reasoning' => sprintf('Category %s with threat score %d/100 (recommended action: %s)', $category, $threatScore, $action),
                'cached' => false,
                'scanned_at' => now()->toIso8601String(),
            ];

            Cache::put($cacheKey, $result, 3600);

            return $result;
        } catch (\Throwable $e) {
            Log::warning('TypeSafe AI scanUrl failed: '.$e->getMessage(), ['url' => $url]);

            return [
                'url' => $url,
                'host' => $host,
                'is_valid' => true,
                'is_safe' => true,
                'category' => 'unverified',
                'threat_score' => 0,
                'confidence' => 0.0,
                'action_recommended' => 'allow',
                'reasoning' => 'TypeSafe AI scanner error (failing open).',
                'cached' => false,
                'scanned_at' => now()->toIso8601String(),
            ];
        }
    }
}
