<?php

namespace App\Http\Controllers;

use App\Services\JobAlerts\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobPushSubscriptionController extends Controller
{
    public function key(WebPushService $webPushService): JsonResponse
    {
        return response()->json(['publicKey' => $webPushService->publicKey()]);
    }

    public function store(Request $request, WebPushService $webPushService): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);
        $subscription = $webPushService->saveSubscription($data, $request->user()?->id);

        return response()->json(['subscribed' => true, 'id' => $subscription->id], 201);
    }

    public function destroy(Request $request, WebPushService $webPushService): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url', 'max:2048']]);
        $webPushService->deleteSubscription($data['endpoint'], $request->user()?->id);

        return response()->json(['subscribed' => false]);
    }
}
