import { Link, router } from '@inertiajs/react';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { Markdown } from '@tiptap/markdown';
import { useRef, useState } from 'react';
import { api, Layout, NavIcon, PageHead, path } from './Shared';
import ObjectImageField, { ALLOWED_TYPES, MAX_BYTES, optimizeImage } from './ObjectImageField';

const lines = (value) => value.split('\n').map((line) => line.trim()).filter(Boolean);

function ToolbarButton({ editor, active, label, onClick, children }) {
    return <button type="button" className={active ? 'is-active' : ''} aria-label={label} aria-pressed={active} disabled={!editor} onClick={onClick}>{children}</button>;
}

function RichEditor({ editor, locale }) {
    const isId = locale === 'id';
    const imageInputRef = useRef(null);
    const [imageBusy, setImageBusy] = useState(false);
    const [imageError, setImageError] = useState('');
    const selectedImage = useEditorState({
        editor,
        selector: ({ editor: current }) => (current?.isActive('image') ? { alt: current.getAttributes('image').alt ?? '' } : null),
    });

    const insertImage = async (event) => {
        const selected = event.target.files?.[0];
        event.target.value = '';
        if (!selected || !editor) return;
        setImageError('');

        if (!ALLOWED_TYPES.includes(selected.type)) {
            setImageError(isId ? 'Format harus JPG, PNG, atau WebP.' : 'The image must be a JPG, PNG, or WebP file.');
            return;
        }

        setImageBusy(true);
        try {
            const optimized = await optimizeImage(selected).catch(() => selected);
            if (optimized.size > MAX_BYTES) {
                throw new Error(isId ? 'Ukuran gambar masih di atas 4 MB. Pilih gambar yang lebih kecil.' : 'The image is still larger than 4 MB. Choose a smaller image.');
            }
            const payload = new FormData();
            payload.append('image', optimized);
            const uploaded = await api('/backend/content-images', 'POST', payload);
            editor.chain().focus().setImage({ src: uploaded.url, alt: '' }).run();
        } catch (failure) {
            setImageError(failure.message);
        } finally {
            setImageBusy(false);
        }
    };
    const setLink = () => {
        const current = editor.getAttributes('link').href ?? '';
        const url = window.prompt(isId ? 'URL tautan (https://)' : 'Link URL (https://)', current);
        if (url === null) return;
        if (url === '') {
            editor.chain().focus().unsetLink().run();
        } else if (/^https:\/\//i.test(url)) {
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        } else {
            window.alert(isId ? 'Gunakan URL https:// yang lengkap.' : 'Use a complete https:// URL.');
        }
    };

    return <div className="vob-richbox">
        <div className="vob-richbox-toolbar" role="toolbar" aria-label={isId ? 'Format tulisan' : 'Text formatting'}>
            <ToolbarButton editor={editor} active={editor?.isActive('bold')} label="Bold" onClick={() => editor.chain().focus().toggleBold().run()}><strong>B</strong></ToolbarButton>
            <ToolbarButton editor={editor} active={editor?.isActive('italic')} label="Italic" onClick={() => editor.chain().focus().toggleItalic().run()}><em>I</em></ToolbarButton>
            <span className="vob-toolbar-divider" />
            <ToolbarButton editor={editor} active={editor?.isActive('heading', { level: 2 })} label={isId ? 'Judul bagian' : 'Section heading'} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}>H2</ToolbarButton>
            <ToolbarButton editor={editor} active={editor?.isActive('heading', { level: 3 })} label={isId ? 'Subjudul' : 'Subheading'} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}>H3</ToolbarButton>
            <span className="vob-toolbar-divider" />
            <ToolbarButton editor={editor} active={editor?.isActive('bulletList')} label={isId ? 'Daftar poin' : 'Bullet list'} onClick={() => editor.chain().focus().toggleBulletList().run()}>• ≡</ToolbarButton>
            <ToolbarButton editor={editor} active={editor?.isActive('orderedList')} label={isId ? 'Daftar nomor' : 'Numbered list'} onClick={() => editor.chain().focus().toggleOrderedList().run()}>1. ≡</ToolbarButton>
            <ToolbarButton editor={editor} active={editor?.isActive('blockquote')} label={isId ? 'Kutipan' : 'Quote'} onClick={() => editor.chain().focus().toggleBlockquote().run()}>❝</ToolbarButton>
            <span className="vob-toolbar-divider" />
            <ToolbarButton editor={editor} active={editor?.isActive('link')} label={isId ? 'Tautan' : 'Link'} onClick={setLink}>↗</ToolbarButton>
            <ToolbarButton editor={imageBusy ? null : editor} active={false} label={imageBusy ? (isId ? 'Mengunggah gambar…' : 'Uploading image…') : (isId ? 'Sisipkan gambar' : 'Insert image')} onClick={() => imageInputRef.current?.click()}>
                {imageBusy ? '…' : <NavIcon name="image" size={15} />}
            </ToolbarButton>
            <input ref={imageInputRef} type="file" accept={ALLOWED_TYPES.join(',')} hidden onChange={insertImage} />
            <ToolbarButton editor={editor} active={false} label={isId ? 'Batalkan' : 'Undo'} onClick={() => editor.chain().focus().undo().run()}>↶</ToolbarButton>
            <ToolbarButton editor={editor} active={false} label={isId ? 'Ulangi' : 'Redo'} onClick={() => editor.chain().focus().redo().run()}>↷</ToolbarButton>
        </div>
        {selectedImage && <label className="vob-image-alt-bar">
            <span>{isId ? 'Deskripsi gambar' : 'Image description'} <small>{isId ? 'opsional · dibacakan untuk pembaca tunanetra' : 'optional · read aloud to blind readers'}</small></span>
            <input
                value={selectedImage.alt}
                onChange={(event) => editor.chain().updateAttributes('image', { alt: event.target.value.replace(/[[\]]/g, '') }).run()}
                maxLength={150}
                placeholder={isId ? 'Mis. Tampak belakang iPhone warna hitam' : 'E.g. Back of a black iPhone'}
            />
        </label>}
        <EditorContent editor={editor} aria-label={isId ? 'Isi tulisan' : 'Article body'} />
        {imageError && <p role="alert" className="vob-form-error">{imageError}</p>}
    </div>;
}

