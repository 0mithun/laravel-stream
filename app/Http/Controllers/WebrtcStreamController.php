<?php

namespace App\Http\Controllers;

use App\Events\StreamAnswer;
use App\Events\StreamOffer;
use Illuminate\Http\Request;

class WebrtcStreamController extends Controller
{


    public function index()
    {
        return view('video-broadcast', [
            'type'      =>  'broadcaster',
            'id'        =>  auth()->id(),
        ]);
    }


    public function consumer(Request $request, $streamId)
    {
        return view('video-broadcast', [
            'type'      =>  'consumer',
            'id'        =>  auth()->id(),
            'streamId'  =>  $streamId,
        ]);
    }

    public function makeStreamoffer(Request $request)
    {
        $data = [
            'broadcaster'   =>  $request->broadcaster,
            'receiver'   =>  $request->receiver,
            'offer'   =>  $request->offer,
        ];
        info($request);

        event(new StreamOffer($data));
    }

    public function makeStreamAnswer(Request $request)
    {
        $data = [
            'broadcaster'   =>  $request->broadcaster,
            'answer'   =>  $request->answer,
        ];

        event(new StreamAnswer($data));
    }
}
