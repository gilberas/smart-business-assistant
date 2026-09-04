<?php

namespace App\Repositories\WhatsApp;

use App\Enums\WhatsApp\PhoneNumberStatus;
use App\Models\WhatsApp\PhoneNumber;

class PhoneNumberRepository
{
    public function findByBusinessId(int $businessId): iterable
    {
        return PhoneNumber::where('business_id', $businessId)->get();
    }

    public function findById(int $id): ?PhoneNumber
    {
        return PhoneNumber::find($id);
    }

    public function findByUuid(string $uuid): ?PhoneNumber
    {
        return PhoneNumber::where('uuid', $uuid)->first();
    }

    public function findByPhoneNumber(int $businessId, string $phoneNumber): ?PhoneNumber
    {
        return PhoneNumber::where('business_id', $businessId)
            ->where('phone_number', $phoneNumber)
            ->first();
    }

    public function create(array $data): PhoneNumber
    {
        return PhoneNumber::create($data);
    }

    public function update(PhoneNumber $phoneNumber, array $data): PhoneNumber
    {
        $phoneNumber->update($data);
        return $phoneNumber->fresh();
    }

    public function delete(PhoneNumber $phoneNumber): void
    {
        $phoneNumber->delete();
    }

    public function syncFromMeta(int $businessId, int $whatsappAccountId, array $phoneNumbers): array
    {
        $synced = [];
        foreach ($phoneNumbers as $data) {
            $data['display_phone'] = $data['display_phone'] ?? $data['display_phone_number'] ?? null;
            $data['status'] = $data['status'] ?? PhoneNumberStatus::Active->value;
            $phone = $this->findByPhoneNumber($businessId, $data['phone_number'] ?? $data['display_phone_number']);
            if ($phone) {
                $this->update($phone, array_merge($data, [
                    'whatsapp_account_id' => $whatsappAccountId,
                    'phone_number_id' => $data['id'] ?? $data['phone_number_id'] ?? null,
                ]));
            } else {
                $phone = $this->create(array_merge($data, [
                    'business_id' => $businessId,
                    'whatsapp_account_id' => $whatsappAccountId,
                    'phone_number_id' => $data['id'] ?? $data['phone_number_id'] ?? null,
                ]));
            }
            $synced[] = $phone;
        }
        return $synced;
    }
}
