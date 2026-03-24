@extends('errors.layout')

@section('code', '429')
@section('title', __('messages.error_429_title'))
@section('message', __('messages.error_429_message'))

@section('icon')
<x-icon.warning class="w-8 h-8 text-amber-400 dark:text-amber-500" />
@endsection
