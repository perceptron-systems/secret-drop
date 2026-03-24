@extends('errors.layout')

@section('code', '419')
@section('title', __('messages.error_419_title'))
@section('message', __('messages.error_419_message'))

@section('icon')
<svg class="w-8 h-8 text-amber-400 dark:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>
@endsection
