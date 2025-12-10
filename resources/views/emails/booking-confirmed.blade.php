<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #3b82f6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }

        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }

        .booking-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            color: #6b7280;
        }

        .detail-value {
            color: #111827;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            color: #3b82f6;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            background-color: #10b981;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .services-list {
            margin: 10px 0;
            padding-left: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Booking Confirmed!</h1>
    </div>

    <div class="content">
        <p>Dear {{ $booking->user->name }},</p>

        <p>Your booking has been confirmed and payment has been received successfully.</p>

        <div class="booking-details">
            <h2>Booking Details</h2>

            <div class="detail-row">
                <span class="detail-label">Booking Reference:</span>
                <span class="detail-value">{{ $booking->booking_reference }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="status-badge">{{ ucfirst($booking->booking_status) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Accommodation:</span>
                <span class="detail-value">{{ $booking->accommodation->name }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Room Type:</span>
                <span class="detail-value">{{ $booking->room->room_type }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Check-in:</span>
                <span class="detail-value">{{ $booking->check_in_date->format('F j, Y') }} at
                    {{ $booking->check_in_time }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Check-out:</span>
                <span class="detail-value">{{ $booking->check_out_date->format('F j, Y') }} at
                    {{ $booking->check_out_time }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Number of Nights:</span>
                <span class="detail-value">{{ $booking->total_nights }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Number of Guests:</span>
                <span class="detail-value">{{ $booking->number_of_guests }}</span>
            </div>

            @if ($booking->services->count() > 0)
                <div class="detail-row">
                    <span class="detail-label">Additional Services:</span>
                    <div class="detail-value">
                        <ul class="services-list">
                            @foreach ($booking->services as $service)
                                <li>{{ $service->service->service_name }} ({{ $service->quantity }}x) - NPR
                                    {{ number_format($service->service_price * $service->quantity, 2) }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Room Subtotal:</span>
                <span class="detail-value">NPR {{ number_format($booking->room_subtotal, 2) }}</span>
            </div>

            @if ($booking->services_subtotal > 0)
                <div class="detail-row">
                    <span class="detail-label">Services Subtotal:</span>
                    <span class="detail-value">NPR {{ number_format($booking->services_subtotal, 2) }}</span>
                </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Total Amount Paid:</span>
                <span class="detail-value total">NPR {{ number_format($booking->total_amount, 2) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">{{ ucfirst($booking->payment_method) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Payment Date:</span>
                <span class="detail-value">{{ $booking->payment_verified_at->format('F j, Y \a\t g:i A') }}</span>
            </div>
        </div>

        @if ($booking->special_requests)
            <div class="booking-details">
                <h3>Special Requests</h3>
                <p>{{ $booking->special_requests }}</p>
            </div>
        @endif

        <p><strong>Important Information:</strong></p>
        <ul>
            <li>Please arrive at the accommodation by {{ $booking->check_in_time }}</li>
            <li>Bring a valid ID for check-in</li>
            <li>Cancellations made 2 or more days before check-in are eligible for an 80% refund</li>
            <li>Contact the accommodation directly for any special arrangements</li>
        </ul>

        <p>If you have any questions, please contact us or the accommodation directly.</p>

        <p>Thank you for choosing our service!</p>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} Pahuna. All rights reserved.</p>
    </div>
</body>

</html>
