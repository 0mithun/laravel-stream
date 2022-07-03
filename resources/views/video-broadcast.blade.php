@extends('layouts.app')


@section('content')
    @if ($type== 'broadcaster')
        <broadcaster
            :auth_user_id="{{ $id }}"
            env="{{ env('APP_ENV')  }}"
            turn_url="{{ env('TURN_SERVER_URL') }}"
            turn_username="{{ env('TURN_SERVER_USERNAME') }}"
            turn_credential="{{ env('TURN_SERVER_CREDENTIAL') }}"
        ></broadcaster>
    @else
    <viewer
        :auth_user_id="{{ $id }}"
        stream_id="{{ $streamId  }}"
        turn_url="{{ env('TURN_SERVER_URL') }}"
        turn_username="{{ env('TURN_SERVER_USERNAME') }}"
        turn_credential="{{ env('TURN_SERVER_CREDENTIAL') }}"
    ></viewer>
    @endif

@endsection
