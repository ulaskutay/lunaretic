@php
    $prepareUrl = route('paytr.token');
    $callbackUrl = route('paytr.callback');
@endphp

<script>
    window.eticPaytrCheckout = {
        prepareUrl: @json($prepareUrl),
        callbackUrl: @json($callbackUrl),
    };
</script>
