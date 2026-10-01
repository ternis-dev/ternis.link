<?php

namespace App\Services;

class CrawlerDetector
{
    private const PATTERN = '/slackbot|twitterbot|facebookexternalhit|linkedinbot|whatsapp|telegrambot|discordbot|embedly|quora|pinterest|google.*snippet|bingpreview|applebot|mastodon|pleroma/i';

    public function isCrawler(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return false;
        }

        return preg_match(self::PATTERN, $userAgent) === 1;
    }
}
