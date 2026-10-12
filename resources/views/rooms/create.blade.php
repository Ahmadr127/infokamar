@extends('layouts.app')

@section('title', 'Tambah Ruangan')

@section('content')
<div class="w-full mx-auto max-w-3xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Tambah Ruangan</h2>
            @include('rooms._form', ['room' => null])
        </div>
    </div>
</div>
@endsection
