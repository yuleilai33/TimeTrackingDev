@extends('layouts.app')

@section('content')
    <div class="main-content">

    	<!-- show the modal page for creating new survey -->
    	{{--@include('surveys.create') --}}


<!-- show all the surveys the auth user has access to -->

    	@include('surveys.index')


    </div>


@endsection