<?php

namespace Database\Seeders;

use App\Enums\DomainType;
use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        $domains = [
            // Public domains
            ['hostname' => 'href.nz',          'type' => DomainType::Public],
            // Business domains
            ['hostname' => 'href.re',          'type' => DomainType::Business],
            // Ternis family domains
            ['hostname' => 'ternis.link',      'type' => DomainType::Ternis],
            ['hostname' => 'links.thosted.de', 'type' => DomainType::Ternis],
            ['hostname' => 'short.thosted.de', 'type' => DomainType::Ternis],
            ['hostname' => 'go.thosted.de',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.net',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.dev',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.org',    'type' => DomainType::Ternis],
            ['hostname' => 'go.ternis.eu',     'type' => DomainType::Ternis],
        ];

        foreach ($domains as $domain) {
            Domain::updateOrCreate(
                ['hostname' => $domain['hostname']],
                ['type' => $domain['type'], 'is_active' => true],
            );
        }
    }
}
