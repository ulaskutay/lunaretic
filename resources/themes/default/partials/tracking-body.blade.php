@if(config('etic.tracking.gtm_container_id'))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('etic.tracking.gtm_container_id') }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
@isset($eticTracking)
<script>
window.dataLayer = window.dataLayer || [];
@foreach($eticTracking->dataLayer() as $event)
window.dataLayer.push(@json($event));
@endforeach
</script>
@endisset
