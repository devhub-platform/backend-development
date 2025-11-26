<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $slug = str(auth()->user()->name ?? auth()->user()->username)->slug();
        $filename = $slug . '-' . time() . '.' . $extension;
        $path = $image->storeAs('avatars', $filename, 's3');

        $user = auth()->user();
        $user->avatar_url = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar image uploaded successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function update(ProfileRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('avatar_url')) {
            $image = $request->file('avatar_url');
            $extension = $image->getClientOriginalExtension();
            $slug = isset($validated['name']) ? str($validated['name'])->slug() : str(auth()->user()->name ?? auth()->user()->username)->slug();
            $filename = $slug . '-' . time() . '.' . $extension;
            $path = $image->storeAs('avatars', $filename, 's3');
            $validated['avatar_url'] = $path;
        }

        $user = auth()->user();
        $user->update($validated);
        $user->refresh(); // Refresh the user instance to get the latest data

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user),
        ]);
    }

    public function delete()
    {
        $user = auth()->user();
        $user->delete();

        return response()->json([
            'message' => 'Profile deleted successfully',
        ]);
    }

    public function forceDelete()
    {
        $user = auth()->user();
        $user->forceDelete();

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
        $request->validated();
        try {
            $this->authorize('update', Auth::user());
            $user = Auth::user();
            $name = $user->name;
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 400);
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json(['message' => "Hi $name Your password updated successfully"]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to update password',
                'message' => $e->getMessage()], 500);
        }
    }
}
