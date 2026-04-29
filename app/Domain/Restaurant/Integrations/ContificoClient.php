<?php

namespace App\Domain\Restaurant\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ContificoClient
{
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return Collection<int, array<string, mixed>>
     */
    public function documents(array $query): Collection
    {
        return $this->paginated('registro/documento/', $query);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function product(string $id): ?array
    {
        $response = $this->request()->get("producto/{$id}/");

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function paginated(string $endpoint, array $query): Collection
    {
        $documents = collect();
        $page = 1;
        $pageSize = (int) ($query['result_size'] ?? 100);

        do {
            $response = $this->request()->get($endpoint, [
                ...$query,
                'result_size' => $pageSize,
                'result_page' => $page,
            ]);

            $response->throw();

            $items = collect($response->json() ?? []);
            $documents = $documents->merge($items);
            $page++;
        } while ($items->count() === $pageSize);

        return $documents->values();
    }

    private function request(): PendingRequest
    {
        $apiKey = $this->apiKey ?? config('services.contifico.api_key');
        $baseUrl = rtrim((string) ($this->baseUrl ?? config('services.contifico.base_url')), '/');

        if (! $apiKey) {
            throw new RuntimeException('CONTIFICO_API_KEY is not configured.');
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withHeaders(['Authorization' => $apiKey])
            ->timeout(30)
            ->retry(2, 500);
    }
}
