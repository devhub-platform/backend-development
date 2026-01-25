<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProfileRequests\ProfileRequest;
use App\Http\Requests\ProfileRequests\UpdatePasswordRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Mail\PasswordUpdatedSuccessfullyMail;
use App\Models\User;
use App\Services\ViewedPostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Exceptions\JWTException;

class ProfileController
{
    use AuthorizesRequests;

    public function show()
    {
        $user = Auth::user();
        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar_url' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'avatar_url.required' => 'Avatar image is required',
            'avatar_url.image' => 'The avatar must be a valid image file',
            'avatar_url.mimes' => 'Avatar must be in JPEG, PNG, JPG, GIF, or SVG format',
            'avatar_url.max' => 'Avatar size must not exceed 2MB',
        ]);

        try {
            $user = Auth::user();
            $image = $request->file('avatar_url');
            $extension = $image->getClientOriginalExtension();
            $slug = Str::slug($user->username);
            $filename = 'avatar-' . $slug . '-' . time() . '.' . $extension;
            $path = $image->storeAs('avatars-images', $filename, 's3');

            // Delete old avatar if exists
            if ($user->avatar_url) {
                $oldPath = str_replace(Storage::url(''), '', $user->avatar_url);
                Storage::disk('s3')->delete('avatars-images/' . basename($oldPath));
            }

            $user->avatar_url = Storage::url($path);
            $user->save();

            Log::info("Avatar uploaded for user: {$user->email}");

            return response()->json([
                'message' => 'Avatar image uploaded successfully',
                'data' => new UserResource($user),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Avatar upload failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to upload avatar. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadCoverImage(Request $request)
    {
        $request->validate([
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ], [
            'cover_image.required' => 'Cover image is required',
            'cover_image.image' => 'The cover image must be a valid image file',
            'cover_image.mimes' => 'Cover image must be in JPEG, PNG, JPG, GIF, or SVG format',
            'cover_image.max' => 'Cover image size must not exceed 5MB',
        ]);

        try {
            $user = auth()->user();
            $image = $request->file('cover_image');
            $extension = $image->getClientOriginalExtension();
            $slug = str($user->username)->slug();
            $filename = 'cover-' . $slug . '-' . time() . '.' . $extension;
            $path = $image->storeAs('covers-profiles', $filename, 's3');

            // Delete old cover image if exists
            if ($user->cover_image) {
                $oldPath = str_replace(Storage::url(''), '', $user->cover_image);
                Storage::disk('s3')->delete('covers-profiles/' . basename($oldPath));
            }

            $user->cover_image = Storage::url($path);
            $user->save();

            Log::info("Cover image uploaded for user: {$user->email}");

            return response()->json([
                'message' => 'Cover image uploaded successfully',
                'data' => new UserResource($user),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Cover image upload failed for user: " . auth()->user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to upload cover image. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(ProfileRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            $originalData = $user->only(array_keys($validated));
            $user->update($validated);

            Log::info("Profile updated for user: {$user->email}", [
                'changes' => array_diff($validated, $originalData)
            ]);

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Profile update failed for user: " . auth()->user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update profile. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addSocialAccounts(Request $request)
    {
        $request->validate([
            'linkedin_username' => 'sometimes|nullable|string|max:255|regex:/^[a-zA-Z0-9\-]+$/',
            'github_username' => 'sometimes|nullable|string|max:255|regex:/^[a-zA-Z0-9\-]+$/',
        ], [
            'linkedin_username.regex' => 'LinkedIn username format is invalid',
            'github_username.regex' => 'GitHub username format is invalid',
        ]);

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            $data = $request->only(['linkedin_username', 'github_username']);
            $user->update($data);

            Log::info("Social accounts updated for user: {$user->email}", $data);

            return response()->json([
                'message' => 'Social accounts updated successfully',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Social accounts update failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update social accounts. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function delete()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            $email = $user->email;
            $user->delete();

            Log::info('User soft deleted: ' . $email);

            return response()->json([
                'message' => 'Profile deleted successfully (soft delete)',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Profile deletion failed for user: " . auth()->user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete profile. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function forceDelete() // permanently delete user
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            $email = $user->email;
            $userId = $user->id;

            if ($user->avatar_url) {
                Storage::disk('s3')->delete(basename($user->avatar_url));
            }
            if ($user->cover_image) {
                Storage::disk('s3')->delete(basename($user->cover_image));
            }

            $user->forceDelete();

            Log::warning('User permanently deleted: ' . $email . ' (ID: ' . $userId . ')');

            return response()->json([
                'message' => 'Profile permanently deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Permanent profile deletion failed for user: " . auth()->user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to permanently delete profile. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function userPosts(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->query('per_page', 15);
        $status = $request->query('status', null);

        $query = $user->posts()->with('tags', 'user')->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $posts = $query->paginate($perPage);

        return response()->json([
            'data' => PostResource::collection($posts),
            'pagination' => [
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function userComments(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->query('per_page', 15);

        $comments = $user->comments()
            ->with('post', 'user')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => CommentResource::collection($comments),
            'pagination' => [
                'total' => $comments->total(),
                'per_page' => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    public function userTags(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->query('per_page', 15);

        $tags = $user->tags()
            ->withCount('posts')
            ->paginate($perPage);

        return response()->json([
            'data' => $tags,
            'pagination' => [
                'total' => $tags->total(),
                'per_page' => $tags->perPage(),
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
            ],
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $validated = $request->validated();
        try {
            $this->authorize('update', Auth::user());
            $user = Auth::user();
            $name = $user->name;

            if (!Hash::check($request->current_password, $user->password)) {
                Log::warning("Failed password update attempt for user: {$user->email} - incorrect current password");
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            Mail::to($user->email)->send(new PasswordUpdatedSuccessfullyMail($user));

            Log::info("Password updated successfully for user: {$user->email}");

            return response()->json([
                'message' => "Hi $name, your password has been updated successfully"
            ], 200);
        } catch (JWTException $e) {
            Log::error("JWT exception during password update for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update password',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error("Password update failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update password. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function activity()
    {
        $user = auth()->user();

        $posts = $user->posts()
            ->select('id', 'title', 'created_at')
            ->get()
            ->map(function ($p) {
                $p->activity_type = 'post';
                return $p;
            });

        $comments = $user->comments()
            ->select('id', 'content', 'created_at')
            ->get()
            ->map(function ($c) {
                $c->activity_type = 'comment';
                return $c;
            });

        $activity = $posts
            ->concat($comments)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'data' => $activity,
        ]);
    }


    public function details() // this details shown in dashboard
    {
        try {
            $user = Auth::user();

            $stats = [
                'number_of_posts_published' => $user->posts()->where('status', '=', 'published')->count(),
                'number_of_posts_drafts' => $user->posts()->where('status', '=', 'draft')->count(),
                'number_of_comments_made' => $user->comments()->count(),
                'number_of_archives_posts' => $user->posts()->onlyTrashed()->count(),
                'pronouns' => $user->pronouns,
                'joined_at' => $user->created_at->diffForHumans(),
                'number_of_followers' => $user->followers()->count(),
                'number_of_users_followed' => $user->following()->count(),
                'tags_followed' => $user->followedTags()->count(),
                'reading_list_count' => $user->savedPosts()->count(),
                'account_status' => $user->status ?? 'active',
                'email_verified' => $user->email_verified_at !== null,
            ];

            return response()->json([
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch profile details for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch profile details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
