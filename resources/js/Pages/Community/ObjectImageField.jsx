import { useEffect, useRef, useState } from 'react';
import { NavIcon } from './Shared';

const MAX_EDGE = 1600;
export const MAX_BYTES = 4 * 1024 * 1024;
export const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

const canvasToBlob = (canvas, type, quality) => new Promise((resolve) => canvas.toBlob(resolve, type, quality));

/**
 * Downscale and re-encode an image in the browser to keep uploads small; the server re-encodes it again.
 */
export async function optimizeImage(file, maxEdge = MAX_EDGE) {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });

    try {
        const scale = Math.min(1, maxEdge / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(bitmap.width * scale));
        canvas.height = Math.max(1, Math.round(bitmap.height * scale));
        const context = canvas.getContext('2d');
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        let blob = await canvasToBlob(canvas, 'image/webp', 0.9);
        if (!blob || blob.type !== 'image/webp') {
            context.globalCompositeOperation = 'destination-over';
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            blob = await canvasToBlob(canvas, 'image/jpeg', 0.92);
        }
        if (!blob || (scale === 1 && blob.size >= file.size)) return file;

        const extension = blob.type === 'image/webp' ? 'webp' : 'jpg';

        return new File([blob], `${file.name.replace(/\.[^.]+$/, '')}.${extension}`, { type: blob.type });
    } finally {
        bitmap.close();
    }
}

export default function ObjectImageField({ file, onChange, locale, currentUrl = null, compact = false }) {
    const isId = locale === 'id';
    const inputRef = useRef(null);
    const [previewUrl, setPreviewUrl] = useState(currentUrl);
    const [error, setError] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleSelect = async (event) => {
        const input = event.target;
        const selected = input.files?.[0] ?? null;
        setError('');

        if (!selected) {
            onChange(null);
            return;
        }

        const reject = (message) => {
            input.value = '';
            setError(message);
            onChange(null);
        };

        if (!ALLOWED_TYPES.includes(selected.type)) {
            reject(isId ? 'Format harus JPG, PNG, atau WebP.' : 'The image must be a JPG, PNG, or WebP file.');
            return;
        }

        setProcessing(true);
        try {
            const optimized = await optimizeImage(selected).catch(() => selected);
            if (optimized.size > MAX_BYTES) {
                reject(isId ? 'Ukuran gambar masih di atas 4 MB. Pilih gambar yang lebih kecil.' : 'The image is still larger than 4 MB. Choose a smaller image.');
                return;
            }
            onChange(optimized);
        } finally {
            setProcessing(false);
        }
    };

    useEffect(() => {
        if (!file) {
            setPreviewUrl(currentUrl);
            return;
        }

        const url = URL.createObjectURL(file);
        setPreviewUrl(url);
        return () => URL.revokeObjectURL(url);
    }, [file, currentUrl]);

    return <div className={`vob-image-field ${compact ? 'is-compact' : ''}`}>
        <label className="vob-image-field-label" htmlFor={compact ? 'object-image-suggestion' : 'object-image-input'}>
            {isId ? 'Gambar objek' : 'Object image'} <span>{isId ? 'opsional' : 'optional'}</span>
        </label>
        <div className="vob-image-field-body">
            <div className="vob-image-preview">
                {previewUrl ? <img src={previewUrl} alt={isId ? 'Pratinjau gambar objek' : 'Object image preview'} /> : <span aria-hidden="true">◎</span>}
            </div>
            <div className="vob-image-field-actions">
                <div className="vob-image-picker">
                    <input
                        id={compact ? 'object-image-suggestion' : 'object-image-input'}
                        ref={inputRef}
                        className="vob-image-input"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        disabled={processing}
                        onChange={handleSelect}
                    />
                    <label className="vob-image-pick-button" htmlFor={compact ? 'object-image-suggestion' : 'object-image-input'}>
                        <NavIcon name="camera" size={16} />
                        {processing ? (isId ? 'Memproses…' : 'Processing…') : file ? (isId ? 'Ganti gambar' : 'Change image') : (isId ? 'Pilih gambar' : 'Choose image')}
                    </label>
                    <span className="vob-image-filename" title={file?.name ?? undefined}>
                        {file?.name ?? (isId ? 'Belum ada berkas dipilih' : 'No file selected')}
                    </span>
                </div>
                <small>{isId ? 'JPG, PNG, atau WebP · maks. 4 MB · dikompres otomatis · ditinjau moderator sebelum tampil.' : 'JPG, PNG, or WebP · up to 4 MB · compressed automatically · reviewed before publication.'}</small>
                {error && <small role="alert" style={{ color: '#b42318' }}>{error}</small>}
                {file && <button type="button" className="vob-image-remove" onClick={() => { if (inputRef.current) inputRef.current.value = ''; onChange(null); }}>{isId ? 'Hapus pilihan' : 'Remove selection'}</button>}
            </div>
        </div>
    </div>;
}
