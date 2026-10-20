import ReactMarkdown from 'react-markdown';

const CONTENT_IMAGE_URL = /^\/backend\/content-images\/\d+(\?[^\s]*)?$/;

/**
 * Only images uploaded through the Votmee editor are rendered; external image URLs are dropped.
 */
function ContentImage({ src, alt }) {
    if (typeof src !== 'string' || !CONTENT_IMAGE_URL.test(src)) return null;

    const medium = `${src}${src.includes('?') ? '&' : '?'}size=medium`;

    return <img
        src={medium}
        srcSet={`${medium} 800w, ${src} 1600w`}
        sizes="(max-width: 860px) 100vw, 760px"
        alt={alt ?? ''}
        loading="lazy"
        decoding="async"
    />;
}

export default function ContentBody({ revision }) {
    if (!revision) return null;

    if (revision.body_format !== 'markdown') {
        return <p className="vob-prose">{revision.body}</p>;
    }

    return <div className="vob-prose vob-markdown">
        <ReactMarkdown components={{
            a: ({ children, href }) => <a href={href} target="_blank" rel="noopener noreferrer">{children}</a>,
            img: ContentImage,
        }}>{revision.body}</ReactMarkdown>
    </div>;
}
