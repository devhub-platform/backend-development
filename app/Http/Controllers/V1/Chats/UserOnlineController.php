<?php

namespace App\Http\Controllers\V1\Chats;

use App\Events\UserOnline;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOnlineController extends Controller
{
    public function __invoke(Request $request, ?User $user = null): JsonResponse
    {
       
    }
}
