"use strict";

var medianestGallery = {
    template: `
    <div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="pswp__bg"></div>
    <div class="pswp__scroll-wrap">
        <div class="pswp__container">
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
        </div>
        <div class="pswp__ui pswp__ui--hidden">
            <div class="pswp__top-bar">
                <div class="pswp__counter"></div>
                <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
                <button class="pswp__button pswp__button--share" title="Share"></button>
                <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
                <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
                <div class="pswp__preloader">
                    <div class="pswp__preloader__icn">
                      <div class="pswp__preloader__cut">
                        <div class="pswp__preloader__donut"></div>
                      </div>
                    </div>
                </div>
            </div>
            <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                <div class="pswp__share-tooltip"></div> 
            </div>
            <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
            </button>
            <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
            </button>
            <div class="pswp__caption">
                <div class="pswp__caption__center"></div>
            </div>
        </div>
    </div>
    </div>`,
    createGallery: function (gallerySelector) {
        if (!document.querySelectorAll(".pswp").length) {
            document.body.insertAdjacentHTML('beforeend', medianestGallery.template);
        }
        medianestGallery.initPhotoSwipeFromDOM(gallerySelector);
    },
    parseThumbnailElements: function (el) {
        var thumbElements = el.childNodes,
            numNodes = thumbElements.length,
            items = [],
            figureEl,
            figcaptionEl,
            liEl,
            linkEl,
            imgEl,
            size,
            item;

        for (var i = 0; i < numNodes; i++) {
            liEl = thumbElements[i];

            if (liEl.nodeType !== 1) {
                continue;
            }

            figureEl = liEl.querySelector("figure");
            if (!figureEl) continue;

            imgEl = figureEl.querySelector("img");
            linkEl = figureEl.querySelector("a");

            if (!linkEl) continue;

            figcaptionEl = figureEl.querySelector("figcaption");

            size = linkEl.getAttribute('data-size') ? linkEl.getAttribute('data-size').split('x') : [0, 0];

            var titleText = linkEl.getAttribute('data-title') || (imgEl ? imgEl.getAttribute('alt') : '');
            var captionText = figcaptionEl ? figcaptionEl.innerHTML : '';
            var fullTitle = titleText;

            if (captionText) {
                fullTitle = titleText + '<div class="medianest-gallery-caption">' + captionText + '</div>';
            }

            item = {
                src: linkEl.getAttribute("href"),
                w: parseInt(size[0], 10) || 0,
                h: parseInt(size[1], 10) || 0,
                title: fullTitle,
                msrc: imgEl ? imgEl.getAttribute("src") : '',
                el: figureEl
            };
            items.push(item);
        }
        return items;
    },
    openPhotoSwipe: function (index, galleryElement, disableAnimation, fromURL) {
        var pswpElement = document.querySelectorAll(".pswp")[0],
            gallery,
            options,
            items;

        items = medianestGallery.parseThumbnailElements(galleryElement);
        options = {
            galleryUID: galleryElement.getAttribute("data-pswp-uid"),
            getThumbBoundsFn: function (index) {
                var thumbnail = items[index].el.getElementsByTagName("img")[0],
                    pageYScroll = window.pageYOffset || document.documentElement.scrollTop,
                    rect = thumbnail.getBoundingClientRect();

                return { x: rect.left, y: rect.top + pageYScroll, w: rect.width };
            },
        };

        if (fromURL) {
            if (options.galleryPIDs) {
                for (var j = 0; j < items.length; j++) {
                    if (items[j].pid == index) {
                        options.index = j;
                        break;
                    }
                }
            } else {
                options.index = parseInt(index, 10) - 1;
            }
        } else {
            options.index = parseInt(index, 10);
        }

        if (isNaN(options.index)) {
            return;
        }

        if (disableAnimation) {
            options.showAnimationDuration = 0;
        }

        gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
        gallery.init();
    },
    onThumbnailsClick: function (e) {
        e = e || window.event;
        e.preventDefault ? e.preventDefault() : (e.returnValue = false);

        var eTarget = e.target || e.srcElement;
        var clickedListItem = eTarget.closest(".blocks-gallery-item");
        if (!clickedListItem) {
            return;
        }
        var clickedGallery = clickedListItem.parentNode,
            childNodes = clickedListItem.parentNode.childNodes,
            numChildNodes = childNodes.length,
            nodeIndex = 0,
            index;
        for (var i = 0; i < numChildNodes; i++) {
            if (childNodes[i].nodeType !== 1) {
                continue;
            }

            if (childNodes[i] === clickedListItem) {
                index = nodeIndex;
                break;
            }
            nodeIndex++;
        }

        if (index >= 0) {
            medianestGallery.openPhotoSwipe(index, clickedGallery);
        }
        return false;
    },
    initPhotoSwipeFromDOM: function (gallerySelector) {
        var galleryElements = document.querySelectorAll(gallerySelector);
        for (var i = 0, l = galleryElements.length; i < l; i++) {
            galleryElements[i].setAttribute("data-pswp-uid", i + 1);
            galleryElements[i].onclick = medianestGallery.onThumbnailsClick;
        }
    },
};

document.addEventListener("DOMContentLoaded", function () {
    medianestGallery.createGallery(".medianest-block-medianest-gallery.is-lightbox");
});
