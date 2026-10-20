import { NavIcon } from './Shared';

export default function ArticleSources({ sources, locale }) {
    if (!sources?.length) return null;

    return (
        <div className="vob-sources">
            <span className="vob-sources-title">
                <NavIcon name="globe" size={13} />
                <strong>{locale === 'id' ? 'Sumber Rujukan' : 'Sources'}</strong>
            </span>
            <ul>
                {sources.map((url) => (
                    <li key={url}>
                        <a href={url} target="_blank" rel="noopener noreferrer">{url}</a>
                    </li>
                ))}
            </ul>
        </div>
    );
}
