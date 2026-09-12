@extends('emails.layouts.racines')

@section('title', $subject)

@section('content')
    {!! nl2br(e($content)) !!}
@endsection
