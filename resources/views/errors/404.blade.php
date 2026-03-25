@extends('errors.layout')

@section('color', 'violet')
@section('code', '404')
@section('title', __('messages.error_404_title'))
@section('message', __('messages.error_404_message'))

@section('icon')
<x-icon.sad-face class="w-7 h-7 text-white" />
@endsection
