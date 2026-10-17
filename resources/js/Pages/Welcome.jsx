import { Head, Link } from "@inertiajs/react";
import { useEffect, useMemo, useRef, useState } from "react";
import "../../css/welcome.css";

const posts = [
    {
        id: 1,
        category: "Teknologi",
        title: "Smartphone Terbaik di 2026 untuk Gaming",
        description:
            "Menurut kalian, smartphone mana yang paling cocok untuk gaming di 2026? Performa, suhu, dan harga jadi pertimbangan utama.",
        author: "@galihpratama",
        age: "3 hari lalu",
        ageOrder: 3,
        votes: 1200,
        voteLabel: "1.2K",
        comments: 128,
    },
    {
        id: 2,
        category: "Wisata",
        title: "Destinasi Wisata Alam Terbaik di Indonesia",
        description:
            "Dari pantai, gunung, hingga danau, mana destinasi alam yang paling layak masuk bucket list?",
        author: "@safirandini",
        age: "5 hari lalu",
        ageOrder: 5,
        votes: 980,
        voteLabel: "980",
        comments: 243,
    },
    {
        id: 3,
        category: "Kuliner",
        title: "Kuliner Malam Terbaik di Jakarta",
        description:
            "Rekomendasi kuliner malam yang wajib dicoba. Mana yang jadi favorit kamu?",
        author: "@radityakrn",
        age: "2 hari lalu",
        ageOrder: 2,
        votes: 760,
        voteLabel: "760",
        comments: 96,
    },
    {
        id: 4,
        category: "Otomotif",
        title: "Mobil Keluarga Terbaik 2026",
        description:
            "Mana mobil yang paling nyaman dan worth it untuk keluarga? Yuk diskusi!",
        author: "@dianputra",
        age: "4 hari lalu",
        ageOrder: 4,
        votes: 620,
        voteLabel: "620",
        comments: 112,
    },
    {
        id: 5,
        category: "Hiburan",
        title: "Film Terbaik Tahun 2025",
        description: "Film mana yang paling berkesan buat kamu di tahun 2025?",
        author: "@nabilaar",
        age: "6 hari lalu",
        ageOrder: 6,
        votes: 580,
        voteLabel: "580",
        comments: 88,
    },
];

const categories = [
    ["Teknologi", "chip"],
    ["Wisata", "mountain"],
    ["Kuliner", "utensils"],
    ["Otomotif", "car"],
    ["Hiburan", "film"],
    ["Pendidikan", "graduation"],
    ["Kesehatan", "heartPulse"],
    ["Keuangan", "wallet"],
    ["Lainnya", "more"],
];

const leaders = [
    ["Samsung Galaxy A26", "12.4K", "12%", true],
    ["iPhone 16", "11.8K", "8%", true],
    ["Xiaomi 14", "9.6K", "4%", false],
    ["Samsung Galaxy A35", "8.4K", "10%", true],
    ["Redmi Note 14", "7.6K", "6%", true],
];

const topCategories = [
    ["Teknologi", "32.4K"],
    ["Wisata", "28.1K"],
    ["Kuliner", "24.6K"],
    ["Otomotif", "18.9K"],
    ["Hiburan", "16.2K"],
];

const communities = [
    ["🤖", "Pengguna Android", "12.4K anggota"],
    ["✈️", "Travel Indonesia", "10.8K anggota"],
    ["🍲", "Kuliner Nusantara", "9.1K anggota"],
];

const englishPosts = {
    1: {
        title: "Best Gaming Smartphones in 2026",
        description:
            "Which smartphone is best for gaming in 2026? Performance, temperature, and price are the main factors.",
        age: "3 days ago",
    },
    2: {
        title: "Best Nature Destinations in Indonesia",
        description:
            "From beaches and mountains to lakes, which nature destination deserves a spot on your bucket list?",
        age: "5 days ago",
    },
    3: {
        title: "Best Late-Night Food in Jakarta",
        description:
            "Share your favorite late-night eats in Jakarta. Which spot should everyone try?",
        age: "2 days ago",
    },
    4: {
        title: "Best Family Cars in 2026",
        description:
            "Which car offers the best comfort and value for families? Join the discussion!",
        age: "4 days ago",
    },
    5: {
        title: "Best Films of 2025",
        description: "Which film made the biggest impression on you in 2025?",
        age: "6 days ago",
    },
};

