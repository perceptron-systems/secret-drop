@extends('errors.layout')

@section('color', 'amber')
@section('code', '429')
@section('title', __('messages.error_429_title'))
@section('message', __('messages.error_429_message'))

@section('icon')
<x-icon.warning class="w-7 h-7 text-white" />
@endsection
