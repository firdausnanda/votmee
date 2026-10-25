import { useId } from 'react';

const BOARD_COLORS = {
    influencer: '#d97706',
    ranker: '#0d9488',
    curator: '#7c3aed',
    writer: '#1044bb',
    voice: '#db2777',
};

const COPY = {
    id: {
        gold: 'Bingkai Emas',
        legend: 'Bingkai Legenda',
        veteran: 'Bingkai Veteran Juara',
        champion: 'Bingkai Juara Bertahan',
    },
    en: {
        gold: 'Gold frame',
        legend: 'Legend frame',
        veteran: 'Champion veteran frame',
        champion: 'Reigning champion frame',
    },
};

/** A point on a circle around the avatar, in the 100 × 100 frame box; 0° is right and 90° is down. */
const polar = (degrees, radius) => {
    const radians = (degrees * Math.PI) / 180;

    return [50 + radius * Math.cos(radians), 50 + radius * Math.sin(radians)];
};

const round = (value) => Math.round(value * 100) / 100;

/** A four-sided gem or stud centred on a point. */
const diamond = ([x, y], size) => `M${round(x)} ${round(y - size)}L${round(x + size)} ${round(y)}L${round(x)} ${round(y + size)}L${round(x - size)} ${round(y)}Z`;

/** A four-point sparkle centred on a point. */
const sparkle = ([x, y], size) => {
    const waist = size * 0.28;

    return `M${round(x)} ${round(y - size)}L${round(x + waist)} ${round(y - waist)}L${round(x + size)} ${round(y)}L${round(x + waist)} ${round(y + waist)}`
        + `L${round(x)} ${round(y + size)}L${round(x - waist)} ${round(y + waist)}L${round(x - size)} ${round(y)}L${round(x - waist)} ${round(y - waist)}Z`;
};

/** The ring itself: a circle, or a rounded square for square avatars. */
function Ring({ shape, inset = 0, ...props }) {
    return shape === 'rounded'
        ? <rect x={9 - inset} y={9 - inset} width={82 + inset * 2} height={82 + inset * 2} rx={20 + inset} {...props} />
        : <circle cx="50" cy="50" r={41 + inset} {...props} />;
}

/** Thin metal edges on both sides of the ring. */
function Rims({ shape, color, width = 1.1 }) {
    return <>
        <Ring shape={shape} inset={3.4} fill="none" stroke={color} strokeWidth={width} />
        <Ring shape={shape} inset={-3.3} fill="none" stroke={color} strokeWidth={width} />
    </>;
}

function GoldDefs({ id }) {
    return <linearGradient id={`${id}-gold`} x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stopColor="#fff7d1" />
        <stop offset="0.22" stopColor="#f5c542" />
        <stop offset="0.48" stopColor="#a86b06" />
        <stop offset="0.7" stopColor="#ffe08a" />
        <stop offset="1" stopColor="#8a5300" />
    </linearGradient>;
}

function GoldArt({ id, shape }) {
    return <>
        <defs>
            <GoldDefs id={id} />
            <linearGradient id={`${id}-amber`} x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stopColor="#fff7ed" />
                <stop offset="0.4" stopColor="#f97316" />
                <stop offset="1" stopColor="#7c2d12" />
            </linearGradient>
        </defs>
        <Ring shape={shape} fill="none" stroke={`url(#${id}-gold)`} strokeWidth="6" />
        <Rims shape={shape} color="#6b3f00" />
        <Ring shape={shape} inset={-2.3} fill="none" stroke="#fff6cf" strokeWidth="0.7" strokeOpacity="0.8" />
        {[0, 90, 180].map((angle) => <g key={angle}>
            <path d={diamond(polar(angle, 41), 4.2)} fill={`url(#${id}-gold)`} stroke="#5c3700" strokeWidth="0.8" />
            <path d={diamond(polar(angle, 41), 1.6)} fill="#fffbeb" />
        </g>)}
        <path d={diamond(polar(-90, 41), 6)} fill={`url(#${id}-amber)`} stroke="#5c3700" strokeWidth="0.9" />
        <path d={diamond(polar(-90, 41), 2.2)} fill="#fff" fillOpacity="0.7" />
    </>;
}

