@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_invalid_link_title'))

@section('content')
<x-auth-invalid-link
    :retryRoute="route('superadmin.index')"
    :retryLabel="__('messages.btn_retry')"
    color="amber"
    iconColor="red"
    icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
/>
@endsection
