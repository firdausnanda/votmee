import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import SelectBox from '../../Components/SelectBox';
import { api, dictionary, Layout, PageHead, path } from './Shared';

export default function Create({ locale, categories, leaders }) {
    const t = dictionary(locale);
    const isId = locale === 'id';

    const [step, setStep] = useState(1);
    const [title, setTitle] = useState('');
    const [criteria, setCriteria] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [languageCode, setLanguageCode] = useState(locale);
    const [regionCode, setRegionCode] = useState('GLOBAL');
    const [query, setQuery] = useState('');
    const [options, setOptions] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [searchError, setSearchError] = useState(false);
    const [searchAttempt, setSearchAttempt] = useState(0);
    const [selected, setSelected] = useState([]);
    const [similar, setSimilar] = useState([]);
    const [proposing, setProposing] = useState(false);
    const [objectName, setObjectName] = useState('');
    const [referenceUrl, setReferenceUrl] = useState('');
    const [brands, setBrands] = useState([]);
    const [brandId, setBrandId] = useState('');
    const [identityKey, setIdentityKey] = useState('');
    const [identityValue, setIdentityValue] = useState('');
    const [message, setMessage] = useState('');
    const [messageType, setMessageType] = useState('info');
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        api('/backend/brands').then(setBrands).catch(() => setBrands([]));
    }, []);

    useEffect(() => {
        if (title.trim().length < 5) {
            setSimilar([]);
            return;
        }
        let active = true;
        setSimilar([]);
        const timer = window.setTimeout(() => {
            api(`/backend/rankings?q=${encodeURIComponent(title.trim())}`)
                .then((result) => {
                    if (active) setSimilar(result.data.slice(0, 3));
                })
                .catch(() => {
                    if (active) setSimilar([]);
                });
        }, 350);
        return () => {
            active = false;
            window.clearTimeout(timer);
        };
    }, [title]);

    useEffect(() => {
        if (query.trim().length < 2 || !categoryId) {
            setOptions([]);
            setIsSearching(false);
            setSearchError(false);
            return;
        }
        let active = true;
        setIsSearching(true);
        setSearchError(false);
        const timer = window.setTimeout(() => {
            api(`/backend/objects?q=${encodeURIComponent(query.trim())}&category_id=${categoryId}`)
                .then((result) => {
                    if (active) setOptions(result);
                })
                .catch(() => {
                    if (active) {
                        setOptions([]);
                        setSearchError(true);
                    }
                })
                .finally(() => {
                    if (active) setIsSearching(false);
                });
        }, 250);
        return () => {
            active = false;
            window.clearTimeout(timer);
        };
    }, [query, categoryId, searchAttempt]);

    const select = (object) => {
        if (selected.length >= 20 || selected.some((item) => item.id === object.id)) return;
        setSelected([...selected, object]);
        setQuery('');
        setOptions([]);
    };

    const moveCandidate = (index, direction) => {
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= selected.length) return;
        const updated = [...selected];
        const [moved] = updated.splice(index, 1);
        updated.splice(targetIndex, 0, moved);
        setSelected(updated);
    };

    const propose = async (event) => {
        event.preventDefault();
        setBusy(true);
        setMessage('');
        try {
            const object = await api('/backend/objects', 'POST', {
                canonical_name: objectName,
                category_id: Number(categoryId),
                ...(brandId ? { brand_id: Number(brandId) } : {}),
                ...(identityKey.trim() && identityValue.trim()
                    ? { identity_attributes: { [identityKey.trim()]: identityValue.trim() } }
                    : {}),
                ...(referenceUrl ? { reference_url: referenceUrl } : {}),
            });
            select(object);
            setProposing(false);
            setObjectName('');
            setReferenceUrl('');
            setBrandId('');
            setIdentityKey('');
            setIdentityValue('');
            setMessageType('success');
            setMessage(t.pendingObject);
        } catch (error) {
            setMessageType('error');
            setMessage(error.message);
        } finally {
            setBusy(false);
        }
    };

    const publish = async () => {
        setBusy(true);
        setMessage('');
        try {
            const post = await api('/backend/rankings', 'POST', {
                title,
                criteria,
                category_id: Number(categoryId),
                language_code: languageCode,
                region_code: regionCode.trim().toUpperCase() || 'GLOBAL',
                object_ids: selected.map((item) => item.id),
            });
            router.visit(path(locale, `/ranking/${post.slug}`));
        } catch (error) {
            setMessageType('error');
            setMessage(error.message);
        } finally {
            setBusy(false);
        }
    };

    const categoryOptions = categories.map((cat) => ({
        value: String(cat.id),
        label: cat.name,
        badge: `#${cat.slug}`,
    }));

    const stepInfo = [
        {
            title: isId ? 'Topik & Detail' : 'Topic & Details',
            sub: isId ? 'Kategori & Kriteria' : 'Category & Criteria',
        },
        {
            title: isId ? 'Pilih Kandidat' : 'Candidates',
            sub: isId ? `${selected.length} / 20 dipilih` : `${selected.length} / 20 selected`,
        },
        {
            title: isId ? 'Tinjau & Publikasi' : 'Review & Publish',
            sub: isId ? 'Periksa sebelum tayang' : 'Final check',
        },
    ];

    const isStep1Valid = title.trim().length >= 5 && categoryId && criteria.trim().length >= 10;
    const isStep2Valid = selected.length >= 2;

    const canNavigateToStep = (targetStep) => {
        if (targetStep === 1) return true;
        if (targetStep === 2) return isStep1Valid;
        if (targetStep === 3) return isStep1Valid && isStep2Valid;
        return false;
    };

    const selectedCategory = categories.find((item) => item.id === Number(categoryId));

    return (
        <Layout locale={locale} categories={categories} leaders={leaders} active="create">
            <PageHead title={t.create} locale={locale} />
            <div className="vcr-page">
                {/* Header */}
                <header className="vcr-page-header">
                    <div className="vcr-eyebrow">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 8v8M8 12h8" />
                        </svg>
                        <span>{t.community} / {t.create}</span>
                    </div>
                    <h1>{t.create}</h1>
                    <p>
                        {isId
                            ? 'Susun topik pembanding, tentukan kandidat, dan terbitkan ranking untuk mendapatkan suara dari komunitas.'
                            : 'Set up comparison topics, pick candidates, and publish your ranking to gather community votes.'}
                    </p>
                </header>

                {/* Interactive Stepper Navigation */}
                <nav className="vcr-progress" aria-label={isId ? 'Tahapan membuat ranking' : 'Ranking creation steps'}>
                    <div className="vcr-progress-track" aria-hidden="true">
                        <div
                            className="vcr-progress-fill"
                            style={{ width: step === 1 ? '16%' : step === 2 ? '50%' : '100%' }}
                        />
                    </div>
                    <ol>
                        {stepInfo.map((info, index) => {
                            const stepNumber = index + 1;
                            const isCurrent = step === stepNumber;
                            const isComplete = step > stepNumber;
                            const isAccessible = canNavigateToStep(stepNumber);

                            return (
                                <li key={info.title}>
                                    <button
                                        type="button"
                                        className={`vcr-progress-btn ${isCurrent ? 'is-current' : ''} ${isComplete ? 'is-complete' : ''}`}
                                        disabled={!isAccessible && !isCurrent}
                                        onClick={() => {
                                            if (isAccessible) {
                                                setMessage('');
                                                setStep(stepNumber);
                                            }
                                        }}
                                        aria-current={isCurrent ? 'step' : undefined}
                                    >
                                        <span className="vcr-progress-number" aria-hidden="true">
                                            {isComplete ? '✓' : stepNumber}
                                        </span>
                                        <div className="vcr-progress-label">
                                            <span className="vcr-progress-title">{info.title}</span>
                                            <span className="vcr-progress-sub">{info.sub}</span>
                                        </div>
                                    </button>
                                </li>
                            );
                        })}
                    </ol>
                </nav>

                {!categories.length ? (
                    <p className="vcr-message is-info" role="status">{t.noCategories}</p>
                ) : (
                    <>
                        {/* STEP 1: Topik & Kriteria */}
                        {step === 1 && (
                            <form
                                className="vcr-panel"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    setMessage('');
                                    setStep(2);
                                }}
                            >
                                <div className="vcr-panel-header">
                                    <span className="vcr-panel-index" aria-hidden="true">01</span>
                                    <div>
                                        <h2>{isId ? 'Tentukan Topik & Kategori' : 'Define Topic & Category'}</h2>
                                        <p>
                                            {isId
                                                ? 'Judul yang tajam dan kriteria yang jelas memudahkan komunitas memahami konteks pembanding.'
                                                : 'A clear title and criteria help others understand the basis for comparison.'}
                                        </p>
                                    </div>
                                </div>

                                <div className="vcr-panel-body">
                                    {/* Title Field */}
                                    <label className="vcr-field">
                                        <div className="vcr-field-header">
                                            <span>{t.title} *</span>
                                            <span className={`vcr-char-counter ${title.length >= 5 ? 'is-valid' : ''}`}>
                                                {title.length} / 255
                                            </span>
                                        </div>
                                        <input
                                            value={title}
                                            minLength={5}
                                            maxLength={255}
                                            onChange={(event) => setTitle(event.target.value)}
                                            placeholder={isId ? 'Contoh: Laptop Terbaik untuk Pemrograman & Desain 2026' : 'Example: Best Laptops for Developers in 2026'}
                                            required
                                        />
                                        <small>
                                            {isId
                                                ? 'Gunakan judul yang spesifik, berorientasi nilai, dan mudah dipahami.'
                                                : 'Use a specific, descriptive, and understandable title.'}
                                        </small>
                                    </label>

                                    {/* Similar Rankings Detection Alert */}
                                    {similar.length > 0 && (
                                        <div className="vcr-similar" role="status">
                                            <strong>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ display: 'inline', verticalAlign: 'middle', marginRight: 5 }}>
                                                    <circle cx="12" cy="12" r="10" />
                                                    <path d="M12 16v-4M12 8h.01" />
                                                </svg>
                                                {isId ? 'Ditemukan ranking dengan topik serupa di komunitas:' : 'Similar rankings found in the community:'}
                                            </strong>
                                            {similar.map((post) => (
                                                <a key={post.id} href={path(locale, `/ranking/${post.slug}`)} target="_blank" rel="noreferrer">
                                                    <span>{post.title}</span>
                                                    <span aria-hidden="true">↗</span>
                                                </a>
                                            ))}
                                        </div>
                                    )}

                                    {/* Category SelectBox with Live Search */}
                                    <div className="vcr-field">
                                        <div className="vcr-field-header">
                                            <span>{t.category} *</span>
                                            {selectedCategory && (
                                                <span style={{ color: '#1044bb', fontSize: '11px', fontWeight: 750 }}>
                                                    #{selectedCategory.slug}
                                                </span>
                                            )}
                                        </div>
                                        <SelectBox
                                            value={categoryId}
                                            onChange={(val) => {
                                                setCategoryId(val);
                                                if (val !== categoryId) {
                                                    setSelected([]);
                                                }
                                            }}
                                            options={categoryOptions}
                                            placeholder={isId ? 'Pilih kategori...' : 'Select category...'}
                                            searchable={true}
                                            searchPlaceholder={isId ? 'Cari kategori...' : 'Search category...'}
                                            clearable={true}
                                            showBadgeInTrigger={true}
                                            align="left"
                                            className="vcr-select-box"
                                        />
                                        {!categoryId && (
                                            <small style={{ color: '#b45309' }}>
                                                {isId ? 'Pilih salah satu kategori untuk melanjutkan.' : 'Select a category to proceed.'}
                                            </small>
                                        )}
                                    </div>

                                    {/* Criteria Textarea (Clean, without prompt pills) */}
                                    <label className="vcr-field">
                                        <div className="vcr-field-header">
                                            <span>{t.criteria} *</span>
                                            <span className={`vcr-char-counter ${criteria.length >= 10 ? 'is-valid' : ''}`}>
                                                {criteria.length} / 3000
                                            </span>
                                        </div>
                                        <textarea
                                            value={criteria}
                                            minLength={10}
                                            maxLength={3000}
                                            rows={4}
                                            onChange={(event) => setCriteria(event.target.value)}
                                            placeholder={isId
                                                ? 'Jelaskan dasar perbandingan dan poin-poin utama yang dinilai (mis. performa, daya tahan baterai, harga pasar, kenyamanan pemakaian)...'
                                                : 'Explain what aspects are being judged so community votes remain grounded and fair (e.g. performance, battery life, price, user comfort)...'}
                                            required
                                        />
                                        <small>
                                            {isId
                                                ? 'Kriteria yang jelas membantu komunitas memberikan suara secara obyektif.'
                                                : 'Clear criteria guide voters to make thoughtful comparisons.'}
                                        </small>
                                    </label>

                                    {/* Language and Region */}
                                    <div className="vcr-field-pair">
                                        <label className="vcr-field">
                                            <span>{t.language}</span>
                                            <select value={languageCode} onChange={(event) => setLanguageCode(event.target.value)}>
                                                <option value="id">Bahasa Indonesia</option>
                                                <option value="en">English</option>
                                            </select>
                                        </label>
                                        <label className="vcr-field">
                                            <span>{t.region}</span>
                                            <input
                                                value={regionCode}
                                                maxLength={16}
                                                onChange={(event) => setRegionCode(event.target.value)}
                                                placeholder="GLOBAL"
                                            />
                                            <small>{isId ? 'Gunakan GLOBAL atau kode negara (ID, US, dll).' : 'Use GLOBAL or a country code (ID, US, etc).'}</small>
                                        </label>
                                    </div>
                                </div>

                                <div className="vcr-panel-footer">
                                    <span>{isId ? 'Langkah 1 dari 3: Topik & Detail' : 'Step 1 of 3: Topic & Details'}</span>
                                    <button
                                        className="vcr-primary"
                                        type="submit"
                                        disabled={!isStep1Valid}
                                    >
                                        <span>{t.next}</span>
                                        <span aria-hidden="true">→</span>
                                    </button>
                                </div>
                            </form>
                        )}

                        {/* STEP 2: Pilih Kandidat */}
                        {step === 2 && (
                            <div className="vcr-panel">
                                <div className="vcr-panel-header">
                                    <span className="vcr-panel-index" aria-hidden="true">02</span>
                                    <div>
                                        <h2>{isId ? 'Pilih & Susun Kandidat' : 'Choose & Order Candidates'}</h2>
                                        <p>
                                            {isId
                                                ? 'Cari dan tambahkan objek dalam kategori. Gunakan tombol panah untuk mengatur urutan awal.'
                                                : 'Search and add objects in the category. Use the arrow buttons to arrange the starting order.'}
                                        </p>
                                    </div>
                                </div>

                                <div className="vcr-panel-body">
                                    {/* Candidate Meter Bar */}
                                    <div className="vcr-candidate-meter">
                                        <div className="vcr-meter-row">
                                            <span>
                                                {isId ? 'Kandidat Terpilih' : 'Selected Candidates'}: {selected.length} / 20
                                            </span>
                                            <span>
                                                {selected.length < 2
                                                    ? (isId ? `Kurang ${2 - selected.length} lagi` : `Need ${2 - selected.length} more`)
                                                    : (isId ? 'Syarat minimum terpenuhi' : 'Minimum requirement met')}
                                            </span>
                                        </div>
                                        <div className="vcr-meter-bar">
                                            <div
                                                className={`vcr-meter-fill ${selected.length >= 2 ? 'is-valid' : ''} ${selected.length === 20 ? 'is-full' : ''}`}
                                                style={{ width: `${Math.min(100, (selected.length / 20) * 100)}%` }}
                                            />
                                        </div>
                                    </div>

                                    {/* Search Candidate Input */}
                                    <div className="vcr-search-section">
                                        <label className="vcr-field">
                                            <span>{t.candidateSearch}</span>
                                            <div className="vcr-search-bar">
                                                <span className="vcr-search-icon" aria-hidden="true">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                                                        <circle cx="11" cy="11" r="8" />
                                                        <path d="m21 21-4.3-4.3" />
                                                    </svg>
                                                </span>
                                                <input
                                                    type="search"
                                                    value={query}
                                                    maxLength={120}
                                                    onChange={(event) => setQuery(event.target.value)}
                                                    placeholder={isId ? 'Ketik nama objek atau kandidat...' : 'Type candidate or object name...'}
                                                />
                                                {query && (
                                                    <button
                                                        type="button"
                                                        className="vcr-search-clear"
                                                        onClick={() => setQuery('')}
                                                        aria-label={isId ? 'Hapus teks' : 'Clear search'}
                                                    >
                                                        ×
                                                    </button>
                                                )}
                                            </div>
                                        </label>

                                        {query.trim().length >= 2 && (
                                            <div className="vcr-search-results">
                                                {isSearching ? (
                                                    <p role="status">
                                                        <span className="vcr-spinner" style={{ display: 'inline-block', verticalAlign: 'middle', marginRight: 8, borderColor: '#94a3b8', borderTopColor: '#1044bb' }} />
                                                        {isId ? 'Mencari kandidat di database...' : 'Searching candidates in database...'}
                                                    </p>
                                                ) : searchError ? (
                                                    <div className="vcr-search-error">
                                                        <p role="alert">{isId ? 'Gagal mengambil data kandidat.' : 'Failed to search candidates.'}</p>
                                                        <button type="button" onClick={() => setSearchAttempt((a) => a + 1)}>
                                                            {isId ? 'Coba lagi' : 'Try again'}
                                                        </button>
                                                    </div>
                                                ) : options.length > 0 ? (
                                                    options.map((object) => {
                                                        const alreadySelected = selected.some((item) => item.id === object.id);
                                                        return (
                                                            <button
                                                                key={object.id}
                                                                type="button"
                                                                disabled={alreadySelected || selected.length >= 20}
                                                                onClick={() => select(object)}
                                                            >
                                                                <span>
                                                                    <strong>{object.canonical_name}</strong>
                                                                    <small>
                                                                        {alreadySelected
                                                                            ? (isId ? '✓ Sudah ditambahkan' : '✓ Already added')
                                                                            : object.status === 'verified'
                                                                                ? (isId ? 'Terverifikasi' : 'Verified')
                                                                                : (isId ? 'Menunggu verifikasi' : 'Pending')}
                                                                    </small>
                                                                </span>
                                                                <b aria-hidden="true">{alreadySelected ? '✓' : '＋'}</b>
                                                            </button>
                                                        );
                                                    })
                                                ) : (
                                                    <p role="status">
                                                        {isId ? 'Kandidat tidak ditemukan. Anda dapat mengajukan kandidat baru di bawah.' : 'No candidates found. You can propose a new one below.'}
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    {/* Selected Candidate List with Move Up/Down Controls */}
                                    <section className="vcr-selected" aria-labelledby="vcr-selected-title">
                                        <div className="vcr-selected-heading">
                                            <div>
                                                <h3 id="vcr-selected-title">{t.selectedCandidates}</h3>
                                                <p>{isId ? 'Urutan awal ranking (dapat diubah dengan tombol ↑ ↓):' : 'Starting order (reorder with ↑ ↓ buttons):'}</p>
                                            </div>
                                            <span style={{ fontWeight: 800 }}>{selected.length} / 20</span>
                                        </div>

                                        {selected.length ? (
                                            <ol>
                                                {selected.map((object, index) => (
                                                    <li key={object.id}>
                                                        <span className="vcr-candidate-number">
                                                            {String(index + 1).padStart(2, '0')}
                                                        </span>
                                                        <strong>{object.canonical_name}</strong>
                                                        <div className="vcr-candidate-actions">
                                                            <button
                                                                type="button"
                                                                className="vcr-reorder-btn"
                                                                disabled={index === 0}
                                                                onClick={() => moveCandidate(index, -1)}
                                                                aria-label={isId ? `Pindahkan ${object.canonical_name} ke atas` : `Move ${object.canonical_name} up`}
                                                                title={isId ? 'Pindah ke atas' : 'Move up'}
                                                            >
                                                                ↑
                                                            </button>
                                                            <button
                                                                type="button"
                                                                className="vcr-reorder-btn"
                                                                disabled={index === selected.length - 1}
                                                                onClick={() => moveCandidate(index, 1)}
                                                                aria-label={isId ? `Pindahkan ${object.canonical_name} ke bawah` : `Move ${object.canonical_name} down`}
                                                                title={isId ? 'Pindah ke bawah' : 'Move down'}
                                                            >
                                                                ↓
                                                            </button>
                                                            <button
                                                                type="button"
                                                                className="vcr-remove-btn"
                                                                onClick={() => setSelected(selected.filter((item) => item.id !== object.id))}
                                                                aria-label={`${isId ? 'Hapus' : 'Remove'} ${object.canonical_name}`}
                                                                title={isId ? 'Hapus kandidat' : 'Remove candidate'}
                                                            >
                                                                ×
                                                            </button>
                                                        </div>
                                                    </li>
                                                ))}
                                            </ol>
                                        ) : (
                                            <div className="vcr-selected-empty">
                                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" style={{ display: 'block', margin: '0 auto 8px', color: '#94a3b8' }}>
                                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                                    <path d="M12 8v8M8 12h8" />
                                                </svg>
                                                {isId
                                                    ? 'Belum ada kandidat terpilih. Gunakan pencarian di atas untuk menambahkan minimal 2 kandidat.'
                                                    : 'No candidates selected yet. Use the search above to add at least 2 candidates.'}
                                            </div>
                                        )}
                                    </section>

                                    {/* Propose Object Collapsible Card */}
                                    <div className="vcr-propose-entry">
                                        <div>
                                            <strong>{isId ? 'Kandidat belum tersedia di database?' : 'Candidate not in the database?'}</strong>
                                            <p>
                                                {selected.length >= 20
                                                    ? (isId ? 'Batas maksimal 20 kandidat telah tercapai.' : 'The 20-candidate limit has been reached.')
                                                    : (isId ? 'Ajukan objek baru secara instan untuk ditambahkan ke ranking ini.' : 'Propose a new object and add it directly to this ranking.')}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            disabled={selected.length >= 20}
                                            onClick={() => {
                                                setProposing(!proposing);
                                                setMessage('');
                                            }}
                                            aria-expanded={proposing}
                                        >
                                            {proposing ? t.cancel : `＋ ${t.proposeObject}`}
                                        </button>
                                    </div>

                                    {proposing && (
                                        <form id="vcr-propose-form" className="vcr-propose-form" onSubmit={propose}>
                                            <h3>{isId ? 'Pengajuan Objek Master Baru' : 'Propose New Master Object'}</h3>
                                            <label className="vcr-field">
                                                <span>{t.objectName} *</span>
                                                <input
                                                    value={objectName}
                                                    minLength={2}
                                                    maxLength={255}
                                                    onChange={(event) => setObjectName(event.target.value)}
                                                    placeholder={isId ? 'mis. Asus Zenbook S 14 OLED' : 'e.g. Asus Zenbook S 14 OLED'}
                                                    required
                                                />
                                            </label>

                                            {brands.length > 0 && (
                                                <label className="vcr-field">
                                                    <span>{isId ? 'Merek (Opsional)' : 'Brand (Optional)'}</span>
                                                    <select value={brandId} onChange={(event) => setBrandId(event.target.value)}>
                                                        <option value="">{isId ? '— Pilih Merek —' : '— Select Brand —'}</option>
                                                        {brands.map((brand) => (
                                                            <option key={brand.id} value={brand.id}>{brand.name}</option>
                                                        ))}
                                                    </select>
                                                </label>
                                            )}

                                            <div className="vcr-field-pair">
                                                <label className="vcr-field">
                                                    <span>{isId ? 'Atribut Identitas (Opsional)' : 'Identity Key (Optional)'}</span>
                                                    <input
                                                        value={identityKey}
                                                        maxLength={60}
                                                        onChange={(event) => setIdentityKey(event.target.value)}
                                                        placeholder={isId ? 'mis. varian, tahun, lokasi' : 'e.g. variant, year, location'}
                                                    />
                                                </label>
                                                <label className="vcr-field">
                                                    <span>{isId ? 'Nilai Atribut' : 'Attribute Value'}</span>
                                                    <input
                                                        value={identityValue}
                                                        maxLength={120}
                                                        onChange={(event) => setIdentityValue(event.target.value)}
                                                        placeholder={isId ? 'mis. 2026, Core Ultra 7' : 'e.g. 2026, Core Ultra 7'}
                                                    />
                                                </label>
                                            </div>

                                            <label className="vcr-field">
                                                <span>{t.referenceUrl}</span>
                                                <input
                                                    type="url"
                                                    value={referenceUrl}
                                                    onChange={(event) => setReferenceUrl(event.target.value)}
                                                    placeholder="https://..."
                                                />
                                            </label>

                                            <div className="vcr-propose-actions">
                                                <button type="button" onClick={() => setProposing(false)}>
                                                    {t.cancel}
                                                </button>
                                                <button type="submit" disabled={busy || selected.length >= 20 || !objectName.trim()}>
                                                    {busy ? (isId ? 'Mengirim...' : 'Submitting...') : t.submitObject}
                                                </button>
                                            </div>
                                        </form>
                                    )}

                                    {message && (
                                        <p className={`vcr-message is-${messageType}`} role={messageType === 'error' ? 'alert' : 'status'}>
                                            {message}
                                        </p>
                                    )}
                                </div>

                                <div className="vcr-panel-footer">
                                    <button
                                        type="button"
                                        className="vcr-secondary"
                                        disabled={busy}
                                        onClick={() => setStep(1)}
                                    >
                                        <span aria-hidden="true">←</span>
                                        <span>{t.back}</span>
                                    </button>
                                    <div>
                                        <span>
                                            {selected.length < 2
                                                ? t.atLeastTwo
                                                : isId
                                                    ? `${selected.length} kandidat siap ditinjau`
                                                    : `${selected.length} candidates ready`}
                                        </span>
                                        <button
                                            type="button"
                                            className="vcr-primary"
                                            disabled={selected.length < 2 || busy}
                                            onClick={() => {
                                                setMessage('');
                                                setStep(3);
                                            }}
                                        >
                                            <span>{t.next}</span>
                                            <span aria-hidden="true">→</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* STEP 3: Tinjau & Terbitkan */}
                        {step === 3 && (
                            <div className="vcr-panel">
                                <div className="vcr-panel-header">
                                    <span className="vcr-panel-index" aria-hidden="true">03</span>
                                    <div>
                                        <h2>{isId ? 'Tinjau & Konfirmasi Publikasi' : 'Review & Confirm Publication'}</h2>
                                        <p>
                                            {isId
                                                ? 'Periksa kembali susunan ranking Anda. Setelah diterbitkan, komunitas dapat langsung memberikan dukungan suara.'
                                                : 'Double-check your ranking details. Once published, the community can immediately cast support votes.'}
                                        </p>
                                    </div>
                                </div>

                                <div className="vcr-panel-body">
                                    {/* Realistic Ranking Card Mockup */}
                                    <div className="vcr-review-card">
                                        <div className="vcr-review-header">
                                            <span className="vcr-review-cat-tag">
                                                #{selectedCategory?.slug || selectedCategory?.name}
                                            </span>
                                            <h3 className="vcr-review-title">{title}</h3>
                                            <p className="vcr-review-criteria-box">{criteria}</p>
                                        </div>

                                        <dl className="vcr-review-meta">
                                            <div>
                                                <dt>{t.category}</dt>
                                                <dd>{selectedCategory?.name}</dd>
                                            </div>
                                            <div>
                                                <dt>{t.language}</dt>
                                                <dd>{languageCode === 'id' ? 'Indonesia' : 'English'}</dd>
                                            </div>
                                            <div>
                                                <dt>{t.region}</dt>
                                                <dd>{regionCode.trim().toUpperCase() || 'GLOBAL'}</dd>
                                            </div>
                                        </dl>

                                        {/* Candidate Ladder with Medals */}
                                        <section className="vcr-review-candidates" aria-labelledby="vcr-review-candidates-title">
                                            <div className="vcr-review-candidates-head">
                                                <h3 id="vcr-review-candidates-title">
                                                    {isId ? 'Urutan Awal Kandidat' : 'Starting Candidate Order'}
                                                </h3>
                                                <span style={{ fontSize: 12, fontWeight: 800, color: '#1044bb' }}>
                                                    {selected.length} {isId ? 'kandidat' : 'candidates'}
                                                </span>
                                            </div>

                                            <ol>
                                                {selected.map((object, index) => {
                                                    const pos = index + 1;
                                                    const posClass = pos === 1 ? 'vcr-pos-1' : pos === 2 ? 'vcr-pos-2' : pos === 3 ? 'vcr-pos-3' : 'vcr-pos-other';
                                                    const medal = pos === 1 ? '🥇' : pos === 2 ? '🥈' : pos === 3 ? '🥉' : null;

                                                    return (
                                                        <li key={object.id} className="vcr-review-item">
                                                            <span className={`vcr-rank-pos ${posClass}`}>
                                                                {medal || String(pos).padStart(2, '0')}
                                                            </span>
                                                            <span className="vcr-review-item-name">{object.canonical_name}</span>
                                                        </li>
                                                    );
                                                })}
                                            </ol>
                                        </section>
                                    </div>

                                    {/* Pre-Publish Checklist */}
                                    <div className="vcr-checklist">
                                        <span className="vcr-checklist-title">
                                            {isId ? 'Pemeriksaan Kesiapan Publikasi' : 'Publication Readiness Checklist'}
                                        </span>
                                        <div className="vcr-check-item">
                                            <span>✓</span>
                                            <span>{isId ? 'Judul spesifik dan memenuhi panjang minimal' : 'Title is descriptive and meets length requirements'}</span>
                                        </div>
                                        <div className="vcr-check-item">
                                            <span>✓</span>
                                            <span>{isId ? `Kategori dipilih (${selectedCategory?.name})` : `Category chosen (${selectedCategory?.name})`}</span>
                                        </div>
                                        <div className="vcr-check-item">
                                            <span>✓</span>
                                            <span>{isId ? 'Kriteria perbandingan telah diisi dengan jelas' : 'Evaluation criteria clearly detailed'}</span>
                                        </div>
                                        <div className="vcr-check-item">
                                            <span>✓</span>
                                            <span>{isId ? `${selected.length} kandidat siap bersaing di voting komunitas` : `${selected.length} candidates ready for community voting`}</span>
                                        </div>
                                    </div>

                                    {message && (
                                        <p className={`vcr-message is-${messageType}`} role={messageType === 'error' ? 'alert' : 'status'}>
                                            {message}
                                        </p>
                                    )}
                                </div>

                                <div className="vcr-panel-footer">
                                    <button
                                        type="button"
                                        className="vcr-secondary"
                                        disabled={busy}
                                        onClick={() => setStep(2)}
                                    >
                                        <span aria-hidden="true">←</span>
                                        <span>{isId ? 'Ubah Kandidat' : 'Edit Candidates'}</span>
                                    </button>
                                    <button
                                        type="button"
                                        className="vcr-primary"
                                        disabled={busy || selected.length < 2 || !title.trim()}
                                        onClick={publish}
                                    >
                                        {busy ? (
                                            <>
                                                <span className="vcr-spinner" aria-hidden="true" />
                                                <span>{isId ? 'Menerbitkan...' : 'Publishing...'}</span>
                                            </>
                                        ) : (
                                            <>
                                                <span>🚀 {t.publish}</span>
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
        </Layout>
    );
}
