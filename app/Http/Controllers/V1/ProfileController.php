<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\ProfileRequests\UploadCvRequest;
use App\Http\Requests\ProfileRequests\ProfileRequest;
use App\Http\Requests\ProfileRequests\UpdatePasswordRequest;
use App\Http\Requests\ProfileRequests\UpdateProfileRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\TrendingPostResource;
use App\Http\Resources\UserResource;
use App\Mail\PasswordUpdatedSuccessfullyMail;
use App\Models\User;
use App\Exceptions\HackClubCdnException;
use App\Services\HackClubCdnService;
use App\Services\ImageUploadCloudinaryService;
use App\Services\ViewedPostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Mail;
// use Tymon\JWTAuth\Exceptions\JWTException;
// use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

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


    public function uploadAvatarImage(ImageUploadRequest $request, ImageUploadCloudinaryService $cloudinaryService)
    {
        $request->validated();

        try {
            $user = Auth::user();
            $image = $request->file('avatar_url');

            if (!$image) {
                return response()->json([
                    'message' => 'No image file provided. Please upload a valid image.',
                ], 422);
            }

            $uploadedFileUrl = $cloudinaryService->uploadAvatar($user, $image);

            $user->update(['avatar_url' => $uploadedFileUrl]);

            return response()->json([
                'message' => 'Avatar image uploaded successfully',
                'data' => new UserResource($user->fresh()),
                'avatar_url' => $uploadedFileUrl
            ], 200);
        } catch (\Exception $e) {
            Log::error("Avatar upload failed for user: " . Auth::user()->email . " - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to upload avatar. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadCoverImage(ImageUploadRequest $request, ImageUploadCloudinaryService $cloudinaryService)
    {
        $request->validated();

        try {
            $user = auth()->user();
            $image = $request->file('cover_image');

            if (!$image) {
                return response()->json([
                    'message' => 'No image file provided. Please upload a valid image.',
                ], 422);
            }

            $uploadedFileUrl = $cloudinaryService->uploadCoverImage($user, $image);

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

    public function uploadCv(UploadCvRequest $request, HackClubCdnService $hackClubCdnService)
    {
        $request->validated();

        try {
            $user = Auth::user();
            $cv = $request->file('cv');

            if (!$cv) {
                return response()->json([
                    'message' => 'No CV file provided. Please upload a valid file.',
                ], 422);
            }

            $uploadedFileUrl = $hackClubCdnService->uploadFileUrl($cv);

            $user->update(['cv_url' => $uploadedFileUrl]);

            return response()->json([
                'message' => 'CV uploaded successfully',
                'data' => new UserResource($user->fresh()),
                'cv_url' => $uploadedFileUrl,
            ], 200);
        } catch (HackClubCdnException $e) {
            Log::error('CV upload to Hack Club CDN failed for user: ' . Auth::user()->email . ' - ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to upload CV to storage. Please try again.',
                'error' => $e->getMessage(),
            ], 502);
        } catch (\Exception $e) {
            Log::error('CV upload failed for user: ' . Auth::user()->email . ' - ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to upload CV. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCv()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if (!$user->cv_url) {
                return response()->json([
                    'message' => 'No CV found to delete.',
                ], 404);
            }

            $user->update(['cv_url' => null]);

            return response()->json([
                'message' => 'CV deleted successfully',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('CV deletion failed for user: ' . Auth::user()->email . ' - ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to delete CV. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        try {
            $validated = $request->validated();

            unset($validated['avatar_url'], $validated['cover_image']);

            $user->update($validated);

            Log::info("Profile updated for user: {$user->email}");

            return response()->json([
                'message' => 'Profile updated successfully',
                'data' => new UserResource($user->fresh()),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Profile update failed for user: {$user->email} - " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update profile. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function userPosts(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->query('per_page', 15);
        $status = $request->query('status', null);

        $query = $user->posts()->with('tags')->latest();

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
                'number_of_views_in_his_posts' => $user->posts()->sum('views'),
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

    public function shareLink()
    {
        $user = Auth::user();

        $identifier = $user->username ?: (string)$user->id;

        $webBaseUrl = Str::rtrim((string)config('services.profile_share.web_base_url', config('app.url')), '/');
        $deepLinkScheme = Str::trim((string)config('services.profile_share.deep_link_scheme', 'devhub'));
        $deepLinkProfilePath = Str::trim((string)config('services.profile_share.deep_link_profile_path', 'profile'), '/');

        $webUrl = $webBaseUrl . '/u/' . rawurlencode($identifier);
        $deepLink = $deepLinkScheme . '://' . $deepLinkProfilePath . '/' . rawurlencode($identifier);

        return response()->json([
            'data' => [
                'user' => [
                    'avatar_url' => $user->avatar_url,
                    'username' => $user->username,
                    'name' => $user->name,
                ],
                'links' => [
                    'deep_link' => $deepLink,
                    'fallback' => $webUrl,
                ],
            ],
        ]);
    }
}
