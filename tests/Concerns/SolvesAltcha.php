<?php

namespace Tests\Concerns;

use AltchaOrg\Altcha\Algorithm\Sha;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\Solution;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Services\AltchaService;

/**
 * Mints real solved Altcha payloads for guest-form tests. Uses a
 * trivial PoW cost so the solve loop stays instant.
 */
trait SolvesAltcha
{
    protected function solvedAltchaPayload(): string
    {
        config(['services.altcha.cost' => 5]);

        $service = app(AltchaService::class);
        $challenge = Challenge::fromArray($service->challenge());

        $solution = (new Altcha(hmacSignatureSecret: $service->secret()))->solveChallenge(
            new SolveChallengeOptions(challenge: $challenge, algorithm: new Sha(), timeout: 10.0)
        );

        $this->assertNotNull($solution);

        assert($solution instanceof Solution);

        return (new Payload(challenge: $challenge, solution: $solution))->toBase64();
    }
}