function LegendArt({ id, shape }) {
    return <>
        <defs>
            <GoldDefs id={id} />
            <linearGradient id={`${id}-holo`} x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stopColor="#f472b6" />
                <stop offset="0.25" stopColor="#a78bfa" />
                <stop offset="0.5" stopColor="#60a5fa" />
                <stop offset="0.75" stopColor="#34d399" />
                <stop offset="1" stopColor="#fbbf24" />
            </linearGradient>
            <linearGradient id={`${id}-amethyst`} x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stopColor="#f5d0fe" />
                <stop offset="0.45" stopColor="#a855f7" />
                <stop offset="1" stopColor="#4c1d95" />
            </linearGradient>
        </defs>
        <g className="vaf-holo">
            <Ring shape={shape} fill="none" stroke={`url(#${id}-holo)`} strokeWidth="6" />
        </g>
        <Rims shape={shape} color={`url(#${id}-gold)`} width={1.4} />
        <Ring shape={shape} fill="none" stroke="#fff" strokeOpacity="0.45" strokeWidth="1" strokeDasharray="2 5" />
        {[-45, 45, 135, 225].map((angle) => <path key={angle} d={sparkle(polar(angle, 41), 4.6)} fill="#fff" stroke="#c4b5fd" strokeWidth="0.5" />)}
        <path d={diamond(polar(-90, 41), 6.2)} fill={`url(#${id}-amethyst)`} stroke="#3b0764" strokeWidth="0.9" />
        <path d={`M50 ${round(polar(-90, 41)[1] - 6.2)}L${round(50 + 6.2)} ${round(polar(-90, 41)[1])}L50 ${round(polar(-90, 41)[1])}Z`} fill="#fff" fillOpacity="0.45" />
    </>;
}

/** Soft light beams fanning out behind the king frame, starting outside the avatar. */
const GOD_RAYS = Array.from({ length: 10 }, (_, index) => {
    const angle = index * 36 - 72;
    const [x1, y1] = polar(angle - 2.5, 44);
    const [x2, y2] = polar(angle + 2.5, 44);
    const [x3, y3] = polar(angle + 7, 68);
    const [x4, y4] = polar(angle - 7, 68);

    return `M${round(x1)} ${round(y1)}L${round(x4)} ${round(y4)}L${round(x3)} ${round(y3)}L${round(x2)} ${round(y2)}Z`;
});

/** Black ermine tails dotted along the king's fur collar. */
const ERMINE_SPOTS = Array.from({ length: 8 }, (_, index) => {
    const angle = 38 + index * 14.8;
    const [x, y] = polar(angle, 47.2);

    return { x: round(x), y: round(y), rotate: round(angle - 90) };
});

/** Embers drifting up from below the frame while it animates. */
const EMBERS = [[36, 0], [44, 0.9], [52, 1.7], [60, 0.4], [66, 2.2]];

/** Laurel nodes climbing both sides of the emperor's wreath, each with an outer and inner leaf. */
const WREATH_NODES = Array.from({ length: 12 }, (_, index) => {
    const angle = 100 + index * 12.6;
    const [outerX, outerY] = polar(angle, 50.5);
    const [innerX, innerY] = polar(angle + 4, 46.2);

    return {
        angle,
        outer: { x: round(outerX), y: round(outerY), rotate: round(angle + 52) },
        inner: { x: round(innerX), y: round(innerY), rotate: round(angle + 128) },
    };
});

const WREATH_STEM = (() => {
    const [x1, y1] = polar(98, 48.2);
    const [x2, y2] = polar(250, 48.2);

    return `M${round(x1)} ${round(y1)}A48.2 48.2 0 0 1 ${round(x2)} ${round(y2)}`;
})();

const ROMAN_NUMERALS = [[1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'], [100, 'C'], [90, 'XC'], [50, 'L'], [40, 'XL'], [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I']];

/** 4 as "IV", for the emperor's medallion. */
const toRoman = (value) => ROMAN_NUMERALS.reduce((numeral, [amount, letters]) => {
    const times = Math.floor(value / amount);
    value -= times * amount;

    return numeral + letters.repeat(times);
}, '');

/** A fleur-de-lis pointing up, about 14 units tall, centred on the origin. */
const FLEUR_DE_LIS = 'M0-7C2.2-4.2 2.8-1 0 2.6-2.8-1-2.2-4.2 0-7Z'
    + 'M-1.2 1.2C-3.4-1.6-6.2-1-6.4 1.6-6.5 3.6-4.6 4.6-3.2 3.6-4.2 3-4.4 1.6-3.3 1.4-2.4 1.3-1.7 2.2-1.2 3Z'
    + 'M1.2 1.2C3.4-1.6 6.2-1 6.4 1.6 6.5 3.6 4.6 4.6 3.2 3.6 4.2 3 4.4 1.6 3.3 1.4 2.4 1.3 1.7 2.2 1.2 3Z'
    + 'M-3.4 2.9H3.4V4.6H-3.4ZM-1.1 4.6H1.1L0 7.6Z';

