/**
 * Lazy Load Google Map
 * Aurora Hotel Plaza - Performance Optimization
 */

(function() {
    'use strict';
    
    let mapLoaded = false;
    
    // Load map function
    window.loadMap = function() {
        if (mapLoaded) return;
        
        const iframe = document.getElementById('google-map');
        const placeholder = document.getElementById('map-placeholder');
        
        if (iframe && placeholder) {
            iframe.src = iframe.dataset.src;
            iframe.style.display = 'block';
            placeholder.style.display = 'none';
            mapLoaded = true;
        }
    };
    
    // Auto-load map when scrolled into view using IntersectionObserver
    if ('IntersectionObserver' in window) {
        const mapObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    window.loadMap();
                    mapObserver.disconnect();
                }
            });
        }, { rootMargin: '200px' });
        
        document.addEventListener('DOMContentLoaded', function() {
            const mapSection = document.getElementById('map-section');
            if (mapSection) {
                mapObserver.observe(mapSection);
            }
        });
    }
})();
