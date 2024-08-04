<?php

use App\Http\Controllers\Cars\CarsTransfersController;
use App\Http\Controllers\Cars\DestinationController;
use App\Http\Controllers\Cars\HotelsController;
use App\Http\Controllers\Flights\AirportSearchController;
use App\Http\Controllers\Flights\BookingController;
use App\Http\Controllers\Flights\FlightOrderController;
use App\Http\Controllers\Flights\FlightSearchController;
use App\Http\Controllers\Flights\MpesaController;
use App\Http\Controllers\Flights\PriceSummaryController;
use App\Models\FlightsSearchModel;
use Illuminate\Support\Facades\Route;

//use App\Http\Controllers\Flights\SeatController;

//airports
Route::prefix('airports')->group(function () {
    Route::get('/all', [AirportSearchController::class, 'index']);
    Route::get('/search', [AirportSearchController::class, 'search']);
    Route::get('/nearest', [FlightsSearchModel::class, 'getLocationBasedOnIP']);
});
//airlines
Route::prefix('airlines')->group(function () {
    Route::get('/search', [AirportSearchController::class, 'airlineSearch']);
});
//flights
Route::prefix('flights')->group(function () {
    Route::get('search/{any}', [FlightSearchController::class, 'search'])->where('any', '.*');
    Route::get('/offers', [FlightSearchController::class, 'offers']);
    Route::post('/offers-price', [FlightSearchController::class, 'flightOffersPrice']);
    Route::get('/seat-map', [FlightOrderController::class, 'getSeatMap']);
//    Route::post('/confirm-offers-price', [FlightSearchController::class, 'confirmFlightOfferPrice']);

    Route::middleware('auth')->group(function () {
        Route::post('/select-flight-offer', [FlightSearchController::class, 'selectFlightOffer']);
        Route::get('/retrieve-flight-order/{flightOrderId}', [FlightOrderController::class, 'retrieve']);
        Route::delete('/cancel-flight-order/{flightOrderId}', [FlightOrderController::class, 'cancel']);
//        Route::get('/select-seat', [SeatController::class, 'selectSeat']);
    });
});
//bookings
Route::prefix('booking')->group(function () {
    Route::post('/store', [BookingController::class, 'store']);
    Route::get('/price-summary/{bookingId}', [PriceSummaryController::class, 'priceSummary']);
    Route::get('/send-ticket/{bookingId}', [PriceSummaryController::class, 'sendTicket']);
});
//payments
Route::prefix('pay')->middleware('auth')->group(function () {
    Route::post('/mpesa/stk', [MpesaController::class, 'stkPush']);
    Route::post('/mpesa/callback', [MpesaController::class, 'callback']);
    Route::post('/mpesa/payment', [MpesaController::class, 'initiatePayment']);
});
//tours n destinations
Route::prefix('destination-experience')->group(function () {
    Route::get('/points-of-interest', [DestinationController::class, 'PointsOfInterest']);
    Route::get('/points-of-interest-by-square', [DestinationController::class, 'PointsOfInterestBySquare']);
    Route::get('/points-of-interest-by-id/{poisId}', [DestinationController::class, 'PointsOfInterestById']);
    Route::get('/tours-and-activities', [DestinationController::class, 'getToursAndActivities']);
    Route::get('/tours-and-activities-by-bounding-box', [DestinationController::class, 'getToursAndActivitiesByBoundingBox']);
    Route::get('/tours-and-activities-by-id/{activityId}', [DestinationController::class, 'getToursAndActivitiesById']);
    Route::get('/city-search', [DestinationController::class, 'searchCity']);
    Route::get('/country-city-search', [DestinationController::class, 'searchCountryCities']);
    Route::get('/page', [DestinationController::class, 'destinationExperiencePage']);
});
//cars n transfers
Route::prefix('car-transfers')->group(function () {
    Route::post('/search', [CarsTransfersController::class, 'transferSearch']);

    Route::middleware('auth')->group(function () {
        Route::post('/booking', [CarsTransfersController::class, 'bookingTransfer']);
        Route::post('/management/{orderId}/transfers/cancellation', [CarsTransfersController::class, 'transferManagement']);
    });
});
//hotels
Route::prefix('hotels')->group(function () {
    Route::get('/by-id', [HotelsController::class, 'hotelById']);
    Route::get('/by-city', [HotelsController::class, 'hotelByCity']);
    Route::get('/by-geocode', [HotelsController::class, 'hotelByGeocode']);
});
