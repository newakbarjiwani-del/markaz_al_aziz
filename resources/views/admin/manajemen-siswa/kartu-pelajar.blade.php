@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/id-card.css') }}?v=19">
@endpush

@section('content')
@include('admin.partials.id-card-gallery', [
    'cards' => $cards,
    'cardType' => $cardType,
    'classes' => $classes ?? null,
])
@endsection
