<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '724550942954701');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=724550942954701&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '2465802043847377');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=2465802043847377&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
<script>
if (!window.metaTrackCatalogEvent) {
	window.metaTrackCatalogEvent = function(eventName, contentIds, extra) {
		if (typeof fbq !== 'function') return;
		var ids = Array.isArray(contentIds) ? contentIds : [contentIds];
		var cleanIds = [];
		for (var i = 0; i < ids.length; i++) {
			if (ids[i] === null || typeof ids[i] === 'undefined') continue;
			var id = String(ids[i]).trim();
			if (id) cleanIds.push(id);
		}
		if (!cleanIds.length) return;
		var payload = {
			content_ids: cleanIds,
			content_type: 'product'
		};
		if (extra && typeof extra === 'object') {
			for (var key in extra) {
				if (Object.prototype.hasOwnProperty.call(extra, key)) {
					payload[key] = extra[key];
				}
			}
		}
		fbq('track', eventName, payload);
	};
}
</script>
