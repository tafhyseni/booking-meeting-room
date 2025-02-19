<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
/*
use Stripe\Stripe;
use Stripe\Charge;
*/
use App\Room;
use App\Booking;
use Auth;

class BookingController extends Controller
{
    /**
     * Booking meeting room
     */
    public function create($permalink)
    {
    	$data      = Room::where('permalink', $permalink)->first();
        $customer  = Auth::user();

    	return view('booking', compact('data', 'customer'));
    }

    /**
     * Validation booking data
     */
    public function validation(Request $request)
    {
    	$validator = Validator::make($request->all(), [
    		'name' => 'required|string|max:225',
    		'date' => 'required|string|max:225',
    		'day' => 'required|integer|min:0',
    	]);

        if($validator->fails())
        {
            $response = [ 'status' => 'error', 'errors' => $validator->errors() ];            
        } else {
        	$room = Room::where('permalink', $request->permalink)
                        ->first();

            if($request->day == 0)
            {
                $date = $request->date;
                $end_date = $request->end_date;
                $booked = Booking::where(function ($query) use ($date, $end_date) {
                        $query->where(function ($query) use ($date){
                            $query->whereRaw('? > date_format(start_date, "%Y-%m-%d %H:%i") and ? < date_format(end_date, "%Y-%m-%d %H:%i")', [$date, $date]);
                        })
                        ->orWhere(function ($query) use ($end_date) {
                            $query->whereRaw('? > date_format(start_date, "%Y-%m-%d %H:%i") and ? < date_format(end_date, "%Y-%m-%d %H:%i")', [$end_date, $end_date]);
                        })
                        ->orWhere(function ($query) use ($date, $end_date) {
                            $query->whereRaw('? <= date_format(start_date, "%Y-%m-%d %H:%i") and ? >= date_format(end_date, "%Y-%m-%d %H:%i")', [$date, $end_date]);
                        });
                    })
                    ->where('room_id', $room->id)
                    ->count();
            }else{
                $booked = Booking::where('room_id', $room->id)
                    ->whereDate('start_date', '<=', Carbon::parse($request->date))
                    ->whereDate('end_date', '>=', Carbon::parse($request->date)->addDays($request->day - 1))
                    ->count();
            }
            
            if($booked > 0){
                $response = [ 'status' => 'error', 'errors' => ['date' => 'Meeting room not available in this date, please try another date or meeting room.'] ];  
            } else {
                $email  = Auth::user()->email;
                $response = [ 
                    'status' => 'valid', 
                    'msg' => 'Data validated.', 
                    'room' => $room, 
                    'email' => $email,
                    'booked' => $booked
                ];
            }

        	
        }

    	return response()->json($response);
    }

    /**
     * Store the booking data
     */
    public function store(Request $request)
    {	
    	$user = Auth::user();
    	$room = Room::find($request->room);

    	try {
            /**
    		Stripe::setApiKey(env('STRIPE_SECRET'));

    		Charge::create([
			    'amount' => ($room->price * 100) * $request->day,
			    'currency' => 'usd',
			    'description' => 'Booking: '.$room->name,
			    'source' => $request->stripeToken,
			    'receipt_email' => $user->email,
			]);
            */
            if($request->full_day)
            {
                $end = Carbon::parse($request->date)->addDays($request->day - 1); 
                $day = $request->day;              
            }else{
                $end = Carbon::parse($request->end_date);
                $day = 0;
            }

			$booking = Booking::create([
				'number' => 'ID'.date('ymdGis'),
				'user_id' => $user->id,
				'room_id' => $room->id,
				'start_date' => Carbon::parse($request->date),
				'end_date' => $end,
				'day' => $day,
				'total' => $room->price * $request->day,
				'note' => $request->note
			]);

    		$response = [ 'status' => 'success', 'msg' => 'Thank you! Your payment has been successfully received.', 'booking' => $booking ];
    	/**
        } catch(\Stripe\Error\Card $e) {
            $body = $e->getJsonBody();
            $errors[] = $body['error']['message'];
        } catch (\Stripe\Error\RateLimit $e) {
            $body = $e->getJsonBody();
            $errors[] = $body['error']['message'];
        } catch (\Stripe\Error\InvalidRequest $e) {
            $body = $e->getJsonBody();
            $errors[] = $body['error']['message'];
        } catch (\Stripe\Error\Authentication $e) {
            $body = $e->getJsonBody();
            $errors[] = $body['error']['message'];
        } catch (\Stripe\Error\ApiConnection $e) {
            $body = $e->getJsonBody();
            $errors[] = $body['error']['message'];
        } catch (\Stripe\Error\Base $e) {
            $body = $e->getJsonBody();
            $errors[] = $body['error']['message'];
        } catch (Exception $e) {
            $body = $e->getJsonBody();
            $errors[] = $body['error']['message'];
        */
        } catch(Exception $e) {
            $response = ['status' => 'Failed'];
        }

        if( empty($errors) ){
            return response()->json( $response );
        } else {
            return response()->json( ['errors' => $errors] );
        }

    	return response()->json($response);
    }
}
