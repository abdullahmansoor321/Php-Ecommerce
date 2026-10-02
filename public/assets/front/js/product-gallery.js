(function () {
    const mainImage = document.getElementById('product-zoom');
    const gallery = document.getElementById('product-zoom-gallery');
    const viewButton = document.getElementById('btn-product-gallery');

    if (!mainImage || !gallery) return;

    function thumbnails() {
        return Array.from(gallery.querySelectorAll('.product-gallery-item'));
    }

    function selectThumbnail(thumbnail) {
        const imageUrl = thumbnail.dataset.image;
        if (!imageUrl) return;

        mainImage.src = imageUrl;
        mainImage.dataset.zoomImage = thumbnail.dataset.zoomImage || imageUrl;
        const thumbnailImage = thumbnail.querySelector('img');
        if (thumbnailImage) mainImage.alt = thumbnailImage.alt;

        thumbnails().forEach(function (item) {
            const isSelected = item === thumbnail;
            item.classList.toggle('active', isSelected);
            if (isSelected) {
                item.setAttribute('aria-current', 'true');
            } else {
                item.removeAttribute('aria-current');
            }
        });
    }

    function openGallery(event) {
        event.preventDefault();
        const items = thumbnails().map(function (thumbnail) {
            const image = thumbnail.querySelector('img');
            return {
                src: thumbnail.dataset.image,
                title: image ? image.alt : ''
            };
        }).filter(function (item) {
            return Boolean(item.src);
        });

        if (items.length === 0) return;

        const activeIndex = Math.max(0, thumbnails().findIndex(function (thumbnail) {
            return thumbnail.classList.contains('active');
        }));

        if (window.jQuery && window.jQuery.magnificPopup) {
            window.jQuery.magnificPopup.open({
                items: items,
                type: 'image',
                gallery: { enabled: true },
                fixedContentPos: false,
                removalDelay: 200,
                closeBtnInside: false
            }, activeIndex);
            return;
        }

        window.open(items[activeIndex].src, '_blank', 'noopener,noreferrer');
    }

    gallery.addEventListener('click', function (event) {
        const thumbnail = event.target.closest('.product-gallery-item');
        if (!thumbnail || !gallery.contains(thumbnail)) return;

        event.preventDefault();
        selectThumbnail(thumbnail);
    });

    mainImage.addEventListener('click', openGallery);
    if (viewButton) viewButton.addEventListener('click', openGallery);
}());
