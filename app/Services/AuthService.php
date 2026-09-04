<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterUserDTO;
use App\DTOs\UpdateProfileDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function register(RegisterUserDTO $dto): User
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        event(new Registered($user));

        return $user;
    }

    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => [__('auth.inactive')],
            ]);
        }

        $user->markAsLoggedIn(request()->ip());

        $token = $user->createToken(
            name: 'auth-token',
            expiresAt: now()->addDays(30),
        )->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
    }

    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill(['password' => $password]);
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    public function verifyEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => [__('verification.already_verified')],
            ]);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));
    }

    public function resendVerificationEmail(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => [__('verification.already_verified')],
            ]);
        }

        $user->sendEmailVerificationNotification();

        return __('verification.sent');
    }

    public function confirmPassword(User $user, string $password): bool
    {
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password')],
            ]);
        }

        return true;
    }

    public function updateProfile(User $user, UpdateProfileDTO $dto): User
    {
        $data = array_filter([
            'name' => $dto->name,
            'phone' => $dto->phone,
            'timezone' => $dto->timezone,
            'locale' => $dto->locale,
            'preferences' => $dto->preferences,
        ], fn ($value) => $value !== null);

        if ($dto->avatar) {
            $path = $dto->avatar->store('avatars', 'public');
            $data['avatar'] = $path;

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
        }

        return $this->userRepository->update($user, $data);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password')],
            ]);
        }

        return $this->userRepository->updatePassword($user, $newPassword);
    }
}
