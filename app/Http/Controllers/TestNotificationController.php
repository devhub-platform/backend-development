<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OneSignal;

class TestNotificationController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->merge([
            'title' => $request->input('title', $request->input('heading')),
        ])->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'url' => 'nullable|url',
        ]);

        try {
            OneSignal::sendNotificationToAll(
                $validated['message'],
                $validated['url'] ?? null,
                null,
                null,
                null,
                $validated['title']
            );

            return response()->json(['success' => true, 'message' => 'Notification sent successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
