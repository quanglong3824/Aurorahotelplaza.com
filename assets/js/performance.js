/**
 * Performance Optimization Script
 * Aurora Hotel Plaza
 * 
 * Features:
 * - Lazy loading images
 * - Defer non-critical resources
 * - Optimize animations
 * - Preload critical resources
 */

(function() {
    'use strict';

    // ============================================
    // 1. LAZY LOADING IMAGES
    // ============================================
    
    /**
     * Intersection Observer for lazy loading
     */
    const lazyLoadImages = () => {
        const images = document.querySelectorAll('img[data-src], img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        // Load the image
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                        }
                        
                        // Add loaded class for fade-in effect
                        img.classList.add('lazy-loaded');
                        
                        // Stop observing this image
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px', // Start loading 50px before entering viewport
                threshold: 0.01
            });
            
            images.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback for browsers without IntersectionObserver
            images.forEach(img => {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                }
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                }
            });
        }
    };

    // ============================================
    // 2. LAZY LOAD BACKGROUND IMAGES
    // ============================================
    
    const lazyLoadBackgrounds = () => {
        const backgrounds = document.querySelectorAll('[data-bg]');
        
        if ('IntersectionObserver' in window) {
            const bgObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        element.style.backgroundImage = `url('${element.dataset.bg}')`;
                        element.removeAttribute('data-bg');
                        element.classList.add('bg-loaded');
                        observer.unobserve(element);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            backgrounds.forEach(bg => bgObserver.observe(bg));
        } else {
            backgrounds.forEach(bg => {
                bg.style.backgroundImage = `url('${bg.dataset.bg}')`;
            });
        }
    };

    // ============================================
    // 3. PRELOAD CRITICAL RESOURCES
    // ============================================
    
    const preloadCriticalResources = () => {
        // Preload critical fonts
        const fonts = [
            '/assets/fonts/playfair-display-700.ttf',
            '/assets/fonts/inter-regular.ttf'
        ];
        
        fonts.forEach(font => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'font';
            link.type = 'font/ttf';
            link.href = font;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        });
    };

    // ============================================
    // 4. DEFER NON-CRITICAL CSS
    // ============================================
    
    const loadDeferredCSS = () => {
        const deferredStyles = document.querySelectorAll('link[data-defer]');
        
        deferredStyles.forEach(link => {
            link.rel = 'stylesheet';
            link.removeAttribute('data-defer');
        });
    };

    // ============================================
    // 5. OPTIMIZE ANIMATIONS
    // ============================================
    
    const optimizeAnimations = () => {
        // Reduce motion for users who prefer it
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        if (prefersReducedMotion) {
            document.documentElement.classList.add('reduce-motion');
            
            // Disable CSS animations
            const style = document.createElement('style');
            style.textContent = `
                *, *::before, *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            `;
            document.head.appendChild(style);
        }
    };

    // ============================================
    // 6. DEBOUNCE SCROLL/RESIZE EVENTS
    // ============================================
    
    const debounce = (func, wait = 100) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // ============================================
    // 7. RESOURCE HINTS
    // ============================================
    
    const addResourceHints = () => {
        // DNS Prefetch for external domains
        const domains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://cdn.tailwindcss.com',
            'https://accounts.google.com'
        ];
        
        domains.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'dns-prefetch';
            link.href = domain;
            document.head.appendChild(link);
        });
        
        // Preconnect to critical domains
        const preconnectDomains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com'
        ];
        
        preconnectDomains.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = domain;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        });
    };

    // ============================================
    // 8. IMAGE OPTIMIZATION HELPER
    // ============================================
    
    const optimizeImages = () => {
        const images = document.querySelectorAll('img:not([loading])');
        
        images.forEach(img => {
            // Add loading="lazy" to images below the fold
            const rect = img.getBoundingClientRect();
            if (rect.top > window.innerHeight) {
                img.loading = 'lazy';
            }
            
            // Add decoding="async" for better performance
            img.decoding = 'async';
        });
    };

    // ============================================
    // 9. CRITICAL CSS INLINE
    // ============================================
    
    const inlineCriticalCSS = () => {
        // This would typically be done during build process
        // For runtime, we can prioritize critical styles
        const criticalStyles = `
            /* Critical above-the-fold styles */
            body { margin: 0; font-family: Inter, sans-serif; }
            .header { position: fixed; top: 0; width: 100%; z-index: 50; }
            /* Add more critical styles as needed */
        `;
        
        // Only add if not already present
        if (!document.querySelector('#critical-css')) {
            const style = document.createElement('style');
            style.id = 'critical-css';
            style.textContent = criticalStyles;
            document.head.insertBefore(style, document.head.firstChild);
        }
    };

    // ============================================
    // 10. PERFORMANCE MONITORING
    // ============================================
    
    const monitorPerformance = () => {
        if ('PerformanceObserver' in window) {
            // Monitor Largest Contentful Paint (LCP)
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                console.log('LCP:', lastEntry.renderTime || lastEntry.loadTime);
            });
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            
            // Monitor First Input Delay (FID)
            const fidObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach(entry => {
                    console.log('FID:', entry.processingStart - entry.startTime);
                });
            });
            fidObserver.observe({ entryTypes: ['first-input'] });
            
            // Monitor Cumulative Layout Shift (CLS)
            let clsScore = 0;
            const clsObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (!entry.hadRecentInput) {
                        clsScore += entry.value;
                        console.log('CLS:', clsScore);
                    }
                }
            });
            clsObserver.observe({ entryTypes: ['layout-shift'] });
        }
        
        // Log page load metrics
        window.addEventListener('load', () => {
            setTimeout(() => {
                const perfData = performance.getEntriesByType('navigation')[0];
                if (perfData) {
                    console.log('Performance Metrics:', {
                        'DNS Lookup': perfData.domainLookupEnd - perfData.domainLookupStart,
                        'TCP Connection': perfData.connectEnd - perfData.connectStart,
                        'Request Time': perfData.responseStart - perfData.requestStart,
                        'Response Time': perfData.responseEnd - perfData.responseStart,
                        'DOM Processing': perfData.domComplete - perfData.domLoading,
                        'Total Load Time': perfData.loadEventEnd - perfData.fetchStart
                    });
                }
            }, 0);
        });
    };

    // ============================================
    // 11. SERVICE WORKER REGISTRATION
    // ============================================
    
    const registerServiceWorker = () => {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('ServiceWorker registered:', registration.scope);
                    })
                    .catch(error => {
                        console.log('ServiceWorker registration failed:', error);
                    });
            });
        }
    };

    // ============================================
    // INITIALIZE ALL OPTIMIZATIONS
    // ============================================
    
    const init = () => {
        // Run immediately
        addResourceHints();
        optimizeAnimations();
        
        // Run on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                lazyLoadImages();
                lazyLoadBackgrounds();
                optimizeImages();
                loadDeferredCSS();
            });
        } else {
            lazyLoadImages();
            lazyLoadBackgrounds();
            optimizeImages();
            loadDeferredCSS();
        }
        
        // Run on load
        window.addEventListener('load', () => {
            preloadCriticalResources();
            monitorPerformance();
            // registerServiceWorker(); // Uncomment when sw.js is ready
        });
    };

    // Start optimization
    init();

    // Export for external use
    window.PerformanceOptimizer = {
        lazyLoadImages,
        lazyLoadBackgrounds,
        optimizeImages,
        debounce
    };

})();
