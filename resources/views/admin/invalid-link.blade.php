@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_invalid_link_title'))

@section('content')
<x-auth-invalid-link
    :retryRoute="route('admin.index')"
    :retryLabel="__('messages.btn_request_new_link')"
    color="violet"
    iconColor="amber"
    icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
/>
@endsection