/** Keeps decorations behind the frame from covering the avatar itself. */
function OutsideMask({ id, shape }) {
    return <mask id={`${id}-outside`} maskUnits="userSpaceOnUse" x="-40" y="-40" width="180" height="180">
        <rect x="-40" y="-40" width="180" height="180" fill="#fff" />
        <Ring shape={shape} inset={-1.5} fill="#000" />
    </mask>;
}

function Scepter({ id, from, to, head }) {
    return <g>
        <line x1={from[0]} y1={from[1]} x2={to[0]} y2={to[1]} stroke="#4a2a00" strokeWidth="4" strokeLinecap="round" />
        <line x1={from[0]} y1={from[1]} x2={to[0]} y2={to[1]} stroke={`url(#${id}-gold)`} strokeWidth="2.6" strokeLinecap="round" />
        <circle cx={from[0]} cy={from[1]} r="2.4" fill={`url(#${id}-gold)`} stroke="#4a2a00" strokeWidth="0.6" />
        {head === 'orb'
            ? <g>
                <circle cx={to[0]} cy={to[1]} r="4.6" fill={`url(#${id}-gold)`} stroke="#4a2a00" strokeWidth="0.7" />
                <path d={`M${to[0] - 4.4} ${to[1]}H${to[0] + 4.4}`} stroke="#7c4a03" strokeWidth="0.8" />
                <path d={`M${to[0]} ${to[1] - 4.6}V${to[1] - 10}M${to[0] - 2.4} ${to[1] - 7.8}H${to[0] + 2.4}`} stroke={`url(#${id}-gold)`} strokeWidth="1.6" strokeLinecap="round" />
                <circle cx={to[0] - 1.5} cy={to[1] - 1.6} r="1.2" fill="#fff" fillOpacity="0.6" />
            </g>
            : <path d={FLEUR_DE_LIS} transform={`translate(${to[0]} ${to[1] - 2}) scale(0.95)`} fill={`url(#${id}-gold)`} stroke="#4a2a00" strokeWidth="0.6" />}
    </g>;
}

