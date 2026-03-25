@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.verify_confirm_title'))

@section('content')
<x-auth-verify-confirm
    :formAction="route('admin.verify', ['token' => $token])"
    :token="$token"
    color="violet"
    iconName="icon.shield-check"
/>
@endsection
