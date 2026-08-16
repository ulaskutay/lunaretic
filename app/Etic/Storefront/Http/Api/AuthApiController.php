<?php

namespace App\Etic\Storefront\Http\Api;

use App\Etic\Storefront\StorefrontAuth;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Customer;

class AuthApiController
{
    public function __construct(private StorefrontAuth $auth) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $parts = explode(' ', $data['name'], 2);
        $customer = Customer::query()->create([
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? $parts[0],
        ]);
        $customer->users()->attach($user);

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Giriş bilgileri hatalı.'],
            ]);
        }

        return $this->tokenResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->forget($request->bearerToken());

        return response()->json(['data' => null]);
    }

    public function account(Request $request): JsonResponse
    {
        $user = $this->user($request);

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'orders' => $user->orders()->latest()->get()->map(fn ($order) => [
                    'id' => $order->id,
                    'reference' => $order->reference,
                    'status' => $order->status,
                    'status_label' => $order->status_label ?? $order->status,
                    'total' => $order->total?->formatted(),
                    'created_at' => $order->created_at?->toIso8601String(),
                ])->values()->all(),
            ],
        ]);
    }

    private function user(Request $request): ?User
    {
        return $this->auth->user($request->bearerToken()) ?? Auth::user();
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $this->auth->issue($user),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ], $status);
    }
}
