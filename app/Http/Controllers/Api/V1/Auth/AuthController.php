<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\Auth\UserResource;
use App\Managers\TenantManager;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterUserDTO::fromRequest($request->validated());
        $user = $this->authService->register($dto);

        return response()->json([
            'message' => __('auth.registration_successful'),
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request->validated());
        $result = $this->authService->login($dto);

        $user = $result['user'];
        $user->load('businesses');

        $tenantManager = app(TenantManager::class);
        $tenantManager->resolve($user);
        $result['current_business'] = $tenantManager->current();

        return response()->json(new AuthResource($result));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => __('auth.logout_successful')]);
    }

    public function logoutAllDevices(Request $request): JsonResponse
    {
        $this->authService->logoutAllDevices($request->user());

        return response()->json(['message' => __('auth.logout_all_successful')]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->sendPasswordResetLink($request->input('email'));

        return response()->json(['message' => $message]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->resetPassword($request->validated());

        return response()->json(['message' => $message]);
    }

    public function verifyEmail(VerifyEmailRequest $request, int $id, string $hash): JsonResponse
    {
        $user = $request->user() ?? User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthenticationException(__('verification.invalid_signature'));
        }

        $this->authService->verifyEmail($user);

        return response()->json(['message' => __('verification.verified')]);
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $message = $this->authService->resendVerificationEmail($request->user());

        return response()->json(['message' => $message]);
    }

    public function confirmPassword(ConfirmPasswordRequest $request): JsonResponse
    {
        $this->authService->confirmPassword($request->user(), $request->input('password'));

        return response()->json(['message' => __('auth.password_confirmed')]);
    }
}
