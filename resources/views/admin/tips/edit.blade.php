@extends('layouts.admin')

@section('title', 'Edit Tips')
@section('header_title', 'Edit Tips')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-extrabold text-[#1F2937]">Edit Tips #{{ $tip->id }}</h3>
    <p class="text-sm text-gray-400">Perbarui konten tips budidaya</p>
</div>

<form action="{{ route('admin.tips.update', $tip) }}" method="POST">
    @method('PUT')
    @include('admin.tips._form')
</form>
@endsection
