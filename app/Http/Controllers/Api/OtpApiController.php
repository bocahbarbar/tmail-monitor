<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OtpApiController extends Controller
{
    /**
     * POST /api/otp
     * Ambil OTP terbaru yang:
     *  1. Dibuat dalam 1 menit terakhir
     *  2. Belum pernah dibaca (read_at IS NULL)
     * Setelah diambil, langsung ditandai read_at = now()
     */
    public function getLatestOtp(Request $request)
    {
        $email   = $request->input('email');
        $cutoff  = Carbon::now()->subMinute(); // 1 menit yang lalu

        $otp = OtpCode::where('to_address', $email)
            ->whereNull('read_at')                    // belum pernah dibaca
            ->where('created_at', '>=', $cutoff)      // maks 1 menit lalu
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'email'   => $email,
                'message' => 'No fresh OTP found (either expired >1min or already read)',
            ], 404);
        }

        // Tandai sudah dibaca
        $otp->update(['read_at' => Carbon::now()]);

        return response()->json([
            'success'     => true,
            'email'       => $email,
            'otp'         => $otp->otp,
            'source'      => $otp->source,
            'status'      => $otp->status,
            'created_at'  => $otp->created_at->format('Y-m-d H:i:s'),
            'read_at'     => $otp->read_at->format('Y-m-d H:i:s'),
            'age_seconds' => $otp->created_at->diffInSeconds(Carbon::now()),
        ]);
    }

    /**
     * POST /api/otp/all
     * Ambil 10 OTP terbaru (termasuk yang sudah dibaca), untuk keperluan debug/log.
     */
    public function getAllOtp(Request $request)
    {
        $email = $request->input('email');

        $otps = OtpCode::where('to_address', $email)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['otp', 'source', 'status', 'created_at', 'read_at']);

        return response()->json([
            'success' => true,
            'email'   => $email,
            'count'   => $otps->count(),
            'data'    => $otps,
        ]);
    }
}
