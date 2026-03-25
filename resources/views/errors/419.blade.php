@extends('errors.layout')

@section('color', 'amber')
@section('code', '419')
@section('title', __('messages.error_419_title'))
@section('message', __('messages.error_419_message'))

@section('icon')
<x-icon.clock class="w-7 h-7 text-white" />
@endsection