const translations = {
    id: {
        skip: "Lewati ke feed",
        searchRanking: "Cari ranking",
        searchPlaceholder: "Cari produk, tempat, atau topik...",
        login: "Masuk",
        register: "Daftar",
        search: "Cari",
        openMenu: "Buka menu",
        closeMenu: "Tutup menu",
        switchToDark: "Aktifkan mode gelap",
        switchToLight: "Aktifkan mode terang",
        mobileMenu: "Menu seluler",
        dismissNotice: "Tutup pemberitahuan",
        communitySummary: "Ringkasan komunitas",
        home: "Beranda",
        explore: "Jelajahi",
        createRanking: "Buat Ranking",
        notifications: "Notifikasi",
        mobileNotifications: "Notifikasi",
        profile: "Profil",
        popularCategories: "KATEGORI POPULER",
        heroDescription:
            "Bandingkan, dukung, dan temukan rekomendasi terbaik dari komunitas.",
        feed: "Feed komunitas",
        feedOrder: "Urutan feed",
        activeCategory: "Kategori",
        clearFilter: "Hapus filter",
        tabs: {
            "Untuk Kamu": "Untuk Kamu",
            Trending: "Trending",
            Terbaru: "Terbaru",
            "Peringkat Global": "Peringkat Global",
        },
        preview: "Pratinjau · data contoh",
        emptyTitle: "Ranking tidak ditemukan",
        emptyDescription: "Coba kata kunci atau kategori lain.",
        showAll: "Tampilkan semua",
        rankingPanel: "Peringkat Objek",
        rankingPeriod: "Periode peringkat",
        periods: {
            Harian: "Harian",
            Mingguan: "Mingguan",
            Sepanjang: "Sepanjang",
        },
        supporters: "pendukung",
        viewRankings: "Lihat semua peringkat",
        weeklyCategories: "Top Kategori Minggu Ini",
        viewCategories: "Lihat semua kategori",
        activeCommunities: "Komunitas Aktif",
        viewAll: "Lihat semua",
        members: "anggota",
        join: "Gabung",
        joined: "Gabung ✓",
        support: "Dukung",
        reduceSupport: "Kurangi dukungan",
        save: "Simpan",
        removeSaved: "Hapus simpanan",
        comments: "komentar",
        voteFor: "Suara untuk",
        createSoon: "Fitur buat ranking segera hadir.",
        notificationsSoon:
            "Notifikasi akan tersedia setelah fitur komunitas diluncurkan.",
        commentsSoon: "Detail komentar segera hadir.",
        communitiesSoon: "Daftar komunitas lengkap segera hadir.",
        categories: {
            Teknologi: "Teknologi",
            Wisata: "Wisata",
            Kuliner: "Kuliner",
            Otomotif: "Otomotif",
            Hiburan: "Hiburan",
            Pendidikan: "Pendidikan",
            Kesehatan: "Kesehatan",
            Keuangan: "Keuangan",
            Lainnya: "Lainnya",
        },
        communities: {
            "Pengguna Android": "Pengguna Android",
            "Travel Indonesia": "Travel Indonesia",
            "Kuliner Nusantara": "Kuliner Nusantara",
        },
    },
    en: {
        skip: "Skip to feed",
        searchRanking: "Search rankings",
        searchPlaceholder: "Search products, places, or topics...",
        login: "Log in",
        register: "Sign up",
        search: "Search",
        openMenu: "Open menu",
        closeMenu: "Close menu",
        switchToDark: "Switch to dark mode",
        switchToLight: "Switch to light mode",
        mobileMenu: "Mobile menu",
        dismissNotice: "Dismiss notification",
        communitySummary: "Community overview",
        home: "Home",
        explore: "Explore",
        createRanking: "Create Ranking",
        notifications: "Notifications",
        mobileNotifications: "Alerts",
        profile: "Profile",
        popularCategories: "POPULAR CATEGORIES",
        heroDescription:
            "Compare, support, and discover the community’s best recommendations.",
        feed: "Community feed",
        feedOrder: "Feed order",
        activeCategory: "Category",
        clearFilter: "Clear filter",
        tabs: {
            "Untuk Kamu": "For You",
            Trending: "Trending",
            Terbaru: "Latest",
            "Peringkat Global": "Global Rankings",
        },
        preview: "Preview · sample data",
        emptyTitle: "No rankings found",
        emptyDescription: "Try another search term or category.",
        showAll: "Show all",
        rankingPanel: "Top Rankings",
        rankingPeriod: "Ranking period",
        periods: { Harian: "Daily", Mingguan: "Weekly", Sepanjang: "All Time" },
        supporters: "supporters",
        viewRankings: "View all rankings",
        weeklyCategories: "Top Categories This Week",
        viewCategories: "View all categories",
        activeCommunities: "Active Communities",
        viewAll: "View all",
        members: "members",
        join: "Join",
        joined: "Joined ✓",
        support: "Support",
        reduceSupport: "Remove support from",
        save: "Save",
        removeSaved: "Remove saved",
        comments: "comments",
        voteFor: "Votes for",
        createSoon: "Create Ranking is coming soon.",
        notificationsSoon:
            "Notifications will be available when community features launch.",
        commentsSoon: "Comment details are coming soon.",
        communitiesSoon: "The full community list is coming soon.",
        categories: {
            Teknologi: "Technology",
            Wisata: "Travel",
            Kuliner: "Food",
            Otomotif: "Automotive",
            Hiburan: "Entertainment",
            Pendidikan: "Education",
            Kesehatan: "Health",
            Keuangan: "Finance",
            Lainnya: "Other",
        },
        communities: {
            "Pengguna Android": "Android Users",
            "Travel Indonesia": "Travel Indonesia",
            "Kuliner Nusantara": "Indonesian Food",
        },
    },
};

