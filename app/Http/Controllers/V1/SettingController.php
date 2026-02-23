<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\AddSocialAccountsRequest;
use App\Http\Requests\ProfileRequests\NotificationPreferenceRequest;
use App\Http\Requests\ProfileRequests\UpdateNotificationPreferencesRequest;
use App\Http\Requests\ProfileRequests\UpdatePasswordRequest;
use App\Http\Requests\ProfileRequests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\SettingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController
{
    use AuthorizesRequests;

    private SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->authorize('update', Auth::user());
        $user = Auth::user();

        $result = $this->settingService->updatePassword(
            $user,
            $request->input('current_password'),
            $request->input('new_password')
        );

        return response()->json([
            'message' => $result['message'],
        ], $result['status']);
    }

    public function delete(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->settingService->softDeleteAccount($user);

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function forceDelete(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->settingService->forceDeleteAccount($user);

        return response()->json(['message' => $result['message']], $result['status']);
    }

    public function addSocialAccounts(AddSocialAccountsRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $data = $request->only(['linkedin_username', 'github_username', 'orcid_username']);
        $result = $this->settingService->updateSocialAccounts($user, $data);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new UserResource($result['user']),
        ], $result['status']);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $data = $request->validated();
        $result = $this->settingService->updateProfile($user, $data);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new UserResource($result['user']),
        ], $result['status']);
    }

    public function getNotificationPreferences(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->settingService->getNotificationPreferences($user);

        return response()->json([
            'message' => $result['message'],
            'data' => $result['preferences'],
        ], $result['status']);
    }

    public function updateNotificationPreference(NotificationPreferenceRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->settingService->updateNotificationPreference(
            $user,
            $request->input('type'),
            $request->input('enabled')
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['preferences'],
        ], $result['status']);
    }

    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $preferences = $request->validated();
        $result = $this->settingService->updateMultiplePreferences($user, $preferences);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['preferences'],
        ], $result['status']);
    }

    public function getAccountSettings(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->settingService->getAccountSettings($user);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['settings'],
        ], $result['status']);
    }

}

