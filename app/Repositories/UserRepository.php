<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->update(['password' => $password]);

        return $user->fresh();
    }

    public function markEmailAsVerified(User $user): User
    {
        $user->update(['email_verified_at' => now()]);

        return $user->fresh();
    }
}
