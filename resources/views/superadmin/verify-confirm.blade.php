@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.verify_confirm_title'))

@section('content')
<x-auth-verify-confirm
    :formAction="route('superadmin.verify', ['token' => $token])"
    :token="$token"
    color="amber"
    iconName="icon.shield-check"
/>
@endsection