export default function ContentEditor({ locale, categories, leaders, object, mode, article, initial, reviewFeedback, reviewPending = false }) {
    const isId = locale === 'id';
    const isArticle = mode === 'article' || mode === 'article_revision';
    const isNew = mode === 'article';
    const [type, setType] = useState(article?.type ?? 'informative');
    const [title, setTitle] = useState(initial?.title ?? '');
    const [objectName, setObjectName] = useState(object.canonical_name);
    const [objectImage, setObjectImage] = useState(null);
    const [sources, setSources] = useState((initial?.sources ?? []).join('\n'));
    const [language, setLanguage] = useState(initial?.language_code ?? locale);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [bodyLength, setBodyLength] = useState((initial?.body ?? '').length);
    const objectUrl = path(locale, `/object/${object.slug}`);
    const heading = mode === 'submission'
        ? (isId ? 'Perbaiki pengajuan objek' : 'Revise object submission')
        : mode === 'summary'
        ? (isId ? 'Usulkan revisi ringkasan' : 'Suggest a summary revision')
        : isNew ? (isId ? 'Tulis artikel' : 'Write an article')
            : (isId ? 'Usulkan revisi artikel' : 'Suggest an article revision');
    const editor = useEditor({
        extensions: [StarterKit.configure({ link: { openOnClick: false, defaultProtocol: 'https' } }), Image.configure({ allowBase64: false }), Markdown],
        content: initial?.body ?? '',
        contentType: 'markdown',
        editorProps: { attributes: { class: 'vob-richbox-content', 'aria-label': isId ? 'Isi tulisan' : 'Article body' } },
        onUpdate: ({ editor: current }) => setBodyLength(current.getText().length),
    });

    const submit = async (event) => {
        event.preventDefault();
        if (!editor || reviewPending) return;
        const body = editor.getMarkdown().trim();
        const sourceUrls = lines(sources);
        if (editor.getText().trim().length < 30 || body.length > 15000) {
            setError(isId ? 'Isi tulisan harus 30–15.000 karakter.' : 'Content must be 30–15,000 characters.');
            return;
        }
        if ((!isArticle || type === 'informative') && sourceUrls.length === 0) {
            setError(isId ? 'Tambahkan minimal satu URL sumber.' : 'Add at least one source URL.');
            return;
        }
        setBusy(true);
        setError('');
        const payload = { body, body_format: 'markdown', language_code: language, sources: sourceUrls, ...(isArticle ? { title: title.trim() } : {}) };
        try {
            if (mode === 'submission') {
                const submission = new FormData();
                submission.append('_method', 'PATCH');
                submission.append('canonical_name', objectName.trim());
                submission.append('summary_body', body);
                submission.append('summary_body_format', 'markdown');
                submission.append('language_code', language);
                sourceUrls.forEach((url, index) => submission.append(`sources[${index}]`, url));
                if (objectImage) submission.append('image', objectImage);
                await api(`/backend/objects/${object.id}/submission`, 'POST', submission);
            } else if (mode === 'summary') {
                await api(`/backend/objects/${object.slug}/summary-revisions`, 'POST', payload);
            } else if (isNew) {
                await api(`/backend/objects/${object.slug}/articles`, 'POST', { ...payload, type });
            } else {
                await api(`/backend/articles/${article.id}`, 'PATCH', payload);
            }
            router.visit(objectUrl);
        } catch (failure) {
            setError(failure.message);
        } finally {
            setBusy(false);
        }
    };

    return <Layout locale={locale} categories={categories} leaders={leaders} active="object">
        <PageHead title={`${heading} - Votmee`} locale={locale} description={heading} />
        <main className="vob-editor-page">
            <nav className="vob-editor-breadcrumb" aria-label="Breadcrumb"><Link href={objectUrl}>{object.canonical_name}</Link><span>/</span><span>{heading}</span></nav>
            <header className="vob-editor-page-header"><div><span className="vob-editor-kicker">{isId ? 'KONTRIBUSI KOMUNITAS' : 'COMMUNITY CONTRIBUTION'}</span><h1>{heading}</h1><p>{isId ? 'Tulis dengan jelas dan periksa sumber sebelum dikirim ke moderator.' : 'Write clearly and check your sources before sending this for review.'}</p></div><span className="vob-editor-language">{language.toUpperCase()}</span></header>
            <form onSubmit={submit} className="vob-editor-layout">
                <div className="vob-editor-main">
                    {(initial?.review_reason || reviewFeedback?.reason) && <div className="vob-editor-feedback" role="status"><strong>{isId ? 'Catatan moderator' : 'Moderator note'}</strong><p>{reviewFeedback?.reason ?? initial.review_reason}</p></div>}
                    {reviewPending && <div className="vob-editor-feedback" role="status"><strong>{isId ? 'Usulan sedang ditinjau' : 'A revision is under review'}</strong><p>{isId ? 'Tunggu keputusan moderator sebelum mengirim revisi berikutnya.' : 'Wait for the moderator’s decision before sending another revision.'}</p></div>}
                    {mode === 'submission' && <label className="vob-page-field">{isId ? 'Nama objek' : 'Object name'}<input value={objectName} onChange={(event) => setObjectName(event.target.value)} minLength={2} maxLength={255} required /></label>}
                    {mode === 'submission' && <ObjectImageField file={objectImage} onChange={setObjectImage} locale={locale} currentUrl={object.pending_image_path ? `/backend/objects/${object.id}/image?pending=1` : null} />}
                    {isArticle && <label className="vob-page-field">{isId ? 'Judul artikel' : 'Article title'}<input value={title} onChange={(event) => setTitle(event.target.value)} minLength={5} maxLength={255} placeholder={isId ? 'Judul yang spesifik dan mudah dipahami' : 'A clear, specific title'} required /></label>}
                    {isNew && <fieldset className="vob-type-options"><legend>{isId ? 'Jenis artikel' : 'Article type'}</legend><label className={type === 'informative' ? 'selected' : ''}><input type="radio" name="type" value="informative" checked={type === 'informative'} onChange={() => setType('informative')} /><span><strong>{isId ? 'Informatif' : 'Informative'}</strong><small>{isId ? 'Fakta dan penjelasan dengan sumber' : 'Facts and explanations with sources'}</small></span></label><label className={type === 'experience' ? 'selected' : ''}><input type="radio" name="type" value="experience" checked={type === 'experience'} onChange={() => setType('experience')} /><span><strong>{isId ? 'Pengalaman pribadi' : 'Personal experience'}</strong><small>{isId ? 'Cerita langsung dari pengalaman Anda' : 'A first-hand account'}</small></span></label></fieldset>}
                    <div className="vob-page-field"><span>{isId ? 'Isi tulisan' : 'Content'}</span><RichEditor editor={editor} locale={locale} /><small>{bodyLength} {isId ? 'karakter teks' : 'text characters'} · {isId ? 'Minimal 30 karakter' : 'Minimum 30 characters'}</small></div>
                    <label className="vob-page-field">{isId ? 'URL sumber' : 'Source URLs'} <span className="vob-field-qualifier">{type === 'experience' && isArticle ? (isId ? 'opsional' : 'optional') : (isId ? 'wajib' : 'required')}</span><textarea value={sources} onChange={(event) => setSources(event.target.value)} rows={4} placeholder="https://example.com/..." required={mode === 'summary' || type === 'informative'} /><small>{isId ? 'Satu URL https:// per baris, maksimal 10.' : 'One https:// URL per line, maximum 10.'}</small></label>
                    {error && <p role="alert" className="vob-form-error">{error}</p>}
                    <div className="vob-page-actions"><Link href={objectUrl}>{isId ? 'Batal' : 'Cancel'}</Link><button type="submit" disabled={busy || !editor || reviewPending}>{busy ? (isId ? 'Mengirim...' : 'Submitting...') : (isId ? 'Kirim untuk ditinjau' : 'Submit for review')}</button></div>
                </div>
                <aside className="vob-editor-aside"><h2>{isId ? 'Sebelum mengirim' : 'Before you submit'}</h2><p>{isId ? 'Tulisan akan ditinjau moderator. Versi yang sudah terbit tetap terlihat sampai revisi disetujui.' : 'A moderator will review your writing. The published version stays visible until a revision is approved.'}</p><label className="vob-page-field">{isId ? 'Bahasa tulisan' : 'Writing language'}<select value={language} onChange={(event) => setLanguage(event.target.value)}><option value="id">Bahasa Indonesia</option><option value="en">English</option></select></label><p>{isId ? 'Pilih bahasa sesuai isi tulisan. Teks tidak diterjemahkan otomatis.' : 'Choose the language of your text. It is not translated automatically.'}</p></aside>
            </form>
        </main>
    </Layout>;
}
