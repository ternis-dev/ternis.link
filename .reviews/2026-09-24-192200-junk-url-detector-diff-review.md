# Code Review: Diff on `app/Services/JunkUrlDetector.php`

**Date:** 2026-09-24 19:22:00 CEST  
**Target:** [`app/Services/JunkUrlDetector.php`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Services/JunkUrlDetector.php)  
**Review Status:** Changes Requested (Major False Positives Identified)

---

## Executive Summary

The changes in this diff attempt to expand detection for vulnerability scanner payloads (adding additional probe keywords, filenames, source control directories, backup extensions, and penultimate domain label checks). While some additions are beneficial (e.g., adding `declare(strict_types=1);`, `rawurldecode()` path normalization, and dedicated regex for `/.git/` and `/.svn/` paths), the diff introduces **severe false positives** that will break core link shortening functionality for legitimate users, particularly developers.

Most critically:
1. `str_contains($host, '.git')` causes `blog.github.com`, `docs.gitlab.com`, `raw.githubusercontent.com`, and `api.github.com` to be flagged as scanner junk.
2. Short 3-letter keyword `'pma'` in `PROBE_KEYWORDS` flags common domains like `topman.com`, `shopmanager.com`, `tripmanager.com`, `shipmate.com`, and organizations like `apma.org`.
3. `'solr'` in `PROBE_KEYWORDS` blocks the official Apache Solr homepage (`solr.apache.org`).
4. The penultimate label check flags any 3-label domain whose second-level name is in `FILE_EXTENSION_TLDS` (e.g., `sub.example.com`, `app.local.ch`, `news.json.org`).

---

## Detailed Findings

