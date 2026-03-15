/**
 * Aurora Hotel Plaza - Index Page Scripts
 * Extracted from index.php
 */

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('action') === 'track_booking') {
        // Clear URL to prevent re-opening on refresh
        window.history.replaceState({}, document.title, window.location.pathname);

        setTimeout(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            if (typeof toggleTrackForm === 'function') {
                toggleTrackForm(true);

                // Show enhanced tooltip with higher z-index - Gold theme
                const trackInput = document.getElementById('trackInput');
                if (trackInput) {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'help-tooltip-highlight';
                    tooltip.innerHTML = `
                        <div class="tooltip-content">
                            <span class="tooltip-icon">🔍</span>
                            <span class="tooltip-text"><strong>Theo dõi đơn đặt phòng</strong><br>Nhập mã đặt phòng, SĐT hoặc email của bạn vào đây!</span>
                        </div>
                    `;

                    // Style the tooltip - Gold theme matching website
                    tooltip.style.cssText = `
                        position: fixed;
                        z-index: 100000;
                        background: rgba(30, 41, 59, 0.95);
                        backdrop-filter: blur(16px) saturate(120%);
                        -webkit-backdrop-filter: blur(16px) saturate(120%);
                        color: white;
                        padding: 16px 20px;
                        border-radius: 12px;
                        border: 1px solid rgba(212, 175, 55, 0.3);
                        box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
                        font-size: 14px;
                        font-weight: 500;
                        line-height: 1.5;
                        pointer-events: none;
                        animation: tooltipPulse 2s ease-in-out infinite;
                        max-width: 320px;
                    `;

                    // Position the tooltip above the input
                    const rect = trackInput.getBoundingClientRect();
                    const isMobile = window.innerWidth < 640;

                    if (isMobile) {
                        // Mobile: show below the input
                        tooltip.style.left = '50%';
                        tooltip.style.top = (rect.bottom + 15) + 'px';
                        tooltip.style.transform = 'translateX(-50%)';
                        tooltip.style.marginTop = '10px';
                    } else {
                        // Desktop: show above and to the left
                        tooltip.style.right = '100%';
                        tooltip.style.top = '50%';
                        tooltip.style.transform = 'translateY(-50%)';
                        tooltip.style.marginRight = '20px';
                    }

                    trackInput.parentNode.appendChild(tooltip);

                    // Add animation keyframes
                    const style = document.createElement('style');
                    style.textContent = `
                        @keyframes tooltipPulse {
                            0%, 100% {
                                transform: ${isMobile ? 'translateX(-50%) scale(1)' : 'translateY(-50%) scale(1)'};
                                box-shadow: 0 8px 32px rgba(212, 175, 55, 0.3);
                                border-color: rgba(212, 175, 55, 0.3);
                            }
                            50% {
                                transform: ${isMobile ? 'translateX(-50%) scale(1.05)' : 'translateY(-50%) scale(1.05)'};
                                box-shadow: 0 12px 40px rgba(212, 175, 55, 0.5);
                                border-color: rgba(212, 175, 55, 0.5);
                            }
                        }
                        .tooltip-content {
                            display: flex;
                            align-items: center;
                            gap: 12px;
                        }
                        .tooltip-icon {
                            font-size: 24px;
                            flex-shrink: 0;
                        }
                        .tooltip-text strong {
                            display: block;
                            margin-bottom: 4px;
                            font-size: 15px;
                            color: #d4af37;
                        }
                    `;
                    document.head.appendChild(style);

                    // Remove tooltip after 8 seconds
                    setTimeout(() => {
                        tooltip.style.opacity = '0';
                        tooltip.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        tooltip.style.transform = isMobile ? 'translateX(-50%) translateY(-10px)' : 'translateY(-50%) translateX(10px)';
                        setTimeout(() => tooltip.remove(), 500);
                    }, 8000);
                }
            }
        }, 500);
    }
});
