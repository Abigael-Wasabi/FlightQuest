<?php


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

function qt_lang($key, $lang = '', $format = false)
{
    $local = session()->has('local') ? session('local') : 'en';
    App::setLocale($local);
    $lang_array = include(base_path('resources/lang/' . $local . '/messages.php'));
    $processed_key = ucfirst(str_replace('_', ' ', Helpers::remove_invalid_charcaters($key)));
    if (!array_key_exists($key, $lang_array)) {
        $lang_array[$key] = $processed_key;
        $str = "<?php return " . var_export($lang_array, true) . ";";
        file_put_contents(base_path('resources/lang/' . $local . '/messages.php'), $str);
        $result = $processed_key;
    } else {
        $result = __('messages.' . $key);
    }
    return $result;
}

function get_flights_search_value($key)
{

    if (!Session::has('flights_search_session')) {

        FlightsController::get_inst()->init_flights_search_model();
    }

    $flightSearchModel = unserialize(Session::get('flights_search_session'));

    return $flightSearchModel->$key;

}

function get_flight_search_model()
{
    if (!Session::has('flights_search_session')) {

        \App\Controllers\Services\FlightsController::get_inst()->init_flights_search_model();
    }

    return unserialize(Session::get('flights_search_session'));
}

function get_flights_search_page($params = '')
{
    //return url(Config::get('awebooking.post_types')['flights']['search_slug'] . '/' . $params);
    return url('/api/flights/search/' . $params);
}

function get_flights_default_search_page($params = '')
{
    return url(Config::get('awebooking.post_types')['flights']['default_search_slug'] . '/' . $params);
}

function get_flights_booking_type()
{
    $booking_type = get_option('flights_booking_type', 'one_way');
    return $booking_type;
}

function get_flights_permalink($flightId)
{
    return url(Config::get('awebooking.post_types')['flights']['booking_slug'] . '/' . $flightId);

}

function get_airline_name($k)
{
    $data = file_get_contents(public_path('data/json/airlines.json'));
    $data = json_decode($data, true);
    $final = array_filter($data, function ($a) use ($k) {
        return (in_array($a['id'], (array)$k));
    });
    $name = $k;
    foreach ($final as $value) {
        $name = $value['name'];
    }
    return $name;
}


function get_airport_name($k)
{
    $data = file_get_contents(public_path('data/json/airports.json'));
    $data = json_decode($data, true);
    $final = array_filter($data, function ($a) use ($k) {
        return (in_array($a['code'], (array)$k));
    });
    $name = $k;
    foreach ($final as $value) {
        $name = $value['fullname'];
    }
    return $name;
}

function get_airport_city($k)
{
    $data = file_get_contents(public_path('data/json/airports.json'));
    $data = json_decode($data, true);
    $final = array_filter($data, function ($a) use ($k) {
        return (in_array($a['code'], (array)$k));
    });
    $name = $k;
    foreach ($final as $value) {
        $name = $value['fullname'];
    }
    return $name;
}

function get_airport_logo($k)
{
    if (file_exists(public_path() . '/images/flights/airlines/' . $k . '.png')) {
        return asset('images/flights/airlines/' . $k . '.png');
    } else {
        return asset('images/flights/airlines/default.png');
    }

}

function get_flight_class_type($k)
{
    //M (economy), W (economy premium), C (business), or F (first class)
    if ($k == 'M') {
        return __('Economy');
    } else if ($k == 'W') {
        return __('Premium Economy');
    } else if ($k == 'C') {
        return __('Business');
    } else if ($k == 'F') {
        return __('First');
    }

}

function get_default_airport_logo()
{
    return asset('images/flights/airlines/default.png');
}

function get_number_of_stops($v)
{
    if ($v == 1) {

        return __('Direct');

    } else if (($v - 1) == 1) {
        return $v - 1 . ' ' . __('Stop');
    } else {
        return $v - 1 . ' ' . __('Stops');
    }
}

function formatPrice($price, $show_symbol = true, $format = true, $_currency = null)
{
    $currency_list = list_currencies();
    $currency = Session::get('flights_search_currency');

    $rate = 1;
    $symbol = $currency;
    $currency_position = 'left';
    $decimal = 2;
    $thousand_separator = ',';
    $decimal_separator = '.';

    if (!empty($currency_list) && $currency) {
        foreach ($currency_list as $item) {
            $unit = trim($item['unit']);
            if ($currency == $unit) {
                $rate = (float)$item['exchange'];
                $currency_position = $item['position'];
                $symbol = $item['symbol'];
                $decimal = (int)$item['currency_decimal'];
                $thousand_separator = $item['thousand_separator'];
                $decimal_separator = $item['decimal_separator'];
                break;
            }
        }
    }

    $price = (float)$price * $rate;

    $price = round($price, $decimal);

    if ($format) {
        $price = number_format($price, $decimal, $decimal_separator, $thousand_separator);
    }
    if (!$show_symbol) {
        return $price;
    } else {
        if ($currency_position == 'right') {
            return $price . $symbol;
        } else {
            return $symbol . $price;
        }
    }
}

