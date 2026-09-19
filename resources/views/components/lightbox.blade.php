<div id="app-lightbox" class="lightbox hidden" aria-hidden="true">
    <div class="lightbox__backdrop" data-lightbox-close></div>
    <div class="lightbox__dialog" role="dialog" aria-modal="true" aria-label="Pratinjau gambar">
        <button type="button" class="lightbox__close" data-lightbox-close aria-label="Tutup">
            <i class="ti ti-x"></i>
        </button>

        <button type="button" class="lightbox__nav lightbox__nav--prev hidden" data-lightbox-prev aria-label="Sebelumnya">
            <i class="ti ti-chevron-left"></i>
        </button>

        <figure class="lightbox__figure">
            <img id="app-lightbox-image" class="lightbox__image" src="" alt="">
            <figcaption id="app-lightbox-caption" class="lightbox__caption"></figcaption>
        </figure>

        <button type="button" class="lightbox__nav lightbox__nav--next hidden" data-lightbox-next aria-label="Selanjutnya">
            <i class="ti ti-chevron-right"></i>
        </button>
    </div>
</div>
