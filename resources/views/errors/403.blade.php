@extends('errors.layout')

@section('color', 'red')
@section('code', '403')
@section('title', __('messages.error_403_title'))
@section('message', __('messages.error_403_message'))

@section('icon')
<x-icon.ban class="w-7 h-7 text-white" />
@endsection
