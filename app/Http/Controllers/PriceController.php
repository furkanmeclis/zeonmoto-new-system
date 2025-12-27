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
            // PIN doğrulandığında session'a kaydet
            $request->session()->put('price_pin_verified', true);
            
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

    /**
     * PIN durumunu kontrol et (session'dan)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPinStatus(Request $request): JsonResponse
    {
        $isVerified = $request->session()->get('price_pin_verified', false);
        
        return response()->json([
            'success' => true,
            'verified' => $isVerified,
        ], 200);
    }

    /**
     * PIN durumunu sıfırla (logout)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPinStatus(Request $request): JsonResponse
    {
        $request->session()->forget('price_pin_verified');
        
        return response()->json([
            'success' => true,
            'message' => 'PIN durumu sıfırlandı.',
        ], 200);
    }
}

