<?php

namespace App\Services\WhatsApp;

use App\Jobs\WhatsApp\DownloadMedia as DownloadMediaJob;
use App\Models\WhatsApp\Media;
use App\Models\WhatsApp\Message;
use App\Repositories\WhatsApp\MediaRepository;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function __construct(
        private readonly MediaRepository $mediaRepo,
        private readonly WhatsAppAccountRepository $accountRepo,
        private readonly WhatsAppApiClient $apiClient,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function queueDownload(Message $message, string $mediaId): void
    {
        DownloadMediaJob::dispatch($message, $mediaId);
    }

    public function downloadFromWhatsApp(Message $message, string $mediaId): ?Media
    {
        $account = $this->accountRepo->findByBusinessId($message->business_id);
        if (!$account || !$account->isConnected()) {
            Log::error('Cannot download media: no connected account', [
                'business_id' => $message->business_id,
                'media_id' => $mediaId,
            ]);
            return null;
        }

        $token = $this->whatsAppService->getDecryptedToken($account);
        if (!$token) {
            return null;
        }

        $mediaInfo = $this->apiClient->getMediaUrl($token, $mediaId);

        if (($mediaInfo['status_code'] ?? 0) !== 200) {
            throw new \RuntimeException('Failed to get media URL from WhatsApp');
        }

        $url = $mediaInfo['body']['url'] ?? null;
        $mimeType = $mediaInfo['body']['mime_type'] ?? 'application/octet-stream';
        $sha256 = $mediaInfo['body']['sha256'] ?? null;
        $fileSize = $mediaInfo['body']['file_size'] ?? null;
        $fileName = $mediaInfo['body']['filename'] ?? $mediaId;

        if (!$url) {
            throw new \RuntimeException('Media URL not found in response');
        }

        $downloadResult = $this->apiClient->downloadMedia($token, $url);

        if (($downloadResult['status_code'] ?? 0) !== 200) {
            throw new \RuntimeException('Failed to download media content');
        }

        $disk = config('whatsapp.media.disk', 'local');
        $path = config('whatsapp.media.path', 'whatsapp/media') . '/' . $message->business_id . '/' . $mediaId;

        Storage::disk($disk)->put($path, $downloadResult['body']);

        $media = $this->mediaRepo->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'business_id' => $message->business_id,
            'message_id' => $message->id,
            'media_id' => $mediaId,
            'mime_type' => $mimeType,
            'sha256' => $sha256,
            'file_size' => (int) $fileSize,
            'file_name' => $fileName,
            'local_path' => $path,
            'disk' => $disk,
            'url' => $url,
        ]);

        $expiresAt = $mediaInfo['body']['expiry'] ?? null;
        if ($expiresAt) {
            $this->mediaRepo->update($media, [
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            ]);
        }

        return $media;
    }

    public function uploadToWhatsApp(Message $message, string $localPath, string $mimeType): string
    {
        $account = $this->accountRepo->findByBusinessId($message->business_id);
        if (!$account || !$account->isConnected()) {
            throw new \RuntimeException('No connected WhatsApp account');
        }

        $token = $this->whatsAppService->getDecryptedToken($account);

        $phoneNumber = $message->phone_number_id;
        if (!$phoneNumber) {
            $primaryPhone = $account->phoneNumbers()->first();
            if ($primaryPhone) {
                $phoneNumber = $primaryPhone->phone_number_id;
            }
        }

        if (!$phoneNumber) {
            throw new \RuntimeException('No phone number ID available for upload');
        }

        $response = $this->apiClient->uploadMedia($token, $phoneNumber, $localPath, $mimeType);

        if (($response['status_code'] ?? 0) !== 200) {
            throw new \RuntimeException('Failed to upload media to WhatsApp');
        }

        return $response['body']['id'] ?? '';
    }

    public function getTemporaryUrl(Media $media): string
    {
        if ($media->temporary_url && $media->expires_at && $media->expires_at->isFuture()) {
            return $media->temporary_url;
        }

        $account = $this->accountRepo->findByBusinessId($media->business_id);
        if (!$account || !$account->isConnected()) {
            return Storage::disk($media->disk ?? config('whatsapp.media.disk'))->url($media->local_path);
        }

        $token = $this->whatsAppService->getDecryptedToken($account);
        $mediaInfo = $this->apiClient->getMediaUrl($token, $media->media_id);

        $url = $mediaInfo['body']['url'] ?? null;
        $expiresAt = $mediaInfo['body']['expiry'] ?? null;

        if ($url) {
            $this->mediaRepo->update($media, [
                'temporary_url' => $url,
                'expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : null,
            ]);
        }

        return $url ?? Storage::disk($media->disk ?? config('whatsapp.media.disk'))->url($media->local_path);
    }

    public function getLocalPath(Media $media): ?string
    {
        if ($media->local_path && Storage::disk($media->disk ?? config('whatsapp.media.disk'))->exists($media->local_path)) {
            return Storage::disk($media->disk ?? config('whatsapp.media.disk'))->path($media->local_path);
        }

        return null;
    }

    public function deleteMedia(Media $media): void
    {
        if ($media->local_path) {
            $disk = $media->disk ?? config('whatsapp.media.disk');
            if (Storage::disk($disk)->exists($media->local_path)) {
                Storage::disk($disk)->delete($media->local_path);
            }
        }

        $this->mediaRepo->delete($media);
    }
}