const iconPaths = {
    home: (
        <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V10Zm6 11v-7h6v7" />
    ),
    search: (
        <>
            <circle cx="11" cy="11" r="7" />
            <path d="m16 16 5 5" />
        </>
    ),
    plus: <path d="M12 5v14M5 12h14" />,
    plusCircle: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v8M8 12h8" />
        </>
    ),
    bell: (
        <>
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
        </>
    ),
    user: (
        <>
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
        </>
    ),
    menu: <path d="M4 6h16M4 12h16M4 18h16" />,
    close: <path d="M5 5 19 19M19 5 5 19" />,
    moon: <path d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z" />,
    sun: (
        <>
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
        </>
    ),
    bookmark: (
        <path d="M5 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16l-7-3.5L5 21V5Z" />
    ),
    comment: (
        <path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8-1.5 0-3-.3-4.3-.9L3 20l1.4-3.7A7.2 7.2 0 0 1 3 12c0-4.4 4-8 9-8s9 3.6 9 8Z" />
    ),
    chevronDown: <path d="m5 9 7 7 7-7" />,
    chevronUp: <path d="m5 15 7-7 7 7" />,
    arrow: <path d="M4 12h16m-6-6 6 6-6 6" />,
    trend: <path d="m3 17 6-6 4 4 7-8M14 7h6v6" />,
    trophy: (
        <>
            <path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" />
        </>
    ),
    filter: <path d="M4 5h16l-6.5 7v6l-3 2v-8L4 5Z" />,
    globe: (
        <>
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3c3 2.5 4.5 5.5 4.5 9S15 18.5 12 21M12 3C9 5.5 7.5 8.5 7.5 12S9 18.5 12 21" />
        </>
    ),
    chip: (
        <>
            <rect x="6" y="6" width="12" height="12" rx="2" />
            <path d="M9 9h6v6H9zM9 2v4m6-4v4M9 18v4m6-4v4M2 9h4m-4 6h4m12-6h4m-4 6h4" />
        </>
    ),
    mountain: (
        <>
            <path d="m2 19 7-11 4 6 2-3 7 8H2Z" />
            <path d="m7 11 2-3 2 3" />
        </>
    ),
    utensils: (
        <>
            <path d="M4 3v8a3 3 0 0 0 6 0V3M7 3v18M19 21V3c-3 1-4 4-4 8h4" />
        </>
    ),
    car: (
        <>
            <path d="m4 15 1.5-7A2 2 0 0 1 7.5 6h9a2 2 0 0 1 2 2l1.5 7M4 15h16v4H4zM7 19v2m10-2v2M4 11h16" />
            <circle cx="7.5" cy="15" r=".5" />
            <circle cx="16.5" cy="15" r=".5" />
        </>
    ),
    film: (
        <>
            <rect x="3" y="4" width="18" height="16" rx="2" />
            <path d="M7 4v16M17 4v16M3 9h4m-4 6h4m10-6h4m-4 6h4" />
        </>
    ),
    graduation: (
        <>
            <path d="m2 9 10-5 10 5-10 5L2 9Zm4 2v6c4 3 8 3 12 0v-6M22 9v7" />
        </>
    ),
    heartPulse: (
        <>
            <path d="M20.8 8.7C20.8 5.6 18.9 4 16.5 4c-2 0-3.3 1-4.5 2.5C10.8 5 9.5 4 7.5 4 5.1 4 3.2 5.6 3.2 8.7c0 4.1 4.3 7.3 8.8 11.3 4.5-4 8.8-7.2 8.8-11.3Z" />
            <path d="M3.5 12h5l1.5-3 3 6 1.5-3h5.8" />
        </>
    ),
    wallet: (
        <>
            <rect x="3" y="5" width="18" height="15" rx="2" />
            <path d="M3 9h18M15 15h3" />
        </>
    ),
    more: (
        <>
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
        </>
    ),
    check: <polyline points="20 6 9 17 4 12" strokeWidth="2.5" />,
    layers: (
        <>
            <polygon points="12 2 2 7 12 12 22 7 12 2" />
            <polyline points="2 17 12 22 22 17" />
            <polyline points="2 12 12 17 22 12" />
        </>
    ),
};

function Icon({ name, size = 18, className = "" }) {
    return (
        <svg
            className={className}
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            {iconPaths[name]}
        </svg>
    );
}

function Brand() {
    return <span className="vm-brand">votmee</span>;
}

