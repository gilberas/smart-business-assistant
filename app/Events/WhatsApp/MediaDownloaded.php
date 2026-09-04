<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsApp\Media;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaDownloaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Media $media,
        public readonly string $localPath,
    ) {}
}
