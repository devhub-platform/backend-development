<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProfileRequests\ProfileRequest;
use App\Http\Requests\ProfileRequests\UpdatePasswordRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Mail\PasswordUpdatedSuccessfullyMail;
use App\Models\User;
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
        ]);

        $image = $request->file('avatar_url');
        $extension = $image->getClientOriginalExtension();
        $slug = Str::slug(Auth::user()->username);
        $filename = $slug . '-' . time() . '.' . $extension;
        $path = $image->storeAs('avatars-images', $filename, 's3');

        $user = Auth::user();
        $user->avatar_url = Storage::url($path);
        $user->save();

        return response()->json([
            'message' => 'Avatar image uploaded successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function uploadCoverImage(Request $request)
    {
        $request->validate([
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $image = $request->file('cover_image');
        $extension = $image->getClientOriginalExtension();
        $slug = str(auth()->user()->username)->slug();
        $filename = 'cover-' . $slug . '-' . time() . '.' . $extension;
        $path = $image->storeAs('covers-profiles', $filename, 's3');

        $user = auth()->user();
        $user->cover_image = Storage::url($path);
        $user->save();

        return response()->json([
            'message' => 'Cover image uploaded successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function update(ProfileRequest $request)
    {
        $validated = $request->validated();

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
        $user->update($validated);
        $user->refresh();

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    public function delete()
    {
        $user = auth()->user();
        $user->delete();
        Log::notice('User deleted-Soft Delete: ' . $user->email);
        return response()->json([
            'message' => 'Profile deleted successfully',
        ]);
    }

    public function forceDelete()
    {
        $user = auth()->user();
        $user->forceDelete();
        Log::notice('User permanently deleted: ' . $user->email);

        if ($user->avatar_url)
            Storage::disk('s3')->delete($user->avatar_url);
        if ($user->cover_image)
            Storage::disk('s3')->delete($user->cover_image);

        return response()->json([
            'message' => 'Profile permanently deleted successfully',
        ]);
    }

    public function userPosts()
    {
        $user = auth()->user();
        $posts = $user->posts;
        return response()->json([
            'data' => PostResource::collection($posts),
        ]);
    }

    public function userComments()
    {
        $user = auth()->user();
        $comments = $user->comments;

        return response()->json([
            'data' => CommentResource::collection($comments),
        ]);
    }

    public function userTags()
    {
        $user = auth()->user();
        $tags = $user->tags;

        return response()->json([
            'data' => $tags,
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
                return response()->json(['message' => 'Current password is incorrect'], 400);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            Mail::to($user->email)->send(new PasswordUpdatedSuccessfullyMail($user));

            return response()->json(['message' => "Hi $name Your password updated successfully"]);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Failed to update password',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function activity()
    {
        $user = auth()->user();
        $posts = $user->posts()->select('id', 'title', 'created_at')->get()->map(function ($p) {
            $p->type = 'post';
            return $p;
        });
        $comments = $user->comments()->select('id', 'content', 'created_at')->get()->map(function ($c) {
            $c->type = 'comment';
            return $c;
        });
        $activity = $posts->concat($comments)->sortByDesc('created_at')->values();

        return response()->json([
            'data' => $activity,
        ]);
    }
}
