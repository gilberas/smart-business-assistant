<?php

namespace App\Jobs\WhatsApp;

use App\Repositories\WhatsApp\MediaRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(MediaRepository $mediaRepo): void
    {
        $expired = $mediaRepo->getExpired();

        foreach ($expired as $media) {
            if ($media->local_path && Storage::disk($media->disk ?? config('whatsapp.media.disk'))->exists($media->local_path)) {
                Storage::disk($media->disk ?? config('whatsapp.media.disk'))->delete($media->local_path);
            }

            $media->delete();

            Log::info('Cleaned up expired media', ['media_id' => $media->id, 'uuid' => $media->uuid]);
        }
    }
}
