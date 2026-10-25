import { Link, usePage } from '@inertiajs/react';
import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import { AvatarFrame } from './AvatarFrame';
import { api, path } from './Shared';

/** Same pattern as App\Services\MentionService::PATTERN. */
const MENTION_PATTERN = /(?<![\w@])@([A-Za-z0-9_-]{2,80})/gu;
const TYPING_PATTERN = /(?:^|[^\w@])@([A-Za-z0-9_-]{0,80})$/u;
const CANDIDATE_LIMIT = 6;

/** Comment text with each recorded mention shown as a link to the member's profile, by name. */
export function MentionText({ body, mentions = [], locale }) {
    if (!mentions.length) return body;

    const byHandle = new Map(mentions.map((mention) => [mention.username.toLowerCase(), mention]));
    const parts = [];
    let last = 0;

    for (const match of body.matchAll(MENTION_PATTERN)) {
        const handle = match[1].replace(/[-_]+$/, '');
        const mention = byHandle.get(handle.toLowerCase());
        if (!mention) continue;

        parts.push(body.slice(last, match.index));
        parts.push(<Link key={match.index} className="vm-mention" href={path(locale, `/profile/${mention.username}`)}>@{mention.name}</Link>);
        last = match.index + 1 + handle.length;
    }
    parts.push(body.slice(last));

    return parts.map((part, index) => <Fragment key={index}>{part}</Fragment>);
}

/**
 * Suggests members while an `@word` sits before the caret: people already in the
 * discussion first, then the user search. Picking one writes `@username `.
 */
function useMentionAutocomplete({ value, onChange, inputRef, participants = [] }) {
    const currentUser = usePage().props.auth?.user;
    const [query, setQuery] = useState(null);
    const [remote, setRemote] = useState([]);
    const [highlight, setHighlight] = useState(0);

    const detect = () => {
        const caret = inputRef.current?.selectionStart ?? value.length;
        const match = value.slice(0, caret).match(TYPING_PATTERN);
        setQuery(match ? { text: match[1], start: caret - match[1].length - 1, end: caret } : null);
        setHighlight(0);
    };

    useEffect(detect, [value]);

    useEffect(() => {
        if (!query || query.text.length < 2) {
            setRemote([]);

            return undefined;
        }
        let active = true;
        const timer = setTimeout(() => {
            api(`/backend/search?q=${encodeURIComponent(`@${query.text}`)}`)
                .then((result) => active && setRemote(result.users ?? []))
                .catch(() => active && setRemote([]));
        }, 250);

        return () => {
            active = false;
            clearTimeout(timer);
        };
    }, [query?.text]);

    const candidates = useMemo(() => {
        if (!query) return [];
        const needle = query.text.toLowerCase();
        const seen = new Set([currentUser?.username]);

        return [...participants, ...remote]
            .filter((person) => {
                if (!person?.username || seen.has(person.username)) return false;
                seen.add(person.username);

                return person.username.toLowerCase().includes(needle) || (person.name ?? '').toLowerCase().includes(needle);
            })
            .slice(0, CANDIDATE_LIMIT);
    }, [query, participants, remote, currentUser?.username]);

    const select = (person) => {
        const insert = `@${person.username} `;
        onChange(value.slice(0, query.start) + insert + value.slice(query.end));
        setQuery(null);
        const caret = query.start + insert.length;
        requestAnimationFrame(() => {
            inputRef.current?.focus();
            inputRef.current?.setSelectionRange(caret, caret);
        });
    };

    const open = candidates.length > 0;
    const onKeyDown = (event) => {
        if (!open) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            setHighlight((current) => (current + step + candidates.length) % candidates.length);
        } else if (event.key === 'Enter' || event.key === 'Tab') {
            event.preventDefault();
            select(candidates[highlight]);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            setQuery(null);
        }
    };

    return {
        open,
        candidates,
        highlight,
        select,
        inputProps: { onKeyDown, onSelect: detect, onBlur: () => setTimeout(() => setQuery(null), 150) },
    };
}

/** Drop-in for a comment `<input>`: same props, plus mention suggestions above it. */
export function MentionInput({ value, onValueChange, participants, locale, ...props }) {
    const inputRef = useRef(null);
    const autocomplete = useMentionAutocomplete({ value, onChange: onValueChange, inputRef, participants });

    return <>
        <input
            {...props}
            ref={inputRef}
            value={value}
            onChange={(event) => onValueChange(event.target.value)}
            role="combobox"
            aria-expanded={autocomplete.open}
            aria-autocomplete="list"
            {...autocomplete.inputProps}
        />
        <MentionMenu autocomplete={autocomplete} locale={locale} />
    </>;
}

function MentionMenu({ autocomplete, locale }) {
    if (!autocomplete.open) return null;

    return <ul className="vm-mention-menu" role="listbox" aria-label={locale === 'id' ? 'Sebut anggota' : 'Mention a member'}>
        {autocomplete.candidates.map((person, index) => <li
            key={person.username}
            role="option"
            aria-selected={index === autocomplete.highlight}
            className={index === autocomplete.highlight ? 'is-active' : ''}
            onMouseDown={(event) => {
                event.preventDefault();
                autocomplete.select(person);
            }}
        >
            <AvatarFrame frame={person.avatar_frame} locale={locale} size="sm">
                <span className="vm-mention-avatar" aria-hidden="true">
                    {person.avatar_url ? <img src={`${person.avatar_url}&size=small`} alt="" /> : (person.name ?? '?').slice(0, 1).toUpperCase()}
                </span>
            </AvatarFrame>
            <span className="vm-mention-copy"><strong>{person.name}</strong><span>@{person.username}</span></span>
        </li>)}
    </ul>;
}
