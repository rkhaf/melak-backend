@extends('layouts.admin')

@section('title', 'Tambah Penyakit')
@section('header_title', 'Tambah Penyakit')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-extrabold text-[#1F2937]">Tambah Penyakit</h3>
    <p class="text-sm text-gray-400">Lengkapi data penyakit untuk ditambahkan ke ensiklopedia (FR-09.2.1)</p>
</div>

<form action="{{ route('admin.diseases.store') }}" method="POST" enctype="multipart/form-data">
    @include('admin.diseases._form')
</form>
@endsection
