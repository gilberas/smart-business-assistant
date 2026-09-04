<?php

namespace App\Jobs\WhatsApp;

use App\Repositories\WhatsApp\MessageRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOutgoingQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(MessageRepository $messageRepo): void
    {
        $pending = $messageRepo->getPendingOutbound(20);

        foreach ($pending as $message) {
            SendOutgoingMessage::dispatch($message);
        }
    }
}