function KingArt({ id, shape, animated }) {
    return <>
        <defs>
            <GoldDefs id={id} />
            <OutsideMask id={id} shape={shape} />
            <linearGradient id={`${id}-velvet`} x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" style={{ stopColor: 'color-mix(in srgb, var(--vaf-accent) 70%, #fff)' }} />
                <stop offset="0.45" style={{ stopColor: 'var(--vaf-accent)' }} />
                <stop offset="1" style={{ stopColor: 'color-mix(in srgb, var(--vaf-accent) 45%, #000)' }} />
            </linearGradient>
            <radialGradient id={`${id}-halo`} cx="0.5" cy="0.5" r="0.5">
                <stop offset="0.66" style={{ stopColor: 'var(--vaf-accent)', stopOpacity: 0 }} />
                <stop offset="0.74" style={{ stopColor: 'var(--vaf-accent)', stopOpacity: 0.5 }} />
                <stop offset="1" style={{ stopColor: 'var(--vaf-accent)', stopOpacity: 0 }} />
            </radialGradient>
            <radialGradient id={`${id}-beam`} gradientUnits="userSpaceOnUse" cx="50" cy="50" r="68">
                <stop offset="0.62" stopColor="#fff" stopOpacity="0.75" />
                <stop offset="1" stopColor="#fff" stopOpacity="0" />
            </radialGradient>
            <linearGradient id={`${id}-gem`} x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stopColor="#fff" />
                <stop offset="0.35" style={{ stopColor: 'var(--vaf-accent)' }} />
                <stop offset="1" style={{ stopColor: 'color-mix(in srgb, var(--vaf-accent) 45%, #000)' }} />
            </linearGradient>
        </defs>

        <circle cx="50" cy="50" r="58" fill={`url(#${id}-halo)`} />
        <g className="vaf-godrays">
            {GOD_RAYS.map((ray) => <path key={ray} d={ray} fill={`url(#${id}-beam)`} />)}
        </g>

        <g mask={`url(#${id}-outside)`}>
            <Scepter id={id} from={[14, 88]} to={[88, 14]} head="orb" />
            <Scepter id={id} from={[86, 88]} to={[12, 14]} head="fleur" />
            <path d="M16 58C10 70 6 83 3.5 96.5Q15 101 26.5 97Q38 101.5 50 97.5Q62 101.5 73.5 97Q85 101 96.5 96.5C94 83 90 70 84 58Z" fill={`url(#${id}-velvet)`} stroke="color-mix(in srgb, var(--vaf-accent) 40%, #000)" strokeWidth="0.6" />
            <path d="M22 70Q17 84 15 98M78 70Q83 84 85 98M35 81Q33.5 90 33 98.5M65 81Q66.5 90 67 98.5" fill="none" stroke="#000" strokeOpacity="0.22" strokeWidth="1.1" strokeLinecap="round" />
        </g>

        <Ring shape={shape} fill="none" stroke={`url(#${id}-gold)`} strokeWidth="6" />
        <Ring shape={shape} inset={-0.2} fill="none" stroke="#fffdf5" strokeWidth="1.7" strokeLinecap="round" pathLength="100" strokeDasharray="0.01 2.5" />
        <g className="vaf-sweep">
            <Ring shape={shape} fill="none" stroke="#fff" strokeOpacity="0.6" strokeWidth="5" strokeLinecap="round" pathLength="100" strokeDasharray="12 88" />
        </g>
        <Rims shape={shape} color="#5c3700" width={1.2} />

        <path d={`M${round(polar(30, 47.2)[0])} ${round(polar(30, 47.2)[1])}A47.2 47.2 0 0 1 ${round(polar(150, 47.2)[0])} ${round(polar(150, 47.2)[1])}`} fill="none" stroke="#475569" strokeWidth="7.6" strokeLinecap="round" />
        <path d={`M${round(polar(30, 47.2)[0])} ${round(polar(30, 47.2)[1])}A47.2 47.2 0 0 1 ${round(polar(150, 47.2)[0])} ${round(polar(150, 47.2)[1])}`} fill="none" stroke="#fffdf7" strokeWidth="6.4" strokeLinecap="round" />
        {ERMINE_SPOTS.map((spot) => <path key={spot.rotate} d="M0-1.9 1 .8 0 1.8-1 .8Z" transform={`translate(${spot.x} ${spot.y}) rotate(${spot.rotate})`} fill="#111827" />)}
        {[[3.5, 50, -90], [96.5, 50, 90]].map(([x, y, rotate]) => <path key={x} d={FLEUR_DE_LIS} transform={`translate(${x} ${y}) rotate(${rotate}) scale(0.82)`} fill={`url(#${id}-gold)`} stroke="#4a2a00" strokeWidth="0.6" />)}

        <path d={diamond(polar(90, 47.5), 5.4)} fill={`url(#${id}-gem)`} stroke="#3f2a00" strokeWidth="0.9" />
        <path d={diamond(polar(90, 47.5), 2)} fill="#fff" fillOpacity="0.7" />

        {animated && <g className="vaf-orbit">
            {[-20, 100, 220].map((angle) => <path key={angle} d={sparkle(polar(angle, 52), 3.4)} fill="#fff" />)}
        </g>}
        {animated && EMBERS.map(([x, delay]) => <circle key={x} className="vaf-ember" cx={x} cy="99" r="1.3" fill="#fde68a" style={{ animationDelay: `${delay}s` }} />)}
    </>;
}

function ImperialEagle({ id }) {
    return <g className="vaf-eagle"><g transform="translate(50 5) scale(1.45) translate(-50 -11)">
        <path d="M50 11C44 4 36 2 28 4.5c4 1.5 6.4 3.6 7.6 5.8-3.6.1-6.8 1.3-9.2 3.4 4.1-.2 7.6.2 10.4 1.2-2.6 1.4-4.4 3.6-5.3 6.2 5.6-2.4 10.6-4.6 14.4-6.6Z" fill={`url(#${id}-gold)`} stroke="#5c3700" strokeWidth="0.55" strokeLinejoin="round" />
        <path d="M50 11C56 4 64 2 72 4.5c-4 1.5-6.4 3.6-7.6 5.8 3.6.1 6.8 1.3 9.2 3.4-4.1-.2-7.6.2-10.4 1.2 2.6 1.4 4.4 3.6 5.3 6.2-5.6-2.4-10.6-4.6-14.4-6.6Z" fill={`url(#${id}-gold)`} stroke="#5c3700" strokeWidth="0.55" strokeLinejoin="round" />
        <path d="M36 8.5 44 11M33 13.5l8.5 1M36 18.5l7.4-3.2M64 8.5 56 11M67 13.5l-8.5 1M64 18.5l-7.4-3.2" stroke="#7c4a03" strokeWidth="0.55" strokeLinecap="round" />
        <path d="M46.8 9.6C46.8 5.4 53.2 5.4 53.2 9.6L52.2 17.2 50 20.4 47.8 17.2Z" fill={`url(#${id}-gold)`} stroke="#5c3700" strokeWidth="0.55" />
        <path d="M47.6 17.4 50 23.2 52.4 17.4Z" fill={`url(#${id}-gold)`} stroke="#5c3700" strokeWidth="0.5" />
        <circle cx="50" cy="4.6" r="2.7" fill={`url(#${id}-gold)`} stroke="#5c3700" strokeWidth="0.55" />
        <path d="M52.3 4.1 55.6 5.2 52.5 5.9Z" fill="#b45309" stroke="#5c3700" strokeWidth="0.35" />
        <circle cx="50.9" cy="4.1" r="0.55" fill="#1f1300" />
    </g></g>;
}

