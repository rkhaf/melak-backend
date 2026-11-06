@extends('layouts.admin')

@section('title', 'Edit Penyakit')
@section('header_title', 'Edit Penyakit')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-extrabold text-[#1F2937]">Edit: {{ $disease->name }}</h3>
    <p class="text-sm text-gray-400">Perbarui data penyakit, pengobatan, dan pencegahan</p>
</div>

<form action="{{ route('admin.diseases.update', $disease) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.diseases._form')
</form>
@endsection
