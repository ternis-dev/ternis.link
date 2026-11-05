<?php

namespace App\Livewire\Concerns;

use App\Models\Link;
use Livewire\Attributes\Locked;

trait ChecksCreatedLinkSafety
{
    #[Locked]
    public ?string $securityCheckLinkId = null;

    public function checkCreatedLinkSafety(): void
    {
        if ($this->securityCheckLinkId === null) {
            return;
        }

        $link = Link::find($this->securityCheckLinkId);

        if (! $link || ! $link->is_active) {
            $this->securityCheckLinkId = null;
            $this->destination_url = $this->originalUrl ?? '';
            $this->shortUrl = null;
            $this->originalUrl = null;
            $this->errorKind = 'security';
            $this->urlState = 'invalid';
            $this->addError('destination_url', $this->securityBlockMessage());

            return;
        }

        if ($link->threatEvaluations()->exists()) {
            $this->securityCheckLinkId = null;
        }
    }
}
