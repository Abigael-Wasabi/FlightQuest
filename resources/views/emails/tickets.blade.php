{{--<!DOCTYPE html>--}}
{{--<html>--}}
{{--<head>--}}
{{--    <title>E-Ticket</title>--}}
{{--</head>--}}
{{--<body>--}}
{{--<h1>Please find attached your e-ticket and price summary.</h1>--}}

{{--<p><strong>Passenger Details:</strong></p>--}}
{{--<ul>--}}
{{--    @foreach($passengerInfo as $info)--}}
{{--        <li>Name: {{ $info['name']['firstName'] }} {{ $info['name']['lastName'] }}</li>--}}
{{--        <li>Email: {{ $info['email'] }}</li>--}}
{{--        <li>Phone Number: {{ $info['phone_number'] }}</li>--}}
{{--        <li>Nationality: {{ $info['nationality'] }}</li>--}}
{{--        <li>Gender: {{ $info['gender'] }}</li>--}}
{{--    @endforeach--}}
{{--</ul>--}}

{{--<p><strong>Flight Information:</strong></p>--}}
{{--<ul>--}}
{{--    @foreach($segments as $segment)--}}
{{--        <li>Departure: {{ $segment['departure']['iataCode'] }} at {{ $segment['departure']['at'] }}</li>--}}
{{--        <li>Arrival: {{ $segment['arrival']['iataCode'] }} at {{ $segment['arrival']['at'] }}</li>--}}
{{--        <li>Carrier Code: {{ $segment['carrierCode'] }}</li>--}}
{{--        <li>Flight Number: {{ $segment['number'] }}</li>--}}
{{--    @endforeach--}}
{{--</ul>--}}

{{--<p><strong>Booking Details:</strong></p>--}}
{{--<ul>--}}
{{--    @foreach($associatedRecords as $record)--}}
{{--        <li>Booking Reference: {{ $record['reference'] }}</li>--}}
{{--    @endforeach--}}
{{--    <li>Status: {{ $bookingInfo['status'] ?? 'Unknown' }}</li>--}}
{{--</ul>--}}

{{--<p><strong>Seats:</strong> {{ $seatsInfo[0]['seat_number'] ?? 'N/A' }}</p>--}}
{{--<p><strong>Luggage:</strong> {{ $luggageInfo[0]['weight_in_kg'] ?? 'N/A' }} kg</p>--}}

{{--<p><strong>Price Summary:</strong></p>--}}
{{--<ul>--}}
{{--    <li>Base Price: {{ $booking_data['flightOffers'][0]['price']['base'] }}</li>--}}
{{--    <li>Total Price: {{ $booking_data['flightOffers'][0]['price']['total'] }}</li>--}}
{{--    <li>Taxes:</li>--}}
{{--    <ul>--}}
{{--        @foreach($booking_data['flightOffers'][0]['travelerPricings'][0]['price']['taxes'] as $tax)--}}
{{--            <li>{{ $tax['code'] }}: {{ $tax['amount'] }}</li>--}}
{{--        @endforeach--}}
{{--    </ul>--}}
{{--</ul>--}}
{{--</body>--}}
{{--</html>--}}














    <!DOCTYPE html>
<html>
<head>
    <title>E-Ticket</title>
</head>
<body>
<h1>Please find attached your e-ticket and price summary.</h1>

<p><strong>Passenger Details:</strong></p>
<ul>
    @foreach($passengerInfo as $info)
        <li>Name: {{ $info['name']['firstName'] }} {{ $info['name']['lastName'] }}</li>
        <li>Email: {{ $info['email'] }}</li>
        <li>Phone Number: {{ $info['phone_number'] }}</li>
        <li>Nationality: {{ $info['nationality'] }}</li>
        <li>Gender: {{ $info['gender'] }}</li>
    @endforeach
</ul>

<p><strong>Flight Information:</strong></p>
<ul>
    @foreach($segments as $segment)
        <li>Departure: {{ $segment['departure']['iataCode'] }} at {{ $segment['departure']['at'] }}</li>
        <li>Arrival: {{ $segment['arrival']['iataCode'] }} at {{ $segment['arrival']['at'] }}</li>
        <li>Carrier Code: {{ $segment['carrierCode'] }}</li>
        <li>Flight Number: {{ $segment['number'] }}</li>
    @endforeach
</ul>

<p><strong>Booking Details:</strong></p>
<ul>
    @foreach($associatedRecords as $record)
        <li>Booking Reference: {{ $record['reference'] }}</li>
    @endforeach
    <li>Status: {{ $bookingInfo['status'] ?? 'Unknown' }}</li>
</ul>

<p><strong>Seats:</strong> {{ $seatsInfo[0]['seat_number'] ?? 'N/A' }}</p>
<p><strong>Luggage:</strong> {{ $luggageInfo[0]['weight_in_kg'] ?? 'N/A' }} kg</p>

<p><strong>Price Summary:</strong></p>
<ul>
    <li>Base Price: {{ $booking_data['flightOffers'][0]['price']['base'] }}</li>
    <li>Total Price: {{ $booking_data['flightOffers'][0]['price']['total'] }}</li>
    <li>Taxes:</li>
    <ul>
        @foreach($booking_data['flightOffers'][0]['price']['taxes'] as $tax)
            <li>{{ $tax['code'] }}: {{ $tax['amount'] }}</li>
        @endforeach
    </ul>
</ul>
</body>
</html>