function EmperorArt({ id, shape, animated, titles }) {
    return <>
        <defs>
            <GoldDefs id={id} />
            <linearGradient id={`${id}-tyrian`} x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stopColor="#c084fc" />
                <stop offset="0.45" stopColor="#7e22ce" />
                <stop offset="1" stopColor="#3b0764" />
            </linearGradient>
            <linearGradient id={`${id}-leaf`} x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stopColor="#fef3c7" />
                <stop offset="0.5" stopColor="#eab308" />
                <stop offset="1" stopColor="#a16207" />
            </linearGradient>
            <radialGradient id={`${id}-aura`} cx="0.5" cy="0.5" r="0.5">
                <stop offset="0.66" stopColor="#a855f7" stopOpacity="0" />
                <stop offset="0.76" stopColor="#a855f7" stopOpacity="0.4" />
                <stop offset="1" stopColor="#a855f7" stopOpacity="0" />
            </radialGradient>
        </defs>

        <circle cx="50" cy="50" r="58" fill={`url(#${id}-aura)`} />

        {[false, true].map((mirrored) => <g key={String(mirrored)} transform={mirrored ? 'matrix(-1 0 0 1 100 0)' : undefined}>
            <path d={WREATH_STEM} fill="none" stroke="#a16207" strokeWidth="1.3" strokeLinecap="round" />
            {WREATH_NODES.map(({ angle, outer, inner }) => <g key={angle}>
                <ellipse cx={outer.x} cy={outer.y} rx="5.6" ry="2.3" transform={`rotate(${outer.rotate} ${outer.x} ${outer.y})`} fill={`url(#${id}-leaf)`} stroke="#854d0e" strokeWidth="0.4" />
                <ellipse cx={inner.x} cy={inner.y} rx="4.6" ry="1.9" transform={`rotate(${inner.rotate} ${inner.x} ${inner.y})`} fill={`url(#${id}-leaf)`} stroke="#854d0e" strokeWidth="0.4" />
            </g>)}
        </g>)}

        <Ring shape={shape} fill="none" stroke={`url(#${id}-tyrian)`} strokeWidth="6" />
        <Ring shape={shape} fill="none" stroke="#fde68a" strokeWidth="1.5" strokeLinecap="round" pathLength="100" strokeDasharray="0.01 3.2" />
        <g className="vaf-sweep">
            <Ring shape={shape} fill="none" stroke="#fff" strokeOpacity="0.5" strokeWidth="5" strokeLinecap="round" pathLength="100" strokeDasharray="12 88" />
        </g>
        <Rims shape={shape} color={`url(#${id}-gold)`} width={1.6} />

        {[0, 180].map((angle) => <g key={angle}>
            <path d={diamond(polar(angle, 41), 3.6)} fill={`url(#${id}-tyrian)`} stroke="#fde68a" strokeWidth="0.8" />
            <path d={diamond(polar(angle, 41), 1.2)} fill="#fff" fillOpacity="0.7" />
        </g>)}

        <g>
            <path d="M44.5 94 36 106l3.8.6 1.8-3.4 5.6-6.4Z" fill={`url(#${id}-tyrian)`} stroke="#fde68a" strokeWidth="0.6" strokeLinejoin="round" />
            <path d="M55.5 94 64 106l-3.8.6-1.8-3.4-5.6-6.4Z" fill={`url(#${id}-tyrian)`} stroke="#fde68a" strokeWidth="0.6" strokeLinejoin="round" />
            <circle cx="50" cy="92" r="8.8" fill={`url(#${id}-gold)`} stroke="#4a2a00" strokeWidth="0.9" />
            <circle cx="50" cy="92" r="6.9" fill="none" stroke="#fff6cf" strokeWidth="0.6" strokeDasharray="0.8 0.9" />
            <text x="50" y="92.4" textAnchor="middle" dominantBaseline="central" fontFamily="Georgia, 'Times New Roman', serif" fontWeight="700" fontSize={titles >= 10 ? 4.6 : 6.4} fill="#4c1d95">{toRoman(Math.max(1, titles))}</text>
        </g>

        <ImperialEagle id={id} />

        {animated && <g className="vaf-orbit">
            {[-20, 100, 220].map((angle) => <path key={angle} d={sparkle(polar(angle, 52), 3.2)} fill="#fde68a" />)}
        </g>}
    </>;
}

