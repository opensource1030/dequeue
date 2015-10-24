<?php

namespace App\Helpers;

use Log;

class LocationHelper {

    function getCoordinate($streetText, $pre_address=false)
    {
        Log::info("+++++ getCoordinate : {$streetText} : {$pre_address}");

        $location = null;

        for($i=0; $i<10; $i++)
        {
            $streetAry = array();
            $streetAry = explode(' ', $streetText);
            $street='';
            for($j=0; $j < count($streetAry); $j++)
            {
                $street .= $streetAry[$j] . ' ';
            }
            $address = $street . ' ' .$pre_address;
            $Addr = strtolower($address);
            if(strpos($Addr, 'in') !== false)
            {

            }
            else
            {
                $address = $address.' IN';
            }
            $prepAddr = str_replace(' ', '+', $address);
            $geocode = file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $prepAddr . '&sensor=false');

            Log::info("{$i} : {$geocode}");
            //print_r($geocode);

            $output= json_decode($geocode);

            if (isset($output->results) && count($output->results) > 0) {
                $location['lat'] = $output->results[0]->geometry->location->lat;
                $location['long'] = $output->results[0]->geometry->location->lng;
                if($location['lat'] != '' && $location['long'] != '')
                    break;
            }
        }

        Log::info("----- getCoordinate");

        return $location;
    }

    function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $lon1 *= $pi80;
        $lat2 *= $pi80;
        $lon2 *= $pi80;

        $r = 6372.797; // mean radius of Earth in km
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km = $r * $c;

        //echo '<br/>'.$km;
        return $km;
    }

    function getZipCode($lat, $lng)
    {
        $url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.trim($lat).','.trim($lng).'&sensor=false';
        $json = @file_get_contents($url);
        $data = json_decode($json);

        if(!empty($data->results))
        {
            foreach ($data->results as $result)
            {
                foreach($result->address_components as $addressPart)
                {
                    if(((in_array('locality', $addressPart->types)) && (in_array('political', $addressPart->types))) || (in_array('sublocality', $addressPart->types)) && (in_array('political', $addressPart->types)))
                        $city = $addressPart->long_name;
                    else if((in_array('administrative_area_level_1', $addressPart->types)) && (in_array('political', $addressPart->types)))
                        $state = $addressPart->long_name;
                    else if((in_array('country', $addressPart->types)) && (in_array('political', $addressPart->types)))
                        $country = $addressPart->long_name;
                    else if((in_array('postal_code', $addressPart->types)))
                        $postalCode = $addressPart->long_name;
                    else if((in_array('route', $addressPart->types)))
                        $route = $addressPart->long_name;
                    else if((in_array('street_number', $addressPart->types)))
                        $street_number = $addressPart->long_name;
                }

                $GeoResultAry['szAddress1'] = $street_number;
                $GeoResultAry['szAddress2'] = $route;
                $GeoResultAry['szCity'] = $city;
                $GeoResultAry['szState'] = $state;
                $GeoResultAry['szZipCode'] = $postalCode;
                $GeoResultAry['szCountry'] = $country;
                $GeoResultAry['szLatitude'] = trim($lat);
                $GeoResultAry['szLongitude'] = trim($lng);
            }
            return $GeoResultAry;
        }
        else
        {
            return false;
        }
    }
}