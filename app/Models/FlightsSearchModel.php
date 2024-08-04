<?php

namespace App\Models;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use GuzzleHttp\Client;


class FlightsSearchModel
{
    protected mixed $amadeusApiKey;
    protected mixed $amadeusApiSecret;
    protected string $amadeusApiUrl = 'https://test.api.amadeus.com/';

    public $form_type;

    public $adults;
    public $children;
    public $infants;

    public $show_adult;
    public $show_children;
    public $show_infant;

    public $show_oneway;
    public $show_return;

    public $origin;
    public $origin_name;
    public $origin_city;
    public $destination;
    public $destination_name;
    public $destination_city;
    public $returnDate;
    public $departureDate;
    public $tripType;
    public $classType;
    public $route;
    public $limit;
    public $broswer_version;


    public function __construct()
    {
        $this->initializeSearch();
        $this->amadeusApiKey = env('AMADEUS_API_KEY');
        $this->amadeusApiSecret = env('AMADEUS_API_SECRET');
    }

    public function parseUriString($args)
    {
        $this->origin = $args[0];
        $this->destination = $args[1];
        $this->tripType = $args[2];
        $this->departureDate = $args[4];
        if ($this->tripType == 'round') {
            $this->returnDate = $args[5];
            $this->adults = $args[6];
            $this->children = $args[7];
            $this->tripType = "return";
            $this->infants = $args[8];
        } else {
            $this->adults = $args[5];
            $this->children = $args[6];
            $this->infants = $args[7];
        }
    }

    private function initializeSearch()
    {
        //Get the location of the user based on IP from IP Stack


        $this->form_type = "form";  // url , form , iframe
        $this->adults = 1;
        $this->children = 0;
        $this->infants = 0;
        $this->show_adult = true;
        $this->show_children = true;
        $this->show_infant = true;
        $this->show_oneway = true;
        $this->show_return = true;
        $this->tripType = 'one_way'; // one_way return
        $this->classType = 'M'; // economy
        $this->origin = 'NBO';
        $this->origin_name = 'Jomo Kenyatta Intl';
        $this->origin_city = 'Nairobi';
        $this->destination = 'DXB';
        $this->destination_name = 'Dubai Intl Airport';
        $this->destination_city = 'Dubai';
        $this->route = '';//get_flights_search_page();
        $this->limit = 100;
        $this->departureDate = date('Y-m-d', strtotime("+2 days"));
        $this->returnDate = date('Y-m-d', strtotime("+30 days"));
        $this->broswer_version = '';
    }


    //get location based on IP

    /**
     * @throws GuzzleException
     */
    public function getLocationBasedOnIP(Request $request)
    {
        //getting the users IP address
        $ip = $request->ip();
        // testing locally,use public IP address (172.89.1.21)
        if ($ip === '127.0.0.1') {//localhost IP addre used during local dvlpmt
            $ip = '172.89.1.21';//replaces it with a test public IP address
        }
        //SEnd request to IP STack
        $client = new Client();
        $apiKey = env('IP_STACK_API_KEY');
        $response = $client->get("http://api.ipstack.com/{$ip}?access_key={$apiKey}");
        $locationData = json_decode($response->getBody(), true);

        //Extracting the coordinates
        $latitude = $locationData['latitude'];
        $longitude = $locationData['longitude'];

        //getting the closest airport based on the coordinates
        $nearestAirport = $this->getNearestAirport($latitude, $longitude);

        return response()->json([
            'ip' => $ip,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'nearest_airport' => $nearestAirport
        ]);
    }

    private function getNearestAirport($latitude, $longitude)
    {
        // List of airports with their coordinates (for demonstration purposes)
        $airports = [
            ['name' => 'Jomo Kenyatta International Airport', 'latitude' => -1.319167, 'longitude' => 36.9275],
            ['name' => 'Wilson Airport', 'latitude' => -1.321667, 'longitude' => 36.814722],
            //more airports
        ];

        $nearestAirport = null;
        $shortestDistance = PHP_INT_MAX;

        foreach ($airports as $airport) {
            $distance = $this->calculateDistance($latitude, $longitude, $airport['latitude'], $airport['longitude']);
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestAirport = $airport['name'];
            }
        }

        return $nearestAirport;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)//haversine formula
    {
        $earthRadius = 6371; //Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

