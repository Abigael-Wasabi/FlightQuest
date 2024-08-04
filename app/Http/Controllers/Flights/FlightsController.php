<?php

namespace App\Http\Controllers\Flights;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use App\Models\FlightsSearchModel;
use Illuminate\Support\Facades\Session;
use function Laravel\Prompts\search;

class FlightsController extends Controller
{

    private $flightSearchModel;

    public function __construct(FlightsSearchModel $searchModel)
    {
        $this->init_flights_search_model();
        $this->flightSearchModel = $searchModel;
    }

    public function init_flights_search_model()
    {
        $search = unserialize(Session::get('flights_search_session'));
        if (empty($search)) {
            $search = new FlightsSearchModel();
            Session::put('flights_search_session', serialize($search));
        }
    }

    public function _searchPage($searchParameters, $page = '1')
    {
        $searchParams = explode('/', $searchParameters);
        $count = count($searchParams);
        $flightsModel = new Flight();
        $search = $this->flightSearchModel;

        /**
         * array(9) {
         * [0]=>
         * string(3) "nbo"
         * [1]=>
         * string(3) "dxb"
         * [2]=>
         * string(6) "return"
         * [3]=>
         * string(1) "w"
         * [4]=>
         * string(10) "2022-12-09"
         * [5]=>
         * string(10) "2022-12-13"
         * [6]=>
         * string(1) "2"
         * [7]=>
         * string(1) "1"
         * [8]=>
         * string(1) "0"
         * }
         */
        if ($count === 9) {
            //Return flight
            $search->origin = strtoupper($searchParams['0']);
            $search->origin_name = $flightsModel->getAirportCity(($searchParams['0']))->name;
            $search->origin_city = $flightsModel->getAirportCity(($searchParams['0']))->cityName;
            $search->destination = strtoupper($searchParams['1']);
            $search->destination_name = $flightsModel->getAirportCity(($searchParams['1']))->name;
            $search->destination_city = $flightsModel->getAirportCity(($searchParams['1']))->cityName;
            $search->tripType = $searchParams['2'];
            $search->classType = $searchParams['3'];
            $search->departureDate = $searchParams['4'];
            $search->returnDate = $searchParams['5'];
            $search->adults = $searchParams['6'];
            $search->children = $searchParams['7'];
            $search->infants = $searchParams['8'];

        } else {
            $search->origin = strtoupper($searchParams['0']);
            $search->origin_name = $flightsModel->getAirportCity(($searchParams['0']))->name;
            $search->origin_city = $flightsModel->getAirportCity(($searchParams['0']))->cityName;
            $search->destination = strtoupper($searchParams['1']);
            $search->destination_name = $flightsModel->getAirportCity(($searchParams['1']))->name;
            $search->destination_city = $flightsModel->getAirportCity(($searchParams['1']))->cityName;
            $search->tripType = $searchParams['2'];
            $search->classType = $searchParams['3'];
            $search->departureDate = $searchParams['4'];
            $search->returnDate = date('Y-m-d', strtotime($searchParams['4'] . " +30 days"));
            $search->adults = $searchParams['5'];
            $search->children = $searchParams['6'];
            $search->infants = $searchParams['7'];
        }

        $search->limit = 100;
        $search->broswer_version = $_SERVER['HTTP_USER_AGENT'];

        //Update search Model
        Session::put('flights_search_session', serialize($search));


        return view("frontend.flights.search.search", ['page' => $page]);
    }


    public static function get_inst()
    {
        static $instance;
        if (is_null($instance)) {
            $instance = new self();
        }

        return $instance;
    }

}
