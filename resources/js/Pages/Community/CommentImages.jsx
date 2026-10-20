import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@headlessui/react';
import { useEffect, useRef, useState } from 'react';
import { ALLOWED_TYPES, MAX_BYTES, optimizeImage } from './ObjectImageField';
import { NavIcon } from './Shared';

export const MAX_COMMENT_IMAGES = 4;

/**
 * Build the request body for a comment: multipart only when images are attached.
 */
export function commentPayload(fields, images = []) {
    if (!images.length) return fields;

    const payload = new FormData();
    Object.entries(fields).forEach(([key, value]) => {
        if (value !== null && value !== undefined) payload.append(key, value);
    });
    images.forEach((image) => payload.append('images[]', image.file));

    return payload;
}

/**
 * Selected comment images with their browser-side compression and object URLs for previews.
 */
export function useCommentImages(locale) {
    const isId = locale === 'id';
    const [images, setImages] = useState([]);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');
    const imagesRef = useRef(images);
    imagesRef.current = images;

    useEffect(() => () => imagesRef.current.forEach((image) => URL.revokeObjectURL(image.previewUrl)), []);

    const add = async (fileList) => {
        setError('');
        const selected = Array.from(fileList ?? []);
        const room = MAX_COMMENT_IMAGES - imagesRef.current.length;
        const accepted = selected.filter((file) => ALLOWED_TYPES.includes(file.type));

        if (accepted.length < selected.length) {
            setError(isId ? 'Hanya gambar JPG, PNG, atau WebP yang bisa dilampirkan.' : 'Only JPG, PNG, or WebP images can be attached.');
        }
        if (accepted.length > room) {
            setError(isId ? `Maksimal ${MAX_COMMENT_IMAGES} gambar per komentar.` : `Up to ${MAX_COMMENT_IMAGES} images per comment.`);
        }

        const files = accepted.slice(0, Math.max(0, room));
        if (!files.length) return;

        setProcessing(true);
        try {
            const optimized = await Promise.all(files.map((file) => optimizeImage(file).catch(() => file)));
            const fitting = optimized.filter((file) => file.size <= MAX_BYTES);
            if (fitting.length < optimized.length) {
                setError(isId ? 'Gambar di atas 4 MB dilewati.' : 'Images larger than 4 MB were skipped.');
            }
            setImages((current) => [
                ...current,
                ...fitting.map((file) => ({ id: `${Date.now()}-${Math.random()}`, file, previewUrl: URL.createObjectURL(file) })),
            ].slice(0, MAX_COMMENT_IMAGES));
        } finally {
            setProcessing(false);
        }
    };

    const remove = (id) => setImages((current) => current.filter((image) => {
        if (image.id === id) URL.revokeObjectURL(image.previewUrl);
        return image.id !== id;
    }));

    const clear = () => {
        imagesRef.current.forEach((image) => URL.revokeObjectURL(image.previewUrl));
        setImages([]);
        setError('');
    };

    return { images, processing, error, add, remove, clear };
}

export function CommentImageAttachButton({ attachments, locale, disabled = false }) {
    const isId = locale === 'id';
    const inputRef = useRef(null);
    const isFull = attachments.images.length >= MAX_COMMENT_IMAGES;
    const label = isFull
        ? (isId ? `Maksimal ${MAX_COMMENT_IMAGES} gambar` : `Up to ${MAX_COMMENT_IMAGES} images`)
        : (isId ? 'Lampirkan gambar' : 'Attach images');

    return <>
        <input
            ref={inputRef}
            type="file"
            accept={ALLOWED_TYPES.join(',')}
            multiple
            hidden
            onChange={(event) => {
                attachments.add(event.target.files);
                event.target.value = '';
            }}
        />
        <button
            type="button"
            className="vr-chat-attach-btn"
            onClick={() => inputRef.current?.click()}
            disabled={disabled || isFull || attachments.processing}
            title={label}
            aria-label={label}
        >
            <NavIcon name="image" size={16} />
        </button>
    </>;
}

