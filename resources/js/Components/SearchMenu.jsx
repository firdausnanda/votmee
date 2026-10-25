import { useEffect, useMemo, useRef, useState } from "react";
import { AvatarFrame } from "../Pages/Community/AvatarFrame";

const RECENT_SEARCHES_KEY = "votmee_recent_searches";
const BROWSE_CATEGORY_LIMIT = 8;
const SUGGESTION_DEBOUNCE_MS = 200;
const SUGGESTION_MIN_LENGTH = 2;

const DEFAULT_RECENTS_ID = [
    "Smartphone Gaming",
    "Wisata Alam",
    "Kuliner Jakarta",
];

const DEFAULT_RECENTS_EN = [
    "Gaming Smartphone",
    "Nature Travel",
    "Jakarta Food",
];

// Self-contained SVG icons for crisp, lightweight rendering
const searchIcons = {
    search: (
        <>
            <circle cx="11" cy="11" r="7" />
            <path d="m16 16 5 5" />
        </>
    ),
    close: <path d="M18 6 6 18M6 6l12 12" />,
    history: (
        <>
            <circle cx="12" cy="12" r="9" />
            <polyline points="12 7 12 12 15 15" />
        </>
    ),
    flame: (
        <path d="M8.5 14.5A2.5 2.5 0 0 0 11 17c1.38 0 2.5-1.12 2.5-2.5 0-1.63-1.04-2.5-1.5-3.5-.46 1-1 1.5-1.5 2a1.5 1.5 0 0 1-2-1c0-1.5 1-2.5 2-4 .5 1.5 1.5 2.5 3 2.5 2.5 0 4.5-2 4.5-5 0-1.5-.5-2.5-1.5-3.5 1 1 2 2.5 2 4.5 0 4.14-3.36 7.5-7.5 7.5S3 16.14 3 12c0-3.1 1.9-5.7 4.5-6.8-.5 1.8-.5 3.8 1 5.3z" />
    ),
    trophy: (
        <path d="M7 3h10v8a5 5 0 0 1-10 0V3ZM7 5H4v3a4 4 0 0 0 4 4M17 5h3v3a4 4 0 0 1-4 4M12 16v4m-4 1h8" />
    ),
    arrow: <path d="M5 12h14m-6-6 6 6-6 6" />,
    user: (
        <>
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
        </>
    ),
    enter: <path d="M9 10L4 15l5 5m11-9v4a2 2 0 0 1-2 2H4" />,
    compass: (
        <>
            <circle cx="12" cy="12" r="9" />
            <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76" />
        </>
    ),
    sparkle: (
        <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3L12 3z" />
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
};

function MenuIcon({ name, size = 16, className = "" }) {
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
            {searchIcons[name] || searchIcons.search}
        </svg>
    );
}

// Highlight matching characters in text
function highlightMatch(text, query) {
    if (!text) return "";
    if (!query || !query.trim()) return text;
    const q = query.trim();
    const index = text.toLowerCase().indexOf(q.toLowerCase());
    if (index === -1) return text;

    return (
        <>
            {text.slice(0, index)}
            <mark className="vm-search-highlight">{text.slice(index, index + q.length)}</mark>
            {text.slice(index + q.length)}
        </>
    );
}

