# ternis.link — TypeSafe AI Spam, Phishing & Threat Detection Plan

> Date: 2026-10-29  
> Status: PROPOSED — For Review & Feedback  
> Scope: Automated spam/phishing classification for new users & guests, spam-attack burst detection, user-agent triage, persistent threat audit logging, and public & admin stats visualization using `sanmai/typesafe-ai-php`.

---

## 1. Executive Summary & Mission

As an open link shortener offering guest links on `href.nz`, `meinlink.at`, and `href.yt`, and member link spaces on `ternis.link` and `clicked.at`, the network is periodically targeted by:
1. **Malicious links**: Zero-day credential harvest pages, deceptive bank logins, fake verification portals, and spam farm cloaking.
2. **Spam attacks**: High-velocity batch provisioning of short links targeting specific campaigns.
3. **Automated vulnerability probes**: Command-line scrapers, headless exploit scripts, and crawlers harvesting redirect endpoints.

This plan introduces a **TypeSafe AI-powered Threat Intelligence Engine** utilizing `sanmai/typesafe-ai-php` (`TypeSafeClient` / Jev model). The engine runs **strictly asynchronously** (queued background evaluations upon creation and anomalous traffic spikes), ensuring zero latency impact on sub-millisecond edge redirects, while providing automated threat quarantine, database auditing, and visual metrics across public and admin dashboards.

---

## 2. Core Architectural Principles

1. **Zero Redirect Latency (Asynchronous Queue Only)**:
   - Evaluated post-submission via queued jobs (`ShouldQueue`), never blocking the HTTP request thread or client redirect execution.
   - For guests and newly registered accounts (&le; 7 days old), initial links are immediately functional but queued for threat evaluation within 1–3 seconds.
2. **Fail-Open for User Experience, Fail-Closed on Confirmed Danger**:
   - If the external AI service is unreachable, timeout occurs, or API key is absent, links remain in standard operation (with rule-based `UnsafeUrlValidator` fallback).
   - High-confidence threats (&ge; 85% confidence score for `phishing` or `malware`) trigger automated link deactivation (`is_active = false`) and admin review queuing.
3. **Privacy-Preserving Auditing**:
   - IP addresses in threat logs are stored exclusively as SHA-256 salted hashes (`creator_ip_hash`), matching the platform's GDPR privacy invariants.
   - No raw visitor IPs or personal data are fed to external model prompts.

---

## 3. Threat Detection Modules

### Module A: Destination Spam & Phishing Classification
- **Target Audience**: Guest links (`href.nz`, `meinlink.at`, `href.yt`) and new users (&le; 7 days old or &le; 5 created links).
- **Evaluation Pipeline**:
  1. Triggered on `LinkService::create` and `LinkService::update`.
  2. Background Job `EvaluateLinkThreatJob` dispatches with `link_id`.
  3. Worker fetches lightweight destination metadata (HTTP HEAD/GET: title, meta description, final redirected host, URL syntax).
  4. TypeSafe Evaluation:
     ```php
     $response = $client->systemOne(
         SystemOneRequest::build("URL: {$destinationUrl}\nTitle: {$pageTitle}\nDescription: {$pageDescription}\nHost: {$host}")
             ->choice('verdict', 'Analyze the security posture of this destination URL', [
                 'safe' => 'Legitimate e-commerce, media, article, personal website, app or documentation',
                 'phishing' => 'Impersonating brand, credential harvest, fake banking/crypto login, deceptive portal',
                 'spam' => 'Low-value SEO link farm, deceptive affiliate redirection, ad stuffing, scam giveaway',
                 'malware' => 'Drive-by download, trojan/exploit payload distribution, suspicious binary bundle',
             ])
             ->score('threat_score', 'Threat probability score from 0 (benign) to 100 (critical threat)')
     );
     ```
  5. Action Matrix:
     - **Score &ge; 85 & (`phishing` | `malware`)**:
       - `link.is_active = false`, `link.tags = array_merge(tags, ['quarantine', verdict])`
       - Logged to `threat_evaluations` as `action_taken = 'quarantined'`.
       - Admin notification dispatched.
     - **Score 60–84 & (`spam` | `phishing`)**:
       - Automatically enforce bot protection: `link.bot_protection = true`.
       - Logged as `action_taken = 'bot_protected'`.
     - **Safe**:
       - Logged as `action_taken = 'cleared'`.

### Module B: High-Velocity Spam-Attack Detection
- **Mechanism**:
  - Sliding-window Redis counters (`threat:burst:{ip_hash}`) tracking link creation velocity over 1-minute and 5-minute windows.
  - Threshold: > 10 links in 60s from a single IP or guest subnet.
  - Evaluation:
    - Gathers the batch of created slugs and destinations.
    - TypeSafe Evaluation classifies attack pattern:
      `choice('attack_type', ['none', 'distributed_phishing_campaign', 'bulk_seo_spam', 'scanner_fuzzing'])`
  - Action:
    - If campaign identified: bulk deactivate matching links, add IP hash to transient edge jail (30-minute HTTP 429), and record attack incident.

### Module C: Client & User-Agent Behavioral Triage
- **Mechanism**:
  - When uncommon, suspicious, or spoofed User-Agents hit links, redirect gates, or probe routes (`/phpmyadmin`, `.env`, `wp-login`):
  - TypeSafe Evaluation assesses client risk:
    ```php
    $response = $client->systemOne(
        SystemOneRequest::build("UA: {$userAgent}\nPath: {$path}\nHeaders: {$headerSummary}")
            ->choice('client_type', 'Classify this client signature', [
                'legitimate_browser' => 'Standard user browsing from consumer browser or webview',
                'verified_crawler'   => 'Known benign search engine or social media unfurler',
                'benign_tool'        => 'Legitimate developer tool, API client or curl testing',
                'aggressive_scraper' => 'Automated content scraper or link harvester',
                'exploit_scanner'    => 'Vulnerability probe, credential stuffer, exploit script',
            ])
            ->score('bot_confidence', 'Probability that this is an automated non-human agent (0-100)')
    );
    ```
  - Action: Automatically adds confirmed hostile patterns to `config/bot_protection.php` dynamic blocklist and enforces challenge redirects.

