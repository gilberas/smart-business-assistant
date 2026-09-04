<?php

namespace App\Services\Broadcast;

use App\Contracts\Broadcast\TemplateServiceInterface;
use App\Models\Business;
use App\Models\WhatsappTemplate;
use App\Repositories\Broadcast\TemplateRepository;
use App\Services\WhatsApp\WhatsAppAccountService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class TemplateService implements TemplateServiceInterface
{
    public function __construct(
        private readonly TemplateRepository $templateRepo,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function paginate(Business $business, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->templateRepo->paginate($business, $filters, $perPage);
    }

    public function find(Business $business, int $id): ?WhatsappTemplate
    {
        return $this->templateRepo->findById($business, $id);
    }

    public function create(Business $business, array $data): WhatsappTemplate
    {
        return $this->templateRepo->create(array_merge($data, ['business_id' => $business->id]));
    }

    public function update(WhatsappTemplate $template, array $data): WhatsappTemplate
    {
        $this->templateRepo->update($template, $data);
        return $template->fresh();
    }

    public function delete(WhatsappTemplate $template): bool
    {
        return $this->templateRepo->delete($template);
    }

    public function syncFromMeta(Business $business): int
    {
        $account = $business->whatsappAccount;
        if (!$account || !$account->isConnected()) {
            throw new \RuntimeException('No connected WhatsApp account found for this business');
        }

        $token = $this->whatsAppService->getDecryptedToken($account);
        if (!$account->waba_id) {
            throw new \RuntimeException('WABA ID is not set');
        }

        $synced = 0;
        $page = 1;

        do {
            try {
                $response = app(\App\Services\WhatsApp\WhatsAppApiClient::class)->get($token, "/{$account->waba_id}/message_templates", [
                    'limit' => 50,
                    'after' => $page > 1 ? (string) $page : null,
                ]);

                $data = $response['body']['data'] ?? [];
                if (empty($data)) {
                    break;
                }

                foreach ($data as $templateData) {
                    $this->templateRepo->upsertByMetaId($business, [
                        'business_id' => $business->id,
                        'meta_template_id' => $templateData['id'],
                        'name' => $templateData['name'],
                        'language' => $templateData['language'] ?? 'en',
                        'category' => $templateData['category'] ?? 'UTILITY',
                        'status' => $templateData['status'] ?? 'approved',
                        'body' => $this->extractBody($templateData),
                        'header' => $this->extractHeader($templateData),
                        'footer' => $this->extractFooter($templateData),
                        'buttons' => $templateData['components'] ? array_values(array_filter($templateData['components'], fn($c) => ($c['type'] ?? '') === 'BUTTONS')) : null,
                    ]);
                    $synced++;
                }

                $paging = $response['body']['paging'] ?? [];
                $next = $paging['next'] ?? null;
                $page++;
            } catch (\Throwable $e) {
                Log::error('Failed to sync templates from Meta', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        } while ($next && $page < 20);

        Log::info('Template sync completed', ['business_id' => $business->id, 'synced' => $synced]);
        return $synced;
    }

    private function extractBody(array $templateData): string
    {
        $components = $templateData['components'] ?? [];
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                return $component['text'] ?? '';
            }
        }
        return '';
    }

    private function extractHeader(array $templateData): ?array
    {
        $components = $templateData['components'] ?? [];
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'HEADER') {
                return $component;
            }
        }
        return null;
    }

    private function extractFooter(array $templateData): ?string
    {
        $components = $templateData['components'] ?? [];
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'FOOTER') {
                return $component['text'] ?? null;
            }
        }
        return null;
    }
}
