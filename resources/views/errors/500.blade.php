@extends('errors.layout')

@section('code', '500')
@section('title', __('messages.error_server_title'))
@section('message', __('messages.error_server_message'))

@section('icon')
<svg class="w-8 h-8 text-red-400 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>
@endsection