const FRAME_ART = { gold: GoldArt, legend: LegendArt, veteran: EmperorArt, champion: KingArt };

/** An id safe to use inside url(#…) references, unique per rendered frame. */
const useSvgId = () => `vaf${useId().replace(/[^a-zA-Z0-9]/g, '')}`;

function FrameArt({ type, detail, shape, animated }) {
    const id = useSvgId();
    const Art = FRAME_ART[type];

    if (!Art) return null;

    return <svg className="vaf-art" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
        <Art id={id} shape={shape} animated={animated} titles={Number(detail) || 0} />
    </svg>;
}

/** A royal crown with a velvet cap, arches and an orb, for reigning champions. */
function Crown({ className = 'vaf-crown' }) {
    const id = useSvgId();

    return <svg className={className} viewBox="0 0 32 30" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id={`${id}-gold`} x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stopColor="#fff4c2" />
                <stop offset="0.35" stopColor="#fbbf24" />
                <stop offset="0.7" stopColor="#d97706" />
                <stop offset="1" stopColor="#92400e" />
            </linearGradient>
            <linearGradient id={`${id}-band`} x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stopColor="#fcd34d" />
                <stop offset="1" stopColor="#a35a05" />
            </linearGradient>
            <radialGradient id={`${id}-velvet`} cx="0.4" cy="0.3" r="0.8">
                <stop offset="0" stopColor="#f87171" />
                <stop offset="0.55" stopColor="#b91c1c" />
                <stop offset="1" stopColor="#5f0a0a" />
            </radialGradient>
            <radialGradient id={`${id}-ruby`} cx="0.35" cy="0.35" r="0.75">
                <stop offset="0" stopColor="#fecdd3" />
                <stop offset="0.4" stopColor="#e11d48" />
                <stop offset="1" stopColor="#881337" />
            </radialGradient>
            <radialGradient id={`${id}-sapphire`} cx="0.35" cy="0.35" r="0.75">
                <stop offset="0" stopColor="#dbeafe" />
                <stop offset="0.45" stopColor="#2563eb" />
                <stop offset="1" stopColor="#1e3a8a" />
            </radialGradient>
        </defs>
        <path d="M6.5 21C6.5 12 10.5 8 16 8s9.5 4 9.5 13Z" fill={`url(#${id}-velvet)`} />
        <path d="M6.2 20.5C7 13 10.8 8.8 16 8.2M25.8 20.5C25 13 21.2 8.8 16 8.2M16 8.2V20" fill="none" stroke={`url(#${id}-gold)`} strokeWidth="1.5" strokeLinecap="round" />
        {[[9.3, 13.6], [11.6, 10.6], [20.4, 10.6], [22.7, 13.6], [16, 12.5], [16, 16.2]].map(([x, y]) => <circle key={`${x}-${y}`} cx={x} cy={y} r="0.75" fill="#fffbeb" />)}
        <circle cx="16" cy="6.1" r="2.3" fill={`url(#${id}-gold)`} stroke="#6b3a00" strokeWidth="0.5" />
        <path d="M16 3.9V.6M14.3 1.9h3.4" stroke={`url(#${id}-gold)`} strokeWidth="1.3" strokeLinecap="round" />
        <path d="M4 25 3 15.5l5 3.6 2.4-5.6 2.9 4.6L16 12.5l2.7 5.6 2.9-4.6 2.4 5.6 5-3.6L28 25Z" fill={`url(#${id}-gold)`} stroke="#6b3a00" strokeWidth="0.9" strokeLinejoin="round" />
        <path d="M5 23.2 4.3 17.5 8 20.2" fill="none" stroke="#fff" strokeOpacity="0.65" strokeWidth="0.8" strokeLinecap="round" strokeLinejoin="round" />
        {[[3, 15.5], [10.4, 13.5], [21.6, 13.5], [29, 15.5]].map(([x, y]) => <circle key={x} cx={x} cy={y} r="1.4" fill="#fffbeb" stroke="#b45309" strokeWidth="0.55" />)}
        <path d="M4 24.4h24l-.5 4.2q-11.5 2.2-23 0Z" fill={`url(#${id}-band)`} stroke="#6b3a00" strokeWidth="0.9" strokeLinejoin="round" />
        <circle cx="16" cy="26.6" r="1.9" fill={`url(#${id}-ruby)`} stroke="#6b3a00" strokeWidth="0.5" />
        <path d={diamond([9.6, 26.5], 1.5)} fill={`url(#${id}-sapphire)`} stroke="#1e1b4b" strokeWidth="0.4" />
        <path d={diamond([22.4, 26.5], 1.5)} fill={`url(#${id}-sapphire)`} stroke="#1e1b4b" strokeWidth="0.4" />
        <path className="vaf-glint" d={sparkle([26, 6], 2.6)} fill="#fff" />
    </svg>;
}

