<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use App\Http\Resources\AccommodationResource;
use App\Models\Accommodation;
use App\Models\AccommodationVerification;
use App\Services\AccommodationService;
use App\Services\KhaltiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccommodationController extends Controller
{
    public function __construct(
        protected AccommodationService $accommodationService,
        protected KhaltiService $khaltiService
    ) {}

    /**
     * Get all verified accommodations (public endpoint)
     */
    public function index(): JsonResponse
    {
        $accommodations = Accommodation::where('is_verified', true)
            ->with('staff')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => AccommodationResource::collection($accommodations)
        ]);
    }

    /**
     * Get all accommodations - Admin only
     * Middleware: auth.admin
     */
    public function indexAll(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => AccommodationResource::collection(Accommodation::latest()->get())
        ]);
    }

    /**
     * Show single accommodation
     */
    public function show(Accommodation $accommodation): JsonResponse
    {
        $accommodation->load(['reviews.user', 'staff']);

        return response()->json([
            'status' => true,
            'data' => new AccommodationResource($accommodation)
        ]);
    }

    /**
     * Create new accommodation - Staff only
     * Middleware: auth.staff
     */
    public function store(StoreAccommodationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $accommodation = $this->accommodationService->createAccommodation($data, $request->user());

        return response()->json([
            'status' => true,
            'data' => new AccommodationResource($accommodation)
        ], 201);
    }

    /**
     * Update accommodation - Owner staff only
     * Middleware: auth.staff
     */
    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation): JsonResponse
    {
        // Authorization: only owner can update
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only update accommodations that you created.'
            ], 403);
        }

        $data = $request->validated();
        $accommodation = $this->accommodationService->updateAccommodation($accommodation, $data);

        return response()->json([
            'status' => true,
            'data' => new AccommodationResource($accommodation)
        ]);
    }

    /**
     * Delete accommodation - Owner staff only
     * Middleware: auth.staff
     */
    public function destroy(Request $request, Accommodation $accommodation): JsonResponse
    {
        // Authorization: only owner can delete
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only delete accommodations that you created.'
            ], 403);
        }

        $this->accommodationService->deleteAccommodation($accommodation);

        return response()->json([
            'status' => true,
            'message' => 'Accommodation deleted successfully.'
        ]);
    }

    /**
     * Verify/unverify accommodation - Admin only
     * Middleware: auth.admin
     */
    public function verify(Accommodation $accommodation): JsonResponse
    {
        if (!$accommodation->hasVerificationPayment()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot verify accommodation. Verification fee has not been paid.'
            ], 400);
        }

        $accommodation = $this->accommodationService->verifyAccommodation($accommodation);

        return response()->json([
            'status' => true,
            'message' => $accommodation->is_verified ?
                'Accommodation verified successfully' :
                'Accommodation verification removed',
            'data' => new AccommodationResource($accommodation)
        ]);
    }

    /**
     * Pay verification fee - Owner staff only
     * Middleware: auth.staff
     */
    public function payVerificationFee(Request $request, Accommodation $accommodation): JsonResponse
    {
        // Authorization: only owner can pay
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only pay for your own accommodations.'
            ], 403);
        }

        if ($accommodation->hasVerificationPayment()) {
            return response()->json([
                'status' => false,
                'message' => 'Verification fee has already been paid.'
            ], 400);
        }

        $request->validate(['token' => 'required|string']);

        $result = $this->accommodationService->processVerificationPayment(
            $accommodation,
            $request->user(),
            $request->token
        );

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Verification fee paid successfully.',
            'data' => [
                'verification' => $result['verification'],
                'accommodation' => new AccommodationResource($accommodation->fresh())
            ]
        ]);
    }

    public function payVerificationFee(Request $request, Accommodation $accommodation)
    {
        try {
            $user = $request->user();

            // Check if user is staff and owns this accommodation
            if (!$user->isStaff() || $accommodation->staff_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. You can only pay for your own accommodations.'
                ], 403);
            }

            // Check if already paid
            if ($accommodation->hasVerificationPayment()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Verification fee has already been paid for this accommodation.'
                ], 400);
            }

            // Validate Khalti token
            $request->validate([
                'token' => 'required|string',
            ]);

            \Illuminate\Support\Facades\Log::info('Verification payment attempt', [
                'accommodation_id' => $accommodation->id,
                'staff_id' => $user->id,
                'token' => $request->token
            ]);

            // Verify payment with Khalti - amount is Rs. 10 = 1000 paisa
            $khaltiService = app(\App\Services\KhaltiService::class);
            $verification = $khaltiService->verifyPayment($request->token, 1000);

            \Illuminate\Support\Facades\Log::info('Khalti verification result', [
                'success' => $verification['success'],
                'data' => $verification['data'] ?? null,
                'message' => $verification['message'] ?? null
            ]);

            if (!$verification['success']) {
                return response()->json([
                    'status' => false,
                    'message' => $verification['message'] ?? 'Payment verification failed',
                    'error' => $verification['error'] ?? null
                ], 400);
            }

            $paymentData = $verification['data'];

            // Create verification payment record
            $verificationRecord = AccommodationVerification::create([
                'accommodation_id' => $accommodation->id,
                'staff_id' => $user->id,
                'verification_fee' => 10.00,
                'payment_method' => 'khalti',
                'transaction_id' => $paymentData['idx'] ?? $request->token,
                'payment_status' => 'completed',
                'paid_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Verification fee paid successfully. Your accommodation can now be verified by admin.',
                'verification' => $verificationRecord,
                'accommodation' => new AccommodationResource($accommodation->fresh())
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Verification fee payment error', [
                'accommodation_id' => $accommodation->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to process verification payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
