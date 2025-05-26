@extends('layouts.app')

@section('title', 'แดชบอร์ด - ศูนย์เปล')

@section('content')
<div class="container">
    <livewire:stretcher-dashboard />
</div>
@endsection

@push('scripts')
<script>
    // Listen for Reverb events and handle them
    if (window.Echo) {
        window.Echo.channel('stretcher-updates')
            .listen('StretcherUpdated', (e) => {
                console.log('📨 Stretcher update received:', e);
                
                // Let Livewire handle the data refresh
                // The component will receive the event automatically
            });
    }
</script>
@endpush