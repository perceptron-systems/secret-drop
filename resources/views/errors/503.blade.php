@extends('errors.layout')

@section('color', 'amber')
@section('code', '503')
@section('title', __('messages.error_503_title'))
@section('message', __('messages.error_503_message'))

@section('icon')
<x-icon.settings class="w-7 h-7 text-white" />
@endsection
