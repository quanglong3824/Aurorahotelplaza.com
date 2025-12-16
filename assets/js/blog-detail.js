document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('interactionSection');
    if (!section) return;

    const postId = section.dataset.postId;
    const starRating = document.getElementById('starRating');
    const likeBtn = document.getElementById('likeBtn');
    const likesCount = document.getElementById('likesCount');
    const ratingAvg = document.getElementById('ratingAvg');
    const ratingCount = document.getElementById('ratingCount');
    const sharesCount = document.getElementById('sharesCount');
    const starBtns = starRating.querySelectorAll('.star-btn');

    // Load initial status
    fetch(`api/blog-interaction.php?action=get_status&post_id=${postId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                if (data.is_liked) likeBtn.classList.add('liked');
                if (data.user_rating > 0) highlightStars(data.user_rating);
                likesCount.textContent = data.likes_count;
                ratingAvg.textContent = data.rating_avg.toFixed(1);
                ratingCount.textContent = data.rating_count;
                sharesCount.textContent = data.shares_count;
            }
        });

    // Handle Rating Hover
    starBtns.forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            const rating = parseInt(btn.dataset.rating);
            highlightStars(rating, true);
        });
        btn.addEventListener('mouseleave', () => {
            // Revert to saved rating or clear if none
            fetch(`api/blog-interaction.php?action=get_status&post_id=${postId}`)
                .then(r => r.json())
                .then(data => {
                    highlightStars(data.user_rating || 0);
                });
        });
        btn.addEventListener('click', () => {
            const rating = parseInt(btn.dataset.rating);
            submitRating(rating);
        });
    });

    function highlightStars(count, isHover = false) {
        starBtns.forEach(btn => {
            const rating = parseInt(btn.dataset.rating);
            if (rating <= count) {
                btn.classList.add(isHover ? 'hover' : 'active');
            } else {
                btn.classList.remove(isHover ? 'hover' : 'active');
            }
        });
    }

    function submitRating(rating) {
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('rating', rating);

        fetch('api/blog-interaction.php?action=rate', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    ratingAvg.textContent = data.new_avg;
                    ratingCount.textContent = data.new_count;
                    highlightStars(rating);
                    // alert('Cảm ơn bạn đã đánh giá!');
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            });
    }

    // Handle Like
    likeBtn.addEventListener('click', () => {
        const formData = new FormData();
        formData.append('post_id', postId);

        fetch('api/blog-interaction.php?action=like', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.liked) {
                        likeBtn.classList.add('liked');
                    } else {
                        likeBtn.classList.remove('liked');
                    }
                    likesCount.textContent = data.new_count;
                } else {
                    if (data.message === 'Login required') {
                        window.location.href = 'auth/login.php?redirect=' + encodeURIComponent(window.location.href);
                    }
                }
            });
    });

    // Handle Share
    document.querySelectorAll('.share-btn-icon').forEach(btn => {
        btn.addEventListener('click', () => {
            const platform = btn.dataset.platform;
            const url = window.location.href;
            const title = document.title;
            let shareUrl = '';

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
                    break;
                case 'copy_link':
                    navigator.clipboard.writeText(url).then(() => {
                        alert('Đã sao chép liên kết!');
                    });
                    return; // Don't open window for copy
            }

            if (shareUrl) {
                window.open(shareUrl, 'share', 'width=600,height=400');

                // Track share
                const formData = new FormData();
                formData.append('post_id', postId);
                formData.append('platform', platform);
                fetch('api/blog-interaction.php?action=share', {
                    method: 'POST',
                    body: formData
                }).then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            sharesCount.textContent = data.new_count;
                        }
                    });
            }
        });
    });
});

// Lightbox & Slider Functions
let currentLightboxIndex = 0;
let totalLightboxImages = 0;
let lightboxImages = [];

function initLightboxImages() {
    lightboxImages = [];
    // Collect all click-able images
    document.querySelectorAll('[onclick^="openLightbox"]').forEach(el => {
        const img = el.querySelector('img');
        if (img) lightboxImages.push(img.src);
    });
    totalLightboxImages = lightboxImages.length;
    document.getElementById('lightboxTotal').textContent = totalLightboxImages;
}

function openLightbox(index) {
    initLightboxImages();
    if (lightboxImages.length === 0) return;

    currentLightboxIndex = index;
    updateLightbox();
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}

function lightboxNav(dir) {
    currentLightboxIndex += dir;
    if (currentLightboxIndex >= totalLightboxImages) currentLightboxIndex = 0;
    if (currentLightboxIndex < 0) currentLightboxIndex = totalLightboxImages - 1;
    updateLightbox();
}

function updateLightbox() {
    // If index provided in onclick is relative to a gallery, we need to map it correctly.
    // Simplifying assumption: visual order matches DOM order of onclick elements.
    if (lightboxImages[currentLightboxIndex]) {
        document.getElementById('lightboxImg').src = lightboxImages[currentLightboxIndex];
        document.getElementById('lightboxCurrent').textContent = currentLightboxIndex + 1;
    }
}

// Slider Logic
function slideImage(dir) {
    const track = document.getElementById('sliderTrack');
    if (!track) return;
    const slides = track.querySelectorAll('.slider-slide');
    if (slides.length === 0) return;

    let currentIndex = parseInt(track.dataset.index || 0);
    currentIndex += dir;

    if (currentIndex >= slides.length) currentIndex = 0;
    if (currentIndex < 0) currentIndex = slides.length - 1;

    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    track.dataset.index = currentIndex;

    updateDots(currentIndex);
}

function goToSlide(index) {
    const track = document.getElementById('sliderTrack');
    if (!track) return;
    track.style.transform = `translateX(-${index * 100}%)`;
    track.dataset.index = index;
    updateDots(index);
}

function updateDots(index) {
    const dots = document.getElementById('sliderDots');
    if (!dots) return;
    dots.querySelectorAll('button').forEach((btn, i) => {
        if (i === index) btn.classList.add('bg-[#d4af37]');
        else btn.classList.remove('bg-[#d4af37]');
    });
}