function ThemeToggle({ theme, onToggle, ui }) {
    const label = theme === "dark" ? ui.switchToLight : ui.switchToDark;

    return (
        <button
            type="button"
            className="vm-theme-toggle"
            aria-label={label}
            aria-pressed={theme === "dark"}
            title={label}
            onClick={onToggle}
        >
            <Icon name={theme === "dark" ? "sun" : "moon"} size={19} />
        </button>
    );
}

function LanguageSelect({ language, onChange, mobile = false }) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);

    useEffect(() => {
        function handleClickOutside(event) {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target)
            ) {
                setOpen(false);
            }
        }
        function handleKeyDown(event) {
            if (event.key === "Escape" && open) {
                setOpen(false);
            }
        }
        if (open) {
            document.addEventListener("mousedown", handleClickOutside);
            document.addEventListener("touchstart", handleClickOutside);
            document.addEventListener("keydown", handleKeyDown);
        }
        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
            document.removeEventListener("touchstart", handleClickOutside);
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [open]);

    const options = [
        {
            code: "id",
            label: "Bahasa Indonesia",
            short: "ID",
            flag: (
                <svg
                    className="vm-flag-id"
                    viewBox="0 0 20 14"
                    width="18"
                    height="13"
                >
                    <path fill="#fff" d="M0 0h20v14H0z" />
                    <path fill="#ed1c24" d="M0 0h20v7H0z" />
                </svg>
            ),
        },
        {
            code: "en",
            label: "English",
            short: "EN",
            flag: <Icon name="globe" size={16} />,
        },
    ];

    const current = options.find((opt) => opt.code === language) || options[0];

    return (
        <div
            ref={containerRef}
            className={`vm-lang-select-box${mobile ? " vm-mobile-language" : ""}`}
        >
            <button
                type="button"
                className={`vm-lang-trigger ${open ? "open" : ""}`}
                onClick={() => setOpen(!open)}
                aria-haspopup="listbox"
                aria-expanded={open}
                aria-label="Pilih bahasa / Select language"
            >
                <span className="vm-lang-flag">{current.flag}</span>
                <span className="vm-lang-code">{current.short}</span>
                <Icon
                    name="chevronDown"
                    size={13}
                    className={`vm-select-chevron ${open ? "rotate" : ""}`}
                />
            </button>

            {open && (
                <div className="vm-lang-dropdown" role="listbox">
                    <div className="vm-select-dropdown-header">
                        <span>
                            {language === "id" ? "Pilih Bahasa" : "Language"}
                        </span>
                    </div>
                    {options.map((item) => {
                        const isSelected = language === item.code;
                        return (
                            <button
                                key={item.code}
                                type="button"
                                role="option"
                                aria-selected={isSelected}
                                className={`vm-lang-option ${isSelected ? "selected" : ""}`}
                                onClick={() => {
                                    onChange(item.code);
                                    setOpen(false);
                                }}
                            >
                                <span className="vm-lang-opt-main">
                                    <span className="vm-lang-opt-flag">
                                        {item.flag}
                                    </span>
                                    <span className="vm-lang-opt-label">
                                        {item.label}
                                    </span>
                                </span>
                                <span className="vm-lang-opt-extra">
                                    <span className="vm-lang-opt-code">
                                        {item.short}
                                    </span>
                                    {isSelected && (
                                        <span className="vm-select-option-check">
                                            <Icon name="check" size={14} />
                                        </span>
                                    )}
                                </span>
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

function FeedCard({ post, vote, saved, onVote, onSave, onComments, ui }) {
    const image = `/images/stitch/feed-desktop-${post.id}.jpg`;
    const mobileImage = `/images/stitch/feed-mobile-${post.id}.jpg`;

    return (
        <article className="vm-feed-card items-center">
            <div
                className="vm-desktop-vote"
                aria-label={`${ui.voteFor} ${post.title}`}
            >
                <button
                    type="button"
                    aria-label={`${ui.support} ${post.title}`}
                    aria-pressed={vote === 1}
                    className={vote === 1 ? "selected" : ""}
                    onClick={() => onVote(post.id, 1)}
                >
                    <Icon name="chevronUp" size={18} />
                </button>
                <strong>{post.voteLabel}</strong>
                <button
                    type="button"
                    aria-label={`${ui.reduceSupport} ${post.title}`}
                    aria-pressed={vote === -1}
                    className={vote === -1 ? "selected" : ""}
                    onClick={() => onVote(post.id, -1)}
                >
                    <Icon name="chevronDown" size={18} />
                </button>
            </div>
            <picture className="vm-feed-image">
                <source media="(max-width: 767px)" srcSet={mobileImage} />
                <img src={image} alt="" />
            </picture>
            <div className="vm-feed-content">
                <div className="vm-feed-title-row">
                    <span
                        className={`vm-category-tag vm-tag-${post.category.toLowerCase()}`}
                    >
                        {ui.categories[post.category]}
                    </span>
                    <button
                        type="button"
                        className={`vm-save${saved ? " selected" : ""}`}
                        aria-label={
                            saved
                                ? `${ui.removeSaved} ${post.title}`
                                : `${ui.save} ${post.title}`
                        }
                        aria-pressed={saved}
                        onClick={() => onSave(post.id)}
                    >
                        <Icon name="bookmark" size={17} />
                    </button>
                </div>
                <h2>{post.title}</h2>
                <p className="vm-feed-description">{post.description}</p>
                <div className="vm-feed-meta">
                    <span className="vm-avatar">
                        {post.author[1].toUpperCase()}
                    </span>
                    <span>
                        {post.author} <span className="vm-dot">·</span>{" "}
                        {post.age}
                    </span>
                    <span className="vm-desktop-comments">
                        <span className="vm-dot">·</span>{" "}
                        <Icon name="comment" size={15} /> {post.comments}{" "}
                        {ui.comments}
                    </span>
                </div>
            </div>
            <div className="vm-mobile-footer">
                <span className="vm-mobile-author">
                    <span className="vm-avatar">
                        {post.author[1].toUpperCase()}
                    </span>
                    <span>
                        {post.author} · {post.age}
                    </span>
                </span>
                <div className="vm-mobile-actions">
                    <button
                        type="button"
                        className={vote === 1 ? "selected" : ""}
                        aria-label={`${ui.support} ${post.title}`}
                        aria-pressed={vote === 1}
                        onClick={() => onVote(post.id, 1)}
                    >
                        <Icon name="chevronUp" size={15} /> {post.voteLabel}{" "}
                        <Icon name="chevronDown" size={13} />
                    </button>
                    <button
                        type="button"
                        aria-label={`${post.comments} ${ui.comments}, ${post.title}`}
                        onClick={onComments}
                    >
                        <Icon name="comment" size={15} /> {post.comments}
                    </button>
                </div>
            </div>
        </article>
    );
}

export default function Welcome({ auth }) {
    const [theme, setTheme] = useState(() => {
        if (typeof window === "undefined") return "light";
        try {
            const savedTheme = window.localStorage.getItem("votmee-theme");
            if (savedTheme === "dark" || savedTheme === "light") {
                return savedTheme;
            }
        } catch {}
        return window.matchMedia?.("(prefers-color-scheme: dark)")?.matches
            ? "dark"
            : "light";
    });
    const [language, setLanguage] = useState(() => {
        if (typeof window === "undefined") return "id";
        try {
            return window.localStorage.getItem("votmee-language") === "en"
                ? "en"
                : "id";
        } catch {
            return "id";
        }
    });
    const [tab, setTab] = useState("Untuk Kamu");
    const [category, setCategory] = useState("Semua Kategori");
    const [searchInput, setSearchInput] = useState("");
    const [searchTerm, setSearchTerm] = useState("");
    const [searchOpen, setSearchOpen] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);
    const [period, setPeriod] = useState("Mingguan");
    const [votes, setVotes] = useState({});
    const [saved, setSaved] = useState([]);
    const [joined, setJoined] = useState([]);
    const [notice, setNotice] = useState("");
    const feedRef = useRef(null);
    const ui = translations[language];

    useEffect(() => {
        document.documentElement.lang = language;
        try {
            window.localStorage.setItem("votmee-language", language);
        } catch {}
    }, [language]);

    useEffect(() => {
        const previousColorScheme = document.documentElement.style.colorScheme;
        document.documentElement.style.colorScheme = theme;
        try {
            window.localStorage.setItem("votmee-theme", theme);
        } catch {}
        return () => {
            document.documentElement.style.colorScheme = previousColorScheme;
        };
    }, [theme]);

    const visiblePosts = useMemo(() => {
        const result = posts.filter(
            (post) =>
                (category === "Semua Kategori" || category === post.category) &&
                `${post.title} ${post.description} ${post.category} ${englishPosts[post.id].title} ${englishPosts[post.id].description} ${translations.en.categories[post.category]}`
                    .toLocaleLowerCase()
                    .includes(searchTerm.toLocaleLowerCase()),
        );
        const ordered =
            tab === "Terbaru"
                ? [...result].sort((a, b) => a.ageOrder - b.ageOrder)
                : tab === "Trending" || tab === "Peringkat Global"
                  ? [...result].sort((a, b) => b.votes - a.votes)
                  : result;
        return language === "en"
            ? ordered.map((post) => ({ ...post, ...englishPosts[post.id] }))
            : ordered;
    }, [category, language, searchTerm, tab]);

    function showNotice(message) {
        setNotice(message);
        window.setTimeout(() => setNotice(""), 5000);
    }

    function chooseCategory(value) {
        setCategory(value);
        setMenuOpen(false);
        feedRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function submitSearch(event) {
        event.preventDefault();
        setSearchTerm(searchInput.trim());
        setSearchOpen(false);
        feedRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function toggleVote(id, direction) {
        setVotes((current) => ({
            ...current,
            [id]: current[id] === direction ? 0 : direction,
        }));
    }

    function toggleSaved(id) {
        setSaved((current) =>
            current.includes(id)
                ? current.filter((item) => item !== id)
                : [...current, id],
        );
    }

    function toggleJoined(index) {
        setJoined((current) =>
            current.includes(index)
                ? current.filter((item) => item !== index)
                : [...current, index],
        );
    }

    return (
        <>
            <Head title="Votmee — Where Favorites Rise">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link
                    rel="preconnect"
                    href="https://fonts.gstatic.com"
                    crossOrigin="anonymous"
                />
                <link
                    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
                    rel="stylesheet"
                />
            </Head>
            <a className="vm-skip" href="#feed">
                {ui.skip}
            </a>
            <div className="vm-page" id="beranda" data-theme={theme}>
                <header className="vm-header">
                    <div className="vm-shell vm-header-inner">
                        <a
                            className="vm-header-brand"
                            href="#beranda"
                            aria-label={`Votmee, ${ui.home}`}
                        >
                            <Brand />
                        </a>
                        <form
                            className="vm-header-search"
                            onSubmit={submitSearch}
                        >
                            <Icon name="search" size={17} />
                            <label className="vm-sr-only" htmlFor="vm-search">
                                {ui.searchRanking}
                            </label>
                            <input
                                id="vm-search"
                                type="search"
                                placeholder={ui.searchPlaceholder}
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                            />
                        </form>
                        <div className="vm-header-right">
                            <ThemeToggle
                                theme={theme}
                                onToggle={() =>
                                    setTheme(theme === "dark" ? "light" : "dark")
                                }
                                ui={ui}
                            />
                            <LanguageSelect
                                language={language}
                                onChange={setLanguage}
                            />
                            {auth?.user ? (
                                <Link
                                    href="/dashboard"
                                    className="vm-auth-link"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href="/login"
                                        className="vm-auth-link"
                                    >
                                        {ui.login}
                                    </Link>
                                    <Link
                                        href="/register"
                                        className="vm-register"
                                    >
                                        {ui.register}
                                    </Link>
                                </>
                            )}
                        </div>
                        <div className="vm-mobile-header-actions">
                            <ThemeToggle
                                theme={theme}
                                onToggle={() =>
                                    setTheme(theme === "dark" ? "light" : "dark")
                                }
                                ui={ui}
                            />
                            <button
                                type="button"
                                aria-label={ui.search}
                                onClick={() => setSearchOpen(!searchOpen)}
                            >
                                <Icon name="search" size={21} />
                            </button>
                            <button
                                type="button"
                                aria-label={
                                    menuOpen ? ui.closeMenu : ui.openMenu
                                }
                                aria-expanded={menuOpen}
                                onClick={() => setMenuOpen(!menuOpen)}
                            >
                                <Icon
                                    name={menuOpen ? "close" : "menu"}
                                    size={23}
                                />
                            </button>
                        </div>
                    </div>
                    {searchOpen && (
                        <form
                            className="vm-mobile-search"
                            onSubmit={submitSearch}
                        >
                            <Icon name="search" size={18} />
                            <label
                                className="vm-sr-only"
                                htmlFor="vm-mobile-search"
                            >
                                {ui.searchRanking}
                            </label>
                            <input
                                id="vm-mobile-search"
                                autoFocus
                                type="search"
                                placeholder={ui.searchPlaceholder}
                                value={searchInput}
                                onChange={(event) =>
                                    setSearchInput(event.target.value)
                                }
                            />
                            <button type="submit">{ui.search}</button>
                        </form>
                    )}
                </header>

                {menuOpen && (
                    <nav className="vm-mobile-menu" aria-label={ui.mobileMenu}>
                        <a href="#beranda" onClick={() => setMenuOpen(false)}>
                            {ui.home}
                        </a>
                        <a href="#feed" onClick={() => setMenuOpen(false)}>
                            {ui.explore}
                        </a>
                        <button
                            type="button"
                            onClick={() => {
                                setMenuOpen(false);
                                showNotice(ui.createSoon);
                            }}
                        >
                            {ui.createRanking}
                        </button>
                        {auth?.user ? (
                            <Link href="/dashboard">{ui.profile}</Link>
                        ) : (
                            <>
                                <Link href="/login">{ui.login}</Link>
                                <Link href="/register">{ui.register}</Link>
                            </>
                        )}
                        <LanguageSelect
                            language={language}
                            onChange={setLanguage}
                            mobile
                        />
                    </nav>
                )}

                <div className="vm-shell vm-layout">
                    <aside className="vm-sidebar" aria-label={ui.home}>
                        <nav className="vm-side-nav">
                            <a className="active" href="#beranda">
                                <Icon name="home" />
                                {ui.home}
                            </a>
                            <a href="#feed">
                                <Icon name="search" />
                                {ui.explore}
                            </a>
                            <button
                                type="button"
                                onClick={() => showNotice(ui.createSoon)}
                            >
                                <Icon name="plusCircle" />
                                {ui.createRanking}
                            </button>
                            <button
                                type="button"
                                onClick={() => showNotice(ui.notificationsSoon)}
                            >
                                <Icon name="bell" />
                                {ui.notifications}
                            </button>
                            {auth?.user ? (
                                <Link href="/dashboard">
                                    <Icon name="user" />
                                    {ui.profile}
                                </Link>
                            ) : (
                                <Link href="/login">
                                    <Icon name="user" />
                                    {ui.profile}
                                </Link>
                            )}
                        </nav>
                        <div className="vm-side-categories">
                            <h2>{ui.popularCategories}</h2>
                            {categories.map(([name, icon]) => (
                                <button
                                    key={name}
                                    type="button"
                                    className={
                                        category === name ? "selected" : ""
                                    }
                                    onClick={() => chooseCategory(name)}
                                >
                                    <span
                                        className={`vm-side-icon vm-side-icon-${name.toLowerCase()}`}
                                    >
                                        <Icon name={icon} size={17} />
                                    </span>
                                    {ui.categories[name]}
                                </button>
                            ))}
                        </div>
                    </aside>

                    <main className="vm-main">
                        <section
                            className="vm-hero"
                            aria-labelledby="vm-hero-title"
                        >
                            <div className="vm-hero-copy">
                                <h1 id="vm-hero-title">
                                    Where Favorites Rise.
                                </h1>
                                <p>{ui.heroDescription}</p>
                                <button
                                    type="button"
                                    onClick={() => showNotice(ui.createSoon)}
                                >
                                    {ui.createRanking}{" "}
                                    <Icon name="arrow" size={17} />
                                </button>
                            </div>
                            <div
                                className="vm-hero-illustration"
                                aria-hidden="true"
                            >
                                <div className="vm-phone">
                                    <span className="vm-phone-speaker" />
                                    <strong>Ranking #1</strong>
                                    <span className="vm-phone-line" />
                                    <span className="vm-phone-line short" />
                                    <small>▲ 98.4%</small>
                                </div>
                                <span className="vm-hero-trophy">
                                    <Icon name="trophy" size={29} />
                                </span>
                                <span className="vm-hero-trend">
                                    <Icon name="trend" size={24} />
                                </span>
                            </div>
                        </section>

                        <section
                            className="vm-feed-section"
                            id="feed"
                            ref={feedRef}
                            aria-label={ui.feed}
                        >
                            <div className="vm-feed-toolbar items-center">
                                <div
                                    className="vm-tabs items-center"
                                    role="tablist"
                                    aria-label={ui.feedOrder}
                                >
                                    {[
                                        "Untuk Kamu",
                                        "Trending",
                                        "Terbaru",
                                        "Peringkat Global",
                                    ].map((name) => (
                                        <button
                                            type="button"
                                            role="tab"
                                            aria-selected={tab === name}
                                            className={`inline-flex items-center justify-center ${
                                                tab === name ? "active" : ""
                                            }`}
                                            key={name}
                                            onClick={() => setTab(name)}
                                        >
                                            {ui.tabs[name]}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            {category !== "Semua Kategori" && (
                                <div className="vm-active-filter">
                                    <span>
                                        {ui.activeCategory}: <strong>{ui.categories[category]}</strong>
                                    </span>
                                    <button
                                        type="button"
                                        onClick={() => setCategory("Semua Kategori")}
                                    >
                                        <Icon name="close" size={15} />
                                        {ui.clearFilter}
                                    </button>
                                </div>
                            )}
                            <div className="vm-feed-list">
                                {visiblePosts.map((post) => (
                                    <FeedCard
                                        key={post.id}
                                        post={post}
                                        vote={votes[post.id] || 0}
                                        saved={saved.includes(post.id)}
                                        onVote={toggleVote}
                                        onSave={toggleSaved}
                                        onComments={() =>
                                            showNotice(ui.commentsSoon)
                                        }
                                        ui={ui}
                                    />
                                ))}
                                {visiblePosts.length === 0 && (
                                    <div className="vm-empty">
                                        <strong>{ui.emptyTitle}</strong>
                                        <p>{ui.emptyDescription}</p>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setCategory("Semua Kategori");
                                                setSearchTerm("");
                                                setSearchInput("");
                                            }}
                                        >
                                            {ui.showAll}
                                        </button>
                                    </div>
                                )}
                            </div>
                        </section>
                    </main>

                    <aside
                        className="vm-rightbar"
                        aria-label={ui.communitySummary}
                    >
                        <section className="vm-panel">
                            <h2>{ui.rankingPanel}</h2>
                            <div
                                className="vm-periods"
                                aria-label={ui.rankingPeriod}
                            >
                                {["Harian", "Mingguan", "Sepanjang"].map(
                                    (name) => (
                                        <button
                                            type="button"
                                            key={name}
                                            aria-pressed={period === name}
                                            className={
                                                period === name ? "active" : ""
                                            }
                                            onClick={() => setPeriod(name)}
                                        >
                                            {ui.periods[name]}
                                        </button>
                                    ),
                                )}
                            </div>
                            <ol className="vm-leader-list">
                                {leaders.map(
                                    ([name, count, change, up], index) => (
                                        <li key={name}>
                                            <span
                                                className={`vm-leader-position vm-position-${index + 1}`}
                                            >
                                                {index + 1}
                                            </span>
                                            <img
                                                src={`/images/stitch/object-${index + 1}.jpg`}
                                                alt=""
                                            />
                                            <span className="vm-leader-name">
                                                <strong>{name}</strong>
                                                <small>
                                                    {count} {ui.supporters}
                                                </small>
                                            </span>
                                            <span
                                                className={
                                                    up ? "vm-up" : "vm-down"
                                                }
                                            >
                                                {up ? "▲" : "▼"} {change}
                                            </span>
                                        </li>
                                    ),
                                )}
                            </ol>
                            <button
                                type="button"
                                className="vm-panel-link"
                                onClick={() => {
                                    setTab("Peringkat Global");
                                    feedRef.current?.scrollIntoView({
                                        behavior: "smooth",
                                    });
                                }}
                            >
                                {ui.viewRankings}{" "}
                                <Icon name="arrow" size={14} />
                            </button>
                        </section>
                        <section className="vm-panel">
                            <h2>{ui.weeklyCategories}</h2>
                            <ol className="vm-top-categories">
                                {topCategories.map(([name, count], index) => (
                                    <li key={name}>
                                        <img
                                            src={`/images/stitch/category-${index + 1}.jpg`}
                                            alt=""
                                        />
                                        <span>
                                            <strong>
                                                {ui.categories[name]}
                                            </strong>
                                            <small>{count} ranking</small>
                                        </span>
                                    </li>
                                ))}
                            </ol>
                            <button
                                type="button"
                                className="vm-panel-link"
                                onClick={() => {
                                    setCategory("Semua Kategori");
                                    feedRef.current?.scrollIntoView({
                                        behavior: "smooth",
                                    });
                                }}
                            >
                                {ui.viewCategories}{" "}
                                <Icon name="arrow" size={14} />
                            </button>
                        </section>
                        <section className="vm-panel">
                            <div className="vm-panel-heading">
                                <h2>{ui.activeCommunities}</h2>
                                <button
                                    type="button"
                                    onClick={() =>
                                        showNotice(ui.communitiesSoon)
                                    }
                                >
                                    {ui.viewAll}
                                </button>
                            </div>
                            <ul className="vm-community-list">
                                {communities.map(
                                    ([emoji, name, count], index) => (
                                        <li key={name}>
                                            <span className="vm-community-emoji">
                                                {emoji}
                                            </span>
                                            <span>
                                                <strong>
                                                    {ui.communities[name]}
                                                </strong>
                                                <small>
                                                    {count.replace(
                                                        "anggota",
                                                        ui.members,
                                                    )}
                                                </small>
                                            </span>
                                            <button
                                                type="button"
                                                className={
                                                    joined.includes(index)
                                                        ? "joined"
                                                        : ""
                                                }
                                                onClick={() =>
                                                    toggleJoined(index)
                                                }
                                            >
                                                {joined.includes(index)
                                                    ? ui.joined
                                                    : ui.join}
                                            </button>
                                        </li>
                                    ),
                                )}
                            </ul>
                        </section>
                    </aside>
                </div>

                <nav className="vm-bottom-nav" aria-label={ui.home}>
                    <a className="active" href="#beranda">
                        <Icon name="home" size={21} />
                        <span>{ui.home}</span>
                    </a>
                    <a href="#feed">
                        <Icon name="search" size={21} />
                        <span>{ui.explore}</span>
                    </a>
                    <button
                        type="button"
                        className="vm-bottom-create"
                        aria-label={ui.createRanking}
                        onClick={() => showNotice(ui.createSoon)}
                    >
                        <Icon name="plus" size={26} />
                    </button>
                    <button
                        type="button"
                        aria-label={ui.notifications}
                        onClick={() => showNotice(ui.notificationsSoon)}
                    >
                        <Icon name="bell" size={21} />
                        <span>{ui.mobileNotifications}</span>
                    </button>
                    {auth?.user ? (
                        <Link href="/dashboard">
                            <Icon name="user" size={21} />
                            <span>{ui.profile}</span>
                        </Link>
                    ) : (
                        <Link href="/login">
                            <Icon name="user" size={21} />
                            <span>{ui.profile}</span>
                        </Link>
                    )}
                </nav>
                {notice && (
                    <div className="vm-toast" role="status">
                        <span>{notice}</span>
                        <button
                            type="button"
                            aria-label={ui.dismissNotice}
                            onClick={() => setNotice("")}
                        >
                            <Icon name="close" size={17} />
                        </button>
                    </div>
                )}
            </div>
        </>
    );
}
