<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Models\Domain;
use App\Services\DomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function __construct(
        private DomainService $domains,
    ) {}

    /**
     * GET /v1/domains — Active system domains plus the user's own domains.
     */
    public function index(Request $request): JsonResponse
    {
        $domains = Domain::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->whereNull('user_id')
                ->orWhere('user_id', $request->user()->id))
            ->withCount('links')
            ->orderBy('hostname')
            ->paginate(25);

        return response()->json($domains);
    }

    /**
     * POST /v1/domains — Register a custom domain (starts unverified).
     */
    public function store(StoreDomainRequest $request): JsonResponse
    {
        $domain = $this->domains->createForUser(
            $request->user(),
            $request->validated('hostname'),
        );

        return response()->json($this->present($domain->fresh()), 201);
    }

    /**
     * GET /v1/domains/{domain} — Domain detail (+ DNS instructions if unverified).
     */
    public function show(Request $request, Domain $domain): JsonResponse
    {
        $this->authorizeView($request, $domain);

        return response()->json($this->present($domain));
    }

    /**
     * POST /v1/domains/{domain}/verify — Attempt DNS TXT verification.
     */
    public function verify(Request $request, Domain $domain): JsonResponse
    {
        $this->authorizeManage($request, $domain);

        if ($this->domains->verify($domain)) {
            return response()->json(['verified' => true, 'domain' => $domain->fresh()->loadCount('links')]);
        }

        return response()->json([
            'message' => 'Verification TXT record not found. Publish the record below, wait for DNS propagation, and retry.',
            'verified' => false,
            'verification' => $this->domains->instructions($domain),
        ], 422);
    }

    /**
     * DELETE /v1/domains/{domain} — Deactivate an owned domain (links preserved).
     */
    public function destroy(Request $request, Domain $domain): JsonResponse
    {
        $this->authorizeManage($request, $domain);

        if ($domain->isSystemDomain()) {
            abort(403, 'System domains cannot be deleted.');
        }

        $this->domains->deactivate($domain);

        return response()->json(null, 204);
    }

    /**
     * Serialize a domain, attaching DNS instructions while unverified.
     */
    private function present(Domain $domain): array
    {
        $domain->loadCount('links');

        $data = $domain->toArray();

        if (! $domain->isSystemDomain() && ! $domain->isVerified()) {
            $data['verification'] = $this->domains->instructions($domain);
        }

        return $data;
    }

    private function authorizeView(Request $request, Domain $domain): void
    {
        $user = $request->user();

        if ($domain->isSystemDomain()) {
            return;
        }

        if ($domain->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'You do not own this domain.');
        }
    }

    private function authorizeManage(Request $request, Domain $domain): void
    {
        $user = $request->user();

        if (! $domain->isSystemDomain()
            && ($domain->user_id === $user->id || $user->isAdmin())) {
            return;
        }

        // System domains can only be inspected by admins, never modified here.
        abort(403, 'You do not own this domain.');
    }
}