### 1. Critical / Blocker: Host-level VCS check flags GitHub, GitLab, and other services
- **Location:** [`app/Services/JunkUrlDetector.php#L164-L166`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Services/JunkUrlDetector.php#L164-L166)
- **Code:**
  ```php
  if (str_contains($host, '.git') || str_contains($host, '.svn')) {
      $reasons[] = 'host targets a VCS repository structure';
  }
  ```
- **Issue:** Any subdomain under `github.com` or `gitlab.com` contains `.git` (e.g., `blog.github.com` contains `.github.com`, which matches `.git`).
- **Reproducible False Positives:**
  - `https://blog.github.com` $\rightarrow$ `["host targets a VCS repository structure"]`
  - `https://docs.gitlab.com` $\rightarrow$ `["host targets a VCS repository structure"]`
  - `https://raw.githubusercontent.com/user/repo/master/file.txt` $\rightarrow$ `["host targets a VCS repository structure"]`
  - `https://cdn.githack.com` $\rightarrow$ `["host targets a VCS repository structure"]`
  - `https://api.gitkraken.com` $\rightarrow$ `["host targets a VCS repository structure"]`
- **Impact:** In a developer-centric URL shortener, users will be unable to shorten or redirect to GitHub, GitLab, or GitBook subdomains, and `links:purge-junk --apply` would deactivate existing valid links.
- **Recommendation:** Remove this host check entirely. Path-level VCS scanning is already handled safely by `preg_match('#(?:^|/)\.(?:git|svn)(?:/|$)#', $normalized)`. For hosts, payloads like `https://.git` are already caught by `str_starts_with($host, '.')`, and `foo.git` is caught by `FILE_EXTENSION_TLDS`.

---

### 2. Critical / Blocker: Short keyword `'pma'` causes broad substring false positives
- **Location:** [`app/Services/JunkUrlDetector.php#L38`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Services/JunkUrlDetector.php#L38)
- **Code:**
  ```php
  private const PROBE_KEYWORDS = [
      ...
      'pma',
  ];
  ```
- **Issue:** `pma` is only 3 characters. `str_contains($host, 'pma')` flags any host containing `pma` anywhere in its domain name.
- **Reproducible False Positives:**
  - `https://topman.com` $\rightarrow$ matches `pma` in `topman`
  - `https://shopmanager.com` $\rightarrow$ matches `pma` in `shopmanager`
  - `https://tripmanager.com` $\rightarrow$ matches `pma` in `tripmanager`
  - `https://shipmate.com` $\rightarrow$ matches `pma` in `shipmate`
  - `https://apma.org` (American Podiatric Medical Assoc.) $\rightarrow$ matches `pma`
  - `https://upma.org` $\rightarrow$ matches `pma`
  - `https://groupma.com` $\rightarrow$ matches `pma`
- **Recommendation:** Remove `'pma'` from `PROBE_KEYWORDS`. If targeting phpMyAdmin path probes, rely on exact filenames or path checks like `preg_match('#(?:^|/)(?:pma|phpmyadmin)(?:/|$)#', $normalized)`.

---

### 3. Critical / Blocker: `'solr'` in `PROBE_KEYWORDS` blocks official Apache Solr and search hosts
- **Location:** [`app/Services/JunkUrlDetector.php#L42`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Services/JunkUrlDetector.php#L42)
- **Issue:** `str_contains($host, 'solr')` flags `https://solr.apache.org` (the official Apache Solr homepage) as scanner junk.
- **Recommendation:** Target specific Solr endpoint paths (e.g. `/solr/admin/`) rather than flagging any host containing the word `solr`.

---

### 4. Critical / Blocker: Penultimate label check misidentifies valid subdomains as file extensions
- **Location:** [`app/Services/JunkUrlDetector.php#L144-L150`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Services/JunkUrlDetector.php#L144-L150)
- **Code:**
  ```php
  // Check second-to-last label if a backup extension was appended (e.g., domain.com.bak)
  if (count($labels) > 2) {
      $penultimate = $labels[count($labels) - 2];
      if (in_array($penultimate, self::FILE_EXTENSION_TLDS, true)) {
          $reasons[] = "host contains embedded file extension .{$penultimate}";
      }
  }
  ```
- **Issue:**
  - In `domain.com.bak`, the penultimate label is `com`, which is not in `FILE_EXTENSION_TLDS`. (And `bak` is already caught as TLD).
  - Meanwhile, `FILE_EXTENSION_TLDS` includes dictionary words and valid domain names: `'example'`, `'local'`, `'config'`, `'json'`, `'git'`, `'svn'`, `'sql'`, `'log'`.
  - For any 3-label domain, `$labels[count - 2]` is the registered domain label (e.g. `example` in `sub.example.com`).
- **Reproducible False Positives:**
  - `https://sub.example.com` $\rightarrow$ `["host contains embedded file extension .example"]`
  - `https://app.local.ch` $\rightarrow$ `["host contains embedded file extension .local"]`
  - `https://news.json.org` $\rightarrow$ `["host contains embedded file extension .json"]`
  - `https://repo.git.org` $\rightarrow$ `["host contains embedded file extension .git"]`
- **Recommendation:** Remove this penultimate check. A backup TLD (`.bak`, `.old`) is already caught by the `$tld` checks (`FILE_EXTENSION_TLDS` and `BACKUP_LABEL_PATTERN`).

---

### 5. Medium: `'well-known'` and `'autodiscover'` in `PROBE_KEYWORDS`
- **Location:** [`app/Services/JunkUrlDetector.php#L43-L44`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Services/JunkUrlDetector.php#L43-L44)
- **Issue:**
  - `well-known`: RFC 8615 well-known URIs (`/.well-known/openid-configuration`, `/.well-known/security.txt`) are standard internet standards, not scanner junk. Furthermore, in `pathReasons()`, `! str_contains($last, '-')` prevents `'well-known'` from ever matching in path segments because it contains a hyphen, making it dead code for paths and only acting as a host match (blocking domains like `well-known.dev`).
  - `autodiscover`: `autodiscover.company.com` is standard Microsoft Exchange infrastructure. Flagging `autodiscover` in hosts prevents legitimate mail setup link sharing.
- **Recommendation:** Remove `'well-known'` and avoid checking `'autodiscover'` on hostnames.

---

### 6. Medium: Backup path pattern matches standard Debian source packages
- **Location:** [`app/Services/JunkUrlDetector.php#L193`](file:///Users/fabianternis/Code/GitHub/ternis-dev/ternis.link/app/Services/JunkUrlDetector.php#L193)
- **Code:**
  ```php
  preg_match('#\.(bak|old|save|backup|swp|tmp|temp|bkp|orig)(?:\.|/|$)#', $normalized) === 1
  ```
- **Issue:** Matches `.orig.` in standard Debian packaging source archives (e.g., `https://deb.debian.org/.../pkg_1.0.orig.tar.gz`).
- **Recommendation:** Restrict backup markers to the end of the filename/path segment (e.g. `(?:\.(?:bak|old|save|backup|swp|tmp|temp|bkp)|~)(?:/|$)`), or ensure `.orig.` is only flagged when not part of an archive extension like `.orig.tar.gz`.

---

### 7. Positive Improvements in the Diff
- **`declare(strict_types=1);`:** Improves type safety across the service.
- **`rawurldecode($rawPath)`:** Correctly normalizes percent-encoded probe attempts such as `%2e%65%6e%76` (`.env`) or `%7e` (`~`).
- **Path-level VCS regex:** `preg_match('#(?:^|/)\.(?:git|svn)(?:/|$)#', $normalized)` is precise and avoids false positives on files like `.gitignore` or `.github/workflows`.
- **Additional probe filenames:** `'web.config'` and `'database.yml'` are accurate additions for standard configuration file probes.

---

### 8. Minor / Documentation
- **Class Docblock Removal:** The original class docblock explained the design philosophy ("Deliberately conservative: anything unrecognized passes. No DNS lookups — they are slow, flaky..."). Retaining this rationale prevents future regressions.
- **Redundant Host Probe Filename Check:** In `reasons()`, `in_array($host, self::PROBE_FILENAMES, true)` is redundant because every filename in `PROBE_FILENAMES` is already caught by the dotless check, leading dot check, or `FILE_EXTENSION_TLDS`.

---

## Conclusion & Suggested Action

Do not merge or commit the current `JunkUrlDetector.php` update as-is. Revert the problematic host checks (`.git`/`.svn` substring, `pma`, `solr`, and penultimate domain label checks) before deploying, as they will cause widespread false rejections on valid links.