export function CommentImagePreviews({ attachments, locale }) {
    const isId = locale === 'id';
    if (!attachments.images.length && !attachments.processing && !attachments.error) return null;

    return <div className="vr-chat-attachments">
        {attachments.images.length > 0 && <ul className="vr-chat-attachment-list" aria-label={isId ? 'Gambar terlampir' : 'Attached images'}>
            {attachments.images.map((image, index) => <li key={image.id} className="vr-chat-attachment">
                <img src={image.previewUrl} alt={isId ? `Lampiran ${index + 1}` : `Attachment ${index + 1}`} />
                <button
                    type="button"
                    onClick={() => attachments.remove(image.id)}
                    aria-label={isId ? `Hapus lampiran ${index + 1}` : `Remove attachment ${index + 1}`}
                >
                    <NavIcon name="x" size={12} />
                </button>
            </li>)}
        </ul>}
        {attachments.processing && <span className="vr-chat-attachment-status">{isId ? 'Memproses gambar…' : 'Processing images…'}</span>}
        {attachments.error && <span className="vr-chat-attachment-error" role="alert">{attachments.error}</span>}
    </div>;
}

/**
 * Thumbnails of a comment's images with a lightbox for the full-size versions.
 */
export function CommentImageGallery({ images = [], locale, authorName }) {
    const isId = locale === 'id';
    const [openIndex, setOpenIndex] = useState(null);
    const [lastIndex, setLastIndex] = useState(0);

    // Warm the browser cache with the neighbouring full-size images so the arrow keys switch instantly.
    useEffect(() => {
        if (openIndex === null || images.length < 2) return;

        new Set([1, -1].map((delta) => (openIndex + delta + images.length) % images.length)).forEach((index) => {
            const preload = new window.Image();
            preload.decoding = 'async';
            preload.src = images[index].url;
        });
    }, [openIndex, images]);

    if (!images.length) return null;

    const shownIndex = openIndex ?? lastIndex;
    const shown = images[shownIndex];
    const open = (index) => {
        setLastIndex(index);
        setOpenIndex(index);
    };
    const step = (delta) => open((shownIndex + delta + images.length) % images.length);

    return <>
        <div className={`vr-comment-gallery is-count-${Math.min(images.length, MAX_COMMENT_IMAGES)}`}>
            {images.map((image, index) => <button
                key={image.id}
                type="button"
                className="vr-comment-gallery-item"
                onClick={() => open(index)}
                aria-label={isId ? `Buka gambar ${index + 1} dari ${images.length}` : `Open image ${index + 1} of ${images.length}`}
            >
                <img src={image.thumb_url} width={image.width} height={image.height} alt="" loading="lazy" decoding="async" />
            </button>)}
        </div>

        <Dialog
            open={openIndex !== null}
            onClose={() => setOpenIndex(null)}
            className="vr-lightbox"
            onKeyDown={(event) => {
                if (images.length > 1 && event.key === 'ArrowRight') step(1);
                if (images.length > 1 && event.key === 'ArrowLeft') step(-1);
            }}
        >
            <DialogBackdrop transition className="vr-lightbox-backdrop" />
            <div className="vr-lightbox-positioner">
                <DialogPanel transition className="vr-lightbox-panel">
                    <DialogTitle className="vm-sr-only">
                        {isId ? `Gambar ${shownIndex + 1} dari ${images.length} oleh ${authorName}` : `Image ${shownIndex + 1} of ${images.length} by ${authorName}`}
                    </DialogTitle>
                    <img
                        key={shown.id}
                        src={shown.url}
                        width={shown.width}
                        height={shown.height}
                        alt={isId ? `Gambar ${shownIndex + 1} dari komentar ${authorName}` : `Image ${shownIndex + 1} from ${authorName}'s comment`}
                    />
                    <button type="button" className="vr-lightbox-close" onClick={() => setOpenIndex(null)} aria-label={isId ? 'Tutup' : 'Close'}>
                        <NavIcon name="x" size={18} />
                    </button>
                    {images.length > 1 && <>
                        <button type="button" className="vr-lightbox-nav is-prev" onClick={() => step(-1)} aria-label={isId ? 'Gambar sebelumnya' : 'Previous image'}>
                            <NavIcon name="arrowLeft" size={18} />
                        </button>
                        <button type="button" className="vr-lightbox-nav is-next" onClick={() => step(1)} aria-label={isId ? 'Gambar berikutnya' : 'Next image'}>
                            <NavIcon name="arrowRight" size={18} />
                        </button>
                        <span className="vr-lightbox-counter">{shownIndex + 1} / {images.length}</span>
                    </>}
                </DialogPanel>
            </div>
        </Dialog>
    </>;
}
