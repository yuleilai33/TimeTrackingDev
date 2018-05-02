@extends('layouts.html')
@section('wrapper')
    <div id="wrapper">
        <div class="main">
            @yield('content')
        </div>
        <div class="clearfix"></div>
        <footer>
            <div class="container-fluid">
                <p class="copyright">&copy; 2018 <a href="https://newlifecfo.com" target="_blank">New Life CFO</a>. All Rights Reserved.</p>
            </div>
        </footer>
    </div>
@endsection