<?php

namespace App\Services\Knowledge;

use App\Models\Business;
use App\Models\BusinessInformation;
use App\Repositories\Knowledge\BusinessInformationRepository;
use Illuminate\Validation\ValidationException;

class BusinessInformationService
{
    public function __construct(
        private readonly BusinessInformationRepository $infoRepository,
    ) {}

    public function list(Business $business, array $params = []): iterable
    {
        return $this->infoRepository->all($business, $params);
    }

    public function get(Business $business, string $uuid): BusinessInformation
    {
        $info = $this->infoRepository->findByUuid($business, $uuid);
        if (!$info) {
            throw ValidationException::withMessages([
                'info' => [__('knowledge.info_not_found')],
            ]);
        }
        return $info;
    }

    public function create(Business $business, array $data): BusinessInformation
    {
        $existing = $this->infoRepository->findByKey($business, $data['key']);
        if ($existing) {
            throw ValidationException::withMessages([
                'key' => [__('knowledge.info_key_exists')],
            ]);
        }
        return $this->infoRepository->create($business, $data);
    }

    public function update(Business $business, BusinessInformation $info, array $data): BusinessInformation
    {
        if (!empty($data['key']) && $data['key'] !== $info->key) {
            $existing = $this->infoRepository->findByKey($business, $data['key']);
            if ($existing && $existing->id !== $info->id) {
                throw ValidationException::withMessages([
                    'key' => [__('knowledge.info_key_exists')],
                ]);
            }
        }
        return $this->infoRepository->update($info, $data);
    }

    public function delete(Business $business, BusinessInformation $info): void
    {
        $this->infoRepository->delete($info);
    }

    public function grouped(Business $business): array
    {
        return $this->infoRepository->getGroupedByType($business);
    }
}