---

## 4. Database Schema Design

### Migration: `create_threat_evaluations_table`
```php
Schema::create('threat_evaluations', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('link_id')->nullable()->constrained('links')->nullOnDelete();
    $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('creator_ip_hash', 64)->index();
    $table->string('destination_url', 2048);
    $table->string('destination_host', 255)->index();
    $table->string('user_agent', 1024)->nullable();
    $table->string('evaluation_type', 40)->index(); // link_creation, spam_burst, user_agent_probe
    $table->string('verdict', 40)->index();          // safe, spam, phishing, malware, exploit_scanner
    $table->unsignedTinyInteger('threat_score');      // 0 - 100
    $table->decimal('confidence', 4, 3);             // 0.000 - 1.000
    $table->string('action_taken', 40)->index();     // cleared, bot_protected, quarantined, rate_limited
    $table->text('reasoning')->nullable();
    $table->json('metadata')->nullable();            // raw probabilities, token usage, model card
    $table->timestamps();

    $table->index(['created_at', 'verdict']);
});
```

---

## 5. Visualizations & Stats Page Enhancements

### 1. Public Network Telemetry (`ternis.link/pages/stats` & API `/v2/stats`)
- **Metric Cards**:
  - `Total Threats Quarantined` (live counter of phishing/malware links blocked).
  - `Automated Probes Filtered` (bots challenged via Turnstile).
  - `Clean Link Ratio` (percentage of network traffic verified safe).
- **Public Privacy Policy**: Reassures visitors that links are actively protected against spam and malicious redirects without selling tracking data.

### 2. Admin Threat Console (`admin.ternis.link/stats/security` & `AdminStatsController`)
- **Threat Event Timeline Chart**:
  - 30-day interactive area/line graph broken down by `Phishing`, `Spam`, and `Exploit Scanners`.
- **Live Security Audit Table (`Livewire/Admin/ThreatStreamTable`)**:
  - Displays: Timestamp, Link Slug / Host, Verdict Badge (Red: Phishing, Orange: Spam, Yellow: Suspicious), Confidence %, Action Taken, Destination Preview.
  - Actions:
    - **Approve / False Positive**: Re-activates link and clears quarantine.
    - **Ban Host**: Adds root domain to permanent blocklist.
- **Top Threat Vectors**:
  - Breakdown of top blocked destination domains and malicious user-agents.

---

## 6. Configuration Knobs (`config/ai_security.php`)

```php
return [
    'enabled' => env('AI_SECURITY_ENABLED', true),
    'api_key' => env('TYPESAFE_API_KEY'),
    'base_url' => env('TYPESAFE_BASE_URL'),
    'model' => env('TYPESAFE_MODEL', 'jev'),
    
    // Evaluation scopes
    'evaluate_guests' => env('AI_SECURITY_EVAL_GUESTS', true),
    'evaluate_new_users_days' => (int) env('AI_SECURITY_NEW_USER_DAYS', 7),
    
    // Decision thresholds
    'quarantine_threshold' => (int) env('AI_SECURITY_QUARANTINE_SCORE', 85),
    'bot_protection_threshold' => (int) env('AI_SECURITY_BOT_PROTECT_SCORE', 60),
    
    // Spam attack limits
    'burst_limit_per_minute' => (int) env('AI_SECURITY_BURST_LIMIT', 10),
];
```

---

## 7. Step-by-Step Implementation Roadmap

1. **Phase 1: Foundation & Configuration**
   - Create `config/ai_security.php`.
   - Create migration for `threat_evaluations` table.
   - Implement `App\Services\AiThreatDetectionService` wrapping `TypeSafeClient`.

2. **Phase 2: Asynchronous Link Evaluation Pipeline**
   - Implement `EvaluateLinkThreatJob` dispatched on guest & new user link creations.
   - Wire automated link quarantine & bot protection escalation rules.
   - Add unit & feature tests with mock AI responses.

3. **Phase 3: Attack Burst & User-Agent Screening**
   - Implement Redis sliding window rate-tracker in `AiThreatDetectionService`.
   - Wire suspicious user-agent evaluation into `RedirectController` & `ProtectedGatewayController`.

4. **Phase 4: Stats Integration & Admin Console**
   - Add aggregated threat metrics to `NetworkStats::overview()` and public `/pages/stats`.
   - Build `AdminSecurityStatsController` and interactive threat stream table in the Admin dashboard.

---

## 8. Verification & Test Plan

- **Unit Tests**:
  - Verify `SystemOneRequest` construction for link metadata and user-agent strings.
  - Verify verdict mapping (`safe`, `phishing`, `spam`, `malware`) to appropriate link actions.
  - Verify graceful fallback when `TYPESAFE_API_KEY` is null or service returns an error.
- **Feature Tests**:
  - Test guest link creation triggers `EvaluateLinkThreatJob`.
  - Test phishing verdict automatically deactivates link and generates audit log.
  - Test spam verdict escalates link to `bot_protection = true`.
  - Test public stats API exposes mitigated threat counts.
  - Test admin moderation actions (unquarantine, domain blocking).