/** "champion-ranker" as { type: 'champion', board: 'ranker' }; a veteran's "board" is their title count. */
export function parseAvatarFrame(frame) {
    if (!frame) return null;

    const [type, board = null] = frame.split('-');

    return { type, board };
}

export function avatarFrameLabel(type, locale) {
    return COPY[locale === 'id' ? 'id' : 'en'][type] ?? type;
}

/**
 * Draws a member's earned frame around an avatar element they already render,
 * the same full frame on every page and at every size. Without a frame the
 * avatar is returned untouched. Square avatars pass shape="rounded".
 */
export function AvatarFrame({ frame, locale = 'id', size = 'md', shape = 'circle', animated = true, className = '', children }) {
    const parts = parseAvatarFrame(frame);

    if (!parts) return children;

    const classes = ['vaf', `vaf-${parts.type}`, `vaf-${size}`, shape === 'rounded' ? 'vaf-rounded' : '', animated ? 'is-animated' : '', className].filter(Boolean).join(' ');

    return <span className={classes} style={parts.type === 'champion' ? { '--vaf-accent': BOARD_COLORS[parts.board] } : undefined} title={avatarFrameLabel(parts.type, locale)}>
        {children}
        <FrameArt type={parts.type} detail={parts.board} shape={shape} animated={animated} />
        {parts.type === 'champion' && <Crown />}
    </span>;
}

const FLAIR_COPY = {
    id: {
        boards: { influencer: 'Pengaruh', ranker: 'Ranking', curator: 'Kurator', writer: 'Penulis', voice: 'Komentar' },
        champion: (board) => `Juara ${board} bertahan`,
        trusted: 'Kontributor tepercaya',
    },
    en: {
        boards: { influencer: 'Influence', ranker: 'Ranking', curator: 'Curator', writer: 'Writer', voice: 'Comment' },
        champion: (board) => `Reigning ${board} champion`,
        trusted: 'Trusted contributor',
    },
};

function TrustedIcon() {
    return <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false">
        <circle cx="8" cy="8" r="7.5" fill="#1044bb" />
        <path d="m4.8 8.2 2.1 2.1 4.3-4.6" fill="none" stroke="#fff" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
    </svg>;
}

/**
 * A member's name with the flair they earned: a gradient name and crown while
 * they reign as last month's champion, or a check mark for trusted contributors.
 */
export function NameFlair({ flair, locale = 'id', size = 'sm', className = '', children }) {
    const parts = parseAvatarFrame(flair);

    if (!parts) return children;

    const copy = FLAIR_COPY[locale === 'id' ? 'id' : 'en'];
    const label = parts.type === 'champion' ? copy.champion(copy.boards[parts.board] ?? parts.board) : copy.trusted;

    return <span className={['vnf', `vnf-${parts.type}`, `vnf-${size}`, className].filter(Boolean).join(' ')} style={parts.board ? { '--vnf-accent': BOARD_COLORS[parts.board] } : undefined}>
        <span className="vnf-label">{children}</span>
        <span className="vnf-icon" role="img" aria-label={label} title={label}>
            {parts.type === 'champion' ? <Crown /> : <TrustedIcon />}
        </span>
    </span>;
}

const FRAME_ORDER = ['champion', 'veteran', 'legend', 'gold'];

