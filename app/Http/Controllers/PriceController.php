<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PriceController extends Controller
{
    /**
     * PIN doğrulama endpoint'i
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
        ], [
            'pin.required' => 'PIN kodu gereklidir.',
            'pin.size' => 'PIN kodu 4 haneli olmalıdır.',
            'pin.regex' => 'PIN kodu sadece rakamlardan oluşmalıdır.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz PIN formatı.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $enteredPin = $request->input('pin');
        $correctPin = config('price.pin');

        if (empty($correctPin)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN sistemi yapılandırılmamış.',
            ], 500);
        }

        if ($enteredPin === $correctPin) {
            return response()->json([
                'success' => true,
                'message' => 'PIN doğrulandı.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Yanlış PIN kodu.',
        ], 401);
    }
}

