@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_invalid_link_title'))

@section('content')
<x-auth-invalid-link
    :retryRoute="route('admin.index')"
    :retryLabel="__('messages.btn_request_new_link')"
    color="violet"
    iconColor="amber"
    iconName="icon.clock"
/>
@endsection
