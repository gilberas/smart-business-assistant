<?php

namespace App\Services\Customer;

use App\Enums\ActivityType;
use App\Jobs\ImportCustomersJob;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\User;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\Customer\CustomerTagRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerImportService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerTagRepository $tagRepository,
    ) {}

    public function preview(Business $business, array $rows): array
    {
        $preview = [];
        $errors = [];
        $duplicates = [];

        foreach ($rows as $index => $row) {
            $rowErrors = [];

            if (empty($row['name'])) {
                $rowErrors[] = __('customers.import_name_required');
            }

            if (empty($row['phone']) && empty($row['email'])) {
                $rowErrors[] = __('customers.import_phone_or_email_required');
            }

            if (!empty($row['phone']) && $this->customerRepository->findByPhone($business, $row['phone'])) {
                $duplicates[] = $row;
            }

            if (!empty($row['email']) && $this->customerRepository->findByEmail($business, $row['email'])) {
                $duplicates[] = $row;
            }

            $preview[] = [
                'row' => $index + 1,
                'data' => $row,
                'errors' => $rowErrors,
                'is_valid' => empty($rowErrors),
            ];

            if (!empty($rowErrors)) {
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => $rowErrors,
                ];
            }
        }

        return [
            'preview' => $preview,
            'errors' => $errors,
            'duplicates' => $duplicates,
            'total' => count($rows),
            'valid' => count(array_filter($preview, fn($p) => $p['is_valid'])),
            'invalid' => count($errors),
            'duplicate_count' => count($duplicates),
        ];
    }

    public function import(Business $business, User $user, array $rows, array $options = []): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                if (empty($row['name'])) {
                    $skipped++;
                    continue;
                }

                if (empty($row['phone']) && empty($row['email'])) {
                    $skipped++;
                    continue;
                }

                if (!($options['update_duplicates'] ?? false)) {
                    if (!empty($row['phone']) && $this->customerRepository->findByPhone($business, $row['phone'])) {
                        $skipped++;
                        continue;
                    }
                    if (!empty($row['email']) && $this->customerRepository->findByEmail($business, $row['email'])) {
                        $skipped++;
                        continue;
                    }
                }

                $data = [
                    'name' => $row['name'],
                    'phone' => $row['phone'] ?? null,
                    'phone_alt' => $row['phone_alt'] ?? null,
                    'email' => $row['email'] ?? null,
                    'source' => 'import',
                    'status' => 'active',
                    'lifecycle_stage' => $row['lifecycle_stage'] ?? 'lead',
                    'customer_type' => $row['customer_type'] ?? 'individual',
                    'company_name' => $row['company_name'] ?? null,
                    'job_title' => $row['job_title'] ?? null,
                    'website_url' => $row['website_url'] ?? null,
                    'bio' => $row['bio'] ?? null,
                    'gender' => $row['gender'] ?? null,
                    'birthday' => $row['birthday'] ?? null,
                    'nationality' => $row['nationality'] ?? null,
                    'timezone' => $row['timezone'] ?? null,
                    'preferred_language' => $row['preferred_language'] ?? null,
                ];

                $customer = $this->customerRepository->create($business, $data);

                if (!empty($row['tags'])) {
                    $tagNames = is_array($row['tags']) ? $row['tags'] : explode(',', $row['tags']);
                    $tagNames = array_map('trim', $tagNames);
                    $tagIds = $this->tagRepository->syncTags($business, $tagNames);
                    $customer->tags()->sync($tagIds);
                }

                CustomerActivity::record(
                    $business->id,
                    $customer->id,
                    ActivityType::Imported,
                    $user->id,
                    __('customers.activity_imported'),
                );

                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 1,
                    'message' => $e->getMessage(),
                ];
                $skipped++;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count($rows),
        ];
    }
}
