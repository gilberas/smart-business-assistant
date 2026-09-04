<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WhatsApp\UploadMediaRequest;
use App\Http\Resources\WhatsApp\MediaResource;
use App\Models\WhatsApp\Media;
use App\Repositories\WhatsApp\MediaRepository;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use App\Services\TenantService;
use App\Services\WhatsApp\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly MediaRepository $mediaRepo,
        private readonly WhatsAppAccountRepository $accountRepo,
        private readonly TenantService $tenantService,
    ) {}

    private function business()
    {
        return $this->tenantService->currentOrFail();
    }

    public function index(Request $request): JsonResponse
    {
        $business = $this->business();

        $media = $this->mediaRepo->getByBusinessId(
            $business->id,
            $request->integer('per_page', 15)
        );

        return response()->json(
            MediaResource::collection($media)->response()->getData()
        );
    }

    public function show(int $mediaId): JsonResponse
    {
        $business = $this->business();

        $media = Media::withoutGlobalScopes()->findOrFail($mediaId);

        if ($media->business_id !== $business->id) {
            abort(403);
        }

        return response()->json([
            'data' => new MediaResource($media),
        ]);
    }

    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $business = $this->business();
        $account = $this->accountRepo->findByBusinessId($business->id);

        if (!$account || !$account->isConnected()) {
            return response()->json(['message' => 'No connected WhatsApp account'], 400);
        }

        $file = $request->file('file');
        $path = $file->store('whatsapp/uploads/' . $business->id);

        $media = $this->mediaRepo->create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'media_id' => null,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_name' => $file->getClientOriginalName(),
            'local_path' => $path,
            'disk' => config('whatsapp.media.disk', 'local'),
        ]);

        try {
            $mediaId = $this->mediaService->uploadToWhatsApp(
                $media,
                Storage::disk(config('whatsapp.media.disk', 'local'))->path($path),
                $file->getMimeType()
            );

            $this->mediaRepo->update($media, ['media_id' => $mediaId]);
        } catch (\Throwable $e) {
            return response()->json([
                'data' => new MediaResource($media),
                'message' => 'File saved locally but failed to upload to WhatsApp: ' . $e->getMessage(),
            ], 201);
        }

        return response()->json([
            'data' => new MediaResource($media->fresh()),
            'message' => 'Media uploaded successfully',
        ], 201);
    }

    public function download(int $mediaId)
    {
        $business = $this->business();

        $media = Media::withoutGlobalScopes()->findOrFail($mediaId);

        if ($media->business_id !== $business->id) {
            abort(403);
        }

        $url = $this->mediaService->getTemporaryUrl($media);
        $localPath = $this->mediaService->getLocalPath($media);

        if ($localPath) {
            return response()->download($localPath, $media->file_name, [
                'Content-Type' => $media->mime_type,
            ]);
        }

        if ($url) {
            return redirect()->away($url);
        }

        return response()->json(['message' => 'Media not available'], 404);
    }

    public function destroy(int $mediaId): JsonResponse
    {
        $business = $this->business();

        $media = Media::withoutGlobalScopes()->findOrFail($mediaId);

        if ($media->business_id !== $business->id) {
            abort(403);
        }

        $this->mediaService->deleteMedia($media);

        return response()->json(['message' => 'Media deleted successfully']);
    }
}
