<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\ProfileRequests\ProfileRequest;
use App\Http\Requests\ProfileRequests\UpdatePasswordRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Mail\PasswordUpdatedSuccessfullyMail;
use App\Models\User;
use App\Services\ImageUploadService;
use App\Services\ViewedPostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


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


    public function uploadAvatarImage(ImageUploadRequest $request, ImageUploadService $uploadService)
    {
        $request->validated();

        try {
            $user = Auth::user();
            $image = $request->file('avatar_url');

            $uploadService->uploadAvatar($user, $image);

            return response()->json([
                'message' => 'Avatar image uploaded successfully',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Avatar upload failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to upload avatar. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadCoverImage(ImageUploadRequest $request, ImageUploadService $uploadService)
    {
        $request->validated();

        try {
            $user = auth()->user();

            $uploadedFile = Cloudinary::upload($request->file('cover_image')->getRealPath(), [
                'folder' => 'covers',
                'public_id' => 'cover_' . $user->id . '_' . time(),
                'overwrite' => true,
                'resource_type' => 'image'
            ]);

            $uploadedFileUrl = $uploadedFile->getSecurePath();

            if (!$uploadedFileUrl) {
                return response()->json([
                    'message' => 'Failed to upload cover image. Please try again.',
                    'error' => 'Cloudinary upload failed'
                ], 500);
            }

            $user->update(['cover_image' => $uploadedFileUrl]);

            return response()->json([
                'message' => 'Cover image uploaded successfully',
                'data' => new UserResource($user->fresh()),
                'cover_image' => $uploadedFileUrl
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
