@php($tracking = $eticTrackingConfig ?? app(\App\Etic\Integrations\Marketing\TrackingSettings::class)->resolved())
@if($tracking['gtm_container_id'] ?? null)
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $tracking['gtm_container_id'] }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
<script>
window.dataLayer = window.dataLayer || [];
window.eticTrack = function (event, params) {
    params = params || {};
    window.dataLayer.push(Object.assign({ event: event }, params));
    if (typeof gtag === 'function') {
        gtag('event', event, params);
    }
    if (typeof fbq === 'function') {
        var map = {
            view_item: 'ViewContent',
            view_item_list: 'ViewContent',
            view_category: 'ViewContent',
            add_to_cart: 'AddToCart',
            begin_checkout: 'InitiateCheckout',
            add_payment_info: 'AddPaymentInfo',
            purchase: 'Purchase',
            search: 'Search'
        };
        if (map[event]) {
            var eventID = params.event_id;
            var pixelParams = Object.assign({}, params);
            delete pixelParams.event_id;
            delete pixelParams.user;
            fbq('track', map[event], pixelParams, eventID ? {eventID: eventID} : {});
        }
    }
};
</script>
@isset($eticTracking)
<script>
@foreach($eticTracking->dataLayer() as $event)
window.eticTrack(@json($event['event']), @json(\Illuminate\Support\Arr::except($event, ['event'])));
@endforeach
</script>
@endisset