const PICKER_COPY = {
    id: {
        title: 'Bingkai avatar',
        intro: 'Bingkai tampil di profil, komentar, Hall of Fame, dan papan kreator.',
        auto: 'Otomatis',
        autoHint: 'Selalu pakai bingkai terlangka yang Anda miliki.',
        none: 'Tanpa bingkai',
        noneHint: 'Tampilkan foto tanpa bingkai.',
        hints: {
            champion: () => 'Juara salah satu papan Hall of Fame bulan lalu. Berlaku selama 1 bulan.',
            veteran: (requirements) => `Raih ${requirements.veteran_titles} gelar Juara Bulanan.`,
            legend: (requirements) => `Punya ${requirements.legend_gold_badges} badge tier Emas.`,
            gold: () => 'Punya 1 badge tier Emas.',
        },
        active: 'Dipakai',
        unlocked: 'Terbuka',
        locked: 'Terkunci',
    },
    en: {
        title: 'Avatar frame',
        intro: 'Frames show on your profile, comments, the Hall of Fame and the creators board.',
        auto: 'Automatic',
        autoHint: 'Always use the rarest frame you hold.',
        none: 'No frame',
        noneHint: 'Show your photo without a frame.',
        hints: {
            champion: () => 'Win any Hall of Fame board last month. Lasts one month.',
            veteran: (requirements) => `Earn ${requirements.veteran_titles} monthly champion titles.`,
            legend: (requirements) => `Hold ${requirements.legend_gold_badges} gold-tier badges.`,
            gold: () => 'Hold any gold-tier badge.',
        },
        active: 'In use',
        unlocked: 'Unlocked',
        locked: 'Locked',
    },
};

/** The frame a stored pick resolves to, as the server does: "auto" when nothing held was picked. */
const selectedOption = ({ preferred, held }) => {
    if (preferred === 'none') return 'none';

    return held.includes(preferred) ? preferred : 'auto';
};

const displayFrame = (type, { board, titles, requirements }) => {
    if (type === 'champion') return `champion-${board ?? 'influencer'}`;
    if (type === 'veteran') return `veteran-${Math.max(titles ?? 0, requirements.veteran_titles)}`;

    return type;
};

/** Lets members pick which of their earned frames surrounds their avatar. */
export function AvatarFramePicker({ user, options, locale, busy = false, error = '', onChoose }) {
    const copy = PICKER_COPY[locale === 'id' ? 'id' : 'en'];
    const selected = selectedOption(options);
    const rarest = options.held[0] ? displayFrame(options.held[0], options) : null;
    const choices = [
        { key: 'auto', value: null, label: copy.auto, hint: copy.autoHint, frame: rarest, held: true },
        ...FRAME_ORDER.map((type) => ({
            key: type,
            value: type,
            label: avatarFrameLabel(type, locale),
            hint: copy.hints[type](options.requirements),
            frame: displayFrame(type, options),
            held: options.held.includes(type),
        })),
        { key: 'none', value: 'none', label: copy.none, hint: copy.noneHint, frame: null, held: true },
    ];

    return <section className="vaf-picker" aria-labelledby="vaf-picker-heading">
        <header>
            <h2 id="vaf-picker-heading">{copy.title}</h2>
            <p>{copy.intro}</p>
        </header>
        <ul>
            {choices.map((choice) => {
                const isSelected = selected === choice.key;

                return <li key={choice.key}>
                    <button
                        type="button"
                        className={'vaf-option' + (choice.held ? '' : ' is-locked')}
                        aria-pressed={isSelected}
                        disabled={busy || !choice.held}
                        onClick={() => !isSelected && onChoose(choice.value)}
                    >
                        <UserAvatar user={user} frame={choice.frame} locale={locale} className="vaf-option-avatar" />
                        <strong>{choice.label}</strong>
                        <small>{choice.hint}</small>
                        <span className="vaf-option-state">{isSelected ? copy.active : choice.held ? copy.unlocked : `🔒 ${copy.locked}`}</span>
                    </button>
                </li>;
            })}
        </ul>
        {error && <p className="vaf-picker-error" role="alert">{error}</p>}
    </section>;
}

/** A round avatar (photo or initial) with the member's frame. */
export function UserAvatar({ user, frame = user?.avatar_frame, locale, size = 'md', pixels, animated = true, className = '', imageSuffix = '' }) {
    const name = user?.name ?? '?';

    return <AvatarFrame frame={frame} locale={locale} size={size} animated={animated}>
        <span className={'vaf-avatar ' + className} style={pixels ? { width: pixels, height: pixels } : undefined} aria-hidden="true">
            {user?.avatar_url ? <img src={`${user.avatar_url}${imageSuffix}`} alt="" loading="lazy" decoding="async" /> : name.slice(0, 1).toUpperCase()}
        </span>
    </AvatarFrame>;
}