export default function SearchMenu({
    searchInput,
    setSearchInput,
    onSearchSubmit,
    onSelectCategory,
    onOpenLink,
    fetchSuggestions,
    onClose,
    posts = [],
    leaders = [],
    categories = [],
    englishPosts = {},
    language = "id",
    ui = {},
    isMobile = false,
    autoFocus = false,
}) {
    const [isOpen, setIsOpen] = useState(autoFocus);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [showAllCategories, setShowAllCategories] = useState(false);
    const [remote, setRemote] = useState({ query: "", posts: [], leaders: [], users: [], loading: false });
    const containerRef = useRef(null);
    const inputRef = useRef(null);
    const listRef = useRef(null);

    // Detect Mac for keyboard shortcut hint
    const isMac = useMemo(() => {
        if (typeof navigator === "undefined") return false;
        return /Mac|iPod|iPhone|iPad/.test(navigator.platform || "");
    }, []);

    const shortcutText = isMac ? "⌘K" : "Ctrl K";

    // Load recent searches from localStorage
    const [recentSearches, setRecentSearches] = useState(() => {
        if (typeof window === "undefined") {
            return language === "en" ? DEFAULT_RECENTS_EN : DEFAULT_RECENTS_ID;
        }
        try {
            const raw = window.localStorage.getItem(RECENT_SEARCHES_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed) && parsed.length > 0) return parsed;
            }
        } catch {}
        return language === "en" ? DEFAULT_RECENTS_EN : DEFAULT_RECENTS_ID;
    });

    // Save recent searches to localStorage
    function saveRecents(list) {
        setRecentSearches(list);
        if (typeof window !== "undefined") {
            try {
                window.localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(list));
            } catch {}
        }
    }

    function addRecentSearch(query) {
        if (!query || !query.trim()) return;
        const q = query.trim();
        const updated = [
            q,
            ...recentSearches.filter((item) => item.toLowerCase() !== q.toLowerCase()),
        ].slice(0, 6);
        saveRecents(updated);
    }

    function removeRecentSearch(e, item) {
        e.stopPropagation();
        e.preventDefault();
        const updated = recentSearches.filter((i) => i !== item);
        saveRecents(updated);
    }

    function clearAllRecents(e) {
        e.stopPropagation();
        e.preventDefault();
        saveRecents([]);
    }

    // Trending items for suggestions
    const trendingItems = useMemo(() => {
        // Build 4 rich trending items from posts & leaders
        const topPosts = [...posts]
            .sort((a, b) => b.votes - a.votes)
            .slice(0, 3)
            .map((p) => ({
                id: `post-${p.id}`,
                title: language === "en" && englishPosts[p.id] ? englishPosts[p.id].title : p.title,
                category: p.category,
                subtitle: `${p.voteLabel} ${ui.votesLabel || (language === "en" ? "votes" : "suara")}`,
                badge: p.category,
                type: "post",
                href: p.href,
            }));

        const topLeader = leaders[0]
            ? [
                  {
                      id: `leader-0`,
                      title: leaders[0][0],
                      category: language === "en" ? "Object" : "Objek",
                      subtitle: `${leaders[0][1]} ${ui.supportersLabel || (language === "en" ? "supporters" : "pendukung")}`,
                      badge: language === "en" ? "Top Leader" : "Peringkat #1",
                      type: "leader",
                      href: leaders[0][4],
                  },
              ]
            : [];

        return [...topLeader, ...topPosts].slice(0, 4);
    }, [posts, leaders, language, englishPosts, ui]);

    // Live search matching results when typing
    const query = searchInput.trim().toLowerCase();
    const hasRemoteResults = Boolean(fetchSuggestions) && remote.query === query && !remote.loading;
    const isSearching = Boolean(fetchSuggestions) && query.length >= SUGGESTION_MIN_LENGTH && !hasRemoteResults;

    // Fetch server-side suggestions (debounced, stale requests aborted)
    useEffect(() => {
        if (!fetchSuggestions || !isOpen || query.length < SUGGESTION_MIN_LENGTH) {
            return undefined;
        }
        const controller = new AbortController();
        setRemote((current) => ({ ...current, loading: true }));
        const timer = setTimeout(() => {
            fetchSuggestions(query, controller.signal)
                .then((result) => setRemote({ query, posts: result.posts ?? [], leaders: result.leaders ?? [], users: result.users ?? [], loading: false }))
                .catch((error) => {
                    if (error?.name !== "AbortError") {
                        setRemote({ query, posts: [], leaders: [], users: [], loading: false });
                    }
                });
        }, SUGGESTION_DEBOUNCE_MS);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [query, isOpen, fetchSuggestions]);

    const matchingPosts = useMemo(() => {
        if (!query) return [];
        if (hasRemoteResults) return remote.posts;
        return posts
            .filter((p) => {
                const title = (
                    language === "en" && englishPosts[p.id]
                        ? englishPosts[p.id].title
                        : p.title
                ).toLowerCase();
                const desc = (
                    language === "en" && englishPosts[p.id]
                        ? englishPosts[p.id].description
                        : p.description
                ).toLowerCase();
                const cat = (p.category || "").toLowerCase();
                const author = (p.author || "").toLowerCase();
                return (
                    title.includes(query) ||
                    desc.includes(query) ||
                    cat.includes(query) ||
                    author.includes(query)
                );
            })
            .slice(0, 5)
            .map((p) => ({
                id: p.id,
                title:
                    language === "en" && englishPosts[p.id]
                        ? englishPosts[p.id].title
                        : p.title,
                description:
                    language === "en" && englishPosts[p.id]
                        ? englishPosts[p.id].description
                        : p.description,
                category: p.category,
                author: p.author,
                votes: p.voteLabel,
                href: p.href,
            }));
    }, [query, posts, language, englishPosts, hasRemoteResults, remote.posts]);

    // Matching category if query matches any category name
    const matchingCategory = useMemo(() => {
        if (!query) return null;
        return categories.find(([name]) => {
            const translated = ui.categories && ui.categories[name] ? ui.categories[name] : name;
            return (
                name.toLowerCase().includes(query) ||
                translated.toLowerCase().includes(query)
            );
        });
    }, [query, categories, ui]);

    // Matching leader objects (e.g. Samsung, iPhone, Xiaomi)
    const matchingLeaders = useMemo(() => {
        if (!query) return [];
        if (hasRemoteResults) return remote.leaders;
        return leaders
            .filter(([name]) => name.toLowerCase().includes(query))
            .slice(0, 3)
            .map(([name, supporters, diff, up, href]) => ({
                name,
                supporters,
                diff,
                up,
                href,
            }));
    }, [query, leaders, hasRemoteResults, remote.leaders]);

    // Matching users (server-side only)
    const matchingUsers = useMemo(
        () => (query && hasRemoteResults ? remote.users : []),
        [query, hasRemoteResults, remote.users]
    );

    // Flatten navigable items list for keyboard navigation
    const navigableItems = useMemo(() => {
        if (!query) {
            const list = [];
            recentSearches.forEach((item) => {
                list.push({ type: "recent", value: item, label: item });
            });
            trendingItems.forEach((item) => {
                list.push({ type: "trending", value: item.title, label: item.title, href: item.href });
            });
            return list;
        } else {
            const list = [
                {
                    type: "direct",
                    value: searchInput.trim(),
                    label: searchInput.trim(),
                },
            ];
            matchingPosts.forEach((post) => {
                list.push({
                    type: "post",
                    value: post.title,
                    label: post.title,
                    href: post.href,
                    post,
                });
            });
            if (matchingCategory) {
                list.push({
                    type: "category",
                    value: matchingCategory[0],
                    label: matchingCategory[0],
                });
            }
            matchingLeaders.forEach((leader) => {
                list.push({
                    type: "leader",
                    value: leader.name,
                    label: leader.name,
                    href: leader.href,
                });
            });
            matchingUsers.forEach((user) => {
                list.push({
                    type: "user",
                    value: user.name,
                    label: user.name,
                    href: user.href,
                });
            });
            return list;
        }
    }, [
        query,
        searchInput,
        recentSearches,
        trendingItems,
        matchingPosts,
        matchingCategory,
        matchingLeaders,
        matchingUsers,
    ]);

    // Execute search action and close
    function handleExecute(text, categoryToSelect = null) {
        if (!text && !categoryToSelect) return;
        const q = (text || "").trim();
        if (q) {
            setSearchInput(q);
            addRecentSearch(q);
        }
        setIsOpen(false);
        setActiveIndex(-1);
        const categoryHandled = categoryToSelect
            ? onSelectCategory?.(categoryToSelect)
            : false;
        if (!categoryHandled) {
            onSearchSubmit?.(q);
        }
        if (isMobile) {
            onClose?.();
        }
    }

    // Open a suggestion directly when it links to a page, otherwise search for it
    function openItem(label, href) {
        if (!href || !onOpenLink) {
            handleExecute(label);
            return;
        }
        addRecentSearch(label);
        setIsOpen(false);
        setActiveIndex(-1);
        onOpenLink(href);
        if (isMobile) {
            onClose?.();
        }
    }

    function selectNavigableItem(item) {
        if (item.type === "category") {
            handleExecute("", item.value);
        } else {
            openItem(item.value, item.href);
        }
    }

    // Keyboard navigation
    function handleKeyDown(event) {
        if (!isOpen) {
            if (event.key === "ArrowDown" || event.key === "Enter") {
                setIsOpen(true);
            }
            return;
        }

        if (event.key === "ArrowDown") {
            event.preventDefault();
            if (navigableItems.length === 0) return;
            setActiveIndex((prev) => (prev + 1 >= navigableItems.length ? 0 : prev + 1));
        } else if (event.key === "ArrowUp") {
            event.preventDefault();
            if (navigableItems.length === 0) return;
            setActiveIndex((prev) => (prev <= 0 ? navigableItems.length - 1 : prev - 1));
        } else if (event.key === "Enter") {
            event.preventDefault();
            if (activeIndex >= 0 && navigableItems[activeIndex]) {
                selectNavigableItem(navigableItems[activeIndex]);
            } else {
                handleExecute(searchInput);
            }
        } else if (event.key === "Escape") {
            event.preventDefault();
            setIsOpen(false);
            setActiveIndex(-1);
            inputRef.current?.blur();
            if (isMobile) {
                onClose?.();
            }
        }
    }

    // Global shortcut Ctrl+K / Cmd+K and '/' listener
    useEffect(() => {
        function handleGlobalShortcut(event) {
            const isTargetInput =
                document.activeElement &&
                (document.activeElement.tagName === "INPUT" ||
                    document.activeElement.tagName === "TEXTAREA" ||
                    document.activeElement.isContentEditable);

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
                event.preventDefault();
                inputRef.current?.focus();
                setIsOpen(true);
            } else if (event.key === "/" && !isTargetInput) {
                event.preventDefault();
                inputRef.current?.focus();
                setIsOpen(true);
            }
        }

        window.addEventListener("keydown", handleGlobalShortcut);
        return () => window.removeEventListener("keydown", handleGlobalShortcut);
    }, []);

    // Click outside listener
    useEffect(() => {
        function handleClickOutside(event) {
            if (containerRef.current && !containerRef.current.contains(event.target)) {
                setIsOpen(false);
                setActiveIndex(-1);
            }
        }

        if (isOpen) {
            document.addEventListener("mousedown", handleClickOutside);
            document.addEventListener("touchstart", handleClickOutside);
        }

        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
            document.removeEventListener("touchstart", handleClickOutside);
        };
    }, [isOpen]);

    // Labels with full ID / EN internationalization
    const labels = useMemo(() => {
        const isId = language === "id";
        return {
            searchPlaceholder:
                ui.searchPlaceholder ||
                (isId
                    ? "Cari produk, tempat, atau topik..."
                    : "Search products, places, or topics..."),
            searchRanking: ui.searchRanking || (isId ? "Cari ranking" : "Search rankings"),
            searchButton: ui.search || (isId ? "Cari" : "Search"),
            recentSearches: isId ? "Pencarian Terkini" : "Recent Searches",
            clearAll: isId ? "Hapus Semua" : "Clear All",
            trendingTitle: isId ? "Sedang Tren di Votmee" : "Trending on Votmee",
            browseCategories: isId ? "Jelajahi Kategori" : "Browse Categories",
            categoryHint: isId ? "Lihat semua ranking dalam kategori ini" : "See all rankings in this category",
            searching: isId ? "Mencari..." : "Searching...",
            viewObject: isId ? "Lihat halaman objek" : "View object page",
            matchingUsers: isId ? "Pengguna" : "Users",
            rankingsCount: isId ? "ranking" : "rankings",
            moreCategories: isId ? "Lainnya" : "More",
            fewerCategories: isId ? "Tampilkan lebih sedikit" : "Show less",
            matchingRankings: isId ? "Rekomendasi Ranking" : "Matching Rankings",
            matchingCategory: isId ? "Kategori Terkait" : "Related Category",
            matchingObject: isId ? "Objek Peringkat" : "Leaderboard Object",
            searchAllFor: isId ? "Cari semua tentang" : "Search all for",
            inAllCategories: isId ? "di semua ranking" : "across all rankings",
            pressEnter: isId ? "Pilih / Cari" : "Select / Search",
            navigate: isId ? "Navigasi" : "Navigate",
            close: isId ? "Tutup" : "Close",
            noRecent: isId ? "Belum ada riwayat pencarian" : "No recent searches yet",
            noResults: isId ? "Tidak ada ranking ditemukan untuk" : "No rankings found for",
            tryOther: isId
                ? "Coba kata kunci lain atau jelajahi kategori di bawah ini:"
                : "Try another keyword or browse popular categories below:",
            clearQuery: isId ? "Hapus pencarian" : "Clear search",
            votes: isId ? "suara" : "votes",
            supporters: isId ? "pendukung" : "supporters",
        };
    }, [language, ui]);

    return (
        <div
            className={`vm-search-container ${isMobile ? "vm-search-mobile-container" : ""}`}
            ref={containerRef}
        >
            {/* Main Search Input Bar */}
            <form
                className={`vm-header-search ${isOpen ? "is-focused" : ""} ${
                    isMobile ? "vm-mobile-search-bar" : ""
                }`}
                onSubmit={(e) => {
                    e.preventDefault();
                    if (activeIndex >= 0 && navigableItems[activeIndex]) {
                        selectNavigableItem(navigableItems[activeIndex]);
                    } else {
                        handleExecute(searchInput);
                    }
                }}
                role="search"
                aria-haspopup="listbox"
                aria-expanded={isOpen}
            >
                <span className="vm-search-lead-icon">
                    <MenuIcon name="search" size={isMobile ? 18 : 17} />
                </span>

                <label className="vm-sr-only" htmlFor={isMobile ? "vm-mobile-search" : "vm-search"}>
                    {labels.searchRanking}
                </label>

                <input
                    ref={inputRef}
                    id={isMobile ? "vm-mobile-search" : "vm-search"}
                    type="search"
                    autoComplete="off"
                    autoCorrect="off"
                    autoCapitalize="off"
                    spellCheck="false"
                    placeholder={labels.searchPlaceholder}
                    value={searchInput}
                    autoFocus={autoFocus}
                    onChange={(e) => {
                        setSearchInput(e.target.value);
                        setIsOpen(true);
                        setActiveIndex(-1);
                    }}
                    onFocus={() => {
                        setIsOpen(true);
                    }}
                    onKeyDown={handleKeyDown}
                />

                {/* Clear button when input has value */}
                {searchInput.length > 0 && (
                    <button
                        type="button"
                        className="vm-search-clear-btn"
                        aria-label={labels.clearQuery}
                        title={labels.clearQuery}
                        onClick={(e) => {
                            e.preventDefault();
                            setSearchInput("");
                            setActiveIndex(-1);
                            inputRef.current?.focus();
                        }}
                    >
                        <MenuIcon name="close" size={13} />
                    </button>
                )}

                {/* Single Ctrl K badge on desktop when input is empty */}
                {!isMobile && searchInput.length === 0 && (
                    <kbd className="vm-search-kbd" title="Ctrl K untuk mencari">
                        Ctrl K
                    </kbd>
                )}

                {/* Mobile action button */}
                {isMobile && (
                    <button type="submit" className="vm-search-submit-btn">
                        {labels.searchButton}
                    </button>
                )}
            </form>

            {/* Professional Search Menu Dropdown */}
            {isOpen && (
                <div
                    className="vm-search-dropdown"
                    ref={listRef}
                    role="listbox"
                    aria-label={labels.searchRanking}
                >
                    {/* MODE A: Empty input -> Recent Searches, Trending, and Quick Categories */}
                    {!query && (
                        <>
                            {/* Section: Recent Searches */}
                            {recentSearches.length > 0 && (
                                <div className="vm-search-section">
                                    <div className="vm-search-section-header">
                                        <span className="vm-search-section-title">
                                            <MenuIcon name="history" size={13} />
                                            {labels.recentSearches}
                                        </span>
                                        <button
                                            type="button"
                                            className="vm-search-clear-all"
                                            onClick={clearAllRecents}
                                        >
                                            {labels.clearAll}
                                        </button>
                                    </div>
                                    <div className="vm-search-items-list">
                                        {recentSearches.map((item, idx) => {
                                            const itemIndex = idx;
                                            const isActive = activeIndex === itemIndex;
                                            return (
                                                <div
                                                    key={`recent-${item}`}
                                                    className={`vm-search-item ${
                                                        isActive ? "is-active" : ""
                                                    }`}
                                                    onClick={() => handleExecute(item)}
                                                    role="option"
                                                    aria-selected={isActive}
                                                >
                                                    <div className="vm-search-item-left">
                                                        <span className="vm-search-item-icon vm-search-icon-recent">
                                                            <MenuIcon name="history" size={14} />
                                                        </span>
                                                        <div className="vm-search-item-text">
                                                            <div className="vm-search-item-title">
                                                                {item}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        className="vm-search-item-remove"
                                                        title="Hapus"
                                                        aria-label={`Hapus ${item}`}
                                                        onClick={(e) => removeRecentSearch(e, item)}
                                                    >
                                                        <MenuIcon name="close" size={12} />
                                                    </button>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Section: Trending on Votmee */}
                            <div className="vm-search-section">
                                <div className="vm-search-section-header">
                                    <span className="vm-search-section-title">
                                        <span className="vm-live-indicator" aria-hidden="true">
                                            <span className="vm-live-ping"></span>
                                            <span className="vm-live-core"></span>
                                        </span>
                                        <MenuIcon name="flame" size={13} className="vm-icon-flame" />
                                        {labels.trendingTitle}
                                    </span>
                                </div>
                                <div className="vm-search-items-list">
                                    {trendingItems.map((item, idx) => {
                                        const itemIndex = recentSearches.length + idx;
                                        const isActive = activeIndex === itemIndex;
                                        return (
                                            <div
                                                key={`trend-${item.id}`}
                                                className={`vm-search-item ${
                                                    isActive ? "is-active" : ""
                                                }`}
                                                onClick={() => openItem(item.title, item.href)}
                                                role="option"
                                                aria-selected={isActive}
                                            >
                                                <div className="vm-search-item-left">
                                                    <span
                                                        className={`vm-trend-rank vm-trend-rank-${
                                                            idx + 1
                                                        }`}
                                                    >
                                                        #{idx + 1}
                                                    </span>
                                                    <div className="vm-search-item-text">
                                                        <div className="vm-search-item-title">
                                                            {item.title}
                                                        </div>
                                                        <div className="vm-search-item-desc">
                                                            {ui.categories && ui.categories[item.category]
                                                                ? ui.categories[item.category]
                                                                : item.category}{" "}
                                                            • {item.subtitle}
                                                        </div>
                                                    </div>
                                                </div>
                                                <span className="vm-search-item-arrow">
                                                    <MenuIcon name="arrow" size={14} />
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Section: Browse Categories */}
                            <div className="vm-search-section vm-search-cat-section">
                                <div className="vm-search-section-header">
                                    <span className="vm-search-section-title">
                                        <MenuIcon name="compass" size={13} />
                                        {labels.browseCategories}
                                    </span>
                                </div>
                                <div className={`vm-search-cat-grid${showAllCategories ? " is-expanded" : ""}`}>
                                    {(showAllCategories ? categories : categories.slice(0, BROWSE_CATEGORY_LIMIT)).map(([name, iconName]) => {
                                        const label =
                                            ui.categories && ui.categories[name]
                                                ? ui.categories[name]
                                                : name;
                                        return (
                                            <button
                                                key={`cat-${name}`}
                                                type="button"
                                                className="vm-search-cat-chip"
                                                onClick={() => handleExecute("", name)}
                                            >
                                                <span className="vm-search-cat-icon">
                                                    <MenuIcon name={iconName} size={14} />
                                                </span>
                                                <span>{label}</span>
                                            </button>
                                        );
                                    })}
                                    {categories.length > BROWSE_CATEGORY_LIMIT && (
                                        <button
                                            type="button"
                                            className="vm-search-cat-chip vm-search-cat-more"
                                            aria-expanded={showAllCategories}
                                            onClick={() => setShowAllCategories((value) => !value)}
                                        >
                                            {showAllCategories
                                                ? labels.fewerCategories
                                                : `${labels.moreCategories} (+${categories.length - BROWSE_CATEGORY_LIMIT})`}
                                        </button>
                                    )}
                                </div>
                            </div>
                        </>
                    )}

                    {/* MODE B: User typing query -> Direct Search, Live Matching Rankings, and Categories */}
                    {query && (
                        <>
                            {/* Primary Action: Direct Search Submit */}
                            <div className="vm-search-section vm-search-direct-section">
                                <div
                                    className={`vm-search-item vm-search-item-direct ${
                                        activeIndex === 0 ? "is-active" : ""
                                    }`}
                                    onClick={() => handleExecute(searchInput)}
                                    role="option"
                                    aria-selected={activeIndex === 0}
                                >
                                    <div className="vm-search-item-left">
                                        <span className="vm-search-item-icon vm-search-icon-direct">
                                            <MenuIcon name="search" size={15} />
                                        </span>
                                        <div className="vm-search-item-text">
                                            <div className="vm-search-item-title">
                                                {labels.searchAllFor}{" "}
                                                <strong className="vm-search-keyword">
                                                    "{searchInput.trim()}"
                                                </strong>
                                            </div>
                                            <div className="vm-search-item-desc">
                                                {isSearching ? labels.searching : labels.inAllCategories}
                                            </div>
                                        </div>
                                    </div>
                                    <span className="vm-search-item-arrow">
                                        <MenuIcon name="arrow" size={14} />
                                    </span>
                                </div>
                            </div>

                            {/* Section: Live Matching Rankings */}
                            {matchingPosts.length > 0 && (
                                <div className="vm-search-section">
                                    <div className="vm-search-section-header">
                                        <span className="vm-search-section-title">
                                            <MenuIcon name="sparkle" size={13} />
                                            {labels.matchingRankings}
                                        </span>
                                        <span className="vm-search-count-badge">
                                            {matchingPosts.length}
                                        </span>
                                    </div>
                                    <div className="vm-search-items-list">
                                        {matchingPosts.map((post, idx) => {
                                            const itemIndex = 1 + idx;
                                            const isActive = activeIndex === itemIndex;
                                            const catIcon =
                                                categories.find(([c]) => c === post.category)?.[1] ||
                                                "chip";
                                            return (
                                                <div
                                                    key={`match-post-${post.id}`}
                                                    className={`vm-search-item ${
                                                        isActive ? "is-active" : ""
                                                    }`}
                                                    onClick={() => openItem(post.title, post.href)}
                                                    role="option"
                                                    aria-selected={isActive}
                                                >
                                                    <div className="vm-search-item-left">
                                                        <span
                                                            className={`vm-search-item-icon vm-search-cat-badge-${(post.category || "").toLowerCase()}`}
                                                        >
                                                            <MenuIcon name={catIcon} size={15} />
                                                        </span>
                                                        <div className="vm-search-item-text">
                                                            <div className="vm-search-item-title">
                                                                {highlightMatch(post.title, query)}
                                                            </div>
                                                            <div className="vm-search-item-desc">
                                                                <span className="vm-search-post-meta">
                                                                    {ui.categories &&
                                                                    ui.categories[post.category]
                                                                        ? ui.categories[post.category]
                                                                        : post.category}
                                                                    {post.author && ` • ${post.author}`}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span className="vm-search-vote-pill">
                                                        {post.votes} {labels.votes}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Section: Matching Category */}
                            {matchingCategory && (
                                <div className="vm-search-section">
                                    <div className="vm-search-section-header">
                                        <span className="vm-search-section-title">
                                            <MenuIcon name="compass" size={13} />
                                            {labels.matchingCategory}
                                        </span>
                                    </div>
                                    <div className="vm-search-items-list">
                                        {(() => {
                                            const [catName, catIcon] = matchingCategory;
                                            const itemIndex = 1 + matchingPosts.length;
                                            const isActive = activeIndex === itemIndex;
                                            const catLabel =
                                                ui.categories && ui.categories[catName]
                                                    ? ui.categories[catName]
                                                    : catName;
                                            return (
                                                <div
                                                    className={`vm-search-item ${
                                                        isActive ? "is-active" : ""
                                                    }`}
                                                    onClick={() => handleExecute("", catName)}
                                                    role="option"
                                                    aria-selected={isActive}
                                                >
                                                    <div className="vm-search-item-left">
                                                        <span className="vm-search-item-icon vm-search-icon-cat">
                                                            <MenuIcon name={catIcon} size={15} />
                                                        </span>
                                                        <div className="vm-search-item-text">
                                                            <div className="vm-search-item-title">
                                                                {highlightMatch(catLabel, query)}
                                                            </div>
                                                            <div className="vm-search-item-desc">
                                                                {labels.categoryHint}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span className="vm-search-item-arrow">
                                                        <MenuIcon name="arrow" size={14} />
                                                    </span>
                                                </div>
                                            );
                                        })()}
                                    </div>
                                </div>
                            )}

                            {/* Section: Matching Leader Objects */}
                            {matchingLeaders.length > 0 && (
                                <div className="vm-search-section">
                                    <div className="vm-search-section-header">
                                        <span className="vm-search-section-title">
                                            <MenuIcon name="trophy" size={13} />
                                            {labels.matchingObject}
                                        </span>
                                    </div>
                                    <div className="vm-search-items-list">
                                        {matchingLeaders.map((lead, idx) => {
                                            const itemIndex =
                                                1 +
                                                matchingPosts.length +
                                                (matchingCategory ? 1 : 0) +
                                                idx;
                                            const isActive = activeIndex === itemIndex;
                                            return (
                                                <div
                                                    key={`leader-${lead.name}`}
                                                    className={`vm-search-item ${
                                                        isActive ? "is-active" : ""
                                                    }`}
                                                    onClick={() => openItem(lead.name, lead.href)}
                                                    role="option"
                                                    aria-selected={isActive}
                                                >
                                                    <div className="vm-search-item-left">
                                                        <span className="vm-search-item-icon vm-search-icon-leader">
                                                            <MenuIcon name="trophy" size={14} />
                                                        </span>
                                                        <div className="vm-search-item-text">
                                                            <div className="vm-search-item-title">
                                                                {highlightMatch(lead.name, query)}
                                                            </div>
                                                            <div className="vm-search-item-desc">
                                                                {lead.supporters
                                                                    ? `${lead.supporters} ${labels.supporters}`
                                                                    : labels.viewObject}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span className="vm-search-item-arrow">
                                                        <MenuIcon name="arrow" size={14} />
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Section: Matching Users */}
                            {matchingUsers.length > 0 && (
                                <div className="vm-search-section">
                                    <div className="vm-search-section-header">
                                        <span className="vm-search-section-title">
                                            <MenuIcon name="user" size={13} />
                                            {labels.matchingUsers}
                                        </span>
                                    </div>
                                    <div className="vm-search-items-list">
                                        {matchingUsers.map((user, idx) => {
                                            const itemIndex =
                                                1 +
                                                matchingPosts.length +
                                                (matchingCategory ? 1 : 0) +
                                                matchingLeaders.length +
                                                idx;
                                            const isActive = activeIndex === itemIndex;
                                            return (
                                                <div
                                                    key={`user-${user.username}`}
                                                    className={`vm-search-item ${
                                                        isActive ? "is-active" : ""
                                                    }`}
                                                    onClick={() => openItem(user.name, user.href)}
                                                    role="option"
                                                    aria-selected={isActive}
                                                >
                                                    <div className="vm-search-item-left">
                                                        <AvatarFrame frame={user.avatarFrame} size="sm">
                                                            <span className="vm-search-item-icon vm-search-icon-user">
                                                                {user.avatar ? (
                                                                    <img src={user.avatar} alt="" loading="lazy" decoding="async" />
                                                                ) : (
                                                                    user.name.slice(0, 1).toUpperCase()
                                                                )}
                                                            </span>
                                                        </AvatarFrame>
                                                        <div className="vm-search-item-text">
                                                            <div className="vm-search-item-title">
                                                                {highlightMatch(user.name, query.replace(/^@/, ""))}
                                                            </div>
                                                            <div className="vm-search-item-desc">
                                                                @{user.username} • {user.rankings} {labels.rankingsCount}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <span className="vm-search-item-arrow">
                                                        <MenuIcon name="arrow" size={14} />
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {/* Empty state if nothing matches */}
                            {!isSearching &&
                                matchingPosts.length === 0 &&
                                !matchingCategory &&
                                matchingLeaders.length === 0 &&
                                matchingUsers.length === 0 && (
                                    <div className="vm-search-empty-state">
                                        <div className="vm-search-empty-icon">
                                            <MenuIcon name="search" size={24} />
                                        </div>
                                        <div className="vm-search-empty-title">
                                            {labels.noResults} "{searchInput}"
                                        </div>
                                        <div className="vm-search-empty-desc">
                                            {labels.tryOther}
                                        </div>
                                        <div className="vm-search-cat-grid vm-search-empty-cats">
                                            {categories.slice(0, 5).map(([name, iconName]) => {
                                                const label =
                                                    ui.categories && ui.categories[name]
                                                        ? ui.categories[name]
                                                        : name;
                                                return (
                                                    <button
                                                        key={`empty-cat-${name}`}
                                                        type="button"
                                                        className="vm-search-cat-chip"
                                                        onClick={() => handleExecute("", name)}
                                                    >
                                                        <span className="vm-search-cat-icon">
                                                            <MenuIcon name={iconName} size={14} />
                                                        </span>
                                                        <span>{label}</span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                        </>
                    )}

                </div>
            )}
        </div>
    );
}
