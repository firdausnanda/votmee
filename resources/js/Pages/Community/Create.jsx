import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { api, dictionary, Layout, PageHead, path } from './Shared';

export default function Create({ locale, categories, leaders }) {
    const t = dictionary(locale);
    const [step, setStep] = useState(1);
    const [title, setTitle] = useState('');
    const [criteria, setCriteria] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [languageCode, setLanguageCode] = useState(locale);
    const [regionCode, setRegionCode] = useState('GLOBAL');
    const [query, setQuery] = useState('');
    const [options, setOptions] = useState([]);
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
    const [busy, setBusy] = useState(false);

    useEffect(() => { api('/backend/brands').then(setBrands).catch(() => setBrands([])); }, []);

    useEffect(() => {
        if (title.trim().length < 5) { setSimilar([]); return; }
        const timer = window.setTimeout(() => {
            api(`/backend/rankings?q=${encodeURIComponent(title.trim())}`).then(result => setSimilar(result.data.slice(0, 3))).catch(() => setSimilar([]));
        }, 350);
        return () => window.clearTimeout(timer);
    }, [title]);

    useEffect(() => {
        if (query.trim().length < 2 || !categoryId) { setOptions([]); return; }
        const timer = window.setTimeout(() => {
            api(`/backend/objects?q=${encodeURIComponent(query.trim())}&category_id=${categoryId}`).then(setOptions).catch(() => setOptions([]));
        }, 250);
        return () => window.clearTimeout(timer);
    }, [query, categoryId]);

    const select = (object) => {
        if (!selected.some(item => item.id === object.id)) setSelected([...selected, object]);
        setQuery(''); setOptions([]);
    };

    const propose = async (event) => {
        event.preventDefault(); setBusy(true); setMessage('');
        try {
            const object = await api('/backend/objects', 'POST', {
                canonical_name: objectName, category_id: Number(categoryId),
                ...(brandId ? { brand_id: Number(brandId) } : {}),
                ...(identityKey.trim() && identityValue.trim() ? { identity_attributes: { [identityKey.trim()]: identityValue.trim() } } : {}),
                ...(referenceUrl ? { reference_url: referenceUrl } : {}),
            });
            select(object); setProposing(false); setObjectName(''); setReferenceUrl(''); setBrandId(''); setIdentityKey(''); setIdentityValue(''); setMessage(t.pendingObject);
        } catch (error) { setMessage(error.message); } finally { setBusy(false); }
    };

    const publish = async () => {
        setBusy(true); setMessage('');
        try {
            const post = await api('/backend/rankings', 'POST', {
                title, criteria, category_id: Number(categoryId), language_code: languageCode,
                region_code: regionCode.trim().toUpperCase() || 'GLOBAL', object_ids: selected.map(item => item.id),
            });
            router.visit(path(locale, `/ranking/${post.slug}`));
        } catch (error) { setMessage(error.message); } finally { setBusy(false); }
    };

    return <Layout locale={locale} categories={categories} leaders={leaders}>
        <PageHead title={t.create} locale={locale} />
        <div className="vt-page-heading"><div><p className="vt-eyebrow">{t.community}</p><h1>{t.create}</h1></div></div>
        <div className="vt-wizard-steps" aria-label={t.create}><span className={step === 1 ? 'active' : ''}>{t.stepOne}</span><span className={step === 2 ? 'active' : ''}>{t.stepTwo}</span><span className={step === 3 ? 'active' : ''}>{t.stepThree}</span></div>
        {!categories.length ? <p className="vt-form-message">{t.noCategories}</p> : <>
            {step === 1 && <form className="vt-wizard-panel" onSubmit={event => { event.preventDefault(); setStep(2); }}>
                <label>{t.title}<input value={title} minLength={5} maxLength={255} onChange={e => setTitle(e.target.value)} required /></label>
                {similar.length > 0 && <div className="vt-similar"><strong>{locale === 'id' ? 'Ranking serupa' : 'Similar rankings'}</strong>{similar.map(post => <a key={post.id} href={path(locale, `/ranking/${post.slug}`)}>{post.title} ↗</a>)}</div>}
                <label>{t.category}<select value={categoryId} onChange={e => { setCategoryId(e.target.value); setSelected([]); }} required><option value="">{t.allCategories}</option>{categories.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
                <label>{t.criteria}<textarea value={criteria} minLength={10} maxLength={3000} rows={5} onChange={e => setCriteria(e.target.value)} required /></label>
                <div className="vt-field-pair"><label>{t.language}<select value={languageCode} onChange={e => setLanguageCode(e.target.value)}><option value="id">Indonesia</option><option value="en">English</option></select></label>
                    <label>{t.region}<input value={regionCode} maxLength={16} onChange={e => setRegionCode(e.target.value)} placeholder="GLOBAL" /></label></div>
                <div className="vt-wizard-actions"><span /><button type="submit">{t.next} →</button></div>
            </form>}
            {step === 2 && <div className="vt-wizard-panel">
                <label>{t.candidateSearch}<input value={query} maxLength={120} onChange={e => setQuery(e.target.value)} placeholder={t.candidateSearch} /></label>
                {options.length > 0 && <div className="vt-search-options">{options.map(object => <button key={object.id} type="button" disabled={selected.some(item => item.id === object.id)} onClick={() => select(object)}><span>{object.canonical_name}<small>{object.status === 'verified' ? t.verified : t.pending}</small></span><b>＋</b></button>)}</div>}
                <div className="vt-selected"><strong>{t.selectedCandidates} ({selected.length})</strong>{selected.map((object, index) => <div key={object.id}><span>{String(index + 1).padStart(2, '0')}</span><b>{object.canonical_name}</b><button aria-label={`${t.cancel} ${object.canonical_name}`} onClick={() => setSelected(selected.filter(item => item.id !== object.id))}>×</button></div>)}</div>
                <button className="vt-text-button" onClick={() => { setProposing(true); setMessage(''); }}>＋ {t.proposeObject}</button>
                {proposing && <form className="vt-propose-form" onSubmit={propose}><h3>{t.proposeObject}</h3>
                    <label>{t.objectName}<input value={objectName} minLength={2} maxLength={255} onChange={e => setObjectName(e.target.value)} required /></label>
                    {brands.length > 0 && <label>{locale === 'id' ? 'Merek (opsional)' : 'Brand (optional)'}<select value={brandId} onChange={e => setBrandId(e.target.value)}><option value="">—</option>{brands.map(brand => <option key={brand.id} value={brand.id}>{brand.name}</option>)}</select></label>}
                    <div className="vt-field-pair"><label>{locale === 'id' ? 'Jenis atribut identitas' : 'Identity attribute'}<input value={identityKey} maxLength={60} onChange={e => setIdentityKey(e.target.value)} placeholder={locale === 'id' ? 'mis. model, varian, lokasi' : 'e.g. model, variant, location'} /></label>
                        <label>{locale === 'id' ? 'Nilai atribut' : 'Attribute value'}<input value={identityValue} maxLength={120} onChange={e => setIdentityValue(e.target.value)} /></label></div>
                    <label>{t.referenceUrl}<input type="url" value={referenceUrl} onChange={e => setReferenceUrl(e.target.value)} placeholder="https://" /></label>
                    <div className="vt-modal-actions"><button type="button" onClick={() => setProposing(false)}>{t.cancel}</button><button disabled={busy}>{t.submitObject}</button></div>
                </form>}
                {message && <p className="vt-form-message" role="status">{message}</p>}
                <div className="vt-wizard-actions"><button onClick={() => setStep(1)}>← {t.back}</button><button disabled={selected.length < 2} onClick={() => setStep(3)}>{t.next} →</button></div>
                {selected.length < 2 && <p className="vt-muted">{t.atLeastTwo}</p>}
            </div>}
            {step === 3 && <div className="vt-wizard-panel"><p className="vt-eyebrow">{t.stepThree}</p><h2>{title}</h2><p>{criteria}</p>
                <dl className="vt-review-list"><div><dt>{t.category}</dt><dd>{categories.find(item => item.id === Number(categoryId))?.name}</dd></div><div><dt>{t.language}</dt><dd>{languageCode.toUpperCase()}</dd></div><div><dt>{t.region}</dt><dd>{regionCode}</dd></div></dl>
                <div className="vt-selected"><strong>{t.selectedCandidates}</strong>{selected.map((object, index) => <div key={object.id}><span>{String(index + 1).padStart(2, '0')}</span><b>{object.canonical_name}</b></div>)}</div>
                {message && <p className="vt-form-message" role="alert">{message}</p>}
                <div className="vt-wizard-actions"><button onClick={() => setStep(2)}>← {t.back}</button><button disabled={busy} onClick={publish}>{t.publish}</button></div>
            </div>}
        </>}
    </Layout>;
}
