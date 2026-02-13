@extends('layout.app')
@section('content')
    @push('meta')
        <title>Profile</title>

    @endpush

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/profile.css') }}">
    @endpush







@endsection

@push('scripts')
    <script src="{{ asset('assets/js/about.js') }}"></script>
@endpush