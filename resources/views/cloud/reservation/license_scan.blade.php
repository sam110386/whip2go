@extends('layouts.main')
@section('title', "Driver's License Scan")
@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h2>Driver&apos;s License Scan</h2>
            <p>
                Use your camera or upload a clear photo of the front and back of your driver&apos;s license.
                Ensure good lighting, no glare, and the document fills the frame.
                For US licenses, include the back (with the barcode) for best results.
            </p>
            <div class="card mb-3">
                <div class="card-body text-center">
                    <script type="module" src="https://cdn.jsdelivr.net/npm/@microblink/blinkid-in-browser-sdk@6.13.3/ui/dist/blinkid-in-browser/blinkid-in-browser.esm.js"></script>
                    <blinkid-in-browser id="blinkid-web"
                        license-key="sRwCAA93d3cud2hpcDJnby5jb20GbGV5SkRjbVZoZEdWa1QyNGlPakUzTnpJeE1qRTVNamMyT0RFc0lrTnlaV0YwWldSR2IzSWlPaUppWXpsbE1ETTRPQzAzWVdVNExUUTRaV0l0WVRFellTMWlObVk0TURZd05HVTFOelVpZlE9PW7u+RxyJ/cx+cCbwDRawJbfc9kWO0AMvW3Yni/ImJOQYgRc4YJeJsX/O0U2N1mv0wVyRdu81lzVGX4ITWCXI/852LzJDtvpIRnA3XkOb/PJp+Hir8L9K4vaApxKmg=="
                        recognizers="BlinkIdMultiSideRecognizer">
                    </blinkid-in-browser>
                </div>
            </div>
            <h4>Extracted data (preview)</h4>
            <pre id="scan-result" class="bg-light p-3" style="min-height: 120px; white-space: pre-wrap;"></pre>
        </div>
    </div>
</div>
<script type="text/javascript">
(function () {
    function initBlinkIdUi() {
        var blinkId = document.querySelector('blinkid-in-browser');
        if (!blinkId) return;
        blinkId.engineLocation = '{{ config("app.url") }}js/resources/';
        blinkId.workerLocation = '{{ config("app.url") }}js/resources/BlinkIDWasmSDK.worker.min.js';
        blinkId.recognizerOptions = {
            BlinkIdMultiSideRecognizer: {
                saveCameraFrames: true, allowUnparsedMrzResults: true, allowUnverifiedMrzResults: true,
                recognitionModeFilter: { enableFullDocumentRecognition: true, enableMrzId: true, enableMrzPassport: true, enableMrzVisa: true, enableBarcodeId: true, enablePhotoId: true }
            }
        };
        var resultEl = document.getElementById('scan-result');
        blinkId.addEventListener('scanSuccess', function (ev) {
            if (!resultEl) return;
            var data = ev.detail || {};
            try { resultEl.textContent = JSON.stringify(data, null, 2); } catch (e) { resultEl.textContent = 'Scan success, but result could not be stringified.'; }
        });
        blinkId.addEventListener('scanError', function (ev) { if (resultEl) resultEl.textContent = 'Recognition failed: ' + JSON.stringify(ev.detail || {}, null, 2); });
        blinkId.addEventListener('fatalError', function (ev) { if (resultEl) resultEl.textContent = 'Fatal error: ' + JSON.stringify(ev.detail || {}); });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initBlinkIdUi); } else { initBlinkIdUi(); }
})();
</script>
@endsection
