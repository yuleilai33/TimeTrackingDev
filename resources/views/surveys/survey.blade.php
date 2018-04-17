@extends('layouts.app')

@section('content')
    <div class="main-content">


<!-- show all the surveys the auth user has access to -->

    	@include('surveys.index')


    </div>


@endsection