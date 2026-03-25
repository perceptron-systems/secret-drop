@extends('errors.layout')

@section('color', 'red')
@section('code', '500')
@section('title', __('messages.error_server_title'))
@section('message', __('messages.error_server_message'))

@section('icon')
<x-icon.exclamation-circle class="w-7 h-7 text-white" />
@endsection
