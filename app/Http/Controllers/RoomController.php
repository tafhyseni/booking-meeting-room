<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Room;
use Auth;
use App\Booking;
use Calendar;
use Carbon\Carbon;

class RoomController extends Controller
{
    /**
     * Browse meeting rooms page 
     */
    public function browse(Request $request)
    {

        $search = $request->get('search');

        if($search){
            $rooms = Room::where('name', 'LIKE', '%'.$search.'%')
                            ->orWhere('location', 'LIKE', '%'.$search.'%')
                            ->paginate(6);
        } else {
            $rooms = Room::paginate(6);
        }
    	

    	return view('browse', compact('rooms', 'search'));
    }

    /**
     * Detail meeting room
     */
    public function detail($permalink)
    {
    	$data = Room::where('permalink', $permalink)->first();

        $bookings = [];

        $fetch_bookings = Booking::whereHas('room', function($q) use ($permalink) {
                                        $q->where('permalink', $permalink);
                                    })
                                    ->whereDate('start_date', '>=', Carbon::now()->format('Y-m-d'))
                                    ->whereDate('start_date', '<=', Carbon::now()->addMonths(2)->format('Y-m-d'))
                                    ->get();

        foreach($fetch_bookings as $key => $item) {
            $full_day = false;
            $start = Carbon::parse($item->start_date)->format('y-m-d H:i');
            $end = Carbon::parse($item->end_date)->format('y-m-d H:i');
            
            if($item->day != 0)
            {
                $full_day = true;
                $start = Carbon::parse($item->start_date)->format('y-m-d');
                $end = Carbon::parse($item->end_date)->format('y-m-d');
            }

            $bookings[] = Calendar::event(
                'Reserved',
                $full_day,
                $start,
                $end,
                $key,
                [
                    'url' => '',
                    'color' => '#800'
                ]
            );
        }

        $calendar = Calendar::addEvents($bookings)
                            ->setOptions([
                                'defaultView' => 'agendaDay',
                                'validRange' => [
                                    'start' => Carbon::now()->format('Y-m-d'),
                                    'end' => Carbon::now()->addMonths(1)->format('Y-m-d')
                                ]
                            ])
                            ->setCallbacks([
                                'themeSystem' => '"bootstrap4"'
                            ]);

    	return view('room', compact('data', 'calendar'));
    }
}
