import ReactMarkdown from 'react-markdown';

export default function ContentBody({ revision }) {
    if (!revision) return null;

    if (revision.body_format !== 'markdown') {
        return <p className="vob-prose">{revision.body}</p>;
    }

    return <div className="vob-prose vob-markdown">
        <ReactMarkdown components={{
            a: ({ children, href }) => <a href={href} target="_blank" rel="noopener noreferrer">{children}</a>,
        }}>{revision.body}</ReactMarkdown>
    </div>;
}
