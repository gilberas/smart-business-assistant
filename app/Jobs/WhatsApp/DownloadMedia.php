<?php

namespace App\Jobs\WhatsApp;

use App\Events\WhatsApp\MediaDownloaded;
use App\Models\WhatsApp\Message;
use App\Repositories\WhatsApp\MediaRepository;
use App\Services\WhatsApp\MediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = [5, 15, 30];

    public function __construct(
        public readonly Message $message,
        public readonly string $mediaId,
    ) {
        $this->onQueue(config('whatsapp.queues.media', 'media'));
    }

    public function handle(
        MediaService $mediaService,
        MediaRepository $mediaRepo,
    ): void {
        try {
            $media = $mediaService->downloadFromWhatsApp($this->message, $this->mediaId);

            if ($media) {
                MediaDownloaded::dispatch($media, $media->local_path);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to download media from WhatsApp', [
                'message_id' => $this->message->id,
                'media_id' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $mediaRepo->create([
                    'business_id' => $this->message->business_id,
                    'message_id' => $this->message->id,
                    'media_id' => $this->mediaId,
                    'mime_type' => 'application/octet-stream',
                    'file_name' => 'failed_' . $this->mediaId,
                    'file_size' => 0,
                ]);
            }

            throw $e;
        }
    }
}