function format_flight_time($date)
{
    return date_format(date_create($date), 'h:i');
}

function format_flight_time_of_day($date)
{
    return date_format(date_create($date), 'A');
}

function format_flight_date($date)
{
    return date_format(date_create($date), 'd M Y');
}


function count_one_way_stops($routes)
{
    $list = array();
    foreach ($routes as $route) {
        if ($route->return == 0) {
            array_push($list, $route);
        }
    }

    return (count($list) - 1);

}

function count_return_stops($routes)
{
    $list = array();
    foreach ($routes as $route) {
        if ($route->return == 1) {
            array_push($list, $route);
        }
    }

    return (count($list) - 1);

}

function calculate_route_duration($route)
{
    if ($route != null) {
        $from = new DateTime($route->local_departure);
        $to = new DateTime($route->local_arrival);
        return $from->diff($to)->format('%hh %im');
    }
}

function signupdeals()
{
    if (!Auth::check()) {
        return '<div class="checked-baggage-item container mb-2">
            <div class="col-md-12">
                <div class="theme-bg p-3">
                    <div class="row">
                        <div class="col-md-10">
                            <h5><i class="la la-lock"></i>' . __('Signup to unlock our secret deals') . '</h5>
                        </div>
                        <div class="col-md-2">
                        <a class="btn btn-primary btn-block" href="javascript: void(0);" data-toggle="modal" data-target="#hh-register-modal">' . __('Sign up') . '</a>

                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }


}

function get_included_bag_combination_title_for_passenger($baggage, $type, $obj = 'baggage_string')
{
    $includedBaggage = free_bag_combination_parser_passenger($baggage, $type);
    if ($obj == 'image') {
        if (count($includedBaggage['personal_items']) > 0) {
            return asset('images/animated/travel-bag.json');
        } else {
            return asset('images/animated/travel-bag-single.json');
        }

    } else if ($obj == 'included_baggage_string') {
        return $includedBaggage['baggage_string'];
    } else if ($obj == 'included_baggage_no') {
        return $includedBaggage['cabin_bags'];
    } else if ($obj == 'included_baggage_weight') {
        $str = $includedBaggage['baggage_weight_cabin_string'];
        if (strlen($includedBaggage['baggage_weight_personal_string']) > 2) {
            //Append personal items dimensions
            $str .= __('Kg (Cabin bag)') . ' + ' . $includedBaggage['baggage_weight_personal_string'] . ' Kg (Personal Item)';
        }
        return $str;
    } else if ($obj == 'included_baggage_dimensions') {
        $str = $includedBaggage['baggage_dimensions_cabin_string'];
        if (strlen($includedBaggage['baggage_dimensions_personal_string']) > 2) {
            //Append personal items dimensions
            $str .= __(' cm (Cabin bag)') . ' + ' . $includedBaggage['baggage_dimensions_personal_string'] . ' (Personal Item)';
        }
        return $str;
    }
}

function free_bag_combination_parser_passenger($baggage, $type)
{
    $cabinBags = [];
    $personalItems = [];
    $baggageString = '';
    //Build string for cabin bag weight e.g 10, 25
    $baggage_weight_cabin_string = '';
    //Build string for personal items such as handbags weight e.g 10, 25
    $baggage_weight_personal_string = '';
    //Build string for cabin bag dimensions in L x W x H e.g 70 x 40 x 55
    $baggage_dimensions_cabin_string = '';
    //Build string for personal items dimensions in L x W x H e.g 70 x 40 x 55
    $baggage_dimensions_personal_string = '';
    //Only look for included cabin bags => not personal items
    foreach ($baggage->combinations->hand_bag as $cabin_bag) {
        if (
            //!$cabin_bag->is_hold //This is not a personal item
            $cabin_bag->category == "hand_bag"
            && !empty($cabin_bag->indices)
            && in_array($type, $cabin_bag->conditions->passenger_groups)) //This bag is also available for this passenger type

        {
            //Check the definition for this baggage. it is a hold item ?
            foreach ($baggage->definitions->hand_bag as $cabin_bag_definition) {
                {
                    if ($cabin_bag_definition->category == "cabin_bag") {
                        array_push($cabinBags, $cabin_bag);
                        $baggage_weight_cabin_string = $cabin_bag_definition->restrictions->weight;
                        $baggage_dimensions_cabin_string = '';
                        $baggage_dimensions_cabin_string .= $cabin_bag_definition->restrictions->length;
                        $baggage_dimensions_cabin_string .= ' x ' . $cabin_bag_definition->restrictions->width;
                        $baggage_dimensions_cabin_string .= ' x ' . $cabin_bag_definition->restrictions->height;
                    }
                }

            }
        }
    }
    $baggageString .= count($cabinBags) . __('× cabin bag');

    //Personal Items
    foreach ($baggage->combinations->hand_bag as $cabin_bag) {
        if (
            //!$cabin_bag->is_hold //This is not a personal item
            $cabin_bag->category == "hand_bag"
            && !empty($cabin_bag->indices)
            && in_array($type, $cabin_bag->conditions->passenger_groups)) //This bag is also available for this passenger type

        {
            //Check the definition for this baggage. it is a hold item ?
            foreach ($baggage->definitions->hand_bag as $cabin_bag_definition) {
                {
                    if ($cabin_bag_definition->category == "personal_item") {
                        array_push($personalItems, $cabin_bag);
                        $baggage_weight_personal_string = $cabin_bag_definition->restrictions->weight;
                        $baggage_dimensions_personal_string = '';
                        $baggage_dimensions_personal_string .= $cabin_bag_definition->restrictions->length;
                        $baggage_dimensions_personal_string .= ' x ' . $cabin_bag_definition->restrictions->width;
                        $baggage_dimensions_personal_string .= ' x ' . $cabin_bag_definition->restrictions->height;
                    }
                }

            }
        }
    }

    //Combine this string
    if (count($personalItems) > 0) {
        $baggageString .= ' + ' . count($personalItems) . __('× Personal item(s)');
    }

    return array(
        'cabin_bags' => $cabinBags,
        'personal_items' => $personalItems,
        'baggage_string' => $baggageString,
        'baggage_weight_cabin_string' => $baggage_weight_cabin_string,
        'baggage_weight_personal_string' => $baggage_weight_personal_string,
        'baggage_dimensions_cabin_string' => $baggage_dimensions_cabin_string,
        'baggage_dimensions_personal_string' => $baggage_dimensions_personal_string
    );

}

