@extends('layout.app')
@section('content')
    @push('meta')
        <title>About us</title>

    @endpush

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/about.css') }}">
    @endpush







@endsection

@push('scripts')
    <script src="{{ asset('assets/js/about.js') }}"></script>
@endpush