/**
 * Aurora Hotel Plaza - JavaScript Error Tracker
 * Bắt tất cả lỗi JS trên toàn bộ frontend và gửi về server
 */
(function () {
    'use strict';

    // ─── Cấu hình ────────────────────────────────────────────────────────────
    var API_ENDPOINT = window.siteBase
        ? window.siteBase + '/api/error-tracker.php'
        : '/api/error-tracker.php';

    var THROTTLE_MS  = 5000;   // Gửi tối đa 1 lỗi / 5 giây (cùng fingerprint)
    var MAX_PER_PAGE = 20;     // Tối đa 20 lỗi mỗi lần load trang
    var sentCount    = 0;
    var sentFingerprints = {};

    // ─── Gửi lỗi lên server ──────────────────────────────────────────────────
    function sendError(payload) {
        if (sentCount >= MAX_PER_PAGE) return;

        // Tạo fingerprint để throttle lỗi trùng
        var fp = (payload.message || '') + '|' + (payload.file || '') + '|' + (payload.line || 0);
        var now = Date.now();
        if (sentFingerprints[fp] && (now - sentFingerprints[fp]) < THROTTLE_MS) return;
        sentFingerprints[fp] = now;
        sentCount++;

        // Thêm thông tin browser
        payload.browser    = navigator.userAgent;
        payload.url        = window.location.href;
        payload.severity   = payload.severity || 'error';
        payload.timestamp  = new Date().toISOString();

        // Gửi bằng Beacon API nếu có, fallback fetch
        var body = JSON.stringify(payload);
        if (navigator.sendBeacon) {
            navigator.sendBeacon(API_ENDPOINT, new Blob([body], { type: 'application/json' }));
        } else {
            fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: body,
                keepalive: true,
            }).catch(function () { /* silent */ });
        }
    }

    // ─── Bắt window.onerror (runtime errors) ──────────────────────────────────
    var prevOnerror = window.onerror;
    window.onerror = function (message, source, lineno, colno, error) {
        // Bỏ qua lỗi từ browser extension hoặc cross-origin script
        if (!source || source === 'undefined' || source.indexOf('chrome-extension') !== -1) {
            return false;
        }

        sendError({
            type    : 'js_error',
            severity: 'error',
            message : String(message),
            file    : source,
            line    : lineno,
            col     : colno,
            stack   : error && error.stack ? error.stack.substring(0, 1000) : '',
        });

        if (typeof prevOnerror === 'function') {
            return prevOnerror.apply(this, arguments);
        }
        return false;
    };

    // ─── Bắt Unhandled Promise Rejections ────────────────────────────────────
    window.addEventListener('unhandledrejection', function (event) {
        var reason = event.reason;
        var message, stack;

        if (reason instanceof Error) {
            message = reason.message;
            stack   = reason.stack ? reason.stack.substring(0, 1000) : '';
        } else {
            message = String(reason);
            stack   = '';
        }

        // Bỏ qua lỗi network thông thường (fetch bị cancel, abort)
        if (message.indexOf('AbortError') !== -1 || message.indexOf('NetworkError') !== -1) return;

        sendError({
            type    : 'js_promise_rejection',
            severity: 'warning',
            message : 'Unhandled Promise Rejection: ' + message,
            stack   : stack,
        });
    });

    // ─── Bắt Resource Load Errors (img, script, link, etc.) ─────────────────
    window.addEventListener('error', function (event) {
        var target = event.target;
        if (!target || target === window) return; // window errors handled by onerror

        var tag = target.tagName ? target.tagName.toLowerCase() : 'unknown';
        var src = target.src || target.href || '';

        // Bỏ qua lỗi không phải resource
        if (!src) return;

        sendError({
            type    : 'js_resource_error',
            severity: 'warning',
            message : 'Resource failed to load: <' + tag + '> ' + src,
            file    : src,
        });
    }, true); // capture phase để bắt resource errors

    // ─── Giám sát fetch/XHR lỗi HTTP (optional, chỉ 500+ errors) ─────────────
    var origFetch = window.fetch;
    if (origFetch) {
        window.fetch = function () {
            var args = arguments;
            return origFetch.apply(this, args).then(function (response) {
                if (response.status >= 500) {
                    var url = typeof args[0] === 'string' ? args[0] : (args[0] && args[0].url ? args[0].url : '');
                    // Bỏ qua chính API error-tracker để tránh vòng lặp
                    if (url.indexOf('error-tracker') === -1) {
                        sendError({
                            type    : 'js_fetch_error',
                            severity: 'error',
                            message : 'HTTP ' + response.status + ' from fetch: ' + url,
                            file    : url,
                        });
                    }
                }
                return response;
            });
        };
    }

    // ─── Expose manual capture API ────────────────────────────────────────────
    window.AuroraTracker = {
        capture: function (type, message, context) {
            sendError({
                type    : type || 'custom',
                severity: 'warning',
                message : message || '',
                context : context || {},
            });
        },
        captureError: function (error, context) {
            sendError({
                type    : 'js_manual',
                severity: 'error',
                message : error instanceof Error ? error.message : String(error),
                stack   : error instanceof Error && error.stack ? error.stack.substring(0, 1000) : '',
                context : context || {},
            });
        },
    };

})();