function get_checked_bag_combination_title_for_passenger($baggage, $type, $obj = 'checked_baggage_string')
{
    $checkedBaggage = checked_bag_combination_parser_passenger($baggage, $type);
    if ($obj == 'image') {
        if (count($checkedBaggage['checked_bags']) > 0) {
            return asset('images/animated/travel-bag.json');
        } else {
            return asset('images/animated/travel-bag-single.json');
        }

    } else if ($obj == 'checked_baggage') {
        return $checkedBaggage['checked_bags'];
    } else if ($obj == 'checked_baggage_string') {
        return $checkedBaggage['baggage_string'];
    } else if ($obj == 'checked_baggage_weight') {
        return $checkedBaggage['baggage_weight_checked_string'];
    } else if ($obj == 'checked_baggage_dimensions') {
        return $checkedBaggage['baggage_dimensions_checked_string'];
    }
}

function get_country_code_from_id($id)
{
    $countries_data = file_get_contents(public_path('vendor/countries/countries.json'));
    $countries_data = json_decode($countries_data, true);
    foreach ($countries_data as $country) {
        if ($country['id'] == $id) {
            return $country['alpha2'];
        }
    }

}


function checked_bag_combination_parser_passenger($baggage, $type)
{
    $checkedBags = [];
    $baggageString = '';
    //Build string for cabin bag weight e.g 10, 25
    $baggage_weight_cabin_string = '';

    //Build string for cabin bag dimensions in L x W x H e.g 70 x 40 x 55
    $baggage_dimensions_cabin_string = '';

    //Only look for checked cabin bags
    foreach ($baggage->combinations->hold_bag as $checked_bag) {

        if (
            $checked_bag->category == "hold_bag"
            && in_array($type, $checked_bag->conditions->passenger_groups)) //This bag is also available for this passenger type

        {
            //Check the definition for this baggage. it is a hold item ?
            foreach ($baggage->definitions->hold_bag as $checked_bag_definition) {
                {
                    if ($checked_bag_definition->category == "hold_bag") {
                        array_push($checkedBags, $checked_bag);
                        $baggage_weight_cabin_string = $checked_bag_definition->restrictions->weight;
                        $baggage_dimensions_cabin_string = '';
                        $baggage_dimensions_cabin_string .= $checked_bag_definition->restrictions->length;
                        $baggage_dimensions_cabin_string .= ' x ' . $checked_bag_definition->restrictions->width;
                        $baggage_dimensions_cabin_string .= ' x ' . $checked_bag_definition->restrictions->height;
                    }
                }

            }
        }
    }
    $baggageString .= count($checkedBags) . __(count($checkedBags) > 1 ? '× checked bags' : '× checked bag');


    return array(
        'checked_bags' => $checkedBags,
        'baggage_string' => $baggageString,
        'baggage_weight_checked_string' => $baggage_weight_cabin_string,
        'baggage_dimensions_checked_string' => $baggage_dimensions_cabin_string,
    );


}

function get_combination_definition($baggage, $checked_bag, $obj = 'dimensions')
{
    $definitions = $baggage->definitions->hold_bag;
    $checked_baggage_definition = [];
    foreach ($definitions as $definition) {
        if ($definition->category == "hold_bag") {
            return $definition->restrictions->length . ' x ' . $definition->restrictions->width . ' x ' . $definition->restrictions->height;
        }
    }
}
