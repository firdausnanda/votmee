import { useEffect, useState } from 'react';
import { NavIcon, SocialIcon } from './Shared';

export default function ShareModal({
    isOpen,
    onClose,
    locale = 'id',
    title,
    description,
    preview,
    shareUrl,
    shareText,
    copyLabel,
    onCopy,
}) {
    const isId = locale === 'id';
    const [copied, setCopied] = useState(false);
    const [toastMessage, setToastMessage] = useState('');

    const modalTitle = title || (isId ? 'Bagikan' : 'Share');
    const modalDescription = description || (isId
        ? 'Bagikan tautan ini ke teman atau media sosial'
        : 'Share this link with friends or social media');

    const url = shareUrl || (typeof window !== 'undefined' ? window.location.href : '');
    const text = shareText || (isId
        ? `Lihat ${preview?.title ?? ''} di Votmee!`
        : `Check out ${preview?.title ?? ''} on Votmee!`);

    const canNativeShare = typeof navigator !== 'undefined' && Boolean(navigator.share);

    useEffect(() => {
        if (!isOpen) return;
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') onClose();
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    const copyToClipboard = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            const msg = isId ? 'Tautan berhasil disalin!' : 'Link copied to clipboard!';
            setToastMessage(msg);
            if (onCopy) onCopy(url);
            setTimeout(() => setCopied(false), 2400);
            setTimeout(() => setToastMessage(''), 3000);
        } catch {
            setToastMessage(url);
            setTimeout(() => setToastMessage(''), 3000);
        }
    };

    const handleNativeShare = async () => {
        if (navigator.share) {
            try {
                await navigator.share({
                    title: preview?.title ? `${preview.title} - Votmee` : 'Votmee',
                    text: text,
                    url: url,
                });
            } catch (err) {
                if (err?.name !== 'AbortError') {
                    copyToClipboard();
                }
            }
        }
    };

    return (
        <>
            <div className="vpr-share-overlay" role="dialog" aria-modal="true" aria-labelledby="vpr-share-modal-title">
                <div className="vpr-share-backdrop" onClick={onClose} aria-hidden="true" />
                <div className="vpr-share-dialog">
                    <div className="vpr-share-header">
                        <div className="vpr-share-title-wrap">
                            <h3 id="vpr-share-modal-title">{modalTitle}</h3>
                            <p>{modalDescription}</p>
                        </div>
                        <button
                            type="button"
                            className="vpr-share-close"
                            onClick={onClose}
                            aria-label={isId ? 'Tutup' : 'Close'}
                        >
                            ✕
                        </button>
                    </div>

                    <div className="vpr-share-body">
                        {preview && (
                            <div className="vpr-share-preview-card">
                                {preview.avatar && (
                                    <div className="vpr-share-preview-avatar" aria-hidden="true">
                                        {preview.avatar}
                                    </div>
                                )}
                                <div className="vpr-share-preview-info">
                                    {preview.title && (
                                        <h4 className="vpr-share-preview-name">{preview.title}</h4>
                                    )}
                                    {preview.subtitle && (
                                        <p className="vpr-share-preview-handle">{preview.subtitle}</p>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="vpr-share-channels">
                            <a
                                href={`https://api.whatsapp.com/send?text=${encodeURIComponent(`${text} ${url}`)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="vpr-channel-btn vpr-channel-whatsapp"
                                aria-label="WhatsApp"
                            >
                                <div className="vpr-channel-icon-wrap">
                                    <SocialIcon name="whatsapp" size={26} />
                                </div>
                                <span className="vpr-channel-label">WhatsApp</span>
                            </a>

                            <a
                                href={`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="vpr-channel-btn vpr-channel-x"
                                aria-label="X (Twitter)"
                            >
                                <div className="vpr-channel-icon-wrap">
                                    <SocialIcon name="x" size={22} />
                                </div>
                                <span className="vpr-channel-label">X</span>
                            </a>

                            <a
                                href={`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="vpr-channel-btn vpr-channel-telegram"
                                aria-label="Telegram"
                            >
                                <div className="vpr-channel-icon-wrap">
                                    <SocialIcon name="telegram" size={26} />
                                </div>
                                <span className="vpr-channel-label">Telegram</span>
                            </a>

                            <a
                                href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="vpr-channel-btn vpr-channel-facebook"
                                aria-label="Facebook"
                            >
                                <div className="vpr-channel-icon-wrap">
                                    <SocialIcon name="facebook" size={26} />
                                </div>
                                <span className="vpr-channel-label">Facebook</span>
                            </a>
                        </div>

                        <div className="vpr-share-copy-section">
                            <label htmlFor="vpr-share-url-input" className="vpr-share-copy-label">
                                {copyLabel || (isId ? 'Salin Tautan' : 'Copy Link')}
                            </label>
                            <div className="vpr-share-copy-box">
                                <input
                                    id="vpr-share-url-input"
                                    type="text"
                                    readOnly
                                    value={url}
                                    className="vpr-share-copy-input"
                                    onFocus={(e) => e.target.select()}
                                />
                                <button
                                    type="button"
                                    onClick={copyToClipboard}
                                    className={`vpr-share-copy-btn ${copied ? 'copied' : ''}`}
                                >
                                    <NavIcon name={copied ? 'check' : 'share'} size={13} />
                                    {copied ? (isId ? 'Tersalin!' : 'Copied!') : (isId ? 'Salin' : 'Copy')}
                                </button>
                            </div>
                        </div>

                        {canNativeShare && (
                            <button
                                type="button"
                                onClick={handleNativeShare}
                                className="vpr-share-native-btn"
                            >
                                <NavIcon name="share" size={13} />
                                {isId ? 'Buka Menu Berbagi Perangkat' : 'Open Device Share Menu'}
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {toastMessage && (
                <div className="vr-toast vr-toast-success" role="status">
                    <span>{toastMessage}</span>
                </div>
            )}
        </>
    );
}
