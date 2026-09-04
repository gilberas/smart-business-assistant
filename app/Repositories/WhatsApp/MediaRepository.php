<?php

namespace App\Repositories\WhatsApp;

use App\Models\WhatsApp\Media;

class MediaRepository
{
    public function findById(int $id): ?Media
    {
        return Media::find($id);
    }

    public function findByUuid(string $uuid): ?Media
    {
        return Media::where('uuid', $uuid)->first();
    }

    public function findByMediaId(string $mediaId): ?Media
    {
        return Media::where('media_id', $mediaId)->first();
    }

    public function getByBusinessId(int $businessId, int $perPage = 15): mixed
    {
        return Media::where('business_id', $businessId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByMessageId(int $messageId): iterable
    {
        return Media::where('message_id', $messageId)->get();
    }

    public function create(array $data): Media
    {
        return Media::create($data);
    }

    public function update(Media $media, array $data): Media
    {
        $media->update($data);
        return $media->fresh();
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }

    public function getExpired(): iterable
    {
        return Media::expired()->get();
    }

    public function getUnprocessed(int $limit = 20): iterable
    {
        return Media::whereNull('local_path')
            ->whereNotNull('media_id')
            ->limit($limit)
            ->get();
    }
}
